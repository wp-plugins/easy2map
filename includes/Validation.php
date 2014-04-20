<link href="<?php echo easy2map_get_plugin_url('/css/bootstrap.min.css'); ?>" rel="stylesheet" media="screen">
<link href="<?php echo easy2map_get_plugin_url('/css/bootstrap-wysihtml5.css'); ?>" rel="stylesheet" media="screen">
<script src="<?php echo easy2map_get_plugin_url('/scripts/bootstrap.min.js'); ?>"></script>

<style type="text/css">

    input[type=text], input[type=password] {
        height: 28px !important;
    }

    td .instructions{
        font-size:14px !important;
        text-align:left;
        font-weight:bold;
    }

    .exampleImg{
        border:1px solid #CCCCCC;
    }

</style>

<div style="width:60%;margin-top:10px;margin-left:auto;margin-right:auto;text-align:right">
    <a id="btnBack" href="?page=easy2map&action=viewmaps">Back to Map Manager</a>
</div>

<form id="save-easy2map_key" name="save-easy2map_key" action="" method="post">

    <table style="background-color:#EBEBEB;width:80%;margin-top:10px;margin-left:auto;margin-right:auto;" cellspacing="3" cellpadding="3" class="table table-bordered">
        <tr>
            <td colspan="2" style="font-size:16px;text-align:center;font-weight:bold;">Build beautiful maps - unlock all the plugin features by upgrading to the Easy2Map Ultimate Version</td>
        </tr>
        <tr><td colspan="2" style="font-size:13px;text-align:left;font-weight:bold;color:#70aa00;">Ultimate Version Features:</td></tr>
        <tr><td style="width:10%;padding:8px;text-align:center;"><img src="<?php echo easy2map_get_plugin_url('/images/tick_small.png'); ?>"/></td>
            <td style="font-weight:bold;">Edit map layout, with an ever-growing list of great looking templates available</td></tr>
        <tr><td style="width:10%;padding:8px;text-align:center;"><img src="<?php echo easy2map_get_plugin_url('/images/tick_small.png'); ?>"/></td>
            <td style="font-weight:bold;">Have the option of displaying a list of markers alongside your map</td></tr>
        <tr><td style="width:10%;padding:8px;text-align:center;"><img src="<?php echo easy2map_get_plugin_url('/images/tick_small.png'); ?>"/></td>
            <td style="font-weight:bold;">Administer map type, and maps' individual style elements to your exact requirements</td></tr>
        <tr><td style="width:10%;padding:8px;text-align:center;"><img src="<?php echo easy2map_get_plugin_url('/images/tick_small.png'); ?>"/></td>
            <td style="font-weight:bold;">Easily import and export Easy2Map maps and/or markers</td></tr>
        <tr><td style="width:10%;padding:8px;text-align:center;"><img src="<?php echo easy2map_get_plugin_url('/images/tick_small.png'); ?>"/></td>
            <td style="font-weight:bold;">Administer markers' popup features, such as 'Directions' link</td></tr>

        <?php
        if (filter_input(INPUT_POST,'easy2map_key') && filter_input(INPUT_POST, 'action') && strcasecmp(filter_input(INPUT_POST, 'action'), "update_easy2mapkey") == 0) {
            if (wp_verify_nonce(filter_input(INPUT_POST,'easy2map_key'), 'update-options')) {

                if (self::easy2MapCodeValidator(filter_input(INPUT_POST,'code'))) {
                    update_option('easy2map-key', filter_input(INPUT_POST,'code'));


                    echo '<tr><td colspan="2" style="text-align:center;vertical-align:middle;height:40px;font-size:1.3em;color:#70aa00;font-weight:bold;">
                            Your version has successfully been upgraded, thank you!
                        </td></tr>';
                } else {

                    echo '<tr><td colspan="2" style="text-align:center;vertical-align:middle;height:40px;font-size:1.3em;color:red;font-weight:bold;">
                            Your version upgrade was not successful!
                        </td></tr>';
                }
            } else {
                echo '<tr><td colspan="2" style="text-align:center;vertical-align:middle;height:40px;font-size:1.3em;color:red;font-weight:bold;">
                        Update failed!
                    </td></tr>';
            }
        }

        if (self::easy2MapCodeValidator(get_option('easy2map-key')) === false) {

            echo '<tr><td colspan="2" style="text-align:center;vertical-align:middle;height:100px;">
                    <a target="_blank" href="http://easy2map.com/payment/paypal/easy2MapPro.php" style="text-decoration:underline;font-size:1.3em;color:#70aa00;font-weight:bold;">Click Here to Upgrade to the Ultimate Version for $11.99</a>
                    <img style="margin-left:30px;margin-right:auto;" src="' . easy2map_get_plugin_url('/images/paypal-verified.png') . '"></td></tr>';
        }

        echo '</table>';

        if (self::easy2MapCodeValidator(get_option('easy2map-key')) === false) {

            echo '<h5>Please Enter Your Activation Code Here:</h5>
        <input type="text" name="code" style="margin-left:auto;margin-right:auto;width:80%" value="" />
        <br>';
            wp_nonce_field('update-options', 'easy2map_key');
            echo '<input type="hidden" name="action" value="update_easy2mapkey">
        <input type="submit" class="btn btn-primary" style="margin-left:100px;width:150px" value="Activate">';
        }
        ?>
        
        <?php if (self::easy2MapCodeValidator(get_option('easy2map-key')) === false) { ?>

        <table style="background-color:#EBEBEB;margin-top:40px;margin-left:auto;margin-right:auto;width:80%;" cellspacing="3" cellpadding="3" class="table table-bordered">
            <tr>
                <td colspan="2" style="font-size:16px;text-align:center;font-weight:bold;">Take Full Control of your Maps Today with the Easy2Map Ultimate Version!</td>
            </tr>
            <tr><td colspan="2" style="text-align:center"><img class="exampleImg" src="<?php echo easy2map_get_plugin_url('/images/ultimate_img0.png'); ?>" width="800px" /></td></tr>
        </table>
        
        <?php } ?>
</form>
