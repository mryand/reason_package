<?php
	//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	//
	//		this line is important - make sure any content handlers have this variable set in their include files!!!!
	
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'associatorManager';
	//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	reason_include_once( 'classes/entity_selector.php' );
	
	class associatorManager extends ContentManager
	{	
		function prep_for_run( $site_id, $type_id, $id, $user_id ) // {{{
		{
			ContentManager::prep_for_run( $site_id , $type_id , $id , $user_id );
			unset( $this->actions[ 'assoc' ] );
			$this->get_relationships();
		} // }}}
		function alter_data() // {{{
		{
		} // }}}
	function show_associations()  // {{{
	{
		//we don't want to show associations since they are already shown in a menu
	} // }}}
	function get_relationships() // {{{
		{
			$q = "SELECT id, relationship_b, name, connections from allowable_relationship where relationship_a = " 
				. $this->get_value( 'type_id' ) . " AND name != 'owns' AND name NOT LIKE  '%archive%'";
			$r = db_query( $q , "Error selecting relationships" );
			
			while( $row = mysql_fetch_array( $r ))
			{
				//get values for menu
				$entity_object = new entity_selector(  $this->get_value( 'site_id' ));
				$entity_object->add_type($row[ 'relationship_b' ] );
				$entity = $entity_object->run_one();
				if(!is_array( $entity ) ) $entity= array();
				reset( $entity );
				$options = array();
				while( list( , $value ) = each( $entity ))
				{
					$options[ $value->id() ] = strip_tags( $value->get_display_name() );
				}
				//get name of relationship
				$default = array();
				if( $this->get_value( 'id' ) )
				{
					$q = "SELECT entity_b FROM relationship where entity_a = " . $this->get_value( 'id' ) . " AND type = " .
						 $row[ 'id' ];
					$r2 = db_query( $q , "error retrieving existing relationship" );
					while($row2 = mysql_fetch_array( $r2 ) )
						$default[] = $row2[ 'entity_b' ];
					mysql_free_result( $r2 );
					if( empty( $default ) )
						$default = array( 0 => '' );
				}
				else $default = array( 0 => '' );
				reset ( $default );
				if( $row['connections'] == 'one_to_many' )
					$this->add_element( $row[ 'name' ] , 'select' , array( 'default' => $default[0] , 'options' => $options) );	
				else
					$this->add_element( $row[ 'name' ] , 'select_multiple' , array( 'default' => $default , 'options' => $options) );	
			}
			mysql_free_result( $r );
			$this->update_relationships();
		} // }}}
		function update_relationships() // {{{
		{
			//overload me
		} // }}}
		function delete_existing_relationships( $omit = false ) // {{{
		//if given a value, the elements of omit should contain the id's of omitted associations
		//this allows given elements to retains their values
		{
			$q = 'SELECT id from allowable_relationship where name = "owns"';
			$r = db_query( $q , 'Error selecting "owns" relationships' );
			$where = "";
			while( $row = mysql_fetch_array( $r , MYSQL_ASSOC ))
				$where .= " AND type != " . $row['id'];   //make sure we don't grab ownership relations
			
			if( $omit )
			{
				reset( $omit );
				while( list( , $value ) = each ( $omit ) )
					$where .= " AND type != " . $value;  //make sure we don't grab omitted relations
			}	
			
			$q = "DELETE from relationship where entity_a = " . $this->get_value( 'id' ) . $where;
			$r = db_query( $q , "Error selecting relationships" );
			
		} // }}}
		function add_new_relationships( $omit = false ) // {{{
		//if given a value, the elements of omit should contain the id's of omitted associations
		//this allows given elements to not be added
		{
			$where = "";
			if( $omit )
			{
				reset( $omit );
				while( list( , $value ) = each ( $omit ) )
					$where .= " AND id != " . $value;
			}
			
			$q = "SELECT id, name, connections from allowable_relationship where relationship_a = " . $this->get_value( 'type_id' ). 
				' AND name != "owns" ' . $where;
			$r = db_query( $q , "Error selecting relationships" );
			while( $row = mysql_fetch_array( $r ) )
			{
				if( $row['connections'] == 'one_to_many')
				{
					if(($this->get_value( 'id' ) && $this->get_value( $row[ 'name' ] ) && $row[ 'id' ]))
					{
						create_relationship( $this->get_value( 'id' ) ,
								     $this->get_value( $row[ 'name' ] ),
								     $row['id']
								   );
					}
				}
				else
				{
					$values = $this->get_value( $row[ 'name' ] );
					while( list( , $val) = each( $values ) )
					{
						if($val)  //val may have value "", in which case it could cause a mysql error...this eliminates that
						{
							create_relationship( $this->get_value( 'id' ) ,
									     $val,
									     $row['id']
									   );
						}
					}
				}
			}
		} // }}}
		function finish() // {{{
		{
			if( !$this->get_value( 'id' ) )
				$this->set_value( 'id' , $this->_inserted_id );
			$this->delete_existing_relationships();
			$this->add_new_relationships();
			return $this->CMfinish();
		} // }}}
	}
	
?>
