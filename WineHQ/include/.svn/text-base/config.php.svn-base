<?php

/*
  WineHQ
  config file reading class
  by Jeremy Newman <jnewman@codeweavers.com>
*/

class config 
{ 
    function config ()
    {
        // get files passed
        $files = func_get_args();
    
        // loop and parse files
        foreach ($files as $path)
        {
            // read global config file
            $this->readConfig($path);
        }
    // end of config()
    }
    
    // reads config from text file
    function readConfig ($file)
    {
        if (file_exists($file))
        {
            $fd = fopen ($file, "r");
            while (!feof ($fd)) {
                $buffer = trim(fgets($fd, 4096));
                if (ereg('^\#', $buffer)) continue;
                if ($buffer == "") continue;
                $arr = preg_split('/:\s+/',$buffer,2);
                $arr[1] = preg_replace("/<br>/","\n",$arr[1]);
                if (ereg('^\@', $arr[0]))
                {
                    // array
                    $arr[0] = ereg_replace('\@', '', $arr[0]); 
                    $this->$arr[0] = preg_split('/,\s+/', $arr[1]);
                }
                else if (ereg('^\%', $arr[0]))
                {
                    // assoc array
                    $arr[0] = ereg_replace('\%', '', $arr[0]);
                    $this->$arr[0] = array();
                    $params = preg_split('/,\s+/', $arr[1]);
                    while (list($n, $m) = each($params))
                    {
                        list($key, $val) = preg_split('/\|/', $m, 2);
                        $this->$arr[0] = array_merge($this->$arr[0], array($key => $val));
                    }
                }
                else
                {
                    // string
                    if (preg_match('/\{\$[a-z0-9_]+\}/', $arr[1]))
                    {
                        // load other vars into existing var
                        $arr[1] = preg_replace('/(.*)\{\$([a-z0-9_]+)\}(.*)/', "\\1,\\2,\\3", $arr[1]);
                        list($a,$b,$c) = split(",", $arr[1], 3);
                        if ($b and isset($this->$b))
                            $arr[1] = $a.$this->$b.$c;
                        else
                            $arr[1] = $a.$c;
                    }
                    $this->$arr[0] = $arr[1];
                }
            }
            fclose ($fd);
        }
        else
        {
            trigger_error("Unable to read config: ".$file);
        }
    // end of readConfig
    }

// done
}
?>
