<?php
/**
 * @package reason_package
 * @subpackage function_libraries
 */

/**
 * Include dependencies
 */
reason_include_once('classes/page_type_rule_parser.php');
reason_include_once('classes/page_type_availability.php');
	
class pageTypeEntities
{
	
	/**
	 * Get all the Reason page type entities
	 *
	 * Get all the Reason page type entities, keyed by page type name for fast lookup
	 *
	 * Uses both in-memory and object caching, so it should be very fast
	 *
	 * @param boolean $refresh_cache Set to true to pull fresh from the database
	 * @return array Page type entities, keyed by page type name
	 */
	static function entities($refresh_cache = false)
	{
		static $page_types;
		if($refresh_cache || !isset($page_types))
		{
			$page_types = array();
			include_once(CARL_UTIL_INC.'cache/object_cache.php');
			$cache = new ObjectCache('reason_page_type_entities', 86400);
			if($refresh_cache || !($page_types = $cache->fetch()) )
			{
				reason_include_once('classes/entity_selector.php');
				$es = new entity_selector();
				$es->add_type(id_of('page_type_type'));
				$es->set_order('`sort_order` ASC, `entity`.`id` ASC');
				$results = $es->run_one();
				foreach($results as $page_type)
				{
					$page_types[$page_type->get_value('name')] = $page_type;
				}
				$cache->set($page_types);
			}
		}
		return $page_types;
	}
	/**
	 * Get the entity for a particular page type name
	 *
	 * @param string $page_type_name
	 * @param boolean $refresh_cache
	 * @return mixed entity if found; NULL if not
	 */
	static function entity($page_type_name, $refresh_cache = false)
	{
		$entities = self::entities($refresh_cache);
		if(isset($entities[$page_type_name]))
			return $entities[$page_type_name];
		return NULL;
	}
	
	/**
	 * Get an array of page types in the format of the legacy page types array (pre-4.6)
	 *
	 * Array format:
	 *
	 * <pre>
	 * array(
	 * 'default' => array(
	 * 		'pre_bluebar' => '',
	 * 		'main' => 'content',
	 * 		'main_head' => 'page_title',
	 * 		'main_post' => '',
	 * 		'main_post_2' => '',
	 * 		'main_post_3' => '',
	 * 		'edit_link' => 'login_link',
	 * 		'pre_banner' => 'announcements',
	 * 		'banner_xtra' => 'search',
	 * 		'post_banner' => 'navigation_top',
	 * 		'pre_sidebar' => 'assets',
	 * 		'sidebar' => 'image_sidebar',
	 * 		'post_sidebar' => '',
	 * 		'navigation' => 'navigation',
	 * 		'footer' => 'maintained',
	 * 		'sub_nav' => 'blurb',
	 * 		'sub_nav_2' => '',
	 * 		'sub_nav_3' => '',
	 * 		'post_foot' => '',
	 * 		'_meta' => array(
	 * 			
	 * 		),
	 * 	),
	 * ...
	 * );
	 *
	 * </pre>
	 *
	 * Uses both in-memory and object caching, so it should be very fast.
	 *
	 * @param boolean $refresh_cache Set to true to pull fresh from the database
	 * @return array Legacy-format page types array
	 */
	static function legacy_array($refresh_cache = false)
	{
		static $legacy_page_types;
		if($refresh_cache || !isset($legacy_page_types))
		{
			$legacy_page_types = array();
			include_once(CARL_UTIL_INC.'cache/object_cache.php');
			$cache = new ObjectCache('reason_legacy_page_types_array', 86400);
			if($refresh_cache || !($legacy_page_types = $cache->fetch()) )
			{
				$page_types = self::entities($refresh_cache);
				if(!empty($page_types))
				{
					foreach($page_types as $pt)
					{
						$page_type = array();
						if($locations = json_decode($pt->get_value('page_locations'), true))
							$page_type = $locations;
						if($meta = json_decode($pt->get_value('meta')))
							$page_type['_meta'] = $meta;
						$legacy_page_types[$pt->get_value('name')] = $page_type;
					}
				}
				$cache->set($legacy_page_types);
			}
		}
		return $legacy_page_types;
	}
	
	static function hierarchy($refresh_cache = false)
	{
		static $hierarchy;
		if($refresh_cache || !isset($hierarchy))
		{
			$hierarchy = array();
			include_once(CARL_UTIL_INC.'cache/object_cache.php');
			$cache = new ObjectCache('reason_page_types_hierarchy', 86400);
			if($refresh_cache || !($hierarchy = $cache->fetch()) )
			{
				$page_types = self::entities($refresh_cache);
				if(!empty($page_types))
				{
					foreach($page_types as $pt)
					{
						$name = $pt->get_value('name');
						$parent = self::parent($name);
						if(empty($parent))
						{
							$hierarchy[$name] = self::build_hierarchy($name);
						}
					}
				}
				$cache->set($hierarchy);
			}
		}
		return $hierarchy;
	}
	
	static function build_hierarchy($page_type_name)
	{
		$ret = array();
		$children = self::children($page_type_name, $refresh_cache = false);
		if(!empty($children))
		{
			foreach($children as $child)
			{
				$ret[$child->get_value('name')] = self::build_hierarchy($child->get_value('name'));
			}
		}
		return $ret;
	}
	
	static function children($page_type_name, $refresh_cache = false)
	{
		static $all_children = array();
		if(!isset($all_children[$page_type_name]))
		{
			$pts = self::entities($refresh_cache);
			if(isset($pts[$page_type_name]))
			{
				$pt = $pts[$page_type_name];
				$children = array();
				foreach($pts[$page_type_name]->get_right_relationship('page_type_parent') as $key => $child)
				{
					if($child->id() != $pt->id())
						$children[$child->get_value('sort_order')] = $child;
				}
				ksort($children);
				$all_children[$page_type_name] = $children;
			}
		}
		return $all_children[$page_type_name];
	}
	
	static function parent($page_type_name, $refresh_cache = false)
	{
		static $parents = array();
		if(!isset($parents[$page_type_name]))
		{
			$pts = self::entities($refresh_cache);
			if($pt = self::entity($page_type_name))
			{
				$parents = $pt->get_left_relationship('page_type_parent');
				$parent = reset($parents);
				if($parent && $parent->id() != $pt->id())
					$parents[$page_type_name] = $parent;
				else
					$parents[$page_type_name] = false;
			}
			else
			{
				$parents[$page_type_name] = false;
			}
		}
		return $parents[$page_type_name];
	}
	
	static function availability($page_type, $user, $page)
	{
		return pageTypeAvailability::availability($page_type, $user, $page);
	}
	
	static function title($page_type)
	{
		if(is_string($page_type))
			$page_type = self::entity($page_type);
		if(empty($page_type))
			return '';
		if($title = $page_type->get_value('title'))
			return $title;
		else
			return reason_htmlspecialchars(prettify_string($page_type->get_value('name')));
	}
	
	/**
	 * Update all page type caches
	 *
	 * Since individual functions do their own caching, this function is the most reliable way to ensure
	 * that all page-type-related caches are updated.
	 *
	 * @return void
	 */
	static function refresh()
	{
		self::legacy_array(true);
		self::hierarchy(true);
	}
	
}