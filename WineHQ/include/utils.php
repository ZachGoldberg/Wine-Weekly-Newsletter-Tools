<?php

/*
  WineHQ
  misc global functions
  by Jeremy Newman <jnewman@codeweavers.com>
*/

// wrapper around require_once to verify lib is not already loaded display nice error on failure
function check_and_require ($path)
{
    global $file_root;
    if (!isset($GLOBALS['LOADED_LIBS']) or !is_array($GLOBALS['LOADED_LIBS']))
        $GLOBALS['LOADED_LIBS'] = array();
    if (in_array($path, $GLOBALS['LOADED_LIBS']))
        return;
    if (file_exists("{$file_root}/include/{$path}.php"))
    {
        debug("global", "loading lib: [{$path}]");
        $GLOBALS['LOADED_LIBS'][] = $path;
        return require_once("{$file_root}/include/{$path}.php");
    }
    trigger_error("Unable to load library: {$path}", E_USER_ERROR);
}

// append to the debug log (only if web_debug is on)
function debug ($channel, $msg)
{
    if ($channel == "global" or in_array("all", $GLOBALS['config']->debug_chan) or in_array($channel, $GLOBALS['config']->debug_chan))
    {
        // store in global debug log var
        if ($GLOBALS['config']->web_debug and $msg)
            $GLOBALS['debug_log'] .= "[".date("D M j G:i:s Y",time())."] ".$msg."\n";
        // save debug log to file
        if ($GLOBALS['config']->debug and isset($GLOBALS['config']->debug_log))
        {
            error_log(
                      "[".date("D M j G:i:s Y",time())."] ".$msg."\n",
                      3,
                      $GLOBALS['config']->debug_log
                     );
        }
    }
}

// output a debug string so it appears above all the webpage content
function crap ($var = "", $var_dump = false)
{
    global $html;
    echo '<div style="position: relative; z-index: 99; width: 80%; padding: 0px; margin: 5px; border: 1px solid #FF0000; '.
         'background-color: #000000; opacity: 0.8; color: #FFFFFF;">'."\n";
    echo '<div style="border-bottom: 1px solid #FF0000; background-color: #FFFFFF; color: #FF0000; font-size: 8px;">DEBUG OUTPUT</div>'."\n";
    echo '<div style="margin: 5px;">'."\n";
    if ($var_dump)
    {
        echo '<xmp style="color: #ffffff;">';
        var_dump($var);
        echo '</xmp>';
    }
    else
    {
        echo $var;
    }
    echo "</div>\n";
    echo "</div>\n";
}

// get a list of files in a directory
function get_files ($dir, $filter = null)
{
    // read dir
    $files = array();
        
    $d = opendir($dir);
    while($entry = readdir($d))
    {
    	if ($filter)
	    {
	        if(!eregi("(.+)\\.".$filter, $entry))
	            continue;
	    }
    	array_push($files, $entry);
    }
    closedir($d);
    
    //sort dir
    sort($files);
    
    return $files;
}

// open file and display contents of selected tag (very simple)
function get_xml_tags ($file, $tags = null)
{
    if (is_array($tags) and file_exists($file))
    {
        $content = array();
        $fp = @fopen($file, "r");
	    $data = fread($fp, filesize($file));
	    @fclose($fp);
        foreach ($tags as $tag)
        {
            if (eregi("<" . $tag . ">(.*)</" . $tag . ">", $data, $out))
            {
                $content[$tag] = $out[1];
            }
        }
        return $content;
    }
    else
    {
        return null;
    }
}

// force and int to be a string
function int2str ($int)
{
    return "$int";
}

// filesize wrapper
function get_filesize ($file)
{
    if (file_exists($file))
        return filesize($file);
    else
        return 0;
}

// convert bytes to human readable
function bytes2human ($bytes)
{
    if (!$bytes)
        return 'n/a';
    $unim = array("B","KB","MB","GB","TB","PB");
    $c = 0;
    while ($bytes >= 1024)
    {
        $c++;
        $bytes = $bytes / 1024;
    }
    return number_format($bytes, ($c ? 2 : 0), ".", ",")." ".$unim[$c];
}

// search an array for a key, and optionally the value
function in_array_key ($key, $array, $value = false)
{
   while (list($k, $v) = each($array))
   {
       if ($key == $k)
       {
           if ($value && $value == $v)
               return true;
           else if ($value && $value != $v)
               return false;
           else
               return true;
       }
   }
   return false;
}

// delete a single element from an array (note: reindexes array as well)
function array_delete ($array, $key)
{
    if (is_array($array))
    {
        $new = array();
        foreach ($array as $n => $val)
        {
            if ($val == $key)
                continue;
            array_push($new, $val);
        }
        return $new;
    }
    return $array;
}

// delete the elements from an array, using values from another array
function array_delval ($delete = array(), $array)
{
    $new = array();
    foreach ($array as $n => $val)
    {
        if (!in_array($val, $delete))
            array_push($new, $val);
    }
    return $new;
}

// convert a numerical array to a name value array with the values the same as the keys
function array_valtokey ($array)
{
    if (!$array or !is_array($array) or count($array) == 0)
        return $array;
    $new_array = array();
    foreach ($array as $key => $val)
    {
        $new_array[$val] = $val;
    }
    return $new_array;
}

// takes assoc array, and filters is down using another list array
function array_finder ($array, $filter)
{
    $ret = array();
    foreach ($array as $key => $val)
    {
        if (in_array($key, $filter))
            $ret[$key] = $val;
    }
    return $ret;
}

// sort a multi-dimentional array by one of the keys
function array_qsort (&$array, $column=0, $order=SORT_ASC, $first=0, $last= -2)
{
    // $array  - the array to be sorted
    // $column - index (column) on which to sort
    //          can be a string if using an associative array
    // $order  - SORT_ASC (default) for ascending or SORT_DESC for descending
    // $first  - start index (row) for partial array sort
    // $last   - stop index (row) for partial array sort
    
    if($last == -2) $last = count($array) - 1;
    if($last > $first)
    {
        $alpha = $first;
        $omega = $last;
        $guess = $array[$alpha][$column];
        while ($omega >= $alpha) {
            if($order == SORT_ASC)
            {
                while($array[$alpha][$column] < $guess) $alpha++;
                while($array[$omega][$column] > $guess) $omega--;
            }
            else
            {
                while($array[$alpha][$column] > $guess) $alpha++;
                while($array[$omega][$column] < $guess) $omega--;
            }
            if($alpha > $omega) break;
            $temporary = $array[$alpha];
            $array[$alpha++] = $array[$omega];
            $array[$omega--] = $temporary;
        }
        array_qsort($array, $column, $order, $first, $omega);
        array_qsort($array, $column, $order, $alpha, $last);
    }
}

// check to see if an email address is valid
function valid_email ($address)
{
    return preg_match('/^[a-z0-9.+_-]+@([a-z0-9-]+.)+[a-z]+$/i', $address);
}

?>
