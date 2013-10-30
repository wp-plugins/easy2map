<?php
include 'ImageFunctions.php';

$imagesDirectory = WP_CONTENT_DIR . "/uploads/easy2map/images/map_pins/uploaded/" . $_GET["map_id"] . "/";
echo $imagesDirectory;
$imagePlusLocation = "";
$errorMessage = "";

try{

if (is_uploaded_file($_FILES["pinicon"]['tmp_name'])) {

    if (!file_exists($imagesDirectory)) {
        mkdir($imagesDirectory);
    }

    $imageName = $_FILES["pinicon"]['name'];
    $uploadedFile = $_FILES["pinicon"]['tmp_name'];
    $extension = strtolower(getExtension($imageName));

    list($width, $height, $type, $attr) = getimagesize($uploadedFile);
    $uploadedImageLocation = $imagesDirectory . $imageName;
    $imageNameExplode = explode(".", $imageName);
    
    
    if ($_FILES["pinicon"]['size'] < 5000000) {
        $arrSmallImage = resizeImage($imagesDirectory, $uploadedFile, $imageName, $width, $height, $type, $attr, '50', '50', "SMALL");
        $imagePlusLocation = WP_CONTENT_URL . "/uploads/easy2map/images/map_pins/uploaded/" . $_GET["map_id"] . "/" . $arrSmallImage[0];
        
    }
}
} catch(Exception $e){
    $errorMessage = $e->getMessage();
}
?>

<script type="text/javascript">

    window.onload = function(){
        
        <?php if (strlen($errorMessage) > 0) { ?>
                alert('<?php echo str_replace("'", "", $errorMessage); ?>');
        <?php } else { ?>
        window.parent.jQuery('#divUploadPinIcon').fadeOut();
        window.parent.jQuery('#draggable').attr('src', '<?php echo $imagePlusLocation; ?>');
        window.parent.easy2map_mappin_functions.setMapPinImage(parent.window.document.getElementById('draggable'));      
        <?php } ?>
    };

</script>
