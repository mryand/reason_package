<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once('classes/page_type_entities.php');	
	include_once(DISCO_INC.'disco.php');
	
	/**
	 * Administrative module that refreshes the page types cache
	 *
	 * Reason tries to refresh this wherever necessary, but we also provide a way to do this
	 * manually, in case there are any places where the refresh does not happen.
	 */
	class RefreshPageTypesCacheModule extends DefaultModule
	{
		function RefreshPageTypesCacheModule( &$page )
		{
			$this->admin_page =& $page;
		} // }}}
		function init()
		{
			$this->admin_page->title = 'Refresh the Page Type Cache';
		}
		function run()
		{
			if(empty($this->admin_page->request[ 'success' ] ) )
			{
				echo '<p>Page types are cached for performance. Changes to page types should in most cases automatically refresh this cache. However, if you have made changes to page types that are not taking hold, you can force a manual refresh of the page types cache.</p>';
				
				$d = new disco();
				$d->actions = array('refresh' => 'Refresh Page Types Cache');
				$d->add_callback(array($this, 'refresh_page_types_cache'), 'process');
				$d->add_callback(array($this, 'where_to'), 'where_to');
				$d->run();
			}
			else
			{
				echo '<p>Page types cache refreshed.</p>';
				echo '<p><a href="'.$this->admin_page->make_link(array('success' => '')).'">Again?</a></p>';
			}
		}
		function refresh_page_types_cache($disco)
		{
			pageTypeEntities::refresh();
		}
		function where_to($disco)
		{
			return unhtmlentities($this->admin_page->make_link(array('success' => 1)));
		}
	}
?>