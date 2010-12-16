<?php

/*
    WineHQ Website
    Select Language Plugin
    by Jeremy Newman <jnewman@codeweavers.com>
*/

global $html, $config;

// if specified, switch to lang
if (defined('PAGE_PARAMS') and in_array(PAGE_PARAMS, $config->languages))
{
    setcookie("lang", PAGE_PARAMS, time()+60*60*24*365, "{$config->base_root}/");
    $html->redirect($config->base_url);
    exit();
}

?>
