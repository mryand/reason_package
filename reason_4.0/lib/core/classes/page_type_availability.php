<?php
/**
 * @package reason
 * @subpackage classes
 */

/**
 * Include dependencies
 */
reason_include_once('classes/page_type_entities.php');
reason_include_once('classes/user.php');

class pageTypeAvailability {
	static function availability($page_type, $user, $page)
	{
		static $site_types = array();
		
		// Sanity check page type input
		if(is_numeric($page_type))
		{
			$test_pt = new entity((integer)$page_type);
			$vals = $test_pt->get_values();
			if(empty($vals))
			{
				trigger_error('Page Type ID '.$page_type.' does not appear to correspond to a Reason entity');
				return NULL;
			}
			$page_type = $test_pt;
		}
		elseif(is_string($page_type))
		{
			if($page_type_entity = pageTypeEntities::entity($page_type))
			{
				$page_type = $page_type_entity;
			}
			else
			{
				trigger_error('Page type not found ('.$page_type.')');
				return NULL;
			}
		}
		elseif(!is_object($page_type))
		{
			trigger_error('A valid page type is required as the first parameter in pageTypeAvailability::availability()');
			return NULL;
		}
		
		if($page_type->get_value('type') != id_of('page_type_type'))
		{
			trigger_error('Page type id '.$user->id().' not a page type');
			return NULL;
		}
		
		
		// Sanity check user input
		if(is_numeric($user))
		{
			$test_user = new entity((integer)$user);
			$vals = $test_user->get_values();
			if(empty($vals))
			{
				trigger_error('User ID '.$user.' does not appear to correspond to a Reason entity');
				return NULL;
			}
			$user = $test_user;
		}
		elseif(is_string($user))
		{
			$u = new User();
			$test_user = $u->get_user($user);
			if(empty($test_user))
			{
				trigger_error('Username '.$user.' not a set-up Reason user');
				return NULL;
			}
			$user = $test_user;
		}
		elseif(!is_object($user))
		{
			trigger_error('A valid user is required as the second parameter in pageTypeAvailability::availability()');
			return NULL;
		}
		
		if($user->get_value('type') != id_of('user'))
		{
			trigger_error('User entity id '.$user->id().' not a user');
			return NULL;
		}
		
		// Sanity check page input
		if(is_numeric($page))
		{
			$test_page = new entity((integer)$page);
			$vals = $test_page->get_values();
			if(empty($vals))
			{
				trigger_error('Page ID '.$page.' does not appear to correspond to a Reason entity.');
				return NULL;
			}
			$page = $test_page;
		}
		elseif(!is_object($page))
		{
			trigger_error('A valid page is required as the third parameter of pageTypeAvailability::availability()');
			return NULL;
		}
		
		if($page->get_value('type') != id_of('minisite_page'))
		{
			trigger_error('Page entity id '.$user->id().' is not a page');
			return NULL;
		}
		
		// Done sanity checks; on to logic
		
		$default_availability = $page_type->get_value('default_availability');
		
		if('available' == $default_availability)
			return 'available';
		
		if($availability_logic = $page_type->get_value('availability_logic'))
		{
			if(empty($parser))
				$parser = new pageTypeRuleParser();
			$logic = $parser->parse($availability_logic);
			if(is_array($logic))
			{
				//echo 'Availability logic: '.$availability_logic;
				//pray($logic);
				if(self::evaluate_logic($logic, $user, $page))
					return 'available';
			}
			else
			{
				trigger_error('Unable to parse page type availability logic for '.$page_type->get_value('name').'. Error message: "'.implode('", "',$parser->get_errors()).'"');
			}
		}
		
		if('visible' == $default_availability)
			return 'visible';
		
		if($visibility_logic = $page_type->get_value('visibility_logic'))
		{
			if(empty($parser))
				$parser = new pageTypeRuleParser();
			$logic = $parser->parse($visibility_logic);
			if(is_array($logic))
			{
				//echo 'Visibility logic: '.$visibility_logic;
				//pray($logic);
				if(self::evaluate_logic($logic, $user, $page))
					return 'visible';
			}
			else
			{
				trigger_error('Unable to parse page type visibility logic for '.$page_type->get_value('name').'. Error message: "'.implode('", "',$parser->get_errors()).'"');
			}
		}
		
		return 'hidden';
	}
	
	protected static function evaluate_logic($logic, $user, $page)
	{
		/*
		Key possibilities:
		NULL
		'AND'
		'OR'
		'NOT'
		(integer)
		(string)
		*/
		
		if(!is_array($logic))
		{
			trigger_error('Unexpected condition: evaluate_logic passed non-array $logic. Returning false.');
			return false;
		}
		
		foreach($logic as $key => $val)
		{
			if('AND' == $key)
			{
				foreach($val as $v)
				{
					if(!self::evaluate_logic($v, $user, $page))
						return false;
				}
				return true;
			}
			elseif('OR' == $key)
			{
				foreach($val as $v)
				{
					if(self::evaluate_logic($v, $user, $page))
						return true;
				}
				return false;
			}
			elseif('NOT' == $key)
			{
				return !self::evaluate_logic($val, $user, $page);
			}
			elseif(is_integer($key))
			{
				if(!is_array($val))
				{
					trigger_error('Unexpected condition: integer key with non-array value. Returning false.');
					return false;
				}
				elseif(count($val) != 1)
				{
					trigger_error('Unexpected condition: integer key with array value of count > 1. Returning false.');
					return false;
				}
				elseif(!is_array($val))
				{
					trigger_error('Unexpected condition: integer key with non-array value. Returning false');
					return false;
				}
				return self::evaluate_logic(current($val), $user, $page);
			}
			elseif(is_string($key))
			{
				// we have an actual key rather than an integer or a logic string
				if(!is_array($val))
				{
					trigger_error('Unexpected condition: string key with non-array value. Returning false.');
					return false;
				}
				foreach($val as $valval) // treat as an OR
				{
					if(!is_string($valval))
					{
						trigger_error('Unexpected condition: non-string value for in strink key array. Not evaluating.');
					}
					if(self::evaluate_key_value_pair($key, $valval, $user, $page))
						return true;
				}
				return false;
			}
		}
		return false;
	}
	
	protected static function evaluate_key_value_pair($key, $value, $user, $page)
	{
		$method = self::get_logic_method($key);
		if(!empty($method))
			return call_user_func($method, $value, $user, $page);
		trigger_error('Unsupported key: '.$key.'. Evaluating as false.');
		return false;
	}
	
	public static function is_supported_logic_key($key)
	{
		if(self::get_logic_method($key))
			return true;
		return false;
	}
	/**
	 * @todo use late static binding technique -- get_called_class() to get current class on statically called method once we no longer support < 5.3.0
	 */
	public static function get_supported_logic_keys()
	{
		static $keys;
		if(!isset($keys))
		{
			$keys = array();
			foreach(get_class_methods(__class__) as $method)
			{
				if(strpos($method, '_evaluate_') === 0)
				{
					$keys[] = substr($method, 10);
				}
			}
		}
		return $keys;
	}
	/**
	 * @todo use late static binding technique -- get_called_class() to get current class on statically called method once we no longer support < 5.3.0
	 */
	protected static function get_logic_method($key)
	{
		$method = array(__class__, '_evaluate_'.$key);
		if(is_callable($method))
			return $method;
		return false;
	}
	
	protected static function _evaluate_user($value, $user, $page)
	{
		return ( $user->get_value('name') == $value );
	}
	
	protected static function _evaluate_pagetype($value, $user, $page)
	{
		if($page->get_value('type') != id_of('minisite_page'))
		{
			trigger_error('Page is not a page! ID: '.$page->id());
		}
		return ( $page->get_value('custom_page') == $value );
	}
	
	protected static function _evaluate_site($value, $user, $page)
	{
		if(reason_unique_name_exists($value) && ( $site = self::get_owner_site($page) ) )
			return ( $site->id() == id_of($value) );
		return false;
	}
	
	protected static function _evaluate_theme($value, $user, $page)
	{
		if(reason_unique_name_exists($value) && ( $theme = self::get_page_theme($page) ) )
			return ( $theme->id() == id_of($value) );
		return false;
	}
	
	protected static function _evaluate_sitetype($value, $user, $page)
	{
		if(reason_unique_name_exists($value) && ( $site_types = self::get_page_sitetypes($page) ) )
		{
			return isset($site_types[id_of($value)]);
		}
		return false;
	}
	
	protected static function _evaluate_type($value, $user, $page)
	{
		if(reason_unique_name_exists($value) && ( $types = self::get_page_types($page) ) )
		{
			return isset($types[id_of($value)]);
		}
		return false;
	}
	
	protected static function _evaluate_homepage($value, $user, $page)
	{
		static $homepages = array();
		if(!isset($homepages[$page->id()]))
		{
			$es = new entity_selector();
			$es->add_type(id_of('minisite_page'));
			$es->add_right_relationship($page->id(), relationship_id_of('minisite_page_parent'));
			$es->add_relation('`entity`.`id` = "'.addslashes($page->id()).'"');
			$es->limit_tables();
			$es->limit_fields();
			$es->set_num(1);
			$pages = $es->run_one();
			$homepages[$page->id()] = !empty($pages);
		}
		
		if(5 == strlen($value) && 'false' == strtolower($value))
			$value = false;
		else
			$value = (boolean) $value;
		
		return ( $value == $homepages[$page->id()] );
	}
	
	protected static function _evaluate_role($value, $user, $page)
	{
		if($roles = reason_user_roles($user->id()))
		{
			//pray($roles);
			return in_array($value, $roles);
		}
		return false;
	}
	
	protected static function get_owner_site($entity)
	{
		static $entities_to_sites = array();
		if(!isset($entities_to_sites[$entity->id()]))
		{
			if($site_id = get_owner_site_id($entity->id()))
				$entities_to_sites[$entity->id()] = new entity($site_id);
			else
				$entities_to_sites[$entity->id()] = false;
		}
		return $entities_to_sites[$entity->id()];
	}
	
	protected static function get_page_theme($page)
	{
		static $page_themes = array();
		if(!isset($page_themes[$page->id()]))
		{
			if($site = self::get_owner_site($page))
			{
				$es = new entity_selector();
				$es->add_type(id_of('theme_type'));
				$es->add_right_relationship($site->id(), relationship_id_of('site_to_theme'));
				$es->limit_tables();
				$es->limit_fields();
				$es->set_num(1);
				$themes = $es->run_one();
				if(!empty($themes))
					$page_themes[$page->id()] = current($themes);
				else
					$page_themes[$page->id()] = false;
			}
			else
			{
				$page_themes[$page->id()] = false;
			}
		}
		return $page_themes[$page->id()];
	}
	
	protected static function get_page_sitetypes($page)
	{
		static $page_sitetypes = array();
		if(!isset($page_sitetypes[$page->id()]))
		{
			if($site = self::get_owner_site($page))
			{
				$es = new entity_selector();
				$es->add_type(id_of('site_type_type'));
				$es->add_right_relationship($site->id(), relationship_id_of('site_to_site_type'));
				$es->limit_tables();
				$es->limit_fields();
				$page_sitetypes[$page->id()] = $es->run_one();
			}
			else
			{
				$page_sitetypes[$page->id()] = array();
			}
		}
		return $page_sitetypes[$page->id()];
	}
	
	protected static function get_page_types($page)
	{
		static $page_types = array();
		if(!isset($page_types[$page->id()]))
		{
			if($site = self::get_owner_site($page))
			{
				$es = new entity_selector();
				$es->add_type(id_of('type'));
				$es->add_right_relationship($site->id(), relationship_id_of('site_to_type'));
				$es->limit_tables();
				$es->limit_fields();
				$page_types[$page->id()] = $es->run_one();
			}
			else
			{
				$page_types[$page->id()] = array();
			}
		}
		return $page_types[$page->id()];
	}
}