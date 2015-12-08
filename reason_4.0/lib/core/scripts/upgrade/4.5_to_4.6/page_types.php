<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.5_to_4.6']['page_types'] = 'ReasonUpgrader_46_SetupPageTypeType';
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('minisite_templates/page_types.php');
reason_include_once('classes/amputee_fixer.php');

class ReasonUpgrader_46_SetupPageTypeType implements /* reasonUpgraderInterface, */ reasonUpgraderInterfaceAdvanced
{
	protected $user_id;
	
	var $details = array (
		'new'=>0,
		'unique_name'=>'page_type_type',
		'custom_content_handler'=>'page_type.php',
		'custom_previewer'=>'page_type.php',
		'custom_deleter'=>'page_type.php',
		'custom_sorter'=>'page_type.php',
		'custom_post_deleter'=>'page_type.php',
		'finish_actions'=>'page_type_finish.php',
		'name'=>'Page Type',
		'plural_name'=>'Page Types',
	);
	
	var $fields = array (
		'page_type' => array(
			'title' => 'tinytext',
			'page_locations' => 'text',
			'note' => 'text',
			'description' => 'text',
			'example_url' => 'tinytext',
			'default_availability' => 'enum("hidden","visible","available") default "hidden"',
			'availability_logic' => 'text',
			'visibility_logic' => 'text',
			'meta' => 'text',
		),
	);
	
	var $view_types = array(
		'page_type.php' => 'Page Type',
	);
	
	var $views = array(
		'Page Type Tree' => array(
			'fields' => array(
				'display_name' => 'Tree',
				'sort_order' => '1',
			),
			'type' => 'page_type.php',
			'searchable_fields' => array(
				'title', 'page_locations', 'example_url', 'default_availability', 'availability_logic', 'visibility_logic', 'meta',
			),
			'columns' => array(
				'title',
			),
		),
		'Page Type List' => array(
			'fields' => array(
				'display_name' => 'List',
				'sort_order' => '2',
			),
			'type' => 'default.php3',
			'searchable_fields' => array(
				'title', 'page_locations', 'example_url', 'default_availability', 'availability_logic', 'visibility_logic', 'meta',
			),
			'columns' => array(
				'title',
			),
		),
	);
	
	var $type_id;
	
	var $relationships = array (
		'page_type_parent' => array(
			'type' => 'page_type_type',
			'values' => array(
				'description'=>'Page Type Parent',
				'directionality'=>'unidirectional',
				'connections'=>'one_to_many',
				'required'=>'yes',
				'is_sortable'=>'no',
				'display_name'=>'Parent',
				'display_name_reverse_direction'=>'Children',
				'description_reverse_direction'=>'Children',
				'custom_associator' => 'parent_tree',
			),
		),
	);
	
	var $replacements = array(
		'faculty_and_children' => 'faculty_sidebar_children',
		'event_signup' => 'events',
		'event_slot_registration' => 'events_verbose',
		'event_slot_registration_cache_1_hour' => 'events_cache_1_hour',
	);
	
	protected $initial_setup = array(
		'default' => array(
			'fields' => array('unique_name' => 'default_page_type', 'title' => 'Normal Page','default_availability'=>'available'),
			'maximal_fields' => array(),
			'parent' => NULL,
		),
		'gallery' => array(
			'fields' => array('title' => 'Photo Gallery','description'=>'Shows associated images in a gallery format','default_availability'=>'visible','availability_logic'=>'type:image'),
			'maximal_fields' => array(),
			'parent' => NULL,
		),
		'show_children' => array(
			'fields' => array('title' => 'Children Pages','description'=>'Shows child pages in a list with their descriptions. Note: this includes pages not shown in navigation.','default_availability'=>'available'),
			'maximal_fields' => array(),
			'parent' => NULL,
		),
		'show_siblings' => array(
			'fields' => array('title' => 'Sibling Pages','description'=>'Shows this page\'s sibling pages after the content of the page. Note: this includes pages not shown in navigation.','default_availability'=>'available'),
			'maximal_fields' => array(),
			'parent' => NULL,
		),
		'siblings_and_children' => array(
			'fields' => array('title' => 'Siblings &amp; Children','description'=>'Shows this page\'s sibling pages after the content of the page, and children in the sidebar. Note: this includes pages not shown in navigation.','default_availability'=>'hidden'),
			'maximal_fields' => array('default_availability'=>'available'),
			'parent' => 'show_siblings',
		),
		'show_siblings_hide_non_nav' => array(
			'fields' => array('title' => 'Sibling Pages (Only Those In Nav)','description'=>'Shows this page\'s siblings, hiding those not shown in the site navigation.','default_availability'=>'hidden'),
			'maximal_fields' => array('default_availability'=>'available'),
			'parent' => 'show_siblings',
		),
		'show_siblings_hide_external_links' => array(
			'fields' => array('title' => 'Sibling Pages (No External Links)','description'=>'Shows this page\'s siblings, hiding those that are external links.','default_availability'=>'hidden'),
			'maximal_fields' => array('default_availability'=>'available'),
			'parent' => 'show_siblings',
		),
		'show_siblings_with_parent_title' => array(
			'fields' => array('title' => 'Sibling Pages With Parent Title','description'=>'Shows this page\'s siblings under a heading of their shared parent page.','default_availability'=>'hidden'),
			'maximal_fields' => array('default_availability'=>'visible'),
			'parent' => 'show_siblings',
		),
		'show_siblings_with_first_images' => array(
			'fields' => array('title' => 'Sibling Pages With Images','description'=>'Shows this page\'s siblings with images.','default_availability'=>'hidden'),
			'maximal_fields' => array('default_availability'=>'visible','availability_logic'=>'type:image'),
			'parent' => 'show_siblings',
		),
		'show_siblings_with_first_images_100x100' => array(
			'fields' => array('title' => 'Sibling Pages With Images (100 px)','description'=>'Shows this page\'s siblings with images sized to 100 pixels square.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'show_siblings_with_first_images',
		),
		'show_siblings_with_first_images_150x150' => array(
			'fields' => array('title' => 'Sibling Pages With Images (150 px)','description'=>'Shows this page\'s siblings with images sized to 150 pixels square.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'show_siblings_with_first_images',
		),
		'show_siblings_with_first_images_200x200' => array(
			'fields' => array('title' => 'Sibling Pages With Images (200 px)','description'=>'Shows this page\'s siblings with images sized to 200 pixels square.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'show_siblings_with_first_images',
		),
		'show_siblings_with_random_images' => array(
			'fields' => array('title' => 'Sibling Pages With Images (Randomized)','description'=>'Shows this page\'s siblings with images selected at random','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:image'),
			'parent' => 'show_siblings_with_first_images',
		),
		'show_siblings_with_random_images_100x100' => array(
			'fields' => array('title' => 'Sibling Pages With Images (Randomized, 100 px)','description'=>'Shows this page\'s siblings with images selected at random and sized to 100 pixels square','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'show_siblings_with_random_images',
		),
		'show_siblings_with_random_images_150x150' => array(
			'fields' => array('title' => 'Sibling Pages With Images (Randomized, 150 px)','description'=>'Shows this page\'s siblings with images selected at random and sized to 150 pixels square','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'show_siblings_with_random_images',
		),
		'show_siblings_with_random_images_200x200' => array(
			'fields' => array('title' => 'Sibling Pages With Images (Randomized, 200 px)','description'=>'Shows this page\'s siblings with images selected at random and sized to 200 pixels square','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'show_siblings_with_random_images',
		),
		'show_siblings_previous_next' => array(
			'fields' => array('title' => 'Sibling Pages (Previous &amp; Next)','description'=>'Links to the previous and next sibling pages.','default_availability'=>'hidden'),
			'maximal_fields' => array('default_availability'=>'available'),
			'parent' => 'show_siblings',
		),
		'siblings_no_nav' => array(
			'fields' => array('title' => 'Sibling Pages (Navigation Hidden)','description'=>'Shows this page\'s siblings, and hides site navigation.','default_availability'=>'hidden'),
			'parent' => 'show_siblings',
		),
		'siblings_and_sidebar_blurbs' => array(
			'fields' => array('title' => 'Sibling Pages with Sidebar Blurbs','description'=>'Shows this page\'s siblings, and places blurbs in sidebar.','default_availability'=>'hidden'),
			'parent' => 'show_siblings',
		),
		'form' => array(
			'fields' => array('title' => 'Form','description'=>'Show a form on this page. Note: A form must be associated with page for this to work.','default_availability'=>'hidden','availability_logic'=>'type:form'),
			'maximal_fields' => array('default_availability'=>'visible'),
			'parent' => NULL,
		),
		'formNoNavNoSearch' => array(
			'fields' => array('title' => 'Form (No Navigation or Search)','description'=>'Show a form on this page, hiding the site navigation and search.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:form'),
			'parent' => 'form',
		),
		'form_sidebar_blurbs' => array(
			'fields' => array('title' => 'Form w/ Blurbs in Sidebar','description'=>'Show a form on this page, placing any blurbs in the sidebar.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:form'),
			'parent' => 'form',
		),
		'form_sidebar_blurbs_if_logged_in' => array(
			'fields' => array('title' => 'Form w/ Conditional Blurbs in Sidebar','description'=>'Like Form w/ Blurbs in Sidebar, but blurbs are only shown to logged-in users.','default_availability'=>'hidden'),
			'parent' => 'form_sidebar_blurbs',
		),
		'publication' => array(
			'fields' => array('title' => 'Blog/Publication','description'=>'Shows a blog or other publication on this page. Note: A blog/publication must be associated with page for this to work.','default_availability'=>'hidden','availability_logic'=>'type:publication_type'),
			'maximal_fields' => array('default_availability'=>'visible'),
			'parent' => NULL,
		),
		'publication_no_dates' => array(
			'fields' => array('title' => 'Blog/Publication (No Dates)','description'=>'Shows a blog or other publication on this page, with no dates. Note: A blog/publication must be associated with page for this to work.','default_availability'=>'hidden'),
			'parent' => 'publication',
		),
		'publication_listnav' => array(
			'fields' => array('title' => 'Blog/Publication (List Categories)','description'=>'Shows a blog or other publication on this page, listing categories as a set of links rather than a dropdown. Note: A blog/publication must be associated with page for this to work.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:publication_type'),
			'parent' => 'publication',
		),
		'publication_no_nav' => array(
			'fields' => array('title' => 'Blog/Publication (No Site Navigation)','description'=>'Shows a blog or other publication on this page, hiding all site navigation. Note: A blog/publication must be associated with page for this to work.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:publication_type'),
			'parent' => 'publication',
		),
		'publication_related' => array(
			'fields' => array('title' => 'Blog/Publication Feed','description'=>'Shows a feed of posts from one or more Reason publications.','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:publication_type'),
			'parent' => 'publication',
		),
		'publication_related_via_category' => array(
			'fields' => array('title' => 'Blog/Publication Category Feed','description'=>'Shows a feed of posts from one or more Reason publications, filtered by page categories.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:publication_type'),
			'parent' => 'publication_related',
		),
		'publication_related_7_headlines' => array(
			'fields' => array('title' => 'Blog/Publication Feed (7 posts)','description'=>'Shows a feed of 7 posts from one or more Reason publications.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:publication_type'),
			'parent' => 'publication_related',
		),
		'publication_section_nav' => array(
			'fields' => array('title' => 'Blog/Publication (Sections as Navigation)','description'=>'Shows a publication, replacing the site navigation with section navigation.','default_availability'=>'hidden'),
			'parent' => 'publication',
		),
		'publication_with_events_sidebar' => array(
			'fields' => array('title' => 'Blog/Publication with Events Sidebar','description'=>'Shows a publication and lists events in the sidebar.','default_availability'=>'hidden'),
			'parent' => 'publication',
		),
		'publication_with_events_sidebar_and_content' => array(
			'fields' => array('title' => 'Blog/Publication with Events Sidebar &amp; Content','description'=>'Shows a publication and lists events in the sidebar. Includes page content above publication rather than the standard publication description.','default_availability'=>'hidden'),
			'parent' => 'publication_with_events_sidebar',
		),
		'publication_sidebar_via_categories' => array(
			'fields' => array('title' => 'Blog/Publication Feed (By Page Categories)','description'=>'Shows a feed of posts from one or more Reason publications, filtered by the categories attached to the current page.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:publication_type AND type:category_type'),
			'parent' => 'publication_related',
		),
		'publication_with_full_images_on_listing' => array(
			'fields' => array('title' => 'Blog/Publication (Full-Sized Images)','description'=>'Shows a blog or other publication on this page, with post images at full size','default_availability'=>'hidden'),
			'parent' => 'publication',
		),
		'publication_with_image_sidebar' => array(
			'fields' => array('title' => 'Blog/Publication with Sidebar Images','description'=>'Shows a blog or other publication on this page, plus a standard image sidebar','default_availability'=>'hidden'),
			'parent' => 'publication',
		),
		'publication_and_events_sidebar' => array(
			'fields' => array('title' => 'Blog/Publication Feed and Events Feed','description'=>'Shows two feeds in sidebar: blog posts and events','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'pagetype:events_and_publication_sidebar'),
			'parent' => NULL,
		),
		'events_and_publication_sidebar' => array(
			'fields' => array('title' => 'Events Feed and Blog/Publication Feed','description'=>'Shows two feeds in sidebar: events and blog posts','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'pagetype:publication_and_events_sidebar'),
			'parent' => 'publication_and_events_sidebar',
		),
		'events_and_publication_sidebar_show_children' => array(
			'fields' => array('title' => 'Events Feed and Blog/Publication Feed, with Children','description'=>'Shows two feeds in sidebar: events and blog posts, with children pages below content','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'pagetype:publication_and_events_sidebar,events_and_publication_sidebar,publication_related_and_events_sidebar_show_children'),
			'parent' => 'events_and_publication_sidebar',
		),
		'publication_related_and_events_sidebar_show_children' => array(
			'fields' => array('title' => 'Blog/Publication Feed and Events Feed, with Children','description'=>'Shows two feeds in sidebar: blog posts and events, with children pages below content','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'pagetype:publication_and_events_sidebar,events_and_publication_sidebar,events_and_publication_sidebar_show_children'),
			'parent' => 'events_and_publication_sidebar',
		),
		'publication_related_and_events_sidebar_show_children_no_title' => array(
			'fields' => array('title' => 'Blog/Publication Feed and Events Feed, with Children, No Page Title','description'=>'Shows two feeds in sidebar: blog posts and events, with children pages below content. Page title hidden.','default_availability'=>'hidden'),
			'parent' => 'publication_related_and_events_sidebar_show_children',
		),
		'events_and_publication_sidebar_by_page_categories' => array(
			'fields' => array('title' => 'Events Feed and Blog/Publication Feed (By Page Categories)','description'=>'Shows two feeds in sidebar: events and blog posts, filtered by page categories.','default_availability'=>'hidden'),
			'parent' => 'events_and_publication_sidebar',
		),
		'blurbs_with_events_and_publication_sidebar_by_page_categories' => array(
			'fields' => array('title' => 'Events Feed and Blog/Publication Feed (By Page Categories), plus Blurbs','description'=>'Shows two feeds in sidebar: events and blog posts, filtered by page categories. Extra bonus: blurbs below content.','default_availability'=>'hidden'),
			'parent' => 'events_and_publication_sidebar_by_page_categories',
		),
		'audio_video' => array(
			'fields' => array('title' => 'Media','description'=>'Shows audio and/or video after the page content. At least one media work must be associated with page for this to work.','default_availability'=>'hidden','availability_logic'=>'type:av'),
			'maximal_fields' => array('default_availability'=>'visible'),
			'parent' => NULL,
		),
		'feed_display_full' => array(
			'fields' => array('title' => 'Feed','description'=>'Shows contents of RSS/Atom feed.','default_availability'=>'hidden','availability_logic'=>'type:external_url'),
			'maximal_fields' => array('default_availability'=>'visible'),
			'parent' => NULL,
		),
		'feed_display_sidebar' => array(
			'fields' => array('title' => 'Feed in Sidebar','description'=>'Shows contents of RSS/Atom feed in the sidebar.','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:external_url'),
			'parent' => 'feed_display_full',
        ),
		'feed_display_sidebar_with_search' => array(
			'fields' => array('title' => 'Feed in Sidebar','description'=>'Shows contents of RSS/Atom feed in the sidebar, with search capability.','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:external_url'),
			'parent' => 'feed_display_full',
        ),
		'sidebar_blurb' => array(
			'fields' => array('title' => 'Sidebar Blurbs','description'=>'Moves blurbs into sidebar, replacing images.','default_availability'=>'hidden','availability_logic'=>'type:text_blurb'),
			'maximal_fields' => array('default_availability'=>'visible'),
			'parent' => 'default',
		),
		'sidebar_blurb_no_title' => array(
			'fields' => array('title' => 'Sidebar Blurbs, Page Title Hidden','description'=>'Moves blurbs into sidebar, replacing images, and hides the page title.','default_availability'=>'hidden'),
			'parent' => 'sidebar_blurb',
		),
		'image_sidebar_100x100' => array(
			'fields' => array('title' => 'Image Sidebar (100 px)','description'=>'Crops images in sidebar to 100x100 pixels square','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'image_sidebar_150x150',
		),
		'image_sidebar_150x150' => array(
			'fields' => array('title' => 'Image Sidebar (150 px)','description'=>'Crops images in sidebar to 150x150 pixels square','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'default',
		),
		'image_sidebar_200x200' => array(
			'fields' => array('title' => 'Image Sidebar (200 px)','description'=>'Crops images in sidebar to 200x200 pixels square','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'image_sidebar_150x150',
		),
		'image_sidebar_200x' => array(
			'fields' => array('title' => 'Image Sidebar (200 px wide)','description'=>'Crops images in sidebar to 200x200 pixels wide, retaining original aspect ratio','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'image_sidebar_150x150',
		),
		'image_sidebar_skip_first' => array(
			'fields' => array('title' => 'Image Sidebar (Skip First Image)','description'=>'Shows attached images in sidebar, omitting the first image. Allows an image in shows_children parent page but not on the page itself.','default_availability'=>'hidden'),
			'parent' => 'default',
		),
		'random_sidebar_images' => array(
			'fields' => array('title' => 'Random Images in Sidebar','description'=>'In place of the standard sidebar images, shows three randomized images from the set attached the to the current page.','default_availability'=>'hidden'),
			'parent' => 'default',
		),
		'random_blurb_and_sidebar_image' => array(
			'fields' => array('title' => 'Random Blurb and Images','description'=>'Displays a random blurb below the navigation, and three randomized images in the sidebar.','default_availability'=>'hidden'),
			'parent' => 'random_sidebar_images',
		),
		'images_under_nav' => array(
			'fields' => array('title' => 'Images Below Navigation','description'=>'Moves sidebar images below the site navigation.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'default',
		),
		'nav_below_content' => array(
			'fields' => array('title' => 'Navigation Below Content','description'=>'Displays the site navigation below the main page content.','default_availability'=>'hidden'),
			'parent' => 'default',
		),
		'assets' => array(
			'fields' => array('title' => 'Assets','description'=>'Lists assets in main content area','default_availability'=>'hidden'),
			'maximal_fields' => array('default_availability'=>'visible','availability_logic'=>'type:asset'),
			'parent' => NULL,
		),
		'assets_with_author_and_date' => array(
			'fields' => array('title' => 'Assets with Author and Date','description'=>'Lists assets in main content area, including their author and date fields','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:asset'),
			'parent' => 'assets',
		),
		'assets_by_date' => array(
			'fields' => array('title' => 'Assets Sorted By Date','description'=>'Lists assets in main content area, ordered by date','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:asset'),
			'parent' => 'assets',
		),
		'assets_by_category' => array(
			'fields' => array('title' => 'Assets Sorted By Category','description'=>'Lists assets in main content area, ordered by category','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:asset AND type:category_type'),
			'parent' => 'assets',
		),
		'assets_by_category_sorted_by_date' => array(
			'fields' => array('title' => 'Assets Sorted By Category and Date','description'=>'Lists assets in main content area, ordered by category, then date','default_availability'=>'hidden'),
			'parent' => 'assets_by_category',
		),
		'assets_by_author' => array(
			'fields' => array('title' => 'Assets Sorted By Author','description'=>'Lists assets in main content area, ordered by author','default_availability'=>'hidden'),
			'parent' => 'assets',
		),
		'audio_video_with_filters' => array(
			'fields' => array('title' => 'Media w/ Search','description'=>'Shows audio and/or video after the page content, with a search box. At least one media work must be associated with page for this to work.','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:av'),
			'parent' => 'audio_video',
		),
		'audio_video_with_filters_reverse_chronological' => array(
			'fields' => array('title' => 'Media w/ Search (Most Recent at Top)','description'=>'Shows audio and/or video after the page content, with a search box, in reverse chronological order','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:av'),
			'parent' => 'audio_video_with_filters',
		),
		'audio_video_on_current_site' => array(
			'fields' => array('title' => 'All Media in Site','description'=>'Shows all the audio and/or video in the current Reason site.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:av'),
			'parent' => 'audio_video',
		),
		'audio_video_on_current_site_with_filters' => array(
			'fields' => array('title' => 'All Media in Site, w/ Search','description'=>'Shows all the audio and/or video in the current Reason site, with a search box.','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'pagetype:audio_video_on_current_site_with_filters'),
			'parent' => 'audio_video_on_current_site',
		),
		'audio_video_on_current_site_no_nav' => array(
			'fields' => array('title' => 'All Media in Site (Hide Navigation)','description'=>'Shows all the audio and/or video in the current Reason site, with no site navigation','default_availability'=>'hidden'),
			'parent' => 'audio_video_on_current_site',
		),
		'audio_video_simple' => array(
			'fields' => array('title' => 'Simple Media','description'=>'Embeds attached audio and video directly in the page, with no additional clicking.','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:av'),
			'parent' => 'audio_video',
		),
		'audio_video_simple_360_wide' => array(
			'fields' => array('title' => 'Simple Media','description'=>'Embeds attached audio and video directly in the page, with no additional clicking. Media sized to 360 pixels wide.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:av'),
			'parent' => 'audio_video_simple',
		),
		'audio_video_simple_640_wide' => array(
			'fields' => array('title' => 'Simple Media','description'=>'Embeds attached audio and video directly in the page, with no additional clicking. Media sized to 640 pixels wide.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:av'),
			'parent' => 'audio_video_simple',
		),
		'audio_video_simple_sidebar' => array(
			'fields' => array('title' => 'Simple Media in Sidebar','description'=>'Embeds attached audio and video directly in the sidebar, with no additional clicking.','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:av'),
			'parent' => 'audio_video_simple',
		),
		'audio_video_unpaginated' => array(
			'fields' => array('title' => 'Media (No Pagination)','description'=>'Shows audio and/or video after the page content, upaginated.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:av'),
			'parent' => 'audio_video',
		),
		'audio_video_150x150_thumbnails' => array(
			'fields' => array('title' => 'Media (150px thumbnails)','description'=>'Shows audio and/or video after the page content, with 150 pixel square thumbnail images.','default_availability'=>'hidden'),
			'parent' => 'audio_video',
		),
		'audio_video_100x100_thumbnails' => array(
			'fields' => array('title' => 'Media (100px thumbnails)','description'=>'Shows audio and/or video after the page content, with 100 pixel square thumbnail images.','default_availability'=>'hidden'),
			'parent' => 'audio_video_150x150_thumbnails',
		),
		'audio_video_200x200_thumbnails' => array(
			'fields' => array('title' => 'Media (200px thumbnails)','description'=>'Shows audio and/or video after the page content, with 200 pixel square thumbnail images.','default_availability'=>'hidden'),
			'parent' => 'audio_video_150x150_thumbnails',
		),
		'audio_video_reverse_chronological' => array(
			'fields' => array('title' => 'Media (Most Recent at Top)','description'=>'Shows audio and/or video after the page content, in reverse chronological order.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:av'),
			'parent' => 'audio_video',
		),
		'audio_video_150x150_thumbnails_reverse_chronological' => array(
			'fields' => array('title' => 'Media (Most Recent at Top, 150px thumbnails)','description'=>'Shows audio and/or video after the page content, in reverse chronological order, with 150 pixel square thumbnail images.','default_availability'=>'hidden'),
			'parent' => 'audio_video_reverse_chronological',
		),
		'audio_video_100x100_thumbnails_reverse_chronological' => array(
			'fields' => array('title' => 'Media (Most Recent at Top, 100px thumbnails)','description'=>'Shows audio and/or video after the page content, in reverse chronological order, with 100 pixel square thumbnail images.','default_availability'=>'hidden'),
			'parent' => 'audio_video_150x150_thumbnails_reverse_chronological',
		),
		'audio_video_200x200_thumbnails_reverse_chronological' => array(
			'fields' => array('title' => 'Media (Most Recent at Top, 200px thumbnails)','description'=>'Shows audio and/or video after the page content, in reverse chronological order, with 200 pixel square thumbnail images.','default_availability'=>'hidden'),
			'parent' => 'audio_video_150x150_thumbnails_reverse_chronological',
		),
		'audio_video_sidebar_blurbs' => array(
			'fields' => array('title' => 'Media with Sidebar Blurbs','description'=>'Shows audio and/or video after the page content and blurbs in sidebar.','default_availability'=>'hidden'),
			'parent' => 'audio_video',
		),
		'audio_video_sidebar_blurbs_reverse_chronological' => array(
			'fields' => array('title' => 'Media (Most Recent at Top) with Sidebar Blurbs','description'=>'Shows audio and/or video after the page content, in reverse chronological order, and blurbs in sidebar.','default_availability'=>'hidden'),
			'parent' => 'audio_video_sidebar_blurb',
		),
		'audio_video_chronological' => array(
			'fields' => array('title' => 'Media (Oldest at Top)','description'=>'Shows audio and/or video after the page content, in chronological order.','default_availability'=>'hidden'),
			'parent' => 'audio_video',
		),
		'audio_video_sidebar' => array(
			'fields' => array('title' => 'Media in Sidebar','description'=>'Shows audio and/or video in the sidebar. Simple Media in Sidebar is likely better for most purposes.','default_availability'=>'hidden'),
			'parent' => 'audio_video',
		),
		'audio_video_sidebar_reverse_chronological' => array(
			'fields' => array('title' => 'Media in Sidebar (Most Recent at Top)','description'=>'Shows audio and/or video in the sidebar, in reverse chronological order. Simple Media in Sidebar is likely better for most purposes.','default_availability'=>'hidden'),
			'parent' => 'audio_video_sidebar',
		),
		'audio_video_sidebar_show_children' => array(
			'fields' => array('title' => 'Media in Sidebar, with Children','description'=>'Shows audio and/or video in the sidebar, and children pages below content.','default_availability'=>'hidden'),
			'parent' => 'audio_video_sidebar',
		),
		'audio_video_sidebar_show_children_reverse_chronological' => array(
			'fields' => array('title' => 'Media in Sidebar (Most Recent at Top) with Children','description'=>'Shows audio and/or video in the sidebar, in reverse chronological order, and children pages below content.','default_availability'=>'hidden'),
			'parent' => 'audio_video_sidebar_show_children',
		),
		'audio_video_offer_original_download' => array(
			'fields' => array('title' => 'Media (Original File Downloads)','description'=>'Shows audio and/or video, and (if integration library permits) a download of the original files.','default_availability'=>'hidden'),
			'parent' => 'audio_video',
		),
		'audio_video_media_above_description' => array(
			'fields' => array('title' => 'Media (Descriptions Below)','description'=>'Shows audio and/or video, with descriptions below the audio and video','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:av'),
			'parent' => 'audio_video',
		),
		'blurb' => array(
			'fields' => array('title' => 'Blurbs','description'=>'Places blurbs in main content area.','default_availability'=>'hidden','availability_logic'=>'type:text_blurb'),
			// Not quite identical to current defaults but I think it makes sense to keep available by default -- mr
			'parent' => NULL,
		),
		'blurb_first_under_nav_rest_below_content' => array(
			'fields' => array('title' => 'Blurbs (Split between navigation and content)','description'=>'Places first blurb under navigation, and the rest in main content area.','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:text_blurb'),
			'parent' => 'blurb',
		),
		'blurb_first_sidebar_others_under_navigation' => array(
			'fields' => array('title' => 'Blurbs (Split between sidebar and navigation)','description'=>'Places first blurb in sidebar, and the rest in under the navigation.','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:text_blurb'),
			'parent' => 'blurb',
		),
		'blurb_no_demotion_of_headings' => array(
			'fields' => array('title' => 'Blurbs (Unchanged Headings)','description'=>'Places blurbs in main content area, leaving headings unchanged.','default_availability'=>'hidden'),
			'parent' => 'blurb',
		),
		'blurb_no_nav' => array(
			'fields' => array('title' => 'Blurbs (No Navigation)','description'=>'Places blurbs in main content area and hides site navigation.','default_availability'=>'hidden'),
			'parent' => 'blurb',
		),
		'blurb_under_nav_and_below_content' => array(
			'fields' => array('title' => 'Blurbs (Below navigation and content)','description'=>'Places all blurbs both in main content area and below navigation. All blurbs appear twice.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:text_blurb'),
			'parent' => 'blurb',
		),
		'blurb_with_children' => array(
			'fields' => array('title' => 'Blurbs And Children','description'=>'Places blurbs in main content area and lists children pages beneath.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:text_blurb'),
			'parent' => 'blurb',
		),
		'blurb_with_siblings' => array(
			'fields' => array('title' => 'Blurbs And Siblings','description'=>'Places blurbs in main content area and lists sibling pages beneath.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:text_blurb'),
			'parent' => 'blurb',
		),
		'blurb_with_children_sidebar' => array(
			'fields' => array('title' => 'Blurbs (Children Sidebar)','description'=>'Places blurbs in main content area and lists children pages in the sidebar.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:text_blurb'),
			'parent' => 'blurb_with_children',
		),
		'blurb_with_siblings_sidebar' => array(
			'fields' => array('title' => 'Blurbs (Siblings Sidebar)','description'=>'Places blurbs in main content area and lists sibling pages in the sidebar.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:text_blurb'),
			'parent' => 'blurb_with_siblings',
		),
		'blurb_2_columns' => array(
			'fields' => array('title' => 'Blurbs (2 Columns)','description'=>'Places blurbs in main content area, separated into two columns.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:text_blurb'),
			'parent' => 'blurb',
		),
		'one_blurb_subnav_others_sidebar' => array(
			'fields' => array('title' => 'Blurbs (Split between navigation and sidebar)','description'=>'Places first blurb under navigation, and the rest in the sidebar.','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:text_blurb'),
			'parent' => 'blurb',
		),

		'child_sites' => array(
			'fields' => array('title' => 'Child Sites','description'=>'Lists child sites of the current site.','default_availability'=>'hidden'),
		),
		'child_sites_with_top_pages' => array(
			'fields' => array('title' => 'Child Sites w/ Top Pages','description'=>'Lists child sites of the current site, plus their top pages.','default_availability'=>'hidden'),
			'parent' => 'child_sites',
		),
		'child_sites_with_top_pages_nonav' => array(
			'fields' => array('title' => 'Child Sites w/ Top Pages (Hide non-nav pages)','description'=>'Lists child sites of the current site, plus any top pages shown in the site navigation.','default_availability'=>'hidden'),
			'parent' => 'child_sites',
		),
		'children_and_grandchildren' => array(
			'fields' => array('title' => 'Children and Grandchildren Pages','description'=>'Shows child and grandchild pages in a list with their descriptions. Note: this includes pages not shown in navigation.','default_availability'=>'hidden'),
			'maximal_fields' => array('default_availability'=>'available'),
			'parent' => 'show_children',
		),
		'children_and_grandchildren_no_page_title' => array(
			'fields' => array('title' => 'Children and Grandchildren (Page Title Hidden)','description'=>'Shows child and grandchild pages in a list with their descriptions, and hides the title of the current page. Note: this includes pages not shown in navigation.','default_availability'=>'hidden'),
			'parent' => 'children_and_grandchildren',
		),
		'children_and_grandchildren_no_nav' => array(
			'fields' => array('title' => 'Children and Grandchildren (Hide Navigation)','description'=>'Shows child and grandchild pages in a list with their descriptions, and hides site navigation. Note: this includes pages not shown in navigation.','default_availability'=>'hidden'),
			'parent' => 'children_and_grandchildren',
		),
		'children_and_grandchildren_no_nav_no_page_title' => array(
			'fields' => array('title' => 'Children and Grandchildren (Hide Navigation and Page Title)','description'=>'Shows child and grandchild pages in a list with their descriptions, and hides site navigation and the title of the current page. Note: this includes pages not shown in navigation.','default_availability'=>'hidden'),
			'parent' => 'children_and_grandchildren',
		),
		'children_and_grandchildren_full_names' => array(
			'fields' => array('title' => 'Children and Grandchildren (Full Page Names)','description'=>'Shows child and grandchild pages in a list with their descriptions, using full page names. Note: this includes pages not shown in navigation.','default_availability'=>'hidden'),
			'maximal_fields' => array('default_availability'=>'visible'),
			'parent' => 'children_and_grandchildren',
		),
		'children_and_grandchildren_full_names_h3' => array(
			'fields' => array('title' => 'Children and Grandchildren (Full Page Names/h3)','description'=>'Shows child and grandchild pages in a list with their descriptions, using full page names in h3 headings. Note: this includes pages not shown in navigation.','default_availability'=>'hidden'),
			'parent' => 'children_and_grandchildren_full_names',
		),
		'children_and_grandchildren_sidebar_blurbs' => array(
			'fields' => array('title' => 'Children and Grandchildren with Sidebar Blurbs','description'=>'Shows child and grandchild pages in a list with their descriptions, plus blurbs in sidebar.','default_availability'=>'hidden'),
			'parent' => 'children_and_grandchildren',
		),
		'children_and_grandchildren_full_names_sidebar_blurbs_no_title' => array(
			'fields' => array('title' => 'Children and Grandchildren (Full Page Names)/No Page Title/Sidebar Blurbs','description'=>'Shows child and grandchild pages in a list with their descriptions, using full page names. No page title; blurbs in sidebar.','default_availability'=>'hidden'),
			'parent' => 'children_and_grandchildren_sidebar_blurbs',
		),
		'children_before_content' => array(
			'fields' => array('title' => 'Children Pages Above Content','description'=>'Shows child pages before the page content, in a list with their descriptions. Note: this includes pages not shown in navigation.','default_availability'=>'hidden'),
			'maximal_fields' => array('default_availability'=>'available'),
			'parent' => 'show_children',
		),
		'children_no_nav' => array(
			'fields' => array('title' => 'Children (Hide Navigation)','description'=>'Shows child pages in a list with their descriptions, and hides site navigation. Note: this includes pages not shown in navigation.','default_availability'=>'hidden'),
			'parent' => 'show_children',
		),
		'children_no_nav_no_title' => array(
			'fields' => array('title' => 'Children (Hide Navigation and Page Title)','description'=>'Shows child pages in a list with their descriptions, and hides site navigation and page title. Note: this includes pages not shown in navigation.','default_availability'=>'hidden'),
			'parent' => 'children_no_nav',
		),
		'show_children_hide_non_nav' => array(
			'fields' => array('title' => 'Children (Only Those in Site Nav)','description'=>'Shows child pages in a list with their descriptions. Only includes pages shown in navigation.','default_availability'=>'hidden'),
			'maximal_fields' => array('default_availability'=>'visible'),
			'parent' => 'show_children',
		),
		'show_children_hide_non_nav_sidebar_blurbs' => array(
			'fields' => array('title' => 'Children (Only Those in Site Nav, plus Sidebar Blurbs)','description'=>'Shows child pages in a list with their descriptions. Only includes pages shown in navigation. Blurbs shown in sidebar.','default_availability'=>'hidden'),
			'parent' => 'show_children_hide_non_nav',
		),
		'show_children_no_title' => array(
			'fields' => array('title' => 'Children (Hide Page Title)','description'=>'Shows child pages in a list with their descriptions, and hides page title. Note: this includes pages not shown in navigation.','default_availability'=>'hidden'),
			'parent' => 'show_children',
		),
		'show_children_no_nav' => array(
			'fields' => array('title' => 'Children (Hide Navigation, Duplicate)','description'=>'Shows child pages in a list with their descriptions, and hides site navigation. Note: this includes pages not shown in navigation.','default_availability'=>'hidden'),
			'parent' => 'children_no_nav',
		),
		'show_children_no_nav_hide_non_nav' => array(
			'fields' => array('title' => 'Children (Only Those in Site Nav, Hide Navigation)','description'=>'Shows child pages in a list with their descriptions, and hides site navigation. Only includes pages shown in navigation.','default_availability'=>'hidden'),
			'parent' => 'children_no_nav',
		),
		'show_children_no_nav_no_title' => array(
			'fields' => array('title' => 'Children (Hide Navigation &amp; Page Title)','description'=>'Shows child pages, and hides site navigation. Only includes pages shown in navigation.','default_availability'=>'hidden'),
			'parent' => 'children_no_nav',
		),
		'show_children_images_replace_nav' => array(
			'fields' => array('title' => 'Children, Images Replace Navigation','description'=>'Shows child pages in main section, sidebar images replace navigation. ','default_availability'=>'hidden'),
			'parent' => 'children_no_nav',
		),
		'show_children_with_az_list' => array(
			'fields' => array('title' => 'Children with Index','description'=>'Shows child pages with an A-Z index. Useful for large sets of pages.','default_availability'=>'hidden'),
			'maximal_fields' => array('default_availability'=>'visible'),
			'parent' => 'show_children',
		),
		'show_children_with_first_images' => array(
			'fields' => array('title' => 'Children with Images','description'=>'Shows child pages with their first images.','default_availability'=>'hidden'),
			'maximal_fields' => array('default_availability'=>'visible','availability_logic'=>'type:image'),
			'parent' => 'show_children',
		),
		'show_children_with_first_images_100x100' => array(
			'fields' => array('title' => 'Children with Images (100 px)','description'=>'Shows child pages with their first images, sized to 100 pixels square.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'show_children_with_first_images',
		),
		'show_children_with_first_images_150x150' => array(
			'fields' => array('title' => 'Children with Images (150 px)','description'=>'Shows child pages with their first images, sized to 150 pixels square.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'show_children_with_first_images',
		),
		'show_children_with_first_images_200x200' => array(
			'fields' => array('title' => 'Children with Images (200 px)','description'=>'Shows child pages with their first images, sized to 200 pixels square.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'show_children_with_first_images',
		),
		'show_children_with_first_images_no_nav' => array(
			'fields' => array('title' => 'Children with Images (Hidden Navigation)','description'=>'Shows child pages with their first images; site navigation hidden','default_availability'=>'hidden'),
			'parent' => 'show_children_with_first_images',
		),
		'show_children_with_random_images' => array(
			'fields' => array('title' => 'Children with Images (Randomized)','description'=>'Shows child pages with their first images, selected at random.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'show_children_with_first_images',
		),
		'show_children_with_random_images_100x100' => array(
			'fields' => array('title' => 'Children with Images (Randomized, 100px)','description'=>'Shows child pages with their first images, selected at random and sized to 100 pixels square.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'show_children_with_random_images',
		),
		'show_children_with_random_images_150x150' => array(
			'fields' => array('title' => 'Children with Images (Randomized, 150px)','description'=>'Shows child pages with their first images, selected at random and sized to 150 pixels square.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'show_children_with_random_images',
		),
		'show_children_with_random_images_200x200' => array(
			'fields' => array('title' => 'Children with Images (Randomized, 200px)','description'=>'Shows child pages with their first images, selected at random and sized to 200 pixels square.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'show_children_with_random_images',
		),
		'sidebar_children' => array(
			'fields' => array('title' => 'Children in Sidebar','description'=>'Shows child pages in the sidebar. Note: this includes pages not shown in navigation.','default_availability'=>'hidden'),
			'maximal_fields' => array('default_availability'=>'available'),
			'parent' => 'show_children',
		),
		'children_with_one_blurb_subnav_others_sidebar' => array(
			'fields' => array('title' => 'Children with Split Sidebar Blurbs','description'=>'Shows child pages below main content, the first blurb under the navigation, and the rest in the sidebar. Note: this includes pages not shown in navigation.','default_availability'=>'hidden'),
			'maximal_fields' => array('default_availability'=>'visible'),
			'parent' => 'show_children',
		),
		'sidebar_blurb_and_children_no_title' => array(
			'fields' => array('title' => 'Children with Sidebar Blurbs (Title Hidden)','description'=>'Shows child pages below main content and blurbs in the sidebar, and hides page title. Note: this includes pages not shown in navigation.','default_availability'=>'hidden'),
			'parent' => 'show_children',
		),
		'sidebar_blurb_and_children_with_images' => array(
			'fields' => array('title' => 'Children with Sidebar Images &amp; Blurbs','description'=>'Shows child pages below main content, and images and blurbs in the sidebar. Note: this includes pages not shown in navigation.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image AND type:text_blurb'),
			'parent' => 'show_children',
		),
		'show_children_and_random_images' => array(
			'fields' => array('title' => 'Children with Randomized Sidebar Images','description'=>'Shows child pages below main content, and shows a random set of four images in the sidebar. Note: this includes pages not shown in navigation.','default_availability'=>'hidden'),
			'parent' => 'show_children',
		),
		'children_and_siblings' => array(
			'fields' => array('title' => 'Children and Siblings','description'=>'Shows child pages below main content, siblings in the sidebar. Note: this includes pages not shown in navigation.','default_availability'=>'hidden'),
			'maximal_fields' => array('default_availability'=>'visible'),
			'parent' => 'show_children',
		),
		'children_and_sidebar_blurbs' => array(
			'fields' => array('title' => 'Children with Sidebar Blurbs','description'=>'Shows child pages below main content, blurbs in the sidebar. Note: this includes pages not shown in navigation.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:text_blurb'),
			'parent' => 'show_children',
		),
		'children_and_sidebar_blurbs_no_nav' => array(
			'fields' => array('title' => 'Children with Sidebar Blurbs, No Navigation','description'=>'Shows child pages below main content, blurbs in the sidebar. Site navigation hidden. Note: this includes pages not shown in navigation.','default_availability'=>'hidden'),
			'parent' => 'children_and_sidebar_blurbs',
		),
		'children_before_content_sidebar_blurbs' => array(
			'fields' => array('title' => 'Children above Content, with Sidebar Blurbs','description'=>'Shows child pages above main content, blurbs in the sidebar. Note: this includes pages not shown in navigation.','default_availability'=>'hidden'),
			'parent' => 'children_and_sidebar_blurbs',
		),
		'events' => array(
			'fields' => array('title' => 'Events Calendar','description'=>'Shows a calendar of all events on the current site.','default_availability'=>'hidden'),
			'maximal_fields' => array('default_availability'=>'visible'),
			'parent' => NULL,
		),
		'events_cache_1_hour' => array(
			'fields' => array('title' => 'Events (1-Hour Cache)','description'=>'Shows a calendar of all events on the current site, with an hour-long cache for performance.','default_availability'=>'hidden'),
		),
		'events_archive' => array(
			'fields' => array('title' => 'Events Archive','description'=>'Lists all events on site, in chronological order.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:event_type'),
			'parent' => 'events',
		),
		'events_archive_verbose' => array(
			'fields' => array('title' => 'Events Archive (Extended Descriptions)','description'=>'Lists all events on site, in chronological order, with extended descriptions in the list.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:event_type'),
			'parent' => 'events_archive',
		),
		'events_hybrid' => array(
			'fields' => array('title' => 'Events (Hybrid Listing)','description'=>'Lists upcopming events first, then all historical events on site in chronological order.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:event_type'),
			'parent' => 'events',
		),
		'events_hybrid_verbose' => array(
			'fields' => array('title' => 'Events Archive (Hybrid Listing w/ Extended Descriptions)','description'=>'Lists upcopming events first, then all historical events on site in chronological order, with extended descriptions in the list.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:event_type'),
			'parent' => 'events_hybrid',
		),
		'events_gallery_archive' => array(
			'fields' => array('title' => 'Events Gallery (Archive)','description'=>'Simplified listing of past events, with images.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:event_type'),
			'parent' => 'events_archive',
		),
		'events_archive_nav_below' => array(
			'fields' => array('title' => 'Events Archive (Site Navigation Below Listing)','description'=>'Lists past events, and places site navigation beneath the events listing.','default_availability'=>'hidden'),
			'parent' => 'events_archive',
		),
		'events_nonav' => array(
			'fields' => array('title' => 'Events (Hide Navigation)','description'=>'Shows a calendar of all events on the current site, and hides the site navigation.','default_availability'=>'hidden'),
			'parent' => 'events',
		),
		'events_sidebar' => array(
			'fields' => array('title' => 'Events Feed in Sidebar','description'=>'Lists site events in the sidebar; links go to separate calendar page.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:event_type'),
			'parent' => 'events',
		),
		'events_instancewide' => array(
			'fields' => array('title' => 'Events (Instancewide)','description'=>'Lists all shared events in Reason from live sites. NOTE: this module may be slow for large Reason installations.','default_availability'=>'hidden'),
			'parent' => 'events',
		),
		'events_sidebar_by_page_categories' => array(
			'fields' => array('title' => 'Events Feed in Sidebar (Filtered by Categories)','description'=>'Lists site events in the sidebar, filtered by the categories associated with the current page; links go to separate calendar page.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:event_type'),
			'parent' => 'events_sidebar',
		),
		'blurbs_with_events_sidebar_by_page_categories' => array(
			'fields' => array('title' => 'Events Feed in Sidebar (Filtered by Categories), Plus Blurbs','description'=>'Lists site events in the sidebar, filtered by the categories associated with the current page. Shows blurbs below main page content.','default_availability'=>'hidden'),
			'parent' => 'events_sidebar_by_page_categories',
		),
		'events_sidebar_grouped_by_category' => array(
			'fields' => array('title' => 'Events Feed in Sidebar (Grouped by Category)','description'=>'Lists site events in the sidebar, grouped by category; links go to separate calendar page.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:event_type'),
			'parent' => 'events_sidebar',
		),
		'events_sidebar_grouped_by_page_categories' => array(
			'fields' => array('title' => 'Events Feed in Sidebar (Filtered and Grouped by Categories)','description'=>'Lists site events in the sidebar, filtered and grouped by the categories associated with the current page; links go to separate calendar page.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:event_type'),
			'parent' => 'events_sidebar',
		),
		'events_sidebar_show_children' => array(
			'fields' => array('title' => 'Events Feed in Sidebar, with Children Pages','description'=>'Lists site events in the sidebar, and children pages below main page content. All children pages listed, including those hidden from the navigation.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:event_type'),
			'parent' => 'events_sidebar',
		),
		'events_sidebar_show_nav_children' => array(
			'fields' => array('title' => 'Events Feed in Sidebar, with Children Pages (Nav Only)','description'=>'Lists site events in the sidebar, and children pages below main page content. Only shows children pages listed in the navigation.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:event_type'),
			'parent' => 'events_sidebar_show_children',
		),
		'events_sidebar_show_children_random_images_in_subnav' => array(
			'fields' => array('title' => 'Events Feed in Sidebar, with Children Pages and Random Images Below Nav','description'=>'Lists site events in the sidebar, children pages below main page content, and 2 random attached images.','default_availability'=>'hidden'),
			'parent' => 'events_sidebar_show_children',
		),
		'events_sidebar_more_show_children' => array(
			'fields' => array('title' => 'Events Feed in Sidebar (Extra), with Children Pages','description'=>'Lists site events in the sidebar, showing more than standard, and children pages below main page content.','default_availability'=>'hidden'),
			'parent' => 'events_sidebar_show_children',
		),
		'events_and_images_sidebar_show_children' => array(
			'fields' => array('title' => 'Events Feed in Sidebar, with Images and Children','description'=>'Lists site events in the sidebar, below any images attached to the page. Children pages listed below content.','default_availability'=>'hidden'),
			'parent' => 'events_sidebar',
		),
		'events_verbose' => array(
			'fields' => array('title' => 'Events Calendar (Verbose)','description'=>'Shows a calendar of all events on the current site; lists events with extended descriptions and locations.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:event_type'),
			'parent' => 'events',
		),	
		'events_schedule' => array(
			'fields' => array('title' => 'Events Calendar (Schedule Listing)','description'=>'Shows a calendar of all events on the current site; groups events by time.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:event_type'),
			'parent' => 'events',
		),	
		'events_verbose_nonav' => array(
			'fields' => array('title' => 'Events Calendar (Verbose, Site Navigation Hidden)','description'=>'Same as Events Calendar (Verbose), but also hides the site\'s navigation.','default_availability'=>'hidden'),
			'parent' => 'events_verbose',
		),
		'faculty' => array(
			'fields' => array('title' => 'Faculty &amp; Staff','description'=>'List of Faculty/Staff for the current site.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:faculty_staff'),
			'parent' => NULL,
		),
		'faculty_first' => array(
			'fields' => array('title' => 'Faculty &amp; Staff Above Content','description'=>'Faculty/Staff for the current site, listed above the page content.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:faculty_staff'),
			'parent' => 'faculty',
		),
		'faculty_no_nav' => array(
			'fields' => array('title' => 'Faculty &amp; Staff (Navigation Hidden)','description'=>'Faculty/Staff for the current site, with the site navigation hidden.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:faculty_staff'),
			'parent' => 'faculty',
		),
		'faculty_sidebar_children' => array(
			'fields' => array('title' => 'Faculty &amp; Staff, with Children in Sidebar','description'=>'Faculty/Staff for the current site, with children pages listed in the sidebar.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:faculty_staff'),
			'parent' => 'faculty',
		),
		'faculty_with_sidebar_blurbs' => array(
			'fields' => array('title' => 'Faculty &amp; Staff, with Blurbs in Sidebar','description'=>'Faculty/Staff for the current site, with text blurbs in the sidebar.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:faculty_staff'),
			'parent' => 'faculty',
		),
		'faqs' => array(
			'fields' => array('title' => 'FAQs','description'=>'Listing of FAQs on the current site.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:faq_type'),
			'parent' => NULL,
		),
		'faqs_ordered_by_keywords' => array(
			'fields' => array('title' => 'FAQs','description'=>'Listing of FAQs on the current site, ordered by the keyword field.','default_availability'=>'hidden'),
			'parent' => 'faqs',
		),
		'feature' => array(
			'fields' => array('title' => 'Features','description'=>'Displays a slideshow of feature items attached to the current page.','default_availability'=>'hidden'),
			'maximal_fields' => array('default_availability'=>'hidden'),
			'maximal_fields' => array('default_availability'=>'visible', 'availability_logic'=>'type:feature_type'),
			'parent' => NULL,
		),
		'feature_shuffled' => array(
			'fields' => array('title' => 'Features (Shuffled)','description'=>'Displays a slideshow of feature items attached to the current page, in random order.','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:feature_type'),
			'parent' => 'feature',
		),
		'feature_av' => array(
			'fields' => array('title' => 'Features (Embedded Media)','description'=>'Same as Features, but with Audio/Video embedded directly instead of in a pop-up.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:feature_type'),
			'parent' => 'feature',
		),
		'feature_autoplay' => array(
			'fields' => array('title' => 'Features (Autoplay)','description'=>'Same as Features, but cycles automatically through the feature items.','default_availability'=>'hidden','availability_logic'=>'type:feature_type'),
			'maximal_fields' => array('availability_logic'=>'type:feature_type'),
			'parent' => 'feature',
		),
		'feature_before_content_sidebar_blurbs_first_under_subnav' => array(
			'fields' => array('title' => 'Features with Sidebar Blurbs','description'=>'Features above content; first blurb under navigation and remaining blurbs in sidebar.','default_availability'=>'hidden'),
			'parent' => 'feature',
		),
		'feature_after_content_sidebar_blurbs_first_under_subnav' => array(
			'fields' => array('title' => 'Features with Sidebar Blurbs','description'=>'Features <em>below</em> content; first blurb under navigation and remaining blurbs in sidebar.','default_availability'=>'hidden'),
			'parent' => 'feature_before_content_sidebar_blurbs_first_under_subnav',
		),
		'gallery_above_content' => array(
			'fields' => array('title' => 'Photo Gallery, Content Beneath','description'=>'Shows associated images in a gallery format, and places page content beneath','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:image'),
			'parent' => 'gallery',
		),
		'gallery_chronological' => array(
			'fields' => array('title' => 'Photo Gallery (Chronological)','description'=>'Shows associated images in a gallery format, in chronological order (oldest first)','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:image'),
			'parent' => 'gallery',
		),
		'gallery_reverse_chronological' => array(
			'fields' => array('title' => 'Photo Gallery (Reverse Chronological)','description'=>'Shows associated images in a gallery format, in reverse chronological order (newest first)','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:image'),
			'parent' => 'gallery',
		),
		'gallery_entire_site' => array(
			'fields' => array('title' => 'Photo Gallery (Entire Site)','description'=>'Shows all the current site\'s images in a gallery format','default_availability'=>'hidden'),
			'maximal_fields' => array('visbility_logic'=>'type:image'),
			'parent' => 'gallery',
		),
		'gallery_entire_site_no_nav' => array(
			'fields' => array('title' => 'Photo Gallery (Entire Site, Navigation Hidden)','description'=>'Shows all the current site\'s images in a gallery format, and hides the site navigation','default_availability'=>'hidden'),
			'parent' => 'gallery_entire_site',
		),
		'gallery_entire_site_no_nav_no_title' => array(
			'fields' => array('title' => 'Photo Gallery (Entire Site, Navigation &amp; Page Title Hidden)','description'=>'Shows all the current site\'s images in a gallery format, and hides the site navigation and page title','default_availability'=>'hidden'),
			'parent' => 'gallery_entire_site',
		),
		'gallery_first_nav_below' => array(
			'fields' => array('title' => 'Shows associated images in a gallery format, and places site navigation beneath','description'=>'','default_availability'=>'hidden'),
			'parent' => 'gallery',
		),
		'gallery_no_nav' => array(
			'fields' => array('title' => 'Photo Gallery (Navigation Hidden)','description'=>'Shows associated images in a gallery format, with site navigation hidden','default_availability'=>'hidden'),
			'maximal_fields' => array('visbility_logic'=>'type:image'),
			'parent' => 'gallery',
		),
		'gallery_single_page' => array(
			'fields' => array('title' => 'Photo Gallery (Single Page)','description'=>'Shows associated images in a gallery format, with no pagination','default_availability'=>'hidden'),
			'maximal_fields' => array('visbility_logic'=>'type:image'),
			'parent' => 'gallery',
		),
		'gallery_vote' => array(
			'fields' => array('title' => 'Photo Contest Voting','description'=>'A photo gallery that allows visitors to vote for their favorite. For photo contests.','default_availability'=>'hidden'),
			'maximal_fields' => array('visbility_logic'=>'type:image'),
			'parent' => 'gallery',
		),
		'gallery_24_per_page' => array(
			'fields' => array('title' => 'Photo Gallery (24/Page)','description'=>'Shows associated images in a gallery format, with 24 images per page','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:image'),
			'parent' => 'gallery',
		),
		'gallery_24_per_page_sidebar_blurb' => array(
			'fields' => array('title' => 'Photo Gallery (24/Page), with sidebar blurbs','description'=>'Shows associated images in a gallery format, with 24 images per page; also places blurbs in sidebar','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:image AND type:text_blurb'),
			'parent' => 'gallery_24_per_page',
		),
		'gallery_100x100_thumbnails' => array(
			'fields' => array('title' => 'Photo Gallery (100x100 Thumbs)','description'=>'Shows associated images in a gallery format, with 100x100 pixel square thumbnails','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'gallery_150x150_thumbnails',
		),
		'gallery_150x150_thumbnails' => array(
			'fields' => array('title' => 'Photo Gallery (150x150 Thumbs)','description'=>'Shows associated images in a gallery format, with 150x150 pixel square thumbnails','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'gallery',
		),
		'gallery_200x200_thumbnails' => array(
			'fields' => array('title' => 'Photo Gallery (200x200 Thumbs)','description'=>'Shows associated images in a gallery format, with 200x200 pixel square thumbnails','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'gallery_150x150_thumbnails',
		),
		'gallery_640px' => array(
			'fields' => array('title' => 'Photo Gallery (640px Images)','description'=>'Shows associated images in a gallery format, sized to 640 pixels','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'gallery',
		),
		'gallery_720px' => array(
			'fields' => array('title' => 'Photo Gallery (720px Images)','description'=>'Shows associated images in a gallery format, sized to 720 pixels','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'gallery_640px',
		),
		'gallery_800px' => array(
			'fields' => array('title' => 'Photo Gallery (800px Images)','description'=>'Shows associated images in a gallery format, sized to 800 pixels','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'gallery_640px',
		),
		'gallery_above_assets' => array(
			'fields' => array('title' => 'Photo Gallery, with Assets Below','description'=>'Shows associated images in a gallery format, and lists attached assets below.','default_availability'=>'hidden'),
			'parent' => 'gallery',
		),
		'gallery_above_blurbs' => array(
			'fields' => array('title' => 'Photo Gallery, with Blurbs Below','description'=>'Shows associated images in a gallery format, and lists attached test blurbs below.','default_availability'=>'hidden'),
			'parent' => 'gallery',
		),
		'image_slideshow' => array(
			'fields' => array('title' => 'Slideshow','description'=>'Shows associated images in a sideshow format','default_availability'=>'hidden'),
			'maximal_fields' => array('default_availability'=>'visible','availability_logic'=>'type:image'),
			'parent' => NULL,
		),
		'image_slideshow_500x375' => array(
			'fields' => array('title' => 'Slideshow (500x375px)','description'=>'Shows associated images in a sideshow format; all images cropped to 500x375 pixels','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:image'),
			'parent' => 'image_slideshow',
		),
		'image_slideshow_before_content' => array(
			'fields' => array('title' => 'Slideshow Above Content','description'=>'Shows associated images in a sideshow format; page content beneath','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:image'),
			'parent' => 'image_slideshow',
		),
		'image_slideshow_before_content_one_blurb_subnav_others_sidebar' => array(
			'fields' => array('title' => 'Slideshow Above Content (Split Blurbs)','description'=>'Shows associated images in a sideshow format; page content beneath, first blurb below navigation, rest in sidebar','default_availability'=>'hidden'),
			'parent' => 'image_slideshow_before_content',
		),
		'image_slideshow_before_content_no_nav' => array(
			'fields' => array('title' => 'Slideshow Above Content (Navigation Hidden)','description'=>'Shows associated images in a sideshow format; site navigation hidden','default_availability'=>'hidden'),
			'parent' => 'image_slideshow_before_content',
		),
		'image_slideshow_manual' => array(
			'fields' => array('title' => 'Slideshow (Manual)','description'=>'Places associated images in a sideshow; slideshow does not auto-play','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:image'),
			'parent' => 'image_slideshow',
		),
		'image_slideshow_manual_500x375' => array(
			'fields' => array('title' => 'Slideshow (Manual, 500x375px)','description'=>'Places associated images in a 500x375 pixel sideshow ; slideshow does not auto-play','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:image'),
			'parent' => 'image_slideshow',
		),
		'images_full_size' => array(
			'fields' => array('title' => 'Simple Images','description'=>'Embeds attached images directly on the page.'),
			'maximal_fields' => array('availability_logic'=>'type:image'),
			'parent' => NULL,
		),
		'images_640w' => array(
			'fields' => array('title' => 'Simple Images (640px wide)','description'=>'Embeds attached images (sized to 640 pixels wide) directly on the page.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'images_full_size',
		),
		'images_800w' => array(
			'fields' => array('title' => 'Simple Images (800px wide)','description'=>'Embeds attached images (sized to 800 pixels wide) directly on the page.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'images_640w',
		),
		'images_640x480' => array(
			'fields' => array('title' => 'Simple Images (640x480)','description'=>'Embeds attached images (sized to 640x480 pixels) directly on the page.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'images_640w',
		),
		'images_full_size_before_content' => array(
			'fields' => array('title' => 'Simple Images Before Content','description'=>'Embeds attached images directly on the page, above the page content.','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:image'),
			'parent' => 'images_full_size',
		),
		'images_full_size_before_content_randomized_single' => array(
			'fields' => array('title' => 'Single Random Image Before Content','description'=>'Embeds a single, random image directly on the page, above the page content.','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:image'),
			'parent' => 'images_full_size_before_content',
		),
		'images_full_size_one_at_a_time' => array(
			'fields' => array('title' => 'Single Image','description'=>'Embeds a single image directly on the page.','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:image'),
			'parent' => 'images_full_size',
		),
		'images_full_size_single_page' => array(
			'fields' => array('title' => 'Simple Images (No Pagination)','description'=>'Embeds attached images directly on the page; no pagination for large image sets.','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:image'),
			'parent' => 'images_full_size',
		),
		
		/* Landing pages (home-page-style complex page types) */
		'feature_before_content_sidebar_events_news' => array(
			'fields' => array('title' => 'Landing page (Features/Events/News)','description'=>'Landing page: Features above page content, with events and news feeds in the sidebar.','default_availability'=>'hidden'),
			'parent' => NULL,
		),
		'feature_before_content_sidebar_events' => array(
			'fields' => array('title' => 'Landing page (Features/Events)','description'=>'Landing page: Features above page content, with events feed in the sidebar.','default_availability'=>'hidden'),
			'parent' => NULL,
		),
		'feature_after_content_sidebar_events' => array(
			'fields' => array('title' => 'Landing page (Features/Events 2)','description'=>'Landing page: Features <em>below</em> page content, with events feed in the sidebar.','default_availability'=>'hidden'),
			'parent' => NULL,
		),
		'feature_slow_after_content_sidebar_news_events' => array(
			'fields' => array('title' => 'Landing page (Slow Features/Events/News)','description'=>'Landing page: Features above page content with 5 second delay, with events and news feeds in the sidebar.','default_availability'=>'hidden'),
			'parent' => NULL,
		),
		'image_slideshow_before_content_publication_sidebar' => array(
			'fields' => array('title' => 'Landing page (Slideshow/Publication)','description'=>'Landing page: photo slideshow above page content, with news feed in the sidebar.','default_availability'=>'hidden'),
			'parent' => NULL,
		),
		'image_slideshow_before_content_events_sidebar' => array(
			'fields' => array('title' => 'Landing page (Slideshow/Events)','description'=>'Landing page: photo slideshow above page content, with events feed in the sidebar.','default_availability'=>'hidden'),
			'parent' => NULL,
		),
		/* End landing pages */
		
		'jobs' => array(
			'fields' => array('title' => 'Jobs','description'=>'Lists jobs on the current site.','default_availability'=>'hidden'),
			'parent' => NULL,
		),
		'live_sites' => array(
			'fields' => array('title' => 'Reason Sites','description'=>'Lists all live Reason sites.','default_availability'=>'hidden'),
			'parent' => NULL,
		),
		'minutes' => array(
			'fields' => array('title' => 'Minutes','description'=>'Lists all minutes on the current site.','default_availability'=>'hidden'),
			'parent' => NULL,
		),
		'publication_sidebar' => array(
			'fields' => array('title' => 'Publication Feed in Sidebar','description'=>'Lists the latest posts from related publications.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:publication_type'),
			'parent' => NULL,
		),
		'publication_sidebar_7_headlines' => array(
			'fields' => array('title' => 'Publication Feed in Sidebar (7 posts)','description'=>'Lists the latest 7 posts from related publications.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:publication_type'),
			'parent' => 'publication_sidebar',
		),
		'publication_sidebar_images_above' => array(
			'fields' => array('title' => 'Publication Feed in Sidebar (Below Images)','description'=>'Lists the latest posts from related publications, below any images attached to the page.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:publication_type AND type:image'),
			'parent' => 'publication_sidebar',
		),
		'publication_sidebar_two_month_expiration' => array(
			'fields' => array('title' => 'Publication Feed in Sidebar (2 Mo. Expiration)','description'=>'Lists the latest posts from related publications; posts older than 2 months fall out of feed.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:publication_type'),
			'parent' => 'publication_sidebar',
		),
		'publication_and_blurbs_sidebar' => array(
			'fields' => array('title' => 'Publication Feed in Sidebar w/ Blurbs','description'=>'Lists the latest posts from related publications, plus blurbs, in the sidebar.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:publication_type AND type:text_blurb'),
			'parent' => 'publication_sidebar',
		),
		'sidebar_blurb_with_related_publication' => array(
			'fields' => array('title' => 'Blurbs in Sidebar w/ Publication Feed','description'=>'Shows blurbs in sidebar, with the latest posts from related publications below.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:publication_type AND type:text_blurb'),
			'parent' => 'publication_and_blurbs_sidebar',
		),
		'show_children_and_publication_sidebar' => array(
			'fields' => array('title' => 'Publication Feed in Sidebar, with Children','description'=>'Lists the latest posts from related publications in the sidebar, and children pages below the main content.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:publication_type'),
			'parent' => 'publication_sidebar',
		),
		'no_title' => array(
			'fields' => array('title' => 'Hide Page Title','description'=>'Hides the page title.','default_availability'=>'hidden'),
			'parent' => NULL,
		),
		'no_nav' => array(
			'fields' => array('title' => 'Hide Site Navigation','description'=>'Hides the site navigation.','default_availability'=>'hidden'),
			'parent' => NULL,
		),
		'noNavNoSearch' => array(
			'fields' => array('title' => 'Hide Site Navigation &amp; Search','description'=>'Hides the site navigation and search box.','default_availability'=>'hidden'),
			'parent' => 'no_nav',
		),
		'no_sub_nav' => array(
			'fields' => array('title' => 'Hide Blurbs','description'=>'Hides page blurbs.','default_availability'=>'hidden'),
			'parent' => 'no_nav',
		),
		'image_left_no_nav' => array(
			'fields' => array('title' => 'Images Replace Navigation','description'=>'Displays images in the site navigation area.','default_availability'=>'hidden'),
			'parent' => 'default',
		),
		'sidebar_images_alpha_by_name' => array(
			'fields' => array('title' => 'Image Sidebar (Alphabetical)','description'=>'Sorts sidebar images alphabetically','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:image'),
			'parent' => 'default',
		),
		'sidebar_images_alpha_by_keywords' => array(
			'fields' => array('title' => 'Image Sidebar (Alphabetical by Keywords)','description'=>'Sorts sidebar images alphabetically, using the Keywords field','default_availability'=>'hidden'),
			'parent' => 'sidebar_images_alpha_by_name',
		),
		'sidebar_images_chronological' => array(
			'fields' => array('title' => 'Image Sidebar (Chronological)','description'=>'Sorts sidebar images chronologically, from oldest to newest','default_availability'=>'hidden'),
			'parent' => 'default',
		),
		'sidebar_images_reverse_chronological' => array(
			'fields' => array('title' => 'Image Sidebar (Reverse Chronological)','description'=>'Sorts sidebar images reverse chronologically, from newest to oldest','default_availability'=>'hidden'),
			'parent' => 'default',
		),
		'poll' => array(
			'fields' => array('title' => 'Poll','description'=>'Uses form to produce a poll, with results chart.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:form'),
			'parent' => NULL,
		),
		'poll_sidebar' => array(
			'fields' => array('title' => 'Poll in Sidebar','description'=>'Uses form to produce a poll in the sidebar, with results chart.','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:form'),
			'parent' => 'poll',
		),
		'site_map' => array(
			'fields' => array('title' => 'Site Map','description'=>'Lists live sites, grouped by site type.','default_availability'=>'hidden'),
			'parent' => NULL,
		),
		'standalone_login_page_stripped' => array(
			'fields' => array('title' => 'Login Page','description'=>'A simple login page.','default_availability'=>'hidden'),
			'parent' => NULL,
		),
		'standalone_login_page' => array(
			'fields' => array('title' => 'Login Page with Chrome','description'=>'A login page with typical Reason page chrome (navigation, title, etc.)','default_availability'=>'hidden'),
			'parent' => 'standalone_login_page_stripped',
		),
        'classified' => array(
			'fields' => array('title' => 'Classifieds','description'=>'Classifieds module (a la Craigslist)','default_availability'=>'hidden'),
			'parent' => NULL,
        ),
        'quote' => array(
			'fields' => array('title' => 'Quotes','description'=>'Shows quotes associated with this page','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:quote_type'),
			'parent' => NULL,
        ),
        'quote_sidebar_random' => array(
			'fields' => array('title' => 'Quote in Sidebar (Random)','description'=>'Shows one random quote at a time in the sidebar','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:quote_type'),
			'parent' => 'quote',
        ),
        'quote_sidebar_random_no_page_title' => array(
			'fields' => array('title' => 'Quote in Sidebar (Random, Page Title Hidden)','description'=>'Shows one random quote at a time in the sidebar, with page title hidden','default_availability'=>'hidden'),
			'parent' => 'quote_sidebar_random',
        ),
        'quote_by_category' => array(
			'fields' => array('title' => 'Quotes By Category','description'=>'Shows quotes associated with this page\'s categories','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:quote_type'),
			'parent' => 'quote',
        ),
        'a_to_z' => array(
			'fields' => array('title' => 'A-to-Z Site List By Keyword','description'=>'Shows a list of live sites, organized by keyword','default_availability'=>'hidden'),
			'parent' => NULL,
        ),
        'related_policies' => array(
			'fields' => array('title' => 'Policies','description'=>'Displays policies attached to the current page','default_availability'=>'hidden'),
			'maximal_fields' => array('availability_logic'=>'type:policy_type'),
			'parent' => 'policy',
        ),
        'related_policies_and_children' => array(
			'fields' => array('title' => 'Policies on Page (Children in Sidebar)','description'=>'Displays policies attached to the current page, and children pages in the sidebar','default_availability'=>'hidden'),
			'parent' => 'related_policies',
        ),
        'related_policies_and_siblings' => array(
			'fields' => array('title' => 'Policies on Page (Siblings in Sidebar)','description'=>'Displays policies attached to the current page, and sibling pages in the sidebar','default_availability'=>'hidden'),
			'parent' => 'related_policies',
        ),
        'all_related_policies' => array(
			'fields' => array('title' => 'Policies on Page (Expanded)','description'=>'Displays policies attached to the current page in a single long, expanded view','default_availability'=>'hidden'),
			'parent' => 'related_policies',
        ),
        'policy' => array(
			'fields' => array('title' => 'All Policies on Site','description'=>'Displays all policies on the current site','default_availability'=>'hidden'),
			'maximal_fields' => array('visibility_logic'=>'type:policy_type'),
			'parent' => 'related_policies',
        ),
        'basic_tabs' => array(
			'fields' => array('title' => 'Sibling Tabs','description'=>'Displays sibling pages as tabs across the top of the page.','default_availability'=>'hidden'),
			'maximal_fields' => array('default_availability'=>'visible'),
			'parent' => 'show_siblings',
        ),
        'basic_tabs_parent' => array(
			'fields' => array('title' => 'Sibling Tabs (Parent Page)','description'=>'Redirects to first child page to complete illusion of tabbed interface. A page given this page type will not itself be visible.','default_availability'=>'hidden'),
			'parent' => 'basic_tabs',
        ),
        'editor_demo' => array(
			'fields' => array('title' => 'WYSIWYG Editor Demo','description'=>'Presents a demo of the WYSIWYG editor assigned to the current site.','default_availability'=>'hidden'),
			'parent' => NULL,
        ),
        'user_settings' => array(
			'fields' => array('title' => 'User Settings','description'=>'Provides an interface that allows users to change their password (if Reason-managed) and other settings.','default_availability'=>'hidden'),
			'parent' => NULL,
        ),
		'projects' => array(
			'fields' => array('title' => 'Projects','description'=>'Lists projects from the current site.','default_availability'=>'hidden'),
			'parent' => NULL,
		),
		'tasks' => array(
			'fields' => array('title' => 'Tasks','description'=>'Lists tasks from the current site.','default_availability'=>'hidden'),
			'parent' => NULL,
		),
	);
	
	protected $hierarchy;
		
	public function user_id( $user_id = NULL)
	{
		if(!empty($user_id))
			return $this->_user_id = $user_id;
		else
			return $this->_user_id;
	}
	/**
	 * Get the title of the upgrader
	 * @return string
	 */
	public function title()
	{
		return 'Set Up Page Types in Database';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * @return string HTML description
	 */
	public function description()
	{
		$str = '<p>This upgrade creates a new type, "page_type_type", which is the new method of managing page types in Reason.</p>';
		return $str;
	}
	/**
	 * Init the upgrader
	 * @return string HTML report
	 */
	public function init($disco, $head_items)
	{
		if($this->upgrade_complete())
		{
			$disco->show_form = false;
			$disco->add_callback(array($this,'no_show_form_disco'), 'no_show_form');
		}
		else
		{
			$disco->add_element('availability_level', 'radio', array('options'=>array('minimal'=>'Minimal','maximal'=>'Maximal')));
			$disco->add_required('availability_level');
			$disco->set_actions(array('test' => 'Test Upgrade','run' => 'Run Upgrade'));
			$disco->add_callback(array($this,'pre_show_form_disco'), 'pre_show_form');
			$disco->add_callback(array($this,'process_disco'), 'process');
		}
	}
	public function pre_show_form_disco($disco)
	{
		$str = '';
		$str .= '<h3>'.$this->title().'</h3>';
		$str .= $this->description();
		$str .= '<p>This upgrade changes the way Reason handles page types in two major ways:</p>';
		$str .= '<ol><li><strong>It moves page types into the database.</strong> This will allow non-programmers to create and modify page type definitions, descriptions, and availability rules. The php file minisite_templates/page_types.php will no longer be used once this upgrade script is run; instead, Reason administrators will manage page types in the Master Admin.</li>';
		$str .= '<li><strong>It introduces a new tool for selecting page types.</strong> This tool allows pages types to be presented in a hierarchy and for page types to be visible but not available for selection by site editors. Together, these enhancements allow you to provide broader access to (and a more comprehensive sense of) the functionality available within Reason.</li></ol>';
		$str .= '<p>You can perform the initial set up of page types using one of these two "availability levels". These are not final states, but starting points for your instance\'s custom page types implementation. Review the options, and select the approach that you feel is closest to fitting your situation.</p>';
		$str .= '<ul><li><strong>Maximal:</strong> Takes advantage of the improved interface for page type selection to provide a rich array of page types for everyday site editors. Many useful page types that were previously unavailable are now either available or (at least) visible to site editors. Despite this expanded set of available page types, the hierarchical view helps keep the interface streamlined, and sensible defaults show only those page types that can actually be used given the types available on the site. Note that selecting this option will likely expand the set of page types available to your editors.</li>';
		$str .= '<li><strong>Minimal:</strong> This more conservative option implements availability rules equivalent to classic defaults: a small handful of commonly-used page types are available to site editors; the vast majority of page types are only visible to and selectable by Reason administrators.</li></ul>';
		$str .= '<p>A note on potential for increased user powers: With an increased palette of page types available, there may be many more pages that site editors can modify. Because it will be easier to make page types available to your editors, you might want to consider using locks as the most reliable way to implement restrictions.</p>';
		$str .= '<p>Note that any custom page types you have created will be migrated into the database, but will not be placed into the page type hierarchy and will be hidden by deault. You will likely want to review those page types to ensure that they appear in the page type picker in the way you prefer. It is recommended that you run this on a test instance of Reason, familiarize yourself with the naure of the changes, and develop a plan for any changes you want to make post-upgrade.</p>';
		return $str;
	}
	public function no_show_form_disco($disco)
	{
		$str = '';
		$str .= '<h3>'.$this->title().'</h3>';
		$str .= '<p>This upgrade has already been run!</p>';
		return $str;
	}
	public function process_disco($disco)
	{
		if('run' == $disco->get_chosen_action())
		{
			$disco->show_form = false;
			echo $this->run($disco->get_value('availability_level'));
		}
		else
		{
			echo $this->test($disco->get_value('availability_level'));
		}
	}
	/**
	 * Do a test run of the upgrader
	 * @return string HTML report
	 */
	public function test($availability_level)
	{
		$replacements = $this->get_pages_with_page_types_to_change();
		if($this->page_type_type_exists() && $this->page_type_view_types_set_up() && $this->page_type_view_exists() && $this->page_types_exist() && $this->page_type_hierarchy_set_up() && empty($replacements))
		{
			return '<p>Page types are set up. This script has already run.</p>';
		}
		else
		{
			$str = '';
			//$str .= $this->pre_import_report();
			if (!$this->page_type_type_exists())
				$str .= '<p>Would create page_type_type.</p>';
			else
				$str .= '<p>page_type_type already created.</p>';
			if(!$this->page_type_view_types_set_up())
				$str .= '<p>Would add view types</p>';
			else
				$str .= '<p>View types already created.</p>';
			if(!$this->page_type_view_exists())
				$str .= '<p>Would add views for page_type_type</p>';
			else
				$str .= '<p>View for page_type_type already created.</p>';
			if(!empty($replacements))
			{
				$str .= '<p>Would change the page types for pages with the following page types:</p>';
				$reps = array();
				foreach($replacements as $page)
				{
					if(!isset($reps[$page->get_value('custom_page')]))
						$reps[$page->get_value('custom_page')] = 0;
					$reps[$page->get_value('custom_page')]++;
				}
				$str .= '<ul>';
				foreach($reps as $pt => $count)
				{
					$str .= '<li>'.$pt.': '.$count.' pages</li>';
				}
				$str .= '</ul>';
			}
			else
			{
				$str .= '<p>No pages need their page types changed.</p>';
			}
			if (!$this->page_types_exist())
				$str .= '<p>Would import all existing page types from current page type definitions.</p>';
			else
				$str .= '<p>Page types already imported.</p>';
			if (!$this->page_type_hierarchy_set_up())
				$str .= '<p>Would set up the default hierarchy for page types</p>';
			else
				$str .= '<p>Default hierarchy already set up</p>';
			return $str;
		}
	}
	
	protected function pre_import_report()
	{
		$str = '';
		$missing_count = 0;
			$no_parent_count = 0;
			$parent_count = 0;
			foreach($GLOBALS['_reason_page_types'] as $pt_name => $pt_stuff)
			{
				if(!isset($this->initial_setup[$pt_name]))
				{
					if(isset($this->replacements[$pt_name]))
					{
						$str .= '<p style="color:#009;">Replaced: '.$pt_name.' with '.$this->replacements[$pt_name].'</p>';
					}
					else
					{
						$str .= '<p style="color:#900;">Not in initial setup array: '.$pt_name.'</p>';
						$missing_count++;
					}
				}
				elseif(empty($this->initial_setup[$pt_name]['parent']))
				{
					$str .= '<p style="color:#090;">No parent: '.$pt_name.'</p>';
					$no_parent_count++;
				}
				else
				{
					$str .= '<p>Has parent: '.$pt_name.'</p>';
					$parent_count++;
				}
			}
			$str .= '<p>Missing: '.$missing_count.'</p>';
			$str .= '<p>No parent: '.$no_parent_count.'</p>';
			$str .= '<p>Parent: '.$parent_count.'</p>';
			$percentage = round($missing_count / ( $no_parent_count + $parent_count ) * 100);
			$str .= '<p>'.$percentage.'% missing</p>';
			return $str;
	}
	
	function upgrade_complete()
	{
		$replacements = $this->get_pages_with_page_types_to_change();
		if($this->page_type_type_exists() && $this->page_type_view_types_set_up() && $this->page_type_view_exists() && $this->page_types_exist() && $this->page_type_hierarchy_set_up() && empty($replacements))
			return true;
		return false;
	}
	
    /**
     * Run the upgrader
     * @return string HTML report
     */
	public function run($availability_level)
	{
		$replacements = $this->get_pages_with_page_types_to_change();
		if($this->page_type_type_exists() && $this->page_type_view_types_set_up() && $this->page_type_view_exists() && $this->page_types_exist() && $this->page_type_hierarchy_set_up() && empty($replacements))
		{
			$str = '<p>Page types are set up. This script has already run.</p>';
		}
		else
		{
			$str = '';
			//echo 'step1 ';
			if (!$this->page_type_type_exists())
				$str .= $this->create_page_type_type();
			else
				$str .= '<p>Page type type already created.</p>';
			//echo 'step2 ';
			if(!$this->page_type_view_types_set_up())
				$str .= $this->create_view_types();
			else
				$str .= '<p>View types already created.</p>';
			if(!$this->page_type_view_exists())
				$str .= $this->create_page_type_views();
			else
				$str .= '<p>Views for page_type_type already created.</p>';
			//echo 'step3 ';
			if(!empty($replacements))
				$str .= $this->change_page_types();
			else
				$str .= '<p>All page types are clean.</p>';
			if (!$this->page_types_exist())
				$str .= $this->import_page_types($availability_level);
			else
				$str .= '<p>Page types already imported.</p>';
			//echo 'step4 ';
			if(!$this->page_type_hierarchy_set_up())
				$str .= $this->set_up_page_type_hierarchy();
			else
				$str .= '<p>Page type hierarcy already set up</p>';
			//echo 'step5 ';
		}
		return $str;
	}
	
	protected function get_page_type_type_id()
	{
		if(!isset($this->type_id))
		{
			$this->type_id = id_of('page_type_type');
		}
		return $this->type_id;
	}
	
	/// FUNCTIONS THAT DO THE CREATION WORK
	protected function create_page_type_type()
	{
		$str = '';
		
		$this->type_id = reason_create_entity(id_of('master_admin'), id_of('type'), $this->user_id(), $this->details['name'], $this->details);
		if($this->type_id)
		{
			$str .= '<p>Created page type type</p>';
		}
		else
		{
			$str .= '<p>Page type type not created. Please consult error logs to troubleshoot.</p>';
			return $str;
		}
		
		if(create_default_rels_for_new_type($this->get_page_type_type_id()))
		{
			$str .= '<p>Added the default relationships for the page type type.</p>';
		}
		else
		{
			$str .= '<p>Default relationships not created properly for the page type type. Please consult error logs to troubleshoot.</p>';
			return $str;
		}
		
		foreach($this->fields as $table => $fields)
		{	
			if(create_reason_table($table, $this->details['unique_name'], $this->user_id()))
			{
				$str .= '<p>Added entity table '.$table.'.</p>';
			}
			else
			{
				$str .= '<p>New table '.$table.' not created. Aborting; please consult error logs to troubleshoot.</p>';
				return $str;
			}
			$ftet = new FieldToEntityTable($table, $fields);
			$ftet->update_entity_table();
			ob_start();
			$ftet->report();
			$str .= ob_get_contents();
			ob_end_clean();
		}
		$es = new entity_selector();
		$es->add_type(id_of('content_table'));
		$es->add_relation('`name` = "sortable"');
		$result_tables = $es->run_one();
		foreach($result_tables as $table)
		{
			if(create_relationship( $this->get_page_type_type_id(), $table->id(), relationship_id_of('type_to_table') ))
			{
				$str .= '<p>Attached the '.$table->get_value('name').' table to the type.</p>';
			}
			else
			{
				$str .= '<p>Failure to attach the table '.$table->get_value('name').' to the page type type. Aborting.</p>';
				return $str;
			}
		}
		foreach($this->relationships as $name => $rel)
		{
			if(create_allowable_relationship($this->get_page_type_type_id(),id_of($rel['type']),$name, $rel['values']))
			{
				$str .= '<p>Created '.htmlspecialchars($name).' relationship.</p>';
			}
			else
			{
				$str .= '<p>Failed to create the relationship '.$name.'. Aborting.</p>';
				return $str;
			}
		}
		if(create_relationship( id_of('master_admin'), $this->get_page_type_type_id(), relationship_id_of('site_to_type') ))
		{
			$str .= '<p>Assigned the page type type to the master admin site.</p>';
		}
		else
		{
			$str .= '<p>Failed to assign the page type type to the master admin site. You should be able to take care of this yourself -- edit the master admin site and add Page Type as one of its available types.</p>';
			return $str;
		}
		
		$fixer = new AmputeeFixer();
		$fixer->fix_amputees($this->get_page_type_type_id()); // not sure why this is needed, but it seems to be...
		
		return $str;
	}
	
	protected function create_view_types()
	{
		$str = '';
		
		if($this->page_type_view_types_set_up())
			return '<p>Page type view types already exist.</p>';
			
		foreach($this->view_types as $filename => $name)
		{
			$es = new entity_selector();
			$es->add_type(id_of('view_type'));
			$es->add_relation('`url` = "'.addslashes($filename).'"');
			$es->set_num(1);
			$views = $es->run_one();
			if(empty($views))
			{
				if(reason_create_entity(id_of('master_admin'), id_of('view_type'), $this->user_id(), $name, array('url' => $filename) ) )
					$str .= '<p>Created view type for '.htmlspecialchars($filename).'</p>';
				else
					$str .= '<p>Failed creating view type for '.htmlspecialchars($filename).'</p>';
			}
			else
			{
				$str .= '<p>View type '.htmlspecialchars($filename).' already exists; not created.</p>';
			}
		}
		
		return $str;
	}
	
	protected function create_page_type_views()
	{
		$str = '';
		if($this->page_type_view_exists())
			return '<p>Page type view already exists.</p>';
		foreach($this->views as $name => $info)
		{
			if(empty($info['type']))
			{
				trigger_error('Each view needs a type. '.$name.' does not; it will not be created.');
				continue;
			}
			$view_type = $this->get_view_type($info['type']);
			if(empty($view_type))
			{
				$str .= '<p>View type for '.$name.' not found. View will not be created.</p>';
				continue;
			}
			$view_id = reason_create_entity(id_of('master_admin'), id_of('view'), $this->user_id(), $name, $info['fields']);
			create_relationship( $view_id, $view_type->id(), relationship_id_of('view_to_view_type') );
			create_relationship( $view_id, $this->get_page_type_type_id(), relationship_id_of('view_to_type') );
			create_relationship( $this->get_page_type_type_id(), $view_id, relationship_id_of('type_to_default_view') );
			
			$fields = get_fields_by_type( $this->get_page_type_type_id(), true );
			if(!empty($info['searchable_fields']))
			{
				foreach($info['searchable_fields'] as $field)
				{
					if($field_entity = $this->get_field_entity($this->get_page_type_type_id(), $field))
					{
						create_relationship( $view_id, $field_entity->id(), relationship_id_of('view_searchable_fields') );
					}
					else
					{
						trigger_error('Unable to find field '.$field.'. This field will not be searchable in the administrative interface.');
					}
				}
			}
			if(!empty($info['columns']))
			{
				foreach($info['columns'] as $field)
				{
					if($field_entity = $this->get_field_entity($this->get_page_type_type_id(), $field))
					{
						create_relationship( $view_id, $field_entity->id(), relationship_id_of('view_columns') );
					}
					else
					{
						trigger_error('Unable to find field '.$field.'. This field will not be part of the list in the administrative interface.');
					}
				}
			}
			$str .= '<p>View type '.$name.' created.</p>';
		}
		return $str;
	}
	
	protected function get_view_type($file)
	{
		$es = new entity_selector();
		$es->add_type(id_of('view_type'));
		$es->add_relation('`url` = "'.addslashes($file).'"');
		$es->set_num(1);
		$tree_views = $es->run_one();
		if(!empty($tree_views))
			return current($tree_views);
		return NULL;
	}
	
	/// FUNCTIONS THAT CHECK IF WE HAVE WORK TO DO
	protected function page_type_type_exists()
	{
		reason_refresh_unique_names();  // force refresh from the database just in case.
		return reason_unique_name_exists('page_type_type');
	}
	protected function page_type_view_types_set_up()
	{
		if(!$this->page_type_type_exists())
			return false;
		if(!isset($this->page_type_view_types_set_up))
		{
			$es = new entity_selector();
			$es->add_type(id_of('view_type'));
			$parts = array();
			foreach($this->view_types as $vt_file => $vt_name)
			{
				$parts[] = '"'.addslashes($vt_file).'"';
			}
			$es->add_relation('`url` IN ('.implode(',',$parts).')');
			$es->set_num(count($this->view_types));
			$view_types = $es->run_one();
			$this->page_type_view_types_set_up = (count($view_types) >= count($this->view_types));
		}
		return $this->page_type_view_types_set_up;
	}
	protected function page_type_view_exists()
	{
		if(!$this->page_type_type_exists())
			return false;
		if(!isset($this->page_type_view_exists))
		{
			$es = new entity_selector();
			$es->add_type(id_of('view'));
			$es->add_left_relationship($this->get_page_type_type_id(),relationship_id_of('view_to_type'));
			$es->set_num(1);
			$views = $es->run_one();
			$this->page_type_view_exists = !empty($views);
		}
		return $this->page_type_view_exists;
	}
	protected function page_types_exist()
	{
		if(!$this->page_type_type_exists())
			return false;
		$es = new entity_selector();
		$es->add_type($this->get_page_type_type_id());
		$es->set_num(1);
		$page_types = $es->run_one();
		return !empty($page_types);
	}
	protected function page_type_hierarchy_set_up()
	{
		if(!isset($this->page_type_hierarchy_set_up))
		{
			$this->page_type_hierarchy_set_up = false;
			if($this->page_types_exist())
			{
				$page_types = $this->get_page_types_without_parents();
				if(empty($page_types))
					$this->page_type_hierarchy_set_up = true;
			}
		}
		return $this->page_type_hierarchy_set_up;
	}
	protected function get_page_types_without_parents()
	{
		if(!isset($this->page_types_without_parents))
		{
			$page_types = $this->get_all_page_type_entities();
			$no_parents = array();
			$rel_id = relationship_id_of('page_type_parent');
			foreach($page_types as $pt_id => $pt)
			{
				$parents = $pt->get_left_relationship($rel_id);
				if(empty($parents))
					$no_parents[$pt_id] = $pt;
			}
			$this->page_types_without_parents = $no_parents;
		}
		return $this->page_types_without_parents;
	}
	protected function get_all_page_type_entities()
	{
		if(!isset($this->all_page_type_entities))
		{
			$es = new entity_selector();
			$es->add_type($this->get_page_type_type_id());
			$this->all_page_type_entities = $es->run_one();
		}
		return $this->all_page_type_entities;
	}
	
	protected function get_all_page_type_entities_by_name()
	{
		$page_types = $this->get_all_page_type_entities();
		$ret = array();
		foreach($page_types as $pt)
		{
			$ret[$pt->get_value('name')] = $pt;
		}
		return $ret;
	}
	protected function import_page_types($availability_level)
	{
		$str = '';
		if(!empty($GLOBALS['_reason_page_types']))
		{
			
			$set_up = array();
			foreach($GLOBALS['_reason_page_types'] as $name => $page_type)
			{
				if(isset($this->replacements[$name]))
					continue;
				if($this->set_up_page_type($name, $page_type, $availability_level))
					$set_up[] = $name;
			}
			$num_set_up = count($set_up);
			$num_total = count($GLOBALS['_reason_page_types']);
			
			$str .= '<p>Added '.$num_set_up.' of '.$num_total.' page types.</p>';
			$str .= spray($set_up);
			if($num_set_up < $num_total)
			{
				$str .= '<p>Not set up: '.implode(', ',array_diff(array_keys($GLOBALS['_reason_page_types']), $set_up)).'</p>';
			}
		}
		else
		{
			$str .= '<p>$GLOBALS[\'_reason_page_types\'] not found. No page types added. NOTE: This means that your Reason database is in a bad state. You should restore it from a backup.</p>';
		}
		return $str;
	}
	protected function set_up_page_type($name, $page_type, $availability_level)
	{
		static $order = 0;
		//echo 'setting up '.$name.' ';
		$values = $this->get_additional_fields($name, $availability_level);
		//echo '.1';
		$values['sort_order'] = $order;
		if(isset($page_type['_meta']))
		{
			$meta = $page_type['_meta'];
			if(isset($meta['note']))
			{
				$values['note'] = $meta['note'];
				unset($meta['note']);
			}
			unset($page_type['_meta']);
		}
		//echo '.2';
		foreach($page_type as $location => $module)
		{
			if(!is_array($module))
				$page_type[$location] = array('module' => $module);
		}
		//echo '.3';
		$values['page_locations'] = json_encode($page_type);
		if(!empty($meta))
			$values['meta'] = json_encode($meta);
		$id = reason_create_entity(id_of('master_admin'), $this->get_page_type_type_id(), $this->user_id(), $name, $values);
		//echo '.4';
		if($id)
		{
			//echo '.5';
			$order++;
			return true;
		}
		return false;
	}
	protected function set_up_page_type_hierarchy()
	{
		$str = '';
		//$str .= $this->report_hierarchy($this->get_hierarchy());
		$str .= '<p>Starting to set up page type hierarchy</p>';
		//echo '.1 ';
		$page_types = $this->get_page_types_without_parents();
		//echo '.2 ';
		$pts_by_name = $this->get_all_page_type_entities_by_name();
		//echo '.3 ';
		$parent_rel_id = relationship_id_of('page_type_parent');
		//echo '.4 ';
		foreach($page_types as $pt_id => $pt)
		{
			//echo $pt->get_value('name').' ';
			$parent_id = false;
			if($parent_name = $this->get_default_page_type_parent($pt->get_value('name')))
			{
				if(isset($pts_by_name[$parent_name]))
				{
					//$str .= '<p>Would make '.$parent_name.' the parent of '.$pt->get_value('name').'.</p>';
					$parent_id = $pts_by_name[$parent_name]->id();
				}
				else
				{
					//$str .= '<p>Parent ('.$parent_name.') not found for '.$pt->get_value('name').'. This page type will not have a parent.</p>';
					$parent_id = $pt_id;
				}
			}
			else // top-level
			{
				//$str .= '<p>Would make '.$pt->get_value('name').' a top-level page type (e.g. its own parent).</p>';
				$parent_id = $pt_id;
			}
			// make parent relationship here
			create_relationship( $pt_id, $parent_id, $parent_rel_id );
			//$str .= '<p>Created parent relationship: '.$pt_id.' ('.$pt->get_value('name').') and '.$parent_id.'</p>';
		}
		//echo '.5 ';
		$str .= '<p>Done setting up page type hierarchy</p>';
		return $str;
	}
	protected function report_hierarchy($hierarchy)
	{
		$ret = '<ul>';
		foreach($hierarchy as $name => $children)
		{
			$ret .= '<li>';
			$ret .= $this->get_page_type_info_markup($name);
			if(!empty($children))
				$ret .= $this->report_hierarchy($children);
			$ret .= '</li>';
		}
		$ret .= '</ul>';
		return $ret;
	}
	protected function get_page_type_info_markup($name)
	{
		$ret = '';
		$ret .= '<strong>'.$name.'</strong> ';
		if(!empty($this->initial_setup[$name]['fields']))
		{
			$array = array();
			foreach($this->initial_setup[$name]['fields'] as $k => $v)
			{
				$array[] = $k.': "'.$v.'"';
			}
			$ret .= implode('; ', $array);
		}
		if(!empty($this->initial_setup[$name]['type']))
		{
			$ret .= '; type: "'.$this->initial_setup[$name]['type'].'"';
		}
		return $ret;
	}
	protected function get_hierarchy()
	{
		if(!isset($this->hierarchy))
		{
			foreach(array_keys($this->initial_setup) as $name)
			{
				$parent = $this->get_default_page_type_parent($name);
				if(empty($parent))
				{
					$this->hierarchy[$name] = $this->recurse_children($name);
				}
			}
		}
		return $this->hierarchy;
	}
	protected function recurse_children($name)
	{
		$ret = array();
		$children = $this->get_default_page_type_children($name);
		if(!empty($children))
		{
			foreach($children as $child_name)
			{
				$ret[$child_name] = $this->recurse_children($child_name);
			}
		}
		return $ret;
	}
	protected function get_additional_fields($name, $availability_level)
	{
		if(isset($this->initial_setup[$name]['fields']))
		{
			$fields = $this->initial_setup[$name]['fields'];
			if('maximal' == $availability_level && isset($this->initial_setup[$name]['maximal_fields']))
			{
				$fields = array_merge($fields, $this->initial_setup[$name]['maximal_fields']);
			}
			return $fields;
		}
		return array();
	}
	protected function get_default_page_type_parent($name)
	{
		if(isset($this->initial_setup[$name]['parent']))
			return $this->initial_setup[$name]['parent'];
		return '';
	}
	protected function get_default_page_type_children($name)
	{
		$index = $this->get_children_index();
		if(isset($index[$name]))
			return $index[$name];
		return array();
	}
	protected function get_children_index()
	{
		if(!isset($this->children_index))
		{
			$ret = array();
			foreach($this->initial_setup as $item_name => $info)
			{
				if(!empty($info['parent']))
				{
					if(!isset($ret[$info['parent']]))
						$ret[$info['parent']] = array();
					$ret[$info['parent']][] = $item_name;
				}
			}
			$this->children_index = $ret;
		}
		return $this->children_index;
	}
	function get_field_entity($type_id, $field_name)
	{
		static $tables = array();
		if(!isset($tables[$type_id]))
		{
			$es = new entity_selector();
			$es->add_type(id_of('content_table'));
			$es->add_right_relationship($type_id, relationship_id_of('type_to_table'));
			$tables[$type_id] = $es->run_one();
		}
		static $fields = array();
		if(!isset($fields[$type_id]))
		{
			$fields[$type_id] = array();
			foreach($tables[$type_id] as $table)
			{
				$es = new entity_selector();
				$es->add_type(id_of('field'));
				$es->add_left_relationship($table->id(), relationship_id_of('field_to_entity_table'));
				$fields[$type_id] += $es->run_one();
			}
		}
		foreach($fields[$type_id] as $field)
		{
			if($field->get_value('name') == $field_name)
				return $field;
		}
		return NULL;
	}
	
	function get_pages_with_page_types_to_change()
	{
		if(empty($this->replacements))
			return array();
		$es = new entity_selector();
		$es->add_type(id_of('minisite_page'));
		$es->add_relation('`custom_page` IN ("'.implode('","',array_keys($this->replacements)).'")');
		return $es->run_one();
	}
	
	function change_page_types()
	{
		$str = '';
		$pages = $this->get_pages_with_page_types_to_change();
		$str .= '<p>Changing page types...</p>';
		$str .= '<ul>';
		foreach($pages as $page)
		{
			if(isset($this->replacements[$page->get_value('custom_page')]))
			{
				if(reason_update_entity($page->id(), $this->user_id(), array('custom_page'=>$this->replacements[$page->get_value('custom_page')])))
				{
					$str .= '<li>Changed page id '.$page->id().' from '.$page->get_value('custom_page').' to '.$this->replacements[$page->get_value('custom_page')].'.</li>';
				}
			}
			else
			{
				trigger_error('Unable to find a new page type for page id '.$page->id());
			}
		}
		$str .= '</ul>';
		return $str;
	}
}