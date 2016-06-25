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
            // Do nothing
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
            wp_enqueue_style( 'css-datatables', '//cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css' );

            wp_enqueue_script('js-leaflet', '//cdn.leafletjs.com/leaflet/v0.7.7/leaflet.js');
            wp_enqueue_script('js-datatables', '//cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js', array( 'jquery' ) );
        }

        static function get_remote_projects() {
            $projects = get_transient( 'remote_projects' );
            if( empty( $projects ) ) {
                $response = wp_remote_get( 'http://dev.gruenanteil.net/projects.json' );
                if( is_wp_error( $response ) ) {
                    return array();
                }

                $projects = json_decode( wp_remote_retrieve_body( $response ) );

                if( empty( $projects ) ) {
                    return array();
                }

                set_transient( 'remote_projects', $projects, MINUTE_IN_SECONDS );
            }

            return $projects;
        }

        // [scipp_projects_map width="100%" height="400px"]
        public static function scipp_projects_map_func( $atts ) {
            $a = shortcode_atts( array(
                'height' => '400px',
                'width' => '100%',
            ), $atts );

            $projects = WP_SCIPP_Plugin::get_remote_projects();

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

                // start the map in South-East England
                scipp_projects_map.setView(new L.LatLng(48.821,7.141),5);
                scipp_projects_map.addLayer(osm);

                L.geoJson(scipp_projects, {
                    style: function (feature) {
                        return {color: feature.properties.color};
                    },
                    onEachFeature: function (feature, layer) {
                        if (feature.properties && feature.properties.abstract) {
                            layer.bindPopup(feature.properties.abstract);
                        }
                    },
                    filter: function(feature, layer) {
                        return feature.geometry.geometries.type == "Point";
                    }
                }).addTo(scipp_projects_map);
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

            $projects = WP_SCIPP_Plugin::get_remote_projects();

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