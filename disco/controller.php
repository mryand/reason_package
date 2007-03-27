<?php
/**
 * Disco Multi Page Controller
 *
 * An attempt at creating a multi page form controller.
 *
 * @author Dave Hendler
 * @since 2005-02-01
 * @package disco
 * @subpackage controller
 */

/**
 * The Form Step class
 */
include_once( 'controller_step.php' );

/**
 * The Step class that the controller depends upon
 */
define( 'FC_STEP_CLASS', 'FormStep' );

/**
 * Multi Page Form Controller
 *
 * @author Dave Hendler
 * @package disco
 * @subpackage controller
 */
class FormController
{
	/**
	 * Storage of individual forms
	 *
	 * 'DiscoFormName' => [DiscoForm object], ...
	 *
	 * @access private
	 * @see FormStep
	 * @var array of objects 
	 */
	var $forms;
	
	/**
	 * possible transitions between forms
	 *
	 * format is:
	 *
	 * <code>
	 * array(
	 *	FORM_NAME => array(
	 *		'next_steps' => array(
	 *			 FORM_NAME => array(
	 *				'label' => LABEL,
	 *			),
	 *			* more steps *
	 *		),
	 *		'step_decision' => array(
	 *			'type' => DECISION_TYPE,
	 *			'method' => METHOD_NAME,
	 *		),
	 *		'start_step' => true|false,
	 *		'final_step' => true|false,
	 *		'back_button_text' => 'Go Back',
	 *		'final_button_text' => 'Submit this form',
	 *	),
	 *   * more forms *
	 * );
	 *
	 * </code>
	 *
	 * FORM_NAME is the name of an existing class that extends from Disco.
	 *
	 * LABEL is the label to be applied to the user choice for that form.
	 *
	 * The 'back_button_text' attribute lets the controller know what text to use when a back button refers to the
	 * defined step.  Kind of confusing.  The text does not appear on that step itself.  Instead, if any step in the
	 * form will be going back to the step that we are defining, it will use this text.
	 *
	 * DECISION_TYPE can either be 'user' or 'method'.
	 *     - 'user' is if you want the user to make the decision about which
	 *       step to go to next.
	 *     - 'method' is a method of the controller class to call to determine
	 *       where to go based on form info.  these methods are documented later.
	 *
	 * The 'final_label' attribute is used to name the final button on the form.  This is only looked at if the step the
	 * user is on is either a de facto finish state or if the transitions array specifically uses the 'final_step'
	 * attribute.
	 *
	 * @access private
	 * @var mixed nested array of info
	 */
	var $transitions;
	/**
	 * Default text for the next/continue button
	 */
	var $default_next_text = 'Continue';
	/**
	 * Default text for the back button
	 */
	var $default_back_text = 'Go Back';
	/**
	 * Default text for the submit/final button
	 */
	var $default_final_text = 'Submit Form';
	/**
	 * Message to show user if a session times out.
	 */
	var $sess_timeout_msg = '<p>It appears that you have not taken an action in a long enough time to warrant us to reset your session as a security measure.  Sorry for any inconvenience.</p>';
	/**
	 * Message if cookies are not enabled.
	 */
	var $no_cookie_msg = '<p>It appears that you do not have cookies enabled.  Making an online gift requires the use of cookies.  Please enable them and reload this page.</p>';
	/**
	 * Session name to be used by PHP's session stuff
	 */
	var $session_name = 'GiftSID';
	/**#@+
	 * @access private
	 */
	
	/**
	 * up to date path of where user has travelled
	 * @var array
	 */
	var $_path;
	
	/**
	 * has the controller init()ed?
	 * @var bool
	 */
	var $_inited = false;
	
	/**
	 * internal copy of request variables available
	 */
	var $_request;
	
	/**
	 * Map of variables to forms
	 *
	 * <code>
	 * example:  form BillingForm uses CCNumber.  entry would look like:
	 *   'CCNumber' => 'BillingForm'
	 * </code>
	 * @var array
	 */
	var $_vars = array();
	
	/**
	 * reverse lookup array of vars.  structured like so:
	 * <code>
	 * 'form1' => array( 'var1','var2' ),
	 * 'form2' => array( 'var3', 'var4' ),
	 * </code>
	 * @var array
	 */
	var $_form_vars = array();
	
	/**
	 * step of the process the user is on.
	 * @var string
	 */
	var $_current_step;
	
	/**
	 * is this the first time the FormController has been run?
	 * @var bool
	 */
	var $_first_run = true;
	
	/**
	 * simple array to store names of form steps in the order they were added.
	 *
	 * this is used as a last resort to determine step order
	 *
	 * @var string
	 */
	var $_form_order_added = array();
	
	/**
	 * Request variable name that holds the current step.  Generally passed in the URL as a GET var
	 * @var string
	 */
	var $_step_var_name = '_step';
	
	/**
	 * key for sessioned form data in _SESSION
	 * @var string
	 */
	var $_data_key = '_fc_data';
	
	/**
	 * determine the base URL for the form once and use at other times
	 * @var string
	 */
	var $_base_url;
	
	/**
	 * Used to store the name of the first step
	 * @var string
	 */
	var $_start_step;
	/**
	 * Stores name of final step
	 * @var string
	 */
	var $_final_step;
	/**
	 * Bool that contains whether a session cookie exists
	 */
	var $_session_existed;
	
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * Does nothing.
	 * @access public
	 */
	function FormController() // {{{
	{
	} // }}}
	
	//=========================================//
	//======== PUBLIC RUNNABLE METHODS ========//
	//=========================================//
	
	/**
	 * Populates each form step with the data saved in the session
	 * @access private
	 * @return void
	 */
	function _populate_step_data() // {{{
	{
		foreach( $this->_vars AS $var => $form_name )
		{
			if( !empty( $_SESSION[ $this->_data_key ][ $var ] ) )
			{
				$fref =& $this->forms[ $form_name ];
				$fref->set_value( $var, $_SESSION[ $this->_data_key ][ $var ] );
			}
		}
	} // }}}
	/**
	 * Set up the controller
	 * @access public
	 * @return void
	 */
	function init() // {{{
	{
		if( !$this->_inited )
		{
			$url_parts = parse_url( get_current_url() );
			$this->_base_url = $url_parts[ 'scheme' ].'://'.$url_parts['host'].$url_parts['path'];
			
			// build the master list of form to variable
			foreach( array_keys( $this->forms ) AS $name )
			{
				$form =& $this->forms[ $name ];
				$form->set_controller( $this );
				$form->init();
				foreach( $form->get_element_names() AS $el )
				{
					if( !empty( $this->_vars[ $el ] ) )
					{
						trigger_error( 'FormController Error: Duplicate variable on two steps' );
					}
					else
					{
						$this->_vars[ $el ] = $name;
						if( empty( $this->_form_vars[ $name ] ) ) $this->_form_vars[ $name ] = array();
						$this->_form_vars[ $name ][] = $el;
					}
				}
			}
			
			// determine if this is a first run or not, start session
			$this->_session_existed = !empty( $_REQUEST[ $this->session_name ] );
			session_name( $this->session_name );
			session_start();
			if( empty( $_SESSION ) )
			{
				$this->_first_run = true;
				$_SESSION[ 'running' ] = true;
			}
			else
			{
				$this->_first_run = false;
				$this->_populate_step_data();
				if( !empty( $_SESSION[ '_path' ] ) )
					$this->_path =& $_SESSION[ '_path' ];
				else
					$this->_path = array();
			}
			
			$this->_inited = true;
		}
	} // }}}
	/**
	 * Figure out where the form is currently.
	 *
	 * Looks first to see if this is the first time the controller has been run.  If so, we need to find a start state
	 * in the transition graph or pick the first.  Otherwise, the controller looks to the request to see if a step class
	 * has been passed through.
	 *
	 * @access private
	 * @return void
	 */
	function determine_step() // {{{
	{
		// first time, no step name
		if( empty( $this->_request[ $this->_step_var_name ] ) )
		{
			$this->_current_step = $this->_get_start_step();
		}
		else
		{
			if( $this->_first_run )
			{
			}
			// if not the first time, figure out which step we are on
			else
			{
				// check request for step name
				if( !empty( $this->_request[ $this->_step_var_name ] ) )
				{
					// check forms to see if this step exists
					$cs = $this->_request[  $this->_step_var_name ];
					if( empty( $this->forms[ $cs ] ) )
					{
						trigger_error($cs.' is not a valid form step.');
					}
					else
					{
						$this->_current_step = $cs;
					}
				}
			}
		}
		
		if( empty( $this->_current_step ) )
		{
			$this->_current_step = $this->_get_start_step();
		}
		
		$this->validate_step();
	} // }}}
	/**
	 * Returns the name of the start step
	 *
	 * As a side effect, populates $this->_start_step.  Probably best to this method.
	 * @return string name of final step
	 * @access private
	 */
	function _get_start_step() // {{{
	{
		if( empty( $this->_start_step ) )
		{
			// find first form / start state
			foreach( $this->transitions AS $name => $trans )
			{
				if( !empty( $trans[ 'start_step' ] ) )
				{
					if( empty( $this->_start_step ) )
					{
						$this->_start_step = $name;
					}
					else
					{
						trigger_error('Two start states found.  Using the first one: '.$this->_start_step);
					}
				}
			}
			if( empty( $this->_start_step ) )
			{
				$this->_start_step = $this->_form_order_added[ 0 ];
			}
		}
		return $this->_start_step;
	} // }}}
	/**
	 * returns the name of the final step
	 *
	 * As a side effect, populates $this->_final_step
	 * @return string Name of the final step
	 * @access private
	 */
	function _get_final_step() // {{{
	{
		if( empty( $this->_final_step ) )
		{
			foreach( $this->transitions AS $name => $trans )
			{
				if( !empty( $trans[ 'final_step' ] ) )
				{
					if( empty( $this->_final_step ) )
					{
						$this->_final_step = $name;
					}
					else
					{
						trigger_error('More than one final state found.  Using the first one: '.$this->_final_step);
					}
				}
			}
			if( empty( $this->_final_step ) )
			{
				$this->_final_step = $this->_form_order_added[ count( $this->_form_order_added ) - 1 ];
			}
		}
		return $this->_final_step;
	} // }}}
	/**
	 * Make sure that _current_step is a valid form and that it makes sense in the flow of the form.
	 * @return void
	 * @access private
	 */
	function validate_step() // {{{
	{
		if( empty( $this->_current_step ) )
		{
			trigger_error('Current step is empty.  Bad.');
			return;
		}
		if( empty( $this->forms[ $this->_current_step ] ) )
		{
			trigger_error('Current step has no defined form.');
			return;
		}
	} // }}}
	/**
	 * @todo write this method
	 */
	function intercept_post() // {{{
	{
	} // }}}
	/**
	 * Updates the session with all new/changed data from form steps
	 * @access private
	 * @return void
	 */
	function update_session_form_vars() // {{{
	{
		$no_session = array();
		foreach( $this->forms AS $f )
		{
			$no_session = array_merge( $no_session, $f->no_session );
		}
		foreach( $this->_form_vars[ $this->_current_step ] AS $var )
		{
			if( !in_array( $var, $no_session ) )
			{
				$_SESSION[ $this->_data_key ][ $var ] = $this->forms[ $this->_current_step ]->get_value( $var );
			}
		}
	} // }}}
	/**
	 * Run the Controller
	 * @access public
	 * @return void
	 */
	function run() // {{{
	{
		$this->init();
		
		$this->determine_step();
		
		if( empty( $this->_request[ $this->_step_var_name ] ) )
		{
			header('Location: '.$this->_base_url.'?'.$this->_step_var_name.'='.$this->_current_step);
			exit;
		}
		elseif( !empty( $this->_session_existed ) AND $this->_first_run )
		{
			// session timed out.  we know this because the cookie or SID exists but PHP could not find a
			// session file.
			trigger_error('Session has expired');
			$_SESSION[ 'timeout_msg' ] = true;
			header('Location: '.$this->_base_url.'?'.$this->_step_var_name.'='.$this->_get_start_step());
			die();
		}
		elseif ($this->_request[ $this->_step_var_name ] != $this->_current_step )
		{
			// Dave -- I changed this so that the form is not left in a bizarre place.  -- matt
			trigger_error( 'Strange behavior: requested multipage form step not the same as the actual step being displayed. Probably due to session timeout. Client browser headered to start of form.' );
			header('Location: '.$this->_base_url.'?'.$this->_step_var_name.'='.$this->_get_start_step() );
			exit;
		}
		
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		
		// intercept posts, store in session, redirect to a new page, send disco the sessioned _POST
		
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$this->intercept_post();
		
		$final_step = ( $this->_current_step == $this->_get_final_step() );
		
		// get the actual object that has already been instantiated.
		// we know current step is good since validate_step has run.
		$f =& $this->forms[ $this->_current_step ];
		$f->set_request( $this->_request );
		$actions = array();
		if( !empty( $this->transitions[ $this->_current_step ] ) )
		{
			$trans = $this->transitions[ $this->_current_step ];
			if( !empty( $trans[ 'step_decision' ] ) )
			{
				$trans_type = !empty( $trans['step_decision']['type'] ) ? $trans['step_decision']['type'] : '';
				switch( $trans_type )
				{
					case 'user':
						$next_steps = $trans[ 'next_steps' ];
						foreach( $next_steps AS $action => $action_info )
						{
							if( !empty( $action_info['label'] ) )
								$label = $action_info[ 'label' ];
							else
								$label = $action;
							$actions[ $action ] = $label;
						}
						break;
					
					case 'method':
						break;
					
					default:
						trigger_error('Unknown transition step decision type.  How is that for programmer jargon?');
						break;
				}
			}
			else
			{
				$actions[ 'next' ] = $this->default_next_text;
			}
		}
		else
		{
			$actions[ 'next' ] = $this->default_next_text;
		}
		if( !empty( $this->_path ) )
		{
			$s = $this->_path[ count( $this->_path ) - 1 ];
			if( !empty( $this->transitions[ $s ][ 'back_button_text' ] ) )
				$actions[ 'back' ] = $this->transitions[ $s ][ 'back_button_text' ];
			else
				$actions[ 'back' ] = $this->default_back_text;
		}
		if( $final_step )
		{
			if( !empty( $this->transitions[ $this->_current_step ][ 'final_button_text' ] ) )
				$actions['next'] = $this->transitions[ $this->_current_step ][ 'final_button_text' ];
			else
				$actions[ 'next' ] = $this->default_final_text;
		}
		$f->actions = $actions;
		
		$f->run_load_phase();
		
		$this->update_session_form_vars();
		
		if( !empty( $f->chosen_action ) )
		{
			if( $f->chosen_action == 'back' )
			{
				$form_jump = array_pop( $this->_path );
			}
		}
		
		if( empty( $form_jump ) )
		{
			$f->run_process_phase();
			
			$this->update_session_form_vars();
			
			// $processed was added to FormStep to see if the form is done.  This will be false on first time or in
			// error checking
			if( $f->processed )
			{
				$this->_add_step_to_path( $this->_current_step );
				$form_jump = $this->_determine_next_step();
			}
		}
		if( !empty( $form_jump ) )
		{
			header('Location: '.$this->_base_url.'?'.$this->_step_var_name.'='.$form_jump);
			exit;
		}

		if( !empty( $_SESSION[ 'timeout_msg' ] ) )
		{
			$_SESSION[ 'timeout_msg' ] = '';
			echo $this->sess_timeout_msg;
		}
		
		$f->run_display_phase();
		
		if( $final_step AND $f->processed )
		{
			$final_where_to = $f->where_to();
			$this->destroy();
			if( !empty( $final_where_to ) )
			{
				header( 'Location: '.$final_where_to );
			}
		}
	} // }}}
	/**
	 * Destroys the session that this controller is using
	 * @access private
	 */
	function destroy() // {{{
	{
		setcookie($this->session_name,'');
		$_SESSION = array();
		session_destroy();
	} // }}}
	
	//====================================================================//
	//========== PUBLIC DATA MANIPULATION AND RETRIEVAL METHODS ==========//
	//====================================================================//
	
	/**
	 * Gets all form class names that the Controller is using
	 * @access public
	 * @return array
	 */
	function get_form_names() // {{{
	{
		return array_keys( $this->forms );
	} // }}}
	/**
	 * Gets a particular form step
	 * @access public
	 * @return FormStep
	 * @param string $name Name of the FormStep to retrieve
	 */
	function get_form( $name ) // {{{
	{
		return $this->forms[ $name ];
	} // }}}
	/**
	 * Get all the forms in an array
	 * @access public
	 * @return array Returns the array of forms
	 */
	function get_forms() // {{{
	{
		return $this->forms;
	} // }}}
	/**
	 * Gets the Request variables this Controller needs
	 * @access public
	 * @return array
	 */
	function get_request_vars() // {{{
	{
		return array_keys( $this->_vars );
	} // }}}
	/**
	 * Gets the value of the element from whichever form it appears on
	 * @access public
	 * @return mixed
	 * @param string $var Name of the variable to get
	 */
	function get( $var ) // {{{
	{
		return $this->forms[ $this->_vars[ $var ] ]->get_value( $var );
	} // }}}
	/**
	 * Gets the names of all the elements of which the controller is aware. An alias to get_request_vars().
	 * @access public
	 * @return array
	 */
	function get_element_names() // {{{
	{
		return $this->get_request_vars();
	} // }}}
	/**
	 * Adds a form to the Controller
	 * @access public
	 * @return void
	 * @param string $name Name of the class to add to the Controller
	 * @param array $transition The transition array to apply to this class.
	 * @see $transition for detailed explanation of the transition array
	 * @todo more error checking.
	 */
	function add_form( $name, $transition = array() ) // {{{
	{
		if( class_exists( $name ) )
		{
			$obj = new $name;
			if( true OR is_subclass_of( $obj, FC_STEP_CLASS ) )
			{
				$this->forms[ $name ] =& $obj;
				$this->_form_order_added[] = $name;
				$this->transitions[ $name ] = $transition;
				// TODO: init form?
			}
			else
			{
				trigger_error("$name must be a subclass of FormStep");
			}
		}
		else
		{
			trigger_error( "$name does not exist and cannot be added to this form" );
		}
	} // }}}
	/**
	 * Bulk setter.
	 * @access public
	 * @return void
	 * @param array $forms Array of transitions keyed by FormStep class names
	 */
	function add_forms( $forms ) // {{{
	{
		if( is_array( $forms ) )
		{
			foreach( $forms AS $form => $transitions )
			{
				$this->add_form( $form, $transitions );
			}
		}
		else
		{
			trigger_error('badly formatted forms array passed to add_forms()');
		}
	} // }}}
	/**
	 * Add a transition to a pre-existing FormStep
	 * @access public
	 * @return void
	 * @param string $form Step name
	 * @param array $transition_args A transition array
	 */
	function add_transition( $form, $transition_args ) // {{{
	{
		// TODO:
		// check to see if next forms actually exist
		
		// make sure decision methods exist
		
		$this->transitions[ $form ] = $transition_args;
	} // }}}
	/**
	 * Sets the internal request store
	 * Defaults to $_REQUEST if nothing is specified.
	 * @access public
	 * @return public
	 * @param array $r Array with the available Request keys-values that the Controller should have access to
	 */
	function set_request( $r = NULL ) // {{{
	{
		$this->_request = ( $r !== NULL ) ? $r : $_REQUEST;
	} // }}}
	/**
	 * Utility method for minisite system.  This can talk to the deep plasmature types to get their cleanup rules.
	 * @returns array A valid cleanup rules array with all elements as well as the form controller variables
	 */
	function get_cleanup_rules() // {{{
	{
		$rules = array();
		$rules[ $this->_step_var_name ] = array( 'function' => 'turn_into_string' );
		foreach( $this->_vars AS $key => $form )
		{
			$el = $this->forms[ $form ]->get_element( $key );
			$rules[ $key ] = $el->get_cleanup_rule();
		}
		return $rules;
	} // }}}
	
	
	//=========================================//
	//========== PRIVATE METHODS ==============//
	//=========================================//
	
	/**
	 * @access private
	 */
	function _determine_next_step() // {{{
	{
		// if it's the final step, there is no next step
		if( $this->_current_step == $this->_get_final_step() )
			return;
		
		// determine where to go from here.
		$trans = $this->transitions[ $this->_current_step ];
		// nothing was specified as a next step for this form.  try to figure out where to go.
		if( empty( $trans[ 'next_steps' ] ) )
		{
			// find the current form and then get the form listed after that one
			$pos = array_search( $this->_current_step, $this->_form_order_added );
			if( $pos !== false )
			{
				if( !empty( $this->_form_order_added[ $pos + 1 ] ) )
					$next_step = $this->_form_order_added[ $pos + 1 ];
				else
					trigger_error('There was no next step in the _form_order_added array');
			}
			else
			{
				trigger_error('The current step was not found in the list of steps.');
			}
		}
		// there's only one choice.  go do it.
		else if ( count( $trans[ 'next_steps' ] ) == 1 )
		{
			reset( $trans[ 'next_steps' ] );
			list( $next_step, $step_info ) = each( $trans[ 'next_steps' ] );
		}
		// more than one step possible.  determine where we are going
		else
		{
			// is there even a decision type specified?
			if( !empty( $trans[ 'step_decision' ] ) )
			{
				$dec = $trans[ 'step_decision' ];
				// check the decision type to see if this is a user decision or a method decision
				if( !empty( $dec[ 'type' ] ) )
				{
					// decision is based on what they user chose
					if( $dec[ 'type' ] == 'user' )
					{
						if( !empty( $this->forms[ $this->_current_step ]->chosen_action ) )
						{
							$next_step = $this->forms[ $this->_current_step ]->chosen_action;
						}
						else
						{
							trigger_error('no next step received from the user');
						}
					}
					// decision uses a method.
					elseif( $dec[ 'type' ] == 'method' )
					{
						if( !empty( $dec[ 'method' ] ) AND method_exists( $this, $dec[ 'method' ] ) )
						{
							$method = $dec[ 'method' ];
							// call the method specified
							$next_step = $this->$method();
						}
						else
						{
							trigger_error('either no method was specified or the method does not exist.');
						}
					}
					else
					{
						trigger_error( 'unknown decision type: '.$dec['type'] );
					}
				}
				else
				{
					trigger_error('No decision type specified for the chosen transition');
				}
			}
			else
			{
				trigger_error('programmer error - no way to decide where to go from here - no decision information on this transition.');
			}
		}
		if( !empty( $next_step ) )
		{
			if( !empty( $this->forms[ $next_step ] ) )
			{
				return $next_step;
			}
			else
			{
				trigger_error( 'next_step ("'.$next_step.'") is not a valid form.  Perhaps fell off of the world' );
				return false;
			}
		}
		else
		{
			trigger_error('No next_step was found at all');
		}
	} // }}}
	/**
	 * @access private
	 */
	function _check_transitions() // {{{
	{
		// TODO:
	} // }}}
	/**
	 * @access private
	 */
	function _add_step_to_path( $step ) // {{{
	{
		$_SESSION[ '_path' ][] = $step;
	} // }}}
}

?>
