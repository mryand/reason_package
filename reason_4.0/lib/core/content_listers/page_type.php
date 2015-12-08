<?php
/**
 * @package reason
 * @subpackage content_listers
 */
/**
 * Include parent class and register viewer with Reason.
 */
reason_include_once( 'content_listers/multiple_root_tree.php' );
$GLOBALS[ '_content_lister_class_names' ][ basename( __FILE__) ] = 'pageTypeTreeLister';

/**
 * A lister for page types
 */
class pageTypeTreeLister extends multiple_root_tree_viewer
{
	var $columns = array(
						'id' => true,
						'name' => true, 
						'availability' => 'show_availability', 
						'last_modified' => 'prettify_mysql_timestamp'
	);
	function show_admin_live( $row , $options)
	{
		echo '<td>';
		if(reason_user_has_privs($this->admin_page->user_id,'edit'))
		{
			echo '<strong>';
			$edit_link = $this->admin_page->make_link(  array( 'cur_module' => 'Editor' , 'id' => $row->id() ) );
			$preview_link = $this->admin_page->make_link(  array( 'cur_module' => 'Preview' , 'id' => $row->id() ) );
			$duplicate_link = $this->admin_page->make_link(  array( 'cur_module' => 'Duplicate' , 'id' => $row->id() ) );
			if (reason_site_can_edit_type($this->admin_page->site_id, $this->admin_page->type_id))
			{
				echo '<a href="' . $preview_link . '">'. 'Preview</a> | <a href="' . $duplicate_link . '">Duplicate</a> | <a href="' . $edit_link . '">Edit</a>';
			}
			else echo '<a href="' . $preview_link . '">'. 'Preview</a>';
			echo '</strong>';
		}
		else
		{
			echo '&nbsp;';
		}
		echo '</td>'."\n";
	}
	/**
	 * @todo add icons... eye is obvious for visible, but what is available, or hidden?
	 */
	function show_availability($row)
	{
		$default_avail = $row->get_value('default_availability');
		$ret = '<div class="availability state-'.reason_htmlspecialchars($default_avail).'">';
		$ret .= '<div class="default">'.$default_avail.'</div>';
		$add = array();
		
		if($row->get_value('availability_logic'))
		{
			$add[] = '<span class="available" title="Available in some cases">Available</span>';
		}
		if($row->get_value('visibility_logic'))
		{
			$add[] = '<span class="visible" title="Visible in some cases">Visible</span>';
		}
		
		if(!empty($add))
			$ret .= '<div class="conditionals smallText">Conditionally '.implode(' &amp; ', $add ).'</div>';
		$ret .= '</div>';
		
		return $ret;
	}
}