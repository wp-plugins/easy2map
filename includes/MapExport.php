<link href="<?php echo easy2map_get_plugin_url('/css/bootstrap.min.css'); ?>" rel="stylesheet" media="screen">
<script src="<?php echo easy2map_get_plugin_url('/scripts/bootstrap.min.js'); ?>"></script>
<?php
if (self::easy2MapCodeValidator(get_option('easy2map-key')) === false) {
    echo '<div style="color:#70aa00;width:90%;text-align:center;margin-bottom:5px;font-weight:bold;">Please upgrade to the Ultimate Version to access this functionality</div>';
} else {
    $mapID = filter_input(INPUT_GET, 'map_id');
    global $wpdb;
    $mapsTable = $wpdb->prefix . "easy2map_maps";
    $markersTable = $wpdb->prefix . "easy2map_map_points";
    $xml_root = new SimpleXMLElement('<xml></xml>');
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $mapsTable WHERE ID = '%s';", $mapID));

    foreach ($results as $result) {
        $name = stripslashes($result->MapName);
        $map = $xml_root->addChild('map');
        $map->addChild('TemplateID', $result->TemplateID);
        $map->addChild('MapName', $name);
        $map->addChild('Settings', urlencode($result->Settings));
        $map->addChild('CSSValues', urlencode($result->CSSValues));
        $map->addChild('CSSValuesList', urlencode($result->CSSValuesList));
        $map->addChild('CSSValuesHeading', urlencode($result->CSSValuesHeading));
        $map->addChild('MapHTML', urlencode($result->MapHTML));
        $map->addChild('IsActive', urlencode($result->IsActive));
    }

    if (filter_input(INPUT_GET, 'markers')) {

        //retrieve map's markers
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $markersTable WHERE MapID = '%s';", $mapID));

        if (count($results) > 0) {
            $markers = $map->addChild('markers');
        }
        foreach ($results as $result) {
            $title = stripslashes($result->Title);
            $arrPinImage = explode("/", $result->PinImageURL);
            if (count($arrPinImage) > 0) {
                $pinImage = $arrPinImage[count($arrPinImage) - 1];
            }
            $marker = $markers->addChild('marker');
            $marker->addChild('LatLong', $result->LatLong);
            $marker->addChild('Title', $title);
            $marker->addChild('PinImage', urlencode($pinImage));
            $marker->addChild('DetailsHTML', urlencode($result->DetailsHTML));
        }
    }

    $xmlDirectory = WP_CONTENT_DIR . "/uploads/easy2map/xml/";
    $xmlURL = content_url() . "/uploads/easy2map/xml/";

    if (!file_exists($xmlDirectory)) {
        mkdir($xmlDirectory);
    }

    $xmlFileName = "Easy2Map_Export_File_" . date("YmdHis") . ".xml";
    $handle = fopen($xmlDirectory . $xmlFileName, "w+");

    if ($handle) {
        fwrite($handle, $xml_root->asXML()); //
        fclose($handle);
    }

    echo '<div class="wrap" id="bodyTag" style="width:100%;text-align:center">';
    echo '<h4 style="margin-top:30px;margin-left:auto;margin-right:auto;">';
    echo '<a href="' . $xmlURL . $xmlFileName . '">Please right-click on this link, to save your export file</a>';
    echo '<p id="mapStyleHint" style="margin-top:20px;font-size:12px;font-weight:bold;">(Right-click on link, click on "Save Link As..." and save to your computer)</p>';
    echo '</h4>';
    echo '<button onclick="window.history.back(-1);" type="button" style="margin-top:10px;width:120px;" class="btn">Back</button>';
    echo '</div>';
}
?>
