<?php

/*
  WineHQ
  HTML class
  by Jeremy Newman <jnewman@codeweavers.com>
*/

class html 
{
    var $_file_root;
    var $_web_root;
    var $page = "";
    var $page_title;
    var $page_blurb;
    var $page_theme;
    var $page_style;
    var $meta_keywords;
    var $meta_description;
    var $rss_link;
    var $nav_add;
    var $_error_mode = 0;
    var $_view_mode = "default";
    var $relative = 1;
    var $in404 = 0;
    var $template_cache = array();
    var $lang = "en";
    var $languages = array();

    // HTML init object
    function html ($file_root = '.')
    {
        // set the file root
        $this->_file_root =& $file_root;
        $this->_web_root =& $GLOBALS['config']->base_root;

        // set HTML document defaults
        $this->page_title = $GLOBALS['config']->site_name;
        $this->page_blurb = $GLOBALS['config']->site_name;
        $this->page_theme = $GLOBALS['config']->theme;
        $this->page_style = "content";

        // get the language to be displayed in templates
        $this->lang = $this->get_lang();

        // start body object
        $this->page = "";
    }

    // SHOWPAGE (display the current page)
    function showpage ()
    {
        global $config;

        // debugging output
        $debug_log = "";
        if ($config->web_debug)
            $debug_log = $this->pre($GLOBALS['debug_log'], 'id="debug_log"');

        // 404 not found header
        if ($this->in404)
            header("HTTP/1.1 404 Not Found");
        
        // set meta tags
        $meta_keywords = "";
        $meta_description = "";
        if ($this->meta_keywords)
            $meta_keywords = $this->meta("keywords", $this->meta_keywords);
        if ($this->meta_description)
            $meta_description = $this->meta("description", $this->meta_description);
        
        // rss link
        if ($this->rss_link)
            $rss_link = '<link rel="alternate" title="'.$title.' RSS" href="'.$this->rss_link.'" type="application/xml">';
        
        // display page based on view mode
        switch ($this->_view_mode)
        {
            // plain text view
            case "text":
                $this->http_header("text/plain");
                echo $this->page;
                break;
        
            // print view
            case "print":
                $this->http_header("text/html");
                echo $this->template(
                                     $this->page_theme,
                                     "{$this->page_style}_print",
                                     array(
                                           'page_title'     => $this->page_title,
                                           'page_body'      => $this->page
                                          ),
                                     1
                                    );
                break;
            
            // regular view
            default:
                $this->http_header("text/html");
                echo $this->template(
                                     $this->page_theme,
                                     $this->page_style,
                                     array(
                                           'page_title'       => $this->page_title,
                                           'page_blurb'       => $this->page_blurb,
                                           'meta_keywords'    => $meta_keywords,
                                           'meta_description' => $meta_description,
                                           'rss_link'         => $rss_link,
                                           'page_body'        => $this->page,
                                           'copyright_year'   => date("Y", time()),
                                           'debug_log'        => $debug_log
                                          ),
                                     1
                                    );
        }
        
        // cleanup
        unset($debug_log);
    }

    // ERROR_PAGE
    function error_page ($message = null)
    {
        global $config;
        // set error mode
        $this->_error_mode = 1;
        // create error page (overwrites the current page in progress)
        $this->page = $message;
        // show page
        $this->showpage($config->theme, $config->title." - Internal Error");
        exit();
    }

    // ERROR_HANDLER
    function error_handler ($errno, $errstr, $errfile, $errline, $errcontext)
    {
        global $config, $data;
        
        // don't exit on a notice
        if ($errno == E_NOTICE or $errno == E_WARNING)
            return;

        // write to the error log (move above the above return to get NOTICE and WARNING messages)
        if (isset($config->error_log) and file_exists($config->error_log))
        {
            error_log(
                      "[".date("D M j G:i:s Y",time())."] [".$data->err[$errno]."] ".$errfile.":".$errline." - ".$errstr."\n",
                      3,
                      $config->error_log
                     );
        }

        // show additional debug output
        if ($config->web_debug and function_exists('debug_backtrace'))
        {
            // build context
            $ctx = '';
            foreach ($errcontext as $key => $value)
            {
                switch (gettype($value))
                {
                    case "string":
                        $ctx .= "[$key] => $value\n";
                        break;
                    default:
                        $ctx .= "[$key] => ".gettype($value)."\n";
                }
            }
            
            // build backtrace
            $backtrace = debug_backtrace();
            foreach ($backtrace as $bt)
            {
               $args = '';
               if (isset($bt['args']))
               {
                   foreach ($bt['args'] as $a)
                   {
                       if (!empty($args))
                           $args .= ', ';
                       switch (gettype($a))
                       {
                           case 'integer':
                           case 'double':
                               $args .= $a;
                               break;
                           case 'string':
                               $a = htmlspecialchars(substr($a, 0, 64)).((strlen($a) > 64) ? '...' : '');
                               $args .= "\"$a\"";
                               break;
                           case 'array':
                               $args .= 'Array('.count($a).')';
                               break;
                           case 'object':
                               $args .= 'Object('.get_class($a).')';
                               break;
                           case 'resource':
                               $args .= 'Resource('.strstr($a, '#').')';
                               break;
                           case 'boolean':
                               $args .= $a ? 'True' : 'False';
                               break;
                           case 'NULL':
                               $args .= 'Null';
                               break;
                           default:
                               $args .= 'Unknown';
                       }
                   }
               }
               $output .= "\n";
               $output .= "file: {$bt['file']}\n";
               $output .= "line: {$bt['line']}\n";
               $output .= "call: {$bt['class']}{$bt['type']}{$bt['function']}($args)\n";
            }
            
            $debug = "Context: \n".$ctx."\n\n";
            $debug .= "Backtrace: \n".$output."\n";
        }
        else
        {
            // cleanup path for non debug mode
            $errfile = basename($errfile);
        }
        
        // load into template and display error page
        $vars = array(
                      'errno'      => $data->err[$errno],
                      'errstr'     => $errstr,
                      'errfile'    => $errfile,
                      'errline'    => $errline,
                      'debug'      => $debug
                     );
        $this->error_page($this->template('local', 'global/fatal_error', $vars));
    }

    // GET LANG (get the language used for this session)
    function get_lang ()
    {
        // default from config
        $lang = $GLOBALS['config']->lang;
        
        // get lang
        if (isset($_COOKIE['lang']) and in_array($_COOKIE['lang'], $GLOBALS['config']->languages))
        {
            // load language from URL or cookie
            debug("global", "lang from cookie: {$_COOKIE['lang']}");
            $lang = $_COOKIE['lang'];
        }
        else if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]))
        {
            // load from web browser environment
            $avail = split(',', array_shift(split(";", $_SERVER["HTTP_ACCEPT_LANGUAGE"])));
            if (isset($avail[0]))
            {
                // if first language is a variation, use the parent language
                if (strlen($avail[0]) > 2)
                    $avail[0] = substr($avail[0], 0, 2);
                // check to make sure lang is defined in our config
                if (in_array($avail[0], $GLOBALS['config']->languages))
                {
                    debug("global", "lang from browser: {$avail[0]}");
                    $lang = $avail[0];
                }
            }
            unset($avail);
        }
        
        // return language
        debug("global", "lang: {$lang}");
        return $lang;
    }


    // GET ROOT - get the root of the website
    function get_root ()
    {
        return ($this->relative ? $this->_web_root : preg_replace("/\/$/", "", $GLOBALS['config']->base_url));
    }

    // HTML BR
    function br ($count = 1)
    {
        return str_repeat("<br />", $count);
    }
    
    // HTML IMG Tag
    function img ($src, $align = "", $alt = " ", $width = null, $height = null, $extra = null)
    {
        if ($align) $align = ' align="'.$align.'"';
        if ($alt) $alt = ' alt="'.$alt.'"';
        if ($extra) $extra = ' '.$extra;
        if ($src and is_file($this->_file_root."/images/".$src))
        {
            // load image from images dir
            $size = getimagesize($this->_file_root."/images/".$src);
            if ($size[3])
                $size = ' '.$size[3];
            return '<img src="'.$this->get_root()."/images/".$src.'"'.$size.$align.$extra.$alt.' />';
        }
        else if ($src)
        {
            // load other image
            if ($width) $width = ' width="'.$width.'"';
            if ($height) $height = ' height="'.$height.'"';
            return '<img src="'.$src.'"'.$width.$height.$align.$extra.$alt.' />';
        }
    }
    
    // HTML A HREF
    function ahref ($label, $url = "", $extra = "")
    {
        if ($extra)
            $extra = " $extra";
        if (!eregi(".",$label) and $url)
        {
            if (ereg("@", $url))
            {
                return '<a href="mailto:'.$url.'"'.$extra.'>'.$url.'</a>';
            }
            else
            {
                return '<a href="'.$url.'"'.$extra.'>'.$this->shorten($url, 20).'</a>';
            }
        }
        else if (!eregi(".", $label))
        {
            return ' &nbsp; ';
        }
        else
        {
            return '<a href="'.$url.'"'.$extra.'>'.$label.'</a>';
        }
    }

    // HTML B (bold)
    function b ($str, $extra = null)
    {
        if ($extra) { $extra = " ".$extra; }
        return "<b".$extra.">".$str."</b>";
    }

    // HTML I (italics)
    function i ($str, $extra = null)
    {
        if ($extra) { $extra = " ".$extra; }
        return "<i".$extra.">".$str."</i>";
    }

    // HTML Hn (header text)
    function h ($n, $str, $extra = null)
    {
        if ($extra) { $extra = " ".$extra; }
        return "<H$n".$extra.">".$str."</H$n>";
    }

    // HTML BLOCKQOUTE (indent)
    function blockquote ($str, $extra = null)
    {
        if ($extra) { $extra = " ".$extra; }
        return "<blockquote".$extra.">".$str."</blockquote>";
    }

    // HTML SMALL (small text)
    function small ($str, $extra = null)
    {
        if ($extra) { $extra = " ".$extra; }
        return "<small".$extra.">".$str."</small>";
    }

    // HTML P
    function p ($str = "&nbsp;", $extra = null)
    {
        if ($extra) { $extra = " ".$extra; }
        return "<p".$extra.">".$str."</p>";
    }

    // HTML DIV
    function div ($str = "&nbsp;", $extra = null)
    {
        if ($extra) { $extra = " ".$extra; }
        return "<div".$extra.">$str</div>";
    }
    
    // HTML SPAN
    function span ($str = "&nbsp;", $extra = null)
    {
        if ($extra) { $extra = " ".$extra; }
        return "<span".$extra.">".$str."</span>";
    }

    // HTML SUP
    function sup ($str = "", $extra = null)
    {
        if ($extra) { $extra = " ".$extra; }
        return "<sup".$extra.">".$str."</sup>";
    }

    // HTML PRE
    function pre ($str = "&nbsp;", $extra = null)
    {
        if ($extra) { $extra = " ".$extra; }
        return "<pre".$extra.">".$str."</pre>";
    }

    // HTML META
    function meta ($name = "", $content = "")
    {
        if ($name)
            return '<meta name="'.$name.'" content="'.$content.'">';
        return "";
    }

    // HTML UL
    function ul ($str = "", $extra = null)
    {
        if (is_array($str))
            $str = join("", array_map(array($this, "li"), $str));
        if ($extra) $extra = " {$extra}";
        return "<ul{$extra}>{$str}</ul>";
    }

    // HTML OL
    function ol ($str = "", $extra = null)
    {
        if (is_array($str))
            $str = join("", array_map(array($this, "li"), $str));
        if ($extra) $extra = " {$extra}";
        return "<ol{$extra}>{$str}</ol>";
    }

    // HTML LI
    function li ($str = "", $extra = null)
    {
        if ($extra) $extra = " {$extra}";
        return "<li{$extra}>{$str}</li>";
    }

    // encode text for use in a form field. Removes HTML reserved characters. (same as htmlspecialchars())
    function encode ($str)
    {
        $str = str_replace('&', '&amp;', $str);
        $str = str_replace('\'', '&#039;', $str);
        $str = str_replace('"', '&quot;', $str);
        $str = str_replace('<', '&lt;', $str);
        $str = str_replace('>', '&gt;', $str);
        return $str;
    }

    // decode text encoded with the encode() function
    function decode ($str)
    {
        $str = str_replace('&gt;', '>', $str);
        $str = str_replace('&lt;', '<', $str);
        $str = str_replace('&quot;', '\"', $str);
        $str = str_replace('&#039;', '\'', $str);
        $str = str_replace('&amp;', '&', $str);
        return $str;
    }
    
    // FORM START
    function form_start ($script, $name, $method = "get", $extra = null)
    {
        $str = '<form name="'.$name.'" action="'.$script.'" method="'.strtolower($method).'" '.$extra.'>'."\n";
        return $str;
    }

    // FORM END
    function form_end ()
    {
        return '</form>'."\n";
    }

    // FORM INPUT TEXT
    function form_input_text ($name, $size = 20, $value = "", $max = 0, $extra = null)
    {
        if ($extra)
            $extra = $extra.' ';
        if ($max)
            $extra .= 'maxlength="'.$max.'" ';
        if ($size >= 100)
            $size = 'style="width:100%;" ';
        else
            $size = 'size="'.$size.'" ';
        $value = ereg_replace('\{\$root\}', '&#123;&#036;root&#125;', $value);            
        $str = '<input type="text" name="'.$name.'" '.$size.'value="'.$value.'" '.$extra.'/>'."\n";
        return $str;
    }

    // FORM INPUT PASSWORD
    function form_input_password ($name, $size = 20, $value = "", $max = 0, $extra = null)
    {
        if ($max)
            $maxlengh = ' maxlength="'.$max.'"';
        if ($extra)
            $extra = ' '.$extra;
        $str = '<input type="password" name="'.$name.'" size="'.$size.'" value="'.$value.'"'.$maxlengh.$extra.' />'."\n";
        return $str;
    }

    // FORM INPUT HIDDEN FIELD
    function form_input_hidden ($name, $value = "")
    {
        $str = '<input type="hidden" name="'.$name.'" value="'.$value.'" />'."\n";
        return $str;
    }

    // FORM INPUT TEXT AREA
    function form_input_textarea ($name, $cols = 20, $rows = 5, $value = "")
    {
        $value = ereg_replace('\{\$root\}', '&#123;&#036;root&#125;', $value);
        if ($cols >= 100)
            $cols = 'style="width:100%;" cols="72" ';
        else
            $cols = 'cols="'.$cols.'" ';
        $str = '<textarea name="'.$name.'" rows="'.$rows.'" '.$cols.'wrap="soft">'.$value.'</textarea>'."\n";
        return $str;
    }

    // FORM INPUT SELECT (drop down)
    function form_input_select ($name, $options, $selected = "", $size = 1, $multi = null, $extra = "", $width = 72)
    {
        if ($multi)
            $multi = " multiple";
        if ($extra)
            $extra = " ".$extra;
        if (!$width)
            $width = 72;
        if (!is_array($options))
            return "";
        $str = '<select name="'.$name.'" size="'.$size.'"'.$multi.$extra.'>'."\n";
        while (list($key, $val) = each($options))
        {
            $val = $this->shorten($val, $width);
            if (is_array($selected) and in_array($key, $selected))
                $str .= '<option value="'.$key.'" selected="selected">'.$val.'</option>'."\n";
            else if ("$key" == "$selected")
                $str .= '<option value="'.$key.'" selected="selected">'.$val.'</option>'."\n";
            else
                $str .= '<option value="'.$key.'">'.$val.'</option>'."\n";		
        }
        $str .= '</select>'."\n";
        return $str;
    }

    // FORM INPUT TIMESTAMP (make an input field for timestamp)
    function form_input_timestamp ($fn, $timestamp, $nohourmin = 0, $allow_null = 0)
    {
        $months = array(1 => 'January', 'February', 'March', 'April', 'May',
                      'June', 'July', 'August', 'September', 'October',
                      'November', 'December');          
        // month
        $ascvar = $fn . "[1]";
        $ret.='  <select name="'.$ascvar.'">'."\n";
        if ($allow_null)
            $ret.='    <option value=""></option>'."\n";
        $cur = 1;
        while ($cur <= 12)
        {
            if ($timestamp and date("n",$timestamp) == $cur) { $selected = ' selected="selected"'; }
            $ret.='    <option value="'.$cur.'"'.$selected.'>'.$months[$cur].'</option>'."\n";
            $cur++;
            $selected = "";
        }
        $ret.='  </select>'."\n";
        
        // day
        $ascvar = $fn . "[2]";
        $ret.='  <select name="'.$ascvar.'">'."\n";
        if ($allow_null)
            $ret.='    <option value=""></option>'."\n";
        $cur = 1;
        while ($cur <= 31)
        {
            if ($timestamp and date("j",$timestamp) == $cur) { $selected = ' selected="selected"'; }
            $ret.='    <option value="'.$cur.'"'.$selected.'>'.$cur.'</option>'."\n";
            $cur++;
            $selected = "";
        }
        $ret.='  </select>'."\n";
        
        // year
        $ascvar = $fn . "[0]";
        $ret.='  <select name="'.$ascvar.'">'."\n";
        if ($allow_null)
            $ret.='    <option value=""></option>'."\n";
        $cur = "1998";
        while ($cur <= (date("Y")+5))
        {
            if ($timestamp and date("Y",$timestamp) == $cur) { $selected = ' selected="selected"'; }
            $ret.='    <option value="'.$cur.'"'.$selected.'>'.$cur.'</option>'."\n";
            $cur++;
            $selected = "";
        }
        $ret.='  </select>'."\n";
        
        // toggle hour minute display
        if ($nohourmin == 0)
        {
            // hour
            $ascvar = $fn . "[3]";
            $ret.='  <select name="'.$ascvar.'">'."\n";
            if ($allow_null)
                $ret.='    <option value=""></option>'."\n";
            $cur = "0";
            while ($cur <= "23")
            {
                if ($timestamp and date("G",$timestamp) == $cur) { $selected = ' selected="selected"'; }
                $ret.='    <option value="'.$cur.'"'.$selected.'>'.$cur.'</option>'."\n";
                $cur++;
                $selected = "";
            }
            $ret.='  </select>'."\n";
            
            // min
            $ascvar = $fn . "[4]";
            $ret.='  <select name="'.$ascvar.'">'."\n";
            if ($allow_null)
                $ret.='    <option value=""></option>'."\n";
            $cur = "0";
            while ($cur <= "59")
            {
                if ($cur < "10") { $cur = "0".$cur; }
                if ($timestamp and date("i",$timestamp) == $cur) { $selected = ' selected="selected"'; }
                $ret.='    <option value="'.$cur.'"'.$selected.'>'.$cur.'</option>'."\n";
                $cur++;
                $selected = "";
            }
            $ret.='  </select>'."\n";
        }
        else if ($timestamp)
        {
            // default to midnight
            $ret .= '<input type="hidden" name="'.$fn.'[3]" value="'.date("G",$timestamp).'" />'."\n";
            $ret .= '<input type="hidden" name="'.$fn.'[4]" value="'.date("i",$timestamp).'" />'."\n";
        }
        
        return $ret;
    }

    // PROC INPUT TIMESTAMP (process timestamp field)
    function proc_input_timestamp ($fn, $mode = 'unix')
    {
        if (is_array($fn))
            $lfn =& $fn;
        else
            $lfn =& $_REQUEST["$fn"];
        $strdate = $lfn[0]."-".$lfn[1]."-".$lfn[2];
        if ($lfn[3] and $lfn[4])
            $strdate .= " ".$lfn[3].":".$lfn[4];
        if ($strdate != "--")
        {
            $timestamp = strtotime($strdate);
            switch ($mode)
            {
                // return sql timestamp
                case "sql":
                    return date("YmdHis",$timestamp);
                    break;
                    
                // return unix timestamp
                default:
                    return $timestamp;
            }
        }
        else
        {
            return -1;
        }
    }

    // FORM INPUT CHECKBOX
    function form_input_checkbox ($name, $value = 1, $checked = 0, $extra = null)
    {
        if (!$extra)
            $extra = 'class="checkbox"';
        $extra = " $extra";
        if ($checked == 1)
            $str = '<input type="checkbox" name="'.$name.'" value="'.$value.'" checked="checked"'.$extra.' />'."\n";
        else
            $str = '<input type="checkbox" name="'.$name.'" value="'.$value.'"'.$extra.' />'."\n";
        return $str;
    }

    // FORM INPUT RADIO
    function form_input_radio ($name, $value = 1, $checked = 0, $extra = null)
    {
        if (!$extra)
            $extra = 'class="radio"';
        if ($extra)
            $extra = " $extra";
        if ($checked == 1)
            $str = '<input type="radio" name="'.$name.'" value="'.$value.'" checked="checked"'.$extra.' />'."\n";
        else
            $str = '<input type="radio" name="'.$name.'" value="'.$value.'"'.$extra.' />'."\n";
        return $str;
    }

    // FORM MULTI CHECKBOX (multi select using checkboxes [tableEditor set field]) 
    function form_multi_checkbox ($name, $options, $selected = "")
    {
        if (!is_array($options))
            return "";
        while (list($key, $val) = each($options))
        {
            if (is_array($selected) and in_array($key, $selected))
                $str .= $this->form_input_checkbox($name, $key, 1)." ".$val.$this->br();
            else if ($key == $selected)
                $str .= $this->form_input_checkbox($name, $key, 1)." ".$val.$this->br();
            else
                $str .= $this->form_input_checkbox($name, $key)." ".$val.$this->br();
        }
        return $str;
    }

    // FORM INPUT MULTI RADIO (multi select using radio [tableEditor enum field]) 
    function form_multi_radio ($name, $options, $selected = "")
    {
        if (!is_array($options))
            return "";
        while (list($key, $val) = each($options))
        {
            if ($key == $selected)
                $str .= $this->form_input_radio($name, $key, 1)." ".$val.$this->br();
            else
                $str .= $this->form_input_radio($name, $key)." ".$val.$this->br();
        }
        return $str;
    }

    // FORM FILE BUTTON (needs enctype set on form)
    function form_input_file ($name, $value = "")
    {
        return '<input type="file" name="'.$name.'" value="'.$value.'" />'."\n";
    }

    // FORM SUBMIT
    function form_submit ($value = "", $name = "", $extra = null)
    {
        if ($name)
            $name = ' name="'.$name.'"';
        $str = '<input type="submit"'.$name.' value="'.$value.'" class="button" '.$extra.' />'."\n";
        return $str;
    }

    // FORM BUTTON
    function form_button ($value = "", $name = "", $extra = null)
    {
        if ($name)
            $name = ' name="'.$name.'"';
        if ($extra)
            $extra .= " ";
        return "<input type=\"button\" class=\"button\"{$name} value=\"{$value}\" {$extra}/>\n";
    }

    // FORM JS BUTTON (button using javascript)
    function form_js_button ($url = null, $name = "&lt;&lt; Back", $extra = "")
    {
        if (!$url)
            $url = $_SERVER['HTTP_REFERER'];
        if ($extra)
            $extra .= " ";
        return "<input type=\"button\" class=\"button\" value=\"{$name}\" ".
               "onClick=\"javascript:self.location='$url';\" {$extra}/>\n";
        return $str;
    }

    // BACK LINK (simple back url)
    function back_link ($howmany = 1, $url = "")
    {
        if (!$url)
            $url = 'javascript:history.back('.$howmany.');';
        return $this->p('&nbsp;&nbsp; '.$this->ahref('&lt;&lt; Back',$url));
    }

    // ADD BR (replace \n with <br>)
    function add_br ($text = "")
    {
        $text = ereg_replace("\n","<br />\n",$text);
        return $text;
    }
    
    // HTML2TXT (convert HTML to text format)
    function html2txt ($str)
    {
        // remove HEADER junk, CSS, JS, and CDATA
        $str = preg_replace("'<head[^>]*>.*</head>'siU", '', $str);
        $str = preg_replace("'<style[^>]*>.*</style>'siU", '', $str);
        $str = preg_replace("'<script[^>]*>.*</script>'siU", '', $str);
        $str = preg_replace('@<![\s\S]*?--[ \t\n\r]*>@', '', $str);
        // P with closing tags replacement
        $str = preg_replace("'<p[^>]*>(.*)</p>'siU", "\\1\n\n", $str);
        // replace BR and single P tags
        $str = preg_replace('/<br\s*\/?>/i', "\n", $str);
        $str = preg_replace('/<p\s*\/?>/i', "\n\n", $str);
        // strip all other tags
        $str = preg_replace('@<[\/\!]*?[^<>]*?>@si', '', $str);
        // convert entities
        $str = preg_replace("/&nbsp;/", " ", $str);
        $str = html_entity_decode($str, null, "UTF-8");
        // remove extra whitespace
        $str = preg_replace("/\t/", "    ", $str);
        $str = preg_replace("/\r/", "", $str);
        $str = preg_replace("/ {1,}\n/sU", "\n", $str);
        $str = preg_replace("/\n{3,}/sU", "\n\n", $str);
        $str = preg_replace("/\n{4,}/sU", "", $str);
        // return text
        return $str;
    }
    
    // FORMAT MSG (make an email, web readable, etc.)
    function format_msg ($text = "", $class = "", $pre = 0)
    {
        $arr = explode("\n", $text);
        while (list($c,$val) = each($arr))
        {
            // remove any existing carrige returns
            $arr[$c] = preg_replace("/[\n\r]/", "", $arr[$c]);
            // line break at 80 chars (only for pre)
            if ($pre and strlen($arr[$c]) > 80)
            {
                $arr[$c] = wordwrap($arr[$c], 80, "\n", 1);
            }
            else if (!$pre)
            {
                // if a single word is longer than 72 chars, add spaces to it (for non-pre)
                $arr[$c] = $this->wraplongwords($arr[$c]);
            }
            // remove any remaining special characters
            $arr[$c] = $this->encode($arr[$c]);
            // add urls
            $arr[$c] = $this->urlify($arr[$c]);
            // add emails
            $arr[$c] = $this->emailify($arr[$c]);
            // add emoticons
            $arr[$c] = $this->emoticon($arr[$c]);
            // strip slashes (causes crash if not here)
            $arr[$c] = preg_replace('/\\\/', '&#092;', $arr[$c]);
            // quote message text
            if ($class)
            {
                if (ereg("^&gt; &gt; &gt; &gt;",$arr[$c]) or ereg("^&gt;&gt;&gt;&gt;",$arr[$c]))
                    $arr[$c] = $this->span($arr[$c], 'class="'.$class.'d"');
                else if (ereg("^&gt; &gt; &gt;",$arr[$c]) or ereg("^&gt;&gt;&gt;",$arr[$c]))
                    $arr[$c] = $this->span($arr[$c], 'class="'.$class.'c"');
                else if (ereg("^&gt; &gt;",$arr[$c]) or ereg("^&gt;&gt;",$arr[$c]))
                    $arr[$c] = $this->span($arr[$c], 'class="'.$class.'b"');
                else if (ereg("^&gt;", $arr[$c]))
                    $arr[$c] = $this->span($arr[$c], 'class="'.$class.'a"');
            }
        }
        $text = implode("\n", $arr);
        
        if ($pre)
            if ($class)
                return $this->pre($text, 'class="'.$class.'"');
            else
                return $this->pre($text);
        else
            if ($class)
                return $this->span(nl2br($text), 'class="'.$class.'"');
            else
                return nl2br($text);
    }

    // WRAPLONGWORDS (fix text that would be too long for web viewing)
    function wraplongwords ($text, $len = 72, $skip_pre = false)
    {
        $skip_wrap = 0;
        $lines = preg_split('/\n/', $text);
        for ($l = 0; $l < count($lines); $l++)
        {
            $words = preg_split('/\s/', $lines[$l]);
            for ($x = 0; $x < count($words); $x++)
            {
                if ($skip_pre)
                {
                    switch (true)
                    {
                        case preg_match("/\<pre\>/", $words[$x]):
                            $skip_wrap = 1;
                            break;
                        case preg_match("/\<\/pre\>/", $words[$x]):
                            $skip_wrap = 0;
                            break;
                    }
                }
                if (strlen($words[$x]) >= $len and !preg_match("%(http://|https://|ftp://)%", $words[$x]) and !$skip_wrap)
                {
                    $arr = preg_split('//', $words[$x], -1, PREG_SPLIT_NO_EMPTY);
                    for ($c = 0; $c < count($arr); $c = $c + $len)
                    {
                        array_splice($arr, $c, 0, 0);
                        $arr[$c] = " ";
                    }
                    $words[$x] = join("", $arr);
                    unset($arr);
                }
            }
            $lines[$l] = join(" ", $words);
        }
        return join("\n", $lines);
    }

    // JSENCODE (convert a string for use in a JavaScript function)
    function jsencode ($str)
    {
        $str = preg_replace('/\r/', '', $str);
        $str = preg_replace('/\n/', '\\n', $str);
        $str = preg_replace('/\'/', '\\\'', $str);
        return $str;
    }

    // URLIFY (search text and make urls linkable, also wrap URL at 100 chars)
    function urlify ($text)
    {
        // only use if text has links in it
        if (preg_match('%(http|https|ftp|rss)(://)([-\w\.]+)%', $text))
        {
            // extract existing HTML so it is left unprocessed (for example, bbcode is pre-converted)
            $html_matches = array();
            preg_match_all("/(<a href=.*>.*<\/a>)/Us", $text, $matches, PREG_PATTERN_ORDER);
            foreach ($matches[0] as $c => $match)
            {
                $html_matches[$c] = $matches[1][$c];
                $text = str_replace("$match", "__HTMLMATCH_{$c}__", $text);
            }
            
            // fix URL converted vars temporarily
            $text = preg_replace('/&gt;/', '<-RPLME->', $text);
            $text = preg_replace('/&quot;/', '"-RPLME-"', $text);
            
            // add a space to the beginning text so regex will work
            $text = " $text";
            
            // perform regular expression
            $text = preg_replace(
                                 '%((http|https|ftp|rss)(://)([-\w\.]+)(:\d+)?(/)?([\w/_\-\.\*\~]+)?(\?)?([\w_\-\.\;\&\=\%]+)?(\#)?([-\w\.]+)?)%e',
                                 "'<a href=\"\\1\">'.wordwrap('\\1', 100, '\n', 1).'</a>'",
                                 $text
                                );
                                
            // remove our temp conversion
            $text = preg_replace('/<-RPLME->/', '&gt;', $text);
            $text = preg_replace('/"-RPLME-"/', '&quot;', $text);
            
            // remove our temp space
            $text = preg_replace('%^ %', '', $text);
            
            // re-insert HTML
            preg_match_all("/(__HTMLMATCH_(\d+)__)/", $text, $matches, PREG_PATTERN_ORDER);
            foreach ($matches[0] as $c => $match)
            {
                $text = str_replace("$match", $html_matches[$c], $text);
            }
        }
        // return linkify'd text
        return $text;
    }

    // EMAILIFY (convert email addresses to links)
    function emailify ($text, $convert = 0)
    {
        // fix quoted printable
        if (ereg("^=", $text))
        {
            $text = quoted_printable_decode($text);
            $text = mb_convert_encoding($text, 'UTF-8');
            $text = eregi_replace("^\=\?[a-z0-9\-]+\?Q\?(.*)\?\=", "\\1", $text);
        }
        $emreg = "(.*) (<|&lt;)([a-zA-Z0-9_\.\+\/-]+)@([a-zA-Z0-9_\.-]+\.[a-zA-Z0-9]+)(>|&gt;)(.*)";
        if ($convert and ereg($emreg, $text))
        {
            // long format puts the name in and hides the email in the href
            //$text = ereg_replace($emreg, "<a href=\"mailto:\\3@\\4\">\\1</a>", $text);
            $text = preg_replace("/".$emreg."/e", "'<a href=\"mailto:\\3@\\4\">'.wordwrap('\\1', 25, ' ', 1).'</a>'", $text);
        }
        else
        {
            // regular emails
            $emailreg = "([a-zA-Z0-9_\.\+\/-]+)@([a-zA-Z0-9_\.-]+\.[a-zA-Z0-9]+)";
            if (ereg($emailreg,$text))
            {
                    $text = ereg_replace($emailreg, "<a href=\"mailto:\\1@\\2\">\\1@\\2</a>", $text);
            }
        }
        return $text;
    }

    // EMOTICON (convert text with emoticons)
    function emoticon ($text)
    {
        $smiles = array(
                        ":-)" => "smile.gif",
                        ":-(" => "sad.gif",
                        "B-)" => "cool.gif",
                        ":-p" => "razz.gif",
                        ";-)" => "wink.gif",
                        ":-D" => "biggrin.gif",
                        "X-(" => "mad.gif"
                       );
        while (list($smile,$img) = each($smiles))
        {
            $path = $this->img("emoticon/".$img);
            $text = str_replace($smile, $path, $text);
        }
        return $text;
    }

    // SHORTEN (take a string, and limit its length)
    function shorten ($str = "", $max = 40)
    {
        $str = ereg_replace("\n","", $str);
        if (strlen($str) > $max)
        {
            $str = substr($str,0,$max);
            $str .= "...";
        }
        return $str;
    }

    // BUILD URLARG (build a valid URL from a list of name/values)
    function build_urlarg ($vars, $skip = null, $array = null)
    {
        // if not a list, try converting it into a list
        if (!is_array($vars))
        {
            list($url,$params) = split('\?', $vars, 2);
            if (ereg(';', $params))
            {
                $urls = split(';',$params);
                $vars = array();
                foreach ($urls as $pair)
                {
                    list($key,$value) = split('=',$pair);
                    $vars[$key] = $value;
                }
            }
            else
            {
                // return nothing, as there is no vars
                return;
            }
        }
        
        // loop through vars and remove any we do not want, then build url
        $arr = array();
        while(list($key, $val) = each($vars))
        {
            // skip COOKIE vars
            if (isset($_COOKIE[$key]))
               continue;
            
            if ($skip and gettype($skip) == "array")
            {
                // simple in array check for strings
                if (in_array($key, $skip))
                    continue;
                // support for nested array keys
                foreach ($skip as $skip_ar)
                {
                    // if skip is an array and the array is the correct key check values of skip_ar
                    if (is_array($skip_ar) and isset($skip_ar[$key]))
                    {
                        $val_keys = array_keys($val);
                        $skip_in = array_values($skip_ar);
                        if (in_array($skip_in[0], $val_keys))
                        {
                            continue 2;
                        }
                    }
                }
                
            }
            else if ($skip and $key == $skip)
            {
                // non arrays, just match key to skip value
                continue;
            }
            
            if ($array)
                $key = $array."[".$key."]";
            
            if(is_array($val))
            {
                    while(list($idx, $value) = each($val))
                    {
                            //echo "Encoding $key / $value<br>";
                            $arr[] = rawurlencode($key."[".$idx."]")."=".rawurlencode($value);
                    }
            }
            else
            {
                $arr[] = $key."=".rawurlencode($val);
            }
        }
        return implode(";", $arr);
    }

    // GEN PASSWD (generate a password for user form, etc)
    function gen_passwd ($pass_len = 8)
    {
        $nps = "";
        mt_srand ((double) microtime() * 1000000);
        while (strlen($nps)<$pass_len)
        {
            $c = chr(mt_rand (0,255));
            if (in_array(strtolower($c), array('0', 'o' ,'i', 'l')))
                continue;
            if (eregi("[a-z0-9]", $c)) $nps = $nps.$c;
        }
        return ($nps);
    }

    // PERM LINK
    function gen_perm_link ($str)
    {
        $str = preg_replace('/\s/', '12WSPC21', $str);
        $str = preg_replace('/\W/', '12WSPC21', $str);
        $str = preg_replace('/12WSPC21/', '-', $str);
        $str = preg_replace('/-{2,}/', '-', $str);
        $str = strtolower($str);
        return $str;
    }

    // TEMPLATE (read a template file and fill in the variables)
    function template ($theme = null, $template, $vars = null, $noremovetags = 0)
    {
        global $config;
        
        // if no theme, use default theme from config
        if (!$theme)
            $theme = $config->theme;
        
        // debug
        debug("template", "loading template: theme:[".$theme."] lang: [".$this->lang."] template:[".$template."]");
        
        // load from cache if we have already loaded before
        if (isset($this->template_cache[$template]))
        {
            $in = $this->template_cache[$template];
        }
        else
        {
            // theme determines where template is loaded from
            switch ($theme)
            {
                // load from local template repository
                case "base":
                case "local":
                    if (file_exists($this->_file_root."/templates/".$this->lang."/".$template.".template"))
                        $in = join("",file($this->_file_root."/templates/".$this->lang."/".$template.".template"));
                    else if (($config->lang != $this->lang) and file_exists($this->_file_root."/templates/".$config->lang."/".$template.".template"))
                        $in = join("",file($this->_file_root."/templates/".$config->lang."/".$template.".template"));
                    break;
                
                // load from global
                case "global":
                    if (file_exists($this->_file_root."/templates/global/".$template.".template"))
                        $in = join("",file($this->_file_root."/templates/global/".$template.".template"));
                    break;

                // load template from theme
                default:
                    if (file_exists($this->_file_root."/include/themes/".$theme."/".$template.".template"))
                        $in = join("",file($this->_file_root."/include/themes/".$theme."/".$template.".template"));                
            }
        }

        // oops not found, load 404 template
        if (!$in)
        {
            $in = '';
            $this->in404 = 1;
            if (file_exists($this->_file_root."/templates/".$this->lang."/global/404.template"))
                $in .= join("",file($this->_file_root."/templates/".$this->lang."/global/404.template"));
            else if (file_exists($this->_file_root."/templates/".$config->lang."/global/404.template"))
                $in .= join("",file($this->_file_root."/templates/".$config->lang."/global/404.template"));
            else
                $in .= $this->h1("404 Not Found!");
        }

        // cache this template to save on i/o
        $this->template_cache[$template] = $in;
        
        // return the text with the vars replaced
        return $this->template_replace($in, $vars, $noremovetags);
    }
    
    // TEMPLATE_REPLACE (does the substitution for TEMPLATE)
    function template_replace ($in = "", $vars = array(), $noremovetags = 0)
    {
        // original in (avoid duplicate replacements)
        $orig = $in;
        
        // default path var
        $vars['root'] =& $this->_web_root;
        $vars['self'] =& $_SERVER['PHP_SELF'];
        $vars['file_root'] = "file://{$this->_file_root}";
        $vars['request_uri'] =& $_SERVER['REQUEST_URI'];
        $vars['request_delim'] = ($_GET ? ';' : '?');
        $vars['server_name'] =& $_SERVER['SERVER_NAME'];
        $vars['base_url'] =& $GLOBALS['config']->base_url;
        $vars['curtime_year'] = date('Y', time());
        
        // language vars
        if (defined("PAGE") and (PAGE == "home" or PAGE == "lang"))
        {
            $vars['langCode'] =& $this->lang;
            $vars['langCur'] =& $GLOBALS['data']->languages[$this->lang]['name'];
            $vars['langChange'] =& $GLOBALS['data']->languages[$this->lang]['change'];
        }
        
        // add config vars
        while (list($key, $val) = each($GLOBALS['config']))
        {
            if (is_string($val))
                $vars["config_{$key}"] = $val;
        }
        
        // replace vars in template
        // NOTE: using preg_replace() breaks as it wants to interpret '$1' in $val
        while (list($key,$val) = each($vars))
        {
            if (preg_match('/\{\$'.$key.'\}/', $orig))
            {
                $in = str_replace('{$'.$key.'}', $val, $in);
            }
            if (preg_match('/\{lc\(\$'.$key.'\\)}/', $orig))
            {
                $in = str_replace('{lc($'.$key.')}', ereg_replace(' ','_',strtolower($val)), $in);
            }
            if (preg_match('/\{htmlspecialchars\(\$'.$key.'\\)}/', $orig))
            {
                $in = str_replace('{htmlspecialchars($'.$key.')}', htmlspecialchars($val), $in);
            }
        }
        unset($key, $val);
        
        // resave the new orig with the template vars inserted (this allows EXEC other statements to include vars)
        $orig = $in;
        
        // allow the insertion of GET FORM vars
        if (preg_match('/\{\$_GET\[[a-z0-9_]+\]\}/', $orig))
        {
            preg_match_all('/\{\$_GET\[([a-z0-9_]+)\]\}/', $orig, $match);
            for ($i = 0; $i < count($match[0]); $i++)
            {
                if (isset($_GET[$match[1][$i]]))
                    $in = str_replace('{$_GET['.$match[1][$i].']}', htmlspecialchars($_GET[$match[1][$i]]), $in);
                else
                    $in = str_replace('{$_GET['.$match[1][$i].']}', "", $in);
            }
            unset($match, $i);
        }
        
        // remove all the unset vars
        if ($noremovetags == 0)
            $in = preg_replace('/\{\$[a-z0-9_\-]+\}/', '', $in);        
        
        // get page name from template
        if (preg_match('/<!--TITLE:\[(.+)\]-->/', $orig, $arr))
        {
            debug("global", "adding to title: {$arr[1]}");
            $in = preg_replace('/<!--TITLE:\[(.+)\]-->\n/', '', $in);
            $this->page_title .= " - {$arr[1]}";
            unset($arr);
        }

        // override the page theme
        if (preg_match('/<!--THEME:\[([\w]+)\]-->/', $orig, $arr))
        {
            debug("global", "changing theme: {$arr[1]}");
            $in = preg_replace('/<!--THEME:\[([\w]+)\]-->\n/', '', $in);
            $this->page_theme = $arr[1];
            unset($arr);
        }

        // override the page style
        if (preg_match('/<!--STYLE:\[([\w]+)\]-->/', $orig, $arr))
        {
            debug("global", "changing page style: {$arr[1]}");
            $in = preg_replace('/<!--STYLE:\[([\w]+)\]-->\n/', '', $in);
            $this->page_style = $arr[1];
            unset($arr);
        }

        // get page blurb template
        if (preg_match('/<!--BLURB:\[(.+)\]-->/', $orig, $arr))
        {
            debug("global", "setting page blurb: {$arr[1]}");
            $in = preg_replace('/<!--BLURB:\[(.+)\]-->\n/', '', $in);
            $this->page_blurb = $arr[1];
            unset($arr);
        }

        // set the meta keywords used for a page
        if (preg_match('/<!--META_KEYWORDS:\[([\w\s\-\&\'\;\,]+)\]-->/', $orig, $arr))
        {
            $in = preg_replace('/<!--META_KEYWORDS:\[([\w\s\-\&\'\;\,]+)\]-->\n/', '', $in);
            $this->meta_keywords = $arr[1];
            unset($arr);
        }

        // set the meta description uses for a page
        if (preg_match('/<!--META_DESCRIPTION:\[([\w\s\-\&\'\;\,\.\?]+)\]-->/', $orig, $arr))
        {
            $in = preg_replace('/<!--META_DESCRIPTION:\[([\w\s\-\&\'\;\,\.\?]+)\]-->\n/', '', $in);
            $this->meta_description = $arr[1];
            unset($arr);
        }


        // load nested templates the template
        if (preg_match('/<!--INCLUDE:\[[a-z0-9_\-\/]+\]-->/', $orig))
        {
            preg_match_all('/<!--INCLUDE:\[([a-z0-9_\-\/]+)\]-->/', $orig, $match);
            for ($i = 0; $i < count($match[0]); $i++)
            {
                $tmpl = $this->template("local", $match[1][$i]);
                $in = str_replace('<!--INCLUDE:['.$match[1][$i].']-->', "$tmpl", $in);
                unset($tmpl);
            }
            unset($match, $i);
        }

        // load and exec plugins in the template
        if (preg_match('/<!--EXEC:\[[0-9a-zA-Z\._=;@\-\?\/\|]+\]-->/', $orig))
        {
            preg_match_all('/<!--EXEC:\[([0-9a-zA-Z\._=;@\-\?\/\|]+)\]-->/', $orig, $match);
            for ($i = 0; $i < count($match[0]); $i++)
            {
                check_and_require("plugin");
                $plugin = new plugin($match[1][$i]);
                $in = str_replace('<!--EXEC:['.$match[1][$i].']-->', $plugin->get(), $in);
                unset($plugin);
            }
            unset($match, $i);
        }

        // override the page view mode (print, text)
        if (preg_match('/<!--VIEW:\[([\w]+)\]-->/', $orig, $arr))
        {
            $this->_view_mode = strtolower($arr[1]);
            $in = preg_replace('/<!--VIEW:\[([\w]+)\]-->\n/', '', $in);  
        }

        // return finished page
        unset($orig);
        return $in;
    }
    
    // HTTP HEADER (better header)
    function http_header ($type = "text/html")
    {
        header("Content-type: ".$type."; charset=UTF-8"); 
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }

    // REDIRECT (simple httpd header redirect) 
    function redirect ($url = "")
    {
        // clear page output buffer
        $this->clear_buffer();
        
        // if no URL default to website root
        if (!$url and $GLOBALS['config']->base_url)
        {
            $url = $GLOBALS['config']->base_url;
        }
        
        // redirect
        if ($url)
        {
            header("Location: ".$url);
            return;
        }
        
        // if still no URL then trigger error page
        trigger_error('Redirect URL not found!', E_USER_ERROR);    
    }

    // VERIFY_REFERER (checks to see if the referer is set to the website url)
    function verify_referer ()
    {
        if (eregi($GLOBALS['config']->base_url, $_SERVER['HTTP_REFERER']) or eregi($GLOBALS['config']->base_url_secure, $_SERVER['HTTP_REFERER']))
            return 1;
        else
            return 0;
    }

    // CLEAR BUFFER
    function clear_buffer ()
    {
        $status = ob_get_status();
        for ($c = 0; $c < ($status['level'] + 1); $c++)
        {
            ob_end_clean();
        }
    }

// end html class
}

?>
