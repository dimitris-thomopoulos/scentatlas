<?php
/**
 * Template for displaying single Perfume posts in WooCommerce style.
 * Based on WooCommerce content-single-product.php but stripped of shop logic.
 */

defined( 'ABSPATH' ) || exit;
?>


<div id="primary" class="content-area primary">
<main id="main" class="site-main">
<div class="ast-woocommerce-container" style="padding: 0">

<div id="product-<?php the_ID(); ?>" style="padding: 0" <?php post_class( 'perfume ast-article-single ast-woo-product-no-review desktop-align-left tablet-align-left mobile-align-left product type-product post-3864 status-publish first instock product_cat-organic-soap has-post-thumbnail shipping-taxable purchasable product-type-simple' ); ?>>

    <div class="summary entry-summary">
        <?php
        
        // ======== EasyRDF working example ========
            require_once get_stylesheet_directory() . '/vendor/autoload.php';

            // Use fully qualified class names (no `use` statements needed)
            \EasyRdf\RdfNamespace::set('perfume', 'http://example.org/perfume/');

            // underscore replacement function 
            
            function cleanVar ($var) {
                $result = str_replace('_', ' ', $var->localName());
                return $result;
            }
            
            // Load ontology
            $graph = new \EasyRdf\Graph();
            $ttl_path = get_stylesheet_directory() . '/includes/perfume_ontology_final.ttl';
            $graph->parseFile($ttl_path, 'turtle');
            
            $perfume_name = get_the_title();

            $perfume_ontology_name = str_replace(' ', '_', trim($perfume_name));
            $uri = 'http://example.org/perfume/' . $perfume_ontology_name;
            $resource = $graph->resource($uri);
            if (!$resource) return '';

            $name = str_replace(array("’", "’", "’", "'", "_"), " ", trim(basename($resource->getUri()))); // Twilly_d_Herms_Eau_Ginger_Herms

            
            $image = $resource->get('perfume:image');
            $fragranceFamily = $resource->get('perfume:fragranceFamily');
            $baseNotes = $resource->all('perfume:hasBaseNote');
            $middleNotes = $resource->all('perfume:hasMiddleNote');
            $topNotes = $resource->all('perfume:hasTopNote');
            $year = $resource->get('perfume:year');
            $concentration = $resource->get('perfume:concentration');
            $desc = $resource->get('perfume:description');
        
//        the_post_thumbnail();
        
           echo '<img src="'. $image . '"></img>';
                                        
        // Description / content
        echo '<div class="woocommerce-product-details__short-description">';
        ?>
        <div class='product-details'>
            <?php
                       
            // Title
            echo '<h1 class="product_title entry-title">' . $name . '</h1>'; 

            // Custom fields
//            $desc  = get_post_meta( get_the_ID(), 'description', true );
//            $concentration     = get_field('concentration');      // e.g. "Eau de Parfum"
//            $year              = get_field('year');               // e.g. "2019"
//            $fragrance_family  = get_field('fragrance_family');   // e.g. "Woody"
//            $top_note          = get_field('top_note');           // e.g. "Lavender, jasmine"
//            $middle_note       = get_field('middle_note');        // e.g. "Rose, Lily"
//            $base_note         = get_field('base_note');          // e.g. "Tobacco"
        
        ?>
            
            <p class="fragrance-meta">
        <strong>
            <span class="concentration"><?php if ($concentration) : echo esc_html($concentration) . ' · '; endif; ?></span>
            <span class="perfume-year"><?php if ($year) : echo esc_html($year) . ' · '; endif; ?></span>
            <span class="fragrance_family"><?php if (!empty($fragranceFamily)) : echo 'Fragrance family: ' . $fragranceFamily; endif; ?></span>
        </strong>
    </p>

    <?php if (!empty($topNotes)) : ?>
    <p class="notes top-notes"><h6>Top notes:</h6><p><?php 
    
    $topHTML = '';
    foreach ($topNotes as $topNote) {
                $topHTML .= cleanVar($topNote) . ', ';
            }
        echo substr($topHTML, 0, -2);    ?></p></p>
    <?php endif;  ?>

    <?php if (!empty($middleNotes)) : ?>
        <p class="notes middle-notes"><h6>Middle notes:</h6><p><?php
        
        $midHTML = '';
        foreach ($middleNotes as $middleNote) {
                $midHTML .= cleanVar($middleNote) . ', ';
            }
        echo substr($midHTML, 0, -2);    ?></p></p>
    <?php endif; ?>

    <?php if (!empty($baseNotes)) : ?>
        <p class="notes base-notes"><h6>Base notes:</h6><p><?php 
        
        $baseHTML = '';
        foreach ($baseNotes as $baseNote) {
                $baseHTML .= cleanVar($baseNote) . ', ';
            }
        echo substr($baseHTML, 0, -2);    ?></p></p>
    <?php endif; ?>
        
        <a href="#perfume-map" class="button btn-map" action="#perfume-map">VIEW NOTES MAP</a>           
        <a href="#recommended-heading" class="button btn-recommended" action="#recommended-heading">VIEW RECOMMENDATIONS</a>           
        </div>
        
        <?php
        the_content();
        echo '</div>';      
        
        if ( $desc ) {
            echo '<h6 class="full-description">Description:</h6><br/><p class="perfume-description summary">' . $desc  . '</p>';
        }
        
        ?>
        
    </div><!-- .summary -->
    
    

    <div class="clearfix"></div>

</div><!-- #product-<?php the_ID(); ?> -->

</div>
</main>
</div>