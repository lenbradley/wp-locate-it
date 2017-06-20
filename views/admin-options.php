<div id="wpli-settings" class="wrap">
    <h2><?php _e( 'WP Locate-It Settings', $wpli->textdomain ); ?></h2>
    <form method="post" action="">

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="wpli-field-api-key"><?php _e( 'Google Browser API Key', $wpli->textdomain ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="wpli_browser_api_key" value="<?php echo $settings['browser_api_key']; ?>" id="wpli-field-browser-api-key" class="regular-text">
                        <p class="description"><?php _e( 'Enter your Google Maps API browser key', $wpli->textdomain ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wpli-field-api-key"><?php _e( 'Google Server API Key', $wpli->textdomain ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="wpli_server_api_key" value="<?php echo $settings['server_api_key']; ?>" id="wpli-field-server-api-key" class="regular-text">
                        <p class="description"><?php _e( 'Enter your Google Maps API server key', $wpli->textdomain ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wpli-field-disable-gmaps-script"><?php _e( 'Google Maps Script', $wpli->textdomain ); ?></label>
                    </th>
                    <td>
                        <label for="wpli-field-disable-gmaps-script">
                            <input type="checkbox" name="wpli_disable_gmaps_script" value="1" id="wpli-field-disable-gmaps-script"<?php if ( $settings['disable_gmaps_script'] ) : ?> checked="checked"<?php endif; ?>>
                            Disable loading Google Maps script
                        </label>
                        <p class="description"><?php _e( 'Check to disable loading the Google Maps script. You must load your own script with the places library included for this plugin to work.', $wpli->textdomain ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php _e( 'Post Types', $wpli->textdomain ); ?></label>
                    </th>
                    <td>
                        <?php foreach ( $settings['all_post_types'] as $key => $type ) : ?>
                        <?php
                            $checked = ( in_array( $key, $settings['set_post_types'] ) ? ' checked="checked"' : '' );
                        ?>
                        <fieldset>
                            <label for="wpli-field-pt-<?php echo $key; ?>">
                                <input type="checkbox" name="wpli_post_types[]" value="<?php echo $key; ?>" id="wpli-field-pt-<?php echo $key; ?>"<?php echo $checked; ?>>
                                <?php echo $type->labels->name; ?>
                            </label>
                        </fieldset>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php _e( 'Database Prefix', $wpli->textdomain ); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <label for="wpli-db-field-prefix">
                            <input type="text" name="wpli_db_field_prefix" value="<?php echo $settings['db_field_prefix']; ?>" id="wpli-db-field-prefix" class="regular-text">
                            <p class="description"><?php _e( 'Prefix to prepend to meta field key. Changing this will not automatically convert already saved location meta, so this should be set before any location data is saved. Should be in the format "_prefix_"', $wpli->textdomain ); ?></p>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th></th>
                    <td>
                        <p class="submit">
                            <input type="submit" class="button button-primary" name="submit" value="<?php _e( 'Save Changes', $wpli->textdomain ); ?>">
                            <input type="hidden" name="action" value="wpli_save_settings">
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</div>