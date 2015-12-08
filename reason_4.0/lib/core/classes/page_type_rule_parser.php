<?php
/**
 * @package reason
 * @subpackage classes
 */

/**
 * Include dependencies
 */
reason_include_once('classes/page_type_availability.php');

/**
 * Parser class for page type rule logic
 *
 * Given a logic string, this class will attempt to parse it into an array that can then have logical operations performed on it
 *
 * Usage:
 *
 * <pre>
 * $parser = new pageTypeRuleParser();
 * $parsed = $parser->parse('site:login_site OR site:some_other_site');
 * if(is_array($parsed))
 *  do_logic($parsed);
 * else
 * 	trigger_error('Unable to parse page type rule!');
 * </pre>
 *
 * @author Mark Heiman
 */
class pageTypeRuleParser
{
	private $blocks = array();
	private $errors = array();
	
	public function parse($rule)
	{
		$this->errors = array();
		
		// We can skip trying to parse if there are unmatched parens
		$open = substr_count($rule, '(');
		$close = substr_count($rule, ')');
		if ($open != $close)
		{
			$this->errors[] = 'Unmatched parentheses in expression.';
			return false;
		}
		
		// Reset the blocks array and populate it from the pre_parser
		$this->blocks = array();
		$this->pre_parser($rule);
		if (count($this->errors)) return false;
		
		// There should be a value in blocks for every set of parens, so we 
		// can do a quick check for uncaught trouble at this stage.
		if (count($this->blocks) < $open)
		{
			$this->errors[] = 'Parsing failed (probably a mismatched parenthesis).';
			return false;
		}
		
		// Now generate the parsed array out of the blocks
		$parsed_tree = $this->parse_expression();
		if (count($this->errors)) return false;
		
		// When we finish parsing, there should only be one element left in the blocks array
		if (count($this->blocks) > 1)
		{
			$this->errors[] = 'Parsing failed near '.array_pop($this->blocks). ' (probably misplaced parentheses).';	
			return false;
		}		
		
		return $parsed_tree;
	}
	
	public function get_errors()
	{
		return $this->errors;
	}
	public function get_error_message()
	{
		$errors = $this->get_errors();
		if (empty($errors)) return false;
		
		if (count($errors) == 1)
			$message = 'An error occurred while parsing your page type rule:';
		else
			$message = 'Errors occurred while parsing your page type rule:';
			
		$message .= '<ul>'."\n";
		foreach ($errors as $error)
			$message .= '<li>'.htmlspecialchars($error).'</li>'."\n";
		$message .= '</ul>'."\n";
		return $message;
	}
	
	/**
	* The preparser processes all of the parenthesized subexpressions within an expression, producing an
	* array of each of the subexpressions where references to deeper subexpressions are replaced by their
	* keys in the array.
	* @access private
	* @param string $rule
	*/
	private function pre_parser($rule) {
		$rule = trim($rule);
		$level = count($this->blocks);
		$this->blocks[$level] = $rule;
				
	    // Split the rule on open and close parenthesis characters. 
	    $parts = preg_split('/[\(\)]/', $rule);
	    // generate a list of all parenthesis positions in the rule
	    preg_match_all('/[\(\)]/', $rule, $paren, PREG_OFFSET_CAPTURE);
	    
	    // use a variable 'stack' to keep track of what level of nested parens we're at. 
	    $stack = 0;
	    $start_index = $start_offset = $offset_offset = 0;
	    // If there are parens, search for the outmost set.
	    if (sizeof($paren[0])) {
		for ($i = 0; $i < sizeof($paren[0]); $i++) {
		    // Everytime we find an open parenthesis we need to increment the stack variable.
		    // If the stack is at its lowest level, save the index of the open parenthesis so we know where to
		    // combine again when we find the matching close parenthesis.
		    if ($paren[0][$i][0] == '(') {
			if ($stack == 0) { 
				$start_index = $i;
				$start_offset = $paren[0][$i][1];
			}
			$stack++;
		    }
		    // If we hit a close parenthesis character, we need to decrement the stack. If the stack value reaches
		    // 0 again, this means we've hit a top level parenthesis and we need to recursively call the function
		    // and tell it to evaluate the inner expression.
		    elseif ($paren[0][$i][0] == ')') {
			$stack--;
			if ($stack == 0) {
			    $sub = '';
			    // Here we recombine the inside equation. Since this can also include nested parens, we need
			    // to take care of that.
			    for ($j = $start_index+1; $j <= $i; $j++) {
				$sub .= $parts[$j];
				if ($j != $i) {
				    $sub .= $paren[0][$j][0];
				}
			    }
			    
			    // Replace the subexpression with the index of the blocks array element that contains it.
			    $rule = substr_replace($rule, count($this->blocks), $start_offset - $offset_offset, $paren[0][$i][1]-$start_offset+1);
			    
			    // offset_offset keeps track of the number of characters that have been removed from the rule
			    // at this level,so if we come back to take some more out, we start at the right spot.
			    $offset_offset += $paren[0][$i][1]-$start_offset;

			    // Update the contents of the blocks array for this level to the shortened rule.
			    $this->blocks[$level] = $rule;

			    // Call this function recursively on the subrule
			    $this->pre_parser( $sub );
			}
		    }
		}
	    } 
	    else 
	    {
		$this->blocks[$level] = $rule;
	    }
	}

	/**
	* The expression parser works on the array of blocks generated by the preparser and 
	* builds the parse tree for the overall rule.
	* @access private
	* @param string $rule
	*/
	private function parse_expression($rule = null)
	{
		$level = NULL;
		
		// If we're calling this without passing a rule, start at the top 
		// of the block of expressions.
		if (empty($rule)) $rule = $this->blocks[0];
		
		// Make sure we don't have any expressions that contain both AND and OR.
		if (preg_match('/\s+AND\s+/', $rule) && preg_match('/\s+OR\s+/', $rule))
		{
			$this->errors[] = 'ANDs and ORs mixed without parentheses in page type rule.';
			return false;
		}
		// If we're passed a number, this is a placeholder for a subexpression in the blocks array
		else if (is_numeric($rule))
		{
			if (isset($this->blocks[$rule]))
			{
				$level = $this->parse_expression($this->blocks[$rule]);
				unset($this->blocks[$rule]);
			}
			else
			{
				$this->errors[] = 'Parsing failed (probably a parenthesis problem).';	
				return false;
			}		
		}
		// If we have one or more ANDs, split and process the subexpressions
		else if (preg_match('/\s+AND\s+/', $rule))
		{
			$parts = preg_split('/\s+AND\s+/', $rule);
			foreach ($parts as $part)
			{
				$level['AND'][] = $this->parse_expression($part);	
			}
		}
		// If we have one or more ORs, split and process the subexpressions
		else if (preg_match('/\s+OR\s+/', $rule))
		{
			$parts = preg_split('/\s+OR\s+/', $rule);
			foreach ($parts as $part)
			{
				$level['OR'][] = $this->parse_expression($part);	
			}
		}
		// If we have NOT, parse the expression that follows
		else if (preg_match('/^\s*NOT\s+\s*(.*)\s*$/', $rule, $matches))
		{
			$level['NOT'] = $this->parse_expression($matches[1]);
		}
		// If there's a token followed by a colon, this is a base rule
		else if (preg_match('/^\s*([a-z0-9_-]+)\s*:\s*(.*)\s*$/', $rule, $matches))
		{
			if(pageTypeAvailability::is_supported_logic_key($matches[1]))
			{
				// Break into chunks that don't contain commas
				preg_match_all('/[^,]+/', $matches[2], $list);
				
				if (count($list[0]))
				{
					foreach ($list[0] as $k=>$v) 
					{
						if (strlen(trim($v))) $values[] = $v;
					}
					$level[$matches[1]] = $values;
				}
				else
					$this->errors[] = 'Parsing failed near '.$rule;
			}
			else
			{
				$this->errors[] = 'Unsupported key ("'.$matches[1].'") in '.$rule;
			}
		}
		else 
		{
			$this->errors[] = 'Parsing failed near '.$rule;	
		}
		return $level;
	}
}