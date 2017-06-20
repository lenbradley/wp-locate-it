<?php
/**
 * WIP
 */

function wpli_render_google_map( $args = array() ) {

    $data = WPLI()->get_location_meta();

    if ( empty( $data ) ) {
        return false;
    }

    ob_start();
    ?>
    <div class="wpli-map">
        <var data-lat="<?php echo $data['latitude']; ?>" data-lng="<?php echo $data['longitude']; ?>"></var>
    </div>
    <?php
    $output = ob_get_contents();
    ob_end_clean();

    return $output;
}
add_shortcode( 'wpli-map', 'wpli_render_google_map' );

?>