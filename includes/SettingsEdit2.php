<?php

$premiumYN = self::easy2MapCodeValidator(get_option('easy2map-key'));

if ($premiumYN === false) {
    echo '<div style="color:#70aa00;width:90%;text-align:center;margin-bottom:5px;font-weight:bold;">Please upgrade to the Ultimate Version to edit settings</div>';
}

echo '<h5 style="margin-top:1em;border-top:0px solid #EBEBEB;padding-top:0.5em;">Map Export</h5>';
if (intval($mapID) > 0) {

    if ($premiumYN === false) {

    } else {
        echo '<h5><a href="#" onclick="document.formExport.submit();">Export map <u>(excluding markers)</u> to XML file</a></h5>'
        . '<h5 style="margin-top:10px;"><a href="#" onclick="document.formExport2.submit()">Export map <u>(including markers)</u> to XML file</a></h5>';
    }
} 
echo '<h5 style="margin-top:20px;border-top:1px solid #EBEBEB;padding-top:0.5em;">Map Import</h5>';

if ($premiumYN === false) {

} else {

    echo '<h5><a href="#" onclick="document.formImport.submit()">Import map &amp; markers from XML file</a></h5>';

    if (intval($mapID) > 0) {
        echo '<h5><a href="#" onclick="document.formImport2.submit()">Import markers from XML file</a></h5>';
    }
}

if ($premiumYN === false) {

} else {

    echo '<h5><a href="#" onclick="easy2map_map_functions.importFileViaCSV()">Import markers from CSV file (using latitude &amp; longitude)</a></h5>';
}

if ($premiumYN === false) {

} else {

    echo '<h5><a href="#" onclick="easy2map_map_functions.importFileViaCSV2()">Import markers from CSV file (using address)</a></h5>';
}

?>