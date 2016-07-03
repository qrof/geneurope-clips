<?php

/*
Plugin Name: SCIPP website helper plugin
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: A brief description of the Plugin.
Version: 1.0
Author: marko kroflic
Author URI: http://www.mkr.si
License: GPL2
*/

if(!class_exists('WP_SCIPP_Plugin'))
{
    class WP_SCIPP_Plugin
    {
        private static $cache_time = MINUTE_IN_SECONDS;

        /**
         * Construct the plugin object
         */
        public function __construct()
        {
            // Initialize Settings
            if( is_admin() ) {
                require_once(sprintf("%s/settings.php", dirname(__FILE__)));
                $scipp_settings_page = new ScippSettingsPage();
            }

            // register actions
            //if( !is_admin() ) {
            add_action('wp_enqueue_scripts', array('WP_SCIPP_Plugin', 'enqueue_scripts'));
            add_action('wp_enqueue_scripts', array('WP_SCIPP_Plugin', 'enqueue_assets'));

            add_action( 'init',  array('WP_SCIPP_Plugin', 'rewrite_rules' ));
            add_action( 'init',  array('WP_SCIPP_Plugin', 'output_options' ));
            add_action( 'template_redirect', array('WP_SCIPP_Plugin', 'rewrite_templates' ));

            // register filters
            add_filter( 'query_vars', array('WP_SCIPP_Plugin', 'rewrite_add_var' ));

            // register shortcodes
            add_shortcode( 'scipp_projects_map', array('WP_SCIPP_Plugin', 'projects_map_func'));
            add_shortcode( 'scipp_projects_list', array('WP_SCIPP_Plugin', 'projects_list_func'));
            //}
        } // END public function __construct

        /**
         * Activate the plugin
         */
        public static function activate()
        {
            // refresh rewrites
            rewrite_rules();
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
            wp_enqueue_script('js-leaflet', '//cdn.leafletjs.com/leaflet/v0.7.7/leaflet.js');
            // include datatables for list
            wp_enqueue_script('js-datatables', '//cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js', array( 'jquery' ) );

            //wp_enqueue_script('js-options', '/?scipp-options=1' );
        }

        static function enqueue_assets() {
            // include leaflet for maps
            wp_enqueue_style( 'css-leaflet', '//cdn.leafletjs.com/leaflet/v0.7.7/leaflet.css' );
            wp_enqueue_style( 'css-scipp-plugin', plugins_url( 'css/style.css', __FILE__ ) );

            wp_enqueue_style( 'css-datatables', '//cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css' );
        }

        public static function get_categories(){
            return WP_SCIPP_Plugin::get_remote_flow("admin/categories");
        }

        public static function get_interactions(){
            return WP_SCIPP_Plugin::get_remote_flow("admin/interactions");
        }

        public static function get_evolutions(){
            return WP_SCIPP_Plugin::get_remote_flow("admin/evolutions");
        }

        static function get_remote_flow( $path ) {
            $data = get_transient( 'scipp_' . $path );
            if( empty( $data ) ) {
                $scipp_options = get_option( 'scipp_options' );
                $api_url = isset( $scipp_options['api_url'] ) ? esc_url( $scipp_options['api_url']) : '';
                $response = wp_remote_get( $api_url . $path, array( 'decompress' => false, 'headers'     => array( 'Accept' => 'application/json'), ) );
                if( is_wp_error( $response ) ) {
                    return array();
                }

                $data = json_decode( wp_remote_retrieve_body( $response ) );

                if( empty( $data ) ) {
                    return array();
                }

                set_transient( 'scipp_' . $path , $data, WP_SCIPP_Plugin::$cache_time );
            }

            return $data;
        }

        private static function detectlanguage() {
            $langcode = explode(";", $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            $langcode = explode(",", $langcode['0']);
            return $langcode['0'];
        }

        // [scipp_map show_projects="true" show_events="true" only_active="true" width="100%" height="400px"]
        public static function projects_map_func( $atts ) {
            $a = shortcode_atts( array(
                'height' => '400px',
                'width' => '100%',
                'show_projects' => 'true',
                'show_events' => 'true',
                'only_active' => 'true',
            ), $atts );

            if( $a['show_projects'] ) {
                $projects = WP_SCIPP_Plugin::get_remote_flow("projects.json");
            }

            if( empty( $projects ) ) {
                return;
            }

            ob_start();
            ?>
            <div id="projects_map" style="height: <?php echo $a['height'];?>; width: <?php echo $a['width'];?>;"></div>
            <script>
                if (scipp_projects == undefined) {
                    var scipp_projects = <?php echo json_encode($projects); ?>;
                }
                var scipp_projects_map = L.map('projects_map');

                // create the tile layer with correct attribution
                var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
                var osmAttrib='Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
                var osm = new L.TileLayer(osmUrl, {minZoom: 4, maxZoom: 12, attribution: osmAttrib});

                // start the map
                scipp_projects_map.setView(new L.LatLng(48.821,7.141),5);
                scipp_projects_map.addLayer(osm);

                L.geoJson(scipp_projects, {
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
                        var popupContent = '<p><strong><a href="' + feature.properties.uri + '/">' +
                            feature.properties.name + '</a></strong></p>';

                        if (feature.properties && feature.properties.abstract) {
                            popupContent += '<p>' + feature.properties.abstract + '</p>';
                            popupContent += '<p>' + feature.properties.description + '</p>';
                        }

                        layer.bindPopup(popupContent);
                    },/*
                    filter: function(feature, layer) {
                        return feature.properties.evolution > 0;
                    }*/
                }).addTo(scipp_projects_map);
                /*
                var overlayMaps = {
                    "Cities": cities
                };

                L.control.layers(overlayMaps).addTo(scipp_projects_map);
                */
            </script>
            <?php
            return ob_get_clean();
        }

        // [scipp_projects_map width="100%" height="400px"]
        public static function projects_list_func( $atts ) {
            $a = shortcode_atts( array(
                'height' => '400px',
                'width' => '100%',
            ), $atts );

            $projects = WP_SCIPP_Plugin::get_remote_flow("projects.json");

            if( empty( $projects ) ) {
                return;
            }

            ob_start();
            ?>
            <div id="projects_list_wrapper">
                <table id="projects_list"  style="width: <?php echo $a['width'];?>;">
                    <thead>
                    <tr>
                        <th>Project</th>
                        <th>Location</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach( $projects->features as $project ) {
                    ?>
                        <tr>
                            <td>
                                <span><a href="<?php echo $project->properties->uri; ?>"><?php echo trim($project->properties->name); ?></a></span>
                                <br/>
                                <span><?php echo trim($project->properties->abstract); ?></span>
                            </td>
                            <td><?php
                                echo (!empty($project->properties->address) ? trim($project->properties->address->city) : "" );
                                echo (!empty($project->properties->address->countryCode) ? ", " . trim($project->properties->address->countryCode) : "" );
                                ?></td>
                        </tr>
                    <?php
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            <script>
                if (scipp_projects == undefined) {
                    scipp_projects = <?php echo json_encode($projects); ?>;
                }
                jQuery(document).ready(function(){
                    /*jQuery('#projects_list').DataTable({
                        data: scipp_projects.features,
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

        // [scipp_map show_projects="true" show_events="true" only_active="true" width="100%" height="400px"]
        public static function projects_map_func( $atts ) {
            $a = shortcode_atts( array(
                'height' => '400px',
                'width' => '100%',
                'show_projects' => 'true',
                'show_events' => 'true',
                'only_active' => 'true',
            ), $atts );

            if( $a['show_projects'] ) {
                $projects = WP_SCIPP_Plugin::get_remote_flow("projects.json");
            }

            if( empty( $projects ) ) {
                return;
            }

            ob_start();
            ?>
            <div id="projects_map" style="height: <?php echo $a['height'];?>; width: <?php echo $a['width'];?>;"></div>
            <script>
                if (scipp_projects == undefined) {
                    var scipp_projects = <?php echo json_encode($projects); ?>;
                }
                var scipp_projects_map = L.map('projects_map');

                // create the tile layer with correct attribution
                var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
                var osmAttrib='Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
                var osm = new L.TileLayer(osmUrl, {minZoom: 4, maxZoom: 12, attribution: osmAttrib});

                // start the map
                scipp_projects_map.setView(new L.LatLng(48.821,7.141),5);
                scipp_projects_map.addLayer(osm);

                L.geoJson(scipp_projects, {
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
                        var popupContent = '<p><strong><a href="' + feature.properties.uri + '/">' +
                            feature.properties.name + '</a></strong></p>';

                        if (feature.properties && feature.properties.abstract) {
                            popupContent += '<p>' + feature.properties.abstract + '</p>';
                            popupContent += '<p>' + feature.properties.description + '</p>';
                        }

                        layer.bindPopup(popupContent);
                    },/*
                     filter: function(feature, layer) {
                     return feature.properties.evolution > 0;
                     }*/
                }).addTo(scipp_projects_map);
                /*
                 var overlayMaps = {
                 "Cities": cities
                 };

                 L.control.layers(overlayMaps).addTo(scipp_projects_map);
                 */
            </script>
            <?php
            return ob_get_clean();
        }

        // [scipp_projects_map width="100%" height="400px"]
        public static function projects_list_func( $atts ) {
            $a = shortcode_atts( array(
                'height' => '400px',
                'width' => '100%',
            ), $atts );

            $projects = WP_SCIPP_Plugin::get_remote_flow("projects.json");

            if( empty( $projects ) ) {
                return;
            }

            ob_start();
            ?>
            <div id="projects_list_wrapper">
                <table id="projects_list"  style="width: <?php echo $a['width'];?>;">
                    <thead>
                    <tr>
                        <th>Project</th>
                        <th>Location</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach( $projects->features as $project ) {
                        ?>
                        <tr>
                            <td>
                                <span><a href="<?php echo $project->properties->uri; ?>"><?php echo trim($project->properties->name); ?></a></span>
                                <br/>
                                <span><?php echo trim($project->properties->abstract); ?></span>
                            </td>
                            <td><?php
                                echo (!empty($project->properties->address) ? trim($project->properties->address->city) : "" );
                                echo (!empty($project->properties->address->countryCode) ? ", " . trim($project->properties->address->countryCode) : "" );
                                ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            <script>
                if (scipp_projects == undefined) {
                    scipp_projects = <?php echo json_encode($projects); ?>;
                }
                jQuery(document).ready(function(){
                    /*jQuery('#projects_list').DataTable({
                     data: scipp_projects.features,
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


        public static function rewrite_add_var( $vars )
        {
            $vars[] = 'scipp-project';
            $vars[] = 'scipp-event';
            $vars[] = 'scipp-lang';
            $vars[] = 'scipp-options';
            return $vars;
        }

        public static function rewrite_rules() {
            add_rewrite_rule( 'projects/([^/]+)/([^/]+)/?$', 'index.php?scipp-project=$matches[2]&scipp-lang=$matches[1]', 'top' );
            add_rewrite_rule( 'events/([^/]+)/([^/]+)/?$', 'index.php?scipp-event=$matches[2]&scipp-lang=$matches[1]', 'top' );
        }

        static function output_options() {
            //$nonce = $_REQUEST['_wpnonce'];
            //if ( !empty($_GET['scipp-options']) && ! wp_verify_nonce( $nonce, $this->scipp-nonce) ) {
                // This nonce is not valid.
            //    die( 'Security check' );
            //} else {

            if ( !empty($_GET['scipp-options']) ) {
                $response = array(
                    'categories'	    => WP_SCIPP_Plugin::get_remote_flow("admin/categories"),
                    'interactions'	    => WP_SCIPP_Plugin::get_remote_flow("admin/interactions"),
                    'evolutions'        => WP_SCIPP_Plugin::get_remote_flow("admin/evolutions")
                );

                $json_response = 'var scipp_options = ' . json_encode( $response ) . ';';

                @header( 'Content-Type: application/javascript; charset=' . get_option( 'blog_charset' ) );
                echo $json_response;
                die();
            }
        }

        public static function rewrite_templates() {
            if ( get_query_var( 'scipp-project' ) ) {
                add_filter( 'template_include', function() {
                    return plugin_dir_path( __FILE__ ) . 'project.php';
                });
            }

            if ( get_query_var( 'scipp-event' ) ) {
                add_filter( 'template_include', function() {
                    return plugin_dir_path( __FILE__ ) . 'event.php';
                });
            }
        }
    } // END class WP_SCIPP_Plugin
} // END if(!class_exists('WP_SCIPP_Plugin'))

if(class_exists('WP_SCIPP_Plugin'))
{
    // Installation and uninstallation hooks
    register_activation_hook(__FILE__, array('WP_SCIPP_Plugin', 'activate'));
    register_deactivation_hook(__FILE__, array('WP_SCIPP_Plugin', 'deactivate'));

    // instantiate the plugin class
    $wp_scipp_plugin = new WP_SCIPP_Plugin();
}

if(!class_exists('WP_SCIPP_Project')){
    class WP_SCIPP_Project{
        public $project;
        /**
         * Construct the object
         */
        public function __construct()
        {
            $this->project = self::get_project();
        }

        private function get_project() {
            if ( get_query_var( 'scipp-project' ) ) {
                $project_id = get_query_var('scipp-project');
                if ( get_query_var( 'scipp-lang' ) ) {
                    $lang = get_query_var('scipp-lang');
                }
                else
                    $lang = "";

                $path = "projects/" . $lang . "/" . $project_id . ".json";

                $project = WP_SCIPP_Plugin::get_remote_flow($path);

                return $project;
            }
            else {
                wp_die("Project not found.");
            }
        }

        public function interactions()
        {
            $interactions = $this->project->properties->interactions;

            $i = "";

            foreach( $interactions as $interaction ) {
                    $i .= "<span>" . $interaction->name . "</span>, ";

            }

            if (!empty($i)){
                $i = rtrim($i, ", ");
                return "<h4 class=\"project-interactions-title\">Interactions</h4>" . $i;
            }

            return $i;
        }

        public function categories()
        {
            $cat_codes = $this->project->properties->cat_codes;

            $c = "";

            foreach( WP_SCIPP_Plugin::get_categories() as $category ) {
                if ( in_array($category->id, $cat_codes) ) {
                    $c .= "<span>" . $category->name . "</span>, ";
                }
            }

            if (!empty($c)){
                $c = rtrim($c, ", ");
                return "<h4 class=\"project-categories-title\">Categories</h4>" . $c;
            }

            return $c;
        }

        public function contacts() {
            $contactRoles = $this->project->properties->contactRoles;

            $c = "";

            foreach( $contactRoles as $contactRole ) {
                $c .= "<div class=\"row cat\">
                            <div class=\"col-xs-5\">" . $contactRole->role . "</div>
                            <div class=\"col-xs-7\">" . $contactRole->contact->firstname . " " . $contactRole->contact->surename ."</div>
                        </div>";
            }

            /*
             * <li class="four columns sh_member_wrapper" data-color="#f79d03"><a href="http://scipp.nl/v1/member/paul-hendriksen/" class="sh_member_link fade_anchor"><div class="member_colored_block boxed_shadow"><div class="member_colored_block_in"><div class="navicon-plus sh_member_link_icon body_bk_color"></div></div><img src=http://scipp.nl/v1/wp-content/uploads/2014/09/paul-300x275.jpg alt="" class="mb_in_img" /></div> </a><div class="sh_member_name zero_color header_font bd_headings_text_shadow"><h3 class="small fade_anchor"><a href="http://scipp.nl/v1/member/paul-hendriksen/" class="fade_anchor">Paul Hendriksen </a></h3></div><div class="sh_member_function zero_color bd_headings_text_shadow header_font">Social, Ecologic, Economic</div><div class="tiny_line"></div><div class="sh_member_email default_color"><a href="mailto:p&#97;&#117;l&#46;&#104;e&#110;drikse&#110;&#64;a&#97;rd&#101;h&#117;&#105;&#115;.&#110;&#108;">&#112;&#97;u&#108;&#46;&#104;en&#100;ri&#107;&#115;&#101;&#110;&#64;&#97;&#97;rd&#101;hui&#115;.nl </a></div><div class="clearfix"></div></li>
             */

            if (!empty($c)){
                return "<h4 class=\"project-contacts-title\">Contacts</h4><div class=\"row contact-role\">" . $c . "</div>";
            }

            return $c;
        }


        public function events() {

        }

        public function address()
        {
            $address = $this->project->properties->address;

            ob_start();
            ?>
            <h4 class="project-address-title">Address</h4>
            <?php
            if (!empty($address->street)) {
                ?>
            <span class="project-address-1">
                <span><?php echo $address->street; ?></span>&nbsp;
            </span>
            <?php
            }

            if (!empty($address->city)) {
                 ?>
            <br/><span class="project-address-2">
                <span><?php echo $address->postcode; ?></span>
                <span><?php echo $address->city; ?></span>
            </span>
            <?php
            }

            if (!empty($address->country)) {
                 ?>
                 <br/><span class="project-address-3">
                     <span><?php echo $address->country; ?></span>
                 </span>
                 <?php
             }
            return ob_get_clean();
        }
    }
}

