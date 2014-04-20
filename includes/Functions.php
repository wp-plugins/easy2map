<?php

function easy2map_get_plugin_url($fileAndLocation) {
    return plugins_url($fileAndLocation, dirname(__FILE__));
}

class Easy2Map_MapFunctions {

    public static function Delete_map($mapID) {

        global $wpdb;
        $mapPointsTable = $wpdb->prefix . "easy2map_map_points";
        $mapsTable = $wpdb->prefix . "easy2map_maps";
        $wpdb->query($wpdb->prepare("DELETE FROM $mapPointsTable WHERE MapID = '%s';", $mapID));
        $wpdb->query($wpdb->prepare("DELETE FROM $mapsTable WHERE ID = '%s';", $mapID));
    }

    public static function Retrieve_map_templates($mapID) {

        global $wpdb;
        $mapsTable = $wpdb->prefix . "easy2map_maps";
        $templatesTable = $wpdb->prefix . "easy2map_templates";
        $returnValue = array();

        $templates = $wpdb->get_results($wpdb->prepare("SELECT A.ID, 
            IFNULL(B.TemplateID,94) AS SelectedTemplate,
            A.TemplateName
            , A.ExampleImage
            , IFNULL(B.CSSValues, A.CSSValues) AS CSSValues
            , IFNULL(B.CSSValuesList, IFNULL(A.CSSValuesList,'')) AS CSSValuesList
            , IFNULL(B.CSSValuesHeading, IFNULL(A.CSSValuesHeading,'')) AS CSSValuesHeading
            , IFNULL(B.MapHTML, A.TemplateHTML) AS TemplateHTML
            , IFNULL(A.StyleParentOnly,0) AS StyleParentOnly
            FROM $templatesTable A
            LEFT JOIN $mapsTable B ON (A.ID = B.TemplateID AND B.ID = %s)
            WHERE A.Active = 1    
            ORDER BY A.DisplayOrder;", $mapID));

        foreach ($templates as $template) {

            $mapTemplate = new e2mMapTemplate($template->ID, 
                    $template->SelectedTemplate, 
                    $template->TemplateName, 
                    $template->ExampleImage, 
                    stripcslashes($template->CSSValues), 
                    stripcslashes($template->CSSValuesList), 
                    stripcslashes($template->CSSValuesHeading), 
                    stripcslashes($template->TemplateHTML), 
                    $template->StyleParentOnly);

            array_push($returnValue, $mapTemplate);
        }

        return $returnValue;
    }

    public static function Save_map_polylines($mapID, $PolyLines) {

        global $wpdb;
        $mapsTable = $wpdb->prefix . "easy2map_maps";

        $wpdb->query(sprintf("UPDATE $mapsTable
        SET PolyLines = '%s'
        WHERE ID = '%s';", $PolyLines, $mapID));

        return "";
    }
    
    public static function Save_default_pin_image($MapID, $MapPinImage) {
        global $wpdb;
        $mapTable = $wpdb->prefix . "easy2map_maps";
        $wpdb->query($wpdb->prepare("UPDATE $mapTable SET DefaultPinImage = '%s' WHERE ID = '%s';", stripcslashes($MapPinImage), $MapID));
        return $MapPinImage;
    }
    
    public static function Retrieve_map_settings($mapID) {

        global $wpdb;
        $mapTable = $wpdb->prefix . "easy2map_maps";

        if (intval($mapID) === 0) {

            $settings = new e2mMapItem("0", "0", "", 
                    str_replace('index.php', '', easy2map_get_plugin_url('/index.php')) . "images/map_pins/pins/111.png"
                    , '<settings lattitude="9.51119363015591" longitude="15.191190643725605" zoom="2" clusterpins="1" mapType="ROADMAP" width="800" height="600" backgroundColor="B52932" draggable="1" scrollWheel="1" mapTypeControl="1" mapTypeControl_style="DROPDOWN_MENU" mapTypeControl_position="TOP_RIGHT" panControl="1" panControl_position="TOP_LEFT" rotateControl="1" rotateControl_position="TOP_LEFT" scaleControl="1" scaleControl_position="TOP_LEFT" streetViewControl="1" streetViewControl_position="TOP_LEFT" zoomControl="1" zoomControl_position="TOP_LEFT" zoomControl_style="LARGE" polyline_strokecolor="000000" polyline_opacity="1.0" polyline_strokeweight="1" showMarkerTitle="0" markerNameFontSize="1.2" setMaxWidthPopups="0" maxWidthPopups="250" showDirections="0" directionsLinkTitle="Get Directions" directionsLinkFontSize="1" />', 
                    '', '', '', '');

            return $settings;
        }

        $mapSettings = $wpdb->get_results($wpdb->prepare("SELECT * 
        FROM $mapTable 
        WHERE ID = '%s';", $mapID));

        foreach ($mapSettings as $row) {

            $settings = new e2mMapItem($row->ID, $row->TemplateID, $row->MapName, $row->DefaultPinImage, $row->Settings, $row->CSSValues, $row->CSSValuesList, $row->CSSValuesHeading, $row->PolyLines);

            return $settings;
        }

        return null;
    }
    
    public static function Retrieve_map_HTML($mapID) {

        global $wpdb;
        $mapTable = $wpdb->prefix . "easy2map_maps";

        $mapHTML = $wpdb->get_results($wpdb->prepare("SELECT MapHTML 
        FROM $mapTable 
        WHERE ID = '%s';", $mapID));

        foreach ($mapHTML as $row) {
            return $row->MapHTML;
        }

        return "";
    }
    
    public static function Save_map($Items) {

        global $wpdb;
        global $current_user;
        $current_user = wp_get_current_user();
        $mapID = $Items["mapID"];
        $mapsTable = $wpdb->prefix . "easy2map_maps";

        if (intval($mapID) != 0) {

            //this is a map update
            $wpdb->query(sprintf("
                UPDATE $mapsTable
                SET TemplateID = '%s',
                    MapName = '%s',
                    Settings = '%s',
                    LastInvoked = CURRENT_TIMESTAMP,
                    CSSValues = '%s',
                    CSSValuesList = '%s',
                    CSSValuesHeading = '%s',
                    MapHTML = '%s',
                    IsActive = 1
                WHERE ID = %s;", 
                    $Items['mapTemplateName'], 
                    $Items['mapName'], 
                    urldecode($Items['mapSettingsXML']), 
                    urldecode($Items["mapCSSXML"]), 
                    urldecode($Items["listCSSXML"]), 
                    urldecode($Items["headingCSSXML"]), 
                    urldecode($Items["mapHTML"]), $mapID));
        } else {

            //this is a map insert
            if (!$wpdb->query(sprintf("
            INSERT INTO $mapsTable(
                TemplateID,
                MapName,
                DefaultPinImage,
                Settings,
                LastInvoked,
                PolyLines,
                CSSValues,
                CSSValuesList,
                CSSValuesHeading,
                MapHTML,
                IsActive
            ) VALUES ('%s', '%s', '%s', '%s', 
                    CURRENT_TIMESTAMP, '%s', '%s', '%s', '%s', '%s', 0);", 
                    $Items['mapTemplateName'], 
                    $Items['mapName'], str_replace('index.php', '', easy2map_get_plugin_url('/index.php')) . "images/map_pins/pins/111.png", 
                    urldecode($Items['mapSettingsXML']), '', 
                    urldecode($Items["mapCSSXML"]), 
                    urldecode($Items["listCSSXML"]), 
                    urldecode($Items["headingCSSXML"]), 
                    urldecode($Items["mapHTML"])))) {
                die("Error!");
            }

            $newRow = $wpdb->get_results("SELECT LAST_INSERT_ID() AS NewMapID;");

            foreach ($newRow as $row) {

                $mapID = $row->NewMapID;

                $imagesDirectory = WP_CONTENT_DIR . "/uploads/";

                if (!is_dir($imagesDirectory)) {
                    mkdir($imagesDirectory);
                }

                $imagesDirectory = WP_CONTENT_DIR . "/uploads/easy2map/";

                if (!is_dir($imagesDirectory)) {
                    mkdir($imagesDirectory);
                }

                $imagesDirectory = WP_CONTENT_DIR . "/uploads/easy2map/images/";

                if (!is_dir($imagesDirectory)) {
                    mkdir($imagesDirectory);
                }

                $imagesDirectory = WP_CONTENT_DIR . "/uploads/easy2map/images/map_pins/";

                if (!is_dir($imagesDirectory)) {
                    mkdir($imagesDirectory);
                }

                $imagesDirectory = WP_CONTENT_DIR . "/uploads/easy2map/images/map_pins/uploaded/";

                if (!is_dir($imagesDirectory)) {
                    mkdir($imagesDirectory);
                }

                $imagesDirectory = WP_CONTENT_DIR . "/uploads/easy2map/images/map_pins/uploaded/" . $mapID . "/";

                if (!is_dir($imagesDirectory)) {
                    mkdir($imagesDirectory);
                }
            }
        }

        return $mapID;
    }
    
    public static function Save_map_name($mapID, $mapName) {

        global $wpdb;
        $mapsTable = $wpdb->prefix . "easy2map_maps";

        $wpdb->query(sprintf("
            UPDATE $mapsTable
            SET MapName = '%s',
            LastInvoked = CURRENT_TIMESTAMP,
            IsActive = 1
            WHERE ID = %s;", $mapName, $mapID));

        return $mapID;
    }

}

class Easy2Map_MapPinFunctions {

    public static function Retrieve_map_pins($mapID) {

        global $wpdb;
        $mapPointsTable = $wpdb->prefix . "easy2map_map_points";
        $returnValue = array();

        $mapPins = $wpdb->get_results($wpdb->prepare("SELECT * 
        FROM $mapPointsTable 
        WHERE MapID = '%s' 
        ORDER BY Title;", $mapID));

        foreach ($mapPins as $mapPin) {
            $mapPoint = new e2mMatchedPoint($mapPin->ID, $mapPin->LatLong, $mapPin->Title, $mapPin->PinImageURL, stripcslashes($mapPin->Settings), stripcslashes($mapPin->DetailsHTML));
            array_push($returnValue, $mapPoint);
        }
        return $returnValue;
    }

    public static function Delete_map_point($mapPointID) {
        global $wpdb;
        $mapPointsTable = $wpdb->prefix . "easy2map_map_points";
        $wpdb->query($wpdb->prepare("DELETE FROM $mapPointsTable WHERE ID = '%s';", $mapPointID));
        return "";
    }
    
    public static function Retrieve_mappin_templates() {

        global $wpdb;
        $templatesTable = $wpdb->prefix . "easy2map_pin_templates";
        $returnValue = array();

        $templates = $wpdb->get_results("SELECT * FROM $templatesTable
        ORDER BY TemplateName");

        foreach ($templates as $template) {

            $mapTemplate = new e2mMapPinTemplate($template->ID, $template->TemplateName, stripcslashes($template->TemplateHTML));

            array_push($returnValue, $mapTemplate);
        }
        return $returnValue;
    }
    
    public static function Update_map_pin_location($LatLng, $MapPointID) {

        global $wpdb;
        $mapPointsTable = $wpdb->prefix . "easy2map_map_points";

        $wpdb->query($wpdb->prepare("
        UPDATE $mapPointsTable
        SET LatLong = '%s'
        WHERE ID = %s;", $LatLng, $MapPointID));
        
        return "";
    }
    
    public static function Save_map_pin($Items) {

        global $wpdb;
        global $current_user;
        $current_user = wp_get_current_user();
        $mapPointsTable = $wpdb->prefix . "easy2map_map_points";
        $mapsTable = $wpdb->prefix . "easy2map_maps";

        if (isset($Items["mapPointID"]) && (int) $Items["mapPointID"] != 0) {

            //this is a map pin update

            $mapPointID = $Items["mapPointID"];

            if (!$wpdb->query($wpdb->prepare("
                UPDATE $mapPointsTable
                SET LatLong = '%s', PinImageURL = '%s', 
                Title = '%s', Settings = '%s', DetailsHTML = '%s'
                WHERE ID = %s;", $Items['latLong'], urldecode($Items['icon']), $Items['pinTitle'], $Items['pinSettingsXML'], urldecode($Items["pinHTML"]), $mapPointID))) {
                return 0;
            }
            
        } else {

            $wpdb->query($wpdb->prepare("
            UPDATE $mapsTable
            SET isActive = 1
            WHERE ID = %s;", $Items["mapID"]));

            //this is a map pin insert
            $wpdb->query($wpdb->prepare("
            INSERT INTO $mapPointsTable(
                MapID
                ,CreatedByUserID
                ,LatLong
                ,Title
                ,PinImageURL
                ,Settings
                ,DetailsHTML
            ) VALUES (%s, %s, '%s', '%s', 
                '%s', '%s', '%s');", $Items["mapID"], $current_user->ID, $Items['latLong'], $Items['pinTitle'], $Items['icon'], $Items['pinSettingsXML'], $Items["pinHTML"]));

            $mapPointID = $wpdb->insert_id;
        }
        return $mapPointID;
    }

}

class Easy2Map_AJAXFunctions {

    public static function Delete_map_point_callback() {
        die(json_encode(Easy2Map_MapPinFunctions::Delete_map_point(filter_input(INPUT_POST, "MapPointID"))));
    }

    public static function Retrieve_map_pins_callback() {
        die(json_encode(Easy2Map_MapPinFunctions::Retrieve_map_pins(filter_input(INPUT_POST, "MapID"))));
    }

    public static function Save_map_polylines_callback() {
        die(json_encode(Easy2Map_MapFunctions::Save_map_polylines(filter_input(INPUT_POST, "mapID"), urldecode(filter_input(INPUT_POST, 'PolyLines')))));
    }

    public static function Save_default_pin_image_callback() {
        die(json_encode(Easy2Map_MapFunctions::Save_default_pin_image(filter_input(INPUT_POST, "MapID"), urldecode(filter_input(INPUT_POST, 'PinImage')))));
    }

    public static function Save_map_pin() {
        die(json_encode(Easy2Map_MapPinFunctions::Save_map_pin($_REQUEST)));
    }

    public static function Update_map_pin_location() {
        die(json_encode(Easy2Map_MapPinFunctions::Update_map_pin_location(filter_input(INPUT_POST, "latLong"), filter_input(INPUT_POST, 'mapPointID'))));
    }

    public static function Save_map() {
        die(json_encode(Easy2Map_MapFunctions::Save_map($_REQUEST)));
    }

    public static function Save_map_name() {
         die(json_encode(Easy2Map_MapFunctions::Save_map_name(filter_input(INPUT_POST, "mapID"), filter_input(INPUT_POST, 'mapName'))));
    }

    public static function Retrieve_pin_icons_callback() {

        $arrImages = array();

        //retrieve map pins
        $dirPins = EASY2MAP_PLUGIN_DIR . "/images/map_pins/pins/";
        $urlPins = plugins_url('/images/map_pins/pins/', dirname(__FILE__) . '/');

        if ($handlePins = opendir($dirPins)) {

            // iterate over the directory entries
            while (false !== ($entry = readdir($handlePins))) {

                // match on .php extension
                if (preg_match("/\.png$/", $entry)) {
                    array_push($arrImages, $urlPins . $entry);
                }

                if (preg_match("/\.jpg$/", $entry)) {
                    array_push($arrImages, $urlPins . $entry);
                }

                if (preg_match("/\.gif$/", $entry)) {
                    array_push($arrImages, $urlPins . $entry);
                }
            }

            // close the directory
            closedir($handlePins);
        }

        //retrieve uploaded files
        if ((int) $_REQUEST['mapID'] != 0) {
            $dirUploaded = WP_CONTENT_DIR . "/uploads/easy2map/images/map_pins/uploaded/" . $_REQUEST['mapID'] . "/";
            $urlUploaded = WP_CONTENT_URL . "/uploads/easy2map/images/map_pins/uploaded/" . $_REQUEST['mapID'] . "/";

            if ($handleUploaded = opendir($dirUploaded)) {

                // iterate over the directory entries
                while (false !== ($entry = readdir($handleUploaded))) {

                    // match on .php extension
                    if (preg_match("/\.png$/", $entry)) {
                        array_push($arrImages, $urlUploaded . $entry);
                    }

                    if (preg_match("/\.jpg$/", $entry)) {
                        array_push($arrImages, $urlUploaded . $entry);
                    }

                    if (preg_match("/\.gif$/", $entry)) {
                        array_push($arrImages, $urlUploaded . $entry);
                    }
                }

                // close the directory
                closedir($handleUploaded);
            }
        }

        echo json_encode($arrImages);
        die;
    }

    public static function Retrieve_map_settings_callback() {
         die(json_encode(Easy2Map_MapFunctions::Retrieve_map_settings($_REQUEST["mapID"])));
    }

    public static function Retrieve_map_templates_callback() {
        die(json_encode(Easy2Map_MapFunctions::Retrieve_map_templates($_REQUEST["mapID"])));
    }

    public static function Retrieve_mappin_templates_callback() {
        die(json_encode(Easy2Map_MapPinFunctions::Retrieve_mappin_templates()));
    }
	
	public static function Get_csv_coords(){
		if ($_FILES[csv][size] > 0) { 

    //get the csv file 
    $file = $_FILES[csv][tmp_name]; 
    $handle = fopen($file,"r"); 
     
    //loop through the csv file and insert into database 
    do { 
        if ($data[0]) { 
            mysql_query("INSERT INTO wp_easy2map_map_points (MapID, LatLong, Title, PinImageURL, DetailsHTML) VALUES 
                ( 
                    '".addslashes($data[0])."', 
                    '".addslashes($data[1])."', 
                    '".addslashes($data[2])."' 
                ) 
            "); 
        } 
    } while ($data = fgetcsv($handle,1000,",","'")); 
    // 

    //redirect 
    header('Location: import.php?success=1'); die; 

} 

		}

}

class e2mMapItem {

    public $ID;
    public $TemplateID;
    public $MapName;
    public $DefaultPinImage;
    public $Settings;
    public $CSSValues;
    public $CSSValuesList;
    public $CSSValuesHeading;
    public $PolyLines;

    public function __construct($ID, $TemplateID, $MapName, $DefaultPinImage, $Settings, $CSSValues, $CSSValuesList, $CSSValuesHeading, $PolyLines) {
        $this->ID = $ID;
        $this->TemplateID = $TemplateID;
        $this->MapName = $MapName;
        $this->DefaultPinImage = $DefaultPinImage;
        $this->Settings = $Settings;
        $this->CSSValues = $CSSValues;
        $this->CSSValuesList = $CSSValuesList;
        $this->CSSValuesHeading = $CSSValuesHeading;
        $this->PolyLines = $PolyLines;
    }

}

class e2mMapTemplate {

    public $ID;
    public $SelectedTemplate;
    public $TemplateName;
    public $ExampleImage;
    public $CSSValues;
    public $CSSValuesList;
    public $CSSValuesHeading;
    public $TemplateHTML;
    public $StyleParentOnly;

    public function __construct($ID, $SelectedTemplate, $TemplateName, $ExampleImage, $CSSValues, $CSSValuesList, $CSSValuesHeading, $TemplateHTML, $StyleParentOnly) {

        $this->ID = $ID;
        $this->SelectedTemplate = $SelectedTemplate;
        $this->TemplateName = $TemplateName;
        $this->ExampleImage = $ExampleImage;
        $this->CSSValues = $CSSValues;
        $this->CSSValuesList = $CSSValuesList;
        $this->CSSValuesHeading = $CSSValuesHeading;
        $this->TemplateHTML = $TemplateHTML;
        $this->StyleParentOnly = $StyleParentOnly;
    }

}

class e2mMapPinTemplate {

    public $ID;
    public $TemplateName;
    public $TemplateHTML;

    public function __construct($ID, $TemplateName, $TemplateHTML) {

        $this->ID = $ID;
        $this->TemplateName = $TemplateName;
        $this->TemplateHTML = $TemplateHTML;
    }

}

class e2mMatchedPoint {

    public $ID;
    public $LatLong;
    public $Title;
    public $ImageURL;
    public $Settings;
    public $MapPinHTML;

    public function __construct($ID, $LatLong, $Title, $ImageURL, $Settings, $MapPinHTML) {

        $this->ID = $ID;
        $this->LatLong = $LatLong;
        $this->Title = $Title;
        $this->ImageURL = $ImageURL;
        $this->Settings = $Settings;
        $this->MapPinHTML = $MapPinHTML;
    }

}

?>
