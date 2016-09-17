<?php
/**
 * The template for displaying a CLIPS project
 *
 */

$path = get_query_var( 'clips-resource' );
$pathinfo = pathinfo($path);

if (isset($pathinfo['extension'])) {
    WP_CLIPS_Plugin::get_webdav_resource( $path );
}


get_header();
?>
<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <article id="post-<?php echo $pathinfo['basename']; ?>" <?php post_class("resource prk_inner_block twelve columns centered", null); ?>>

            <header class="entry-header bd_headings_text_shadow zero_color">
                <div class="prk_titlify_father"><h1 class="entry-title header_font"><a href="../"><?php echo urldecode($pathinfo['dirname']); ?></a><?php echo ' / '. urldecode($pathinfo['basename']); ?></h1></div>
            </header><!-- .entry-header -->

            <div class="entry-content">
                <div id="resource-details" class="container">
                    <div class="row">
                        <!-- LEFT Column -->
                        <div class="resource-details-left">
                            <?php
                                echo WP_CLIPS_Plugin::resource_list_func( [] );
                            ?>
                        </div>
                        <!-- RIGHT Column-->
                        <div class="resource-details-right">

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

        ?>

    </main><!-- .site-main -->
</div><!-- .content-area -->

<?php get_footer(); ?>
