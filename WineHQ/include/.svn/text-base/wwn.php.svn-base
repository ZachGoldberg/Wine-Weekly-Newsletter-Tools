<?

/*
  WineHQ
  WWN (World Wine News) issue class
  by Jeremy Newman <jnewman@codeweavers.com>
*/

class wwn 
{
    var $currentTag;
    var $issue;
    var $who;
    var $author;
    var $email;
    var $contact;
    var $body;
    var $summary;
    var $person;
    var $map_array;
    var $inquote = 0;
    var $mode = 'wwn';
    
    // init wwn object
    function wwn ()
    {
        $this->summary = array();
        $this->person = array();
        $this->map_array = array(
                                 "a"	        => "a",
                                 "b"	        => "b",
                                 "blockquote"	=> "blockquote",
                                 "center"       => "center",
                                 "code"         => "code",
                                 "div"          => "div",
                                 "font"          => "font",
                                 "i"	        => "i",
                                 "img"          => "img",
                                 "li"	        => "li",
                                 "table"        => "table",
                                 "tr"	        => "tr",
                                 "td"	        => "td",
                                 "th"           => "th",
                                 "dt"           => "dt",
                                 "dd"           => "dd",
                                 "tt"           => "tt",
                                 "pre"          => "pre",
                                 "u"	        => "u",
                                 "ul"	        => "ul",
                                 "ol"	        => "ol",
                                );
    }
    
    // read dir and get issues
    function get_list ()
    {
        global $config;
        $wwn = array();
        switch ($this->mode)
        {
            case "interviews":
                $dir = opendir($config->wwn_xml_path."/".$config->lang."/interviews/".$path);
                break;
            default:
                $dir = opendir($config->wwn_xml_path."/".$config->lang);
        }
        while($file = readdir($dir))
        {
            if ($file == "." or $file == ".." or $file == "CVS" or $file == "interviews")
              continue;
            switch ($this->mode)
            {
                case "interviews":
                    $issue = ereg_replace("interview_([0-9]+)\.xml", "\\1", $file);
                    break;
                
                default:
                    $issue = ereg_replace("wn[0-9]+_([0-9]+)\.xml", "\\1", $file);
            }
            if ($issue > $old)
                $cur = $issue;
            $old = $issue;
            $wwn[$issue] = $file;
        }
        closedir($dir);
        natsort($wwn);
        $wwn = array_reverse($wwn, true);
        return array($wwn, $cur);
    }

    // show back issues
    function issues_list ($issues, $cur, $limit = 0, $pos = 0)
    {
        global $file_root, $config, $html;
    
        // number of issues to show per page
        switch ($this->mode)
        {
            case "interviews":
                $perpage = 50;
                break;
            default:
                $perpage = 12;
        }
        
        // total issues
        $total = count($issues);
        
        // couters
        $where = 0;
        $c = 0;
        $num = -1;
        
        if ($limit == 0)
          $back = '<div align=center><table cellpadding=0 cellspacing=20 width="100%"><tr valign=top>'."\n";	
        
        while(list($issue,$file) = each($issues))
        {
            // inner counter
            $num++;
    
            if ($limit != 0 && $c == $limit)
            {
              // break out if we hit our limit
              break;
            }
            else if ($pos > $num)
            {
              // do not show images less than current pos
              continue;
            }
    
            // display summary
            $this->wwn_xml_parse($file);
            $summary_box = $this->format_summary($issue, $this->issue);
            
            if ($limit == 0)
              $back .= "\n\n<td>\n".$summary_box."\n</td>\n";
            else
              $back .= "\n\n".$summary_box."\n";
              
            // outer counter
            $c++;
    
            // end row at 3
            if (($limit == 0) && ($c % 3 == 0) && ($c != 1))
            {
                $back .= "</tr><tr valign=top>\n";
            }
            
            // end at max
            if ($c == $perpage)
            {
                $where = $num;
                break;
            }			
        }
        
        if ($limit == 0)
          $back .= '</tr></table></div><br>'."\n";
        
        if ($limit != 0)
        {
            $back .= "<p class=\"small\"><a href=\"".$html->_web_root."/".PAGE."/"."\">More Issues...</a></p>";
        }
        else if ($total > $perpage)
        {
            $nextLink = "&nbsp;";
            $nextLink = "&nbsp;";
    
            // display prev link
            if ($pos >= $perpage)
            {
                $prev = $pos - $perpage;
                $prevLink = $html->ahref("&lt;&lt; Prev $perpage Issues", $html->_web_root."/".PAGE."/?pos=".$prev);
            }
            else
            {
                $prevLink = "<span class=\"disabled\">&lt;&lt; Prev $perpage Issues</span>";
            }
    
            // display next link
            if (($pos + $perpage) < $total)
            {
                $next = $where + 1;
                $nextLink = $html->ahref("Next $perpage Issues &gt;&gt;",$html->_web_root."/".PAGE."/?pos=".$next);
            }
            else
            {
                $nextLink = "<span class=\"disabled\">Next $perpage Issues &gt;&gt;</span>";
            }
            
        
            $back .= "<div align=\"center\"><table width=\"50%\"><tr>\n".
                     "<td align=\"left\">&nbsp; $prevLink</td>".
                     "<td align=\"right\">&nbsp; $nextLink</td>".
                     "</tr></table></div>\n";
        }
            
        return $back;
    }
    
    // format the summary box
    function format_summary ($cur, $date = null)
    {
        global $html;
        
        switch ($this->mode)
        {
            case "interviews":
                $summary_box = '<a href="'.$html->_web_root."/".PAGE."/".$cur.'"><b>'.$this->who.'</b></a>'.
                               '<br><span class=small>'.$date.'</span><ul type="circle">'."\n";
                break;
            
            default:
                $summary_box = '<a href="'.$html->_web_root."/".PAGE."/".$cur.'"><b>Issue: '.$cur.'</b></a>'.
                               '<br><span class=small>'.$date.'</span><ul type="circle">'."\n";
        }
        
        $c = 0;
        while(list($id,$sum) = each($this->summary))
        {
            $summary_box .= '<li><a href="'.$html->_web_root."/".PAGE."/".$cur."#".$sum.'" class="small">'.$sum.'</a></li>'."\n";
            $c++;
        }
        $summary_box .= "</ul>";
        return $summary_box;
    }
    
    // display a single wwn issue
    function view_issue ($view = "latest")
    {
        global $config, $html, $cur;
    
        // read dir and get issues
        list($wwn, $cur) = $this->get_list();
    
        // view back issues
        if ($view == 'back')
        {
            $html->template_title = 'WWN Back Issues';
            return $this->issues_list($wwn, $cur, 0, $_REQUEST['pos']);
        }
        
        // show selected issue, or current
        if ($view and $view != 'latest' and $wwn[$view])
          $cur = $view;

        // get issue
        $this->wwn_xml_parse($wwn[$cur]);
        
        // get summary
        $summary_box = $this->format_summary($cur);
        
        // title for page
        $html->template_title = 'WWN Issue #'.$cur;
        
        // load issue into template
        $wwn_vars = array(
                          'issue'   => $cur,
                          'date'    => $this->issue,
                          'summary' => $summary_box,
                          'xml'     => basename($wwn[$cur], '.xml'),
                          'author'  => $this->author,
                          'contact' => $this->contact,
                          'body'    => $this->body,
                         );

        return $html->template("base", "wwn_content", $wwn_vars);;
    }
    
    // display a single wwn issue
    function view_interview($interview)
    {
        global $config, $html;
    
        // read dir and get issues
        list($wwn, $cur) = $this->get_list("interviews");

        // no interview found, show a 404
        if (!$interview or !$wwn[$interview])
        {
            $html->in404 = 1;
            return $html->template("base", "404");
        }
        
        // get issue
        $this->wwn_xml_parse($wwn[$interview]);
        
        // title for page
        $html->template_title = $this->who.' Interview';
        
        // load issue into template
        $wwn_vars = array(
                          'who'    => $this->who,
                          'date'   => $this->issue,
                          'author' => $html->ahref($this->author, 'mailto:'.$this->email),
                          'body'   => $this->body,
                         );
        
        return $html->template("base", "wwn_interview", $wwn_vars);
    }
    
    // read the xml file and parse it
    function wwn_xml_parse ($file)
    {
        global $config, $html;
        
        // load file from language
        switch ($this->mode)
        {
            // interviews mode
            case "interviews":
                if (file_exists($config->wwn_xml_path."/".$html->lang."/interviews/".$file))
                    $file = $config->wwn_xml_path."/".$html->lang."/interviews/".$file;
                else
                    $file = $config->wwn_xml_path."/".$config->lang."/interviews/".$file;
                break;
            
            // wwn mode
            default:
                if (file_exists($config->wwn_xml_path."/".$html->lang."/".$file))
                    $file = $config->wwn_xml_path."/".$html->lang."/".$file;
                else
                    $file = $config->wwn_xml_path."/".$config->lang."/".$file;
        }
        
        $this->issue = "";
        $this->body = "";
        $this->who = "";
        $this->summary = array();
    
        // initialize parser
        $xml_parser = xml_parser_create();
    
        // set callback functions
        xml_set_object($xml_parser, &$this);
        xml_set_element_handler($xml_parser, "startElement", "endElement");
        xml_set_character_data_handler($xml_parser, "characterData");
    
        // open XML file
        if (!($fp = fopen($file, "r")))
        {
            $this->body = "Cannot locate XML data file: $file";
            return;
        }
    
        // read and parse data
        while ($data = fread($fp, 16384))
        {
            // translate & entities
            $data = _translateLiteral2NumericEntities($data);
            $data = _translateWWN2valid($data);
            
            // error handler
            if (!xml_parse($xml_parser, $data, feof($fp)))
            {
                $xml_error = xml_error_string(xml_get_error_code($xml_parser));
                $xml_line = xml_get_current_line_number($xml_parser);
                $this->body = "<h1>XML error: $xml_error at line $xml_line</h1>";
                return;
            }
        }
    
        // clean up
        xml_parser_free($xml_parser);
    }
    
    // process elements in xml
    function startElement ($parser, $name, $attrs)
    {
        global $html;
    
        // set current tag
        $this->currentTag = $name;
        
        // output opening HTML tags
        switch ($name)
        {
            case "KC":
            case "TITLE":
              break;
            case "ISSUE":
              $this->issue = $attrs{'DATE'};
              $this->who = $attrs{'WHO'};
              break;
            case "AUTHOR":
              $this->email = $attrs{'EMAIL'};
              $this->contact = $attrs{'CONTACT'};
              break;
            case "INTRO":
              $this->body .= "<a name=\"Intro\"></a>\n";
              break;	
            case "SECTION":
              array_push($this->summary, $attrs{'TITLE'});
              $this->body .= $html->br();
              $this->body .= "<a name=\"".$attrs{'TITLE'}."\"></a>\n";
              $this->body .= "<table>\n";
              $this->body .= "<tr>\n";
              $this->body .= "<th width=\"100%\">".$attrs{'TITLE'}."</th>\n";
              $this->body .= "<th>".$attrs{'STARTDATE'}."</td>\n";
              $this->body .= "<th><a href=\"".$attrs{'ARCHIVE'}."\">Archive</a></th>\n";
              $this->body .= "</tr>\n";
              $this->body .= "<tr><td colspan=\"3\">\n";
              break;
            case "STATS":
              $pctMTO = intval(($attrs{'MULTIPLES'} / $attrs{'CONTRIB'}) * 100);
              $pctLWK = intval(($attrs{'LASTWEEK'} / $attrs{'CONTRIB'}) * 100);
              $this->body .= $html->br();
              $this->body .= "<table>\n";
              $this->body .= "<tr><td colspan=\"3\">\n";
              $this->body .= "<p> This week, ".$attrs{'POSTS'}." posts consumed ".$attrs{'SIZE'}." K. ".
                      "There were ".$attrs{'CONTRIB'}." different contributors. ".
                  $attrs{'MULTIPLES'}." (".$pctMTO."%) posted more than once. ".
                  $attrs{'LASTWEEK'}." (".$pctLWK."%) posted last week too.</p>\n".
                  "<p>The top 5 posters of the week were:</p>\n";
              break;
            case "PERSON":
              array_push(
                         $this->person,
                         array(
                               "WHO"   => $attrs{'WHO'},
                               "POSTS" => $attrs{'POSTS'},
                               "SIZE"  => $attrs{'SIZE'}
                              )
                        );
              break;
            case "QUOTE":
              $this->body .= '<span class="wwnQuote">';
              $this->inquote = 1;
              break;
            case "INTERVIEW":
              break;
            case "QUESTION":
              $this->body .= "<p><b>";
              break;
            case "ANSWER":
              $this->body .= '<span class="wwnQuote">';
              $this->inquote = 1;
              break;
            case "TOPIC":
              $this->body .= "<b>";
              break;
            case "P":
              if ($this->inquote)
                $this->body .= '<p class="indent">';
              else
                $this->body .= "<p>";
              break;
            case "DL":
              if ($this->inquote)
                $this->body .= '<dl style="margin-left:1em;">';
              else
                $this->body .= '<dl>';
              break;
            case "BR":
              $this->body .= $html->br();
              break;
            default:
              if (isset($this->map_array[strtolower($name)]))
              {
                $attribs = "";
                while(list($key,$value) = each($attrs))
                {
                    $attribs .= " $key=\"$value\"";
                }
                $this->body .= "<".$this->map_array[strtolower($name)].$attribs.">";		  
              }
              else
              {
                $this->body .= "&lt;".strtolower($name)."&gt;";
              }
              break;
        }
    }
    
    function endElement ($parser, $name)
    {
        global $html;
        
        // output closing HTML tags
        switch ($name)
        {
            case "KC":
            case "TITLE":
            case "AUTHOR":
            case "ISSUE":
            case "PERSON":
            case "INTRO":
              break;	
            case "SECTION":
              $this->body .= "\n\n</td></tr>\n";
              $this->body .= "</table>";
              break;
            case "STATS":
              $this->body .= "<ol>\n";
              $c = 0;
              array_qsort($this->person, "POSTS", SORT_DESC);
              foreach ($this->person as $array)
              {
                if ($c == 5)
                  break;
                $array['WHO'] = preg_replace("/&lt;.*&gt;/", "", $array['WHO']);
                $this->body .= "<li>".$array['POSTS']." posts in ".
                               $array['SIZE']."K by ".
                               $array['WHO']."</li>\n";
                $c++;
              }
              $this->body .= "</ol>\n";
              $this->body .= "\n\n</td></tr>\n";
              $this->body .= "</table>";
              break;
            case "QUOTE":
              $this->body .= "</span>";
              $this->inquote = 0;
              break;
            case "INTERVIEW":
              break;
            case "QUESTION":
              $this->body .= "</b></p>";
              break;
            case "ANSWER":
              $this->body .= "</span></p>";
               $this->inquote = 0;
              break;
            case "TOPIC":
              $this->body .= "</b>";
              break;
            case "P":
              $this->body .= "</p>";
              break;
            case "DL":
              $this->body .= "</dl>";
              break;
            case "BR":
              break;
            default:
              if (isset($this->map_array[strtolower($name)]))
              {
                $this->body .= "</".$this->map_array[strtolower($name)].">\n";
              }
              else
              {
                $this->body .= "&lt;/".strtolower($name)."&gt;";
              }
              break;
        }
    
        // clear current tag variable
        $this->currentTag = "";
    }
    
    // process data between tags
    function characterData ($parser, $data)
    {    
        // format the data
        switch ($this->currentTag)
        {
            case "TITLE":
              break;
            case "AUTHOR":
              $this->author = $data;
              break;		  
            default:
              $this->body .= $data;
              break;	  
        }
    }

}
// end wwn class

// translate & entities to html numeric values
function _translateLiteral2NumericEntities ($xmlSource, $reverse = FALSE)
{
    static $literal2NumericEntity;    
    if (empty($literal2NumericEntity)) {
      $transTbl = get_html_translation_table(HTML_ENTITIES);
      foreach ($transTbl as $char => $entity) {
        if (strpos('&"<>', $char) !== FALSE) continue;
        $literal2NumericEntity[$entity] = '&#'.ord($char).';';
      }
    }
    if ($reverse) {
      return strtr($xmlSource, array_flip($literal2NumericEntity));
    } else {
      return strtr($xmlSource, $literal2NumericEntity);
    }
}

// fix other issues in wwn xml
function _translateWWN2valid ($xmlSource)
{
    // fix any stray ampersands
    $xmlSource = eregi_replace("&", "&#038;", $xmlSource);
    // strip out comments
    $xmlSource = eregi_replace("<!-- .* -- />", "", $xmlSource);	
    return $xmlSource;
}

?>
