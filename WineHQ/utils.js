/*
  Misc JavaScipt for WineHQ
*/

// global document operations
$(document).ready(function()
{
    // fix PNG images for IE6
    if ($.browser.msie && $.browser.version == "6.0")
    {
        $.getScript("jquery.pngfix.js",function()
        {
            $("img[@src$=png]").pngfix(); /* all img tags with .png extension */
            $("#tabs li").pngfix(); /* top tabs li backgrounds */
        });
    }
});

// done
