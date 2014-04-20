var easy2map_map_functions = (function() {

    displayGoogleMap = function() {

        var settings = jQuery.xml2json($mapSettings.Settings);
        var $lat = settings.lattitude;
        var $lng = settings.longitude;
        var $zoom = parseInt(settings.zoom);

        if ($map != null) {
            $lat = $map.getCenter().lat();
            $lng = $map.getCenter().lng();
            $zoom = $map.getZoom();
        }

        var mapOptions = retrieveMapOptions($lat, $lng, $zoom);

        $map = new google.maps.Map(document.getElementById("divMap"), mapOptions);
        $overlay = new google.maps.OverlayView();
        $overlay.draw = function() {
        };
        $overlay.setMap($map);

        if (mapOptions.trafficlayer) {
            var trafficLayer = new google.maps.TrafficLayer();
            trafficLayer.setMap($map);
        }

        if (mapOptions.transitlayer) {
            var transitLayer = new google.maps.TransitLayer();
            transitLayer.setMap($map);
        }

        if (mapOptions.bicyclelayer) {
            var bicycleLayer = new google.maps.BicyclingLayer();
            bicycleLayer.setMap($map);
        }

    };

    //change 'border_width' into 'Border Width', for example
    normaliseCSSElement = function(item) {
        item = replaceAll(item, "_", " ");
        item = item.toLowerCase().replace(/\b[a-z]/g, function(letter) {
            return letter.toUpperCase();
        });
        return item;
    };

    //refresh the example map
    refreshExampleMap = function(saveMapToDatabase) {

        var templateID = parseInt(jQuery("#MapTemplateName").val());

        for (var t = 0; t < $arrTemplates.length; t++) {

            var template = $arrTemplates[t];

            if (parseInt(template.ID) == templateID) {

                jQuery('#divPreview').html(replaceAll(template.TemplateHTML, '[siteurl]', $pluginsURL));

                if (parseInt(template.StyleParentOnly) === 0) {

                    jQuery('[id ^= styleElement]').each(function() {
                        jQuery('#divMap').css(replaceAll(jQuery(this).attr('item'), "_", "-"), jQuery(this).attr('value'));
                    });

                } else {

                    jQuery('[id ^= styleElement]').each(function() {

                        if (jQuery(this).attr('item') === "height") {

                            if (!!jQuery("#divPinList2")) {
                                jQuery('#divMap').css(replaceAll(jQuery(this).attr('item'), "_", "-"), jQuery(this).attr('value'));
                            } else {

                                jQuery("#divMap").height('200px');
                                jQuery("#divPinList").height('186px');
                            }
                        }

                        jQuery('#divMapParent').css(replaceAll(jQuery(this).attr('item'), "_", "-"), jQuery(this).attr('value'));
                    });

                    if (!!jQuery("#divPinList2") === false) {
                        jQuery("#divMap").height(jQuery("#divMapParent").height());
                        jQuery("#divPinList").height(jQuery("#divMapParent").height() - 14);
                    }

                    jQuery("#easy2mapIimgShadow").width(jQuery("#divMapParent").width());
                    jQuery("#easy2mapIimgShadow").width(jQuery("#divMapParent").width());
                    jQuery("#ulEasy2MapPinList").width(jQuery("#divMapParent").width());
                    jQuery("#divMapHeading").html(jQuery('#mapName2').html() === "" ? "Untitled Map" : jQuery('#mapName2').html());
                    //jQuery("#easy2mapIimgShadow").css('marginLeft', jQuery("#divMapParent").css('marginLeft'));
                    //jQuery("#easy2mapIimgShadow").css('marginRight', jQuery("#divMapParent").css('marginRight'));
                }

                jQuery('[id ^= styleList]').each(function() {
                    jQuery('#divPinList').css(replaceAll(jQuery(this).attr('item'), "_", "-"), jQuery(this).attr('value'));
                    jQuery('#divPinList2').css(replaceAll(jQuery(this).attr('item'), "_", "-"), jQuery(this).attr('value'));
                });

                jQuery('[id ^= styleHeading]').each(function() {
                    jQuery('#divMapHeading').css(replaceAll(jQuery(this).attr('item'), "_", "-"), jQuery(this).attr('value'));
                });
            }
        }

        //save the map to the database if required to do so
        if (!!saveMapToDatabase) {

            easy2map_map_functions.saveMap(false, false);

        }

        displayGoogleMap();

        //google.maps.event.addListenerOnce($map, 'idle', function(){
        //    //once the map is loaded, retrieve the map pins
        //    easy2map_mappin_functions.retrieveMapPoints();
        //});

        google.maps.event.addDomListener(window, 'load', easy2map_mappin_functions.retrieveMapPoints());

    };

    //retrieve map heading CSS elements for saving to the database
    retrieveHeadingCSS = function() {

        var arrCSS = [];

        jQuery('[id ^= styleHeading]').each(function() {
            var item = replaceAll(jQuery(this).attr('item'), "_", "-");
            arrCSS[item] = jQuery(this).attr('value');
        });

        var options = {
            formatOutput: true,
            rootTagName: 'settings'
        };
        return jQuery.json2xml(arrCSS, options);

    };

    //retrieve map pin list CSS elements for saving to the database
    retrieveListCSS = function() {

        var arrCSS = [];

        jQuery('[id ^= styleList]').each(function() {
            var item = replaceAll(jQuery(this).attr('item'), "_", "-");
            arrCSS[item] = jQuery(this).attr('value');
        });

        var options = {
            formatOutput: true,
            rootTagName: 'settings'
        };
        return jQuery.json2xml(arrCSS, options);

    };

    //retrieve map's CSS elements for saving to the database
    retrieveMapCSS = function() {

        var arrCSS = [];

        jQuery('[id ^= styleElement]').each(function() {
            var item = replaceAll(jQuery(this).attr('item'), "_", "-");
            arrCSS[item] = jQuery(this).attr('value');
        });

        var options = {
            formatOutput: true,
            rootTagName: 'settings'
        };
        return jQuery.json2xml(arrCSS, options);

    };

    //retrieve the map settings for rendering/saving the map
    retrieveMapOptions = function($lat, $lng, $zoom) {

        var $mapType = jQuery('#mapType').val().toUpperCase();
        if ($mapType === "ROADMAP")
            $mapType = google.maps.MapTypeId.ROADMAP;
        else if ($mapType === "HYBRID")
            $mapType = google.maps.MapTypeId.HYBRID;
        else if ($mapType === "SATELLITE")
            $mapType = google.maps.MapTypeId.SATELLITE;
        else if ($mapType === "TERRAIN")
            $mapType = google.maps.MapTypeId.TERRAIN;
        else
            $mapType = google.maps.MapTypeId.ROADMAP;

        var $mapTypeControl_style = google.maps.MapTypeControlStyle.DEFAULT;
        var $mapTypeControl_position = google.maps.ControlPosition.TOP_LEFT;
        var $latlng = new google.maps.LatLng($lat, $lng);

        var mapOptions = {
            zoom: $zoom,
            center: $latlng,
            mapTypeId: $mapType,
            mapTypeControl: false,
            clusterpins: false,
            trafficlayer: false,
            transitlayer: false,
            bicyclelayer: false,
            polyline_strokecolor: '000000',
            polyline_opacity: '1.0',
            polyline_strokeweight: '1',
            mapTypeControlOptions: {
                style: $mapTypeControl_style,
                position: $mapTypeControl_position
            },
            zoomControl: true,
            navigationControl: true,
            zoomControlOptions: {
                style: google.maps.ZoomControlStyle.SMALL,
                position: google.maps.ControlPosition.LEFT_TOP
            },
            scaleControl: false,
            scaleControlOptions: {
                position: google.maps.ControlPosition.TOP_LEFT
            },
            streetViewControl: true,
            panControl: false,
            draggable: true
        };

        return mapOptions;

    };

    //retrieve all map templates from the database
    retrieveMapTemplates = function(mapID, templateID) {

        jQuery.ajax({
            type: 'POST',
            url: ajaxurl,
            dataType: 'json',
            data: {
                mapID: mapID,
                action: "retrieve_map_templates"
            },
            success: function(arrTemplates) {

                $arrTemplates = arrTemplates;

                for (var t = 0; t < arrTemplates.length; t++) {
                    jQuery("#MapTemplateName").append("<option value='" + arrTemplates[t].ID + "'>" + arrTemplates[t].TemplateName + "</option>");
                }

                if (parseInt(templateID) === 0) {
                    //this is a new map - set it to the first template in the list
                    jQuery("#MapTemplateName").val(arrTemplates[0].ID);
                } else {
                    jQuery("#MapTemplateName").val(templateID);
                }

                easy2map_map_functions.changeMapTemplate();
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                alert(errorThrown);
            }
        });

    };

    return {
        //Change a single CSS value
        changeElementValue: function(i, element, proVersion) {

            if (proVersion === false)
                return;

            var attribute, value, type = "px";

            if (element === 1) {
                attribute = jQuery('#styleElement' + i).attr('item');
                value = jQuery('#styleElement' + i).attr('value');
                if (value.indexOf("px") !== -1) {
                    type = "px";
                } else if (value.indexOf("em") !== -1) {
                    type = "em";
                }
                value = replaceAll(replaceAll(replaceAll(value, "em", ""), "px", ""), "#", "");
            } else if (element === 2) {
                attribute = jQuery('#styleList' + i).attr('item');
                value = jQuery('#styleList' + i).attr('value');
                if (value.indexOf("px") !== -1) {
                    type = "px";
                } else if (value.indexOf("em") !== -1) {
                    type = "em";
                }
                value = replaceAll(replaceAll(replaceAll(value, "em", ""), "px", ""), "#", "");
            } else if (element === 3) {
                attribute = jQuery('#styleHeading' + i).attr('item');
                value = jQuery('#styleHeading' + i).attr('value');
                if (value.indexOf("px") !== -1) {
                    type = "px";
                } else if (value.indexOf("em") !== -1) {
                    type = "em";
                }
                value = replaceAll(replaceAll(replaceAll(value, "em", ""), "px", ""), "#", "");
            }

            $styleElementIndex = i;
            $styleSelectedElement = element;
            jQuery('div[id ^= div_edit_]').hide();

            switch (attribute) {

                case "border_style":
                    {
                        jQuery('#tdheading_style').html("Border Style");
                        jQuery('#div_edit_style').modal();
                        jQuery('#txtDefaultValue_style').val(value).focus();
                        break;
                    }

                case "font_family":
                    {
                        jQuery('#tdheading_fontfamily').html("Font Family");
                        jQuery('#div_edit_fontfamily').modal();
                        jQuery('#txtDefaultValue_fontfamily').val(value).focus();
                        break;
                    }

                case "border_width":
                    {

                        jQuery('#tdheading_pixel').html("Border Width");
                        //jQuery('#txtDefaultValue_pixel').find('option').remove();
                        //for (var i = parseInt(0); i <= parseInt(50); i++)
                        //    jQuery('#txtDefaultValue_pixel').append('<option value="' + i + '">' + i + '</option>');
                        jQuery('#div_edit_pixel').modal();
                        jQuery('#txtDefaultValue_pixel').val(value).focus();
                        jQuery('#txtDefaultValue_pixel_em').val(type).prop('disabled', false);
                        break;
                    }

                case "font_size":
                    {
                        jQuery('#tdheading_pixel').html("Font Size");
                        //jQuery('#txtDefaultValue_pixel').find('option').remove();
                        //for (var i = parseInt(5); i <= parseInt(25); i++)
                        //    jQuery('#txtDefaultValue_pixel').append('<option value="' + i + '">' + i + '</option>');
                        jQuery('#div_edit_pixel').modal();
                        jQuery('#txtDefaultValue_pixel').val(value).focus();
                        jQuery('#txtDefaultValue_pixel_em').val(type).prop('disabled', false);
                        break;
                    }

                case "padding":
                    {
                        jQuery('#tdheading_pixel').html("Padding");
                        //jQuery('#txtDefaultValue_pixel').find('option').remove();
                        //for (var i = parseInt(10); i <= parseInt(2000); i++)
                        //    jQuery('#txtDefaultValue_pixel').append('<option value="' + i + '">' + i + '</option>');
                        jQuery('#div_edit_pixel').modal();
                        jQuery('#txtDefaultValue_pixel').val(value).focus();
                        jQuery('#txtDefaultValue_pixel_em').val(type).prop('disabled', false);
                        break;
                    }

                case "text_align":
                    {
                        jQuery('#tdheading_textalign').html("Text Align");
                        jQuery('#div_edit_textalign').modal();
                        jQuery('#txtDefaultValue_textalign').val(value).focus();
                        break;
                    }

                case "width":
                    {

                        if (value.indexOf("%") !== -1) {

                            //percentage-based width
                            jQuery('#tdheading_percentage').html("Width");
                            jQuery('#txtDefaultValue_percentage').find('option').remove();
                            for (var i = parseInt(1); i <= parseInt(110); i++)
                                jQuery('#txtDefaultValue_percentage').append('<option value="' + i + '">' + i + '</option>');
                            jQuery('#div_edit_percentage').modal();
                            jQuery('#txtDefaultValue_percentage').val(replaceAll(value, "%", "")).focus();

                        } else {

                            //pixel-based width
                            jQuery('#tdheading_pixel').html("Width");
                            jQuery('#div_edit_pixel').modal();
                            jQuery('#txtDefaultValue_pixel').val(value).focus();
                            jQuery('#txtDefaultValue_pixel_em').val('px').prop('disabled', 'disabled');
                        }


                        break;
                    }

                case "height":
                    {

                        jQuery('#tdheading_pixel').html("Height");
                        //jQuery('#txtDefaultValue_pixel').find('option').remove();
                        //for (var i = parseInt(10); i <= parseInt(2000); i++)
                        //    jQuery('#txtDefaultValue_pixel').append('<option value="' + i + '">' + i + '</option>');
                        jQuery('#div_edit_pixel').modal();
                        jQuery('#txtDefaultValue_pixel').val(value).focus();
                        jQuery('#txtDefaultValue_pixel_em').val('px').prop('disabled', 'disabled');
                        break;
                    }

                case "max_height":
                    {

                        jQuery('#tdheading_pixel').html("Minimum Height");
                        //jQuery('#txtDefaultValue_pixel').find('option').remove();
                        //for (var i = parseInt(10); i <= parseInt(2000); i++)
                        //    jQuery('#txtDefaultValue_pixel').append('<option value="' + i + '">' + i + '</option>');
                        jQuery('#div_edit_pixel').modal();
                        jQuery('#txtDefaultValue_pixel').val(value).focus();
                        jQuery('#txtDefaultValue_pixel_em').val('px').prop('disabled', 'disabled');
                        break;
                    }

                case "margin_bottom":
                    {

                        jQuery('#tdheading_margin').html("Bottom Margin");
                        jQuery('#txtDefaultValue_margin').find('option').remove();
                        jQuery('#txtDefaultValue_margin').append('<option value="auto">auto</option>');
                        for (var i = parseInt(0); i <= parseInt(500); i++)
                            jQuery('#txtDefaultValue_margin').append('<option value="' + i + '">' + i + '</option>');
                        jQuery('#div_edit_margin').modal();
                        jQuery('#txtDefaultValue_margin').val(value).focus();
                        break;
                    }

                case "margin_top":
                    {

                        jQuery('#tdheading_margin').html("Top Margin");
                        jQuery('#txtDefaultValue_margin').find('option').remove();
                        jQuery('#txtDefaultValue_margin').append('<option value="auto">auto</option>');
                        for (var i = parseInt(0); i <= parseInt(500); i++)
                            jQuery('#txtDefaultValue_margin').append('<option value="' + i + '">' + i + '</option>');
                        jQuery('#div_edit_margin').modal();
                        jQuery('#txtDefaultValue_margin').val(value).focus();
                        break;
                    }

                case "margin_left":
                    {

                        jQuery('#tdheading_margin').html("Left Margin");
                        jQuery('#txtDefaultValue_margin').find('option').remove();
                        jQuery('#txtDefaultValue_margin').append('<option value="auto">auto</option>');
                        for (var i = parseInt(0); i <= parseInt(500); i++)
                            jQuery('#txtDefaultValue_margin').append('<option value="' + i + '">' + i + '</option>');
                        jQuery('#div_edit_margin').modal();
                        jQuery('#txtDefaultValue_margin').val(value).focus();
                        break;
                    }

                case "margin_right":
                    {

                        jQuery('#tdheading_margin').html("Right Margin");
                        jQuery('#txtDefaultValue_margin').find('option').remove();
                        jQuery('#txtDefaultValue_margin').append('<option value="auto">auto</option>');
                        for (var i = parseInt(0); i <= parseInt(500); i++)
                            jQuery('#txtDefaultValue_margin').append('<option value="' + i + '">' + i + '</option>');
                        jQuery('#div_edit_margin').modal();
                        jQuery('#txtDefaultValue_margin').val(value).focus();
                        break;
                    }

                case "border_radius":
                    {

                        jQuery('#tdheading_pixel').html("Border Radius");
                        //jQuery('#txtDefaultValue_pixel').find('option').remove();
                        //for (var i = parseInt(0); i <= parseInt(500); i++)
                        //    jQuery('#txtDefaultValue_pixel').append('<option value="' + i + '">' + i + '</option>');
                        jQuery('#div_edit_pixel').modal();
                        jQuery('#txtDefaultValue_pixel').val(value).focus();
                        jQuery('#txtDefaultValue_pixel_em').val(type).prop('disabled', false);
                        break;
                    }

                case "border_color":
                    {
                        jQuery('#tdheading_color').html("Border Color");
                        jQuery('#div_edit_color').modal();
                        jQuery('#txtDefaultValue_color').css('background-color', '#' + value).val(value).focus();
                        jQuery('#txtDefaultValue_color2').val(value);
                        var myPicker = new jscolor.color(document.getElementById('txtDefaultValue_color'), {});
                        myPicker.fromString(document.getElementById("txtDefaultValue_color").value);
                        break;
                    }

                case "background_color":
                    {
                        jQuery('#tdheading_color').html("Background Color");
                        jQuery('#div_edit_color').modal();
                        jQuery('#txtDefaultValue_color').css('background-color', '#' + value).val(value).focus();
                        jQuery('#txtDefaultValue_color2').val(value);
                        var myPicker = new jscolor.color(document.getElementById('txtDefaultValue_color'), {});
                        myPicker.fromString(document.getElementById("txtDefaultValue_color").value);
                        break;
                    }

                case "color":
                    {
                        jQuery('#tdheading_color').html("Color");
                        jQuery('#div_edit_color').modal();
                        jQuery('#txtDefaultValue_color').css('color', '#' + value).val(value).focus();
                        jQuery('#txtDefaultValue_color2').val(value);
                        var myPicker = new jscolor.color(document.getElementById('txtDefaultValue_color'), {});
                        myPicker.fromString(document.getElementById("txtDefaultValue_color").value);
                        break;
                    }

            }

        },
        //change a map's template
        changeMapTemplate: function() {

            var templateID = jQuery("#MapTemplateName").val();
            jQuery('#divPreview').html('');
            var proVersion = !!jQuery("#mapType").attr("proVersion");

            for (var t = 0; t < $arrTemplates.length; t++) {

                var template = $arrTemplates[t];
                if (parseInt(template.ID) == templateID) {

                    jQuery('#MapTemplateExampleImg').attr("src", $pluginsURL + template.ExampleImage);

                    var css = jQuery.xml2json(template.CSSValues);
                    var css2 = jQuery.xml2json(template.CSSValuesList);
                    var css3 = jQuery.xml2json(template.CSSValuesHeading);
                    jQuery('#MapTemplateCSS').html('');
                    jQuery('#MapTemplateListCSS').html('');
                    jQuery('#MapTemplateHeadingCSS').html('');

                    var j = 0;
                    var width = "", height = "";
                    for (var item in css) {

                        if (item === "width") {
                            width = css[item];
                        }
                        if (item === "height") {
                            height = css[item];
                        }

                        jQuery('#MapTemplateCSS').append('<div class="cssStyleEditorParent"><a id="styleElement' + j + '" class="cssEdit" href="javascript:easy2map_map_functions.changeElementValue(' + j + ', 1, ' + proVersion + ');" item="' + item + '" value="' + css[item] + '">' + normaliseCSSElement(item) + " (" + css[item] + ")</a></div>");
                        j += 1;
                    }

                    j = 0;
                    for (var item in css2) {
                        jQuery('#MapTemplateListCSS').append('<div class="cssStyleEditorParent"><a id="styleList' + j + '" class="cssEdit" href="javascript:easy2map_map_functions.changeElementValue(' + j + ', 2, ' + proVersion + ');" item="' + item + '" value="' + css2[item] + '">' + normaliseCSSElement(item) + " (" + css2[item] + ")</a></div>");
                        j += 1;
                    }

                    j = 0;
                    for (var item in css3) {
                        jQuery('#MapTemplateHeadingCSS').append('<div class="cssStyleEditorParent"><a id="styleHeading' + j + '" class="cssEdit" href="javascript:easy2map_map_functions.changeElementValue(' + j + ', 3, ' + proVersion + ');" item="' + item + '" value="' + css3[item] + '">' + normaliseCSSElement(item) + " (" + css3[item] + ")</a></div>");
                        j += 1;
                    }

                    jQuery("#mapSize").html("");
                    jQuery("#mapSize").append("<option value='640px,480px'>640px x 480px</option>");
                    jQuery("#mapSize").append("<option value='425px,350px'>425px x 350px</option>");
                    jQuery("#mapSize").append("<option value='300px,300px'>300px x 300px</option>");
                    jQuery("#mapSize").append("<option value='custom'>Set Custom Size</option>");

                    jQuery("#mapSize").val(width + "," + height).attr("selected", "selected");

                    if (jQuery("#mapSize option:selected").val() != width + "," + height) {
                        jQuery("#mapSize option:eq(" + parseInt(jQuery("#mapSize option").length - 2) + ")").after("<option selected='selected' value='" + width + "," + height + "'>" + width + " x " + height + "</option>");
                    }

                }
            }
            refreshExampleMap(true);
        },
        changeMapType: function() {


            var $mapType = jQuery('#mapType').val().toUpperCase();
            if ($mapType === "ROADMAP")
                $mapType = google.maps.MapTypeId.ROADMAP;
            else if ($mapType === "HYBRID")
                $mapType = google.maps.MapTypeId.HYBRID;
            else if ($mapType === "SATELLITE")
                $mapType = google.maps.MapTypeId.SATELLITE;
            else if ($mapType === "TERRAIN")
                $mapType = google.maps.MapTypeId.TERRAIN;
            else
                $mapType = google.maps.MapTypeId.ROADMAP;

            $map.setMapTypeId($mapType);

        },
        changeMarkerPopups: function() {
            refreshExampleMap(true);
        },
        cloneExistingMap: function(mapID) {

            this.retrieveMapSettings(mapID, true);
            jQuery('#MapClone').hide();

        },
        //allow the user to set a custom size for their map
        setCustomMapSize: function() {

            if (isNaN(document.getElementById('txtCustomWidthValue').value))
                return;
            if (isNaN(document.getElementById('txtCustomHeightValue').value))
                return;

            if (parseInt(document.getElementById('txtCustomWidthValue').value) < 0)
                return;
            if (parseInt(document.getElementById('txtCustomHeightValue').value) < 0)
                return;

            var width = jQuery('#txtCustomWidthValue').val() + jQuery('#mapWidthDimension').val();
            var height = jQuery('#txtCustomHeightValue').val() + 'px';
            easy2map_map_functions.changeMapSize(width + ',' + height);
        },
        //save the map's new size 
        changeMapSize: function(size) {

            if (size === "custom") {
                jQuery('[id ^= styleElement]').each(function() {

                    if (jQuery(this).attr('item') === 'width') {

                        var currentWidth = jQuery(this).attr('value');
                        if (currentWidth.indexOf('px') > 0) {
                            jQuery('#mapWidthDimension').val('px');
                        } else {
                            jQuery('#mapWidthDimension').val('%');
                        }

                        jQuery('#txtCustomWidthValue').val(parseInt(currentWidth));

                    }
                    if (jQuery(this).attr('item') === 'height') {
                        jQuery('#txtCustomHeightValue').val(parseInt(jQuery(this).attr('value')));
                    }
                });
                jQuery('#div_edit_widthheight').modal();
                return;
            }
            var arrSize = size.split(',');
            if (arrSize.length < 2)
                return;

            jQuery('[id ^= styleElement]').each(function() {

                if (jQuery(this).attr('item') === 'width') {
                    jQuery(this).attr('value', arrSize[0]);
                    jQuery(this).html('Width' + ' (' + arrSize[0] + ')');
                }
                if (jQuery(this).attr('item') === 'height') {
                    jQuery(this).attr('value', arrSize[1]);
                    jQuery(this).html('Height' + ' (' + arrSize[1] + ')');
                }

            });

            jQuery("#mapSize").val(arrSize[0] + "," + arrSize[1]).attr("selected", "selected");

            if (jQuery("#mapSize option:selected").val() != arrSize[0] + "," + arrSize[1]) {
                jQuery("#mapSize option:eq(" + parseInt(jQuery("#mapSize option").length - 2) + ")").after("<option selected='selected' value='" + arrSize[0] + "," + arrSize[1] + "'>" + arrSize[0] + " x " + arrSize[1] + "</option>");
            }

            refreshExampleMap(true);

        },
        //retrieve map's settings from the database
        retrieveMapSettings: function(mapID, ignoreMapName) {

            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                dataType: 'json',
                data: {
                    mapID: mapID,
                    action: "retrieve_map_settings"
                },
                success: function(mapSettings) {

                    $mapSettings = mapSettings;
                    var settings = jQuery.xml2json($mapSettings.Settings);
                    jQuery('#draggable').attr("src", $mapSettings.DefaultPinImage);

                    if (!!ignoreMapName === false) {
                        if ($mapSettings.MapName) {
                            jQuery('#mapName').val($mapSettings.MapName);
                            jQuery('#mapName2').html($mapSettings.MapName);
                        } else {
                            jQuery('#mapName2').html("Untitled Map");
                        }
                    }

                    jQuery('#mapType').val(settings.mapType.toUpperCase());
                    jQuery('#markerNameInPopups').prop("checked", parseInt(settings.showMarkerTitle) === 1 ? true : false);
                    jQuery('#markerNameFontSize').val(parseInt(settings.markerNameFontSize) > 0 ? settings.markerNameFontSize + '' : 1 + '');

                    jQuery('#setMaxWidthPopups').prop("checked", parseInt(settings.setMaxWidthPopups) === 1 ? true : false);
                    jQuery('#maxWidthPopups').val(parseInt(settings.maxWidthPopups) > 0 ? settings.maxWidthPopups : 250);

                    jQuery('#directionsInPopups').prop("checked", parseInt(settings.showDirections) === 1 ? true : false);
                    jQuery('#directionsLinkTitle').val(!!settings.directionsLinkTitle ? settings.directionsLinkTitle : "Get Directions");
                    jQuery('#directionsLinkFontSize').val(parseInt(settings.directionsLinkFontSize) > 0 ? settings.directionsLinkFontSize : 1);

                    jQuery('#mapEditPencil').show();
                    retrieveMapTemplates(mapID, $mapSettings.TemplateID);

                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    alert('could not retrieve map settings');
                }
            });

        },
        //save the CSS element that has been edited
        saveItemValue: function() {

            var attribute;

            if ($styleSelectedElement === 1)
                attribute = jQuery('#styleElement' + $styleElementIndex).attr('item');
            else if ($styleSelectedElement === 2)
                attribute = jQuery('#styleList' + $styleElementIndex).attr('item');
            else
                attribute = jQuery('#styleHeading' + $styleElementIndex).attr('item');

            var alteredValue = '';
            switch (attribute) {
                case "border_style":
                    {
                        alteredValue = jQuery('#txtDefaultValue_style').val();
                        jQuery('#div_edit_style').hide();
                        break;
                    }
                case "font_family":
                    {
                        alteredValue = jQuery('#txtDefaultValue_fontfamily').val();
                        jQuery('#div_edit_fontfamily').hide();
                        break;
                    }

                case "font_size":
                    {
                        alteredValue = jQuery('#txtDefaultValue_pixel').val() + jQuery('#txtDefaultValue_pixel_em').val();
                        jQuery('#div_edit_em').hide();
                        break;

                    }

                case "padding":
                    {
                        alteredValue = jQuery('#txtDefaultValue_pixel').val() + jQuery('#txtDefaultValue_pixel_em').val();
                        jQuery('#div_edit_em').hide();
                        break;
                    }

                case "text_align":
                    {
                        alteredValue = jQuery('#txtDefaultValue_textalign').val();
                        jQuery('#div_edit_textalign').hide();
                        break;
                    }

                case "border_width":
                    {
                        alteredValue = jQuery('#txtDefaultValue_pixel').val() + jQuery('#txtDefaultValue_pixel_em').val();
                        jQuery('#div_edit_pixel').hide();
                        break;
                    }
                case "width":
                    {

                        if (jQuery('#styleElement' + $styleElementIndex).attr('value').indexOf("%") !== -1) {
                            //percentage-based width
                            alteredValue = jQuery('#txtDefaultValue_percentage').val() + "%";
                            jQuery('#div_edit_percentage').hide();
                        }
                        else {
                            //pixel based width
                            alteredValue = jQuery('#txtDefaultValue_pixel').val() + "px";
                            jQuery('#div_edit_pixel').hide();

                        }
                        break;
                    }
                case "height":
                    {
                        alteredValue = jQuery('#txtDefaultValue_pixel').val() + "px";
                        jQuery('#div_edit_pixel').hide();
                        break;
                    }
                case "max_height":
                    {
                        alteredValue = jQuery('#txtDefaultValue_pixel').val() + "px";
                        jQuery('#div_edit_pixel').hide();
                        break;
                    }
                case "margin_top":
                    {
                        alteredValue = jQuery('#txtDefaultValue_margin').val();
                        if (alteredValue != "auto")
                            alteredValue += "px";
                        jQuery('#div_edit_margin').hide();
                        break;
                    }
                case "margin_bottom":
                    {
                        alteredValue = jQuery('#txtDefaultValue_margin').val();
                        if (alteredValue != "auto")
                            alteredValue += "px";
                        jQuery('#div_edit_margin').hide();
                        break;
                    }
                case "margin_left":
                    {
                        alteredValue = jQuery('#txtDefaultValue_margin').val();
                        if (alteredValue != "auto")
                            alteredValue += "px";
                        jQuery('#div_edit_margin').hide();
                        break;
                    }
                case "margin_right":
                    {
                        alteredValue = jQuery('#txtDefaultValue_margin').val();
                        if (alteredValue != "auto")
                            alteredValue += "px";
                        jQuery('#div_edit_margin').hide();
                        break;
                    }

                case "border_color":
                    {
                        alteredValue = '#' + jQuery('#txtDefaultValue_color').val();
                        jQuery('#tdItemElementValue').css('background-Color', '#' + jQuery('#txtDefaultValue_color').val());
                        jQuery('#div_edit_color').hide();
                        break;
                    }
                case "background_color":
                    {
                        alteredValue = '#' + jQuery('#txtDefaultValue_color').val();
                        jQuery('#tdItemElementValue').css('background-Color', '#' + jQuery('#txtDefaultValue_color').val());
                        jQuery('#div_edit_color').hide();
                        break;
                    }
                case "color":
                    {
                        alteredValue = '#' + jQuery('#txtDefaultValue_color').val();
                        jQuery('#tdItemElementValue').css('color', '#' + jQuery('#txtDefaultValue_color').val());
                        jQuery('#div_edit_color').hide();
                        break;
                    }
                case "z-index":
                    {
                        alteredValue = jQuery('#txtDefaultValue_pixel').val();
                        jQuery('#div_edit_pixel').hide();
                        break;
                    }
                case "border_radius":
                    {
                        alteredValue = jQuery('#txtDefaultValue_pixel').val() + jQuery('#txtDefaultValue_pixel_em').val();
                        jQuery('#div_edit_pixel').hide();
                        break;
                    }
            }

            if ($styleSelectedElement === 1) {
                jQuery('#styleElement' + $styleElementIndex).attr('value', alteredValue);
                jQuery('#styleElement' + $styleElementIndex).html(normaliseCSSElement(attribute) + ' (' + alteredValue + ')');

            }
            else if ($styleSelectedElement === 2) {
                jQuery('#styleList' + $styleElementIndex).attr('value', alteredValue);
                jQuery('#styleList' + $styleElementIndex).html(normaliseCSSElement(attribute) + ' (' + alteredValue + ')');

            }
            else {
                jQuery('#styleHeading' + $styleElementIndex).attr('value', alteredValue);
                jQuery('#styleHeading' + $styleElementIndex).html(normaliseCSSElement(attribute) + ' (' + alteredValue + ')');

            }

            refreshExampleMap(true);
        },
        //save the map to the database
        saveMap: function(redirect, showMapID) {

            var settings = jQuery.xml2json($mapSettings.Settings);
            var $lat = settings.lattitude;
            var $lng = settings.longitude;
            var $zoom = parseInt(settings.zoom);

            var $showMarkerTitle = jQuery('#markerNameInPopups').prop("checked") ? "1" : "0";
            var $markerNameFontSize = jQuery('#markerNameFontSize').val();
            var $setMaxWidthPopups = jQuery('#setMaxWidthPopups').prop("checked") ? "1" : "0";
            var $maxWidthPopups = jQuery('#maxWidthPopups').val();

            var $showDirections = jQuery('#directionsInPopups').prop("checked") ? "1" : "0";
            var $directionsLinkTitle = encodeURIComponent(jQuery('#directionsLinkTitle').val());
            var $directionsLinkFontSize = jQuery('#directionsLinkFontSize').val();

            if ($map != null) {
                $lat = $map.getCenter().lat();
                $lng = $map.getCenter().lng();
                $zoom = $map.getZoom();
            }

            var $mapType = jQuery('#mapType').val().toUpperCase();
            if ($mapType === "ROADMAP")
                $mapType = google.maps.MapTypeId.ROADMAP;
            else if ($mapType === "HYBRID")
                $mapType = google.maps.MapTypeId.HYBRID;
            else if ($mapType === "SATELLITE")
                $mapType = google.maps.MapTypeId.SATELLITE;
            else if ($mapType === "TERRAIN")
                $mapType = google.maps.MapTypeId.TERRAIN;
            else
                $mapType = google.maps.MapTypeId.ROADMAP;

            var mapOptions = {
                lattitude: $lat,
                longitude: $lng,
                zoom: $zoom,
                mapType: $mapType,
                backgroundColor: 'FFFFFF',
                draggable: 1,
                clusterpins: 0,
                trafficlayer: 0,
                transitlayer: 0,
                bicyclelayer: 0,
                scrollWheel: 0,
                mapTypeControl: 0,
                panControl: 0,
                rotateControl: 0,
                scaleControl: 0,
                streetViewControl: 1,
                zoomControl: 1,
                mapTypeControl_style: 'DROPDOWN_MENU',
                mapTypeControl_position: 'TOP_RIGHT',
                panControl_position: 'TOP_LEFT',
                rotateControl_position: 'TOP_LEFT',
                scaleControl_position: 'TOP_LEFT',
                streetViewControl_position: 'TOP_LEFT',
                zoomControl_position: 'TOP_LEFT',
                zoomControl_style: 'SMALL',
                polyline_strokecolor: '000000',
                polyline_opacity: '1.0',
                polyline_strokeweight: '1',
                showMarkerTitle: $showMarkerTitle,
                markerNameFontSize: $markerNameFontSize,
                setMaxWidthPopups: $setMaxWidthPopups,
                maxWidthPopups: $maxWidthPopups,
                showDirections: $showDirections,
                directionsLinkTitle: $directionsLinkTitle,
                directionsLinkFontSize: $directionsLinkFontSize
            };
            var options = {
                formatOutput: true,
                rootTagName: 'settings'
            };

            jQuery('#divMap').html('');
            var HTMLToSave = jQuery('#divPreview').html();

            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                dataType: 'json',
                data: {
                    mapID: $mapID,
                    mapName: jQuery('#mapName').val() == "" ? "Untitled Map" : jQuery('#mapName').val(),
                    mapTemplateName: jQuery('#MapTemplateName').val(),
                    action: "save_map",
                    mapSettingsXML: jQuery.json2xml(mapOptions, options),
                    mapCSSXML: encodeURIComponent(retrieveMapCSS()),
                    listCSSXML: encodeURIComponent(retrieveListCSS()),
                    headingCSSXML: encodeURIComponent(retrieveHeadingCSS()),
                    mapHTML: encodeURIComponent(HTMLToSave)
                },
                success: function(mapID) {
                    if (redirect) {

                        if (showMapID) {

                            jQuery('#txtShortCode').val('[easy2map id=' + mapID + ']');
                            jQuery('#mapShortCode').modal({
                                keyboard: false
                            });

                            jQuery('#mapShortCode').on('shown', function() {
                                document.getElementById('txtShortCode').focus();
                                document.getElementById('txtShortCode').select();
                            });

                        } else {

                            window.location = '?page=easy2map&action=viewmaps';

                        }

                        return;
                    }
                    $mapID = mapID;
                    //set the action of the pin upload form to reflect the new mapID!
                    jQuery('#formAddPinIcon').attr('action', '?page=easy2map&action=mappinimagesave&map_id=' + $mapID);

                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    alert(errorThrown);
                }
            });
        },
        //save the map to the database
        saveMapName: function() {

            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                dataType: 'json',
                data: {
                    mapID: $mapID,
                    mapName: jQuery('#mapName').val() == "" ? "Untitled Map" : jQuery('#mapName').val(),
                    action: "save_map_name"
                },
                success: function() {
                    jQuery("#divMapHeading").html(jQuery('#mapName').val() === "" ? "Untitled Map" : jQuery('#mapName').val());
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    alert(errorThrown);
                }
            });
        },
        //verify that the import file is a valid XML document
        uploadImportFile: function() {

            if (document.getElementById('xmlfile').value === "")
                return;
            var bContinue = true;

            var validateFileType = checkFileExtensionSilent(document.getElementById('xmlfile'));
            if (validateFileType != '') {
                alert('Invalid XML file.');
                bContinue = false;
                return;
            }

            if (bContinue == true) {
                busy();
                document.formImport.submit();
            }
        },
        importFileViaCSV: function() {

            if (parseInt($mapID) === 0) {

                jQuery.when(easy2map_map_functions.saveMap(false, false)).then(
                        function(data, textStatus, jqXHR) {
                            window.location = '?page=easy2map&action=mapimportcsv&map_id=' + $mapID;
                        });
            } else {
                window.location = '?page=easy2map&action=mapimportcsv&map_id=' + $mapID;
            }

        },
        //verify that the import file is a valid XML document
        uploadImportCSV: function() {

            if (document.getElementById('csvfile').value === "")
                return;
            var csvContinue = true;

            var validateFileType = checkFileExtensionSilent(document.getElementById('csvfile'));
            if (validateFileType !== '') {
                alert('Invalid CSV file.');
                csvContinue = false;
                return;
            }

            if (csvContinue === true) {
                busy();
                document.formImport3.submit();
            }
        }

    };


})();