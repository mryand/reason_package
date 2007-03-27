<?php
	// get the name of the file, without all the path information
	// and without the .php suffix and set the name of the class
	// to that index of this global array

reason_include_once( 'minisite_templates/modules/policy.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'RelatedPolicyModule';

class RelatedPolicyModule extends PolicyModule
{
	function get_cleanup_rules()
	{
		$cr = parent::get_cleanup_rules();
		$cr['show_all'] = array( 'function' => 'check_against_array', 
								 'extra_args' => array( 'true', 'false' ) );
								 
		$cr['policy_id'] = array( 'function' => 'turn_into_int' );
		return $cr;
	}
	
	function init( $args = array() ) // {{{
	{
		// this is dumb.  but there's no better way to do it.  this runs the
		// entire init() method of the PolicyModule which a bunch of the same
		// stuff.  This one 
		DefaultMinisiteModule::init( $args );

		$es = new entity_selector( $this->parent->site_id );
		$es->add_type( id_of( 'policy_type' ) );
		//$es->set_order( 'sortable.sort_order ASC' );
		$es->set_order( 'entity.name ASC' );
		$es->add_relation( 'show_hide.show_hide != "hide"' );
		$es->add_left_relationship_field( 'parent' , 'entity' , 'id' , 'parent_id' );
		$es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('page_to_policy') );

		$this->values = $es->run_one();
		$this->pages = new PolicyNavigation;
		$this->pages->request =& $this->request;
		// small kludge - just give the tree view access to the site info.  used in the show_item function to show the root node of the navigation
		if ( !empty ( $this->site_info ) )
			$this->pages->site_info = $this->site_info;
		$this->pages->order_by = 'sortable.sort_order ASC';
		$this->pages->init( $this->parent->site_id, id_of('policy_type') );
		if( !empty( $this->request[ 'policy_id' ] ) )
		{
			if(array_key_exists($this->request[ 'policy_id' ], $this->values))
			{
				$this->policy = new entity( $this->request[ 'policy_id' ] );
				$this->parent->add_crumb( $this->policy->get_value( 'name' ) , '?policy_id=' . $this->request[ 'policy_id' ] );
			}
			else
			{
				$this->policy = NULL;
			}
		}
	} // }}}
	function run() // {{{
	{
		if( !empty($this->request[ 'show_all' ]) )
		{
			// this->values contains the policies associated with this page.
			// lopo through those looking for root nodes to list
			foreach( $this->values AS $node )
				if( $node->id() == $node->get_value( 'parent_id' ) )
				{
					// HUUUUUUUUUUUUUUUUGE HACK
					// this really isn't that bad.  the reason it works is
					// because of the flawed design of the listers.  the policy
					// navigation lister depends on the policy_id IN the request
					// array to determine which root to use.  So what I'm doing
					// is just running the do_display method several times while
					// changing the internal storage of the policy_id
					$this->request['policy_id'] = $node->id();
					$this->pages->do_display();
				}

		}
		else
		{
			parent::run();
		}
	} // }}}
}
	
?>
