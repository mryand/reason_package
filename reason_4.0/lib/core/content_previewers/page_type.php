<?php
/**
 * @package reason
 * @subpackage content_previewers
 */
	/**
	 * Register previewer with Reason
	 */
	$GLOBALS[ '_content_previewer_class_names' ][ basename( __FILE__) ] = 'pageTypePreviewer';
	
	/**
	 * Include dependencies
	 */
	reason_include_once('content_previewers/default.php');
	include_once(CARL_UTIL_INC.'basic/json.php');

	/**
	 * A minisite previewer for page types
	 */
	class pageTypePreviewer extends default_previewer
	{
		function init( $id , &$page)
		{
			parent::init( $id, $page );
			if(!empty($this->head_items))
			{
				$this->head_items->add_stylesheet(REASON_ADMIN_CSS_DIRECTORY.
				'previewers/page_type.css');
			}
		}
		function pre_show_entity()
		{
			$this->show_merged_page_type();
			echo '<h3>Entity Data</h3>';
		}
		function get_standard_page_locations_array()
		{
			return array(
				'banner' => array(
					'pre_bluebar', 'pre_banner', 'banner_xtra', 'post_banner',
				),
				'navigation' => array(
					'navigation', 'sub_nav', 'sub_nav_2', 'sub_nav_3',
				),
				'content' => array(
					'main_head', 'main', 'main_post', 'main_post_2', 'main_post_3',
				),
				'related' => array(
					'pre_sidebar', 'sidebar', 'post_sidebar',
				),
				'footer' => array(
					'footer', 'edit_link', 'post_foot',
				),
			);
		}
		function show_merged_page_type()
		{
			if(!empty($this->_entity) && reason_unique_name_exists('default_page_type'))
			{
				
				$page_locations = json_decode($this->_entity->get_value('page_locations'), true);
				$default_locations = array();
				if('default_page_type' != $this->_entity->get_value('unique_name'))
				{
					$default_pt = new entity(id_of('default_page_type'));
					$default_locations = json_decode($default_pt->get_value('page_locations'), true);
				}
				echo '<div class="locationsReport" id="locationsReport">';
				echo '<h3>Page Type Layout</h3>';
				echo '<p class="note">This preview shows a common layout. Theme and page-level styles may produce a different layout.</p>';
				
				$layout_locations = $this->get_standard_page_locations_array();
				$unshown_locations = array_flip(array_unique(array_merge(array_keys($page_locations),array_keys($default_locations))));
				foreach($layout_locations as $name => $locations)
				{
					echo '<ul class="region '.htmlspecialchars($name).'">';
					foreach($locations as $location)
					{
						if(isset($page_locations[$location]))
							$this->show_page_location($location, $page_locations[$location], true);
						elseif(isset($default_locations[$location]))
							$this->show_page_location($location, $default_locations[$location]);
						if(isset($unshown_locations[$location]))
							unset($unshown_locations[$location]);
					}
					echo '</ul>'."\n";
				}
				if(!empty($unshown_locations))
				{
					echo '<div class="region additional">';
					pray($unshown_locations);
					echo '</div>'."\n";
				}
				
				/*
				echo '<ul class="locationList local">';
				foreach($page_locations as $loc => $module_info)
				{
					$this->show_page_location($loc, $module_info);
				}
				echo '</ul>';
				if(!empty($default_locations))
				{
					echo '<h3>Modules Defined by The Default Page Type</h3>';
					echo '<ul class="locationList default">';
					foreach($default_locations as $loc => $module_info)
					{
						$this->show_page_location($loc, $module_info, isset($page_locations[$loc]));
					}
					echo '</ul>';
				}
				*/
				echo '</div>';
			}
		}
		function show_page_location( $loc, $module_info, $local = false )
		{
			$classes = array('location');
			if($local)
				$classes[] = 'local';
			else
				$classes[] = 'nonlocal';
			echo '<li class="'.implode(' ',$classes).'">';
			echo '<div class="locationInfo">';
			echo '<h4>Page Location:</h4>';
			echo '<div class="locationName">'.htmlspecialchars($loc).'</div>';
			echo '</div>';
			echo '<div class="moduleInfo">';
			echo '<h4>Module:</h4>'."\n";
			if(!empty($module_info['module']))
			{
				echo '<div class="moduleName">'.htmlspecialchars($module_info['module']).'</div>';
				$params = $module_info;
				unset($params['module']);
				if(!empty($params))
				{
					echo '<pre class="parameters"><code>';
					echo $this->simplify_json_for_display($params);
					/* foreach($params as $param_name => $param_value)
					{
						echo '<li>';
						echo '<div class="paramName">'.htmlspecialchars($param_name).':</div> ';
						if(is_array($param_value))
							echo json_encode($param_value);
						else
							echo htmlspecialchars($param_value);
						echo '</li>';
					} */
					echo '</code></pre>';
				}
				if($local)
					echo '<div class="note">Defined in '.$this->_entity->get_value('name').'</div>';
				else
					echo '<div class="note">Defined in default</div>';
			}
			else
			{
				echo '<div class="moduleName none">[None]</div>';
				if($local)
					echo '<div class="note">Defined in '.$this->_entity->get_value('name').'</div>';
				else
					echo '<div class="note">Defined in default</div>';
			}
			echo '</div>';
			echo '</li>';
		}
		function simplify_json_for_display($json)
		{
			$json_string = json_format($json);
			$lines = explode("\n",$json_string);
			array_pop($lines);
			array_shift($lines);
			foreach($lines as $key => $line)
			{
				$lines[$key] = substr($line, 1);
			}
			return implode("\n",$lines);
		}
		function show_item_page_locations( $field , $value )
		{
			$this->show_item_json( $field, $value );
		}
		function show_item_meta( $field , $value )
		{
			$this->show_item_json( $field, $value );
		}
		function show_item_json( $field, $value )
		{
			if(strlen($value) > 0)
				$formatted = '<pre>'.htmlspecialchars(json_format($value)).'</pre>';
			else
				$formatted = '';
			$this->show_item_default( $field , $formatted );
		}
	}
?>