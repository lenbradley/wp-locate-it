jQuery(document).ready( function($) {

    var map;
    var wpLocateItSearch    = $('#wpli-google-search');
    var latInput            = $('#wp-locate-it #wpli-field-latitude');
    var lngInput            = $('#wp-locate-it #wpli-field-longitude');

    if ( latInput.length && latInput.val() && lngInput.length && lngInput.val() ) {
        WPLIRenderMap( latInput.val(), lngInput.val() );
    }

    if ( wpLocateItSearch.length ) {

        var searchBox = new google.maps.places.SearchBox( wpLocateItSearch[0] );

        searchBox.addListener( 'places_changed', function() {

            var places      = searchBox.getPlaces();
            var components  = {};

            if ( typeof places[0].address_components !== 'undefined' ) {
                $.each( places[0].address_components, function( k1, v1 ) {
                    $.each( v1.types, function( k2, v2 ) {
                        components[v2] = v1;
                    });
                });
            }

            var fields      = {
                address         : '',
                city            : '',
                county          : '',
                country         : '',
                country_abbr    : '',
                latitude        : 0,
                longitude       : 0,
                state           : '',
                state_abbr      : '',
                zip_code        : '',
                zip_suffix      : ''
            };

            if ( typeof components.street_number !== 'undefined' ) {
                fields.address = components.street_number.long_name;
            }

            if ( typeof components.route !== 'undefined' ) {
                fields.address += ( fields.address ? ' ' : '' ) + components.route.long_name;
            }

            if ( typeof components.locality !== 'undefined' ) {
                fields.city = components.locality.long_name;
            }

            if ( typeof components.administrative_area_level_1 !== 'undefined' ) {
                fields.state = components.administrative_area_level_1.long_name;
                fields.state_abbr = components.administrative_area_level_1.short_name;
            }

            if ( typeof components.postal_code !== 'undefined' ) {
                fields.zip_code = components.postal_code.long_name;
            }

            if ( typeof components.postal_code_suffix !== 'undefined' ) {
                fields.zip_suffix = components.postal_code_suffix.long_name;
            }

            if ( typeof components.administrative_area_level_2 !== 'undefined' ) {
                fields.county = components.administrative_area_level_2.long_name.replace( ' County', '' );
            }

            if ( typeof components.country !== 'undefined' ) {
                fields.country      = components.country.long_name;
                fields.country_abbr = components.country.short_name;
            }

            if ( typeof places[0].geometry !== 'undefined' ) {
                fields.latitude     = places[0].geometry.location.lat();
                fields.longitude    = places[0].geometry.location.lng();
            }

            $.each( fields, function( k, v ) {
                $('#wp-locate-it > #wpli-fields').find( '#wpli-field-' + k ).val(v);
            });

            if ( latInput.length && latInput.val() && lngInput.length && lngInput.val() ) {
                WPLIRenderMap( latInput.val(), lngInput.val() );
            }
        });
    }

    function WPLIRenderMap( lat, lng ) {

        var latlng = { lat: parseFloat(lat), lng: parseFloat(lng) };

        map = new google.maps.Map( document.getElementById('wpli-gmap'), {
            center  : latlng,
            zoom    : 18
        });

        var marker = new google.maps.Marker({
            position    : latlng,
            map         : map,
            draggable   : true
        });

        google.maps.event.addListener( marker, 'dragend', function() {
            latInput.val( marker.position.lat() );
            lngInput.val( marker.position.lng() );
        });
    }
});