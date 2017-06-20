<?php

class WPLocateIt
{
    /**
     * Defines the instance of plugin
     *
     * @var WPLocateIt
     * @access protected
     */
    private static $instance = null;

    /**
     * Default unit of measurement to use
     * @var string
     * @access public
     */
    private $unit_of_measure = '';

    /**
     * Plugin version
     *
     * @var string
     * @access public
     */
    public $version = WPLI_PLUGIN_VERSION;

    /**
     * WP Locate-It text domain
     * @var string
     * @access public
     */
    public $textdomain = 'wp-locate-it';

    /**
     * Register Wordpress specific hooks
     * @return null
     */
    public function register_wp_hooks() {

        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );
            add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
            add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
            add_action( 'current_screen', array( $this, 'save_settings' ) );
            add_action( 'save_post', array( $this, 'save_post_meta' ), 10, 3 );
        } else {
            add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
        }

        /**
         * Load plugin textdomain
         */
        add_action( 'init', array( $this, 'wpli_load_textdomain' ) );

        /**
         * Extend proximity search to WP_Query
         */
        add_filter( 'query_vars', array( $this, 'proximity_search_query_vars' ), 10, 1 );
        add_filter( 'posts_fields', array( $this, 'proximity_search_fields' ), 10, 2 );
        add_filter( 'posts_join', array( $this, 'proximity_search_joins' ), 10, 2 );
        add_filter( 'posts_where', array( $this, 'proximity_search_where' ), 10, 2 );
        add_filter( 'posts_orderby', array( $this, 'proximity_search_orderby' ), 10, 2 );
        add_filter( 'posts_groupby', array( $this, 'proximity_search_groupby' ), 10, 2 );
        add_filter( 'posts_distinct', array( $this, 'proximity_search_distict' ), 10, 2 );
    }

    /**
     * Load an instance of this class
     * @return WPLocateIt Class Object
     */
    public static function instance() {

        if ( ! is_object( self::$instance ) ) {
            self::$instance = new WPLocateIt();
        }

        return self::$instance;
    }

     /**
     * Gets the plugin file path so we can use it elsewhere
     * @return string
     */
    public function get_plugin_path() {
        return WPLI_PLUGIN_PATH;
    }

    public function wpli_load_textdomain() {
        load_plugin_textdomain( $this->textdomain, false, $this->get_plugin_path() . '/languages' );
    }

    public function set_unit_of_measure( $unit = '' ) {
        $this->unit_of_measure = $unit;
    }

    public function get_api_key( $type = 'browser' ) {
        $api_key = get_option( 'wpli_' . $type . '_api_key', '' );
        return trim( $api_key );
    }

    /**
     * Enqueue the required sripts and stylesheets
     * @return null
     */
    public function load_scripts() {

        $api_key        = ( $this->get_api_key('browser') ? 'key=' . $this->get_api_key('browser') . '&' : '' );
        $libraries      = apply_filters( 'wp_locate_it_google_maps_api_libraries', 'places' );
        $in_footer      = apply_filters( 'wp_locate_it_load_scripts_in_footer', true );
        $disable_script = get_option( 'wpli_disable_gmaps_script' );

        if ( ! $disable_script ) {
            // Enqueue the required Google Maps API script
            wp_register_script( 'wpli-google-maps-api', '//maps.googleapis.com/maps/api/js?' . $api_key . 'libraries=' . $libraries, array( 'jquery' ), $this->version, $in_footer );
            wp_enqueue_script( 'wpli-google-maps-api' );
        }

        if ( is_admin() ) {

            $screen = get_current_screen();

            if (
                ( ! empty( $screen->post_type ) && in_array( $screen->post_type, $this->get_post_types() ) ) ||
                ( isset( $_GET['page'] ) && $_GET['page'] == 'wp-locate-it-settings' )
            ) {
                wp_register_style( 'wpli-admin', WPLI_PLUGIN_URL . '/library/styles/wpli-admin.css', array(), $this->version );
                wp_enqueue_style( 'wpli-admin' );
            }

            wp_register_script( 'wpli-js-backend', WPLI_PLUGIN_URL . '/library/scripts/wpli-backend.js', array( 'jquery' ), $this->version );
            wp_enqueue_script( 'wpli-js-backend' );
        } else {

            wp_register_style( 'wpli-frontend', WPLI_PLUGIN_URL . '/library/styles/wpli-styles.css', array(), $this->version );
            wp_enqueue_style( 'wpli-frontend' );

            wp_register_script( 'wpli-js-frontend', WPLI_PLUGIN_URL . '/library/scripts/wpli-frontend.js', array( 'jquery' ), $this->version, $in_footer );
            wp_enqueue_script( 'wpli-js-frontend' );
        }
    }

    /**
     * Process and parse proximity data for usuage in query
     * @param  array    $data
     * @param  boolean  $check_request
     * @return array
     */
    public function parse_proximity_data( $data = array(), $check_request = true ) {

        $var_checks = array(
            0 => array( 'latitude', 'lat' ),
            1 => array( 'longitude', 'lng', 'lon' ),
            2 => array( 'distance', 'dist' ),
            3 => array( 'unit', 'units' )
        );

        if ( is_string( $data ) ) {
            $data = explode( ',', $data );
        }

        if ( $check_request && ! empty( $_REQUEST ) ) {

            $data = array();

            foreach ( $var_checks as $key => $values ) {
                foreach ( $values as $value ) {
                    if ( isset( $_REQUEST[$value] ) ) {
                        $data[$key] = $_REQUEST[$value];
                        break;
                    }
                }
            }

            unset( $key, $values, $value );
        }

        if ( is_array( $data ) ) {

            foreach ( $var_checks as $key => $values ) {
                foreach ( $values as $value ) {
                    if ( isset( $data[$value] ) ) {
                        $data[$key] = $data[$value];
                        unset( $data[$value] );
                        break;
                    }
                }
            }

            unset( $key, $values, $value );
        }

        if ( is_array( $data ) && ! empty( $data[0] ) && ! empty( $data[1] ) ) {

            $output = array(
                'latitude'  => $data[0],
                'longitude' => $data[1]
            );

            if ( ! empty( $data[2] ) ) {

                if ( is_int( $data[2] ) || ctype_digit( $data[2] ) ) {
                    $output['distance'] = $data[2];
                } else {

                    $data[2] = preg_replace( '/\s+/', '', strtolower( $data[2] ) );

                    $_nums = preg_replace( "/[^0-9\.]/", "", $data[2] );
                    $_char = preg_replace( "/[^a-z-]/i", "", strtolower( $data[2] ) );

                    $output['distance'] = $_nums;

                    if ( $_char ) {
                        $data[3] = $_char;
                    }
                }
            } else {
                $output['distance'] = 0;
            }

            if ( empty( $data[3] ) ) {
                $data[3] = $this->unit_of_measure;
            }

            if ( ! $data[3] || ! is_numeric( $data[3] ) ) {

                $data[3] = strtolower( $data[3] );

                switch ( $data[3] ) {
                    case 'cm' :
                    case 'centimeter' :
                    case 'centimetre' :
                    case 'centimeters' :
                    case 'centimetres' :
                        $output['modifier'] = 6371000000;
                        break;
                    case 'i' :
                    case 'in' :
                    case 'inch' :
                    case 'inches' :
                        $output['modifier'] = 250842240;
                        break;
                    case 'f' :
                    case 'ft' :
                    case 'feet' :
                        $output['modifier'] = 20903520;
                        break;
                    case 'y' :
                    case 'yd' :
                    case 'yard' :
                    case 'yards' :
                        $output['modifier'] = 6967840;
                        break;
                    case 'm' :
                    case 'meter' :
                    case 'metre' :
                    case 'meters' :
                    case 'metres' :
                        $output['modifier'] = 6371000;
                        break;
                    case 'k' :
                    case 'km' :
                    case 'kilometer' :
                    case 'kilometre' :
                    case 'kilometers' :
                    case 'kilometres' :
                        $output['modifier'] = 6371;
                        break;
                    default :
                        $output['modifier'] = 3959; // Defaults to miles
                }
            } else {
                $output['modifier'] = $data[3];
            }

            return apply_filters( 'wp_locate_it_parsed_proximity_data', $output );

        } else {
            return false;
        }
    }

    public function proximity_search_query_vars( $query_vars ) {
        $query_vars[] = 'proximity';
        return $query_vars;
    }

    public function proximity_search_fields( $fields, $query ) {

        global $wpdb;

        $data = $this->parse_proximity_data( $query->get('proximity') );

        if ( $data ) {

            $fields .= $wpdb->prepare( ', ( %f * acos(
                cos( radians( %s ) ) *
                cos( radians( latitude.meta_value ) ) *
                cos( radians( longitude.meta_value ) - radians( %s ) ) +
                sin( radians( %s ) ) *
                sin( radians( latitude.meta_value ) )
            ) ) AS distance ', $data['modifier'], $data['latitude'], $data['longitude'], $data['latitude'] );

            $fields .= ', latitude.meta_value AS latitude ';
            $fields .= ', longitude.meta_value AS longitude ';
        }

        return $fields;
    }

    public function proximity_search_joins( $joins, $query ) {

        global $wpdb;

        $data = $this->parse_proximity_data( $query->get('proximity') );

        if ( $data ) {
            $joins .= ' INNER JOIN ' . $wpdb->postmeta . ' AS latitude ON ' . $wpdb->posts . '.ID = latitude.post_id ';
            $joins .= ' INNER JOIN ' . $wpdb->postmeta . ' AS longitude ON ' . $wpdb->posts . '.ID = longitude.post_id ';
        }

        return $joins;
    }

    public function proximity_search_where( $where, $query ) {

        global $wpdb;
        $data = $this->parse_proximity_data( $query->get('proximity') );

        if ( $data ) {

            $lat_meta_key = $this->get_db_prefix() . 'latitude';
            $lng_meta_key = $this->get_db_prefix() . 'longitude';

            $where .= $wpdb->prepare( ' AND latitude.meta_key = %s AND TRIM( latitude.meta_value ) != %s ', $lat_meta_key, '' );
            $where .= $wpdb->prepare( ' AND longitude.meta_key = %s AND TRIM( longitude.meta_value ) != %s ', $lng_meta_key, '' );

            if ( $data['distance'] ) {
                $where .= $wpdb->prepare( ' HAVING distance <= %f ', $data['distance'] );
            }
        }

        return $where;
    }

    public function proximity_search_orderby( $orderby, $query ) {

        global $wpdb;
        $data = $this->parse_proximity_data( $query->get('proximity') );

        if ( $data ) {
            $orderby = ' distance ASC ';
        }

        return $orderby;
    }

    public function proximity_search_groupby( $groupby, $query ) {

        global $wpdb;
        $data = $this->parse_proximity_data( $query->get('proximity') );

        if ( $data ) {
            $groupby = '';
        }

        return $groupby;
    }

    public function proximity_search_distict( $disctict, $query ) {

        global $wpdb;
        $data = $this->parse_proximity_data( $query->get('proximity') );

        if ( $data ) {
            return 'DISTINCT';
        }

        return $disctict;
    }

    /**
     * Grab and return an array containing the post types that this plugin will work with
     * @return array
     */
    public function get_post_types() {

        $post_types = get_option('wpli_post_types');

        if ( $post_types ) {
            $post_types = json_decode( $post_types );
        } else {
            $post_types = array();
        }

        $types = apply_filters( 'wp_locate_it_post_types', $post_types );
        return $types;
    }

    /**
     * Get fields to render
     * @return array
     */
    public function get_fields() {

        $fields = array(
            array(
                'slug'  => 'address',
                'label' => __( 'Address', $this->textdomain )
            ),
            array(
                'slug'  => 'city',
                'label' => __( 'City', $this->textdomain )
            ),
            array(
                'slug'  => 'state',
                'label' => __( 'State (full)', $this->textdomain )
            ),
            array(
                'slug'  => 'state_abbr',
                'label' => __( 'State (abbr)', $this->textdomain )
            ),
            array(
                'slug'  => 'zip_code',
                'label' => __( 'Zip Code', $this->textdomain )
            ),
            array(
                'slug'  => 'zip_suffix',
                'label' => __( 'Zip Suffix', $this->textdomain )
            ),
            array(
                'slug'  => 'country',
                'label' => __( 'Country (full)', $this->textdomain )
            ),
            array(
                'slug'  => 'country_abbr',
                'label' => __( 'Country (abbr)', $this->textdomain )
            ),
            array(
                'slug'  => 'county',
                'label' => __( 'County', $this->textdomain )
            ),
            array(
                'slug'  => 'latitude',
                'label' => __( 'Latitude', $this->textdomain )
            ),
            array(
                'slug'  => 'longitude',
                'label' => __( 'Longitude', $this->textdomain )
            )
        );

        return apply_filters( 'wp_locate_it_fields', $fields );
    }

    /**
     * Gets location meta associated with post
     * @param  integer $post_id
     * @return object
     */
    public function get_location_meta( $post_id = 0, $key = '' ) {

        if ( ! $post_id ) {
            global $post;
            $post_id = $post->ID;
        }

        if ( $key ) {
            return get_post_meta( $post_id, $this->get_db_prefix() . trim( strtolower( $key ) ), true );
        } else {

            $data   = array();
            $fields = $this->get_fields();

            foreach ( $fields as $field ) {
                $data[$field['slug']] = get_post_meta( $post_id, $this->get_db_prefix() . $field['slug'], true );
            }

            return $data;
        }
    }

    /**
     * Generates a formated address based off the location meta
     * @param  integer $post_id
     * @return string
     */
    public function get_formatted_address( $post_id = 0 ) {

        $output = '';

        if ( ! $post_id ) {
            global $post;
            $post_id = $post->ID;
        }

        $meta = $this->get_location_meta( $post_id );

        if ( ! empty( $meta['address'] ) ) {
            $output .= ', ' . ucwords( strtolower( trim( $meta['address'] ) ) );
        }

        if ( ! empty( $meta['city'] ) ) {
            $output .= ', ' . ucwords( strtolower( trim( $meta['city'] ) ) );
        }

        if ( ! empty( $meta['state_abbr'] ) ) {
            $output .= ', ' . strtoupper( trim( $meta['state_abbr'] ) );
        }

        if ( ! empty( $meta['zip_code'] ) ) {
            $output .= ' ' . trim( $meta['zip_code'] );
        }

        $output = trim( trim( $output, ', ' ) );

        return $output;
    }

    /**
     * Generates and formats a URL that links to Google Maps
     * @param  integer $post_id
     * @param  string  $gmaps_url
     * @return string
     */
    public function get_google_maps_link( $post_id = 0, $gmaps_url = 'https://www.google.com/maps/place/' ) {

        $output = '';

        if ( ! $post_id ) {
            global $post;
            $post_id = $post->ID;
        }

        $address = $this->get_formatted_address( $post_id );

        if ( $address ) {
            $output = trailingslashit( $gmaps_url ) . urlencode( trim( $address ) );
        }

        return $output;
    }

    /**
     * Loads a template from the plugin templates directory
     * @param  string  $file
     * @param  array   $args
     * @param  boolean $echo
     * @return string/html
     */
    public function get_view( $file = '', $args = array(), $echo = true ) {

        $output = '';

        if ( ! empty( $file ) ) {

            $tpl_dir    = $this->get_plugin_path() . '/views/';
            $tpl_file   = trim( $file, '/ ' ) . ( substr( $file, -4 ) == '.php' ? '' : '.php' );
            $tpl_path   = $tpl_dir . $tpl_file;

            if ( file_exists( $tpl_path ) ) {

                if ( ! empty( $args ) && is_array( $args ) ) {
                    extract( $args, EXTR_OVERWRITE );
                }

                ob_start();
                include $tpl_path;
                $output = ob_get_contents();
                ob_end_clean();
            }
        }

        if ( $echo == true ) {
            echo $output;
        } else {
            return $output;
        }
    }

    /**
     * Adds meta box to post details
     * @return null
     */
    public function add_meta_box() {

        $screen = get_current_screen();

        if ( ! empty( $screen->post_type ) && in_array( $screen->post_type, $this->get_post_types() ) ) {

            add_meta_box(
                'wp-locate-it-location-info',
                __( 'Location Details', $this->textdomain ),
                array( $this, 'render_metabox' ),
                $this->get_post_types(),
                apply_filters( 'wp_locate_it_meta_box_context', 'normal' ),
                apply_filters( 'wp_locate_it_meta_box_priority', 'high' )
            );
        }
    }

    /**
     * Renders the metabox HTML
     * @return null
     */
    public function render_metabox() {
        $this->get_view( 'post-meta', array( 'wpli' => $this ) );
    }

    /**
     * Saves location meta data when posts are saved
     * @param  int $post_id
     * @param  object $post
     * @param  array $update
     * @return null
     */
    public function save_post_meta( $post_id, $post, $update ) {

        if ( ! empty( $_REQUEST['wp_locate_it'] ) && is_array( $_REQUEST['wp_locate_it'] ) ) {
            foreach ( $_REQUEST['wp_locate_it'] as $key => $value ) {
                $value = apply_filters( 'wp_locate_it_before_update_post_meta', $value, $key );
                update_post_meta( $post_id, $this->get_db_prefix() . sanitize_key( $key ), sanitize_text_field( $value ) );
            }
        }

        if ( isset( $_REQUEST['wpli_location_input'] ) ) {
            update_post_meta( $post_id, 'wpli_location_input', sanitize_text_field( $_REQUEST['wpli_location_input'] ) );
        }
    }

    /**
     * Create the settings page in the admin secion
     */
    public function add_settings_page() {

        add_submenu_page(
            'options-general.php',
            __( 'WP Locate-It Settings', $this->textdomain ),
            __( 'WP Locate-It', $this->textdomain ),
            apply_filters( 'wp_locate_it_capabilities', 'manage_options' ),
            'wp-locate-it-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Render the settings by by generating the data and pulling the view
     */
    public function render_settings_page() {

        $post_types = apply_filters( 'wp_locate_it_allowed_post_types', array( 'public' => true ) );

        $settings = array(
            'browser_api_key'       => $this->get_api_key('browser'),
            'server_api_key'        => $this->get_api_key('server'),
            'set_post_types'        => $this->get_post_types(),
            'all_post_types'        => get_post_types( $post_types, 'objects' ),
            'disable_gmaps_script'  => get_option('wpli_disable_gmaps_script'),
            'db_field_prefix'       => get_option('wpli_db_field_prefix')
        );

        $this->get_view( 'admin-options', array( 'wpli' => $this, 'settings' => $settings ) );
    }

    /**
     * Process the POST data
     */
    public function save_settings() {

        if ( isset( $_POST['action'] ) && $_POST['action'] == 'wpli_save_settings' ) {

            // Check for and update the Google Maps browser API key
            if ( isset( $_POST['wpli_browser_api_key'] ) ) {
                update_option( 'wpli_browser_api_key', $_POST['wpli_browser_api_key'] );
            }

            // Check for and update the Google Maps browser API key
            if ( isset( $_POST['wpli_server_api_key'] ) ) {
                update_option( 'wpli_server_api_key', $_POST['wpli_server_api_key'] );
            }

            // Update the Database Prefix field
            $db_field_prefix = ( isset( $_POST['wpli_db_field_prefix'] ) ? trim( $_POST['wpli_db_field_prefix'] ) : '' );
            update_option( 'wpli_db_field_prefix', $db_field_prefix );

            // Update the option to disable the default Google Maps script
            $disable_gmaps_script = ( isset( $_POST['wpli_disable_gmaps_script'] ) ? $_POST['wpli_disable_gmaps_script'] : '' );
            update_option( 'wpli_disable_gmaps_script', $disable_gmaps_script );

            // Find and update all selected post types to apply WPLI to
            if ( ! empty( $_POST['wpli_post_types'] ) && is_array( $_POST['wpli_post_types'] ) ) {
                update_option( 'wpli_post_types', json_encode( $_POST['wpli_post_types'] ) );
            } else {
                update_option( 'wpli_post_types', '' );
            }

            do_action( 'wpli_save_settings' );

            // Generate a notice that the fields have been updated
            add_action( 'admin_notices', function() {
                ?>
                <div class="notice notice-success">
                    <p><?php _e( 'WP Locate-It settings have been updated.', $this->textdomain ); ?></p>
                </div>
                <?php
            });
        }
    }

    public function get_db_prefix() {

        $prefix = get_option( 'wpli_db_field_prefix' );

        if ( ! $prefix ) {
            $prefix = '_';
        }

        return apply_filters( 'wpli_db_prefix', $prefix );
    }
}

?>