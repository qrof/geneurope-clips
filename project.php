<?php
/**
 * The template for displaying a SCIPP project
 *
 */

get_header(); ?>

<?php
$p = new WP_SCIPP_Project();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

        <?php
        if ( isset( $p->project ) && isset( $p->project->id )) {

        ?>
        <article id="post-<?php echo $p->project->id; ?>" <?php post_class("project", null); ?>>

            <header class="entry-header">
                <h1 class="entry-title"><?php echo $p->project->properties->name; ?></h1>
                <div class="project-evolution"><span><?php echo $p->project->properties->evolution->name;?></span></div>
            </header><!-- .entry-header -->

            <div class="entry-content">
                <?php the_content(); ?>
                <div id="project-details" class="container">
                    <div class="row">
                        <!-- LEFT Column -->
                        <div class="project-details-left col-md-7 col-sm-7">
                            <h3 class="project-abstract"><?php echo $p->project->properties->abstract; ?></h3>
                            <div class="project-description"><?php echo $p->project->properties->description; ?></div>
                            <div class="project-interactions"><?php echo $p->interactions();?></div>
                            <div class="project-categories"><?php echo $p->categories();?></div>
                            <div class="project-address"><?php echo $p->address();?></div>
                            <div class="project-contacts"><?php echo $p->contacts();?></div>
                            <div class="project-events"><?php echo $p->events();?></div>
                        </div>
                        <!-- RIGHT Column-->
                        <div class="project-details-right col-md-5 col-sm-5">
                            <?php
                            if ( !empty($p->project->properties->thumbnail) ) {
                            ?>
                            <div class="col-r-1"><img class="project-image" src="<?php echo $p->project->properties->thumbnail; ?>"/></div>
                            <?php
                            }
                            ?>
                            <div id="project-map"></div>
                            <script>
                                var scipp_project = <?php echo json_encode($p->project); ?>;
                                
                                var scipp_project_map = L.map('project-map');

                                // create the tile layer with correct attribution
                                var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
                                var osmAttrib='Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
                                var osm = new L.TileLayer(osmUrl, {minZoom: 4, maxZoom: 12, attribution: osmAttrib});

                                // start the map
                                var coords;
                                for (var i in scipp_project.geometry.geometries ) {
                                    if ( scipp_project.geometry.geometries[i].type == "Point" ) {
                                        coords = scipp_project.geometry.geometries[i].coordinates;
                                        break;
                                    }
                                }
                                if ( coords != undefined ) {
                                    scipp_project_map.setView(new L.LatLng(coords[1],coords[0]),9);
                                    scipp_project_map.addLayer(osm);

                                    L.geoJson(scipp_project, {
                                        style: function (feature) {
                                            return {color: feature.properties.color};
                                        },
                                        onEachFeature: function (feature, layer) {
                                            var popupContent = '<p><strong>' +
                                                feature.properties.name + '</strong></p>';

                                            if (feature.properties && feature.properties.abstract) {
                                                popupContent += '<p>' + feature.properties.abstract + '</p>';
                                            }

                                            layer.bindPopup(popupContent);
                                        }
                                    }).addTo(scipp_project_map);

                                }

                            </script>
                        </div>
                    </div>
                </div>
            </div><!-- .entry-content -->

        </article><!-- #post-## -->

        <?php
            // If comments are open or we have at least one comment, load up the comment template.
            if ( comments_open() || get_comments_number() ) :
                comments_template();
            endif;

        }
        ?>

    </main><!-- .site-main -->
</div><!-- .content-area -->

<?php get_footer(); ?>
