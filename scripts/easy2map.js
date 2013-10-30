jQuery(function() {
    easy2map_functions.initialise_all_easy2maps();
});

var easy2map_functions = (function() {
    
    var markers = [];
    var infoWindow;

    ajax_location = function(mapID) {

        var arrMapID = mapID.split("_");
        var id = arrMapID[arrMapID.length - 1];
        return jQuery('#easy2map_ajax_url_' + id).val();

    };

    insertNewMapPoint = function(objMapPoint, map, maxPopupWidth) {

        var marker = new google.maps.Marker({
            position: new google.maps.LatLng(objMapPoint.lattitude, objMapPoint.longitude),
            draggable: false,
            map: map,
            title: objMapPoint.title.replace(/\\/gi, ''),
            ID: objMapPoint.ID,
            icon: objMapPoint.icon,
            pinHTML: objMapPoint.pinHTML
        });
        
        markers.push(marker);

        //retrieve pin settings
        var pinSettings = jQuery.xml2json(objMapPoint.settings);

        var loadBehaviour = 1;
        var loadURL = "";

        if (pinSettings.load && pinSettings.load != "") {
            loadBehaviour = parseInt(pinSettings.load);
        }

        if (pinSettings.url && pinSettings.url != "" && pinSettings.url != "http://") {
            loadURL = pinSettings.url;
        }
        
        try{
            var popup = '<p id="e2mpopuphook">' + decodeURIComponent(marker.pinHTML) + '</p>';
        }catch(e){
            var popup = '<p id="e2mpopuphook">' + marker.pinHTML + '</p>';
        }

        //if pin 'load' settings set to '1', open popup on pin click (default behaviour)
        if (loadBehaviour === 1 || (loadBehaviour === 3 && loadURL === "")) {
            google.maps.event.addListener(marker, "click", function(mEvent) {
                
                if (infoWindow) {
                    infoWindow.close();
                }
                
                if (!!maxPopupWidth && typeof maxPopupWidth !== "undefined" && !isNaN(maxPopupWidth)){
                    infoWindow = new google.maps.InfoWindow({ maxWidth: maxPopupWidth });
                } else {
                    infoWindow = new google.maps.InfoWindow();
                }
                
                infoWindow.setContent(popup);
                infoWindow.open(marker.map, marker);

                google.maps.event.addListener(infoWindow, 'domready', function() {
                    try{                    
                    var l = jQuery('#e2mpopuphook').parent().parent().parent().siblings();

                    for (var i = 0; i < l.length; i++) {

                        if (jQuery(l[i]).css('z-index') == 'auto') {
                            jQuery(l[i]).css('border-radius', '7px');
                        }
                    }} catch(e){}
                });



            });
        }
        //if pin 'load' settings set to '2', open popup on window load
        if (loadBehaviour === 2) {
            
            if (infoWindow) {
                infoWindow.close();
            }

            infoWindow = new google.maps.InfoWindow();
            infoWindow.setContent(popup);
            infoWindow.open(marker.map, marker);

        }
        //if pin 'load' settings set to '3', open URL
        if (loadBehaviour === 3 && loadURL != "") {

            google.maps.event.addListener(marker, "click", function(mEvent) {
                window.location.href = loadURL;
            });

        }

        return marker;
    };

    replaceAll = function(strOrig, strFind, strReplace) {

        var intCount = strOrig.indexOf(strFind);
        while (intCount != -1)
        {
            strOrig = replaceChars(strOrig, intCount, strFind.length, strReplace);
            intCount = strOrig.indexOf(strFind);
        }
        return strOrig;
    };

    replaceChars = function(strOrig, intPos, intNoChars, strReplace) {
        if (intPos < 0)
            intPos = 0;
        if (intPos >= strOrig.length)
            intPos = strOrig.length - 1;
        if (intNoChars < 0)
            intNoChars = 0;
        if (intNoChars > strOrig.length)
            intNoChars = strOrig.length;
        return (strOrig.substring(0, intPos) + strReplace + strOrig.substring(intPos + intNoChars));
    };

    retrieve_map_pins = function(map, mapControl, maxPins, arrMapPins, mapSettings) {

        var arrMapID = mapControl.split("_");
        var mapID = arrMapID[arrMapID.length - 1];
        var maxPopupWidth = '';
        
         if (!!mapSettings 
                 && !!mapSettings.setMaxWidthPopups 
                 && typeof mapSettings.setMaxWidthPopups !== "undefined"
                 && !!mapSettings.maxWidthPopups 
                 && typeof mapSettings.maxWidthPopups !== "undefined"){
             
             if (parseInt(mapSettings.setMaxWidthPopups) === 1 && !isNaN(mapSettings.maxWidthPopups)){
                 maxPopupWidth = mapSettings.maxWidthPopups;
             }
             
         }

        var data = {
            action: 'retrieve_map_points',
            MapID: mapID,
            maxPins: maxPins
        };

        jQuery.ajax({
            type: "POST",
            url: ajax_location(mapControl),
            data: data,
            dataType: 'json',
            success: function(returnData) {

                if (typeof returnData === "undefined" || typeof returnData == "null")
                    return;
                if (!returnData)
                    return;
                if (returnData.length === 0)
                    return;

                for (var t = 0; t < returnData.length; t++) {

                    var arrLatLng = replaceAll(replaceAll(replaceAll(returnData[t].LatLong, ' ', ''), '(', ''), ')', '').split(',');
                    
                    var pinHTML = returnData[t].MapPinHTML;
                    
                    //show marker's name as title if settings indicate so
                    if (!!mapSettings && !!mapSettings.showMarkerTitle && typeof mapSettings.showMarkerTitle !== "undefined"){
                        if (parseInt(mapSettings.showMarkerTitle) === 1){
                            
                            if (!!mapSettings.markerNameFontSize && mapSettings.markerNameFontSize !== "undefined" && mapSettings.markerNameFontSize.length > 0){
                                var fontSize = mapSettings.markerNameFontSize + 'em';
                            } else {
                                fontSize = '1.2em';
                            }                            
                            
                            pinHTML = "<p style='margin:0; padding:0;font-weight:bold;font-size:" + fontSize + ";'>" + returnData[t].Title + "</p>" + pinHTML;
                        }
                    }
                    
                    //show directions link if settings indicate so
                    if (!!mapSettings && !!mapSettings.showDirections && typeof mapSettings.showDirections !== "undefined"){
                        if (parseInt(mapSettings.showDirections) === 1){
                            
                            var directionsURL = "https://maps.google.com/maps?t=m&z=16&daddr=" + arrLatLng[0] + "," + arrLatLng[1];
                            
                            directionsFontSize = '1.2em';
                            if (!!mapSettings.directionsLinkFontSize && mapSettings.directionsLinkFontSize !== "undefined" && mapSettings.directionsLinkFontSize.length > 0){
                                var directionsFontSize = mapSettings.directionsLinkFontSize + 'em';
                            }
                            
                            var directionsLinkTitle = 'Get Directions';
                            if (!!mapSettings.directionsLinkTitle && mapSettings.directionsLinkTitle !== "undefined" && mapSettings.directionsLinkTitle.length > 0){
                                try{
                                    directionsLinkTitle = decodeURIComponent(mapSettings.directionsLinkTitle);
                                    
                                }catch(e){
                                    directionsLinkTitle = mapSettings.directionsLinkTitle;
                                }
                            } 
                            
                            pinHTML = pinHTML + "<a style='margin:0;margin-top:0.8em;display:block;padding:0;text-align:right;font-weight:bold;font-size:" + directionsFontSize + ";' target='_blank' href='" + directionsURL + "'>" + directionsLinkTitle + "</a>";
                        }
                    }

                    var objMapPoint = {
                        lattitude: arrLatLng[0],
                        longitude: arrLatLng[1],
                        title: returnData[t].Title,
                        ID: returnData[t].ID,
                        icon: returnData[t].ImageURL,
                        settings: returnData[t].Settings,
                        pinHTML: pinHTML
                    };
                    arrMapPins.push(objMapPoint);
                }

                var markersArray = [];

                for (var i = 0; i < arrMapPins.length; i++) {
                    markersArray.push(insertNewMapPoint(arrMapPins[i], map, maxPopupWidth));
                }

                /*if (!!mapSettings.clusterpins){
                 var mcOptions       = {
                 gridSize: 50, 
                 maxZoom: 15
                 };
                 var markerClusterer = new MarkerClusterer(map, markersArray, mcOptions);
                 }*/
            }
        });
    };

    retrieve_map_settings = function(mapControl) {

        var map;
        var arrMapPins = [];

        var arrMapID = mapControl.split("_");
        var mapID = arrMapID[arrMapID.length - 1];

        jQuery.ajax({
            type: 'POST',
            url: ajax_location(mapControl),
            dataType: 'json',
            data: {
                mapID: mapID,
                action: "retrieve_map_settings"
            },
            success: function(returnData) {

                var mapSettings = jQuery.xml2json(returnData.Settings);

                var $lat = mapSettings.lattitude;
                var $lng = mapSettings.longitude;
                var $zoom = parseInt(mapSettings.zoom);
                var $latlng = new google.maps.LatLng($lat, $lng);
                var $mapType = mapSettings.mapType.toUpperCase();
                var $mapTypeControl_style = mapSettings.mapTypeControl_style.toUpperCase();
                var $mapTypeControl_position = mapSettings.mapTypeControl_position.toUpperCase();
                var $zoomControlOptions_style = mapSettings.zoomControl_style.toUpperCase();
                var $zoomControlOptions_position = mapSettings.zoomControl_position.toUpperCase();
                var $scaleControlOptions_position = mapSettings.zoomControl_position.toUpperCase();

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

                if ($mapTypeControl_style === "DEFAULT")
                    $mapTypeControl_style = google.maps.MapTypeControlStyle.DEFAULT;
                else if ($mapTypeControl_style === "DROPDOWN_MENU")
                    $mapTypeControl_style = google.maps.MapTypeControlStyle.DROPDOWN_MENU;
                else if ($mapTypeControl_style === "SATELLITE")
                    $mapTypeControl_style = google.maps.MapTypeControlStyle.SATELLITE;
                else
                    $mapTypeControl_style = google.maps.MapTypeControlStyle.DEFAULT;

                if ($mapTypeControl_position === "TOP_LEFT")
                    $mapTypeControl_position = google.maps.ControlPosition.TOP_LEFT;
                else if ($mapTypeControl_position === "TOP_RIGHT")
                    $mapTypeControl_position = google.maps.ControlPosition.TOP_RIGHT;
                else if ($mapTypeControl_position === "TOP_CENTER")
                    $mapTypeControl_position = google.maps.ControlPosition.TOP_CENTER;
                else
                    $mapTypeControl_position = google.maps.ControlPosition.TOP_RIGHT;

                if ($zoomControlOptions_style === "DEFAULT")
                    $zoomControlOptions_style = google.maps.ZoomControlStyle.DEFAULT;
                else if ($zoomControlOptions_style === "LARGE")
                    $zoomControlOptions_style = google.maps.ZoomControlStyle.LARGE;
                else
                    $zoomControlOptions_style = google.maps.ZoomControlStyle.SMALL;

                if ($zoomControlOptions_position === "TOP_LEFT")
                    $zoomControlOptions_position = google.maps.ControlPosition.TOP_LEFT;
                else if ($zoomControlOptions_position === "TOP_RIGHT")
                    $zoomControlOptions_position = google.maps.ControlPosition.TOP_RIGHT;
                else if ($zoomControlOptions_position === "TOP_CENTER")
                    $zoomControlOptions_position = google.maps.ControlPosition.TOP_CENTER;
                else
                    $zoomControlOptions_position = google.maps.ControlPosition.TOP_RIGHT;

                if ($scaleControlOptions_position === "TOP_LEFT")
                    $scaleControlOptions_position = google.maps.ControlPosition.TOP_LEFT;
                else if ($scaleControlOptions_position === "TOP_RIGHT")
                    $scaleControlOptions_position = google.maps.ControlPosition.TOP_RIGHT;
                else if ($scaleControlOptions_position === "TOP_CENTER")
                    $scaleControlOptions_position = google.maps.ControlPosition.TOP_CENTER;
                else
                    $scaleControlOptions_position = google.maps.ControlPosition.TOP_RIGHT;

                var mapOptions = {
                    zoom: $zoom,
                    center: $latlng,
                    mapTypeId: $mapType,
                    mapTypeControl: !!parseInt(mapSettings.mapTypeControl),
                    mapTypeControlOptions: {
                        style: $mapTypeControl_style,
                        position: $mapTypeControl_position
                    },
                    zoomControl: !!parseInt(mapSettings.zoomControl),
                    navigationControl: true,
                    zoomControlOptions: {
                        style: $zoomControlOptions_style,
                        position: $zoomControlOptions_position
                    },
                    scaleControl: !!parseInt(mapSettings.scaleControl),
                    scaleControlOptions: {
                        position: $scaleControlOptions_position
                    },
                    streetViewControl: !!parseInt(mapSettings.streetViewControl),
                    panControl: !!parseInt(mapSettings.panControl),
                    draggable: !!parseInt(mapSettings.draggable)
                };

                map = new google.maps.Map(document.getElementById(mapControl), mapOptions);

                if (!!parseInt(mapSettings.trafficlayer)) {
                    var trafficLayer = new google.maps.TrafficLayer();
                    trafficLayer.setMap(map);
                }

                if (!!parseInt(mapSettings.transitlayer)) {
                    var transitLayer = new google.maps.TransitLayer();
                    transitLayer.setMap(map);
                }

                if (!!parseInt(mapSettings.bicyclelayer)) {
                    var bicycleLayer = new google.maps.BicyclingLayer();
                    bicycleLayer.setMap(map);
                }

                google.maps.event.addDomListener(window, 'load', retrieve_map_pins(map, mapControl, 250, arrMapPins, mapSettings));

            }
        });

    }

    return{
        
        displayPinItem : function(ID){
            for (var i = 0; i < markers.length; i++){
                if (markers[i].ID){
                    
                    if (parseInt(markers[i].ID) === ID){
                        google.maps.event.trigger(markers[i], 'click');
                        return;
                    }
                }
            }
        },
        initialise_all_easy2maps: function() {

            jQuery('div [id ^= easy2map_canvas_]').each(function() {

                var mapID = jQuery(this).attr('id');
                retrieve_map_settings(mapID);

            });
        }
    }

})();