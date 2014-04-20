var easy2map_mappin_functions = (function() {

    var infoWindow;

    /*clear the array that contains all pins located on the map*/
    clearPinArray = function() {

        if ($pinsArray) {
            for (i in $pinsArray) {
                $pinsArray[i].setMap(null);
                $pinsArray[i].setAnimation(null);
            }
        }
        if ($pinsArray)
            $pinsArray.length = 0;
        $selectedPin = null;

    };

    /*add the selected pin onto the Google map*/
    addPinToMap = function(objPointDetails) {

        this.selectedMapPoint = objPointDetails;

        try {
            var pinhtml = decodeURIComponent(this.selectedMapPoint.pinhtml);
        } catch (e) {
            pinhtml = this.selectedMapPoint.pinhtml;
        }

        var showMarkerTitle = jQuery('#markerNameInPopups').prop("checked") ? true : false;

        var markerNameFontSize = jQuery('#markerNameFontSize').val();
        var setMaxWidthPopups = jQuery('#setMaxWidthPopups').prop("checked") ? true : false;
        var maxWidthPopups = jQuery('#maxWidthPopups').val();

        var showDirections = jQuery('#directionsInPopups').prop("checked") ? true : false;
        var directionsLinkTitle = jQuery('#directionsLinkTitle').val();
        var directionsLinkFontSize = jQuery('#directionsLinkFontSize').val();

        if (showMarkerTitle) {
            pinhtml = "<p style='font-weight:bold;font-size:" + markerNameFontSize + "em;'>" +
                    this.selectedMapPoint.title.replace(/\\/gi, '') + "</p>" + pinhtml;
        }

        if (showDirections) {
            var directionsURL = "https://maps.google.com/maps?t=m&z=16&daddr=" + this.selectedMapPoint.lattitude + "," + this.selectedMapPoint.longitude;
            pinhtml = pinhtml + "<a style='display:block;margin:0;margin-top:0.8em;padding:0;text-align:right;font-weight:bold;font-size:" + directionsLinkFontSize + "em;' target='_blank' href='" + directionsURL + "'>" + directionsLinkTitle + "</a>";
        }

        var marker = new google.maps.Marker({
            position: new google.maps.LatLng(this.selectedMapPoint.lattitude, this.selectedMapPoint.longitude),
            draggable: true,
            map: $map,
            title: this.selectedMapPoint.title.replace(/\\/gi, ''),
            ID: this.selectedMapPoint.ID,
            icon: this.selectedMapPoint.icon,
            settings: this.selectedMapPoint.settings,
            pinHTML: pinhtml
        });

        $pinsArray.push(marker);
        //insert the pin onto the map here
        google.maps.event.addListener(marker, "dragend", function(mEvent) {
            $latlng = mEvent.latLng;
            showLatLong();
            updateMapPinLocation(marker.ID);
        });

        //show the pin's info window when it is clicked
        google.maps.event.addListener(marker, "click", function(mEvent) {

            if (infoWindow) {
                infoWindow.close();
            }

            var lines = marker.pinHTML.split('\n');
            var pinContent = '';
            for (var i = 0; i < lines.length; i++) {
                if (i > 0)
                    pinContent += "<br>";
                pinContent += lines[i];
            }
            var popup = '<p id="e2mpopuphook">' + pinContent + '</p>';

            if (setMaxWidthPopups === true && parseInt(maxWidthPopups) > 0) {
                infoWindow = new google.maps.InfoWindow({maxWidth: maxWidthPopups});
            } else {
                infoWindow = new google.maps.InfoWindow();
            }

            infoWindow.setContent(popup);
            infoWindow.open(marker.map, marker);

            google.maps.event.addListener(infoWindow, 'domready', function() {
                try {
                    var l = jQuery('#e2mpopuphook').parent().parent().parent().siblings();

                    for (var i = 0; i < l.length; i++) {

                        if (jQuery(l[i]).css('z-index') == 'auto') {
                            jQuery(l[i]).css('border-radius', '7px');
                        }
                    }
                } catch (e) {
                }
            });


        });
    };

    //function used as object for saving details of pins in a loop
    listedMapPin = function(objPinDetails) {
        this.pinDetails = objPinDetails;
    };

    //set the current pins image
    setPinImage = function(img) {
        jQuery('#draggable').attr('src', jQuery(img).attr('src'));
        jQuery('#mapPinIconList').modal('hide');

        if ($pinsArray) {
            for (i in $pinsArray) {

                if (typeof $pinsArray[i].ID == "undefined" || typeof $selectedPin.ID == "undefined")
                    continue;
                if ($pinsArray[i].ID == $selectedPin.ID)
                    $pinsArray[i].setIcon(jQuery(img).attr('src'));
            }
        }
    };

    //show lat/lng of current pin
    showLatLong = function() {
        jQuery('#latLongParent').show();
        jQuery('#divLatLong').html('Position of Pin: ' + $latlng);
    };

    //update the lat lng of the current pin after it is dragged and dropped
    updateMapPinLocation = function(mapPinID) {

        jQuery.ajax({
            type: 'POST',
            url: ajaxurl,
            dataType: 'json',
            data: {
                mapPointID: mapPinID,
                latLong: $latlng + '',
                action: "update_map_pin_location"
            }
        });
    };

    return{
        //prepare the front-end to allow the user to add a new marker to the map
        addNewMapMarker: function() {

            jQuery('#AddMarker').hide();
            jQuery('#SaveMap').hide();
            jQuery('#tblAddMapMarker').show();

        },
        //hide all edit controls from view
        cancelSaveMapPin: function() {

            jQuery('#pinNameParent').hide();
            jQuery('#pinDescriptionParent').hide();
            jQuery('#divAddressSearch').show();
            jQuery('#btnCancelPin').hide();

            jQuery('#AddMarker').hide();
            jQuery('#SaveMap').hide();
            jQuery('#tblAddMapMarker').hide();

            jQuery('#address').attr('placeholder', "Enter Marker's Address");
            document.getElementById('mapSize').disabled = false;

            jQuery('#draganddrop').show();
            jQuery('#AddEditPinTitle').html('Add New Marker');
            jQuery('#divDrag').show();
            jQuery('#divPinAddEditParent').hide();

            jQuery('#easy2maptab2').removeClass('disabled');

            jQuery('#pinName').val('');
            jQuery('#pinDescription').data("wysihtml5").editor.clear();

            jQuery('#btnDeletePin').hide();
            jQuery('#btnSavePin').hide();
            jQuery('#latLongParent').hide();
            jQuery('#draggable').attr("src", $mapSettings.DefaultPinImage);

            if ($selectedPin)
                $selectedPin.setAnimation(null);
            $selectedPin = null;

            //prepare the pin for dragging onto the map
            easy2map_mappin_functions.setIconDraggable();

            jQuery('td [id ^= imageTd]').removeClass('highlighted');
            jQuery('td [id ^= nameTd]').removeClass('highlighted');

            //remove the current pin from the public array
            if ($pinsArray) {
                for (i in $pinsArray) {
                    if ($pinsArray[i].ID == 0) {
                        $pinsArray[i].setMap(null);
                        $pinsArray[i].setAnimation(null);
                        $pinsArray.splice(i, 1);
                    }
                }
            }

            jQuery('tr [id ^=divPinInstructions]').show();
            jQuery('#draganddrop').show();
            $mapPinID = 0;

        },
        //allow the user to change the pin template
        changeMapPinTemplate: function() {

            var templateID = parseInt(jQuery("#MapPinTemplateName").val());

            for (var t = 0; t < $arrTemplates.length; t++) {

                var template = $arrTemplates[t];
                if (parseInt(template.ID) == templateID) {
                    jQuery('#MapTemplateExampleImg').html(template.TemplateHTML);
                }
            }
        },
        //deleting map pin
        deleteSelectedPoint: function() {

            if (!confirm('Are you sure you wish to delete this pin? This action is not reversible!'))
                return;

            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                dataType: 'json',
                data: {
                    MapPointID: $mapPinID,
                    action: 'delete_map_point'
                },
                success: function(returnData) {

                    if (returnData.length > 0) {
                        alert('D' + returnData);
                        return;
                    }

                    clearPinArray();
                    if ($pinsArray)
                        $pinsArray.length = 0;
                    easy2map_mappin_functions.retrieveMapPoints();
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    if (errorThrown.length > 0)
                        alert('E' + errorThrown);
                }
            });
        },
        displayPinItem: function(ID) {
            for (var i = 0; i < $pinsArray.length; i++) {
                if ($pinsArray[i].ID) {

                    if (parseInt($pinsArray[i].ID) === ID) {
                        google.maps.event.trigger($pinsArray[i], 'click');
                        return;
                    }
                }
            }
        },
        //prepare a pin item for editing
        editPinItem: function(index) {

            var pinDetails = $markersArray[index].pinDetails;
            $latlng = new google.maps.LatLng(pinDetails.lattitude, pinDetails.longitude);
            $selectedPin = $pinsArray[index];
            showLatLong();

            $selectedPin.setAnimation(google.maps.Animation.BOUNCE);
            setTimeout(function() {
                $selectedPin.setAnimation(null);
            }, 1500);

            jQuery('td [id ^= imageTd]').removeClass('highlighted');
            jQuery('td [id ^= nameTd]').removeClass('highlighted');

            jQuery('#imageTd' + index).addClass('highlighted');
            jQuery('#nameTd' + index).addClass('highlighted');
            document.getElementById('mapSize').disabled = true;

            jQuery('#pinNameParent').show();
            jQuery('#pinDescriptionParent').show();
            jQuery('#divAddressSearch').show();
            jQuery('#address').attr('placeholder', "Change Marker's Location");

            jQuery('#AddMarker').hide();
            jQuery('#SaveMap').hide();
            jQuery('#tblAddMapMarker').show();

            jQuery('#draganddrop').hide();
            jQuery('#btnCancelPin').show();
            jQuery('#AddEditPinTitle').html('Edit Marker\'s Details');
            jQuery('#btnUploadIcon').html('Change Icon');
            jQuery('#divDrag').hide();
            jQuery('#divPinAddEditParent').show();

            jQuery('#easy2maptab2').addClass('disabled');

            jQuery('#pinName').val(pinDetails.title.replace(/\\/gi, ''));

            try {
                var pinhtml = decodeURIComponent(pinDetails.pinhtml);
            } catch (e) {
                pinhtml = pinDetails.pinhtml;
            }

            jQuery('#pinDescription').data("wysihtml5").editor.setValue(pinhtml);

            $mapPinID = pinDetails.ID;
            jQuery('#btnDeletePin').show();
            jQuery('#btnSavePin').show();
            jQuery('tr [id ^=divPinInstructions]').hide();
            jQuery('#draggable').attr('src', pinDetails.icon);
            easy2map_mappin_functions.setIconNotDraggable();

        },
        hidePreviewPage: function() {
            jQuery('#divMultipleLocations').fadeOut();
        },
        //retrieve all pin icons associated with this map
        openImagesDirectory: function(selectedImage) {

            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                dataType: 'json',
                data: {
                    mapID: $mapID,
                    action: 'retrieve_pin_icons'
                },
                success: function(returnData) {

                    jQuery('#tblPinImages').find('tr').remove();
                    var iCounter = 0;
                    for (var i = 0; i < returnData.length; i++) {
                        if (iCounter % 6 == 0) {
                            var tr = document.createElement('tr');
                            document.getElementById('tblPinImages').appendChild(tr);
                        }

                        var imageTd = document.createElement('td');
                        imageTd.align = "center";
                        imageTd.style.borderColor = "#FFFFFF";
                        imageTd.style.padding = "2px";
                        if (returnData[i] == selectedImage) {
                            imageTd.style.borderColor = "#EBEBEB";
                            imageTd.style.borderWidth = "2px";
                            imageTd.style.borderStyle = "solid";
                            imageTd.style.borderRadius = "3px"
                        }

                        var image = document.createElement('img');
                        image.style.cursor = "pointer";
                        image.setAttribute("onClick", "easy2map_mappin_functions.setMapPinImage(this)");

                        var arrImage = returnData[i].split("/");
                        if (arrImage.length > 0) {
                            image.setAttribute('title', arrImage[arrImage.length - 1]);
                        }
                        image.src = returnData[i];

                        imageTd.appendChild(image);
                        tr.appendChild(imageTd);
                        iCounter += 1;
                    }

                    jQuery('#mapPinIconList').modal();
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    alert('E' + errorThrown);
                }
            });
        },
        //run this function when a pin is dropped onto a map - setup controls for creating pin
        placePin: function(location) {

            var marker = new google.maps.Marker({
                position: location,
                ID: 0,
                draggable: true,
                bouncy: true,
                title: 'Click on icon to edit details',
                map: $map,
                icon: jQuery('#draggable').attr('src')
            });

            $latlng = marker.position;
            $pinsArray.push(marker);
            $selectedPin = $pinsArray[$pinsArray.length - 1];
            showLatLong();

            jQuery('#pinNameParent').show();
            jQuery('#pinDescriptionParent').show();
            jQuery('#divAddressSearch').hide();
            document.getElementById('mapSize').disabled = true;

            jQuery('#draganddrop').hide();
            jQuery('#btnCancelPin').show();
            jQuery('#AddEditPinTitle').html('Set Marker\'s Details');
            jQuery('#btnUploadIcon').html('Change Icon');
            jQuery('#address').attr('placeholder', "Change Marker's Location");
            jQuery('#divDrag').hide();
            jQuery('#divPinAddEditParent').show();

            jQuery('tr [id ^=divPinInstructions]').hide();
            jQuery('#pinName').focus();

            jQuery('#btnSavePin').show();
            easy2map_mappin_functions.setIconNotDraggable();

            google.maps.event.addListener(marker, "dragend", function(mEvent) {
                $latlng = mEvent.latLng;
                showLatLong();
            });

            google.maps.event.addListener(marker, "click", function(mEvent) {
                $latlng = mEvent.latLng;
                showLatLong();
            });

        },
        //retrieve all points associated with this map
        retrieveMapPoints: function() {

            //jQuery('#pleaseWaitDialog').modal();

            var data = {
                action: 'retrieve_map_points',
                MapID: $mapID
            };

            try {

                //clear all controls for blank slate
                easy2map_mappin_functions.cancelSaveMapPin();

                jQuery.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: data,
                    dataType: 'json',
                    success: function(returnData) {

                        jQuery('#tblMapMarkers').show().find('tr').remove();
                        jQuery('#tblEasy2MapPinList').find('tr').remove();
                        for (var k = 1; k <= 6; k++) {
                            jQuery('#tblEasy2MapPinList' + k).find('tr').remove();
                        }
                        jQuery('#ulEasy2MapPinList').find('li').remove();
                        jQuery('#divAddressSearch').show();

                        var noPinsFound = false;

                        if (typeof returnData == "undefined" || typeof returnData == "null")
                            noPinsFound = true;
                        if (!returnData)
                            noPinsFound = true;
                        if (returnData.length == 0)
                            noPinsFound = true;

                        if (noPinsFound) {
                            //if no pins are found, prompt the user to add a new pin
                            jQuery('#AddMarker').hide();
                            jQuery('#SaveMap').hide();
                            jQuery('#tblAddMapMarker').show();
                            jQuery('#MarkersListHeading').html('');
                            //jQuery('#pleaseWaitDialog').modal('hide');
                            return;
                        } else {
                            //show all pins associated with the map
                            jQuery('#AddMarker').show();
                            jQuery('#SaveMap').show();
                            jQuery('#tblAddMapMarker').hide();
                            jQuery('#MarkersListHeading').html('<h6>Map Markers</h6>');
                        }

                        if ($markersArray)
                            $markersArray.length = 0;
                        clearPinArray();

                        for (var t = 0; t < returnData.length; t++) {

                            var arrLatLng = replaceAll(replaceAll(replaceAll(returnData[t].LatLong, ' ', ''), '(', ''), ')', '').split(',');
                            var objMapPoint = {
                                lattitude: arrLatLng[0],
                                longitude: arrLatLng[1],
                                ID: returnData[t].ID,
                                title: returnData[t].Title,
                                icon: returnData[t].ImageURL,
                                settings: returnData[t].Settings,
                                pinhtml: returnData[t].MapPinHTML
                            };
                            $markersArray.push(new listedMapPin(objMapPoint));
                        }

                        for (var i = 0; i < $markersArray.length; i++) {

                            var selectedListItem = $markersArray[i].pinDetails;

                            addPinToMap(selectedListItem);

                            var tr = document.createElement('tr');
                            document.getElementById('tblMapMarkers').appendChild(tr);

                            var imageTd = document.createElement('td');
                            imageTd.id = "imageTd" + i;
                            imageTd.align = "center";
                            var image = document.createElement('img');
                            image.style.cursor = "pointer";
                            image.setAttribute("onClick", "easy2map_mappin_functions.editPinItem(" + i + ")");
                            image.src = selectedListItem.icon;
                            imageTd.style.minWidth = '30px';
                            imageTd.style.textAlign = 'center';
                            imageTd.appendChild(image);

                            var nameTd = document.createElement('td');
                            nameTd.id = "nameTd" + i;
                            nameTd.innerHTML = selectedListItem.title.replace(/\\/gi, '');
                            nameTd.style.width = '80%';
                            nameTd.style.cursor = "pointer";
                            nameTd.setAttribute("onClick", "easy2map_mappin_functions.editPinItem(" + i + ")");

                            var editTd = document.createElement('td');
                            editTd.id = "editTd" + i;
                            editTd.style.minWidth = '10%';
                            editTd.style.textAlign = 'center';
                            var editLink = document.createElement('a');
                            editLink.innerText = 'edit';
                            editLink.textContent = 'edit';
                            editLink.href = '#';
                            editLink.setAttribute('className', 'smallE2MLink');
                            editLink.setAttribute('onclick', "easy2map_mappin_functions.editPinItem(" + i + ")");
                            editTd.appendChild(editLink);

                            tr.appendChild(imageTd);
                            tr.appendChild(nameTd);
                            tr.appendChild(editTd);

                            //populate the map pin list (if applicable)
                            var tblPinList = document.getElementById('tblEasy2MapPinList');

                            if (tblPinList !== null) {

                                var tr2 = document.createElement('tr');
                                var tr3 = document.createElement('tr');
                                var tr4 = document.createElement('tr');

                                var imageTd2 = document.createElement('td');
                                imageTd2.align = "center";
                                imageTd2.rowSpan = "2";
                                imageTd2.style.padding = '3px';
                                imageTd2.style.verticalAlign = "top";
                                var image2 = document.createElement('img');
                                image2.src = selectedListItem.icon;
                                image2.style.cursor = "pointer";
                                image2.setAttribute("onClick", "if (typeof easy2map_functions !== 'undefined') { easy2map_functions.displayPinItem(" + selectedListItem.ID + ")} else { easy2map_mappin_functions.displayPinItem(" + selectedListItem.ID + ")}");
                                imageTd2.style.textAlign = 'center';
                                imageTd2.appendChild(image2);

                                var nameTd2 = document.createElement('td');
                                nameTd2.innerHTML = selectedListItem.title.replace(/\\/gi, '');
                                nameTd2.style.verticalAlign = 'top';
                                nameTd2.style.fontSize = '1.2em';
                                nameTd2.style.padding = '3px';
                                nameTd2.style.fontWeight = 'bold';
                                nameTd2.style.cursor = "pointer";
                                nameTd2.setAttribute("onClick", "if (typeof easy2map_functions !== 'undefined') { easy2map_functions.displayPinItem(" + selectedListItem.ID + ")} else { easy2map_mappin_functions.displayPinItem(" + selectedListItem.ID + ")}");

                                var descriptionTd = document.createElement('td');
                                descriptionTd.style.textAlign = 'left';
                                descriptionTd.style.padding = '3px';

                                try {
                                    var pinhtml = decodeURIComponent(selectedListItem.pinhtml);
                                } catch (e) {
                                    pinhtml = selectedListItem.pinhtml;
                                }

                                descriptionTd.innerHTML = pinhtml;

                                var emptyTd = document.createElement('td');
                                emptyTd.style.height = '10px';

                                tblPinList.appendChild(tr2);
                                tr2.appendChild(imageTd2);
                                tr2.appendChild(nameTd2);
                                tblPinList.appendChild(tr3);
                                tr3.appendChild(descriptionTd);
                                tblPinList.appendChild(tr4);
                                tr4.appendChild(emptyTd);

                            }

                            for (var k = 1; k <= 6; k++) {

                                //populate the map pin list (if applicable)
                                var tblPinList = document.getElementById('tblEasy2MapPinList' + k);

                                if (tblPinList !== null) {

                                    if (k > $markersArray.length) {
                                        var cellWidth = (10 / $markersArray.length) * 10;
                                    } else {
                                        var cellWidth = (10 / k) * 10;
                                    }

                                    cellWidth = cellWidth + '%';

                                    if ((i % k === 0) === true) {

                                        var tr2 = document.createElement('tr');
                                        var tr3 = document.createElement('tr');
                                        var tr4 = document.createElement('tr');

                                        tr2.id = 'tblEasy2MapPinList2tr2' + i;
                                        tr3.id = 'tblEasy2MapPinList2tr3' + i;
                                        tr4.id = 'tblEasy2MapPinList2tr4' + i;

                                    } else {

                                        var j = i - (i % k);

                                        var tr2 = document.getElementById('tblEasy2MapPinList2tr2' + j);
                                        var tr3 = document.getElementById('tblEasy2MapPinList2tr3' + j);
                                        var tr4 = document.getElementById('tblEasy2MapPinList2tr4' + j);

                                    }

                                    var imageTd2 = document.createElement('td');
                                    imageTd2.align = "center";
                                    imageTd2.rowSpan = "2";
                                    imageTd2.style.padding = '4px';
                                    imageTd2.style.verticalAlign = "top";
                                    var image2 = document.createElement('img');
                                    image2.src = selectedListItem.icon;
                                    image2.style.cursor = "pointer";
                                    image2.setAttribute("onClick", "if (typeof easy2map_functions !== 'undefined') { easy2map_functions.displayPinItem(" + selectedListItem.ID + ")} else { easy2map_mappin_functions.displayPinItem(" + selectedListItem.ID + ")}");
                                    imageTd2.style.textAlign = 'center';
                                    imageTd2.appendChild(image2);

                                    var nameTd2 = document.createElement('td');
                                    nameTd2.innerHTML = selectedListItem.title.replace(/\\/gi, '');
                                    nameTd2.style.verticalAlign = 'top';
                                    nameTd2.style.fontSize = '1.2em';
                                    nameTd2.style.padding = '4px';
                                    nameTd2.style.fontWeight = 'bold';
                                    nameTd2.style.cursor = "pointer";
                                    nameTd2.setAttribute("onClick", "if (typeof easy2map_functions !== 'undefined') { easy2map_functions.displayPinItem(" + selectedListItem.ID + ")} else { easy2map_mappin_functions.displayPinItem(" + selectedListItem.ID + ")}");

                                    var descriptionTd = document.createElement('td');
                                    descriptionTd.style.textAlign = 'left';
                                    descriptionTd.style.verticalAlign = 'top';
                                    descriptionTd.style.padding = '4px';
                                    descriptionTd.style.width = cellWidth;

                                    try {
                                        var pinhtml = decodeURIComponent(selectedListItem.pinhtml);
                                    } catch (e) {
                                        pinhtml = selectedListItem.pinhtml;
                                    }

                                    descriptionTd.innerHTML = pinhtml;

                                    var emptyTd = document.createElement('td');
                                    //emptyTd.colspan = k;
                                    emptyTd.style.height = '10px';

                                    if ((i % k === 0) === true) {

                                        tblPinList.appendChild(tr2);
                                        tr2.appendChild(imageTd2);
                                        tr2.appendChild(nameTd2);
                                        tblPinList.appendChild(tr3);
                                        tr3.appendChild(descriptionTd);
                                        tblPinList.appendChild(tr4);
                                        tr4.appendChild(emptyTd);

                                    } else {

                                        tr2.appendChild(imageTd2);
                                        tr2.appendChild(nameTd2);
                                        tr3.appendChild(descriptionTd);
                                        tr4.appendChild(emptyTd);
                                    }
                                }
                            }

                            //populate the map pin list (if applicable)
                            var ulPinList = document.getElementById('ulEasy2MapPinList');

                            if (ulPinList !== null) {

                                var li = document.createElement('li');
                                var tbl = document.createElement('table');
                                var tr2 = document.createElement('tr');
                                var tr3 = document.createElement('tr');

                                li.style.display = 'table-cell';
                                li.style.verticalAlign = 'top';
                                li.style.minWidth = '200px';

                                tbl.cellPadding = '2';
                                tbl.cellSpacing = '2';
                                tbl.className = 'tblHorizontalPinList';

                                var imageTd2 = document.createElement('td');
                                imageTd2.align = "center";
                                imageTd2.rowSpan = "2";
                                imageTd2.style.padding = '3px';
                                imageTd2.style.paddingLeft = '16px';
                                imageTd2.style.verticalAlign = "top";
                                var image2 = document.createElement('img');
                                image2.src = selectedListItem.icon;
                                image2.style.cursor = "pointer";
                                image2.setAttribute("onClick", "if (typeof easy2map_functions !== 'undefined') { easy2map_functions.displayPinItem(" + selectedListItem.ID + ")} else { easy2map_mappin_functions.displayPinItem(" + selectedListItem.ID + ")}");
                                imageTd2.style.textAlign = 'center';
                                imageTd2.appendChild(image2);

                                var nameTd2 = document.createElement('td');
                                nameTd2.innerHTML = selectedListItem.title.replace(/\\/gi, '');
                                nameTd2.style.verticalAlign = 'top';
                                nameTd2.style.padding = '3px';
                                nameTd2.style.fontWeight = 'bold';
                                nameTd2.style.cursor = "pointer";
                                nameTd2.setAttribute("onClick", "if (typeof easy2map_functions !== 'undefined') { easy2map_functions.displayPinItem(" + selectedListItem.ID + ")} else { easy2map_mappin_functions.displayPinItem(" + selectedListItem.ID + ")}");

                                var descriptionTd = document.createElement('td');
                                descriptionTd.style.textAlign = 'left';
                                descriptionTd.style.padding = '3px';

                                try {
                                    var pinhtml = decodeURIComponent(selectedListItem.pinhtml);
                                } catch (e) {
                                    pinhtml = selectedListItem.pinhtml;
                                }

                                descriptionTd.innerHTML = pinhtml;

                                tbl.appendChild(tr2);
                                tr2.appendChild(imageTd2);
                                tr2.appendChild(nameTd2);
                                tbl.appendChild(tr3);
                                tr3.appendChild(descriptionTd);

                                li.appendChild(tbl);
                                ulPinList.appendChild(li);

                            }


                        }

                        jQuery('#tblMapMarkers').removeClass('table-striped').addClass('table-striped');
                        //jQuery('#pleaseWaitDialog').modal('hide');
                    }
                });

            } catch (e) {
                //jQuery('#pleaseWaitDialog').modal('hide');
            }
        },
        //save map pin to database
        saveMapPin: function() {

            var pinHTML = jQuery('#pinDescription').val();

            //remove MS Word formatting
            pinHTML = replaceAll(pinHTML, "&quot;Arial&quot;", "Arial");
            pinHTML = replaceAll(pinHTML, "&quot;Times New Roman&quot;", "Arial");
            pinHTML = replaceAll(pinHTML, "&quot;sans-serif&quot;", "sans-serif");
            pinHTML = replaceAll(pinHTML, 'class="MsoNormal"', '');

            pinHTML = pinHTML.replace(/ – /g, " - ");

            //gmail does not render line-height correctly
            pinHTML = replaceAll(pinHTML, "line-height:", "min-height:");

            if (pinHTML.indexOf('<p') !== -1) {
                pinHTML = replaceAll(pinHTML, "'", '&#39;');
            }

            pinHTML = replaceAll(pinHTML, '<p>', '<p style="margin:0; padding:0;">');
            pinHTML = replaceAll(pinHTML, '<p style="text-align: left;">', '<p style="text-align: left;margin:0; padding:0;">');
            pinHTML = replaceAll(pinHTML, '<p style="text-align: right;">', '<p style="text-align: left;margin:0; padding:0;">');
            pinHTML = replaceAll(pinHTML, '<p style="text-align: center;">', '<p style="text-align: left;margin:0; padding:0;">');

            //remove MS Word formatting
            if (pinHTML.indexOf('[endif]') !== -1) {
                pinHTML = pinHTML.substring(pinHTML.lastIndexOf('[endif]') + 10);
            }

            //convert pin settings to XML
            //var pinSettings = {
            //    load : jQuery('#PinLoad option:selected').val(),
            //    url : jQuery('#PinURL').val()
            //}
            //var options                             = { formatOutput: true, rootTagName: 'settings'};
            //var PinSettingsXML                      = jQuery.json2xml(pinSettings, options);

            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                dataType: 'json',
                data: {
                    mapPointID: $mapPinID,
                    action: "save_map_pin",
                    latLong: $latlng + '',
                    icon: jQuery('#draggable').attr('src'),
                    pinTitle: jQuery('#pinName').val(),
                    pinSettingsXML: '',
                    pinHTML: encodeURIComponent(pinHTML),
                    mapID: $mapID
                },
                success: function(mapPinID) {
                    easy2map_mappin_functions.retrieveMapPoints();
                    $selectedPin = null;
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    alert('F' + errorThrown);
                }
            });

        },
        setCurrentPosition: function(lat, lng) {
            var location = new google.maps.LatLng(lat, lng);
            $latlng = location;
        },
        //save default image associated with map pins
        setMapPinImage: function(img) {

            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                dataType: 'json',
                data: {
                    MapID: $mapID,
                    PinImage: encodeURIComponent(jQuery(img).attr('src')),
                    action: "save_default_pin_image"
                },
                success: function(returnImage) {
                    jQuery('#draggable').attr('src', jQuery(img).attr('src'));
                    jQuery('#mapPinIconList').modal('hide');
                    $mapSettings.DefaultPinImage = returnImage;
                    setPinImage(img);
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    alert('G' + errorThrown);
                }
            });

        },
        //set the pin's lat-lng position
        SetPinPosition: function(lat, lng) {

            var location = new google.maps.LatLng(lat, lng);
            $latlng = location;
            if ($selectedPin == null) {
                easy2map_mappin_functions.placePin(location);

                if ($markersArray.length == 0) {
                    //set map center, and save settings to map settings
                    $map.setCenter(location);
                    $map.setZoom(12);
                    var settings = jQuery.xml2json($mapSettings.Settings);
                    settings.lattitude = lat;
                    settings.longitude = lng;
                    settings.zoom = $map.getZoom();

                    var options = {formatOutput: true, rootTagName: 'settings'};
                    $mapSettings.Settings = jQuery.json2xml(settings, options);
                }

            }
            else
                $selectedPin.setPosition(location);

            setTimeout("jQuery('#address').val('')", 1500);
        },
        //prepare the icon to be dragged onto the map
        setIconDraggable: function() {

            jQuery("#draggable").css('cursor', 'move');
            jQuery("#draggable").draggable({
                appendTo: '#bodyTag',
                scroll: false,
                helper: 'clone',
                stop: function(e) {

                    var bounds = $map.getBounds();
                    var latLngSouthW = bounds.getSouthWest();
                    var latLngNorthE = bounds.getNorthEast();
                    var position = jQuery('#divMap').offset();
                    var point = new google.maps.Point(e.pageX - position.left, e.pageY - position.top + 22);
                    var item = $overlay.getProjection().fromContainerPixelToLatLng(point);
                    if (latLngSouthW.lng() > 0 && (item.lng() > 0 && item.lng() < latLngSouthW.lng()))
                        return;
                    if (latLngSouthW.lng() < 0 && (item.lng() < 0 && item.lng() < latLngSouthW.lng()))
                        return;

                    if (latLngNorthE.lng() > 0 && (item.lng() > 0 && item.lng() > latLngNorthE.lng()))
                        return;
                    if (latLngNorthE.lng() < 0 && (item.lng() < 0 && item.lng() > latLngNorthE.lng()))
                        return;

                    easy2map_mappin_functions.placePin(item);
                }

            });

        },
        setIconNotDraggable: function() {
            jQuery('#draggable').css('cursor', 'default').draggable('destroy');
        },
        //verify that the pin icon is a valid image file
        uploadPinIcon: function() {

            var validateFileType = checkFileExtensionSilent(document.getElementById('pinicon'));
            if (validateFileType !== '') {
                alert('Invalid file, only the following image types are allowed: ' + validateFileType + '. Please upload a different image.');
                return;
            }
            document.formAddPinIcon.submit();
        }
    };

})();