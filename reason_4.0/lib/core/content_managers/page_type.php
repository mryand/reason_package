<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Include the parent class
	 */
	reason_include_once( 'content_managers/parent_child.php3' );
	reason_include_once( 'classes/page_type_entities.php' );
	reason_include_once( 'classes/page_type_availability.php' );
	include_once(CARL_UTIL_INC.'basic/json.php');
	
	/**
	 * Register module with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'PageTypeManager';

	/**
	 * A content manager for page types
	 */
	class PageTypeManager extends parent_childManager
	{
		var $allow_creation_of_root_node = true;
		var $multiple_root_nodes_allowed = true;
		var $root_node_description_text = '** Top-Level Page Type (no parent) **';
		var $box_class = 'stackedBox';
		function init_head_items()
		{
			parent::init_head_items();
			$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH.'content_managers/page_type.js');
			$this->head_items->add_stylesheet(REASON_ADMIN_CSS_DIRECTORY.'content_managers/page_type.css');
		}
		function post_show_form()
		{
			if($this->get_value('name'))
			{
				echo '<div id="relatedInfo">';
				$this->show_page_type_usage();
				echo '</div>';
			}
		}
		function alter_data()
		{
			parent::alter_data();
			
			$this->add_element('general_heading','comment',array('text'=>'<h4>Page Type Definition</h4>'));
			
			if($this->get_value('unique_name') == 'default_page_type')
			{
				$this->change_element_type('name','solidtext');
				$this->change_element_type('parent_id','cloaked');
				$this->add_comments('page_locations','<div><strong>WARNING:</strong> Changes to these page locations affect <strong>ALL</strong> reason pages. Edit this page type with great care.</div>','before');
			}
			else
			{
				$this->add_comments('name',form_comment('Unique key for this page type. May only contain letters, numbers, underscores, and hyphens.<br />NOTE: Changing this will cause any pages with this page type to revert to the default page type. Do so only with great caution.'));
			
				$this->set_display_name('parent_id', 'Parent');
				$this->add_comments('parent_id',form_comment('Determines position in the page type picker'));
			}
			
			$this->add_comments('title',form_comment('A nice, human friendly title for this page type.'));
			
			$this->set_value('page_locations', json_format($this->get_value('page_locations')));
			$this->add_comments('page_locations',form_comment('A JSON array mapping page locations to modules.'));
			
			if('default_page_type' != $this->get_value('unique_name'))
			{
				$default_page_type = pageTypeEntities::entity('default');
				$default_page_locations = array_keys(json_decode($default_page_type->get_value('page_locations'), true));
				$this->add_comments('page_locations',form_comment('Default page locations: '.implode(', ',$default_page_locations)));
				$this->add_comments('page_locations',form_comment('Other page locations may not be recognized by all templates.'));
			}
			$this->add_comments('page_locations',form_comment('<a href="'.$this->admin_page->make_link( array( 'cur_module' => 'Preview' )).'#locationsReport" target="_blank">Page type layout visualization</a>'));
			
			$this->set_element_properties( 'page_locations', array('rows' => 20) );
			
			$this->_no_tidy[] = 'page_locations';
			$this->set_allowable_html_tags('page_locations','all');
			
			$this->add_element('descriptive_heading','comment',array('text'=>'<h4>Descriptive Metadata</h4>'));
			
			$this->set_element_properties( 'description', array('rows' => 4) );
			$this->add_comments('description',form_comment('A short explanation of this page type. Used in the page type picker.'));
			
			$this->set_element_properties( 'note', array('rows' => 6) );
			$this->add_comments('note',form_comment('A longer explanation of this page type. Used in the page content manager to explain to users how to manage the content of the page.'));
			$this->change_element_type( 'note' , html_editor_name($this->admin_page->site_id) , html_editor_params($this->admin_page->site_id, $this->admin_page->user_id) );
			
			$this->add_comments('example_url',form_comment('The URL of an example page.'));
			
			if(strlen($this->get_value('meta')) > 0)
				$this->set_value('meta',json_format($this->get_value('meta')));
			$this->add_comments('meta',form_comment('A JSON array of custom key-value pairs. Primarily used to provide custom metadata to templates.'));
			
			$this->_no_tidy[] = 'meta';
			$this->set_allowable_html_tags('meta','all');
			
			$this->add_element('availability_heading','comment',array('text'=>'<h4>Availability/Visibility</h4>'));
			
			$this->change_element_type('default_availability', 'select_no_sort', array('options'=>array('hidden'=>'Hidden','visible'=>'Visible','available'=>'Available')));
			$this->add_comments('default_availability',form_comment('Available, visible, or hidden for all non-admin users?'));
			
			
			$keys = pageTypeAvailability::get_supported_logic_keys();
			foreach($keys as $k=>$key)
				$keys[$k] = htmlspecialchars($key);
			
			$this->_no_tidy[] = 'availability_logic';
			$this->set_allowable_html_tags('availability_logic','all');
			$this->set_element_properties( 'availability_logic', array('rows' => 4) );
			$this->add_comments('availability_logic',form_comment('Under what conditions should this page type be available?'));
			$this->add_comments('availability_logic',form_comment('Example: site:site_unique_name,site_unique_name_2 OR (theme:theme_unique_name AND user:username)'));
			if(!empty($keys))
				$this->add_comments('availability_logic',form_comment('Options: '.implode(', ',$keys)));
			
			$this->_no_tidy[] = 'visibility_logic';
			$this->set_allowable_html_tags('visibility_logic','all');
			$this->set_element_properties( 'visibility_logic', array('rows' => 4) );
			$this->add_comments('visibility_logic',form_comment('Under what conditions should this page type be visible?'));
			$this->add_comments('visibility_logic',form_comment('Example: site:site_unique_name,site_unique_name_2 OR (theme:theme_unique_name AND user:username)'));
			if(!empty($keys))
				$this->add_comments('visibility_logic',form_comment('Options: '.implode(', ',$keys)));

			$this->set_order(array('general_heading','name','page_locations','descriptive_heading','title','parent_id','description','note','example_url','meta','availability_heading','default_availability','availability_logic','visibility_logic'));
		}
		function run_error_checks()
		{
			if( !$this->has_error( 'name' ) )
			{
				if( !preg_match( "|^[0-9a-z_\-]*$|i" , $this->get_value('name') ) )
				{
					$this->set_error( 'name', 'Page type names may only contain letters, numbers, hyphens, and underscores' );
				}
				else
				{
					$es = new entity_selector();
					$es->add_type(id_of('page_type_type'));
					$es->set_num(1);
					$es->add_relation('`entity`.`name` = "'.addslashes($this->get_value('name')).'"');
					$es->add_relation('`entity`.`id` != "'.addslashes($this->get_value('id')).'"');
					$others = $es->run_one();
					if(!empty($others))
						$this->set_error( 'name', 'The name "'.htmlspecialchars($this->get_value('name')).'" is already taken. Please use another name for this page type.' );
				}
			}
			$locations = $this->get_value('page_locations');
			$locations_array = json_decode($locations, true);
			if(!is_array($locations_array))
			{
				$this->set_error('page_locations','The page locations field must contain a valid json object with page location keys and module information values');
			}
			else
			{
				foreach($locations_array as $loc => $module_info)
				{
					if(!is_array($module_info))
					{
						$this->set_error('page_locations','The page location '.htmlspecialchars($loc).' value must be a module information object, e.g. { "module": "module_name", "parameter1": "Value1" }');
					}
					elseif(!isset($module_info['module']))
					{
						$this->set_error('page_locations','The page location '.htmlspecialchars($loc).' object must contain a "module" key, e.g. { "module": "module_name" }');
					}
				}
			}
			if($this->get_value('meta'))
			{
				$meta = json_decode($this->get_value('meta'), true);
				if(!is_array($meta))
				{
					$this->set_error('meta','The Meta field must be a json object. Other values aren\'t permitted in the Meta field.');
				}
			}
			$parser = new pageTypeRuleParser();
			if($availability_logic = $this->get_value('availability_logic'))
			{
				$logic = $parser->parse($availability_logic);
				if(!is_array($logic))
				{
					$error_text = 'There is an error in the availability logic.';
					$errors = $parser->get_errors();
					if(!empty($errors))
					{
						$error_text .= ' Error report:';
						$error_text .= '<ul>';
						foreach($errors as $error)
							$error_text .= '<li>'.htmlspecialchars($error).'</li>';
						$error_text .= '</ul>';
					}
					$this->set_error('availability_logic', $error_text);
				}
			}
			if($visibility_logic = $this->get_value('visibility_logic'))
			{
				$logic = $parser->parse($visibility_logic);
				if(!is_array($logic))
				{
					$error_text = 'There is an error in the visibility logic.';
					$errors = $parser->get_errors();
					if(!empty($errors))
					{
						$error_text .= ' Error report:';
						$error_text .= '<ul>';
						foreach($errors as $error)
							$error_text .= '<li>'.htmlspecialchars($error).'</li>';
						$error_text .= '</ul>';
					}
					$this->set_error('visibility_logic', $error_text);
				}
			}
		}
		function process()
		{
			parent::process();
			pageTypeEntities::refresh();
		}
		function show_page_type_usage()
		{
			if($name = $this->get_value('name'))
			{
				echo '<div id="pageTypeUsage">';
				$es = new entity_selector();
				$es->add_type(id_of('minisite_page'));
				$es->add_relation('`page_node`.`custom_page` = "'.addslashes($name).'"');
				$es->limit_tables('page_node');
				$es->limit_fields('custom_page');
				$es->set_order('RAND()');
				$pages = $es->run_one();
				$count = count($pages);
				$text = (1 == $count) ? 'Page Uses This Page Type' : 'Pages Use This Page Type';
				echo '<h3>'.$count.' '.$text.'</h3>';
				if($count)
				{
					if(empty($this->admin_page->request['list_all_pages']))
					{
						if($count > 10)
						{
							echo '<h4>10 random pages that use this page type:</h4>';
						}
						$pages_to_display = array_slice($pages, 0, 10);
					}
					else
					{
						echo '<h4>All pages:</h4>';
						$pages_to_display = $pages;
					}
					echo '<ul>';
					foreach($pages_to_display as $page)
					{
						$owner_site_id = get_owner_site_id( $page->id() );
						if($owner_site_id)
						{
							$owner_site = new entity($owner_site_id);
						}
						echo '<li>';
						if($owner_site_id)
							echo '<a href="?site_id='.$owner_site_id.'&amp;type_id='.id_of('minisite_page').'&amp;id='.$page->id().'&amp;cur_module=Preview" target="_new">';
						echo $page->get_value('name');
						if($owner_site_id)
							echo '</a>';
						if($owner_site_id)
							echo ' <em class="ownerSite">['.$owner_site->get_value('name').']</em>';
						else
							echo ' <em class="ownerSite orphaned">[orphaned page]</em>';
						echo '</li>';
					}
					echo '</ul>';
					if($count > 10)
					{
						if(empty($this->admin_page->request['list_all_pages']))
						{
							echo '<p><a href="'.$this->admin_page->make_link(array('list_all_pages' => 1)).'" class="listAll">List all '.$count.' pages that use this page type</a></p>';
						}
						else
						{
							echo '<p><a href="'.$this->admin_page->make_link(array('list_all_pages' => '')).'" class="listAll">List 10 pages</a></p>';
						}
						
					}
				}
				echo '</div>';
			}
		}
	}