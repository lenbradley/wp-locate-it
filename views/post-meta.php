<?php
/**
 * Passed Variables
 *     $wpli (WPLocateIt class instance)
 */

global $post;

if ( empty( $wpli ) ) {
    wp_die();
}

?>
<div id="wp-locate-it">
    <?php do_action( 'wp_locate_it_before_address_finder', $post ); ?>
    <div id="wpli-address-finder">
        <label for="wpli-google-search"><?php _e( 'Search', $wpli->textdomain ); ?></label>
        <input id="wpli-google-search" name="wpli_location_input" value="<?php echo get_post_meta( $post->ID, 'wpli_location_input', true ); ?>" type="text" placeholder="<?php _e( 'Search for location...', $wpli->textdomain ); ?>">
    </div>
    <?php do_action( 'wp_locate_it_before_fields', $post ); ?>
    <div id="wpli-fields">
        <?php foreach ( $wpli->get_fields() as $field ) : ?>
        <div class="wpli-field">
            <div class="wpli-field-label">
                <label for="wpli-field-<?php echo $field['slug']; ?>"><?php echo $field['label']; ?></label>
            </div>
            <div class="wpli-field-input">
                <input type="text" id="wpli-field-<?php echo $field['slug']; ?>" name="wp_locate_it[<?php echo $field['slug']; ?>]" value="<?php echo get_post_meta( $post->ID, $wpli->get_db_prefix() . $field['slug'], true ); ?>">
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php do_action( 'wp_locate_it_after_fields', $post ); ?>
    <div id="wpli-gmap"></div>
</div>