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
        /**
         * Construct the plugin object
         */
        public function __construct()
        {
            // register actions
            //if( !is_admin() ) {
                add_action('wp_enqueue_scripts', array('WP_SCIPP_Plugin', 'scipp_enqueued_assets'));

            // register shortcodes
            add_shortcode( 'scipp_projects_map', array('WP_SCIPP_Plugin', 'scipp_projects_map_func'));
            add_shortcode( 'scipp_projects_list', array('WP_SCIPP_Plugin', 'scipp_projects_list_func'));
            //}
        } // END public function __construct

        /**
         * Activate the plugin
         */
        public static function activate()
        {
            // refresh rewrites
            scipp_rewrite_rules();
            flush_rewrite_rules();
        } // END public static function activate

        /**
         * Deactivate the plugin
         */
        public static function deactivate()
        {
            // Do nothing
        } // END public static function deactivate

        static function scipp_enqueued_assets() {
            // include leaflet for maps
            wp_enqueue_style( 'css-leaflet', '//cdn.leafletjs.com/leaflet/v0.7.7/leaflet.css' );
            //wp_enqueue_style( 'css-scipp-plugin', plugins_url( 'css/style.css', __FILE__ ) );

            wp_enqueue_style( 'css-datatables', '//cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css' );

            wp_enqueue_script('js-leaflet', '//cdn.leafletjs.com/leaflet/v0.7.7/leaflet.js');
            wp_enqueue_script('js-datatables', '//cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js', array( 'jquery' ) );
        }

        static function get_remote_flow( $path ) {
            $data = get_transient( 'scipp_' . $path );
            if( empty( $data ) ) {
                $response = wp_remote_get( 'http://dev.gruenanteil.net/' . $path . '.json' );
                if( is_wp_error( $response ) ) {
                    return array();
                }

                $data = json_decode( wp_remote_retrieve_body( $response ) );

                if( empty( $data ) ) {
                    return array();
                }

                set_transient( 'scipp_' . $path , $data, MINUTE_IN_SECONDS );
            }

            return $data;
        }

        private static function detectlanguage() {
            $langcode = explode(";", $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            $langcode = explode(",", $langcode['0']);
            return $langcode['0'];
        }

        // [scipp_map show_projects="true" show_events="true" only_active="true" width="100%" height="400px"]
        public static function scipp_projects_map_func( $atts ) {
            $a = shortcode_atts( array(
                'height' => '400px',
                'width' => '100%',
                'show_projects' => 'true',
                'show_events' => 'true',
                'only_active' => 'true',
            ), $atts );

            if( $a['show_projects'] ) {
                $projects = WP_SCIPP_Plugin::get_remote_flow("projects");
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
                        var popupContent = '<p><strong><a href="' + feature.properties.uri + '">' +
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
        public static function scipp_projects_list_func( $atts ) {
            $a = shortcode_atts( array(
                'height' => '400px',
                'width' => '100%',
            ), $atts );

            $projects = WP_SCIPP_Plugin::get_remote_flow("projects");

            if( empty( $projects ) ) {
                return;
            }

            ob_start();
            ?>
            <div id="projects_list_wrapper" style="height: <?php echo $a['height'];?>; width: <?php echo $a['width'];?>;">
                <table id="projects_list" class="display" cellspacing="0" width="<?php echo $a['width'];?>">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Abstract</th>
                        <th>City</th>
                        <th>Country</th>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr>
                        <th>Name</th>
                        <th>Abstract</th>
                        <th>City</th>
                        <th>Country</th>
                    </tr>
                    </tfoot>
                    <tbody>
                    <?php
                    foreach( $projects->features as $project ) {
                    ?>
                        <tr>
                            <td>
                                <a href="#<?php echo $project->id; ?>"><?php echo $project->properties->name; ?></a>
                            </td>
                            <td><?php echo $project->properties->abstract; ?></td>
                            <td><?php echo ($project->properties->address ? $project->properties->address->city : "" ); ?></td>
                            <td><?php echo ($project->properties->address ? $project->properties->address->countryCode : "" ); ?></td>
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

        public static function scipp_rewrite_rules() {
            add_rewrite_rule( 'projects/([^/]+)/([^/]+)', 'index.php?project=$matches[2]&lang=$matches[1]', 'top' );
            add_rewrite_rule( 'events/([^/]+)/([^/]+)', 'index.php?event=$matches[2]&lang=$matches[1]', 'top' );
        }

        public static function scipp_rewrite_templates() {

            if ( get_query_var( 'project' ) && is_singular( 'project' ) ) {
                add_filter( 'template_include', function() {
                    return get_template_directory() . '/single-project.php';
                });
            }

            if ( get_query_var( 'event' ) && is_singular( 'event' ) ) {
                add_filter( 'template_include', function() {
                    return get_template_directory() . '/single-event.php';
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

    add_action( 'init', array('WP_SCIPP_Plugin', 'scipp_rewrite_rules' ));
    add_action( 'template_redirect', array('WP_SCIPP_Plugin', 'scipp_rewrite_templates' ));

    // instantiate the plugin class
    $wp_scipp_plugin = new WP_SCIPP_Plugin();
}