jQuery(document).ready( function($) {

    $('.wpli-input').each( function() {

        var $input      = $(this),
            $form       = $(this.form),
            $geoLocate  = $form.find('.wpli-geolocate'),
            srcParam    = WPLIGetUrlParam('loc'),
            latParam    = WPLIGetUrlParam('lat'),
            lngParam    = WPLIGetUrlParam('lng'),
            distParam   = WPLIGetUrlParam('dist'),
            unitParam   = WPLIGetUrlParam('unit');

        if ( $geoLocate.length ) {
            $geoLocate.on( 'click', function(e) {

                e.preventDefault();

                if ( navigator.geolocation ) {
                    navigator.geolocation.getCurrentPosition( WPLISetFromGeoLocation );
                } else {
                    alert( 'Geolocation is not supported by this browser.' );
                }
            });
        }

        if ( srcParam ) {
            $input.val( srcParam );
        }

        $input.after('<input type="hidden" name="loc" value="">');

        if ( ! $('input[name="lat"]').length ) {
            $form.append('<input type="hidden" name="lat" value="">');
        }

        if ( ! $('input[name="lng"]').length ) {
            $form.append('<input type="hidden" name="lng" value="">');
        }

        if ( latParam ) {
            $form.find('[name="lat"]').val( latParam );
        }

        if ( lngParam ) {
            $form.find('[name="lng"]').val( lngParam );
        }

        if ( distParam ) {
            $form.find('[name="dist"]').val( distParam );
        }

        if ( unitParam ) {
            $form.find('[name="unit"]').val( unitParam );
        }

        var searchBox = new google.maps.places.SearchBox( $input[0] );

        searchBox.addListener( 'places_changed', function() {

            var place           = searchBox.getPlaces()[0];

            if ( typeof place.geometry !== 'undefined' ) {
                $form.find('input[name="lat"]').val( place.geometry.location.lat() );
                $form.find('input[name="lng"]').val( place.geometry.location.lng() );
            }
        });

        $input.on( 'change focus blur', function(event) {

            if ( $.trim( $input.val() ) == '' ) {
                $form.find('input[name="lat"]').val('');
                $form.find('input[name="lng"]').val('');
            }
        });

        $input.on( 'keydown', function(event) {

            var keycode = ( event.keyCode ? event.keyCode : event.which );

            if ( keycode == '13' ) {
                event.preventDefault();
            }
        });

        $form.on( 'submit', function(form) {

            form.preventDefault();

            var searchString    = $input.val();
            var lonField        = $form.find('[name="lat"]').val();
            var lngField        = $form.find('[name="lng"]').val();
            var getPlaces       = searchBox;

            if ( searchString && lonField && lngField ) {
                $form.find('input[name="loc"]').val( searchString );
                $input.val('');
                $form.off().submit();
            } else {
                alert('You must search for and select a location before continuing.');
            }
        });

        function WPLISetFromGeoLocation( position ) {

            if ( ! position.coords.latitude && ! position.coords.longitude) {
                alert('Location could not be found! Please check you browser settings.');
                $input.val('');
            } else {
                $form.find('[name="lat"]').val( position.coords.latitude );
                $form.find('[name="lng"]').val( position.coords.longitude );
                $input.val('My Location');
            }
        }
    });

    function WPLIGetUrlParam( param ) {

        var pageURL = decodeURIComponent( window.location.search.substring(1) ),
            urlVars = pageURL.split('&'),
            paramName,
            i;

        for ( i = 0; i < urlVars.length; i++ ) {

            paramName = urlVars[i].split('=');

            if ( paramName[0] === param ) {
                return paramName[1] === undefined ? true : paramName[1].replace( /\+/g, ' ' );
            }
        }

        return '';
    };
});

jQuery(document).ready( function($) {

    $('.wpli-map').each( function() {

        $(this).css( 'min-height', '250px' ).css( 'width', '100%' );

        var markers         = [];
        var gMapContainer   = this;
        var gMapObject      = $(this);
        var gMapStyles      = [];

        var gMapSettings    = {
            zoom                : ( gMapObject.data('zoom') ? parseInt( gMapObject.data('zoom') ) : 8 ),
            reZoomOnInit        : ( gMapObject.data('zoom') ? true : false ),
            disableDefaultUI    : ( gMapObject.data('disableDefaultUI') ? Boolean( gMapObject.data('disableDefaultUI' ) ) : false ),
            scrollwheel         : ( gMapObject.data('scrollwheel') ? Boolean(  gMapObject.data('scrollwheel') ) : false ),
            draggable           : ( gMapObject.data('scrollwheel') ? Boolean(  gMapObject.data('scrollwheel') ) : true )
        };

        gMapObject.find('var').each( function() {

            var markerData = {
                latitude    : $(this).data('lat'),
                longitude   : $(this).data('lng'),
                address     : $(this).data('address'),
                title       : $(this).data('title'),
                id          : $(this).data('location-id'),
            };

            markers.push( markerData );
        });

        gMapSettings.defaultLat = ( typeof markers[0] !== 'undefined' ? markers[0].latitude : 0 );
        gMapSettings.defaultLat = ( typeof markers[0] !== 'undefined' ? markers[0].longitude : 0 );

        var map = new google.maps.Map( gMapContainer, {
            zoom                    : gMapSettings.zoom,
            styles                  : gMapStyles,
            center                  : new google.maps.LatLng( gMapSettings.defaultLat, gMapSettings.defaultLat ),
            disableDefaultUI        : gMapSettings.disableDefaultUI,
            scrollwheel             : gMapSettings.scrollwheel,
            draggable               : gMapSettings.draggable
        });

        var bounds      = new google.maps.LatLngBounds();
        var infoWindow  = new google.maps.InfoWindow();
        var overlay     = new google.maps.OverlayView();

        $.each( markers, function( i, marker ) {

            var addMarker = new google.maps.Marker({
                position    : new google.maps.LatLng( marker.latitude, marker.longitude ),
                map         : map,
                title       : marker.title
            });

            bounds.extend( addMarker.position );

            google.maps.event.addListener( addMarker, 'click', function() {
                infoWindow.setContent(
                    '<div class="wpli-gm-overlay-window">' +
                        '<div class="wpli-gm-overlay-window-inner">' +
                            '<h2 class="wpli-gm-overlay-window-title">' + markers[i].title + '</h2>' +
                            '<p class="wpli-gm-overlay-window-content">' + markers[i].address + '</p>' +
                        '</div>' +
                    '</div>'
                );
                infoWindow.open( map, addMarker );
            });

            // google.maps.event.addListener( addMarker, 'mouseout', function() {
            //     infoWindow.close();
            // });
        });

        map.fitBounds( bounds );

        if ( gMapSettings.reZoomOnInit ) {
            var resizeMapOnInit = google.maps.event.addListener( map, 'idle', function () {
                map.setZoom( gMapSettings.zoom );
                google.maps.event.removeListener( resizeMapOnInit );
            });
        }
    });
});