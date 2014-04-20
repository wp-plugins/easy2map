<link href="<?php echo easy2map_get_plugin_url('/css/bootstrap.min.css'); ?>" rel="stylesheet" media="screen">
<script src="<?php echo easy2map_get_plugin_url('/scripts/bootstrap.min.js'); ?>"></script>
<script src="<?php echo easy2map_get_plugin_url('/scripts/functions.map.admin.js'); ?>"></script>
<script src="<?php echo easy2map_get_plugin_url('/scripts/common.js'); ?>"></script>
<?php
if (self::easy2MapCodeValidator(get_option('easy2map-key')) === false) {
    die('<div style="color:#70aa00;width:90%;text-align:center;margin-bottom:5px;font-weight:bold;">Please upgrade to the Ultimate Version to access this functionality</div>');
}
$mapID = filter_input(INPUT_GET, 'map_id');
global $wpdb;
global $current_user;
$current_user = wp_get_current_user();
$mapsTable = $wpdb->prefix . "easy2map_maps";
$markersTable = $wpdb->prefix . "easy2map_map_points";

if (is_uploaded_file($_FILES["xmlfile"]['tmp_name'])) {

    try {

        //convert XML document into object
        $xmlObject = simplexml_load_string(file_get_contents($_FILES["xmlfile"]['tmp_name']));
        
        if (isset($xmlObject->map)) {

            //only import map settings if required
            if (!filter_input(INPUT_GET, 'markersonly')) {

                if (intval($mapID) === 0) {

                    //insert map & settings
                    $SQL = $wpdb->prepare("INSERT INTO $mapsTable(
                        TemplateID,
                        MapName,
                        DefaultPinImage,
                        Settings,
                        LastInvoked,
                        CSSValues,
                        CSSValuesList,
                        CSSValuesHeading,
                        MapHTML,
                        IsActive
                    ) 
                    VALUES ('%s', '%s', '%s', '%s', CURRENT_TIMESTAMP, 
                    '%s', '%s', '%s', '%s', 0);", 
                            $xmlObject->map->TemplateID, 
                            $xmlObject->map->MapName, 
                            str_replace('index.php', '', 
                                    easy2map_get_plugin_url('/index.php')) . "images/map_pins/pins/111.png", 
                            urldecode($xmlObject->map->Settings), 
                            urldecode($xmlObject->map->CSSValues), 
                            urldecode($xmlObject->map->CSSValuesList),
                            urldecode($xmlObject->map->CSSValuesHeading), 
                            urldecode($xmlObject->map->MapHTML));

                    if (!$wpdb->query($SQL)) {
                        die("Error!");
                    }

                    $newRow = $wpdb->get_results("SELECT LAST_INSERT_ID() AS NewMapID;");

                    //retrieve new MapID
                    foreach ($newRow as $row) {
                        $mapID = $row->NewMapID;
                    }
                    
                } else {

                    //update map and settings
                    $SQL = $wpdb->prepare("
                    UPDATE $mapsTable
                        SET MapName = '%s',
                        TemplateID = '%s',
                        Settings = '%s',
                        CSSValues = '%s',
                        MapHTML = '%s',
                        LastInvoked = CURRENT_TIMESTAMP
                    WHERE ID = %s;", $xmlObject->map->MapName, $xmlObject->map->TemplateID, urldecode($xmlObject->map->Settings), urldecode($xmlObject->map->CSSValues), urldecode($xmlObject->map->MapHTML), $mapID);

                    //update map settings
                    $wpdb->query($SQL);
                }
            }

            //import map markers if applicable
            if (isset($xmlObject->map->markers->marker) && count($xmlObject->map->markers->marker) > 0) {

                foreach ($xmlObject->map->markers->marker as $marker) {

                    $pinImage = str_replace('index.php', '', easy2map_get_plugin_url('/index.php')) . "images/map_pins/pins/111.png";

                    if (file_is_valid_image(str_replace('index.php', '', easy2map_get_plugin_url('/index.php')) . "images/map_pins/pins/" . $marker->PinImage)) {
                        $pinImage = str_replace('index.php', '', easy2map_get_plugin_url('/index.php')) . "images/map_pins/pins/" . $marker->PinImage;
                    }

                    $SQL = $wpdb->prepare("
                    INSERT INTO $markersTable (MapID,
                    CreatedByUserID,
                    LatLong,
                    Title,
                    PinImageURL,
                    DetailsHTML)
                    VALUES (%s, '%s', '%s', '%s', '%s', '%s');", 
                            $mapID, $current_user->ID, 
                            $marker->LatLong, 
                            $marker->Title, 
                            $pinImage, urldecode(urldecode($marker->DetailsHTML)));

                    $wpdb->query($SQL);
                }
            }
        }

        echo '<script> jQuery(function() { window.location = "?page=easy2map&action=edit&map_id=' . $mapID . '";});</script>';
    } catch (Exception $e) {
        echo "File could not be imported successfully. " . $e->getMessage();
    }
}
?>

<div class="wrap" id="bodyTag" style='width:100%;text-align:center'>

    <form name="formImport" 
          enctype="multipart/form-data" 
          id="formImport"
          <?php if (!filter_input(INPUT_GET, 'markersonly')) { ?>
          action="?page=easy2map&action=mapimport&map_id=<?php echo $mapID; ?>"
          <?php } else { ?>
          action="?page=easy2map&action=mapimport&markersonly=true&map_id=<?php echo $mapID; ?>"
          <?php } ?>
          method="post">

        <table style="background-color:#EBEBEB;width:60%;margin-left:auto;margin-right:auto;margin-top:10px;" cellspacing="3" cellpadding="3" class="table table-bordered">
            <tr>
                <td class="instructions"><h5>Import Map &amp;/or Markers</h5>
                </td>
            </tr>

            <tr><td align="center" style="text-align:center">

                    <h5><input type='file' name='xmlfile' 
                               id='xmlfile' 
                               size='30' style="width:300px;vertical-align:middle;"
                               acceptedFileList='XML'
                               accept='xml/*'></h5>
                    <h6><i>(Only Valid Easy2Map Export Files Accepted)</i></h6>
                    <button style="margin-top:20px;margin-left:auto;" class="btn btn-primary" data-dismiss="modal" 
                            onclick="easy2map_map_functions.uploadImportFile()" aria-hidden="true">Upload MapExport File</button>
                    <button onclick="window.history.back(-1);" type="button" 
                            style="margin-top:20px;width:120px;float:right" class="btn">Back</button>
                </td></tr>
        </table>

    </form>

</div>

