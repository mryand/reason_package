<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module and other needed utilities
  */
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once('classes/page_type_entities.php');
	include_once( DISCO_INC.'disco.php' );
	include_once('tyr/email.php');
	include_once(CARL_UTIL_INC.'dir_service/directory.php');
	reason_include_once( 'function_libraries/user_functions.php' );
	
	/**
	 * Image Sizer Module
	 *
	 * This module allows site admins to get the URLs of Reason images
	 * at any size or crop style.
	 *
	 * @author Matt Ryan
	 */
	class RequestPageTypeModule extends DefaultModule
	{
		/**
		 * The disco form object that this module runs
		 * @var object disco form
		 */
		var $_form;
		/**
		 * Is this module OK rto run, based on the sharing properties, etc.?
		 *
		 * Do not access this var directly; use the method _ok_to_run_module() instead.
		 *
		 * @var boolean
		 */
		var $_ok_to_run;
		
		var $acceptable_paramaters = array('page_type' => array('function' => 'turn_into_string'));
		
		protected $page_type_entity;
		protected $page_entity;
		protected $site_entity;
		protected $user_entity;
		
		/**
		 * Constructor
		 */
		function ImageSizerModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		
		/**
		 * Initialize the module
		 *
		 * Set up the page title, add appropriate css, and set up the form
		 *
		 * @return void
		 */
		function init()
		{
			parent::init();

			$this->admin_page->title = 'Request Page Type';
			if($pt = $this->get_page_type_entity())
			{
				$this->admin_page->title .= ' ('.pageTypeEntities::title($pt).')';
			
				if($this->_ok_to_run_module())
				{
					$this->_set_up_form($pt->get_value('name'));
				}
			}
			
		}
		
		function get_page_type_entity()
		{
			if(!isset($this->page_type_entity))
			{
				$this->page_type_entity = false;
				if(!empty($this->admin_page->request['page_type']))
				{
					$pt = (string) $this->admin_page->request['page_type'];
					if(!empty($pt))
						$this->page_type_entity = pageTypeEntities::entity($pt);
				}
			}
			return $this->page_type_entity;
		}
		
		function get_page_entity()
		{
			// $this->admin_page->id
			
			if(!isset($this->page_entity))
			{
				$this->page_entity = new entity($this->admin_page->id);
			}
			return $this->page_entity;
		}
		
		function get_site_entity()
		{
			if(!isset($this->site_entity))
			{
				$this->site_entity = new entity($this->admin_page->site_id);
			}
			return $this->site_entity;
		}
		
		function get_user_entity()
		{
			if(!isset($this->user_entity))
			{
				$this->user_entity = new entity($this->admin_page->user_id);
			}
			return $this->user_entity;
		}
		
		/**
		 * Set up the disco form for this module
		 *
		 * @param object reason image entity
		 * @return void
		 */
		function _set_up_form($page_type)
		{
			$this->_form = new Disco();
			
			$this->_form->set_box_class('StackedBox');
			
			$this->_form->add_element('page_type','cloaked');
			$this->_form->set_value('page_type', $page_type);

			$this->_form->add_element('info', 'textarea');
			$this->_form->set_display_name('info', 'Tell us more about why you are requesting this page type change, so it can be set up properly');
			$this->_form->add_required('info');
			
			$this->_form->set_actions(array('Submit Request'));
			
			//$pre_show_callback = array($this,'pre_show_disco');
			//$this->_form->add_callback($pre_show_callback, 'pre_show_form');
			
			$process_callback = array($this,'process_disco');
			$this->_form->add_callback($process_callback, 'process');
		}
		
		
		/**
		 * Is it OK to run this module?
		 * @return boolean
		 */
		function _ok_to_run_module()
		{
			if($this->_ok_to_run !== true && $this->_ok_to_run !== false)
			{
				$this->_ok_to_run = false;
				
				if(!$this->admin_page->id)
				{
					return $this->_ok_to_run;
				}
			
				$owner_site = get_owner_site_id( $this->admin_page->id );
			
				$entity = new entity($this->admin_page->id);
			
				if($owner_site == $this->admin_page->site_id)
				{
					$this->_ok_to_run = true;
					return $this->_ok_to_run;
				}
			}
			return $this->_ok_to_run;
		}
		
		/**
		 * Run the module
		 * @return void
		 */
		function run() // {{{
		{
			if(!empty($this->_form))
			{
				$page = $this->get_page_entity();
				echo '<div id="pageTypeRequestModule">'."\n";
				echo '<p><strong>Page:</strong> '.$page->get_value('name').'</p>';
					echo '<p><strong>Current page type:</strong> '.pageTypeEntities::title($page->get_value('custom_page')).'</p>';
					echo '<p><strong>Requested page type:</strong> '.pageTypeEntities::title($this->get_page_type_entity()).'</p>';
				$this->_form->run();
				if(!$this->_form->show_form)
				{
					echo '<p>Thank you for your request. We will get back to you soon about setting up your requested page type.</p>';
				}
				echo '</div>'."\n";
			}
			else
				echo '<p>This module needs a valid page ID and page type to run</p>'."\n";
		}
		
		function get_user_info()
		{
			
			$user = $this->get_user_entity();
			$ret = array('username' => $user->get_value('name'));
			
			$dir = new directory_service();
			$dir->search_by_attribute('ds_username', array($user->get_value('name')), array('ds_username','ds_email','ds_fullname'));
			$email = $dir->get_first_value('ds_email');
			if(is_array($email))
				$email = reset($email);
			$ret['email'] = $email;
			
			$name = $dir->get_first_value('ds_fullname');
			if(is_array($name))
				$name = reset($name);
			$ret['name'] = $name;
			
			return $ret;
		}
		
		function process_disco($disco)
		{
			$email = '';
			$email .= 'Page type request'."\n\n";
			
			$info = $this->get_user_info();
			$email .= 'Sent by:'."\n";
			
			$user_parts = array();
			if(!empty($info['username']))
				$user_parts[] = $info['username'];			
			if(!empty($info['name']))
				$user_parts[] = $info['name'];
			if(!empty($info['email']))
				$user_parts[] = $info['email'];
			
			$email .= implode(' / ', $user_parts);
				
			$email .= "\n\n";
			
			$site = $this->get_site_entity();
			$email .= 'Site:'."\n".$site->get_value('name').' (id: '.$site->id().')'."\n\n";
			$page = $this->get_page_entity();
			$email .= 'Page:'."\n".$page->get_value('name').' (id: '.$page->id().')'."\n\n";
			$email .= 'Current page type:'."\n".pageTypeEntities::title($page->get_value('custom_page')).' ('.$page->get_value('custom_page').')'."\n\n";
			$email .= 'Requested page type:'."\n".pageTypeEntities::title($disco->get_value('page_type')).' ('.$disco->get_value('page_type').')'."\n\n";
			$email .= 'Comments:'."\n".$disco->get_value('info')."\n\n";
			
			$link = HTTPS_AVAILABLE ? 'https://' : 'http://';
			$link .= REASON_WEB_ADMIN_PATH.'?site_id='.$site->id().'&type_id='.id_of('minisite_page').'&id='.$page->id();
			$email .= 'Edit page:'."\n".$link."\n\n";
			
			$from = !empty($info['email']) ? $info['email'] : TYR_REPLY_TO_EMAIL_ADDRESS;
			
			$tyr = new Email(REASON_PAGE_TYPE_CHANGE_REQUEST_EMAIL_ADDRESSES, $from, $from, '[Reason] Page type request', $email, nl2br(htmlspecialchars($email)));
			$tyr->send();
			
			$disco->show_form = false;
		}
		
	}
?>