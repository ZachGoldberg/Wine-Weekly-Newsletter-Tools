<?php

/*
    WineHQ Website
    WWN (World Wine News) plugin
    by Jeremy Newman <jnewman@codeweavers.com>
*/

// import globals
global $html, $config;

// load wwn object
check_and_require("wwn");
$wwn = new wwn();

// change display mased on mode
switch ($this->params['mode'])
{
    // interviews
    case "interviews":
        // set wwn mode
        $wwn->mode = 'interviews';
        // display interviews based on page params
        switch (true)
        {
            // single issue interview
            case preg_match("/^[0-9]+$/", PAGE_PARAMS):
                // display single WWN issue
                echo $wwn->view_interview(PAGE_PARAMS);
                break;
             // end single issue

            // default view (list of interviews)
            default:
                $issues = $wwn->get_list();
                echo $wwn->issues_list($issues[0], $issues[1], 0, $_GET['pos']);
            // end default
        }
        break;
    
    // world wine news
    default:
        // display news based on page params
        switch (true)
        {
            // source view
            case preg_match("/^source\/\w+$/", PAGE_PARAMS):
                // output the source of a single issue
                $issue = preg_replace("/^source\/(\w+)$/", "\\1", PAGE_PARAMS);
                $xmlFile = $config->wwn_xml_path."/".$html->lang."/".$issue.".xml";
                if (file_exists($xmlFile))
                {
                    $html->clear_buffer().
                    header('Content-Description: File Transfer');
                    header('Content-Type: text/xml');
                    header('Content-Disposition: attachment; filename='.$issue.".xml");
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                    header('Pragma: public');
                    header('Content-Length: '.filesize($xmlFile));
                    readfile($xmlFile);
                    exit();
                }
                else
                {
                    $html->in404 = 1;
                    echo $html->template("base", "404");
                }
                break;
             // end source view

            // single issue view
            case preg_match("/^[0-9]+$/", PAGE_PARAMS):
                // display single WWN issue
                echo $wwn->view_issue(PAGE_PARAMS);
                break;
             // end single issue

            // default view (list of WWN issues)
            default:
                $issues = $wwn->get_list();
                echo $wwn->issues_list($issues[0], $issues[1], 0, $_GET['pos']);
            // end default
        }
        // end wwn param switch
}
// end mode switch

// done
?>
