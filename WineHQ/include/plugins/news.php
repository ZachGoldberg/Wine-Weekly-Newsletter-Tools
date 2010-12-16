<?php

/*
    WineHQ Website
    News plugin
    by Jeremy Newman <jnewman@codeweavers.com>
*/

// import globals
global $html, $config;

// language override
if ($_GET['lang'] and in_array($_GET['lang'], $config->languages))
    $html->lang = $_GET['lang'];

// display news based on page params
switch (true)
{
    // single issue view
    case (defined('PAGE_PARAMS') and preg_match("/[0-9]{10}/", PAGE_PARAMS)):
        // get data from XML file
        $item = PAGE_PARAMS . '.xml';
        $vars = array();
        if (file_exists($config->news_xml_path.'/'.$html->lang.'/'.$item))
            $vars = get_xml_tags($config->news_xml_path.'/'.$html->lang.'/'.$item, array('date', 'title', 'link', 'body'));
        else
            $vars = get_xml_tags($config->news_xml_path.'/'.$config->lang.'/'.$item, array('date', 'title', 'link', 'body'));
        
        // if link defined, use it in title
        if ($vars['link'])
        {
            $vars['link'] = str_replace("{\$root}", $html->_web_root, $vars['link']);
            $vars['title'] = '<a href="'.$vars['link'].'">'.$vars['title'].'</a>';
        }
        else
        {
            $vars['title'] = '<a href="'.$html->_web_root.'/news/'.basename($item, ".xml").'">'.$vars['title'].'</a>';
        }
        
        // replace {$root}
        $vars['body'] = str_replace("{\$root}", $html->_web_root, $vars['body']);
        
        // add to news body
        echo $html->template('base', 'news_row', $vars);
        echo $html->p($html->ahref("&lt;&lt; Back", "{$html->_web_root}/news"));
        break;

    // RSS view
    case (defined('PAGE_PARAMS') and PAGE_PARAMS == "rss"):
        // get list of news items
        $news = get_files($config->news_xml_path."/".$config->lang, "xml");
        $news = array_reverse ($news);

        // clear cache and output the rss file
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: application/xml');
        header('Content-Disposition: inline; filename="winehq_news.xml";');   
        $rss_rows = "";
        $c = 0;
        foreach ($news as $key => $item)
        {
            $c++;
            
            // limit to 10 most recent articles
            if ($c > 10)
                continue;
            
            // get data from XML file
            if (file_exists($config->news_xml_path.'/'.$html->lang.'/'.$item))
                $vars = get_xml_tags($config->news_xml_path.'/'.$html->lang.'/'.$item, array('date', 'title', 'link', 'body'));
            else
                $vars = get_xml_tags($config->news_xml_path.'/'.$config->lang.'/'.$item, array('date', 'title', 'link', 'body'));

            // fix filename for urls
            $item = str_replace(".xml", "", $item);

            // compute where to link to if unspecified
            if (!$vars['link'])
                $vars['link'] = $config->base_url.'news/'.$item;

            // replace {$root}
            $vars['link'] = str_replace("{\$root}", $config->base_url, $vars['link']);
            $vars['body'] = str_replace("{\$root}", $config->base_url, $vars['body']);

            // display row
            $rss_row = array(
                                'item_title' => strip_tags($vars['title']),
                                'item_desc'  => htmlentities($vars['body']),
                                'item_link'  => $vars['link'],
                                'item_guid'  => $config->base_url.'?news='.$item,
                                'item_date'  => date("r", strtotime($vars['date']))
                            );
            $rss_rows .= $html->template('global', 'xml/rss_row', $rss_row, 1);
        }
        unset($c);
        $rss = array(
                        'rss_date'  => date("r", $top_date),
                        'rss_title' => $config->site_name.' News',
                        'rss_link'  => "{$config->base_url}news/rss",
                        'rss_img'   => "{$config->base_url}images/classic_top_logo.png",
                        'rss_desc'  => 'News and information about Wine',
                        'rss_crt'   => '(C) '.$config->site_name.' '.date("Y", time()),
                        'rss_rows' => $rss_rows
                    );
        echo $html->template('global', 'xml/rss', $rss);
        exit();
        break;
     // end rss

    // default view
    default:
        // load rss link
        $html->rss_link = "{$config->base_url}news/rss/";

        // max count for posts per page
        $amax = (PAGE == "home" ? 10 : 25);

        // where are we in news list
        $x = 0;
        if (is_numeric(PAGE_PARAMS))
            $x = PAGE_PARAMS;

        // get list of news items
        $news = get_files($config->news_xml_path."/".$config->lang, "xml");
        $news = array_reverse ($news);
        $total = count($news);

        // loop and display news
        $c = 0;
        foreach ($news as $key => $item)
        {
            // counter
            $c++;

            // do not show posts less than current pos
            if ($x != 1 and $x >= $c)
                continue;

            // get data from XML file
            $vars = array();
            if (file_exists($config->news_xml_path.'/'.$html->lang.'/'.$item))
                $vars = get_xml_tags($config->news_xml_path.'/'.$html->lang.'/'.$item, array('date', 'title', 'link', 'body'));
            else
                $vars = get_xml_tags($config->news_xml_path.'/'.$config->lang.'/'.$item, array('date', 'title', 'link', 'body'));
            
            // if link defined, use it in title
            if ($vars['link'])
            {
                $vars['link'] = str_replace("{\$root}", $html->_web_root, $vars['link']);
                $vars['title'] = '<a href="'.$vars['link'].'">'.$vars['title'].'</a>';
            }
            else
            {
                $vars['title'] = '<a href="'.$html->_web_root.'/news/'.basename($item, ".xml").'">'.$vars['title'].'</a>';
            }
            
            // replace {$root}
            $vars['body'] = str_replace("{\$root}", $html->_web_root, $vars['body']);
            
            // add to news body
            echo $html->template('base', 'news_row', $vars);
            
            // show only $max
            if ($c - $x == $amax)
                break;
        }
        // end of news loop

        // prev/next links
        if (PAGE != 'home')
        {
            $pages = floor(($total / $amax));
            $prev_link = "";
            $next_link = "";
            // add back to first page link when not first page
            if ($x != 0)
                $prev_link .=  $html->ahref("[First Page]", $_SERVER['PHP_SELF'], 'class="menuItem"')." ";
            // display prev link
            if ($x > 0)
                $prev_link .= $html->ahref("&lt;&lt; Previous Page","{$_SERVER['PHP_SELF']}".($x - $amax), 'class="menuItem"');
            // display next link
            if (($x + $amax) < $total)
            {
                $next_link .= $html->ahref("Next Page &gt;&gt;","{$_SERVER['PHP_SELF']}".($x + $amax) , 'class="menuItem"');
                // add jump to last page link
                $next_link .= " ".$html->ahref("[Last Page]","{$_SERVER['PHP_SELF']}".($pages * $amax) , 'class="menuItem"');
            }
            // add prev/next links
            echo $html->p($html->div($prev_link.' &nbsp; '.$next_link, 'align="center"'));
        }
    // end default
}

?>
