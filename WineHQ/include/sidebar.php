<?php

/*
  WienHQ
  by Jeremy Newman <jnewman@codeweavers.com>
*/  

/*
 * sidebar.php -- Navigation creation Class
 */

class sidebar 
{
    // init the sidebar object
    function sidebar ($theme, &$menus, $root)
    {
        // use current theme
        $this->_theme = $theme;
        // load in the menus array
        $this->_menus = $menus;
        // root
        $this->_file_root = $root;
    }

	function menu ()
	{  
    	// loop and display nav
		while (list($name, $menus) = each($this->_menus))
		{
			$g = new htmlmenu($this->_theme, $name, $menus[$name]);
			while(list($item, $url) = each($menus))
			{
				if ($item == $name) continue;
                $url = ereg_replace('\{\$root\}', $this->_file_root, $url);
			    $g->add($item, $url);
			}
			$_menu .= $g->done("");
		}

		return $_menu;        
	}
}

/*
   HTMLmenu class
*/
class htmlmenu
{
	// create a htmlmenu object
    function htmlmenu ($theme, $name, $url)
    {
        $this->_menu = "";
        $this->vars = array(
                            'theme' => $theme,
                            'name' => $name,
                            'url' => $url
                           );
    }

    // add a menu link
    function add ($name, $url = null, $selected = 0)
    {
		if ($selected) $name = '<b>'.$name.'</b>';
		if ($url) $name = '<a href="'.$url.'" class="menuItem">'.$name.'</a>';
    	$this->_menu .= '  <tr class="sideMenu"><td width="100%"><span class="menuItem">&nbsp;'.$name.'</span></td></tr>'."\n";
    }
    
    // add text/misc to menu
    function addmisc ($stuff, $align = "left")
    {
		$this->_menu .= '   <tr class=sideMenu><td width="100%" align="'.$align.'">'.$stuff.'</td></tr>'."\n";
    }

    // complete and return menu box
    function done ($form = null)
    {
        global $html;
        $this->vars['body'] = $this->_menu;
        $menu = $html->template($this->vars['theme'], "menu", $this->vars);
		return $menu;
    }
    
// end htmlmenu class
}

?>
