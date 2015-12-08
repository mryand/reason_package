<?php
/**
 * This plasmature type provides an interface for selecting page types.
 *
 * @package reason
 * @subpackage classes
 */
include_once( DISCO_INC.'plasmature/plasmature.php' );
reason_include_once('classes/page_type_entities.php');
 
class page_type_pickerType extends defaultType
{
	var $all_available = false;
	var $visible = array();
	var $available = array();
	var $hierarchy = array();
	var $request_url = '';
	var $deprecated = array();
	var $show_names = true;
	var $mode;
	var $definition_urls = array();
	var $type_valid_args = array('hierarchy', 'availability', 'deprecated', 'request_url', 'show_names', 'definition_urls');
	var $listed_page_types = array();
	var $radio_checked = false;
	
	/**
	 * Grab the value from userland.
	 *
	 * Only change value if the current page type is available
	 */
	function grab()
	{
		if('edit' == $this->get_mode())
			return parent::grab();
	}
	/**
	 * Returns the markup for this element.
	 * @return string HTML to display this element.
	 */
	function get_display()
	{
		//return spray($this->hierarchy);
		$i = 0;
		$ret = '<div id="'.$this->name.'_container" class="pageTypePicker '.htmlspecialchars($this->get_mode()).'Mode">'."\n";
		$ret .= '<div class="pageTypeInfo">';
		$ret .= '<strong class="currentPageType">'.$this->page_type_title($this->value).'</strong>';
		$ret .= '</div>';
		$ret .= '<div class="pageTypes">'."\n";
		$no_children = array();
		foreach( $this->hierarchy as $page_type_name => $children )
		{
			if('hidden' != $this->page_type_availability($page_type_name))
			{
				if($pt = pageTypeEntities::entity($page_type_name))
				{
					if(empty($children))
					{
						$no_children[] = $pt;
						continue;
					}
					$ret .= '<div class="section main">';
					$ret .= '<h5>'.$this->page_type_title($pt).'</h5>';
					$ret .= '<div class="sectionContent">';
					$ret .= $this->get_item_markup($pt, $children);
					$ret .= '</div>';
					$ret .= '</div>';
				}
				else
				{
					trigger_error('Hierarchy item '.$page_type_name.' not a page type entity. Not displaying its\' branch.');
				}
			}
		}
		if(!empty($no_children))
		{
			$ret .= '<div class="section others">';
			$ret .= '<h5>Others</h5>';
			$ret .= '<div class="sectionContent">';
			$ret .= '<ul>';
			foreach($no_children as $page_type)
			{
				$ret .= '<li>';
				$ret .= $this->get_item_markup($page_type, array());
				$ret .= '</li>';
			}
			// In case the current page type is a descendant of a hidden page type
			if(!$this->radio_checked)
			{
				$ret .= '<li>';
				$ret .= $this->get_item_markup($this->value, array());
				$ret .= '</li>';
			}
			$ret .= '</ul>';
			$ret .= '</div>';
			$ret .= '</div>';
		}
		$ret .= '</div>'."\n";
		$ret .= '</div>'."\n";
		return $ret;
	}
	
	protected function get_item_markup($page_type, $children)
	{
		$ret = '';
		$name = $page_type->get_value('name');
		$selectable = $this->page_type_selectable($name);
		$this->listed_page_types[] = $name;
		$classes = array('item');
		$classes[] = 'wrapper-'.reason_htmlspecialchars($name);
		if($this->is_current_value($name))
			$classes[] = 'current';
		$classes[] = $selectable ? 'selectable' : 'requestable';
		$ret .= '<div class="'.implode(' ',$classes).'">';
		$ret .= '<div class="pageTypeInfo">';
		$title = $this->page_type_title($page_type);
		$id = $this->name.'--'.$name;
		if($selectable)
		{
			$ret .= '<span class="selection"><input type="radio" id="'.htmlspecialchars($id).'" name="'.htmlspecialchars($this->name).'" 	value="'.htmlspecialchars($name).'"';
			if($this->is_current_value($name))
			{
				$ret .= ' checked="checked"';
				$this->radio_checked = true;
			}
			$ret .= ' /><button class="selector">Select</button><span class="indicator">Selected</span></span>';
			$ret .= ' <strong class="title"><label for="'.htmlspecialchars($id).'">'.$title.'</label></strong>';
			if($this->show_names)
				$ret .= ' <span class="name">'.htmlspecialchars($name).'</span>';
		}
		else
		{
			$ret .= '<span class="selection"><span class="request"><a href="'.$this->request_url.'&amp;page_type='.reason_htmlspecialchars($name).'" target="_blank" title="This page type needs special setup. Send a request to your friendly Reason administrator, and they can get you set up.">Request</a></span></span> <span class="title">'.$title.'</span>';
			if($this->show_names)
				$ret .= ' <span class="name">'.htmlspecialchars($name).'</span>';
		}
		$ret .= '</div>';
		$desc = $page_type->get_value('description');
		$url = $page_type->get_value('example_url');
		$definition_url = isset($this->definition_urls[$name]) ? $this->definition_urls[$name] : '';
		$deprecated = in_array($name, $this->deprecated);
		if($desc || $url || $definition_url || $deprecated)
		{
			$ret .= '<div class="meta">';
			if($deprecated)
				$ret .= '<div class="deprecationNotice">Deprecated (This page type will be removed in the future, so it is not reliable)</div>';
			if($desc)
				$ret .= '<div class="description">'.$desc.'</div>';
			if($url || $definition_url)
			{
				$ret .= '<div class="example">';
				if($url)
					$ret .= '<a href="'.reason_htmlspecialchars($url).'" target="_blank">Example</a> ';
				if($definition_url)
					$ret .= '<a href="'.reason_htmlspecialchars($definition_url).'" target="_blank">Full Definition</a>';
				$ret .= '</div>';
			}
			$ret .= '</div>';
		}
		$ret .= '</div>';
		if(!empty($children))
		{
			$children_markup = '';
			foreach($children as $child_name => $grandchildren)
			{
				if('hidden' == $this->page_type_availability($child_name))
					continue;
				if($child = pageTypeEntities::entity($child_name))
				{
					$children_markup .= '<li>';
					$children_markup .= $this->get_item_markup($child, $grandchildren);
					$children_markup .= '</li>';
				}
				else
				{
					trigger_error('Hierarchy item '.$child_name.' not a page type entity. Not displaying its\' branch.');
				}
			}
			if(!empty($children_markup))
			{
				$ret .= '<div class="children">';
				//$ret .= '<div class="label"><strong>Children</strong></div>';
				$ret .= '<ul>';
				$ret .= $children_markup;
				$ret .= '</ul>';
				$ret .= '</div>';
			}
		}
		return $ret;
	}
	protected function page_type_title($page_type)
	{
		return pageTypeEntities::title($page_type);
	}
	protected function page_type_availability($page_type)
	{
		if(!is_string($page_type))
			$page_type = $page_type->get_value('name');
		if(isset($this->availability[$page_type]))
			return $this->availability[$page_type];
		return 'hidden';
	}
	protected function is_current_value($value)
	{
		if(!isset($this->value) && NULL !== $value )
			return false;
		return ( (string) $value == (string) $this->value );
	}
	protected function get_mode()
	{
		$value = !empty($this->value) ? $this->value : 'default';
		if(!isset($this->mode))
		{
			if('available' == $this->page_type_availability($value))
				$this->mode = 'edit';
			else
				$this->mode = 'request';
		}
		return $this->mode;
	}
	protected function page_type_selectable($page_type_name)
	{
		return ($this->is_current_value($page_type_name) || ('edit' == $this->get_mode() && 'available' == $this->page_type_availability($page_type_name)));
	}
}


?>
