<?php
/**
 * The template for displaying a CLIPS project
 *
 */

get_header('default'); ?>

<?php
$p = WP_CLIPS_Plugin::get_project_fromurl();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

        <?php
        if ( !empty( $p ) && !empty( $p->id )) {

        ?>
        <article id="post-<?php echo $p->id; ?>" <?php post_class("project prk_inner_block twelve columns centered", null); ?>>

            <header class="entry-header bd_headings_text_shadow zero_color">
                <div class="prk_titlify_father"><h1 class="entry-title header_font"><?php echo $p->properties->name; ?></h1></div>
                <?php
                if (!empty($p->properties->evolution)) {
                    ?>
                    <div class="clear"></div>
                    <div class="project-evolution">
                        <span><?php echo $p->properties->evolution->name; ?></span>
                    </div>
                    <?php
                }
                ?>
            </header><!-- .entry-header -->

            <div class="entry-content">
                <div id="project-details" class="container">
                        <!-- LEFT Column -->
                        <div class="project-details-left">
                            <?php
                            if (!empty($p->properties->abstract)) {
                            ?>
                            <div class="clear"></div>
                            <div class="project-abstract"><p>
                                    <?php echo $p->properties->abstract; ?>
                                </p></div><?php
                            }
                            if (!empty($p->properties->description)) {
                                ?><div class="clear"></div>
                                <div class="project-description">
                                <?php echo $p->properties->description; ?>
                                </div><?php
                            }

                            $interactions = $p->properties->interactions;

                            if (!empty($interactions)) {
                                $i = "";
                                foreach ($interactions as $interaction) {
                                    $interaction = WP_CLIPS_Plugin::get_interaction($interaction->id);
                                    if (!empty($interaction)) {
                                        $i .= "<span>" . $interaction->name . "</span> - <span>" . $interaction->description . "</span>, ";
                                    }
                                }


                                $i = rtrim($i, ", ");
                                ?>
                                <div class="clear"></div>
                                <div class="project-interactions">
                                    <h4 class="project-interactions-title">Interactions</h4>
                                    <div><?php echo $i; ?></div>
                                </div>
                                <?php
                            }

                            $cat_codes = $p->properties->cat_codes;

                            $c = "";

                            foreach( $cat_codes as $cat_code ) {
                                $category = WP_CLIPS_Plugin::get_category($cat_code);
                                if (!empty($category)) {
                                    $c .= "<span>" . $category->name . "</span> - <span>" . $category->description . "</span>, ";
                                }
                            }

                            if (!empty($c)){
                                $c = rtrim($c, ", ");
                                ?>
                                <div class="clear"></div>
                                <div class="project-categories">
                                    <h4 class="project-categories-title">Categories</h4>
                                    <div><?php echo $c; ?></div>
                                </div>
                                <?php
                            }

                            $address = $p->properties->address;
                            ?>
                            <div class="clear"></div>
                            <div class="project-address">
                                <h4 class="project-address-title">Address</h4>
                                <?php
                                if (!empty($address->street)) {
                                    ?>
                                    <span class="project-address-1">
                                                <span><?php echo $address->street; ?></span>
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
                                ?>
                            </div>
                            <?php
                            $contactRoles = $p->properties->contactRoles;
                            if (!empty($contactRoles)){
                                ?>
                                <div class="clear"></div>
                                <div class="project-contacts">
                                    <h4 class="project-contacts-title">Contacts</h4>
                                    <div class="contact-roles">
                                        <?php
                                        foreach( $contactRoles as $contactRole ) { ?>
                                            <div class="contact-role">
                                                <div class="role"><?php echo $contactRole->role; ?></div>
                                                <div class="clear"></div>
                                                <div class="contact-details">
                                                    <?php echo (!empty($contactRole->contact->organisation)) ? "<span><strong>" . $contactRole->contact->organisation . "</strong></span><br/>" : ""; ?>
                                                    <?php echo (!empty($contactRole->contact->firstname)) ? "<span><strong>" . $contactRole->contact->function . " " . $contactRole->contact->firstname . " " . $contactRole->contact->surename . "</strong></span><br/>" : ""; ?>
                                                    <?php echo (!empty($contactRole->contact->fon)) ? "<span>Phone: " . $contactRole->contact->fon  . "</span><br/>": ""; ?>
                                                    <?php echo (!empty($contactRole->contact->mobile)) ? "<span>Mobile: " . $contactRole->contact->mobile  . "</span><br/>": ""; ?>
                                                    <?php echo (!empty($contactRole->contact->email)) ? "<span>E-mail: <a href=\"" . antispambot( 'mailto:' . $contactRole->contact->email ) . "\">" . antispambot( $contactRole->contact->email ) . "</a></span><br/>": ""; ?>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                        ?></div></div>
                                <?php
                            }

                            $events = $p->properties->events;
                            if (!empty($events)){
                                ?>
                                <div class="clear"></div>
                                <div class="project-events">
                                    <h4 class="project-events-title">Related Events</h4>
                                        <?php

                                        foreach( $events as $event ) { ?>
                                            <div class="project-event">
                                                <div class="project-event-title"><a href="<?php echo get_site_url() . $event->uri; ?>"><?php echo $event->name; ?></a></div>
                                                <div class="clear"></div>
                                                    <?php
                                                    if (!empty($event->start)) {
                                                    ?>
                                                    <div class="project-event-times"><p><?php
                                                            echo date_i18n( get_option( 'date_format' ), strtotime( $event->start ) );

                                                            if (!empty($event->stop)) {
                                                                echo "&nbsp;-&nbsp;" . date_i18n(get_option('date_format'), strtotime($event->stop));
                                                            }
                                                            ?></p>
                                                    </div><?php
                                                    }
                                                    ?>
                                            </div>
                                            <?php
                                        }
                                        ?></div>
                                <?php
                            }
                            ?>
                        </div>
                        <!-- RIGHT Column-->
                        <div class="project-details-right">
                            <?php
                            //TODO: display networks

                            if ( !empty($p->properties->thumbnail) ) {
                            ?>
                            <div class="project-thumbnail"><img class="project-thumbnail-image" src="<?php echo $p->properties->thumbnail; ?>"/></div>
                            <?php
                            }

                            $clips_options = get_option( 'clips_options' );
                            $mapbox_token = $clips_options['mapbox_token'];

                            ?>
                            <div id="project-map"></div>
                            <script>
                                var clips_project = <?php echo json_encode($p); ?>;
                                
                                var clips_project_map;

                                // start the map
                                var coords = new L.LatLng(47.626349,7.336981); //Europe

                                for (var i in clips_project.geometry.geometries ) {
                                    if ( clips_project.geometry.geometries[i].type == "Point" ) {
                                        coords = clips_project.geometry.geometries[i].coordinates;
                                        coords = new L.LatLng(coords[1],coords[0]);
                                        break;
                                    }
                                }

                                L.mapbox.accessToken = '<?php echo $mapbox_token ?>';
                                if ( !L.mapbox.accessToken.trim() ) {
                                    // create the tile layer with correct attribution
                                    var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
                                    var osmAttrib='Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
                                    var osm = new L.TileLayer(osmUrl, {minZoom: 4, maxZoom: 12, attribution: osmAttrib});

                                    clips_project_map = L.map('project-map');
                                    clips_project_map.setView(coords,9);
                                    clips_project_map.addLayer(osm);
                                }
                                else {
                                    clips_project_map = L.mapbox.map('project-map', 'mapbox.streets').setView(coords, 12);
                                }

                                L.geoJson(clips_project, {
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
                                }).addTo(clips_project_map);



                            </script>
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

<?php get_footer('default'); ?>
