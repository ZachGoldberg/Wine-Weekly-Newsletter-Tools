<?php

/*
  WineHQ
  by Jeremy Newman <jnewman@codeweavers.com>
*/

// load modules and defines
$file_root = realpath(dirname(__FILE__));
require("{$file_root}/include/incl.php");

// Fix the PHP_SELF global var
$_SERVER['PHP_SELF'] = preg_replace('/(site\/|site$)/', '', $_SERVER['PHP_SELF'], 1);
if (!preg_match('/\/$/', $_SERVER['PHP_SELF']))
    $_SERVER['PHP_SELF'] = $_SERVER['PHP_SELF']."/";

// load the path for the page
if (isset($_SERVER['PATH_INFO']))
{
    // set the Current Page
    $dirs = split('/', $_SERVER['PATH_INFO']);
    array_shift($dirs);
    if (count($dirs) > 1)
    {
        $good_dirs = array();
        foreach ($dirs as $dir)
        {
            // if page starts with a dot, it is a hidden file continue
            if (preg_match('/^\./', $dir))
                continue;
            
            // current page as string
            $temp_page = join('/', $good_dirs);
            
            // if this page is defined as a stop page, anything after it is not a template but params
            if (in_array($temp_page, $data->stop_page))
            {
                // get the params from the path
                $page_params = str_replace($temp_page, '', $_SERVER['PATH_INFO']);
                
                // cleanup the page params
                $page_params = preg_replace('/(^\/\/|^\/|\/$)/', '', $page_params);
                if ($page_params)
                    define("PAGE_PARAMS", $page_params);
                    
                // cleanup the PHP_SELF var
                $_SERVER['PHP_SELF'] = str_replace($page_params, '', $_SERVER['PHP_SELF']);
                $_SERVER['PHP_SELF'] = preg_replace('/\/\/$/', '/', $_SERVER['PHP_SELF']);
                
                // don't contine the loop, this is the end page
                unset($page_params);
                break;
            }
            
            // store the page
            array_push($good_dirs, $dir);
            unset($temp_page);
        }
        
        // cleanup and define page
        $page = join('/',$good_dirs);
        if (ereg('/$', $page))
            $page = ereg_replace('/$', '', $page);
        unset($c);
    }
    else
    {
        $page = $dirs[0];
    }
    define("PAGE", $page);
    unset($page, $dirs, $good_dirs);
}
else
{
    define("PAGE", 'home');
}

// loag page content
$html->page = $html->template("local", PAGE);

// display page
$html->showpage();

// done
?>
