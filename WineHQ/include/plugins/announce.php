<?php

/*
    WineHQ Website
    Announce plugin (grabs latest announce from git)
    by Jeremy Newman <jnewman@codeweavers.com>
*/

// import global objects
global $html, $config;

// ver from URL
$ver = PAGE_PARAMS;

// build tag and announce URL
$tag = ($ver == "latest") ? "master" : "tags/wine-" . urlencode($ver);
$announce = $config->git_tree . "/wine.git/" . $tag . ":ANNOUNCE";

// load announce
if ($arr = file($announce))
{
    $in_header = 1;
    while (list($c,$val) = each($arr))
    {
        $arr[$c] = $html->urlify($arr[$c]);

        if (ereg("^--------------------", $arr[$c])) $in_header = 0;
        else if (ereg("^Bugs fixed", $arr[$c])) $in_bugs = 1;
        else if (ereg("^Changes since", $arr[$c])) $in_bugs = 0;

        if ($in_header)
        {
            $arr[$c] = ereg_replace("AUTHORS",
                                    "<a href=\"".$config->git_tree."/wine.git/".$tag.":\\0\">\\0</a>",
                                    $arr[$c]);
        }           
        else if ($in_bugs)
        {
            $arr[$c] = ereg_replace("^( +)([0-9]+)",
                                    "\\1<a href=\"".$config->bug_system."\\2\">\\2</a>",
                                    $arr[$c]);
        }
    }
    $announce = join("",$arr);

    // display page
    echo $html->pre($announce);
}
else
{
    // not found, redirect to home page
    $html->redirect($html->_web_root);
}

// done
?>
