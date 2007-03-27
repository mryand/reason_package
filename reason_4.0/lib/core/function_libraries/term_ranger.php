<?php
/*
    term sorting code written by Mark Heiman, adapted by Jason Oswald
    added summer "term" in, just in case. made it functionally equivalent
    a fall term and so we don't lose the 'adjacency' of spring and fall. 
*/

function term_range( $termlist )
{
	if( !is_array( $termlist ) )
		$termlist = array( $termlist );
    usort($termlist, "termCmp");
    return termRange($termlist);
}

function termCmp( $a, $b ) 
{
    // sort an array of terms in 00/TT format
    list($aYear,$aTerm) = split("/",$a);
    list($bYear,$bTerm) = split("/",$b);
    $term["FA"] = 3;
    $term["SU"] = 3;
    $term["SP"] = 2;
    $term["WI"] = 1;
    if ($aYear == $bYear) 
    { 
       $result = ($term[$aTerm] < $term[$bTerm]) ? -1 : 1; 
    }
    else
    { 
        $result = ($aYear < $bYear) ? -1 : 1; 
    }
    return $result;
}

function termRange($termlist) 
{
    // Take a list of terms in 00/TT format and turn them into
    // a list of ranges of terms for display.
    $termName["FA"] = "Fall";
    $termName["WI"] = "Winter";
    $termName["SP"] = "Spring";
    $termName["SU"] = "Summer";
    
    $term["FA"] = 3;
    $term["SU"] = 3;
    $term["SP"] = 2;
    $term["WI"] = 1;
    $start[] = $termlist[0];
    $currEnd = $termlist[0];

    for ($i=0; $i<(sizeof($termlist)-1); $i++) 
    {
        list($aYear,$aTerm) = split("/",$termlist[$i]);
        list($bYear,$bTerm) = split("/",$termlist[$i+1]);
        // if the year is the same, see if the terms are sequential
        if ($aYear == $bYear) 
        {
            if (($term[$aTerm] + 1) == $term[$bTerm]) 
            {
                $currEnd = $termlist[$i+1];
            }
            else 
            {
                $end[] = $termlist[$i];
                $start[] = $termlist[$i+1];
                $currEnd = $termlist[$i+1];
            }
        // if the years aren't the same, see if they're sequential
        } 
        elseif ($aYear + 1 == $bYear) 
        {
            if ($term[$aTerm] - $term[$bTerm] == 2) 
            {
                $currEnd = $termlist[$i+1];
            } 
            else 
            {
                $end[] = $termlist[$i];
                $start[] = $termlist[$i+1];
                $currEnd = $termlist[$i+1];
            }
        // if the years aren't the same, start a new range
        } 
        else 
        {
            $end[] = $termlist[$i];
            $start[] = $termlist[$i+1];
            $currEnd = $termlist[$i+1];
        }
    }
    $end[] = $currEnd;
    $range = array();
    for ($i=0; $i<sizeof($start); $i++) 
    {
        
        // convert to Term Year format for display
        list($lyear,$lterm) = split("/", $start[$i]);
        $start[$i] = $termName[$lterm]." 20".$lyear;
        list($lyear,$lterm) = split("/", $end[$i]);
        $end[$i] = $termName[$lterm]." 20".$lyear;
        $range[] .= ($start[$i] == $end[$i]) ? $start[$i] : 
        $start[$i]." through ".$end[$i];
    }
    if( is_array($range) ) 
    {
        $range_str = join(", ",$range);
    }
    return $range_str;
}
?>
