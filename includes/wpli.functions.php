<?php
/**
 * Generalized functions that can be used by theme developers
 */

/**
 * Returns an instance of the WPLI object
 */
function WPLI() {
    global $WPLocateIt;
    return $WPLocateIt;
}

/**
 * Gets location meta associated with single post
 * @param  integer $post_id
 * @param  string  $key
 * @return array
 */
function wpli_get_location_meta( $post_id = 0, $key = '' ) {
    return WPLI()->get_location_meta( $post_id, $key );
}

/**
 * Gets Google Maps link based on location data
 * @param  integer $post_id
 * @param  string  $gmaps_url
 * @return string
 */
function wpli_get_google_maps_link( $post_id = 0, $gmaps_url = 'https://www.google.com/maps/place/' ) {    
    return WPLI()->get_google_maps_link( $post_id, $gmaps_url );
}

/**
 * Gets formatted location string from meta data
 * @param  integer $post_id
 * @return string
 */
function wpli_get_formatted_address( $post_id = 0 ) {
    return WPLI()->get_formatted_address( $post_id );
}

?>