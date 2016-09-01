<?php
/**
 * Created by PhpStorm.
 * User: marko
 * Date: 29. 06. 16
 * Time: 20:37
 */

class ScippSettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin',
            'CLIPS Settings',
            'manage_options',
            'clips-setting-admin',
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'clips_options' );
        ?>
        <div class="wrap">
            <h2>CLIPS Settings</h2>
            <p>For API access URL, please contact GEN Europe at info@gen-europe.org.</p>
            <p>To use Mapbox tiles instead of OpenStreeMap, please register at <a href="http://www.mapbox.com" target="_blank">www.mapbox.com</a>, visit the Studio area to get the Mapbox Access token and paste it into the field below.</p>
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields( 'clips_option_group' );
                do_settings_sections( 'clips-setting-admin' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            'clips_option_group', // Option group
            'clips_options', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_db', // ID
            'CLIPS DB Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'clips-setting-admin' // Page
        );

        add_settings_section(
            'setting_section_maps', // ID
            'CLIPS Map Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'clips-setting-admin' // Page
        );

        add_settings_field(
            'api_url',
            'API url',
            array( $this, 'api_url_callback' ),
            'clips-setting-admin',
            'setting_section_db'
        );


        add_settings_field(
            'mapbox_token', // ID
            'Mapbox token', // Title
            array( $this, 'mapbox_token_callback' ), // Callback
            'clips-setting-admin', // Page
            'setting_section_maps' // Section
        );


    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['mapbox_token'] ) )
            $new_input['mapbox_token'] = esc_attr( $input['mapbox_token'] );

        if( isset( $input['api_url'] ) )
            $new_input['api_url'] = esc_url_raw( $input['api_url'] );

        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your settings below:';
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function mapbox_token_callback()
    {
        printf(
            '<input type="text" id="mapbox_token" name="clips_options[mapbox_token]" value="%s" class="regular-text code"/>',
            isset( $this->options['mapbox_token'] ) ? esc_attr( $this->options['mapbox_token']) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function api_url_callback()
    {
        printf(
            '<input type="url" id="api_url" name="clips_options[api_url]" value="%s" class="regular-text code" />',
            isset( $this->options['api_url'] ) ? esc_url( $this->options['api_url']) : ''
        );
    }
}