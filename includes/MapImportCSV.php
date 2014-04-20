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
$uploaded = false;

//is there a file uploaded
if (is_uploaded_file($_FILES["csvfile"]['tmp_name'])) {

    $file = $_FILES["csvfile"]['tmp_name'];
    $handle = fopen($file, "r");
    $row = 0;

    //can the file be opened correctly?
    if (!empty($handle)) {

        $uploaded = true;

        //loop over file rows
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

            //should be 5 columns in each row
            $num = count($data);

            if ($num == 5) {
                $lat = trim($data[0]);
                $lng = trim($data[1]);
                $title = trim(urldecode($data[2]));
                $pinIcon = trim($data[3]);
                $pinHTML = trim(urldecode($data[4]));

                if (is_numeric($lat) && is_numeric($lng)) {

                    $pinImage = str_replace('index.php', '', easy2map_get_plugin_url('/index.php')) . "images/map_pins/pins/111.png";

                    if (file_is_valid_image(str_replace('index.php', '', easy2map_get_plugin_url('/index.php')) . "images/map_pins/pins/" . $pinIcon)) {
                        $pinImage = str_replace('index.php', '', easy2map_get_plugin_url('/index.php')) . "images/map_pins/pins/" . $pinIcon;
                    }

                    $SQL = $wpdb->prepare("
                    INSERT INTO $markersTable (MapID,
                    CreatedByUserID,
                    LatLong,
                    Title,
                    PinImageURL,
                    DetailsHTML)
                    VALUES (%s, '%s', '%s', '%s', '%s', '%s');", $mapID, $current_user->ID, "(" . $lat . "," . $lng . ")", $title, $pinImage, $pinHTML);

                    $wpdb->query($SQL);
                    $row++;
                }
            }
        }
        fclose($handle);
        echo '<script> jQuery(function() { window.location = "?page=easy2map&action=edit&map_id=' . $mapID . '";});</script>';
    }
}
?>

<div class="wrap" id="bodyTag" style='width:100%;text-align:center'>

    <form name="formImport3" enctype="multipart/form-data" id="formImport3"
          action="?page=easy2map&action=mapimportcsv&map_id=<?php echo $mapID; ?>"
          method="post">

        <table style="background-color:#EBEBEB;width:60%;margin-left:auto;margin-right:auto;margin-top:10px;" cellspacing="3" cellpadding="3" class="table table-bordered">
            <tr>
                <td class="instructions"><h5>Import Markers via .CSV</h5>
                </td>
            </tr>

            <tr><td align="center" style="text-align:center">

                    <h5><input type='file' name='csvfile' 
                               id='csvfile' 
                               size='30' style="width:300px;vertical-align:middle;"
                               acceptedFileList='CSV'
                               accept='csv/*'></h5>
                    <h6><i>(Only Valid .CSV Files Accepted)</i></h6>
                    <button style="margin-top:20px;margin-left:auto;" class="btn btn-primary" data-dismiss="modal" 
                            onclick="easy2map_map_functions.uploadImportCSV()" aria-hidden="true">Upload CSV File</button>
                    <button onclick="window.history.back(-1);" type="button" 
                            style="margin-top:20px;width:120px;float:right" class="btn">Back</button>
                </td></tr>
        </table>

        <table style="font-size:11px;background-color:#FFFFFF;width:60%;margin-left:auto;margin-right:auto;margin-top:30px;" cellspacing="3" cellpadding="3" class="table table-bordered">

            <tr>
                <th>Please upload .CSV files in the following format:</th>
            </tr>
            <tr><td>[marker 1 latitude],[marker 1 longitude],[marker 1 title],[marker 1 icon],[marker 1 description]<br>
            [marker 2 latitude],[marker 2 longitude],[marker 2 title],[marker 2 icon],[marker 2 description]</td></tr>
            <tr>

                <td style="margin-top:20px;">

                    <h5>Important to note:</h5>
                    <ul>
                        <li> <b>latitude and longitude:</b> must be numeric, for example <b><i>-26.022850990407825, 28.046894073486328</i></b></li>
                        <li> <b>marker icon:</b> this can be the file name of a marker icon that has been uploaded, for example <b>CoolPinIcon.png</b><br>(leave this field empty for default icon to appear)</li>
                        <li> <b>marker description:</b> can contain HTML</li>
                    </ul>


                </td>

            </tr>
        </table>


    </form>
</div>

