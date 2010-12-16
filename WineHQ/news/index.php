<?php

/*
  WineHQ
  by Jeremy Newman <jnewman@codeweavers.com>
*/

// fix old WWN urls

if ($_GET['view'])
    header("Location: ../?issue=".$_GET['view']);
else
    header("Location: ../");

?>
