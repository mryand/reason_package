<?php
/**
 * Default Minisite Navigation
 * @package Reason_Core
 */
 
 	/**
	 * Include the Reason Header and the Tree Lister (which this extends)
	 */
	include_once( 'reason_header.php' );
	reason_include_once( '/content_listers/tree.php3' );

	/**
	 * Default Minisite Navigation Class
	 *
	 * Class used for building and displaying minisite navigation
	 */
	class MinisiteNavigation extends tree_viewer
	{
		var $nice_urls = array();
	
		// Zero shows the root; 1 shows top-level; etc.
		var $start_depth = 0;
		var $display_parent_of_open_branch = false;
		var $link_to_current_page = false;

		function make_tree( &$item , &$root , $depth ) // {{{
		{
			$display_item = false;
			if($this->should_show_children($item))
			{
				$children = $this->children( $item );
			}
			else
			{
				$children = array();
			}
			if ( $depth >= $this->start_depth )
			{
				$display_item = true;
			}
			elseif( $this->display_parent_of_open_branch && $depth == $this->start_depth-1 && $this->is_open( $item ) && !empty( $children ) )
			{
				$display_item = true;
			}
			if( $display_item )
			{
				$open = $this->is_open( $item );
				$class = $this->get_item_class($item, $open, $depth);
				echo '<li class="navListItem '.$class.'">';
				$this->show_item( $this->values[ $item  ] );
				if( $open AND !empty( $children ))
				{
					echo '<ul class="navList">';
					reset( $children );
					while( list( , $child) = each( $children ) )
					{
						$c = $this->values[ $child ];
						if( $c->get_value( 'nav_display' ) == 'Yes' )
							$this->make_tree( $child , $root, $depth +1);
					}
					echo '</ul>';
				}
				echo '</li>';
			}
			else
			{
				if( $this->is_open( $item ) AND !empty( $children ) )
				{
					reset( $children );
					while( list( , $child) = each( $children ) )
					{
						$c = $this->values[ $child ];
						if( $c->get_value( 'nav_display' ) == 'Yes' )
							$this->make_tree( $child , $root, $depth +1);
					}
				}
			}
		} // }}}
		
		function get_item_class($item, $open)
		{
			if($open)
				$class = 'open';
			else
				$class = 'closed';
			return $class;
		}
		
		function show_all_items() // {{{
		{
			$root = $this->root_node();
			echo '<ul class="navListTop">';
			$this->make_tree( $root , $root , 0);
			echo '</ul>'."\n";
		} // }}}
		function should_show_children($id)
		{
			return true;
		}
		/**
		 * Forces the current page to be a link rather than unlinked text
		 *
		 * Use this method if the current page is in a mode which the user might want to exit
		 * @return void
		 */
		function make_current_page_a_link()
		{
			$this->link_to_current_page = true;
		}
		function show_item( &$item , $options = false) // {{{
		{
			$page_name = $item->get_value( 'link_name' ) ? $item->get_value( 'link_name' ) : $item->get_value('name');
			// Show home instead of site name again
			// notice, this overrides the link_name if set above
			if( $item->id() == $this->root_node() )
				$page_name = '<span>'.$this->site_info->get_value('name').' Home</span>';
			$page_name = strip_tags($page_name,'<span><strong><em>');
			if( $this->cur_page_id != $item->id() || $this->link_to_current_page )
			{
				$link = $this->get_full_url($item->id());
				
				// if the selected page should not be shown in the nav, then we should highlight the parent of the
				// invisible page.  This code checks to see if the current page is the parent of the selected
				// page and checks if the selected page should not be shown.
				// It also checks to see if the current page is the same as the item id in the case where link_to_urrent_page is set
				if($this->cur_page_id == $item->id()
					||
					( $this->values[ $this->cur_page_id ]->get_value( 'parent_id' ) == $item->id() AND
					$this->values[ $this->cur_page_id ]->get_value( 'nav_display' ) == 'No' )
				)
				{
					$prepend = '<strong>';
					$append = '</strong>';
				}
				else
				{
					$prepend = '';
					$append = '';
				}
				
				$link = '<a href="'.$link.'">'.$prepend.$page_name.$append.'</a>';

				echo $link;
			}
			else
				echo '<strong>'.$page_name.'</strong>';
		} //  }}}
		function modify_base_url($base_url) // {{{
		// for extending
		{
			return $base_url;
		} // }}}
		/**
		 * Recursive function to build the path of a page inside the site
		 *
		 * This method does not return a usable URL -- it only provides the part of the full URL 
		 * that comes after the site's base URL.
		 * If you want to get a usable URL, try the method get_url_from_base() or get_full_url().
		 *
		 * @param integer $id The ID of the page
		 * @param integer $depth The depth from the first get_nice_url() called in this stack
		 * @return string The url of the page (relative to the base url of the site)
		 */
		function get_nice_url( $id, $depth = 1) // {{{
		{
			$ret = false;
			if($depth > 60) // deeper than 60 we figure there is a problem
			{
				trigger_error('Apparent infinite loop; maximum get_nice_url() depth of 60 reached (id '.$id.')');
				return false;
			}
			if(!empty($id))
			{
				if( isset( $this->values[ $id ] ) )
				{
					if( isset( $this->nice_urls[ $id ] ) )
						$ret = $this->nice_urls[ $id ];
					elseif( $id == $this->root_node() )
					{
						$this->nice_urls[ $this->root_node() ] = $this->values[ $this->root_node() ]->get_value( 'url_fragment' );
						$ret = $this->nice_urls[ $this->root_node() ];
					}
					else
					{
						$depth++;
						$p_id = $this->parent( $id );
						if(isset($this->values[ $p_id ])) // need to check or else we will call get_nice_url on a page not in the site
						{
							$ret = $this->get_nice_url( $p_id, $depth ).'/'.$this->values[ $id ]->get_value( 'url_fragment' );
						}
					}
				}
				else
				{
					trigger_error('get_nice_url() called with an id not in site ('.$id.') at depth '.$depth);
				}
			}
			else
			{
				trigger_error('get_nice_url() called with an empty id at depth '.$depth);
			}
			return $ret;
		} // }}}
		/**
		 * Gets the path to a page from the server root
		 *
		 * If you want textonly inclusion and/or awareness of external URL pages, you might try get_full_url().
		 *
		 * @param integer $id The ID of the page
		 * @return string The url of the page (relative to the server root)
		 */
		function get_url_from_base( $id )
		{
			static $base_url;
			static $base_prepped = false;
			if(!$base_prepped)
			{
				$trimmed_base = trim_slashes($this->site_info->get_value( 'base_url' ));
				if(empty($trimmed_base))
					$base_url = '';
				else
					$base_url = '/'.$trimmed_base;
				$base_url = $this->modify_base_url($base_url);
			}
			return $base_url.$this->get_nice_url( $id ).'/';
		}
		/**
		 * Gets the full url of the page
		 *
		 * If the page is an external link, this method returns the page's url value.
		 * 
		 * Otherwise, it returns a url that conforms to the parameters given.
		 * This method pays attention to the textonly value of the page tree object, and appends that value if it exists.
		 *
		 * @param integer $id The ID of the page
		 * @param boolean $as_uri If true, provides a fully qualified URL (e.g. a URI, like: http://www.somesite.com/sitebase/page/path/) If false, provides a URL relative to the base of the server
		 * @param boolean $secure If true, uses https; otherwise uses http. This param only has an effect if $as_uri is true
		 * @return string The url of the page
		 */
		function get_full_url( $id, $as_uri = false, $secure = false )
		{
			if(empty($this->values[ $id ]))
			{
				return false;
			}
			else
			{
				$item =& $this->values[ $id ];
				if( !$item->get_value( 'url' ) )
				{
					$link = $this->get_url_from_base( $id );
					if ( !empty( $this->textonly ) )
						$link .= '?textonly=1';
					if($as_uri)
					{
						if($secure)
						{
							$link = 'https://'.REASON_HOST.$link;
						}
						else
						{
							$link = 'http://'.REASON_HOST.$link;
						}
					}
				}
				else
				{
					$link = $item->get_value( 'url' );
				}
				return $link;
			}
		}
	}
?>
