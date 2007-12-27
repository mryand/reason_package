<?php
reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'classes/quote_helper.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'QuoteModule';
	
/**
 * The quote module displays a single random quote, and provides an option to refresh the quote
 *
 * If changing class names or the HTML generation structure, make sure to modify the html generation
 * portions of quote_retrieve.js so that dynamically created quotes maintain the same structure.
 *
 * @package reason
 * @subpackage minisite_modules
 * 
 * @author Nathan White
 */
	class QuoteModule extends DefaultMinisiteModule
	{
		var $quotes;
		var $acceptable_params = array ('page_category_mode' => false,
										'cache_lifespan' => 0,
										'num_to_display' => NULL,
										'enable_javascript_refresh' => false,
										'prefer_short_quotes' => false,
										'rand_flag' => false);
		
		function init( $args = array() )
		{	
			$qh = new QuoteHelper($this->site_id, $this->page_id);
			if ($this->params['cache_lifespan'] > 0) $qh->set_cache_lifespan($this->params['cache_lifespan']);
			if ($this->params['page_category_mode']) $qh->set_page_category_mode($this->params['page_category_mode']);
			$qh->init();
			$this->quotes =& $qh->get_quotes($this->params['num_to_display'], $this->params['rand_flag']);
			$this->init_head_items();
		}
		
		function init_head_items()
		{
			if ($this->quotes && $this->params['enable_javascript_refresh'])
			{
				$quote = current($this->quotes);
				$quote_id = $quote->id();
				$page_cat_mode = ($this->params['page_category_mode']) ? 1 : 0;
				$prefer_short_quotes = ($this->params['prefer_short_quotes']) ? 1 : 0;
				
				$cache_lifespan = ($this->params['cache_lifespan'] > 0) ? $this->params['cache_lifespan'] : 0;
				
				// all these parameters are sent in integer format so the javascript can accept only numeric params for security
				$qry_string = '?site_id='.$this->site_id.
							  '&page_id='.$this->page_id.
							  '&quote_id='.$quote_id.
							  '&page_category_mode='.$page_cat_mode.
							  '&cache_lifespan='.$cache_lifespan.
							  '&prefer_short_quotes='.$prefer_short_quotes;
				
				$head_items =& $this->parent->head_items;
				$head_items->add_javascript(REASON_HTTP_BASE_PATH . 'js/jquery/jquery-1.2.1.min.js');
				$head_items->add_javascript(REASON_HTTP_BASE_PATH . 'js/quote/quote_retrieve.js'.$qry_string); // pass params in qry string
			}
		}
		
		function has_content()
		{
			if( !empty($this->quotes) )
				return true;
			else
				return false;
		}
		
		function run()
		{
			echo '<div id="quotes">'."\n";
			foreach ($this->quotes as $quote)
			{
				echo '<div class="quote">'."\n";
				echo $this->get_quote_content_html($quote) . "\n";
				echo $this->get_quote_author_html($quote) . "\n";
				echo '</div>'."\n";
			}
			echo '</div>'."\n";
		}
		
		function get_quote_content_html(&$quote)
		{
			$short_description = ($this->params['prefer_short_quotes']) ? $quote->get_value('description') : '';
			$quote_text = ($short_description) ? $short_description : $quote->get_value('content');
			$quote_html = '<p class="quoteText">';
			$quote_html .= $quote_text;
			$quote_html .= '</p>';
			return $quote_html;
		}
		
		function get_quote_author_html(&$quote)
		{
			$author = $quote->get_value('author');
			if ($author)
			{
				$author_html = '<p class"quoteAuthor">';
				$author_html .= $author;
				$author_html .= '</p>';
				return $author_html;
			}
			return '';
		}
		
		/**
		 * This method will clear the quotation cache generated by this module for the site and page
		 * @todo implement something to call this
		 */
		function clear_cache($site_id, $page_id)
		{
			$qh = new QuoteHelper($this->site_id, $this->page_id);
			$qh->clear_cache();
		}
	}
?>
