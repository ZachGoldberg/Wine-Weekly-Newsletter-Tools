<?php

/*
  WineHQ
  by Jeremy Newman <jnewman@codeweavers.com>

*/

/*
 * Main Include Library
 */

// load config class
require("{$file_root}/include/config.php");

// create config object
$config = new config($file_root."/include/"."winehq.conf", $file_root."/include/"."globals.conf");

// load global functions lib
require_once("{$file_root}/include/utils.php");

// load data lib
check_and_require("data");
$data = new data();

// load html lib
check_and_require("html");

// create html object
$html = new html($file_root);

?>
