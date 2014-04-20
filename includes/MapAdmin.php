<link href="<?php echo easy2map_get_plugin_url('/css/bootstrap.min.css'); ?>" rel="stylesheet" media="screen">
<link href="<?php echo easy2map_get_plugin_url('/css/bootstrap-wysihtml5.css'); ?>" rel="stylesheet" media="screen">
<script src="http://maps.google.com/maps/api/js?sensor=false&libraries=drawing,places"></script>
<script src="<?php echo easy2map_get_plugin_url('/scripts/common.js'); ?>"></script>
<script src="<?php echo easy2map_get_plugin_url('/scripts/jquery.json2xml.js'); ?>"></script>
<script src="<?php echo easy2map_get_plugin_url('/scripts/jquery.xml2json.js'); ?>"></script>
<script src="<?php echo easy2map_get_plugin_url('/scripts/functions.map.admin.js'); ?>"></script>
<script src="<?php echo easy2map_get_plugin_url('/scripts/functions.mappin.admin.js'); ?>"></script>
<script src="<?php echo easy2map_get_plugin_url('/scripts/bootstrap.min.js'); ?>"></script>
<script src="<?php echo easy2map_get_plugin_url('/scripts/jscolor/jscolor.js'); ?>"></script>
<script src="<?php echo easy2map_get_plugin_url('/scripts/wysihtml5-0.3.0.js'); ?>"></script>
<script src="<?php echo easy2map_get_plugin_url('/scripts/bootstrap-wysihtml5.js'); ?>"></script>
<style type="text/css">
input[type=text], input[type=password] {
	height: 28px !important;
}
#divPreview img {
	max-width: none !important;
}
td .instructions {
	font-size:14px;
	text-align:left;
	font-weight:bold;
}
td .instructions2 {
	font-size:12px;
	text-align:left;
	font-weight:bold;
}
td .highlighted {
	text-shadow:2px 2px 8px #575757;
	font-weight:bold;
}
.smallE2MLink {
	font-size:0.5em;
	text-decoration: none;
}
.btn-custom {
	background-color: hsl(88, 50%, 33%) !important;
	background-repeat: repeat-x;
 filter: progid:DXImageTransform.Microsoft.gradient(startColorstr="#86c043", endColorstr="#567e2a");
	background-image: -khtml-gradient(linear, left top, left bottom, from(#86c043), to(#567e2a));
	background-image: -moz-linear-gradient(top, #86c043, #567e2a);
	background-image: -ms-linear-gradient(top, #86c043, #567e2a);
	background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0%, #86c043), color-stop(100%, #567e2a));
	background-image: -webkit-linear-gradient(top, #86c043, #567e2a);
	background-image: -o-linear-gradient(top, #86c043, #567e2a);
	background-image: linear-gradient(#86c043, #567e2a);
	border-color: #567e2a #567e2a hsl(88, 50%, 28.5%);
	color: #fff !important;
	text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.29);
	-webkit-font-smoothing: antialiased;
}
</style>
<?php
include('CSSEditHTML.html');
$mapID = filter_input(INPUT_GET, 'map_id');
?>
<script>
    var $overlay, $styleElementIndex, $styleSelectedElement, $geocoder, $map, $mapSettings, $latlng, $arrTemplates, $mapPinID, $pinsArray = [], $markersArray = [], $selectedPin;
    var $pluginsURL = "<?php echo str_replace('index.php', '', easy2map_get_plugin_url('/index.php')); ?>";
    var $mapID = <?php echo $mapID; ?>;

    jQuery.noConflict();

    jQuery(function() {

        $geocoder = new google.maps.Geocoder();
        easy2map_map_functions.retrieveMapSettings($mapID);

        //add autocomplete to the address search textbox
        var input = document.getElementById('address');
        var autocomplete = new google.maps.places.Autocomplete(input);
        google.maps.event.addListener(autocomplete, 'place_changed', function() {

            var place = autocomplete.getPlace();
            easy2map_mappin_functions.SetPinPosition(place.geometry.location.lat(), place.geometry.location.lng());
        });

        jQuery('#pinDescription').wysihtml5({
            "font-styles": false, //Font styling, e.g. h1, h2, etc. Default true
            "emphasis": true, //Italics, bold, etc. Default true
            "lists": false, //(Un)ordered lists, e.g. Bullets, Numbers. Default true
            "link": true, //Button to insert a link. Default true'
            "html": true, //Button which allows you to edit the generated HTML. Default false
            "image": false, //Button to insert an image. Default true,
            "color": true //Button to change color of font  
        });

    });

    function showMapNameEdit() {

        jQuery('#mapName').val(jQuery('#mapName2').html()).toggle().select();
        jQuery('#mapEditPencil').toggle();
        jQuery('#mapName2').toggle();

    }

    function runMapNameEdit(e) {
        if (e.keyCode == 13) {
            document.getElementById('btnBack').focus();
            return false;
        }
    }

    function saveMapNameEdit() {

        var mapName = jQuery.trim(jQuery('#mapName').val()) == "" ? "Untitled Map" : jQuery.trim(jQuery('#mapName').val());

        jQuery('#mapName2').html(mapName).toggle();
        jQuery('#mapEditPencil').toggle();
        jQuery('#mapName').toggle();
        easy2map_map_functions.saveMapName();
    }

</script>

<div class="wrap" id="bodyTag">
  <div style="margin:auto;text-align: center;max-width:200px;" class="modal hide" id="pleaseWaitDialog" data-backdrop="static" data-keyboard="false">
    <div style="text-align:center;color:#404040;">
      <h5>Processing...</h5>
    </div>
  </div>
  <table style="width:100%;margin-bottom:10px;" cellpadding="2" cellspacing="2">
    <tr>
      <td style="width:34%;vertical-align:top;"><h3>
        <span style="cursor:pointer;" onclick="showMapNameEdit()" id="mapName2"></span> <a id="mapEditPencil" href="#" style="display:none" onclick="showMapNameEdit()" class="smallE2MLink">edit</a> 
        <!---<i id="mapEditPencil" style="cursor:pointer;margin-top:8px;" onclick="showMapNameEdit()" class="icon-pencil"></i>--->
        
        <input maxlength="128" name="mapName" onblur="saveMapNameEdit()"
                           onkeypress="return runMapNameEdit(event)"
                           id="mapName" value="" maxlength="128"
                           type="text" placeholder="Give your map a name" 
                           class="input-large" style="display:none;width:300px;margin-bottom:-6px" />
        
        <!---<?php if (!isset($_REQUEST["no_back"])) { ?>
                                                                    <button onclick="window.location='?page=easy2map&action=viewMaps'" type="button" 
                                                                            style="margin-left:30px;width:100px;" 
                                                                            class="btn">Back</button> 
                    <?php } ?>---></td>
      <td style="width:1%"></td>
      <td align="center" id="TitleMapSize" style="text-align:center;width:65%;font-size:15px;"><select id="mapSize" onchange="easy2map_map_functions.changeMapSize(jQuery(this).val())" style="width:150px">
        </select>
        <a id="btnBack" href="#" onclick="easy2map_map_functions.saveMap(true, false);" type="button" 
                   style="float:right;margin-right:5px;">Back to Map Manager</a></td>
    </tr>
  </table>
  <table style="width:100%" cellpadding="2" cellspacing="2">
    <tr>
      <td style="width:34%;vertical-align:top;"><div class="control-group" style="width:100%;">
          <p style="display:none;margin-top:-20px;" id="SaveMap">
            <button id="btnSaveAndBack" onclick="easy2map_map_functions.saveMap(true, true);" type="button" 
                                style="float:right;width:150px;"
                                class="btn btn-custom">Map Completed</button>
          </p>
          <ul class="nav nav-tabs" style="clear:both;">
            <li class="active"> <a href="#easy2maptabs-1" data-toggle="tab">Map Markers</a> </li>
            <li id='easy2maptab2'><a href="#easy2maptabs-2" data-toggle="tab">Additional Settings</a></li>
            
          </ul>
          <div class="tab-content">
            <div class="tab-pane active" id="easy2maptabs-1">
              <p style="display:none;margin-bottom:20px;" id="AddMarker"> <a href="#" onclick="easy2map_mappin_functions.addNewMapMarker()"> <img alt="easy2mapwordpress131723" src="<?php echo easy2map_get_plugin_url('/images/e2m_icon_add.png'); ?>" style="margin-right:10px;"> Add New Marker</a> </p>
              <table id="tblAddMapMarker" style="display:none;background-color:#EBEBEB;width:100%;" cellspacing="3" cellpadding="3" class="table table-bordered">
                <tr>
                  <td id="AddEditPinTitle" colspan="2" class="instructions">Add New Marker </td>
                </tr>
                <tr id="pinNameParent" style="display:none;">
                  <td colspan="2"><h5 style="margin-top:0px;">Marker's Name</h5>
                    <input maxlength="128" name="pinName"
                                               id="pinName" value=""
                                               type="text" placeholder="Enter map marker name" 
                                               class="input-large" style="width:100%;margin:0;" /></td>
                </tr>
                <tr id="pinDescriptionParent" style="display:none;">
                  <td colspan="2" style=""><h5 style="margin-top:0px;">Popup's Content</h5>
                    <textarea style="width:100%;height:100px;margin:0;" placeholder="Enter popup content here" id="pinDescription" name="pinDescription"></textarea></td>
                </tr>
                <tr id="divAddressSearch">
                  <td colspan="2" style="vertical-align:middle;"><input class="input-xlarge" 
                                               id="address" 
                                               type="text"
                                               placeholder="Enter Marker's Address" 
                                               style="width:100%;margin:0"></td>
                </tr>
                <tr id="divDrag">
                  <td align="center" colspan="2" style="vertical-align:middle;text-align:center;font-size:13px"> or drag marker onto map </td>
                </tr>
                <tr>
                  <td align="center" style="text-align:center;vertical-align:middle;"><button id="btnUploadIcon" onclick="easy2map_mappin_functions.openImagesDirectory(jQuery('#draggable').attr('src'))" type="button" 
                                                style="margin-top:auto;margin-bottom:auto;width:120px;" 
                                                class="btn">Change Icon</button></td>
                  <td align="center" style="text-align:center;vertical-align:middle;font-size:13px;" class="instructions"><img id="draganddrop" src="<?php echo easy2map_get_plugin_url('/images/draganddrop.png'); ?>" 
                                             style="float:right;"> <img id="draggable" name="draggable"
                                             style="z-index:9999;cursor:move;vertical-align:top"/></td>
                </tr>
                <tr id="divPinAddEditParent" style="display:none;">
                  <td style="text-align:left;" colspan="2"><button id="btnDeletePin" onclick="easy2map_mappin_functions.deleteSelectedPoint();" type="button" 
                                                style="float:right;display:none;margin-top:10px;width:70px;" 
                                                class="btn">Delete</button>
                    <button id="btnSavePin" onclick="easy2map_mappin_functions.saveMapPin();" type="button" 
                                                style="float:left;display:none;margin-left:5px;margin-top:10px;width:110px;" 
                                                class="btn btn-custom">Save Marker</button>
                    <button id="btnCancelPin" onclick="easy2map_mappin_functions.retrieveMapPoints();" type="button" 
                                                style="display:none;margin-left:15px;margin-top:10px;width:70px;" 
                                                class="btn">Cancel</button></td>
                </tr>
              </table>
              <div id="MarkersListHeading" style="padding-left:5px;clear:both">&nbsp;</div>
              <table id="tblMapMarkers" style="display:none;width:98%;margin-top:20px;"  class="table table-striped">
              </table>
            </div>
            <div class="tab-pane" id="easy2maptabs-2">
              <?php require_once 'SettingsEdit.php'; ?>
            </div>
            
          </div>
        </div></td>
      <td style="width:1%"></td>
      <td style="width:65%;vertical-align:top;"><div class="control-group" style="clear:both;width:100%;">
        <div id="divPreview" style="width:100%;min-height:500px;"></div></td>
    </tr>
  </table>
</div>
<form name="formAddPinIcon" 
      target="frameAddPinIcon" 
      enctype="multipart/form-data" 
      id="formAddPinIcon"
      action="?page=easy2map&action=mappinimagesave&map_id=<?php echo $mapID; ?>"
      method="post">
  <div id="mapPinIconList" style="width:600px" 
         class="modal hide fade" tabindex="-1" 
         role="dialog" aria-labelledby="winSettingsModalLabel" data-keyboard="true" aria-hidden="true">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
      <h3>My map icons</h3>
    </div>
    <div class="modal-body" style="max-height: 400px">
      <h5>Upload own icon:
        <input type='file' name='pinicon' 
                                        id='pinicon' 
                                        size='30' style="width:300px;vertical-align:middle;"
                                        acceptedFileList='JPG;JPEG;PJPEG;GIF;PNG;X-PNG'
                                        accept='image/*'>
      </h5>
      <h6><i>Valid image files accepted (.jpg, .png, .gif) with a maximum file size of 5MB.</i></h6>
      <table style="width:96%" 
                   cellpadding="4" 
                   cellspacing="4" id="tblPinImages">
      </table>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
      <button class="btn btn-primary" data-dismiss="modal" onclick="easy2map_mappin_functions.uploadPinIcon()" aria-hidden="true">Upload Icon</button>
    </div>
  </div>
</form>
<form name="formExport" 
      id="formExport"
      action="?page=easy2map&action=mapexport&map_id=<?php echo $mapID; ?>"
      method="post">
</form>
<form name="formExport2" 
      id="formExport2"
      action="?page=easy2map&markers=true&action=mapexport&map_id=<?php echo $mapID; ?>"
      method="post">
</form>
<form name="formImport" 
      id="formImport"
      action="?page=easy2map&action=mapimport&map_id=<?php echo $mapID; ?>"
      method="post">
</form>
<form name="formImport2" 
      id="formImport2"
      action="?page=easy2map&action=mapimport&markersonly=true&map_id=<?php echo $mapID; ?>"
      method="post">
</form>
<form name="formImport3" 
      id="formImport3"
      action="?page=easy2map&action=mapimportcsv&map_id=<?php echo $mapID; ?>"
      method="post">
</form>
<div id="mapShortCode" style="width:700px" 
     class="modal hide fade" tabindex="-1" 
     role="dialog" aria-labelledby="winSettingsModalLabel" data-keyboard="true" aria-hidden="true">
  <div class="modal-header">
    <h3>Your map has been saved</h3>
  </div>
  <div  class="modal-body" style="font-size:16px;max-height:400px"> Your map's short-code is&nbsp;&nbsp;:&nbsp;&nbsp;
    <input type="text" style="text-align:center;width:220px;font-size:1.1em;" id="txtShortCode">
    <br>
    <br>
    <i>(copy and paste this code into your posts or pages to display your map)</i> </div>
  <div class="modal-footer">
    <button class="btn btn-primary" data-dismiss="modal" onclick=" window.location = '?page=easy2map&action=viewmaps'" aria-hidden="true">OK</button>
  </div>
</div>
<iframe name="frameAddPinIcon" 
        id="frameAddPinIcon" 
        width="98%" height="98%" 
        frameborder="0" style="display:none;margin:auto;" 
        scrolling="auto" src=""></iframe>