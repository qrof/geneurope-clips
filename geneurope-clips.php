<?php

/*
Plugin Name: GEN Europe CLIPS Wordpress plugin
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Get Projects, Events from the FLOW database through an API and display it on the website.
Version: 1.4.1
Author: marko kroflic
Author URI: http://clips.gen-europe.org
License: GPL2
*/

/*
|--------------------------------------------------------------------------
| CONSTANTS
|--------------------------------------------------------------------------
*/

if ( ! defined( 'CLIPS_BASE_FILE' ) )
    define( 'CLIPS_BASE_FILE', __FILE__ );
if ( ! defined( 'CLIPS_BASE_DIR' ) )
    define( 'CLIPS_BASE_DIR', dirname( CLIPS_BASE_FILE ) );
if ( ! defined( 'CLIPS_PLUGIN_URL' ) )
    define( 'CLIPS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if(!class_exists('WP_CLIPS_Plugin'))
{
    class WP_CLIPS_Plugin
    {
        private static $cache_time = 60 * MINUTE_IN_SECONDS;
        private static $cache_time_webdav = 60 * MINUTE_IN_SECONDS;

        /**
         * Construct the plugin object
         */
        public function __construct()
        {
            // Initialize Settings
            if( is_admin() ) {
                require_once(sprintf("%s/settings.php", dirname(__FILE__)));
                new WP_CLIPS_Settings();
            }

            //include country codes
            require_once(sprintf("%s/countrycodes_iso3166.php", dirname(__FILE__)));

            // register actions
            //if( !is_admin() ) {
            add_action('wp_enqueue_scripts', array('WP_CLIPS_Plugin', 'enqueue_scripts'));
            add_action('wp_enqueue_scripts', array('WP_CLIPS_Plugin', 'enqueue_assets'));

            add_action( 'init',  array('WP_CLIPS_Plugin', 'rewrite_rules' ));
            add_action( 'init',  array('WP_CLIPS_Plugin', 'output_options' ));
            add_action( 'template_redirect', array('WP_CLIPS_Plugin', 'rewrite_templates' ));

            // register filters
            add_filter( 'query_vars', array('WP_CLIPS_Plugin', 'rewrite_add_var' ));

            // register shortcodes
            add_shortcode( 'clips_projects_map', array('WP_CLIPS_Plugin', 'projects_map_func'));
            add_shortcode( 'clips_projects_list', array('WP_CLIPS_Plugin', 'projects_list_func'));
            add_shortcode( 'clips_events_map', array('WP_CLIPS_Plugin', 'events_map_func'));
            add_shortcode( 'clips_events_list', array('WP_CLIPS_Plugin', 'events_list_func'));
            add_shortcode( 'clips_resource_list', array('WP_CLIPS_Plugin', 'resource_list_func'));

            //}
        } // END public function __construct

        /**
         * Activate the plugin
         */
        public static function activate()
        {
            // refresh rewrites
            WP_CLIPS_Plugin::rewrite_rules();
            flush_rewrite_rules();
        } // END public static function activate

        /**
         * Deactivate the plugin
         */
        public static function deactivate()
        {
            // Do nothing
        } // END public static function deactivate

        static function enqueue_scripts() {
            // include leaflet for maps
            wp_enqueue_script('js-mapbox', 'https://api.mapbox.com/mapbox.js/v2.4.0/mapbox.js');
            // include datatables for list
            wp_enqueue_script('js-datatables', '//cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js', array( 'jquery' ) );
            // include js moment for datetime sorting
            wp_enqueue_script('js-moment', '//cdnjs.cloudflare.com/ajax/libs/moment.js/2.8.4/moment.min.js', array( 'jquery' ) );

            wp_enqueue_script('js-datatables-moment', '//cdn.datatables.net/plug-ins/1.10.12/sorting/datetime-moment.js', array( 'jquery', 'js-moment', 'js-datatables' ) );

            wp_enqueue_script( 'js-clips-plugin', plugins_url( 'js/clips.js', __FILE__ ) );
            //wp_enqueue_script( 'js-clips-options', '?clips-options=1' );


        }

        static function enqueue_assets() {
            // include leaflet for maps
            wp_enqueue_style( 'mapbox', 'https://api.mapbox.com/mapbox.js/v2.4.0/mapbox.css' );
            wp_enqueue_style( 'clips-plugin', plugins_url( 'css/style.css', __FILE__ ) );

            wp_enqueue_style( 'datatables', '//cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css' );
        }

        private static function get_categories(){
            return WP_CLIPS_Plugin::get_remote_flow("admin/categories");
        }

        public static function get_category( $id ){
            $categories = WP_CLIPS_Plugin::get_categories();
            foreach ($categories as $category){
                if ( $category->id == $id ){
                    return $category;
                }
            }
            return "";
        }

        private static function get_interactions(){
            return WP_CLIPS_Plugin::get_remote_flow("admin/interactions");
        }

        public static function display_interactions( $id_array ){
            $interactions = WP_CLIPS_Plugin::get_interactions();
            $response_array = array();
            foreach ($interactions as $interaction){
                foreach ( $id_array as $id) {
                    if ( $interaction->id == $id && $interaction->selectable == true ){
                        array_push($response_array, $interaction->name);
                    }
                }
            }
            if ( !empty($response_array) ) {
                return implode(", ", $response_array);
            }

            return "";
        }

        public static function get_interaction( $id ){
            $interactions = WP_CLIPS_Plugin::get_interactions();
            foreach ($interactions as $interaction){
                if ( $interaction->id == $id ){
                    return $interaction;
                }
            }
            return "";
        }

        private static function get_evolutions(){
            return WP_CLIPS_Plugin::get_remote_flow("admin/evolutions");
        }

        public static function get_evolution( $id ){
            $evolutions = WP_CLIPS_Plugin::get_evolutions();
            foreach ($evolutions as $evolution){
                if ( $evolution->id == $id ){
                    return $evolution;
                }
            }
            return "";
        }

        static function get_remote_flow( $path ) {
            $data = get_transient( 'clips_' . $path );
            if( empty( $data ) ) {
                // get api url from options - need to enter it!
                $clips_options = get_option( 'clips_options' );
                $api_url = !empty( $clips_options['api_url'] ) ? esc_url( $clips_options['api_url']) : '';
                // trim and add trailing slash
                $api_url = rtrim($api_url, '/') . '/';

                $api_url = esc_url($api_url);

                $response = wp_remote_get( $api_url . $path, array( 'decompress' => false, 'headers'     => array( 'Accept' => 'application/json', 'Accept-Language' => get_locale()), ) );
                if( is_wp_error( $response ) ) {
                    return array();
                }

                $data = json_decode( wp_remote_retrieve_body( $response ) );

                if( empty( $data ) ) {
                    return array();
                }

                set_transient( 'clips_' . $path , $data, WP_CLIPS_Plugin::$cache_time );
            }

            return $data;
        }

        static function get_webdav_resource( $url = '' )
        {
            // get api url from options - need to enter it!
            $clips_options = get_option('clips_options');
            $webdav_url = !empty($clips_options['webdav_url']) ? esc_url($clips_options['webdav_url']) : '';
            $webdav_username = !empty($clips_options['webdav_username']) ? esc_attr($clips_options['webdav_username']) : '';
            $webdav_password = !empty($clips_options['webdav_password']) ? esc_attr($clips_options['webdav_password']) : '';

            $url = '/' . ltrim($url, '/');
            $url = $webdav_url . $url;


            $pathinfo = pathinfo($url);
            if (isset($pathinfo['extension'])) {
                $response = wp_remote_get($url, array('headers' => array('Authorization' => 'Basic ' . base64_encode($webdav_username . ':' . $webdav_password)
                )));

                if (is_wp_error($response)) {
                    wp_die("Resource not found.");
                }

                if (is_array($response)) {
                    $header = $response['headers']; // array of http header lines
                    $body = $response['body']; // use the content

                    if (isset($header['set-cookie'])) {
                        unset($header['set-cookie']);
                    }
                    if (isset($header['x-powered-by'])) {
                        unset($header['x-powered-by']);
                    }
                    if (isset($header['content-disposition'])) {
                        unset($header['content-disposition']);
                    }

                    header("Content-Disposition: attachment; filename=".$pathinfo['basename']);

                    foreach ($header as $name => $value) {
                        header($name . ': ' . $value);
                    }
                    flush();
                    echo $body;
                    die();
                }
            }
        }

        static function get_webdav_resourcelist( $url = '' ) {
            // get api url from options - need to enter it!
            $clips_options = get_option( 'clips_options' );
            $webdav_url = !empty( $clips_options['webdav_url'] ) ? esc_url( $clips_options['webdav_url']) : '';
            $webdav_username = !empty( $clips_options['webdav_username'] ) ? esc_attr( $clips_options['webdav_username']) : '';
            $webdav_password = !empty( $clips_options['webdav_password'] ) ? esc_attr( $clips_options['webdav_password']) : '';

            if( empty($url) ) {
                $url = $webdav_url;
            } else {
                $url = '/' . ltrim($url, '/') ;
                $url = $webdav_url . $url;
            }

            $url = esc_url($url);

            $data = get_transient( 'clips_' . $url );
            if( empty( $data ) ) {
                $response = wp_remote_request( $url, array( 'method' => 'PROPFIND', 'decompress' => false, 'headers' => array( 'Authorization' => 'Basic ' . base64_encode( $webdav_username . ':' . $webdav_password )
                ) ) );

                if( is_wp_error( $response ) ) {
                    return array();
                }

                $data = wp_remote_retrieve_body( $response );
                if( empty( $data ) ) {
                    return array();
                }

                // simplify xml definition
                $data = str_replace( 'd:', '', $data);

                set_transient( 'clips_' . $url , $data, WP_CLIPS_Plugin::$cache_time_webdav );
            }

            $xml = simplexml_load_string( str_replace( 'd:', '', $data) );

            $path = wp_parse_url($webdav_url, PHP_URL_PATH);

            foreach ($xml->xpath('//response') as $asset) {
                $href = str_replace($path, '/resources', $asset->href);
                $name = pathinfo($href)['basename'];
                $modified = $asset->propstat->prop->getlastmodified;

                if ($asset->propstat->prop->resourcetype->collection) {
                    $size = $asset->propstat->prop->{'quota-used-bytes'};
                    $assets[] = array("mime-type"=>"folder", "name"=>$name, "uri"=>$href, "last-modified"=>$modified, "size"=>$size);
                }
                else {
                    $contenttype = $asset->propstat->prop->getcontenttype;
                    $contenttype = explode('/', $contenttype)[1];
                    $size = $asset->propstat->prop->getcontentlength;
                    $assets[] = array("mime-type"=>$contenttype, "name"=>$name, "uri"=>$href, "last-modified"=>$modified, "size"=>$size);
                }
            }

            return $assets;
        }

        public static function get_event_fromurl() {
            if ( get_query_var( 'clips-event' ) ) {
                $event_id = get_query_var('clips-event');
                if ( get_query_var( 'clips-lang' ) ) {
                    $lang = get_query_var('clips-lang');
                }
                else
                    $lang = "";

                $path = "events/" . $lang . "/" . $event_id . ".json";

                $event = WP_CLIPS_Plugin::get_remote_flow($path);

                return $event;
            }
            wp_die("Event not found.");
        }

        public static function get_project_fromurl() {
            if ( get_query_var( 'clips-project' ) ) {
                $project_id = get_query_var('clips-project');
                if ( get_query_var( 'clips-lang' ) ) {
                    $lang = get_query_var('clips-lang');
                }
                else
                    $lang = "";

                $path = "projects/" . $lang . "/" . $project_id . ".json";

                $project = WP_CLIPS_Plugin::get_remote_flow($path);

                return $project;
            }

            wp_die("Project not found.");

        }

        // [clips_projects_map width="100%" height="400px"]
        public static function projects_map_func( $atts ) {
            $a = shortcode_atts( array(
                'height' => '400px',
                'width' => '100%'
            ), $atts );

            $projects = WP_CLIPS_Plugin::get_remote_flow("projects.json");

            if( empty( $projects ) ) {
                return;
            }

            $clips_options = get_option( 'clips_options' );
            $mapbox_token = $clips_options['mapbox_token'];

            ob_start();
            ?>
            <div id="projects_map" style="height: <?php echo $a['height'];?>; width: <?php echo $a['width'];?>;"></div>
            <script>
                if (typeof clips_projects == 'undefined') {
                    var clips_projects = <?php echo json_encode($projects); ?>;
                }
                var clips_projects_map;

                var locationEurope = new L.LatLng(47.626349,7.336981);

                L.mapbox.accessToken = '<?php echo $mapbox_token ?>';

                if ( !L.mapbox.accessToken.trim() ) {
                    // create the tile layer with correct attribution
                    var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
                    var osmAttrib='Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
                    var osm = new L.TileLayer(osmUrl, {minZoom: 4, maxZoom: 12, attribution: osmAttrib});

                    clips_projects_map = L.map('projects_map');
                    clips_projects_map.setView(locationEurope,4);

                    // start the map
                    clips_projects_map.addLayer(osm);
                }
                else {
                    clips_projects_map = L.mapbox.map('projects_map', 'mapbox.streets').setView(locationEurope, 4);
                }


                L.geoJson(clips_projects, {
                    style: function (feature) {
                        return {color: feature.properties.color};
                    },
                    onEachFeature: function (feature, layer) {
                        var popupContent = '<p><strong><a href="<?php echo get_home_url(); ?>' + feature.properties.uri + '/">' +
                            feature.properties.name + '</a></strong></p>';

                        if (feature.properties && feature.properties.abstract) {
                            popupContent += '<p>' + feature.properties.abstract + '</p>';
                            popupContent += '<p>' + feature.properties.description + '</p>';
                        }

                        layer.bindPopup(popupContent);
                    }/*,
                    filter: function(feature, layer) {
                        return feature.properties.evolution > 0;
                    }*/
                }).addTo(clips_projects_map);
            </script>
            <?php
            return ob_get_clean();
        }

        // [clips_projects_list width="100%"]
        public static function projects_list_func( $atts ) {
            $a = shortcode_atts( array(
                'width' => '100%',
            ), $atts );

            $projects = WP_CLIPS_Plugin::get_remote_flow("projects.json");

            if( empty( $projects ) ) {
                return "";
            }

            ob_start();
            ?>
            <div id="projects_list_wrapper">
                <table id="projects_list"  style="width: <?php echo $a['width'];?>;">
                    <thead>
                    <tr>
                        <th>Project</th>
                        <th>Interactions</th>
                        <th class="nowrap">Location</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach( $projects->features as $project ) {
                    ?>
                        <tr>
                            <td>
                                <span><a href="<?php echo get_home_url() . $project->properties->uri; ?>"><?php echo trim($project->properties->name); ?></a></span>
                                <br/>
                                <?php echo (!empty($project->properties->evolution) ? "(" . WP_CLIPS_Plugin::get_evolution($project->properties->evolution)->name . ")" : ""); ?>
                                <br/>
                                <span><?php echo trim($project->properties->abstract); ?></span>
                            </td>
                            <td>
                                <span><?php echo (!empty($project->properties->interactions) ? WP_CLIPS_Plugin::display_interactions($project->properties->interactions) : ""); ?></span>
                            </td>
                            <td class="nowrap right"><?php
                                echo (!empty($project->properties->address) ? trim($project->properties->address->city) : "" );
                                echo (!empty($project->properties->address->countryCode) ? ", " . WP_CLIPS_Countries::getCountry(trim($project->properties->address->countryCode)) : "" );
                                ?></td>
                        </tr>
                    <?php
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            <script>
                if (typeof clips_projects == 'undefined') {
                    clips_projects = <?php echo json_encode($projects); ?>;
                }
                jQuery(document).ready(function(){
                    /*jQuery('#projects_list').DataTable({
                        data: clips_projects.features,
                        columns: [
                            { data: 'properties.name' },
                            { data: 'properties.abstract' },
                            { data: 'properties.address.city' },
                            { data: 'properties.address.countryCode' }
                        ],
                        columnDefs: [ {
                            targets: [ 0 ],
                            data: null,
                            defaultContent: "Click to edit"
                        } ]
                    });*/
                    jQuery('#projects_list').DataTable();
                });
            </script>
            <?php
            return ob_get_clean();
        }

        // [clips_resource_list width="100%"]
        public static function resource_list_func( $atts ) {
            $a = shortcode_atts( array(
                'width' => '100%',
                'folder' => ''
            ), $atts );

            $path = get_query_var( 'clips-resource' );
            if ( empty($path) ) {
                $path = $a['folder'];
            }
            $assets = WP_CLIPS_Plugin::get_webdav_resourcelist( $path );

            if( empty( $assets ) ) {
                return "";
            }

            ob_start();
            ?>
            <div class="resource_list_wrapper">
                <table id="resource_list_<?php echo urlencode($path);?>"  style="width: <?php echo $a['width'];?>;">
                    <thead>
                    <tr>
                        <th></th>
                        <th>File</th>
                        <th class="nowrap">Size</th>
                        <th class="nowrap">Last modified</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    array_shift($assets);
                    foreach( $assets as $asset ) {
                        $name = urldecode($asset['name']);
                        $img = wp_mime_type_icon('default');
                        if ( !empty($asset['mime-type'])) {
                            $img = wp_mime_type_icon($asset['mime-type']);
                        }
                        ?>
                        <tr>
                            <td><span class="media-icon image-icon"><img width="12" height="16" class="size-12x16" src="<?php echo $img; ?>" title="<?php echo $asset['mime-type']; ?>" /></span>
                            </td>
                            <td>
                                <span><a href="<?php echo $asset['uri']; ?>" title="<?php echo $name; ?>"><?php echo $name; ?></a></span>
                            </td>
                            <td class="nowrap filesize"><?php echo size_format($asset['size']); ?></td>
                            <td class="nowrap date"><?php echo date_i18n( get_option( 'date_format' ), strtotime($asset['last-modified'])); ?></td>

                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            <script>
                if (typeof clips_resources_<?php echo urlencode($path);?> == 'undefined') {
                    clips_resources_<?php echo urlencode($path);?> = <?php echo json_encode($assets); ?>;
                }
                jQuery(document).ready(function(){
                    jQuery('#resource_list_<?php echo urlencode($path);?>').DataTable({
                        paging: false
                    });
                });
            </script>
            <?php
            return ob_get_clean();
        }

        // [clips_events_map width="100%" height="400px"]
        public static function events_map_func( $atts ) {
            $a = shortcode_atts( array(
                'height' => '400px',
                'width' => '100%'
            ), $atts );

            $events = WP_CLIPS_Plugin::get_remote_flow("events.json");

            if( empty( $events ) ) {
                return "";
            }

            $clips_options = get_option( 'clips_options' );
            $mapbox_token = $clips_options['mapbox_token'];

            ob_start();
            ?>
            <div id="events_map" style="height: <?php echo $a['height'];?>; width: <?php echo $a['width'];?>;"></div>
            <script>
                if (typeof clips_events == 'undefined') {
                    var clips_events = <?php echo json_encode($events); ?>;
                }
                var clips_events_map;

                var locationEurope = new L.LatLng(47.626349,7.336981);

                L.mapbox.accessToken = '<?php echo $mapbox_token ?>';

                if ( !L.mapbox.accessToken.trim() ) {
                    // create the tile layer with correct attribution
                    var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
                    var osmAttrib='Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
                    var osm = new L.TileLayer(osmUrl, {minZoom: 4, maxZoom: 12, attribution: osmAttrib});

                    clips_events_map = L.map('projects_map');
                    clips_events_map.setView(locationEurope,4);

                    // start the map
                    clips_events_map.addLayer(osm);
                }
                else {
                    clips_events_map = L.mapbox.map('events_map', 'mapbox.streets').setView(locationEurope, 4);
                }

                L.geoJson(clips_events, {
                    style: function (feature) {
                        return {color: feature.properties.color};
                    },
                    /*
                     pointToLayer: function (feature, latlng) {
                     return L.circleMarker(latlng, {
                     radius: 8,
                     fillColor: feature.properties.color,
                     color: "#000",
                     weight: 1,
                     opacity: 1,
                     fillOpacity: 0.8
                     });
                     },*/
                    onEachFeature: function (feature, layer) {
                        var start = new Date(feature.properties.start);
                        start = start.toLocaleDateString() + ', ' + start.toLocaleTimeString();

                        var stop = new Date(feature.properties.stop);
                        stop = stop.toLocaleDateString() + ', ' + stop.toLocaleTimeString();

                        var popupContent = '<p><strong><a href="<?php echo get_home_url(); ?>' + feature.properties.uri + '/">' +
                            feature.properties.name + '</a></strong></p>';

                        popupContent += '<p>' + start + '&nbsp;-&nbsp;' + stop + '</p>';

                        if (feature.properties && feature.properties.abstract) {
                            popupContent += '<p>' + feature.properties.abstract + '</p>';
                            popupContent += '<p>' + feature.properties.description + '</p>';
                        }

                        layer.bindPopup(popupContent);
                    }/*,
                     filter: function(feature, layer) {
                     return feature.properties.evolution > 0;
                     }*/
                }).addTo(clips_events_map);
                /*
                 var overlayMaps = {
                 "Cities": cities
                 };

                 L.control.layers(overlayMaps).addTo(clips_projects_map);
                 */
            </script>
            <?php
            return ob_get_clean();
        }

        // [clips_events_list width="100%"]
        public static function events_list_func( $atts ) {
            $a = shortcode_atts( array(
                'width' => '100%',
            ), $atts );

            $events = WP_CLIPS_Plugin::get_remote_flow("events.json");

            if( empty( $events ) ) {
                return "";
            }

            ob_start();
            ?>
            <div id="events_list_wrapper">
                <table id="events_list"  style="width: <?php echo $a['width'];?>;">
                    <thead>
                    <tr>
                        <th>When</th>
                        <th>Event</th>
                        <th>Location</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach( $events->features as $event ) {
                        ?>
                        <tr>
                            <td><?php echo date_i18n( get_option( 'date_format' ), strtotime( $event->properties->start ) ); ?></td>
                            <td>
                                <span><a href="<?php echo get_home_url() . $event->properties->uri; ?>"><?php echo trim($event->properties->name); ?></a></span>
                                <br/>
                                <span><?php echo trim($event->properties->abstract); ?></span>
                            </td>
                            <td><?php
                                echo (!empty($event->properties->address) ? trim($event->properties->address->city) : "" );
                                echo (!empty($event->properties->address->countryCode) ? ", " . WP_CLIPS_Countries::getCountry(trim($event->properties->address->countryCode)) : "" );
                                ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            <script>
                if (typeof clips_events == 'undefined') {
                    clips_events = <?php echo json_encode($events); ?>;
                }
                jQuery(document).ready(function(){
                    jQuery.fn.dataTable.moment( '<?php echo get_option( 'date_format' ); ?>' );
                    jQuery('#events_list').DataTable();
                });
            </script>
            <?php
            return ob_get_clean();
        }


        public static function rewrite_add_var( $vars )
        {
            $vars[] = 'clips-project';
            $vars[] = 'clips-event';
            $vars[] = 'clips-lang';
            $vars[] = 'clips-options';
            $vars[] = 'clips-resource';
            return $vars;
        }

        public static function rewrite_rules() {
            add_rewrite_rule( 'projects/([^/]+)/([^/]+)/?$', 'index.php?clips-project=$matches[2]&clips-lang=$matches[1]', 'top' );
            add_rewrite_rule( 'events/([^/]+)/([^/]+)/?$', 'index.php?clips-event=$matches[2]&clips-lang=$matches[1]', 'top' );
            add_rewrite_rule( 'resources/(.*)?$', 'index.php?clips-resource=$matches[1]', 'top' );
        }

        static function output_options() {
            //$nonce = $_REQUEST['_wpnonce'];
            //if ( !empty($_GET['clips-options']) && ! wp_verify_nonce( $nonce, $this->clips-nonce) ) {
                // This nonce is not valid.
            //    die( 'Security check' );
            //} else {

            if ( !empty($_GET['clips-options']) ) {
                $response = array(
                    'categories'	    => WP_CLIPS_Plugin::get_remote_flow("admin/categories"),
                    'interactions'	    => WP_CLIPS_Plugin::get_remote_flow("admin/interactions"),
                    'evolutions'        => WP_CLIPS_Plugin::get_remote_flow("admin/evolutions")
                );

                $json_response = 'var clips_options = ' . json_encode( $response ) . ';';

                @header( 'Content-Type: application/javascript; charset=' . get_option( 'blog_charset' ) );
                echo $json_response;
                die();
            }
        }

        public static function rewrite_templates() {
            if ( get_query_var( 'clips-project' ) ) {
                add_filter( 'template_include', function() {
                    return plugin_dir_path( __FILE__ ) . 'project.php';
                });
            }

            if ( get_query_var( 'clips-event' ) ) {
                add_filter( 'template_include', function() {
                    return plugin_dir_path( __FILE__ ) . 'event.php';
                });
            }

            if ( get_query_var( 'clips-resource' ) ) {
                add_filter( 'template_include', function() {
                    return plugin_dir_path( __FILE__ ) . 'resource.php';
                });
            }
        }
    } // END class WP_CLIPS_Plugin
} // END if(!class_exists('WP_CLIPS_Plugin'))

if(class_exists('WP_CLIPS_Plugin'))
{
    // Installation and uninstallation hooks
    register_activation_hook(__FILE__, array('WP_CLIPS_Plugin', 'activate'));
    register_deactivation_hook(__FILE__, array('WP_CLIPS_Plugin', 'deactivate'));

    // instantiate the plugin class
    $wp_clips_plugin = new WP_CLIPS_Plugin();
}

