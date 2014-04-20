<?php

$premiumYN = self::easy2MapCodeValidator(get_option('easy2map-key'));

if ($premiumYN === false) {
    echo '<div style="color:#70aa00;width:90%;text-align:center;margin-bottom:5px;font-weight:bold;">Please upgrade to the Ultimate Version to edit settings</div>';
}

echo '<h5 style="margin-top:20px">Map Type</h5>'
 . '<select proVersion="';
echo $premiumYN . '" onclick="easy2map_map_functions.changeMapType();" size="1" 
id="mapType" name="mapType" style="font-size:12px;width:300px;"';
if ($premiumYN === false) {
    echo ' disabled="disabled" ';
}
echo '><option value="HYBRID">HYBRID</option><option selected="selected" value="ROADMAP">ROADMAP</option><option value="SATELLITE">SATELLITE</option><option value="TERRAIN">TERRAIN</option></select>';

echo '<h5 style="margin-top:20px;border-top:1px solid #EBEBEB;padding-top:0.5em;">Map Template</h5>'
 . '<select onclick="easy2map_map_functions.changeMapTemplate()" size="8" 
                                    id="MapTemplateName" name="MapTemplateName" 
                                    style="display:block;font-size:12px;width:315px"';
if ($premiumYN === false) {
    echo ' disabled="disabled"';
}
echo '></select>'
 . '<h5 style="margin-top:20px;border-top:1px solid #EBEBEB;padding-top:0.5em;">Map Style</h5>'
 . '<ul class="nav nav-pills"><li class="active"><a href="#MapTemplateCSS" data-toggle="tab">Map</a></li><li><a href="#MapTemplateListCSS" data-toggle="tab">Markers List</a></li><li><a href="#MapTemplateHeadingCSS" data-toggle="tab">Map Heading</a></li></ul>'
 . '<div class="tab-content"><div class="tab-pane active" id="MapTemplateCSS"></div><div class="tab-pane" id="MapTemplateListCSS"></div><div class="tab-pane" id="MapTemplateHeadingCSS"></div></div>';

echo '<h5 style="margin-top:20px;border-top:1px solid #EBEBEB;padding-top:0.5em;">Map Export</h5>';
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

    echo '<h5><a href="#" onclick="easy2map_map_functions.importFileViaCSV()">Import markers from CSV file</a></h5>';
}

echo '<h5 style="margin-top:20px;border-top:1px solid #EBEBEB;padding-top:0.5em;">Marker Popup Settings</h5>'
 . '<ul class="nav nav-pills">
    <li class="active"><a href="#InfoWindow" data-toggle="tab">Popup Window</a></li>
    <li><a href="#Directions" data-toggle="tab">Directions Link</a></li>
    </ul>
    <div class="tab-content">
    <div class="tab-pane active" style="" id="InfoWindow">
        <h6>
            <input type="checkbox" onclick="easy2map_map_functions.changeMarkerPopups()" 
                        id="markerNameInPopups" name="markerNameInPopups"';
if ($premiumYN === false) {
    echo ' disabled="disabled"';
}
echo ' />&nbsp;&nbsp;Display <span style="font-size:1.2em"> marker\'s name </span> in popup windows
         </h6>    
        <h6>Font size:
             <select onclick="easy2map_map_functions.changeMarkerPopups()" 
            id="markerNameFontSize" name="markerNameFontSize" 
            style="font-size:12px;width:80px"';
if ($premiumYN === false) {
    echo ' disabled="disabled"';
}
echo '>';
for ($j = 0.4; $j <= 4; $j += 0.2) {
    echo '<option value="' . $j . '">' . number_format($j, 2) . '</option>';
}
echo '</select> em</h6>';
echo '<h6 style="margin-top:18px;"><input type="checkbox" onclick="easy2map_map_functions.changeMarkerPopups()" 
                        id="setMaxWidthPopups" name="setMaxWidthPopups"';
if ($premiumYN === false) {
    echo ' disabled="disabled"';
}
echo ' />&nbsp;&nbsp;Set maximum width of popup windows</h6>
            <h6>Max width:     
             <select onclick="easy2map_map_functions.changeMarkerPopups()"
                        id="maxWidthPopups" name="maxWidthPopups" 
                        style="font-size:12px;width:80px"';
if ($premiumYN === false) {
    echo ' disabled="disabled"';
}
echo '>';
for ($i = 200; $i <= 600; $i += 25) {
    echo '<option value="' . $i . '">' . $i . '</option>';
}
echo '</select> pixels</h6></div>';

echo '<div class="tab-pane" style="" id="Directions">
            <h6><input type="checkbox" onclick="easy2map_map_functions.changeMarkerPopups()" 
            id="directionsInPopups" name="directionsInPopups"';
if ($premiumYN === false) {
    echo ' disabled="disabled"';
}
echo ' />&nbsp;&nbsp;Display <span style="font-size:1.2em"> \'Get Directions\' </span> link in popups</h6>

            <h6 style="margin-top:12px;">Link text:</h6>

            <input type="text" onblur="easy2map_map_functions.changeMarkerPopups()" 
                                                id="directionsLinkTitle" name="directionsLinkTitle" 
                                                style="font-size:12px;width:300px"';
if ($premiumYN === false) {
    echo ' disabled="disabled"';
}
echo '/>

            <h6 style="margin-top:12px;">Font size:

             <select onclick="easy2map_map_functions.changeMarkerPopups()"
                                                id="directionsLinkFontSize" name="directionsLinkFontSize" 
                                                style="font-size:12px;width:60px"';
if ($premiumYN === false) {
    echo ' disabled="disabled"';
}
echo '>';

for ($i = 0.4; $i <= 4; $i += 0.2) {
    echo '<option value="' . $i . '">' . number_format($i, 2) . '</option>';
}
echo '</select> em</h6>
</div>
</div>';
?>