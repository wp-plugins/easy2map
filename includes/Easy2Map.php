<?php

class Easy2Map {
    //PART 1 - START

    const plugin_name = 'Easy2Map';
    const min_php_version = '5.0';
    const min_wp_version = '3.0';
    const e2m_version = '1.2.6';

    // Used to uniquely identify this plugin's menu page in the WP manager
    const admin_menu_slug = 'easy2map';

    static private $url = "http://maps.google.com/maps/api/geocode/json?sensor=false&address=";

    /** Adds the necessary JavaScript and/or CSS to the pages to enable the Ajax search. */
    public static function head() {

        $src_Easy2MapAPI = "http://maps.google.com/maps/api/js?sensor=false";
        $src_Easy2Map = plugins_url('scripts/easy2map.js', dirname(__FILE__));
        $src_Xml2json = plugins_url('scripts/jquery.xml2json.js', dirname(__FILE__));
        //$src_Cluster = plugins_url('scripts/easy2map.cluster.js', dirname(__FILE__));

        wp_register_script('easy2map_js_api', $src_Easy2MapAPI);
        wp_register_script('easy2map_js_easy2map', $src_Easy2Map);
        wp_register_script('easy2map_js_Xml2json', $src_Xml2json);
        //wp_register_script('easy2map_js_cluster', $src_Cluster);

        wp_enqueue_script('easy2map_js_api');
        wp_enqueue_script('easy2map_js_easy2map');
        wp_enqueue_script('easy2map_js_Xml2json');

        //wp_enqueue_script('easy2map_js_cluster');
    }

    /** The main function for this plugin, similar to __construct() */
    public static function initialize() {

        Easy2MapTest::min_php_version(self::min_php_version);
        Easy2MapTest::min_wordpress_version(self::min_wp_version);

        wp_enqueue_script('jquery'); // make sure jQuery is loaded!
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');

        remove_action('admin_init', 'wp_auth_check_load');

    }

    /**     * Register the shortcodes used */
    public static function register_shortcodes() {
        add_shortcode('easy2map', 'Easy2Map::retrieve_map');
    }

    public static function create_admin_tables() {

        global $wpdb;
        $error = "<div id='error' class='error'><p>%s</p></div>";
        $map_table = $wpdb->prefix . "easy2map_maps";
        $map_points_table = $wpdb->prefix . "easy2map_map_points";
        $map_point_templates_table = $wpdb->prefix . "easy2map_pin_templates";
        $map_templates_table = $wpdb->prefix . "easy2map_templates";
        $map_themes_table = $wpdb->prefix . "easy2map_themes";

        $result = $wpdb->get_var("show tables like '$map_table'");

        if (strtolower($result) != strtolower($map_table)) {

            $SQL = "CREATE TABLE `$map_table` (
            `ID` bigint(20) NOT NULL AUTO_INCREMENT,
            `TemplateID` bigint(20) DEFAULT NULL,
            `MapName` varchar(256) DEFAULT NULL,
            `MapTitle` varchar(512) DEFAULT NULL,
            `DefaultPinImage` varchar(256) DEFAULT NULL,
            `Settings` text,
            `LastInvoked` datetime DEFAULT NULL,
            `PolyLines` text,
            `CSSValues` text,
            `MapHTML` text,
            `IsActive` smallint(6),
            `CSSValuesList` text,
            `CSSValuesHeading` text,
            `ThemeID` bigint(2) DEFAULT NULL,
            PRIMARY KEY (`ID`),
            UNIQUE KEY `ID_UNIQUE` (`ID`)
            ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8";

            if (!$wpdb->query($SQL)) {
                echo sprintf($error, __("Could not create easy2map maps table.", 'easy2map'));
                return;
            }
        } else {

            try {

                //convert to utf8 collation if necessary
                $collation = $wpdb->get_var("SELECT CCSA.character_set_name 
                FROM information_schema.`TABLES` T,
                information_schema.`COLLATION_CHARACTER_SET_APPLICABILITY` CCSA
                WHERE CCSA.collation_name = T.table_collation
                AND T.table_schema  = '" . DB_NAME . "'
                AND T.table_name = '$map_table' LIMIT 1;");

                if (isset($collation) && strcasecmp(strtolower($collation), "utf8") !== 0) {
                    $wpdb->query("ALTER TABLE `$map_table` CONVERT TO CHARACTER SET utf8;");
                }
            } catch (Exception $e) {
                
            }

            //does themeID column exist?
            $arrThemeIDColumnFound = $wpdb->get_results("SELECT * FROM information_schema.COLUMNS 
            WHERE TABLE_NAME = '$map_table' AND TABLE_SCHEMA = '" . DB_NAME . "' AND COLUMN_NAME = 'ThemeID';");

            //add themeID table column
            if (count($arrThemeIDColumnFound) === 0) {
                $wpdb->query("ALTER TABLE $map_table ADD ThemeID int(11) DEFAULT NULL;");
            }

        }

        $result = $wpdb->get_var("show tables like '$map_points_table'");

        if (strtolower($result) != strtolower($map_points_table)) {

            $SQL = "CREATE TABLE `$map_points_table` (
                `ID` bigint(20) NOT NULL AUTO_INCREMENT,
                `MapID` bigint(20) DEFAULT NULL,
                `CreatedByUserID` bigint(20) DEFAULT NULL,
                `LatLong` varchar(128) DEFAULT NULL,
                `Title` varchar(512) DEFAULT NULL,
                `PinImageURL` varchar(512) DEFAULT NULL,
                `Settings` varchar(512) DEFAULT NULL,
                `DetailsHTML` text,
                PRIMARY KEY (`ID`),
                UNIQUE KEY `ID_UNIQUE` (`ID`),
                KEY `wp_easy2map_map_points_MapID` (`MapID`),
                CONSTRAINT `wp_easy2map_map_points_MapID` FOREIGN KEY (`MapID`) REFERENCES `$map_table` (`ID`) ON DELETE NO ACTION ON UPDATE NO ACTION
              ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8";

            if (!$wpdb->query($SQL)) {
                echo sprintf($error, __("Could not create easy2map pins table.", 'easy2map'));
                return;
            }
        } else {

            try {

                //convert to utf8 collation if necessary
                $collation = $wpdb->get_var("SELECT CCSA.character_set_name 
                FROM information_schema.`TABLES` T,
                information_schema.`COLLATION_CHARACTER_SET_APPLICABILITY` CCSA
                WHERE CCSA.collation_name = T.table_collation
                AND T.table_schema  = '" . DB_NAME . "'
                AND T.table_name = '$map_points_table' LIMIT 1;");

                if (isset($collation) && strcasecmp(strtolower($collation), "utf8") !== 0) {
                    $wpdb->query("ALTER TABLE `$map_points_table` CONVERT TO CHARACTER SET utf8;");
                }
            } catch (Exception $e) {
                
            }
        }

        $result = $wpdb->get_var("show tables like '$map_point_templates_table'");

        if (strtolower($result) != strtolower($map_point_templates_table)) {

            $SQL = "CREATE TABLE `$map_point_templates_table` (
            `ID` int(11) NOT NULL AUTO_INCREMENT,
            `TemplateName` varchar(128) DEFAULT NULL,
            `TemplateHTML` text,
            PRIMARY KEY (`ID`),
            UNIQUE KEY `ID_UNIQUE` (`ID`)
          ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8";

            if (!$wpdb->query($SQL)) {
                echo sprintf($error, __("Could not create easy2map pin templates table.", 'easy2map'));
                return;
            }

            $SQLInsert1 = "INSERT INTO `$map_point_templates_table`
            (`TemplateName`,
            `TemplateHTML`)
            VALUES
            ('Thumbnail Image on Left', 
            '<table style=\"margin-top:8px;overflow:hidden;vertical-align:middle;width:325px;border-radius:0px;margin-left:5px;margin-right:20px;\"><tr><td style=\"vertical-align:top;padding:6px;border-style:solid;border-width:1px;vertical-align:top;border-color:#FFFFFF;\"><img style=\"border:0px solid #000000;border-radius: 0px;\" src=\"http://easy2map.com/images/css_templates/thumbnail20120424025713000000_CSS.png\" border=\"0\"></td><td style=\"width:100%;vertical-align:top;\"><p align=\"left\" style=\"border-color:#E53840;border-style:solid;border-width:1px;color:#FFFFFF;font-family:Arial, Helvetica, sans-serif;font-size:14px;font-weight:bold;padding:4px;text-align:left;text-decoration:none;vertical-align:middle;background-color:#E53840;border-radius:0px;margin:3px;\">Section 1</p><p align=\"left\" style=\"margin:3px;background-color:#009AD7;border-color:#009AD7;border-style:solid;border-width:1px;color:#FFFFFF;font-family:Arial, Helvetica, sans-serif;font-size:13px;font-weight:bold;padding:4px;text-align:left;text-decoration:none;vertical-align:top;border-radius:0px;\">Section 2</p><p align=\"left\" style=\"margin:3px;background-color:#DADADA;border-color:#DADADA;border-style:solid;border-width:1px;color:#000000;font-family:Arial, Helvetica, sans-serif;font-size:13px;font-weight:bold;padding:4px;text-align:left;text-decoration:none;vertical-align:middle;border-radius:0px;\">Section 3</p><p align=\"left\" style=\"margin:3px;background-color:#FFFFFF;border-color:#EBEBEB;border-style:solid;border-width:1px;color:#000000;font-size:12px;font-weight:normal;padding:4px;text-align:left;border-radius:0px;\">Section 4</p></td></tr></table>')";

            if (!$wpdb->query($SQLInsert1)) {
                echo sprintf($error, __("Could not insert data into easy2map pin templates table.", 'easy2map'));
                return;
            }

            $SQLInsert2 = "INSERT INTO `$map_point_templates_table`
            (`TemplateName`,
            `TemplateHTML`)
            VALUES
            ('Thumbnail Image on Right',
            '<table style=\"margin-top:8px;overflow:hidden;vertical-align:middle;width:325px;border-radius:0px;margin-left:5px;margin-right:20px;\"><tr><td style=\"width:100%;vertical-align:top;\"><p align=\"left\" style=\"border-color:#E53840;border-style:solid;border-width:1px;color:#FFFFFF;font-family:Arial, Helvetica, sans-serif;font-size:14px;font-weight:bold;padding:4px;text-align:left;text-decoration:none;vertical-align:middle;background-color:#E53840;border-radius:0px;margin:3px;\">Section 1</p><p align=\"left\" style=\"margin:3px;background-color:#009AD7;border-color:#009AD7;border-style:solid;border-width:1px;color:#FFFFFF;font-family:Arial, Helvetica, sans-serif;font-size:13px;font-weight:bold;padding:4px;text-align:left;text-decoration:none;vertical-align:top;border-radius:0px;\">Section 2</p><p align=\"left\" style=\"margin:3px;background-color:#DADADA;border-color:#DADADA;border-style:solid;border-width:1px;color:#000000;font-family:Arial, Helvetica, sans-serif;font-size:13px;font-weight:bold;padding:4px;text-align:left;text-decoration:none;vertical-align:middle;border-radius:0px;\">Section 3</p><p align=\"left\" style=\"margin:3px;background-color:#FFFFFF;border-color:#EBEBEB;border-style:solid;border-width:1px;color:#000000;font-size:12px;font-weight:normal;padding:4px;text-align:left;border-radius:0px;\">Section 4</p></td><td style=\"vertical-align:top;padding:6px;border-style:solid;border-width:1px;vertical-align:top;border-color:#FFFFFF;\"><img style=\"border:0px solid #000000;border-radius: 0px;\" src=\"http://easy2map.com/images/css_templates/thumbnail20120424025713000000_CSS.png\" border=\"0\"></td></tr></table>')";

            if (!$wpdb->query($SQLInsert2)) {
                echo sprintf($error, __("Could not insert data into easy2map pin templates table.", 'easy2map'));
                return;
            }
        }

        $result = $wpdb->get_var("show tables like '$map_templates_table'");

        if (strtolower($result) != strtolower($map_templates_table)) {

            $SQL = "CREATE TABLE `$map_templates_table` (
            `ID` int(11) NOT NULL AUTO_INCREMENT,
            `TemplateName` varchar(256) DEFAULT NULL,
            `ExampleImage` varchar(512) DEFAULT NULL,
            `DisplayOrder` smallint(6) DEFAULT NULL,
            `CSSValues` text,
            `TemplateHTML` text,
            `StyleParentOnly` smallint(6) DEFAULT NULL,
            `Active` smallint(6) DEFAULT NULL,
            `CSSValuesList` text,
            `CSSValuesHeading` text,
            `Version` varchar(128) DEFAULT NULL,
            PRIMARY KEY (`ID`),
            UNIQUE KEY `ID_UNIQUE` (`ID`)
          ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8";

            if (!$wpdb->query($SQL)) {
                echo sprintf($error, __("Could not create easy2map templates table1.", 'easy2map'));
                return;
            }

            $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (94,'Map Style 1','',1,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin-bottom:5px;margin-left:5px;margin-right:5px;margin-top:5px;position:relative;\"></div></td></tr></table></div>',1,1,NULL,NULL,'" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (95,'Map Style 2','',2,'<settings border-style=\"solid\" border-width=\"1px\" border-color=\"#525252\" border-radius=\"0px\" width=\"600px\" height=\"400px\" margin-left=\"auto\" margin-right=\"auto\" />','<div id=\"divMap\" style=\"\"></div>',0,1,NULL,NULL,'" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (96,'Map Style 4','',4,'<settings border-style=\"double\" border-width=\"4px\" border-color=\"#828282\" border-radius=\"4px\" width=\"600px\" height=\"400px\" margin-left=\"auto\" margin-right=\"auto\" />','<div id=\"divMap\" style=\"\"></div>',0,1,NULL,NULL,'" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (97,'Map Style 3','',3,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"600px\" height=\"400px\" margin-bottom=\"0px\" />','<div align=\"center\" style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\" style=\"position:relative;\"><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin-bottom:5px;margin-left:5px;margin-right:5px;margin-top:5px;position:relative;\"></div></td></tr></table><img style=\"width:600px;\" id=\"easy2mapIimgShadow\" src=\"[siteurl]images/map_templates/easy2map_map-shadow_bottom_1.png\"/></div>',1,1,NULL,NULL,'" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (98,'Map Style 5 (includes list of markers)','',5,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:200px;\"><div id=\"divPinList\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin-bottom:5px;margin-left:5px;margin-top:5px;position:relative;\"><table cellpadding=\"2\" cellspacing=\"2\" id=\"tblEasy2MapPinList\"></table></div></td><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin-bottom:5px;margin-left:4px;margin-right:5px;margin-top:5px;position:relative;\"></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" border-radius=\"0px\" />',NULL,'" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (99,'Map Style 6 (includes list of markers)',NULL,6,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin-bottom:5px;margin-right:4px;margin-left:5px;margin-top:5px;position:relative;\"></div></td><td id=\"tdPinList\" style=\"vertical-align:top;width:200px;\"><div id=\"divPinList\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin-bottom:5px;margin-right:5px;margin-top:5px;position:relative;\"><table cellpadding=\"2\" cellspacing=\"2\" id=\"tblEasy2MapPinList\"></table></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" border-radius=\"0px\" />',NULL,'" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (100,'Map Style 7 (includes list of markers)',NULL,7,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin:5px;position:relative;\"></div></td></tr><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:100%;\"><div id=\"divPinList2\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin:5px;margin-top:0px;position:relative;\"><ul id=\"ulEasy2MapPinList\" style=\"padding:0px;margin:0px;\"></ul></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" border-radius=\"0px\" />',NULL,'" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (101,'Map Style 9 (includes map heading)',NULL,9,'<settings border-style=\"solid\" border-width=\"1px\" border-color=\"#525252\" border-radius=\"0px\" width=\"600px\" height=\"400px\" margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"0\" cellspacing=\"0\" id=\"divMapParent\"><tr><td id=\"tdMap\" style=\"vertical-align:top;\"><div id=\"divMapHeading\" style=\"\"></div><div id=\"divMap\" style=\"top:0px;left:0px;min-width:10px;margin:0px;position:relative;\"></div></td></tr></table></div>',1,1,'','<settings color=\"#FFFFFF\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#525252\" padding=\"3px\" font-size=\"14px\" text-align=\"center\" />','" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (102,'Map Style 8 (includes list of markers)',NULL,8,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:100%;\"><div id=\"divPinList2\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin:5px;margin-bottom:0px;position:relative;\"><ul id=\"ulEasy2MapPinList\" style=\"padding:0px;margin:0px;\"></ul></div></td></tr><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin:5px;position:relative;\"></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" border-radius=\"0px\" />','','" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (103,'Map Style 10 (includes map heading)',NULL,10,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMapHeading\" style=\"margin-left:3px;margin-right:3px;margin-top:3px;\"></div><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin-bottom:3px;margin-left:3px;margin-right:3px;margin-top:3px;position:relative;\"></div></td></tr></table></div>',1,1,NULL,'<settings color=\"#000000\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" padding=\"3px\" font-size=\"14px\" text-align=\"center\" border-color=\"#EBEBEB\" border-style=\"solid\" border-width=\"1px\" border-radius=\"1px\" />','" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (104,'Map Style 11 (includes map heading)',NULL,11,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdMap\" style=\"vertical-align:top;position:relative\"><div id=\"divMapHeading\" style=\"z-index:999;position:absolute;top:0px;right:0px;min-width:10px;\"></div><div id=\"divMap\" style=\"background-color:#EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin-bottom:3px;margin-left:3px;margin-right:3px;margin-top:3px;position:relative;\"></div></td></tr></table></div>',1,1,'','<settings color=\"#000000\" width=\"200px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" padding=\"3px\" font-size=\"15px\" text-align=\"center\" border-radius=\"0px\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" margin-right=\"-8px\" margin-top=\"-8px\" />','" . self::e2m_version . "');");
            
            $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (105,'Map Style 12 (1-column list of markers above map)',NULL,12,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:100%;\"><div id=\"divPinList2\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin:5px;margin-bottom:0px;position:relative;\"><table cellpadding=\"2\" cellspacing=\"2\" id=\"tblEasy2MapPinList1\"></table></div></td></tr><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin:5px;position:relative;\"></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" max-height=\"300px\" border-radius=\"0px\" />',NULL,'" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (106,'Map Style 13 (1-column list of markers below map)',NULL,13,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin:5px;position:relative;\"></div></td></tr><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:100%;\"><div id=\"divPinList2\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin:5px;margin-top:0px;position:relative;\"><table cellpadding=\"2\" cellspacing=\"2\" id=\"tblEasy2MapPinList1\"></table></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" max-height=\"300px\" border-radius=\"0px\" />',NULL,'" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (107,'Map Style 14 (2-column list of markers above map)',NULL,14,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:100%;\"><div id=\"divPinList2\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin:5px;margin-bottom:0px;position:relative;\"><table cellpadding=\"2\" cellspacing=\"2\" id=\"tblEasy2MapPinList2\"></table></div></td></tr><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin:5px;position:relative;\"></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" max-height=\"300px\" border-radius=\"0px\" />',NULL,'" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (108,'Map Style 15 (2-column list of markers below map)',NULL,15,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin:5px;position:relative;\"></div></td></tr><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:100%;\"><div id=\"divPinList2\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin:5px;margin-top:0px;position:relative;\"><table cellpadding=\"2\" cellspacing=\"2\" id=\"tblEasy2MapPinList2\"></table></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" max-height=\"300px\" border-radius=\"0px\" />',NULL,'" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (109,'Map Style 16 (3-column list of markers above map)',NULL,16,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:100%;\"><div id=\"divPinList2\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin:5px;margin-bottom:0px;position:relative;\"><table cellpadding=\"2\" cellspacing=\"2\" id=\"tblEasy2MapPinList3\"></table></div></td></tr><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin:5px;position:relative;\"></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" max-height=\"300px\" border-radius=\"0px\" />',NULL,'" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (110,'Map Style 17 (3-column list of markers below map)',NULL,17,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin:5px;position:relative;\"></div></td></tr><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:100%;\"><div id=\"divPinList2\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin:5px;margin-top:0px;position:relative;\"><table cellpadding=\"2\" cellspacing=\"2\" id=\"tblEasy2MapPinList3\"></table></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" max-height=\"300px\" border-radius=\"0px\" />',NULL,'" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (111,'Map Style 18 (4-column list of markers above map)',NULL,18,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:100%;\"><div id=\"divPinList2\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin:5px;margin-bottom:0px;position:relative;\"><table cellpadding=\"2\" cellspacing=\"2\" id=\"tblEasy2MapPinList4\"></table></div></td></tr><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin:5px;position:relative;\"></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" max-height=\"300px\" border-radius=\"0px\" />',NULL,'" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (112,'Map Style 19 (4-column list of markers below map)',NULL,19,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin:5px;position:relative;\"></div></td></tr><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:100%;\"><div id=\"divPinList2\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin:5px;margin-top:0px;position:relative;\"><table cellpadding=\"2\" cellspacing=\"2\" id=\"tblEasy2MapPinList4\"></table></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" max-height=\"300px\" border-radius=\"0px\" />',NULL,'" . self::e2m_version . "');");
            
        } else {

            $arrFound = $wpdb->get_results("SELECT * FROM information_schema.COLUMNS 
            WHERE TABLE_NAME = '$map_templates_table' AND TABLE_SCHEMA = '" . DB_NAME . "' AND COLUMN_NAME = 'Version';");

            //add Version table column
            if (count($arrFound) === 0) {
                $wpdb->query("ALTER TABLE $map_templates_table ADD Version varchar(128) NULL;");
            }

            $continue = true;

            $arrVersion = $wpdb->get_results("SELECT IFNULL(Version,'0') AS Version FROM $map_templates_table LIMIT 1;");
            foreach ($arrVersion as $version) {
                if (strcasecmp($version->Version, self::e2m_version) === 0) {
                    $continue = false;
                }
            }

            if ($continue === true) {

                //check to see if the new columns (for version 1.2.1) have been added
                $arrFound1 = $wpdb->get_results("SELECT * FROM information_schema.COLUMNS 
                WHERE TABLE_NAME = '$map_templates_table' AND TABLE_SCHEMA = '" . DB_NAME . "' AND COLUMN_NAME = 'CSSValuesList';");

                //add CSSValuesList table column
                if (count($arrFound1) === 0) {
                    $wpdb->query("ALTER TABLE $map_templates_table ADD CSSValuesList TEXT NULL;");
                }

                //check to see if the new columns (for version 1.2.1) have been added
                $arrFound2 = $wpdb->get_results("SELECT * FROM information_schema.COLUMNS 
                WHERE TABLE_NAME = '$map_table' AND TABLE_SCHEMA = '" . DB_NAME . "' AND COLUMN_NAME = 'CSSValuesList';");

                //add CSSValuesList table column
                if (count($arrFound2) === 0) {
                    $wpdb->query("ALTER TABLE $map_table ADD CSSValuesList TEXT NULL;");
                }

                $arrFound3 = $wpdb->get_results("SELECT * FROM information_schema.COLUMNS 
                WHERE TABLE_NAME = '$map_templates_table' AND TABLE_SCHEMA = '" . DB_NAME . "' AND COLUMN_NAME = 'CSSValuesHeading';");

                //add CSSValuesHeading table column
                if (count($arrFound3) === 0) {
                    $wpdb->query("ALTER TABLE $map_templates_table ADD CSSValuesHeading TEXT NULL;");
                }

                $arrFound4 = $wpdb->get_results("SELECT * FROM information_schema.COLUMNS 
                WHERE TABLE_NAME = '$map_table' AND TABLE_SCHEMA = '" . DB_NAME . "' AND COLUMN_NAME = 'CSSValuesHeading';");

                //add CSSValuesHeading table column
                if (count($arrFound4) === 0) {
                    $wpdb->query("ALTER TABLE $map_table ADD CSSValuesHeading TEXT NULL;");
                }

                //does template 94 exist?
                $arrFound5 = $wpdb->get_results("SELECT ID FROM `$map_templates_table` WHERE ID = 94");
                if (count($arrFound5) === 0) {

                    if (!$wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading) VALUES (94,'Map Style 1','',1,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin-bottom:5px;margin-left:5px;margin-right:5px;margin-top:5px;position:relative;\"></div></td></tr></table></div>',1,1,NULL,NULL);")) {
                        echo sprintf($error, __("Could not add data to easy2map templates table3.", 'easy2map'));
                        return;
                    }
                } else {

                   $wpdb->query("UPDATE `$map_templates_table`
                   SET CSSValues = '<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />'
                   ,TemplateHTML = '<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin-bottom:5px;margin-left:5px;margin-right:5px;margin-top:5px;position:relative;\"></div></td></tr></table></div>' 
                   WHERE ID = 94;");
                }

                //does template 95 exist?
                $arrFound6 = $wpdb->get_results("SELECT ID FROM `$map_templates_table` WHERE ID = 95");
                if (count($arrFound6) === 0) {

                    if (!$wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading) VALUES (95,'Map Style 2','',2,'<settings border-style=\"solid\" border-width=\"1px\" border-color=\"#525252\" border-radius=\"0px\" width=\"600px\" height=\"400px\" margin-left=\"auto\" margin-right=\"auto\" />','<div id=\"divMap\" style=\"\"></div>',0,1,NULL,NULL);")) {
                        echo sprintf($error, __("Could not add data to easy2map templates table4.", 'easy2map'));
                        return;
                    }
                } else {

                    $wpdb->query("UPDATE `$map_templates_table`
                   SET CSSValues = '<settings border-style=\"solid\" border-width=\"1px\" border-color=\"#525252\" border-radius=\"0px\" width=\"600px\" height=\"400px\" margin-left=\"auto\" margin-right=\"auto\" />'
                   ,TemplateHTML = '<div id=\"divMap\" style=\"\"></div>' 
                   WHERE ID = 95;");
                }

                //does template 96 exist?
                $arrFound7 = $wpdb->get_results("SELECT ID FROM `$map_templates_table` WHERE ID = 96");
                if (count($arrFound7) === 0) {

                    if (!$wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading) VALUES (96,'Map Style 4','',4,'<settings border-style=\"double\" border-width=\"4px\" border-color=\"#828282\" border-radius=\"4px\" width=\"600px\" height=\"400px\" margin-left=\"auto\" margin-right=\"auto\" />','<div id=\"divMap\" style=\"\"></div>',0,1,NULL,NULL);")) {
                        echo sprintf($error, __("Could not add data to easy2map templates table5.", 'easy2map'));
                        return;
                    }
                } else {

                    $wpdb->query("UPDATE `$map_templates_table`
                   SET CSSValues = '<settings border-style=\"double\" border-width=\"4px\" border-color=\"#828282\" border-radius=\"4px\" width=\"600px\" height=\"400px\" margin-left=\"auto\" margin-right=\"auto\" />'
                   ,TemplateHTML = '<div id=\"divMap\" style=\"\"></div>' 
                   WHERE ID = 96;");
                }

                //does template 97 exist?
                $arrFound8 = $wpdb->get_results("SELECT ID FROM `$map_templates_table` WHERE ID = 97");
                if (count($arrFound8) === 0) {

                    if (!$wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading) VALUES (97,'Map Style 3','',3,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"600px\" height=\"400px\" margin-bottom=\"0px\" />','<div align=\"center\" style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\" style=\"position:relative;\"><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin-bottom:5px;margin-left:5px;margin-right:5px;margin-top:5px;position:relative;\"></div></td></tr></table><img style=\"width:600px;\" id=\"easy2mapIimgShadow\" src=\"[siteurl]images/map_templates/easy2map_map-shadow_bottom_1.png\"/></div>',1,1,NULL,NULL);")) {
                        echo sprintf($error, __("Could not add data to easy2map templates table6.", 'easy2map'));
                        return;
                    }
                } else {

                    $wpdb->query("UPDATE `$map_templates_table`
                   SET CSSValues = '<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"600px\" height=\"400px\" margin-bottom=\"0px\" />'
                   ,TemplateHTML = '<div align=\"center\" style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\" style=\"position:relative;\"><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin-bottom:5px;margin-left:5px;margin-right:5px;margin-top:5px;position:relative;\"></div></td></tr></table><img style=\"width:600px;\" id=\"easy2mapIimgShadow\" src=\"[siteurl]images/map_templates/easy2map_map-shadow_bottom_1.png\"/></div>' 
                   WHERE ID = 97;");
                }

                //does template 98 exist?
                $arrFound9 = $wpdb->get_results("SELECT ID FROM `$map_templates_table` WHERE ID = 98");
                if (count($arrFound9) === 0) {

                    if (!$wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading) VALUES (98,'Map Style 5 (includes list of markers)','',5,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:200px;\"><div id=\"divPinList\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin-bottom:5px;margin-left:5px;margin-top:5px;position:relative;\"><table cellpadding=\"2\" cellspacing=\"2\" id=\"tblEasy2MapPinList\"></table></div></td><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin-bottom:5px;margin-left:4px;margin-right:5px;margin-top:5px;position:relative;\"></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" border-radius=\"0px\" />',NULL);")) {
                        echo sprintf($error, __("Could not add data to easy2map templates table7.", 'easy2map'));
                        return;
                    }
                } else {

                    $wpdb->query("UPDATE `$map_templates_table`
                   SET CSSValues = '<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />'
                   ,TemplateHTML = '<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:200px;\"><div id=\"divPinList\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin-bottom:5px;margin-left:5px;margin-top:5px;position:relative;\"><table cellpadding=\"2\" cellspacing=\"2\" id=\"tblEasy2MapPinList\"></table></div></td><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin-bottom:5px;margin-left:4px;margin-right:5px;margin-top:5px;position:relative;\"></div></td></tr></table></div>'
                   ,CSSValuesList = '<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" border-radius=\"0px\" />'
                   WHERE ID = 98;");
                }

                //does template 99 exist?
                $arrFound10 = $wpdb->get_results("SELECT ID FROM `$map_templates_table` WHERE ID = 99");
                if (count($arrFound10) === 0) {

                    if (!$wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading) VALUES (99,'Map Style 6 (includes list of markers)',NULL,6,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin-bottom:5px;margin-right:4px;margin-left:5px;margin-top:5px;position:relative;\"></div></td><td id=\"tdPinList\" style=\"vertical-align:top;width:200px;\"><div id=\"divPinList\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin-bottom:5px;margin-right:5px;margin-top:5px;position:relative;\"><table cellpadding=\"2\" cellspacing=\"2\" id=\"tblEasy2MapPinList\"></table></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" border-radius=\"0px\" />',NULL);")) {
                        echo sprintf($error, __("Could not add data to easy2map templates table8.", 'easy2map'));
                        return;
                    }
                } else {

                    $wpdb->query("UPDATE `$map_templates_table`
                   SET CSSValues = '<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />'
                   ,TemplateHTML = '<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin-bottom:5px;margin-right:4px;margin-left:5px;margin-top:5px;position:relative;\"></div></td><td id=\"tdPinList\" style=\"vertical-align:top;width:200px;\"><div id=\"divPinList\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin-bottom:5px;margin-right:5px;margin-top:5px;position:relative;\"><table cellpadding=\"2\" cellspacing=\"2\" id=\"tblEasy2MapPinList\"></table></div></td></tr></table></div>'
                   ,CSSValuesList = '<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" border-radius=\"0px\" />'
                   WHERE ID = 99;");
                }

                //does template 100 exist?
                $arrFound11 = $wpdb->get_results("SELECT ID FROM `$map_templates_table` WHERE ID = 100");
                if (count($arrFound11) === 0) {

                    if (!$wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading) VALUES (100,'Map Style 7 (includes list of markers)',NULL,7,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin:5px;position:relative;\"></div></td></tr><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:100%;\"><div id=\"divPinList2\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin:5px;margin-top:0px;position:relative;\"><ul id=\"ulEasy2MapPinList\" style=\"padding:0px;margin:0px;\"></ul></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" border-radius=\"0px\" />',NULL);")) {
                        echo sprintf($error, __("Could not add data to easy2map templates table9.", 'easy2map'));
                        return;
                    }
                } else {

                    $wpdb->query("UPDATE `$map_templates_table`
                    SET CSSValues = '<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />'
                    ,TemplateHTML = '<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin:5px;position:relative;\"></div></td></tr><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:100%;\"><div id=\"divPinList2\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin:5px;margin-top:0px;position:relative;\"><ul id=\"ulEasy2MapPinList\" style=\"padding:0px;margin:0px;\"></ul></div></td></tr></table></div>'
                    ,CSSValuesList = '<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" border-radius=\"0px\" />'
                    WHERE ID = 100;");
                }

                //does template 101 exist?
                $arrFound12 = $wpdb->get_results("SELECT ID FROM `$map_templates_table` WHERE ID = 101");
                if (count($arrFound12) === 0) {

                    if (!$wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading) VALUES (101,'Map Style 9 (includes map heading)',NULL,9,'<settings border-style=\"solid\" border-width=\"1px\" border-color=\"#525252\" border-radius=\"0px\" width=\"600px\" height=\"400px\" margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"0\" cellspacing=\"0\" id=\"divMapParent\"><tr><td id=\"tdMap\" style=\"vertical-align:top;\"><div id=\"divMapHeading\" style=\"\"></div><div id=\"divMap\" style=\"top:0px;left:0px;min-width:10px;margin:0px;position:relative;\"></div></td></tr></table></div>',1,1,'','<settings color=\"#FFFFFF\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#525252\" padding=\"3px\" font-size=\"14px\" text-align=\"center\" />');")) {
                        echo sprintf($error, __("Could not add data to easy2map templates table10.", 'easy2map'));
                        return;
                    }
                } else {

                    $wpdb->query("UPDATE `$map_templates_table`
                    SET CSSValues = '<settings border-style=\"solid\" border-width=\"1px\" border-color=\"#525252\" border-radius=\"0px\" width=\"600px\" height=\"400px\" margin-left=\"auto\" margin-right=\"auto\" />'
                    ,TemplateHTML = '<div style=\"margin:auto;\"><table cellpadding=\"0\" cellspacing=\"0\" id=\"divMapParent\"><tr><td id=\"tdMap\" style=\"vertical-align:top;\"><div id=\"divMapHeading\" style=\"\"></div><div id=\"divMap\" style=\"top:0px;left:0px;min-width:10px;margin:0px;position:relative;\"></div></td></tr></table></div>'
                    ,CSSValuesHeading = '<settings color=\"#FFFFFF\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#525252\" padding=\"3px\" font-size=\"14px\" text-align=\"center\" />'
                    WHERE ID = 101;");
                }

                //does template 102 exist?
                $arrFound13 = $wpdb->get_results("SELECT ID FROM `$map_templates_table` WHERE ID = 102");
                if (count($arrFound13) === 0) {

                    if (!$wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading) VALUES (102,'Map Style 8 (includes list of markers)',NULL,8,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:100%;\"><div id=\"divPinList2\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin:5px;margin-bottom:0px;position:relative;\"><ul id=\"ulEasy2MapPinList\" style=\"padding:0px;margin:0px;\"></ul></div></td></tr><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin:5px;position:relative;\"></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" border-radius=\"0px\" />','');")) {
                        echo sprintf($error, __("Could not add data to easy2map templates table11.", 'easy2map'));
                        return;
                    }
                } else {

                    $wpdb->query("UPDATE `$map_templates_table`
                    SET CSSValues = '<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />'
                    ,TemplateHTML = '<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:100%;\"><div id=\"divPinList2\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin:5px;margin-bottom:0px;position:relative;\"><ul id=\"ulEasy2MapPinList\" style=\"padding:0px;margin:0px;\"></ul></div></td></tr><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin:5px;position:relative;\"></div></td></tr></table></div>'
                    ,CSSValuesList = '<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" border-radius=\"0px\" />'
                    WHERE ID = 102;");
                }

                //does template 103 exist?
                $arrFound14 = $wpdb->get_results("SELECT ID FROM `$map_templates_table` WHERE ID = 103");
                if (count($arrFound14) === 0) {

                    if (!$wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading) VALUES (103,'Map Style 10 (includes map heading)',NULL,10,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMapHeading\" style=\"margin-left:3px;margin-right:3px;margin-top:3px;\"></div><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin-bottom:3px;margin-left:3px;margin-right:3px;margin-top:3px;position:relative;\"></div></td></tr></table></div>',1,1,NULL,'<settings color=\"#000000\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" padding=\"3px\" font-size=\"14px\" text-align=\"center\" border-color=\"#EBEBEB\" border-style=\"solid\" border-width=\"1px\" border-radius=\"1px\" />');")) {
                        echo sprintf($error, __("Could not add data to easy2map templates table12.", 'easy2map'));
                        return;
                    }
                } else {

                    $wpdb->query("UPDATE `$map_templates_table`
                    SET CSSValues = '<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />'
                    ,TemplateHTML = '<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMapHeading\" style=\"margin-left:3px;margin-right:3px;margin-top:3px;\"></div><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin-bottom:3px;margin-left:3px;margin-right:3px;margin-top:3px;position:relative;\"></div></td></tr></table></div>'
                    ,CSSValuesHeading = '<settings color=\"#000000\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" padding=\"3px\" font-size=\"14px\" text-align=\"center\" border-color=\"#EBEBEB\" border-style=\"solid\" border-width=\"1px\" border-radius=\"1px\" />'
                    WHERE ID = 103;");
                }

                //does template 104 exist?
                $arrFound15 = $wpdb->get_results("SELECT ID FROM `$map_templates_table` WHERE ID = 104");
                if (count($arrFound15) === 0) {

                    if (!$wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading) VALUES (104,'Map Style 11 (includes map heading)',NULL,11,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdMap\" style=\"vertical-align:top;position:relative\"><div id=\"divMapHeading\" style=\"z-index:999;position:absolute;top:0px;right:0px;min-width:10px;\"></div><div id=\"divMap\" style=\"background-color:#EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin-bottom:3px;margin-left:3px;margin-right:3px;margin-top:3px;position:relative;\"></div></td></tr></table></div>',1,1,'','<settings color=\"#000000\" width=\"200px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" padding=\"3px\" font-size=\"15px\" text-align=\"center\" border-radius=\"0px\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" margin-right=\"-8px\" margin-top=\"-8px\" />');")) {
                        echo sprintf($error, __("Could not add data to easy2map templates table13.", 'easy2map'));
                        return;
                    }
                } else {

                    $wpdb->query("UPDATE `$map_templates_table`
                    SET CSSValues = '<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />'
                    ,TemplateHTML = '<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdMap\" style=\"vertical-align:top;position:relative\"><div id=\"divMapHeading\" style=\"z-index:999;position:absolute;top:0px;right:0px;min-width:10px;\"></div><div id=\"divMap\" style=\"background-color:#EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin-bottom:3px;margin-left:3px;margin-right:3px;margin-top:3px;position:relative;\"></div></td></tr></table></div>'
                    ,CSSValuesHeading = '<settings color=\"#000000\" width=\"200px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" padding=\"3px\" font-size=\"15px\" text-align=\"center\" border-radius=\"0px\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" margin-right=\"-8px\" margin-top=\"-8px\" />'
                    WHERE ID = 104;");
                }

                //does template 105 exist?
                $arrFound16 = $wpdb->get_results("SELECT ID FROM `$map_templates_table` WHERE ID = 105");
                if (count($arrFound16) === 0) {

                    $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (105,'Map Style 12 (1-column list of markers above map)',NULL,12,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:100%;\"><div id=\"divPinList2\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin:5px;margin-bottom:0px;position:relative;\"><table cellpadding=\"2\" cellspacing=\"2\" id=\"tblEasy2MapPinList1\"></table></div></td></tr><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin:5px;position:relative;\"></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" max-height=\"300px\" border-radius=\"0px\" />',NULL,'" . self::e2m_version . "');");
                }


                //does template 106 exist?
                $arrFound17 = $wpdb->get_results("SELECT ID FROM `$map_templates_table` WHERE ID = 106");
                if (count($arrFound17) === 0) {

                    $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (106,'Map Style 13 (1-column list of markers below map)',NULL,13,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin:5px;position:relative;\"></div></td></tr><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:100%;\"><div id=\"divPinList2\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin:5px;margin-top:0px;position:relative;\"><table cellpadding=\"2\" cellspacing=\"2\" id=\"tblEasy2MapPinList1\"></table></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" max-height=\"300px\" border-radius=\"0px\" />',NULL,'" . self::e2m_version . "');");
                }
                
                //does template 107 exist?
                $arrFound16 = $wpdb->get_results("SELECT ID FROM `$map_templates_table` WHERE ID = 107");
                if (count($arrFound16) === 0) {

                    $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (107,'Map Style 14 (2-column list of markers above map)',NULL,14,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:100%;\"><div id=\"divPinList2\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin:5px;margin-bottom:0px;position:relative;\"><table cellpadding=\"2\" cellspacing=\"2\" id=\"tblEasy2MapPinList2\"></table></div></td></tr><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin:5px;position:relative;\"></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" max-height=\"300px\" border-radius=\"0px\" />',NULL,'" . self::e2m_version . "');");
                }

                //does template 108 exist?
                $arrFound17 = $wpdb->get_results("SELECT ID FROM `$map_templates_table` WHERE ID = 108");
                if (count($arrFound17) === 0) {

                    $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (108,'Map Style 15 (2-column list of markers below map)',NULL,15,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin:5px;position:relative;\"></div></td></tr><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:100%;\"><div id=\"divPinList2\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin:5px;margin-top:0px;position:relative;\"><table cellpadding=\"2\" cellspacing=\"2\" id=\"tblEasy2MapPinList2\"></table></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" max-height=\"300px\" border-radius=\"0px\" />',NULL,'" . self::e2m_version . "');");
                }
                
                //does template 109 exist?
                $arrFound18 = $wpdb->get_results("SELECT ID FROM `$map_templates_table` WHERE ID = 109");
                if (count($arrFound18) === 0) {

                    $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (109,'Map Style 16 (3-column list of markers above map)',NULL,16,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:100%;\"><div id=\"divPinList2\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin:5px;margin-bottom:0px;position:relative;\"><table cellpadding=\"2\" cellspacing=\"2\" id=\"tblEasy2MapPinList3\"></table></div></td></tr><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin:5px;position:relative;\"></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" max-height=\"300px\" border-radius=\"0px\" />',NULL,'" . self::e2m_version . "');");
                }
                
                //does template 110 exist?
                $arrFound19 = $wpdb->get_results("SELECT ID FROM `$map_templates_table` WHERE ID = 110");
                if (count($arrFound19) === 0) {

                    $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (110,'Map Style 17 (3-column list of markers below map)',NULL,17,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin:5px;position:relative;\"></div></td></tr><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:100%;\"><div id=\"divPinList2\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin:5px;margin-top:0px;position:relative;\"><table cellpadding=\"2\" cellspacing=\"2\" id=\"tblEasy2MapPinList3\"></table></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" max-height=\"300px\" border-radius=\"0px\" />',NULL,'" . self::e2m_version . "');");
            
                }
                
                //does template 111 exist?
                $arrFound20 = $wpdb->get_results("SELECT ID FROM `$map_templates_table` WHERE ID = 111");
                if (count($arrFound20) === 0) {

                    $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (111,'Map Style 18 (4-column list of markers above map)',NULL,18,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:100%;\"><div id=\"divPinList2\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin:5px;margin-bottom:0px;position:relative;\"><table cellpadding=\"2\" cellspacing=\"2\" id=\"tblEasy2MapPinList4\"></table></div></td></tr><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin:5px;position:relative;\"></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" max-height=\"300px\" border-radius=\"0px\" />',NULL,'" . self::e2m_version . "');");
            
                }
                
                //does template 112 exist?
                $arrFound21 = $wpdb->get_results("SELECT ID FROM `$map_templates_table` WHERE ID = 112");
                if (count($arrFound21) === 0) {

                    $wpdb->query("INSERT INTO `$map_templates_table` (ID,TemplateName,ExampleImage,DisplayOrder,CSSValues,TemplateHTML,StyleParentOnly,Active,CSSValuesList,CSSValuesHeading,Version) VALUES (112,'Map Style 19 (4-column list of markers below map)',NULL,19,'<settings background-color=\"#FFFFFF\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" width=\"640px\" height=\"480px\"  margin-left=\"auto\" margin-right=\"auto\" />','<div style=\"margin:auto;\"><table cellpadding=\"1\" cellspacing=\"1\" id=\"divMapParent\"><tr><td id=\"tdMap\" editable=\"0\" style=\"vertical-align:top;\"><div id=\"divMap\" style=\"background-color: #EBEBEB;border-style:solid;border-width:1px;border-color:transparent;top:0px;left:0px;min-width:10px;margin:5px;position:relative;\"></div></td></tr><tr><td id=\"tdPinList\" style=\"vertical-align:top;width:100%;\"><div id=\"divPinList2\" style=\"overflow:auto;top:0px;left:0px;min-width:10px;margin:5px;margin-top:0px;position:relative;\"><table cellpadding=\"2\" cellspacing=\"2\" id=\"tblEasy2MapPinList4\"></table></div></td></tr></table></div>',1,1,'<settings color=\"#000000\" font-size=\"12px\" font-family=\"Arial, Helvetica, sans-serif\" background-color=\"#FFFFFF\" text-align=\"left\" border-style=\"solid\" border-width=\"1px\" border-color=\"#EBEBEB\" max-height=\"300px\" border-radius=\"0px\" />',NULL,'" . self::e2m_version . "');");
            
                }

                //set all old templates to ZERO
                $wpdb->query("UPDATE `$map_templates_table` SET `Active` = 0 WHERE ID NOT IN (94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,111,112);");
                $wpdb->query("UPDATE `$map_templates_table` SET `Active` = 1 WHERE ID IN (94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,107,108,109,110,111,112);");
                //set all maps to default template
                $wpdb->query("UPDATE `$map_table` SET `TemplateID` = 94 WHERE `TemplateID` NOT IN (94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,107,108,109,110,111,112);");
                $wpdb->query("UPDATE `$map_templates_table` SET `Version` = '" . self::e2m_version . "';");
            }
        }


        $result = $wpdb->get_var("show tables like '$map_themes_table'");

        if (strtolower($result) != strtolower($map_themes_table)) {

            $SQL = "CREATE TABLE `$map_themes_table` (
                `ID` int(11) NOT NULL AUTO_INCREMENT,
                `ThemeName` varchar(256) DEFAULT NULL,
                `Styles` text,
                `Version` varchar(128) DEFAULT NULL,
                PRIMARY KEY (`ID`),
                UNIQUE KEY `ID_UNIQUE` (`ID`)
                ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8";

            if (!$wpdb->query($SQL)) {
                echo sprintf($error, __("Could not create easy2map themes table.", 'easy2map'));
                return;
            }

            $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (1, 'No Theme','','" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (2, 'Subtle Greyscale','[{\"featureType\":\"landscape\",\"stylers\":[{\"saturation\":-100},{\"lightness\":65},{\"visibility\":\"on\"}]},{\"featureType\":\"poi\",\"stylers\":[{\"saturation\":-100},{\"lightness\":51},{\"visibility\":\"simplified\"}]},{\"featureType\":\"road.highway\",\"stylers\":[{\"saturation\":-100},{\"visibility\":\"simplified\"}]},{\"featureType\":\"road.arterial\",\"stylers\":[{\"saturation\":-100},{\"lightness\":30},{\"visibility\":\"on\"}]},{\"featureType\":\"road.local\",\"stylers\":[{\"saturation\":-100},{\"lightness\":40},{\"visibility\":\"on\"}]},{\"featureType\":\"transit\",\"stylers\":[{\"saturation\":-100},{\"visibility\":\"simplified\"}]},{\"featureType\":\"administrative.province\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"water\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"on\"},{\"lightness\":-25},{\"saturation\":-100}]},{\"featureType\":\"water\",\"elementType\":\"geometry\",\"stylers\":[{\"hue\":\"#ffff00\"},{\"lightness\":-25},{\"saturation\":-97}]}]','" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (3, 'Blue Essence','[{\"featureType\":\"landscape.natural\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"visibility\":\"on\"},{\"color\":\"#e0efef\"}]},{\"featureType\":\"poi\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"visibility\":\"on\"},{\"hue\":\"#1900ff\"},{\"color\":\"#c0e8e8\"}]},{\"featureType\":\"road\",\"elementType\":\"geometry\",\"stylers\":[{\"lightness\":100},{\"visibility\":\"simplified\"}]},{\"featureType\":\"road\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"transit.line\",\"elementType\":\"geometry\",\"stylers\":[{\"visibility\":\"on\"},{\"lightness\":700}]},{\"featureType\":\"water\",\"elementType\":\"all\",\"stylers\":[{\"color\":\"#7dcdcd\"}]}]','" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (4, 'Apple Maps-esque','[{\"featureType\":\"landscape.man_made\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#f7f1df\"}]},{\"featureType\":\"landscape.natural\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#d0e3b4\"}]},{\"featureType\":\"landscape.natural.terrain\",\"elementType\":\"geometry\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi.business\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi.medical\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#fbd3da\"}]},{\"featureType\":\"poi.park\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#bde6ab\"}]},{\"featureType\":\"road\",\"elementType\":\"geometry.stroke\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road.highway\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#ffe15f\"}]},{\"featureType\":\"road.highway\",\"elementType\":\"geometry.stroke\",\"stylers\":[{\"color\":\"#efd151\"}]},{\"featureType\":\"road.arterial\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#ffffff\"}]},{\"featureType\":\"road.local\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"black\"}]},{\"featureType\":\"transit.station.airport\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#cfb2db\"}]},{\"featureType\":\"water\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#a2daf2\"}]}]','" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (5, 'Blue Water','[{\"featureType\":\"administrative\",\"elementType\":\"labels.text.fill\",\"stylers\":[{\"color\":\"#444444\"}]},{\"featureType\":\"landscape\",\"elementType\":\"all\",\"stylers\":[{\"color\":\"#f2f2f2\"}]},{\"featureType\":\"poi\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road\",\"elementType\":\"all\",\"stylers\":[{\"saturation\":-100},{\"lightness\":45}]},{\"featureType\":\"road.highway\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"road.arterial\",\"elementType\":\"labels.icon\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"transit\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"water\",\"elementType\":\"all\",\"stylers\":[{\"color\":\"#46bcec\"},{\"visibility\":\"on\"}]}]','" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (6, 'Pale Dawn','[{\"featureType\":\"administrative\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"on\"},{\"lightness\":33}]},{\"featureType\":\"landscape\",\"elementType\":\"all\",\"stylers\":[{\"color\":\"#f2e5d4\"}]},{\"featureType\":\"poi.park\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#c5dac6\"}]},{\"featureType\":\"poi.park\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"on\"},{\"lightness\":20}]},{\"featureType\":\"road\",\"elementType\":\"all\",\"stylers\":[{\"lightness\":20}]},{\"featureType\":\"road.highway\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#c5c6c6\"}]},{\"featureType\":\"road.arterial\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#e4d7c6\"}]},{\"featureType\":\"road.local\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#fbfaf7\"}]},{\"featureType\":\"water\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"on\"},{\"color\":\"#acbcc9\"}]}]','" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (7, 'Retro','[{\"featureType\":\"administrative\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"road\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"water\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"transit\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"landscape\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"road.highway\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road.local\",\"stylers\":[{\"visibility\":\"on\"}]},{\"featureType\":\"road.highway\",\"elementType\":\"geometry\",\"stylers\":[{\"visibility\":\"on\"}]},{\"featureType\":\"water\",\"stylers\":[{\"color\":\"#84afa3\"},{\"lightness\":52}]},{\"stylers\":[{\"saturation\":-17},{\"gamma\":0.36}]},{\"featureType\":\"transit.line\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#3f518c\"}]}]','" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (8, 'Paper','[{\"featureType\":\"administrative\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"landscape\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"simplified\"},{\"hue\":\"#0066ff\"},{\"saturation\":74},{\"lightness\":100}]},{\"featureType\":\"poi\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"road\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"road.highway\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"off\"},{\"weight\":0.6},{\"saturation\":-85},{\"lightness\":61}]},{\"featureType\":\"road.highway\",\"elementType\":\"geometry\",\"stylers\":[{\"visibility\":\"on\"}]},{\"featureType\":\"road.arterial\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road.local\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"on\"}]},{\"featureType\":\"transit\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"water\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"simplified\"},{\"color\":\"#5f94ff\"},{\"lightness\":26},{\"gamma\":5.86}]}]','" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (9, 'Gowalla','[{\"featureType\":\"administrative.land_parcel\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"landscape.man_made\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"simplified\"},{\"lightness\":20}]},{\"featureType\":\"road.highway\",\"elementType\":\"geometry\",\"stylers\":[{\"hue\":\"#f49935\"}]},{\"featureType\":\"road.highway\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"road.arterial\",\"elementType\":\"geometry\",\"stylers\":[{\"hue\":\"#fad959\"}]},{\"featureType\":\"road.arterial\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road.local\",\"elementType\":\"geometry\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"road.local\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"transit\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"water\",\"elementType\":\"all\",\"stylers\":[{\"hue\":\"#a1cdfc\"},{\"saturation\":30},{\"lightness\":49}]}]','" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (10, 'Neutral Blue','[{\"featureType\":\"water\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#193341\"}]},{\"featureType\":\"landscape\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#2c5a71\"}]},{\"featureType\":\"road\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#29768a\"},{\"lightness\":-37}]},{\"featureType\":\"poi\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#406d80\"}]},{\"featureType\":\"transit\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#406d80\"}]},{\"elementType\":\"labels.text.stroke\",\"stylers\":[{\"visibility\":\"on\"},{\"color\":\"#3e606f\"},{\"weight\":2},{\"gamma\":0.84}]},{\"elementType\":\"labels.text.fill\",\"stylers\":[{\"color\":\"#ffffff\"}]},{\"featureType\":\"administrative\",\"elementType\":\"geometry\",\"stylers\":[{\"weight\":0.6},{\"color\":\"#1a3541\"}]},{\"elementType\":\"labels.icon\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi.park\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#2c5a71\"}]}]','" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (11, 'MapBox','[{\"featureType\":\"water\",\"stylers\":[{\"saturation\":43},{\"lightness\":-11},{\"hue\":\"#0088ff\"}]},{\"featureType\":\"road\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"hue\":\"#ff0000\"},{\"saturation\":-100},{\"lightness\":99}]},{\"featureType\":\"road\",\"elementType\":\"geometry.stroke\",\"stylers\":[{\"color\":\"#808080\"},{\"lightness\":54}]},{\"featureType\":\"landscape.man_made\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#ece2d9\"}]},{\"featureType\":\"poi.park\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#ccdca1\"}]},{\"featureType\":\"road\",\"elementType\":\"labels.text.fill\",\"stylers\":[{\"color\":\"#767676\"}]},{\"featureType\":\"road\",\"elementType\":\"labels.text.stroke\",\"stylers\":[{\"color\":\"#ffffff\"}]},{\"featureType\":\"poi\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"landscape.natural\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"visibility\":\"on\"},{\"color\":\"#b8cb93\"}]},{\"featureType\":\"poi.park\",\"stylers\":[{\"visibility\":\"on\"}]},{\"featureType\":\"poi.sports_complex\",\"stylers\":[{\"visibility\":\"on\"}]},{\"featureType\":\"poi.medical\",\"stylers\":[{\"visibility\":\"on\"}]},{\"featureType\":\"poi.business\",\"stylers\":[{\"visibility\":\"simplified\"}]}]','" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (12, 'becomeadinosaur','[{\"elementType\":\"labels.text\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"landscape.natural\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#f5f5f2\"},{\"visibility\":\"on\"}]},{\"featureType\":\"administrative\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"transit\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi.attraction\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"landscape.man_made\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#ffffff\"},{\"visibility\":\"on\"}]},{\"featureType\":\"poi.business\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi.medical\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi.place_of_worship\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi.school\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi.sports_complex\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road.highway\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#ffffff\"},{\"visibility\":\"simplified\"}]},{\"featureType\":\"road.arterial\",\"stylers\":[{\"visibility\":\"simplified\"},{\"color\":\"#ffffff\"}]},{\"featureType\":\"road.highway\",\"elementType\":\"labels.icon\",\"stylers\":[{\"color\":\"#ffffff\"},{\"visibility\":\"off\"}]},{\"featureType\":\"road.highway\",\"elementType\":\"labels.icon\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road.arterial\",\"stylers\":[{\"color\":\"#ffffff\"}]},{\"featureType\":\"road.local\",\"stylers\":[{\"color\":\"#ffffff\"}]},{\"featureType\":\"poi.park\",\"elementType\":\"labels.icon\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi\",\"elementType\":\"labels.icon\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"water\",\"stylers\":[{\"color\":\"#71c8d4\"}]},{\"featureType\":\"landscape\",\"stylers\":[{\"color\":\"#e5e8e7\"}]},{\"featureType\":\"poi.park\",\"stylers\":[{\"color\":\"#8ba129\"}]},{\"featureType\":\"road\",\"stylers\":[{\"color\":\"#ffffff\"}]},{\"featureType\":\"poi.sports_complex\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#c7c7c7\"},{\"visibility\":\"off\"}]},{\"featureType\":\"water\",\"stylers\":[{\"color\":\"#a0d3d3\"}]},{\"featureType\":\"poi.park\",\"stylers\":[{\"color\":\"#91b65d\"}]},{\"featureType\":\"poi.park\",\"stylers\":[{\"gamma\":1.51}]},{\"featureType\":\"road.local\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road.local\",\"elementType\":\"geometry\",\"stylers\":[{\"visibility\":\"on\"}]},{\"featureType\":\"poi.government\",\"elementType\":\"geometry\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"landscape\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road.arterial\",\"elementType\":\"geometry\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"road.local\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"road\"},{\"featureType\":\"road\"},{},{\"featureType\":\"road.highway\"}]','" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (13, 'Avocado World','[{\"featureType\":\"water\",\"elementType\":\"geometry\",\"stylers\":[{\"visibility\":\"on\"},{\"color\":\"#aee2e0\"}]},{\"featureType\":\"landscape\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#abce83\"}]},{\"featureType\":\"poi\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#769E72\"}]},{\"featureType\":\"poi\",\"elementType\":\"labels.text.fill\",\"stylers\":[{\"color\":\"#7B8758\"}]},{\"featureType\":\"poi\",\"elementType\":\"labels.text.stroke\",\"stylers\":[{\"color\":\"#EBF4A4\"}]},{\"featureType\":\"poi.park\",\"elementType\":\"geometry\",\"stylers\":[{\"visibility\":\"simplified\"},{\"color\":\"#8dab68\"}]},{\"featureType\":\"road\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"road\",\"elementType\":\"labels.text.fill\",\"stylers\":[{\"color\":\"#5B5B3F\"}]},{\"featureType\":\"road\",\"elementType\":\"labels.text.stroke\",\"stylers\":[{\"color\":\"#ABCE83\"}]},{\"featureType\":\"road\",\"elementType\":\"labels.icon\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road.local\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#A4C67D\"}]},{\"featureType\":\"road.arterial\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#9BBF72\"}]},{\"featureType\":\"road.highway\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#EBF4A4\"}]},{\"featureType\":\"transit\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"administrative\",\"elementType\":\"geometry.stroke\",\"stylers\":[{\"visibility\":\"on\"},{\"color\":\"#87ae79\"}]},{\"featureType\":\"administrative\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#7f2200\"},{\"visibility\":\"off\"}]},{\"featureType\":\"administrative\",\"elementType\":\"labels.text.stroke\",\"stylers\":[{\"color\":\"#ffffff\"},{\"visibility\":\"on\"},{\"weight\":4.1}]},{\"featureType\":\"administrative\",\"elementType\":\"labels.text.fill\",\"stylers\":[{\"color\":\"#495421\"}]},{\"featureType\":\"administrative.neighborhood\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"off\"}]}]','" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (14, 'Bentley','[{\"featureType\":\"landscape\",\"stylers\":[{\"hue\":\"#F1FF00\"},{\"saturation\":-27.4},{\"lightness\":9.4},{\"gamma\":1}]},{\"featureType\":\"road.highway\",\"stylers\":[{\"hue\":\"#0099FF\"},{\"saturation\":-20},{\"lightness\":36.4},{\"gamma\":1}]},{\"featureType\":\"road.arterial\",\"stylers\":[{\"hue\":\"#00FF4F\"},{\"saturation\":0},{\"lightness\":0},{\"gamma\":1}]},{\"featureType\":\"road.local\",\"stylers\":[{\"hue\":\"#FFB300\"},{\"saturation\":-38},{\"lightness\":11.2},{\"gamma\":1}]},{\"featureType\":\"water\",\"stylers\":[{\"hue\":\"#00B6FF\"},{\"saturation\":4.2},{\"lightness\":-63.4},{\"gamma\":1}]},{\"featureType\":\"poi\",\"stylers\":[{\"hue\":\"#9FFF00\"},{\"saturation\":0},{\"lightness\":0},{\"gamma\":1}]}]','" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (15, 'Bright and Bubbly','[{\"featureType\":\"water\",\"stylers\":[{\"color\":\"#19a0d8\"}]},{\"featureType\":\"administrative\",\"elementType\":\"labels.text.stroke\",\"stylers\":[{\"color\":\"#ffffff\"},{\"weight\":6}]},{\"featureType\":\"administrative\",\"elementType\":\"labels.text.fill\",\"stylers\":[{\"color\":\"#e85113\"}]},{\"featureType\":\"road.highway\",\"elementType\":\"geometry.stroke\",\"stylers\":[{\"color\":\"#efe9e4\"},{\"lightness\":-40}]},{\"featureType\":\"road.arterial\",\"elementType\":\"geometry.stroke\",\"stylers\":[{\"color\":\"#efe9e4\"},{\"lightness\":-20}]},{\"featureType\":\"road\",\"elementType\":\"labels.text.stroke\",\"stylers\":[{\"lightness\":100}]},{\"featureType\":\"road\",\"elementType\":\"labels.text.fill\",\"stylers\":[{\"lightness\":-100}]},{\"featureType\":\"road.highway\",\"elementType\":\"labels.icon\"},{\"featureType\":\"landscape\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"landscape\",\"stylers\":[{\"lightness\":20},{\"color\":\"#efe9e4\"}]},{\"featureType\":\"landscape.man_made\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"water\",\"elementType\":\"labels.text.stroke\",\"stylers\":[{\"lightness\":100}]},{\"featureType\":\"water\",\"elementType\":\"labels.text.fill\",\"stylers\":[{\"lightness\":-100}]},{\"featureType\":\"poi\",\"elementType\":\"labels.text.fill\",\"stylers\":[{\"hue\":\"#11ff00\"}]},{\"featureType\":\"poi\",\"elementType\":\"labels.text.stroke\",\"stylers\":[{\"lightness\":100}]},{\"featureType\":\"poi\",\"elementType\":\"labels.icon\",\"stylers\":[{\"hue\":\"#4cff00\"},{\"saturation\":58}]},{\"featureType\":\"poi\",\"elementType\":\"geometry\",\"stylers\":[{\"visibility\":\"on\"},{\"color\":\"#f0e4d3\"}]},{\"featureType\":\"road.highway\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#efe9e4\"},{\"lightness\":-25}]},{\"featureType\":\"road.arterial\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#efe9e4\"},{\"lightness\":-10}]},{\"featureType\":\"poi\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"simplified\"}]}]','" . self::e2m_version . "');");
            $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (16, 'Nature','[{\"featureType\":\"landscape\",\"stylers\":[{\"hue\":\"#FFA800\"},{\"saturation\":0},{\"lightness\":0},{\"gamma\":1}]},{\"featureType\":\"road.highway\",\"stylers\":[{\"hue\":\"#53FF00\"},{\"saturation\":-73},{\"lightness\":40},{\"gamma\":1}]},{\"featureType\":\"road.arterial\",\"stylers\":[{\"hue\":\"#FBFF00\"},{\"saturation\":0},{\"lightness\":0},{\"gamma\":1}]},{\"featureType\":\"road.local\",\"stylers\":[{\"hue\":\"#00FFFD\"},{\"saturation\":0},{\"lightness\":30},{\"gamma\":1}]},{\"featureType\":\"water\",\"stylers\":[{\"hue\":\"#00BFFF\"},{\"saturation\":6},{\"lightness\":8},{\"gamma\":1}]},{\"featureType\":\"poi\",\"stylers\":[{\"hue\":\"#679714\"},{\"saturation\":33.4},{\"lightness\":-25.4},{\"gamma\":1}]}]','" . self::e2m_version . "');");

            
        } else {

            //does theme exist?
            $arrThemeSearch1 = $wpdb->get_results("SELECT ID FROM `$map_themes_table` WHERE ID = 1");
            if (count($arrThemeSearch1) === 0) {
                $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (1, 'No Theme','','" . self::e2m_version . "');");
            }
            $arrThemeSearch2 = $wpdb->get_results("SELECT ID FROM `$map_themes_table` WHERE ID = 2");
            if (count($arrThemeSearch2) === 0) {
                $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (2, 'Subtle Greyscale','[{\"featureType\":\"landscape\",\"stylers\":[{\"saturation\":-100},{\"lightness\":65},{\"visibility\":\"on\"}]},{\"featureType\":\"poi\",\"stylers\":[{\"saturation\":-100},{\"lightness\":51},{\"visibility\":\"simplified\"}]},{\"featureType\":\"road.highway\",\"stylers\":[{\"saturation\":-100},{\"visibility\":\"simplified\"}]},{\"featureType\":\"road.arterial\",\"stylers\":[{\"saturation\":-100},{\"lightness\":30},{\"visibility\":\"on\"}]},{\"featureType\":\"road.local\",\"stylers\":[{\"saturation\":-100},{\"lightness\":40},{\"visibility\":\"on\"}]},{\"featureType\":\"transit\",\"stylers\":[{\"saturation\":-100},{\"visibility\":\"simplified\"}]},{\"featureType\":\"administrative.province\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"water\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"on\"},{\"lightness\":-25},{\"saturation\":-100}]},{\"featureType\":\"water\",\"elementType\":\"geometry\",\"stylers\":[{\"hue\":\"#ffff00\"},{\"lightness\":-25},{\"saturation\":-97}]}]','" . self::e2m_version . "');");
            }
            $arrThemeSearch3 = $wpdb->get_results("SELECT ID FROM `$map_themes_table` WHERE ID = 3");
            if (count($arrThemeSearch3) === 0) {
               $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (3, 'Blue Essence','[{\"featureType\":\"landscape.natural\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"visibility\":\"on\"},{\"color\":\"#e0efef\"}]},{\"featureType\":\"poi\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"visibility\":\"on\"},{\"hue\":\"#1900ff\"},{\"color\":\"#c0e8e8\"}]},{\"featureType\":\"road\",\"elementType\":\"geometry\",\"stylers\":[{\"lightness\":100},{\"visibility\":\"simplified\"}]},{\"featureType\":\"road\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"transit.line\",\"elementType\":\"geometry\",\"stylers\":[{\"visibility\":\"on\"},{\"lightness\":700}]},{\"featureType\":\"water\",\"elementType\":\"all\",\"stylers\":[{\"color\":\"#7dcdcd\"}]}]','" . self::e2m_version . "');");
            }
            $arrThemeSearch4 = $wpdb->get_results("SELECT ID FROM `$map_themes_table` WHERE ID = 4");
            if (count($arrThemeSearch4) === 0) {
               $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (4, 'Apple Maps-esque','[{\"featureType\":\"landscape.man_made\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#f7f1df\"}]},{\"featureType\":\"landscape.natural\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#d0e3b4\"}]},{\"featureType\":\"landscape.natural.terrain\",\"elementType\":\"geometry\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi.business\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi.medical\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#fbd3da\"}]},{\"featureType\":\"poi.park\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#bde6ab\"}]},{\"featureType\":\"road\",\"elementType\":\"geometry.stroke\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road.highway\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#ffe15f\"}]},{\"featureType\":\"road.highway\",\"elementType\":\"geometry.stroke\",\"stylers\":[{\"color\":\"#efd151\"}]},{\"featureType\":\"road.arterial\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#ffffff\"}]},{\"featureType\":\"road.local\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"black\"}]},{\"featureType\":\"transit.station.airport\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#cfb2db\"}]},{\"featureType\":\"water\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#a2daf2\"}]}]','" . self::e2m_version . "');");
            }
            $arrThemeSearch5 = $wpdb->get_results("SELECT ID FROM `$map_themes_table` WHERE ID = 5");
            if (count($arrThemeSearch5) === 0) {
               $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (5, 'Blue Water','[{\"featureType\":\"administrative\",\"elementType\":\"labels.text.fill\",\"stylers\":[{\"color\":\"#444444\"}]},{\"featureType\":\"landscape\",\"elementType\":\"all\",\"stylers\":[{\"color\":\"#f2f2f2\"}]},{\"featureType\":\"poi\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road\",\"elementType\":\"all\",\"stylers\":[{\"saturation\":-100},{\"lightness\":45}]},{\"featureType\":\"road.highway\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"road.arterial\",\"elementType\":\"labels.icon\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"transit\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"water\",\"elementType\":\"all\",\"stylers\":[{\"color\":\"#46bcec\"},{\"visibility\":\"on\"}]}]','" . self::e2m_version . "');");
            }
            $arrThemeSearch6 = $wpdb->get_results("SELECT ID FROM `$map_themes_table` WHERE ID = 6");
            if (count($arrThemeSearch6) === 0) {
               $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (6, 'Pale Dawn','[{\"featureType\":\"administrative\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"on\"},{\"lightness\":33}]},{\"featureType\":\"landscape\",\"elementType\":\"all\",\"stylers\":[{\"color\":\"#f2e5d4\"}]},{\"featureType\":\"poi.park\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#c5dac6\"}]},{\"featureType\":\"poi.park\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"on\"},{\"lightness\":20}]},{\"featureType\":\"road\",\"elementType\":\"all\",\"stylers\":[{\"lightness\":20}]},{\"featureType\":\"road.highway\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#c5c6c6\"}]},{\"featureType\":\"road.arterial\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#e4d7c6\"}]},{\"featureType\":\"road.local\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#fbfaf7\"}]},{\"featureType\":\"water\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"on\"},{\"color\":\"#acbcc9\"}]}]','" . self::e2m_version . "');");
            }
            $arrThemeSearch7 = $wpdb->get_results("SELECT ID FROM `$map_themes_table` WHERE ID = 7");
            if (count($arrThemeSearch7) === 0) {
               $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (7, 'Retro','[{\"featureType\":\"administrative\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"road\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"water\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"transit\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"landscape\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"road.highway\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road.local\",\"stylers\":[{\"visibility\":\"on\"}]},{\"featureType\":\"road.highway\",\"elementType\":\"geometry\",\"stylers\":[{\"visibility\":\"on\"}]},{\"featureType\":\"water\",\"stylers\":[{\"color\":\"#84afa3\"},{\"lightness\":52}]},{\"stylers\":[{\"saturation\":-17},{\"gamma\":0.36}]},{\"featureType\":\"transit.line\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#3f518c\"}]}]','" . self::e2m_version . "');");
            }
            $arrThemeSearch8 = $wpdb->get_results("SELECT ID FROM `$map_themes_table` WHERE ID = 8");
            if (count($arrThemeSearch8) === 0) {
               $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (8, 'Paper','[{\"featureType\":\"administrative\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"landscape\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"simplified\"},{\"hue\":\"#0066ff\"},{\"saturation\":74},{\"lightness\":100}]},{\"featureType\":\"poi\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"road\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"road.highway\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"off\"},{\"weight\":0.6},{\"saturation\":-85},{\"lightness\":61}]},{\"featureType\":\"road.highway\",\"elementType\":\"geometry\",\"stylers\":[{\"visibility\":\"on\"}]},{\"featureType\":\"road.arterial\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road.local\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"on\"}]},{\"featureType\":\"transit\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"water\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"simplified\"},{\"color\":\"#5f94ff\"},{\"lightness\":26},{\"gamma\":5.86}]}]','" . self::e2m_version . "');");
            }
            $arrThemeSearch9 = $wpdb->get_results("SELECT ID FROM `$map_themes_table` WHERE ID = 9");
            if (count($arrThemeSearch9) === 0) {
               $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (9, 'Gowalla','[{\"featureType\":\"administrative.land_parcel\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"landscape.man_made\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"simplified\"},{\"lightness\":20}]},{\"featureType\":\"road.highway\",\"elementType\":\"geometry\",\"stylers\":[{\"hue\":\"#f49935\"}]},{\"featureType\":\"road.highway\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"road.arterial\",\"elementType\":\"geometry\",\"stylers\":[{\"hue\":\"#fad959\"}]},{\"featureType\":\"road.arterial\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road.local\",\"elementType\":\"geometry\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"road.local\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"transit\",\"elementType\":\"all\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"water\",\"elementType\":\"all\",\"stylers\":[{\"hue\":\"#a1cdfc\"},{\"saturation\":30},{\"lightness\":49}]}]','" . self::e2m_version . "');");
            }
            $arrThemeSearch10 = $wpdb->get_results("SELECT ID FROM `$map_themes_table` WHERE ID = 10");
            if (count($arrThemeSearch10) === 0) {
               $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (10, 'Neutral Blue','[{\"featureType\":\"water\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#193341\"}]},{\"featureType\":\"landscape\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#2c5a71\"}]},{\"featureType\":\"road\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#29768a\"},{\"lightness\":-37}]},{\"featureType\":\"poi\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#406d80\"}]},{\"featureType\":\"transit\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#406d80\"}]},{\"elementType\":\"labels.text.stroke\",\"stylers\":[{\"visibility\":\"on\"},{\"color\":\"#3e606f\"},{\"weight\":2},{\"gamma\":0.84}]},{\"elementType\":\"labels.text.fill\",\"stylers\":[{\"color\":\"#ffffff\"}]},{\"featureType\":\"administrative\",\"elementType\":\"geometry\",\"stylers\":[{\"weight\":0.6},{\"color\":\"#1a3541\"}]},{\"elementType\":\"labels.icon\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi.park\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#2c5a71\"}]}]','" . self::e2m_version . "');");
            }
            $arrThemeSearch11 = $wpdb->get_results("SELECT ID FROM `$map_themes_table` WHERE ID = 11");
            if (count($arrThemeSearch11) === 0) {
                $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (11, 'MapBox','[{\"featureType\":\"water\",\"stylers\":[{\"saturation\":43},{\"lightness\":-11},{\"hue\":\"#0088ff\"}]},{\"featureType\":\"road\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"hue\":\"#ff0000\"},{\"saturation\":-100},{\"lightness\":99}]},{\"featureType\":\"road\",\"elementType\":\"geometry.stroke\",\"stylers\":[{\"color\":\"#808080\"},{\"lightness\":54}]},{\"featureType\":\"landscape.man_made\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#ece2d9\"}]},{\"featureType\":\"poi.park\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#ccdca1\"}]},{\"featureType\":\"road\",\"elementType\":\"labels.text.fill\",\"stylers\":[{\"color\":\"#767676\"}]},{\"featureType\":\"road\",\"elementType\":\"labels.text.stroke\",\"stylers\":[{\"color\":\"#ffffff\"}]},{\"featureType\":\"poi\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"landscape.natural\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"visibility\":\"on\"},{\"color\":\"#b8cb93\"}]},{\"featureType\":\"poi.park\",\"stylers\":[{\"visibility\":\"on\"}]},{\"featureType\":\"poi.sports_complex\",\"stylers\":[{\"visibility\":\"on\"}]},{\"featureType\":\"poi.medical\",\"stylers\":[{\"visibility\":\"on\"}]},{\"featureType\":\"poi.business\",\"stylers\":[{\"visibility\":\"simplified\"}]}]','" . self::e2m_version . "');");
            } 
            $arrThemeSearch12 = $wpdb->get_results("SELECT ID FROM `$map_themes_table` WHERE ID = 12");
            if (count($arrThemeSearch12) === 0) {
                $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (12, 'becomeadinosaur','[{\"elementType\":\"labels.text\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"landscape.natural\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#f5f5f2\"},{\"visibility\":\"on\"}]},{\"featureType\":\"administrative\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"transit\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi.attraction\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"landscape.man_made\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#ffffff\"},{\"visibility\":\"on\"}]},{\"featureType\":\"poi.business\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi.medical\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi.place_of_worship\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi.school\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi.sports_complex\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road.highway\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#ffffff\"},{\"visibility\":\"simplified\"}]},{\"featureType\":\"road.arterial\",\"stylers\":[{\"visibility\":\"simplified\"},{\"color\":\"#ffffff\"}]},{\"featureType\":\"road.highway\",\"elementType\":\"labels.icon\",\"stylers\":[{\"color\":\"#ffffff\"},{\"visibility\":\"off\"}]},{\"featureType\":\"road.highway\",\"elementType\":\"labels.icon\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road.arterial\",\"stylers\":[{\"color\":\"#ffffff\"}]},{\"featureType\":\"road.local\",\"stylers\":[{\"color\":\"#ffffff\"}]},{\"featureType\":\"poi.park\",\"elementType\":\"labels.icon\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"poi\",\"elementType\":\"labels.icon\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"water\",\"stylers\":[{\"color\":\"#71c8d4\"}]},{\"featureType\":\"landscape\",\"stylers\":[{\"color\":\"#e5e8e7\"}]},{\"featureType\":\"poi.park\",\"stylers\":[{\"color\":\"#8ba129\"}]},{\"featureType\":\"road\",\"stylers\":[{\"color\":\"#ffffff\"}]},{\"featureType\":\"poi.sports_complex\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#c7c7c7\"},{\"visibility\":\"off\"}]},{\"featureType\":\"water\",\"stylers\":[{\"color\":\"#a0d3d3\"}]},{\"featureType\":\"poi.park\",\"stylers\":[{\"color\":\"#91b65d\"}]},{\"featureType\":\"poi.park\",\"stylers\":[{\"gamma\":1.51}]},{\"featureType\":\"road.local\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road.local\",\"elementType\":\"geometry\",\"stylers\":[{\"visibility\":\"on\"}]},{\"featureType\":\"poi.government\",\"elementType\":\"geometry\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"landscape\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road.arterial\",\"elementType\":\"geometry\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"road.local\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"road\"},{\"featureType\":\"road\"},{},{\"featureType\":\"road.highway\"}]','" . self::e2m_version . "');");
            }
            $arrThemeSearch13 = $wpdb->get_results("SELECT ID FROM `$map_themes_table` WHERE ID = 13");
            if (count($arrThemeSearch13) === 0) {
                $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (13, 'Avocado World','[{\"featureType\":\"water\",\"elementType\":\"geometry\",\"stylers\":[{\"visibility\":\"on\"},{\"color\":\"#aee2e0\"}]},{\"featureType\":\"landscape\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#abce83\"}]},{\"featureType\":\"poi\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#769E72\"}]},{\"featureType\":\"poi\",\"elementType\":\"labels.text.fill\",\"stylers\":[{\"color\":\"#7B8758\"}]},{\"featureType\":\"poi\",\"elementType\":\"labels.text.stroke\",\"stylers\":[{\"color\":\"#EBF4A4\"}]},{\"featureType\":\"poi.park\",\"elementType\":\"geometry\",\"stylers\":[{\"visibility\":\"simplified\"},{\"color\":\"#8dab68\"}]},{\"featureType\":\"road\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"visibility\":\"simplified\"}]},{\"featureType\":\"road\",\"elementType\":\"labels.text.fill\",\"stylers\":[{\"color\":\"#5B5B3F\"}]},{\"featureType\":\"road\",\"elementType\":\"labels.text.stroke\",\"stylers\":[{\"color\":\"#ABCE83\"}]},{\"featureType\":\"road\",\"elementType\":\"labels.icon\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"road.local\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#A4C67D\"}]},{\"featureType\":\"road.arterial\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#9BBF72\"}]},{\"featureType\":\"road.highway\",\"elementType\":\"geometry\",\"stylers\":[{\"color\":\"#EBF4A4\"}]},{\"featureType\":\"transit\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"administrative\",\"elementType\":\"geometry.stroke\",\"stylers\":[{\"visibility\":\"on\"},{\"color\":\"#87ae79\"}]},{\"featureType\":\"administrative\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#7f2200\"},{\"visibility\":\"off\"}]},{\"featureType\":\"administrative\",\"elementType\":\"labels.text.stroke\",\"stylers\":[{\"color\":\"#ffffff\"},{\"visibility\":\"on\"},{\"weight\":4.1}]},{\"featureType\":\"administrative\",\"elementType\":\"labels.text.fill\",\"stylers\":[{\"color\":\"#495421\"}]},{\"featureType\":\"administrative.neighborhood\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"off\"}]}]','" . self::e2m_version . "');");
            }
            $arrThemeSearch14 = $wpdb->get_results("SELECT ID FROM `$map_themes_table` WHERE ID = 14");
            if (count($arrThemeSearch14) === 0) {
                $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (14, 'Bentley','[{\"featureType\":\"landscape\",\"stylers\":[{\"hue\":\"#F1FF00\"},{\"saturation\":-27.4},{\"lightness\":9.4},{\"gamma\":1}]},{\"featureType\":\"road.highway\",\"stylers\":[{\"hue\":\"#0099FF\"},{\"saturation\":-20},{\"lightness\":36.4},{\"gamma\":1}]},{\"featureType\":\"road.arterial\",\"stylers\":[{\"hue\":\"#00FF4F\"},{\"saturation\":0},{\"lightness\":0},{\"gamma\":1}]},{\"featureType\":\"road.local\",\"stylers\":[{\"hue\":\"#FFB300\"},{\"saturation\":-38},{\"lightness\":11.2},{\"gamma\":1}]},{\"featureType\":\"water\",\"stylers\":[{\"hue\":\"#00B6FF\"},{\"saturation\":4.2},{\"lightness\":-63.4},{\"gamma\":1}]},{\"featureType\":\"poi\",\"stylers\":[{\"hue\":\"#9FFF00\"},{\"saturation\":0},{\"lightness\":0},{\"gamma\":1}]}]','" . self::e2m_version . "');");
            }
            $arrThemeSearch15 = $wpdb->get_results("SELECT ID FROM `$map_themes_table` WHERE ID = 15");
            if (count($arrThemeSearch15) === 0) {
                $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (15, 'Bright and Bubbly','[{\"featureType\":\"water\",\"stylers\":[{\"color\":\"#19a0d8\"}]},{\"featureType\":\"administrative\",\"elementType\":\"labels.text.stroke\",\"stylers\":[{\"color\":\"#ffffff\"},{\"weight\":6}]},{\"featureType\":\"administrative\",\"elementType\":\"labels.text.fill\",\"stylers\":[{\"color\":\"#e85113\"}]},{\"featureType\":\"road.highway\",\"elementType\":\"geometry.stroke\",\"stylers\":[{\"color\":\"#efe9e4\"},{\"lightness\":-40}]},{\"featureType\":\"road.arterial\",\"elementType\":\"geometry.stroke\",\"stylers\":[{\"color\":\"#efe9e4\"},{\"lightness\":-20}]},{\"featureType\":\"road\",\"elementType\":\"labels.text.stroke\",\"stylers\":[{\"lightness\":100}]},{\"featureType\":\"road\",\"elementType\":\"labels.text.fill\",\"stylers\":[{\"lightness\":-100}]},{\"featureType\":\"road.highway\",\"elementType\":\"labels.icon\"},{\"featureType\":\"landscape\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"landscape\",\"stylers\":[{\"lightness\":20},{\"color\":\"#efe9e4\"}]},{\"featureType\":\"landscape.man_made\",\"stylers\":[{\"visibility\":\"off\"}]},{\"featureType\":\"water\",\"elementType\":\"labels.text.stroke\",\"stylers\":[{\"lightness\":100}]},{\"featureType\":\"water\",\"elementType\":\"labels.text.fill\",\"stylers\":[{\"lightness\":-100}]},{\"featureType\":\"poi\",\"elementType\":\"labels.text.fill\",\"stylers\":[{\"hue\":\"#11ff00\"}]},{\"featureType\":\"poi\",\"elementType\":\"labels.text.stroke\",\"stylers\":[{\"lightness\":100}]},{\"featureType\":\"poi\",\"elementType\":\"labels.icon\",\"stylers\":[{\"hue\":\"#4cff00\"},{\"saturation\":58}]},{\"featureType\":\"poi\",\"elementType\":\"geometry\",\"stylers\":[{\"visibility\":\"on\"},{\"color\":\"#f0e4d3\"}]},{\"featureType\":\"road.highway\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#efe9e4\"},{\"lightness\":-25}]},{\"featureType\":\"road.arterial\",\"elementType\":\"geometry.fill\",\"stylers\":[{\"color\":\"#efe9e4\"},{\"lightness\":-10}]},{\"featureType\":\"poi\",\"elementType\":\"labels\",\"stylers\":[{\"visibility\":\"simplified\"}]}]','" . self::e2m_version . "');");
            }
            $arrThemeSearch16 = $wpdb->get_results("SELECT ID FROM `$map_themes_table` WHERE ID = 16");
            if (count($arrThemeSearch16) === 0) {
                $wpdb->query("INSERT INTO `$map_themes_table` (ID, ThemeName, Styles, Version) VALUES (16, 'Nature','[{\"featureType\":\"landscape\",\"stylers\":[{\"hue\":\"#FFA800\"},{\"saturation\":0},{\"lightness\":0},{\"gamma\":1}]},{\"featureType\":\"road.highway\",\"stylers\":[{\"hue\":\"#53FF00\"},{\"saturation\":-73},{\"lightness\":40},{\"gamma\":1}]},{\"featureType\":\"road.arterial\",\"stylers\":[{\"hue\":\"#FBFF00\"},{\"saturation\":0},{\"lightness\":0},{\"gamma\":1}]},{\"featureType\":\"road.local\",\"stylers\":[{\"hue\":\"#00FFFD\"},{\"saturation\":0},{\"lightness\":30},{\"gamma\":1}]},{\"featureType\":\"water\",\"stylers\":[{\"hue\":\"#00BFFF\"},{\"saturation\":6},{\"lightness\":8},{\"gamma\":1}]},{\"featureType\":\"poi\",\"stylers\":[{\"hue\":\"#679714\"},{\"saturation\":33.4},{\"lightness\":-25.4},{\"gamma\":1}]}]','" . self::e2m_version . "');");
            }
        }

    }

    /**     * Create custom post-type menu */
    public static function create_admin_menu() {
        add_menu_page('My Easy2Maps', // page title
                'Easy2Map', // menu title
                'manage_options', // capability 
                self::admin_menu_slug, // menu slug 
                'Easy2Map::get_admin_page', // callback 
                plugins_url('images/e2m_favicon2020.png', dirname(__FILE__)) //default icon
        );
    }

    /** Prints the administration page for this plugin. */
    public static function get_admin_page() {

        if (isset($_GET["action"]) && strcasecmp($_GET["action"], "addeditpins") == 0 && isset($_GET["map_id"])) {
            include('AddEditMapPins.php');
        } else if (isset($_GET["action"]) && strcasecmp($_GET["action"], "edit") == 0 && isset($_GET["map_id"])) {
            include('MapAdmin.php');
        } else if (isset($_GET["action"]) && strcasecmp($_GET["action"], "mappinimagesave") == 0 && isset($_GET["map_id"])) {
            include('MapPinImageSave.php');
        } else if (isset($_GET["action"]) && strcasecmp($_GET["action"], "mappreview") == 0 && isset($_GET["map_id"])) {
            include('MapPreview.php');
        } else if (isset($_GET["action"]) && strcasecmp($_GET["action"], "mapexport") == 0 && isset($_GET["map_id"])) {
            include('MapExport.php');
        } else if (isset($_GET["action"]) && strcasecmp($_GET["action"], "mapimport") == 0 && isset($_GET["map_id"])) {
            include('MapImport.php');
		} else if (isset($_GET["action"]) && strcasecmp($_GET["action"], "mapimportcsv") == 0 && isset($_GET["map_id"])) {
            include('MapImportCSV.php');
        } else if (isset($_GET["action"]) && strcasecmp($_GET["action"], "mapimportcsv2") == 0 && isset($_GET["map_id"])) {
            include('MapImportCSV2.php');
        } else if (isset($_GET["action"]) && strcasecmp($_GET["action"], "activation") == 0) {
            include('Validation.php');
        } else {
            include('MapManager.php');
        }
    }

    private static function easy2MapCodeValidator($code) {

        $validation = true;
        $string = substr($code, 32, -32);
        $pie_1 = substr($code, 0, 32);
        $pie_2 = substr($code, -32, 32);
        //get integers + characters in string
        $regex_first = "/[0-9]+/";
        $regex_second = "/[A-z]+/";
        preg_match_all($regex_first, $string, $integers);
        preg_match_all($regex_second, $string, $characters);
        //divide integer by key number
        $integer1 = $integers[0][0] / 3137831;
        $integer2 = $integers[0][1] / 7713;
        //validate integers
        $regex_decimal = "/[.]/";
        if (preg_match($regex_decimal, $integers[0][0]) || $integer1 <= 2211 || $integer1 >= 5353 || preg_match($regex_decimal, $integers[0][1]) || $integer2 <= 1001 || $integer2 >= 10201) {
            $validation = false;
        }
        //validate characters
        $regex_characters1 = "/[^ACEGIKMOQSUWY]/";
        $regex_characters2 = "/[^bdfhjlnprtvxz]/";
        if (preg_match($regex_characters1, $characters[0][0]) || preg_match($regex_characters2, $characters[0][1])) {
            $validation = false;
        }
        //validate MD5
        $val_1 = $string[3] . $string[15] . $string[7] . $string[4] . $string[9] . $string[13] . $string . $string[2] . $string[1] * $string[3];
        $val_2 = $string[5] . $string[1] . $string . $string[17] . $string[8] . $string[7] . $string[11] . $string[6] . $string[4] * $string[2];
        if (md5($val_1) != $pie_1 || md5($val_2) != $pie_2) {
            $validation = false;
        }
        return $validation;
    }

    /** Validation page. */
    public static function easy2map_admin_validation() {
        include('Validation.php');
    }

    /**
     * * _is_searchable_page * 
     * * Any page that's not in the WP admin area is considered searchable. 
     * * @return boolean Simple true/false as to whether the current page is searchable. */
    private static function _is_searchable_page() {
        if (is_admin()) {
            return false;
        } else {
            return true;
        }
    }

    public static function retrieve_map($raw_args, $content = null) {
        $defaults = array('id' => '',);
        $sanitized_args = shortcode_atts($defaults, $raw_args);
        if (empty($sanitized_args['id'])) {
            return '';
        }

        $mapHTML = Easy2Map_MapFunctions::Retrieve_map_HTML($sanitized_args['id']);
        $mapHTML = str_ireplace("divMap", "easy2map_canvas_" . $sanitized_args['id'], $mapHTML);

        return '
            <style type="text/css">
            #easy2map_canvas_' . $sanitized_args['id'] . ' img {
                max-width: none !important;
                border-radius: 0px !important;
                box-shadow: 0 0 0 transparent !important;
            } 
            
            #tdMap{
                 border-top: 0px solid #ddd !important;
            }
            
            #tdPinList{
                 border-top: 0px solid #ddd !important;
            }

            #easy2map_canvas_' . $sanitized_args['id'] . ' table,td {
              margin: -1.5em 0 !important;
              padding: 0px !important;
            }
            #tblEasy2MapPinList td, tr{border-width:0px;}
            #tblEasy2MapPinList td{padding:3px !important;}
            #tblEasy2MapPinList{border-width:0px;}
            #tblEasy2MapPinList img {max-width: none !important;border-radius: 0px !important;border-width:0px;box-shadow: 0 0 0 transparent !important;}
            
            #tblEasy2MapPinList1 td, tr{border-width:0px;}
            #tblEasy2MapPinList1 td{padding:3px !important;}
            #tblEasy2MapPinList1{border-width:0px;}
            #tblEasy2MapPinList1 img {max-width: none !important;border-radius: 0px !important;border-width:0px;box-shadow: 0 0 0 transparent !important;}
            
            #tblEasy2MapPinList2 td, tr{border-width:0px;}
            #tblEasy2MapPinList2 td{padding:3px !important;}
            #tblEasy2MapPinList2{border-width:0px;}
            #tblEasy2MapPinList2 img {max-width: none !important;border-radius: 0px !important;border-width:0px;box-shadow: 0 0 0 transparent !important;}
            
            #tblEasy2MapPinList3 td, tr{border-width:0px;}
            #tblEasy2MapPinList3 td{padding:3px !important;}
            #tblEasy2MapPinList3{border-width:0px;}
            #tblEasy2MapPinList3 img {max-width: none !important;border-radius: 0px !important;border-width:0px;box-shadow: 0 0 0 transparent !important;}
            
            #tblEasy2MapPinList4 td, tr{border-width:0px;}
            #tblEasy2MapPinList4 td{padding:3px !important;}
            #tblEasy2MapPinList4{border-width:0px;}
            #tblEasy2MapPinList4 img {max-width: none !important;border-radius: 0px !important;border-width:0px;box-shadow: 0 0 0 transparent !important;}
            

            #ulEasy2MapPinList li{
                border-width:0px;
                padding:3px !important;
            }

            #ulEasy2MapPinList{
                border-width:0px;
            }
            
             #ulEasy2MapPinList img {
                max-width: none !important;
                border-radius: 0px !important;
                border-width:0px;
                box-shadow: 0 0 0 transparent !important;
            }
            
            #ulEasy2MapPinList table, td, tr{
                border-width:0px;
            }
            #ulEasy2MapPinList td{
                padding:3px !important;
            }
            #ulEasy2MapPinList td{
                border-width:0px;
            }
            
            #easy2mapIimgShadow{
            max-width: none !important;
                border-radius: 0px !important;
                border-width:0px;
                box-shadow: 0 0 0 transparent !important;
            }

            </style><input type="hidden" id="easy2map_ajax_url_' . $sanitized_args['id'] . '" value="' . admin_url("admin-ajax.php") . '">' . $mapHTML;
    }

    public static function getLocation($address){

        try{

            $url = self::$url.urlencode($address);
            
            $resp_json = self::curl_file_get_contents($url);
            $resp = json_decode($resp_json, true);

            if($resp['status']='OK'){
               return $resp['results'][0]['geometry']['location'];
            }else{
                return false;
            }

        } catch(Exception $e){
            return false;
        }
    }


    private static function curl_file_get_contents($URL){

        try{

            $c = curl_init();
            curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($c, CURLOPT_URL, $URL);
            $contents = curl_exec($c);
            curl_close($c);

        if ($contents) return $contents;
            else return FALSE;
        } catch(Exception $e){
            return FALSE;
        }
    }
}

?>