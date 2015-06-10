<?php
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )

exit ();

	global $wpdb;
	$error =  "<div id='error' class='error'><p>%s</p></div>";
	$map_table = $wpdb->prefix . "easy2map_maps";
	$map_points_table = $wpdb->prefix . "easy2map_map_points";
	$map_point_templates_table = $wpdb->prefix . "easy2map_pin_templates";
	$map_templates_table = $wpdb->prefix . "easy2map_templates";
	$map_themes_table = $wpdb->prefix . "easy2map_themes";
		
	$SQLMapPoints = "DROP TABLE `$map_points_table`";
	if (!$wpdb->query($SQLMapPoints)){
		echo sprintf($error, __("Could not drop easy2map map points table.", 'easy2map'));
		return;
	}
        
	$SQLMaps = "DROP TABLE `$map_table`";
	if (!$wpdb->query($SQLMaps)){
		echo sprintf($error, __("Could not drop easy2map map table.", 'easy2map'));
		return;
	}
        
	$SQLMapPointTemplates = "DROP TABLE `$map_point_templates_table`";
	if (!$wpdb->query($SQLMapPointTemplates)){
		echo sprintf($error, __("Could not drop easy2map map point templates table.", 'easy2map'));
		return;
	}
        
	$SQLMapTemplates = "DROP TABLE `$map_templates_table`";
	if (!$wpdb->query($SQLMapTemplates)){
		echo sprintf($error, __("Could not drop easy2map map templates table.", 'easy2map'));
		return;
	}

	$SQLMapThemes = "DROP TABLE `$map_themes_table`";
	if (!$wpdb->query($SQLMapThemes)){
		echo sprintf($error, __("Could not drop easy2map map themes table.", 'easy2map'));
		return;
	}
        

?>