<?php
/**
 * @package reason
 * @subpackage content_deleters
 */
	/**
	 * Register deleter with Reason & include parent class
	 */
	$GLOBALS[ '_reason_content_deleters' ][ basename( __FILE__) ] = 'pageTypeDeleter';

	reason_include_once( 'classes/admin/admin_disco.php' );

	/**
	 * A content deleter for page types
	 */
	class pageTypeDeleter extends deleteDisco
	{
		/* To do:
			1. Refuse to delete the default page type, with unique name default_page_type
			2. Offer to update pages that use the page type -- select from list of other page types
		*/
		protected $page_type_pages;
		function on_every_time()
		{
			if(id_of('default_page_type') == $this->get_value( 'id' ) )
			{
				// echo "The default page type is not deletable.";
				$this->show_form = false;
			}
		}
		function get_pages_that_use_page_type()
		{
			if(!isset($this->page_type_pages))
			{
				$this->page_type_pages = array();
				$entity = new entity($this->get_value('id'));
				if($name = $entity->get_value('name'))
				{
					$es = new entity_selector();
					$es->add_type(id_of('minisite_page'));
					$es->add_relation('`page_node`.`custom_page` = "'.addslashes($name).'"');
					$es->limit_tables('page_node');
					$es->limit_fields('custom_page');
					$this->page_type_pages = $es->run_one();
				}
			}
			return $this->page_type_pages;
		}
		function pre_show_form()
		{
			$pages = $this->get_pages_that_use_page_type();
			$count = count($pages);
			if(0 == $count)
			{
				echo '<p>There are no pages that use this page type. Deletion should be clean.</p>';
			}
			elseif(1 == $count)
			{
				echo '<p>There is 1 page that uses this page type. Please change its\' page type before deleting:</p>';
				$this->list_pages($pages);
			}
			elseif(10 >= $count)
			{
				echo '<p>There are '.$count.' pages that use this page type. Please change their page types before deleting:</p>';
				$this->list_pages($pages);
			}
			else
			{
				echo '<p>There are '.$count.' pages that use this page type. Please use <a href="'.REASON_HTTP_BASE_PATH.'scripts/search/find_and_replace.php?active_screen=4&amp;type_id='.id_of('minisite_page').'&amp;type_fields[4]=custom_page">bulk search and replace</a> to change their page types before deleting.</p>';
			}
		}
		function list_pages($pages)
		{
			if(!empty($pages))
			{
				echo '<ul>';
				foreach($pages as $page)
				{
					$owner_site_id = get_owner_site_id( $page->id() );
					echo '<li>';
					if($owner_site_id)
					{
						echo '<a href="?site_id='.$owner_site_id.'&amp;type_id='.id_of('minisite_page').'&amp;id='.$page->id().'&amp;cur_module=Preview" target="_new">'.$page->get_value('name').'</a>';
						
						$owner_site = new entity($owner_site_id);
						echo ' ['.$owner_site->get_value('name').']';
					}
					else
						echo $page->get_value('name').' [Orphaned page]';
					echo '</li>';
				}
				echo '</ul>';
			}
		}
		function no_show_form()
		{
			echo "<p>The default page type cannot be deleted (all other page types depend on it!)</p>";
			echo '<p><a href="?site_id='.htmlspecialchars($this->get_value( 'site_id' )).'&amp;type_id='.htmlspecialchars($this->get_value( 'type_id' )).'&amp;id='.htmlspecialchars($this->get_value( 'id' )).'">Back</a></p>';
		}
		function run_error_checks()
		{
			if(id_of('default_page_type') == $this->get_value( 'id' ) )
			{
				$this->set_error('id', "The default page type is not deletable.");
			}
		}
	}
?>
