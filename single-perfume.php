<?php
defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

while ( have_posts() ) :
    the_post();

    wc_get_template( 'content-single-perfume.php' ); // our cloned layout

endwhile;

//echo get_stylesheet_directory();  

echo '<div id="perfume-map">' . do_shortcode('[perfume-map]') . '</div>';
echo do_shortcode('[perfume_recommendations]');

get_footer( 'shop' );