<?php
	/**
	 * {{{
	 * This class sets the stage for listing the content of a type
	 * by creating a class that can be overloaded.
	 *
	 * The viewer attempts to grab as much data as possible from
	 * the database and from global $_REQUEST variables.  It works by
	 * taking and entity_selector and manipulating the results according 
	 * to the specified functions in display.  It is broken 
	 * down into the major components that one would use when 
	 * attempting to display data from the database.  Some functions 
	 * are not defined and may require overloading.
	 *
	 * ***************MAIN OVERLOADABLE FUNCTIONS********************
	 *
	 * show_no_results:  the function defines what to do if no results
	 * are found from the database query.
	 *
	 * show_sorting:  provides a list of possible sorting options, 
	 * usually at the top of the list and periodically throughout, if 
	 * the list is long enough.  It may simple be column headers.
	 *
	 * show_paging:  shows paging, allowing the user to walk through
	 * the data set.
	 *
	 * show_filters:  show a list of filters.
	 *
	 * show_all_items:  usually, this should just go through the values
	 * and call show_item for each one.
	 *
	 * show_item:  used to display each item in the value set.
	 *
	 * **********************USING THE VIEWER************************
	 *
	 * The viewer is usually used in the following manner( or 
	 * something similar):
	 *
	 * <code>
	 * $v = new {class that extends viewer}();
	 * $v->init( $site_id , $type_id, $viewer_id );
	 * $v->do_display();
	 * </code>
	 *
	 * The type id is required.  The site id is pretty much required 
	 * as well, although a small change to init could result in the 
	 * entity_selector pulling from all sites.  The viewer id is 
	 * optional.  If the viewer gets one, it will try and pull certain
	 * information from the database which it will use in the display
	 * functions.
	 *
	 * }}}
	 * @author Brendon Stanton and Dave Hendler
	 * @package Reason_Core
	 */
	
	/**
	 * make sure class is only included once
	 */
	if(!defined( '__VIEWER_CLASS' ))
	{
		define('__VIEWER_CLASS', true );

		/**
		 * This class sets the stage for listing the content of a type
		 * by creating a class that can be overloaded.
		 *
		 * The viewer attempts to grab as much data as possible from
		 * the database and from global $_REQUEST variables.  It works by
		 * taking and entity_selector and manipulating the results according 
		 * to the specified functions in display.  It is broken 
		 * down into the major components that one would use when 
		 * attempting to display data from the database.  Some functions 
		 * are not defined and may require overloading.
		 */
		class Viewer
		{
			/**#@+
			 * @access public
			 */
			var $site_id;
			var $type_id;
			var $viewer_id;

			var $alias = array(); //for aliased variables
			
			var $filters;
			var $active_filters = false;
			var $es;
			var $values;
			var $columns;
			var $num_per_page = 40;
			var $page = '';
			var $rows_per_sorting = 20;
			var $dir = '';
			var $order_by = '';
			
			var $state = '';
			var $assoc = false;
			var $filter_es_name = 'es';

			/**
			 * Default columns that are modified by prettify_mysql_datetime function
			 */
			var $datetime = array( 'datetime' => true, 'event_start' => true );
			/**
			 * Default columns that are modified by prettify_mysql_timestamp function
			 */
			var $timestamp = array( 'last_modified' =>true , 'creation_date' => true );
			/**#@-*/

			/**
			 * Overloadable function meant for altering the filters (if needed)
			 * @return void
			 */
			function alter_filters() // {{{
			{
				//overload
			} // }}}
			/**
			 * Adds a filter (must be called before filters are initialized)
			 * @param string $name Name of Field
			 * @return void
			 */
			function add_filter( $name ) // {{{
			{
				$this->filters[ $name ] = true;
			} // }}}
			/**
			 * Removes a filter (must be called before filters are initialized)
			 * @param string $name Name of Field
			 * @return void
			 */
			function remove_filter( $name ) // {{{
			{
				if( isset( $this->filters[ $name ] ) )
					unset( $this->filters[ $name ] );
			} // }}}

			/**
			 * Overloadable function meant for adding or removing columns
			 * @return void
			 */
			function alter_columns() // {{{
			{
				//overload
			} // }}}
			/**
			 * Adds a column.  The options paramater can be updated to do sophisticate data handling.
			 * <code>
			 * $v->add_column( 'name' )
			 * // better example forthcoming
			 * </code>
			 * @param string $name Name of the column
			 * @param mixed $options Either an array, string or the boolean true.  Used to handle advanced column display
			 * @return void
			 */
			function add_column( $name , $options=true) // {{{
			{
				$this->columns[ $name ] = $options;
			} // }}}
			/**
			 * Gets rid of a column.
			 * @param string $name name of column to remove
			 * @return void
			 */
			function remove_column( $name ) // {{{
			{
				if( isset( $this->columns[ $name ] ))
					unset( $this->columns[ $name ] );
			} // }}}
			/**
			 * Draws from the reason DB to set column order
			 * @return void
			 */
			function set_column_order() // {{{
			{
				if( $this->viewer_id )
				{
					$v = new entity( $this->viewer_id );
					$order = explode( ',' , $v->get_value( 'column_order' ) );
					if( $order );
						$this->sort_columns( $order );
				}
			} // }}}
			/**
			 * Reorders the columns
			 * @param array $order the new order of columns
			 * @return void
			 */
			function sort_columns( $order ) // {{{
			{
				if( is_array( $order ) )	
				{
					$elements = $this->columns;
					
					$new_elements = array();
					reset( $order );
					while( list(  , $name ) = each( $order ) )
					{
						$name = trim($name);
						if( isset( $elements[ $name ] ) AND $elements[ $name ] )
						{
							$new_elements[ $name ] = $elements[ $name ];
							unset( $elements[ $name ] );
						}
					}
					reset( $elements );
					while( list( $name , $value ) = each( $elements ) )
						$new_elements[ $name ] = $value;
					$this->columns = $new_elements;
				}
			} // }}}
			
			/**#@+
			 * Adds a column that is actually a relationship and not part of the entity
			 * @param string $rel_name name of relationship
			 * @param string $table name of the table where the field is
			 * @param string $field name of the column to select
			 * @param string $alias alias for field name, this will show up as the column name by default
			 */
			function add_left_relationship_column( $rel_name, $table, $field , $alias ) // {{{
			{
				$this->alias = $this->alias + 
							$this->es->add_left_relationship_field( $rel_name , $table , $field , $alias );
				$this->add_column( $alias );
			} // }}}
			function add_right_relationship_column( $rel_name, $table, $field , $alias ) // {{{
			{
				$this->alias = $this->alias + 
							$this->es->add_right_relationship_field( $rel_name , $table , $field , $alias );
				$this->add_column( $alias );
			} // }}}
			/**#@-*/
			
			/**
			 * Overloadable function.  Do ANYTHING you want!!!!
			 */
			function alter_values() // {{{
			{
				//overload
			} // }}}
			
			/**
			 * Gets all the appropriate globals and stores them into the class
			 */
			 
			// SECURITY RISK - class variables extended from viewer.php3 that are defined as 0 or null will be set to
			// any post or get variables named the same as the class variable - no filtering or other checking is done.
			function grab_globals() // {{{
			{
				$this->request = isset( $this->page->request ) ? $this->page->request : array_diff( conditional_stripslashes($_REQUEST), conditional_stripslashes($_COOKIE) );
				foreach( $this->request AS $name => $value )
				{
					if( (isset( $this->$name ) ) AND ( $name != 'es' ) AND (empty( $this->$name )  || $name == 'dir' || $name == 'order_by' ) )
					{
						$this->$name = $value;
					}
				}
				if( !$this->page )
					$this->page = 1;
				if( !$this->state )
					$this->state = 'Live';
			} // }}}
			/**
			 * Scans the global request variables for search variables and adds the
			 * appropriate relations to the entity_selector
			 */
			function grab_filters() // {{{
			{
				foreach( $this->filters AS $name => $value )
				{
					if( $value )
					{
						$key = 'search_' . $name;
						if( !empty( $this->admin_page->request[ $key ] ) )
						{
							$value = addslashes($this->admin_page->request[ $key ]);
							$this->active_filters = true;
							$alias = isset( $this->alias[ $name ] ) ? $this->alias[ $name ] : '';
							if( $alias )  //first, check aliases
								$table = $alias[ 'table' ] . '.' . $alias[ 'field' ];
							else //then check normal values
								$table = table_of( $name , $this->type_id);
							if($table)  //if we've found one, add the relation
							{
								if( $this->is_exact_match( $name ) )
									$this->add_exact_filter( $table , $value );
								elseif( $this->is_less_than_match( $name ) )
									$this->add_less_than_filter( $table , $value );
								elseif( $this->is_greater_than_match( $name ) )
									$this->add_greater_than_filter( $table , $value );
								elseif( $this->is_less_than_equal_match( $name ) )
									$this->add_less_than_equal_filter( $table , $value );
								elseif( $this->is_greater_than_equal_match( $name ) )
									$this->add_greater_than_equal_filter( $table , $value );
								else
									$this->add_like_filter(  $table , $value );
							}
						}
					}
				}
			} // }}}

			/**#@+
			 * Adds a relation of the correct type
			 * @param string $table name of the table/field in the form <table>.<field>
			 * @param mixed $value value against which the field will be tested
			 * @return void
			 */
			function add_exact_filter( $table , $value ) // {{{
			{
				$filter_es_name = $this->filter_es_name;
				$this->$filter_es_name->add_relation( $table . ' = "'. $value . '"' );
			} // }}}
			function add_like_filter( $table , $value ) // {{{
			{
				$filter_es_name = $this->filter_es_name;
				$this->$filter_es_name->add_relation( $table . ' LIKE "%'. $value . '%"' );
			} // }}}
			function add_less_than_filter( $table , $value ) // {{{
			{
				$filter_es_name = $this->filter_es_name;
				$this->$filter_es_name->add_relation( $table . ' < "'. $value . '"' );
			} // }}}
			function add_less_than_equal_filter( $table , $value ) // {{{
			{
				$filter_es_name = $this->filter_es_name;
				$this->$filter_es_name->add_relation( $table . ' <= "'. $value . '"' );
			} // }}}
			function add_greater_than_filter( $table , $value ) // {{{
			{
				$filter_es_name = $this->filter_es_name;
				$this->$filter_es_name->add_relation( $table . ' > "'. $value . '"' );
			} // }}}
			function add_greater_than_equal_filter( $table , $value ) // {{{
			{
				$filter_es_name = $this->filter_es_name;
				$this->$filter_es_name->add_relation( $table . ' >= "'. $value . '"' );
			} // }}}
			/**#@-*/

			/**#@+
			 * Scans the request variable and returns true if the variable name is a search of the given type
			 * @param string $name name of field
			 * @return boolean
			 */
			function is_exact_match( $name ) // {{{
			{
				return !empty( $this->admin_page->request[ 'search_exact_' . $name ] );
			} // }}}
			function is_less_than_match( $name ) // {{{
			{
				return !empty( $this->admin_page->request[ 'search_less_than_' . $name ] );
			} // }}}
			function is_less_than_equal_match( $name ) // {{{
			{
				return !empty( $this->admin_page->request[ 'search_less_than_equal_' . $name ] );
			} // }}}
			function is_greater_than_match( $name ) // {{{
			{
				return !empty( $this->admin_page->request[ 'search_greater_than_' . $name ] );
			} // }}}
			function is_greater_than_equal_match( $name ) // {{{
			{
				return !empty( $this->admin_page->request[ 'search_greater_than_equal_' . $name ] );
			} // }}}
			/**#@-*/
			
			/**
			 * Grabs the sort from known info and adds the appropriate sorting info to the entity_selector
			 */
			function grab_sort() // {{{
			{
				if(!empty($this->order_by))
				{
					$alias = isset( $this->alias[ $this->order_by ] ) ? $this->alias[ $this->order_by ] : '';
					if( $alias )  //first, check aliases
						$table = $alias[ 'table' ] . '.' . $alias[ 'field' ];
					else //then check normal values
						$table = table_of( $this->order_by , $this->type_id);
					if($table)  //if we've found one, add the relation
						$this->es->set_order($table . ' ' . $this->dir);
				}
			} // }}}
			/**
			 * Does all the basic initializing functions.
			 * @param int $site_id id of current site
			 * @param int $type_id id of current type
			 * @param mixed $viewer_id if set, the id of the viewer in the reason database
			 * @return void
			 */
			function init( $site_id , $type_id , $viewer_id = false) // {{{
			{
				$this->site_id = $site_id;
				$this->type_id = $type_id;
				$this->viewer_id = $viewer_id;
				if( !$this->num_per_page )
					$this->num_per_page = 20;
				if( !$this->rows_per_sorting )
					$this->rows_per_sorting = 20;
				if( !$this->filters )
					$this->filters = array( 'id' => true , 'name' => true);
				if( !$this->columns )
					$this->columns = array();
				
				$this->viewer_columns();
				$this->viewer_searchable_fields();
				$this->viewer_default_sort();
				$this->alter_columns();
				$this->alter_filters();
				$this->es = new entity_selector( $site_id );
				$this->es->add_type( $this->type_id );
				$this->es->set_sharing( 'owns' );
				$this->alter_values();
				$this->grab_globals();
				$this->grab_sort();
				$this->grab_filters();
				$this->set_column_order();
				$this->load_values();
			} // }}}
			/**
			 * Check bounds of page, make sure it's in range 
			 * Sets up appropriate variables if not already set
			 * @return void
			 */
			function check_bounds() // {{{
			{
				if( empty( $this->page ) ) $this->page = 1;
				$count = $this->es->get_one_count($this->state);
				$max_page = ceil( $count / $this->num_per_page );
				if( $this->page > $max_page ) $this->page = $max_page;
				if( $this->page < 1 ) $this->page = 1;
			} // }}}
			/**
			 * Runs the es and stores the values in $this->values
			 * @return void
			 */
			function load_values() // {{{
			{
				$es = $this->es;
				if( isset( $this->num_per_page ) && isset( $this->page ) )
				{
					$this->check_bounds();
					$es->set_start( ($this->page - 1 ) * ( $this->num_per_page ) );
					$es->set_num( $this->num_per_page );
					if(!$es->orderby)
					{
						$es->set_order( 'entity.last_modified DESC' );
					}
				}
				$this->values = $es->run_one($this->type_id, $this->state);
				$this->es = $es;
			} // }}}

			/**
			 * Either displays the list or calls show_no_results() if there aren't any
			 * @return void
			 */
			function do_display() // {{{
			{
				if( $this->values )
					$this->display();
				else
					$this->show_no_results();
			} // }}}
			/**
			 * Does the actual displaying once the viewer has determined there are values.
			 * @return void
			 */
			function display() // {{{
			{
				$this->show_filters();
				$this->show_all_items();
				echo '<br />';
				$this->show_paging();
			} // }}}

			/**
			 * Creates a link based on all the current data that is available.
			 *
			 * If values is set up, it will overwrite any of the default info
			 * <code>
			 * $this->get_link( array( 'page' => 2 , 'search_name' => 'q' ) );
			 * </code>
			 * The above example will create a link to a page with the same variables as here,
			 * but will change page to 2 and search_name to "q".  If either of these values don't
			 * currently exist, they will be added
			 * @param array $values array of values to add/change
			 * @return string new link string
			 */
			function get_link( $values = false )  // returns a string, containing all the current get info{{{
			//values contains an array of variables to be overwritten (i.e if $values = array( 'page' => 1 )
			//then the value 1 is used in page rather than the current get value
			{
				if(!$values) $values = array();
				$string = '?';
				$first = true;
				foreach( $this->request AS $name => $value )
				{
					if(!$first)
						$string .= '&amp;';
					$first = false;
					$string .= $name . '=';
					if(isset( $values[ $name ] ) AND $values[ $name ] )
					{
						$string .= $values[ $name ];
						unset( $values[ $name ] );
					}
					else $string .= $value;
				}
				reset( $values );
				while( list($name, $value) = each($values))
				{
					if(!$first)
						$string .= '&amp;';
					$first = false;
					$string .= $name . '=';
					$string .= $value;
				}
				return $string;				
			} // }}}

			/**
			 * Sets up viewer columns, attempts to grab from DB
			 * @return void
			 */
			function viewer_columns() // {{{
			{
				if($this->viewer_id)
				{
					$e = new entity( $this->viewer_id );
					$fields = get_fields_by_type( $this->type_id );
					$column_add = $e->get_left_relationship( 'view_columns' );
					foreach( $column_add AS $value )
					{
						if(isset( $this->datetime[ $value->get_value( 'name' ) ] ) AND $this->datetime[ $value->get_value( 'name' ) ] )
							$options = 'prettify_mysql_datetime' ;
						elseif(isset( $this->timestamp[ $value->get_value( 'name' ) ] ) AND $this->timestamp[ $value->get_value( 'name' ) ] )
							$options = 'prettify_mysql_timestamp';
						else
							$options = true;

							
						if(!empty( $fields[ $value->get_value( 'name' ) ] ) )
							$this->add_column( $value->get_value( 'name' ), $options );
					}
				}
			} // }}}
			/**
			 * Checks DB for all possible searchable_fields
			 * @return void
			 */
			function viewer_searchable_fields() // {{{
			{
				if($this->viewer_id)
				{
					$e = new entity( $this->viewer_id );
					$fields = get_fields_by_type( $this->type_id );
					$column_add = $e->get_left_relationship( 'view_searchable_fields' );
					foreach( $column_add AS $value )
					{
						if(!empty($fields[ $value->get_value( 'name' ) ] ) )
							$this->add_filter( $value->get_value( 'name' ) );
					}
				}
			} // }}}
			/**
			 * Gets default sorting info from DB.  
			 *
			 * This is important when a user first enters a page, since reason needs to know how to sort
			 * @return void
			 */
			function viewer_default_sort() // {{{
			{
				if( $this->viewer_id )
				{
					$e = new entity( $this->viewer_id );
					$x = $e->get_value( 'default_sort' );
					$sort = explode( ',' , $x );
					
					$name = trim( isset($sort[0]) ? $sort[0] : '' );
					$dir = trim( isset($sort[1]) ? $sort[1] : '' );
					
					if( $name )
						$this->order_by = $name;
					if( $dir )
						$this->dir = $dir;

					$num = $e->get_value( 'num_per_page' );
					if( $num )
						$this->num_per_page = $num;
				}
			} // }}}
			  
			  //------------------------------//
			 //--+= overloadable methods =+--//
			//------------------------------//

			/**#@+
			 * Overloadable method.  Used for display of page
			 * @return void
			 */
			function show_no_results() // {{{
			{
				echo '<br />';
				if( $this->active_filters )
					$this->show_no_search_results();
				else
					$this->show_no_items();
			} // }}}
			function show_no_search_results() // {{{
			{
				echo 'No items matched your search.';
			} // }}}
			function show_no_items() // {{{
			{
				echo 'No items currently exist.';
			} // }}}
			function show_sorting() // {{{
			{
			} // }}}
			function show_paging() // {{{
			{
			} // }}}
			function show_filters() // {{{
			{
			} // }}}
			/**#@-*/
			/**
			 * Default for showing items, can be overloaded
			 */
			function show_all_items() // {{{
			{
				//this function is meant for overloading
				echo '<table cellspacing="0" cellpadding="8">';
				$row = 0;
				reset( $this->values );
				while( list( $id, $item ) = each( $this->values ) )
				{
					if( ($row % $this->rows_per_sorting) == 0 )
						$this->show_sorting();
					$this->show_item( $item );
					$row++;
				}
				echo '</table>';
			} // }}}
			/**
			 * Default for showing one item in a column, can be overloaded
			 */
			function show_item( $item ) // {{{
			{
				static $row_num = 1;
				$row_num = 1 - $row_num;
				echo '<tr>';
				$values = $item->get_values();
				reset( $values );
				while( list( $key,$val ) = each( $values ) )
					echo '<td bgcolor="#'.($row_num ? 'DDDDDD' : 'FFFFFF' ).'">'.$val.'</td>';
				echo '</tr>';
			} // }}}
		}
	}
?>
