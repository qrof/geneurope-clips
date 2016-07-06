<?php
/**
 * The template for displaying a SCIPP event
 *
 */

get_header(); ?>

<?php
$e = WP_SCIPP_Plugin::get_event_fromurl();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

        <?php
        if ( !empty( $e ) && !empty( $e->id )) {

            ?>
            <article id="post-<?php echo $e->id; ?>" <?php post_class("event", null); ?>>
                <header class="entry-header">
                    <h1 class="entry-title"><?php echo $e->properties->name; ?></h1>
                    <?php
                    if (!empty($e->properties->evolution)) {
                        ?>
                    <div class="event-evolution">
                        <span><?php echo $e->properties->evolution->name; ?></span>
                    </div>
                        <?php
                    }
                    ?>
                </header><!-- .entry-header -->

                <div class="entry-content">
                    <div id="event-details" class="container">
                        <div class="row">
                            <!-- LEFT Column -->
                            <div class="event-details-left col-md-7 col-sm-7">
                                <?php
                                    if (!empty($e->properties->start)) {
                                        ?>
                                        <div class="event-openingtimes"><p><?php
                                        echo date_i18n( get_option( 'date_format' ), strtotime( $e->properties->start ) );

                                        if (!empty($e->properties->stop)) {
                                            echo "&nbsp;-&nbsp;" . date_i18n(get_option('date_format'), strtotime($e->properties->stop));
                                        }
                                                ?></p>
                                        </div><?php
                                    }

                                    if (!empty($e->properties->abstract)) {
                                        ?>
                                        <div class="clear"></div>
                                        <div class="event-abstract"><p>
                                        <?php echo $e->properties->abstract; ?>
                                        </p></div><?php
                                    }
                                    if (!empty($e->properties->description)) {
                                        ?><div class="clear"></div>
                                    <div class="event-description">
                                        <?php echo $e->properties->description; ?>
                                    </div><?php
                                    }

                                    $interactions = $e->properties->interactions;

                                    if (!empty($interactions)) {
                                        $i = "";
                                        foreach ($interactions as $interaction) {
                                            $interaction = WP_SCIPP_Plugin::get_interaction($interaction->id);
                                            if (!empty($interaction)) {
                                                $i .= "<span>" . $interaction->name . "</span> - <span>" . $interaction->description . "</span>, ";
                                            }
                                        }


                                        $i = rtrim($i, ", ");
                                        ?>
                                        <div class="clear"></div>
                                        <div class="event-interactions">
                                            <h4 class="event-interactions-title">Interactions</h4>
                                            <div><?php echo $i; ?></div>
                                        </div>
                                    <?php
                                    }

                                    $cat_codes = $e->properties->cat_codes;

                                    $c = "";

                                    foreach( $cat_codes as $cat_code ) {
                                        $category = WP_SCIPP_Plugin::get_category($cat_code);
                                        if (!empty($category)) {
                                            $c .= "<span>" . $category->name . "</span> - <span>" . $category->description . "</span>, ";
                                        }
                                    }

                                    if (!empty($c)){
                                        $c = rtrim($c, ", ");
                                        ?>
                                         <div class="clear"></div>
                                         <div class="event-categories">
                                             <h4 class="event-categories-title">Categories</h4>
                                             <div><?php echo $c; ?></div>
                                         </div>
                                    <?php
                                    }

                                    $address = $e->properties->address;
                                ?>
                                <div class="clear"></div>
                                <div class="event-address">
                                    <h4 class="event-address-title">Address</h4>
                                    <?php
                                        if (!empty($address->street)) {
                                            ?>
                                            <span class="event-address-1">
                                                <span><?php echo $address->street; ?></span>
                                            </span>
                                            <?php
                                        }

                                        if (!empty($address->city)) {
                                            ?>
                                            <br/><span class="event-address-2">
                                                <span><?php echo $address->postcode; ?></span>
                                                <span><?php echo $address->city; ?></span>
                                            </span>
                                            <?php
                                        }

                                        if (!empty($address->country)) {
                                            ?>
                                            <br/><span class="event-address-3">
                                                 <span><?php echo $address->country; ?></span>
                                             </span>
                                            <?php
                                        }
                                    ?>
                                </div>
                                <?php
                                    $contactRoles = $e->properties->contactRoles;
                                    if (!empty($contactRoles)){
                                        ?>
                                <div class="clear"></div>
                                <div class="event-contacts">
                                    <h4 class="project-contacts-title">Contacts</h4>
                                    <div class="contact-role">
                                        <?php
                                        foreach( $contactRoles as $contactRole ) { ?>
                                            <div class="contactrole">
                                                <div class="col-xs-5"><?php echo $contactRole->role; ?></div>
                                                <div class="clear"></div>
                                                <div class="col-xs-7">
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
                                    ?>
                            </div>
                            <!-- RIGHT Column-->
                            <div class="event-details-right col-md-5 col-sm-5">
                                <?php
                                if ( !empty($e->properties->project) ) {
                                ?>
                                <div class="event-project">
                                    <p class="event-project-title">This event is part of the project<br/><a href="<?php echo $e->properties->project->uri; ?>"><?php echo $e->properties->project->name; ?></a></p>
                                    <div class="col-r-1"><a href="<?php echo $e->properties->project->uri; ?>"><img class="project-image" src="<?php echo $e->properties->project->thumbnail; ?>"/></a></div>
                                </div>
                                <?php
                                }

                                if ( !empty($e->properties->thumbnail) ) {
                                    ?>
                                    <div class="col-r-1"><img class="event-image" src="<?php echo $e->properties->thumbnail; ?>"/></div>
                                    <?php
                                }
                                ?>
                                <div id="event-map"></div>
                                <script>
                                    var scipp_event = <?php echo json_encode($e); ?>;

                                    var scipp_event_map = L.map('event-map');

                                    // create the tile layer with correct attribution
                                    var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
                                    var osmAttrib='Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
                                    var osm = new L.TileLayer(osmUrl, {minZoom: 4, maxZoom: 12, attribution: osmAttrib});

                                    // start the map
                                    var coords;
                                    for (var i in scipp_event.geometry.geometries ) {
                                        if ( scipp_event.geometry.geometries[i].type == "Point" ) {
                                            coords = scipp_event.geometry.geometries[i].coordinates;
                                            break;
                                        }
                                    }
                                    if ( coords != undefined ) {
                                        scipp_event_map.setView(new L.LatLng(coords[1],coords[0]),9);
                                        scipp_event_map.addLayer(osm);

                                        L.geoJson(scipp_event, {
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
                                        }).addTo(scipp_event_map);

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
