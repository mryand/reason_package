<?php
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'sidebar_feature_manager';

	class sidebar_feature_manager extends ContentManager 
	{
	
		function alter_data() {
			$this -> add_required ("description");
			$this -> add_required ("show_hide");
			$this -> set_comments ("name", form_comment("For your reference only"));
			$this -> set_display_name ("description", "Sidebar Feature Text");
			$this -> set_display_name ("show_hide", "Show/Hide");
			$this -> set_comments ("show_hide", form_comment('This allows you to toggle a feature on &amp; off'));
		}
		
	}
?>
