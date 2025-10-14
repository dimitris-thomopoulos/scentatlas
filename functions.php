<?php
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'child-style', get_stylesheet_uri() );
});

// replace products with perfumes in shop (explore) page

add_action('pre_get_posts', function($query) {
    // Only modify the main WooCommerce shop query on the frontend
    if (!is_admin() && $query->is_main_query() && is_shop()) {
        
        // Change post type from 'product' to your custom one
        $query->set('post_type', 'perfume');

        // Optional: you can also customize order, taxonomy filters, etc.
        // Example:
        // $query->set('orderby', 'date');
        // $query->set('order', 'DESC');
    }
});


// perfume grid shortcode

// === Perfume Grid with AJAX Load More ===
function show_perfumes_grid() {
    // Initial perfume query (first 10)
    $args = array(
        'post_type' => 'perfume',
        'posts_per_page' => 8,
        'paged' => 1,
    );

    $query = new WP_Query($args);
    ob_start();

    if ($query->have_posts()) {
        echo '<div id="perfume-grid" class="perfume-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            $image = get_field('image');
            $description = get_field('description');
            $permalink = get_permalink();
			echo '<a href="'.esc_url($permalink).'" class="perfume-item" style="text-decoration:none;color:inherit;">';
			if ($image) {
				echo '<img src="'.esc_url($image).'" alt="'.get_the_title().'">';
			}
			echo '<h3>'.get_the_title().'</h3>';
			if ($description) {
				echo '<p>'.wp_trim_words($description, 25).'</p>';
			}
			echo '</a>';
        }
        echo '</div>';
        echo '<a href="/explore" id="view-catalogue" class="button" data-page="1" style="display: table; margin:20px auto;">VIEW CATALOGUE</a>';
    }
    wp_reset_postdata();

    // Enqueue the JS file
    wp_enqueue_script('load-more-perfumes', get_stylesheet_directory_uri() . '/js/load-more-perfumes.js', array('jquery'), null, true);
    wp_localize_script('load-more-perfumes', 'perfume_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));

    return ob_get_clean();
}
add_shortcode('perfume_grid', 'show_perfumes_grid');


// === AJAX Handler ===
function load_more_perfumes_ajax() {
    $paged = isset($_POST['page']) ? intval($_POST['page']) + 1 : 1;

    $args = array(
        'post_type' => 'perfume',
        'posts_per_page' => 8,
        'paged' => $paged,
    );

    $query = new WP_Query($args);
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $image = get_field('image');
            $description = get_field('description');
            $permalink = get_permalink();
			echo '<a href="'.esc_url($permalink).'" class="perfume-item" style="text-decoration:none;color:inherit;">';
			if ($image) {
    			echo '<img src="'.esc_url($image).'" alt="'.get_the_title().'">';
			}
			echo '<h3>'.get_the_title().'</h3>';
			if ($description) {
    		echo '<p>'.wp_trim_words($description, 25).'</p>';
			}
			echo '</a>';
        }
    }
    wp_reset_postdata();
    wp_die(); // required for AJAX to end properly
}
add_action('wp_ajax_load_more_perfumes', 'load_more_perfumes_ajax');
add_action('wp_ajax_nopriv_load_more_perfumes', 'load_more_perfumes_ajax');


// perfume summary

add_action( 'woocommerce_single_product_summary', function() {
    if ( get_post_type() === 'perfume' ) {
        $desc = get_field( 'description' ); // ACF field

        if ( $desc ) {
            echo '<div class="perfume-description">';
            echo '<h3>Perfume Details</h3>';
            echo wp_kses_post( $desc ); // allow some HTML if you used WYSIWYG
            echo '</div>';
        }
    }
}, 25 );

function enqueue_leaflet_assets() {
    // Leaflet CSS
    wp_enqueue_style(
        'leaflet-css',
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
    );

    // Leaflet JS
    wp_enqueue_script(
        'leaflet-js',
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
        array(), // no dependencies
        null,
        true // load in footer
    );
}
add_action('wp_enqueue_scripts', 'enqueue_leaflet_assets');

// custom shortcode for perfume map


function perfume_leaflet_map_shortcode() {
    
    global $post;
    if ($post->post_type !== 'perfume') return '';

    // 1. Get notes (can be comma-separated strings or arrays)
    $get_notes = function($field) use ($post) {
        $value = get_field($field, $post->ID);
        if (is_array($value)) {
            return array_map('trim', $value);
        }
        if (is_string($value)) {
            // Split by comma and clean spaces
            return array_filter(array_map('trim', explode(',', $value)));
        }
        return [];
    };

    $top_notes    = $get_notes('top_note');
    $middle_notes = $get_notes('middle_note');
    $base_notes   = $get_notes('base_note');

    // 2. Load mappings
    $origins_file = WP_CONTENT_DIR . '/uploads/2025/10/notes_origins.json';
    if (!file_exists($origins_file)) return 'Notes origins file not found.';
    $origins = json_decode(file_get_contents($origins_file), true);

    $world_file = WP_CONTENT_DIR . '/uploads/2025/10/countries.geojson';
    if (!file_exists($world_file)) return 'World GeoJSON not found.';
    $world = json_decode(file_get_contents($world_file), true);

      // 3. Build list of note types and countries
    $note_groups = [
        'top'    => $top_notes,
        'middle' => $middle_notes,
        'base'   => $base_notes
    ];

    $country_notes = [];

    foreach ($note_groups as $type => $notes) {
        // Inside your foreach ($note_groups as $type => $notes) loop
foreach ($notes as $note) {
    if (!empty($note) && !empty($origins[$note])) {
        foreach ($origins[$note] as $country) {
            if (!isset($country_notes[$country])) {
                $country_notes[$country] = [
                    'types' => [],
                    'count' => 0,
                    'notes' => [] // ✅ NEW
                ];
            }

            $country_notes[$country]['count']++;

            if (!in_array($type, $country_notes[$country]['types'])) {
                $country_notes[$country]['types'][] = $type;
            }

            // ✅ Add the specific note name
            if (!in_array($note, $country_notes[$country]['notes'])) {
                $country_notes[$country]['notes'][] = $note;
            }
        }
    }
}

    }
    
    // --- 3a. Find the country with the most notes ---
$top_country = null;
$max_count   = 0;

foreach ($country_notes as $country => $data) {
    if ($data['count'] > $max_count) {
        $max_count   = $data['count'];
        $top_country = $country;
    }
}
    
    // --- 3b. Save unique country list to ACF field ---
    if (!empty($country_notes)) {
        $unique_countries = array_keys($country_notes);
        $country_list = implode(', ', $unique_countries);

        // Save or update the 'countries' field for this perfume
        update_field('countries', $country_list, $post->ID);
    }

    // 4. Filter and label features
    $filtered_features = array_values(array_filter($world['features'], function ($f) use ($country_notes) {
        $name = $f['properties']['ADMIN'] ?? $f['properties']['name'] ?? '';
        return isset($country_notes[$name]);
    }));

    foreach ($filtered_features as &$f) {
    $country_name = $f['properties']['ADMIN'] ?? $f['properties']['name'] ?? '';
    if (isset($country_notes[$country_name])) {
        $f['properties']['note_types'] = implode(',', $country_notes[$country_name]['types']);
        $f['properties']['note_count'] = $country_notes[$country_name]['count'];
        $f['properties']['notes']      = implode(', ', $country_notes[$country_name]['notes']); // ✅ NEW
    } else {
        $f['properties']['note_types'] = '';
        $f['properties']['note_count'] = 0;
        $f['properties']['notes']      = '';
    }
}


    $filtered = [
        'type' => 'FeatureCollection',
        'features' => $filtered_features
    ];

    // 5. Save to tmp folder
    $upload_dir = wp_upload_dir();
    $tmp_path = $upload_dir['basedir'] . '/tmp-geojson/';
    $tmp_url  = $upload_dir['baseurl'] . '/tmp-geojson/';

    if (!file_exists($tmp_path)) mkdir($tmp_path, 0775, true);
    $file_path = $tmp_path . 'perfume-' . $post->ID . '.geojson';
    file_put_contents($file_path, json_encode($filtered, JSON_PRETTY_PRINT));

    // Force HTTPS to avoid Mixed Content
    $file_url = str_replace('http://', 'https://', $tmp_url) . 'perfume-' . $post->ID . '.geojson';

    // 6. Output shortcode using note_count as the numeric value
    $shortcode = <<<HTML
    [leaflet-map zoom="8" fitbounds detect-retina height=600]
    [leaflet-geojson src=$file_url][/leaflet-geojson]
    [choropleth valueProperty="note_count" scale="#c2e3bb, #9bd799, #74b366, #4b7722" steps=12 mode=q legend showclasses=0 fillopacity=1]
        <strong>{name}:</strong> {note_count} {note_types} fragrance notes ({notes})
    [/choropleth]
    [zoomhomemap]
    HTML;
    
    


// Return shortcode and JS together

$script = <<<JS
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mapContainer = document.querySelector('.leaflet-map');
    if (!mapContainer) return;

    // Create preloader overlay
    const loader = document.createElement('div');
    loader.classList.add('leaflet-preloader');
    mapContainer.appendChild(loader);

        // Safety fallback: hide loader after 3s max
            setTimeout(() => {
                if (loader && document.body.contains(loader)) {
                    loader.classList.add('fade-out');
                    setTimeout(() => loader.remove(), 400);
                }
            }, 2000);
        
    if (window.wpLeafletMapInit) {
        wpLeafletMapInit.push(function(map, element) {
            // Track when the GeoJSON file finishes loading
            map.on('layeradd', function(e) {
                if (e.layer && e.layer.feature) {
                    // GeoJSON feature layer has been added → hide loader
                    loader.classList.add('fade-out');
                    setTimeout(() => loader.remove(), 400);

                    // Fit to data
                    // const geoBounds = e.layer.getBounds ? e.layer.getBounds() : null;
                    // if (geoBounds && geoBounds.isValid()) {
                    //     map.fitBounds(geoBounds);
                    //    if (map.getZoom() > 4) map.setZoom(4);
                    //}
                }
            });

            
        });
    }
});
</script>
JS;

//return do_shortcode($shortcode) .  $script;
return do_shortcode($shortcode);

}
add_shortcode('perfume-map', 'perfume_leaflet_map_shortcode');





// perfume recommendations pulled from ontology using EasyRDF - WORKING

function perfume_recommendations_shortcode() {
    
    if (!is_singular('perfume')) return '';

    $perfume_name = get_the_title();
    if (!$perfume_name) return '';
    
    
    // ======== EasyRDF working example ========
    require_once get_stylesheet_directory() . '/vendor/autoload.php';

     // Use fully qualified class names (no `use` statements needed)
    \EasyRdf\RdfNamespace::set('perfume', 'http://example.org/perfume/');

    // Load ontology
    $graph = new \EasyRdf\Graph();
    $ttl_path = get_stylesheet_directory() . '/includes/perfume_ontology_final.ttl';
    $graph->parseFile($ttl_path, 'turtle');

    $perfume_ontology_name = str_replace(' ', '_', trim($perfume_name));
    $uri = 'http://example.org/perfume/' . $perfume_ontology_name;
    $resource = $graph->resource($uri);
    if (!$resource) return '';

    $recommended = $resource->all('perfume:similar_to');
    if (empty($recommended)) return '';

    $output = '<h1 id="recommended-heading" class="recommended-title">Perfumes With Similar Fragrance Notes</h3>';
    $output .= '<div class="recommended-grid">';

    foreach ($recommended as $rec) {
        $rec_name = str_replace('_', ' ', $rec->localName());

        $query = new WP_Query([
            'post_type' => 'perfume',
            'title' => $rec_name,
            'posts_per_page' => 1,
        ]);

        if (!$query->have_posts()) {
            $query = new WP_Query([
                'post_type' => 'perfume',
                's' => $rec_name,
                'posts_per_page' => 1,
            ]);
        }

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $image = get_field('image');
                $family = get_field('fragrance_family');
                $output .= '<div class="perfume-item">';
                $output .= '<a href="' . get_permalink() . '">';
                $output .= '<img src="' . esc_url($image) . '" alt="' . esc_attr(get_the_title()) . '">';
                $output .= '<h4>' . esc_html(get_the_title()) . '</h4>';
                $output .= '<p>' . esc_html($family) . '</p>';
                $output .= '</a></div>';
            }
            wp_reset_postdata();
        }
    }

    $output .= '</div><div class="back-to-perfumes"><a href="https://scentatlas.dimitristho.com/explore" class="button">BACK TO PERFUMES</a></div>
    <style>
    .recommended-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .recommended-title {
        font-size: 1.5em;
        margin-bottom: 10px;
        text-align: center;
    }
    .perfume-item {
        text-align: center;
    }
    .perfume-item img {
        max-width: 100%;
        border-radius: 10px;
    }
    </style>';

    return $output;
}
add_shortcode('perfume_recommendations', 'perfume_recommendations_shortcode');


// modify theme's search mechanism to support perfume search

function include_perfume_in_search($query) {
    
    if ($query->is_search && !is_admin() && $query->is_main_query()) {
        $query->set('post_type', array('post', 'page', 'perfume'));
    }
}
add_action('pre_get_posts', 'include_perfume_in_search');




//FILTER MECHANISM - REST API ENDPOINTS
// ===== Enqueue frontend script =====
add_action('wp_enqueue_scripts', function () {
	// Only on perfume archive
	if (!is_post_type_archive('perfume')) { return; }

	wp_enqueue_script(
		'perfume-archive-filters',
		get_stylesheet_directory_uri() . '/js/perfume-archive-filters.js',
		[],
		filemtime(get_stylesheet_directory() . '/js/perfume-archive-filters.js'),
		true
	);

	wp_localize_script('perfume-archive-filters', 'PF_API', [
		'root'  => esc_url_raw( rest_url('perfumes/v1/') ),
		'nonce' => wp_create_nonce('wp_rest'),
		'perPage' => 12, // change grid size here
	]);
});


// ===== Helpers: explode textarea lines/commas, trim and normalize =====
function pf_tokenize($raw) {
	if (!$raw) return [];
	$raw = is_array($raw) ? implode(",", $raw) : $raw;
	$parts = preg_split('/[\r\n,]+/', $raw);
	$out = [];
	foreach ($parts as $p) {
		$t = trim(wp_strip_all_tags($p));
		if ($t !== '') { $out[] = $t; }
	}
	return $out;
}

// ===== Card renderer =====
function pf_render_card($post_id) {
	$title   = get_the_title($post_id);
	$link    = get_permalink($post_id);
	$image   = get_field('image', $post_id); // assuming this returns a URL; adjust if ACF returns array
	$family  = get_field('fragrance_family', $post_id);
	$conc    = get_field('concentration', $post_id);
	$year    = get_field('year', $post_id);

	ob_start(); ?>
	<article class="pf-card" style="border:1px solid #eee;border-radius:12px;overflow:hidden">
		<a href="<?php echo esc_url($link); ?>" style="display:block;text-decoration:none;color:inherit">
			<?php if ($image): ?>
				<img src="<?php echo esc_url(is_array($image)?($image['url']??''):$image); ?>" alt="<?php echo esc_attr($title); ?>" style="width:100%;aspect-ratio:1/1;object-fit:cover" />
			<?php endif; ?>
			<div style="padding:.8rem">
				<h3 style="margin:0 0 .25rem 0;font-size:1rem;line-height:1.2"><?php echo esc_html($title); ?></h3>
				<div style="font-size:.9rem;color:#555">
					<?php
						$bits = array_filter([
							$conc ? esc_html($conc) : null,
							$year ? esc_html($year) : null,
							$family ? esc_html($family) : null
						]);
						echo esc_html( implode(' • ', $bits) );
					?>
				</div>
			</div>
		</a>
	</article>
	<?php
	return ob_get_clean();
}

// ===== REST: facets (unique values + year bounds) =====
add_action('rest_api_init', function () {
	register_rest_route('perfumes/v1', '/facets', [
		'methods'  => 'GET',
		'permission_callback' => '__return_true',
		'callback' => function () {
			$q = new WP_Query([
				'post_type' => 'perfume',
				'posts_per_page' => -1,
				'fields' => 'ids',
				'no_found_rows' => true,
			]);

			$sets = [
				'top_note'          => [],
				'middle_note'       => [],
				'base_note'         => [],
				'concentration'     => [],
				'fragrance_family'  => [],
				'countries'         => [],
			];

			$years = [];

			foreach ($q->posts as $pid) {
				// Textareas → tokenize
				foreach (['top_note','middle_note','base_note','countries'] as $k) {
					foreach (pf_tokenize( get_field($k, $pid) ) as $val) {
						$sets[$k][$val] = true;
					}
				}
				// Simple texts
				foreach (['concentration','fragrance_family'] as $k) {
					$val = trim((string) get_field($k, $pid));
					if ($val !== '') { $sets[$k][$val] = true; }
				}
				$y = (int) get_field('year', $pid);
				if ($y) { $years[] = $y; }
			}

			$result = [];
			foreach ($sets as $k => $hash) {
				$vals = array_keys($hash);
				natcasesort($vals);
				$result[$k] = array_values($vals);
			}
			$minY = $years ? min($years) : 1900;
			$maxY = $years ? max($years) : (int) date('Y');

			return new WP_REST_Response([
				'facets' => $result,
				'year'   => ['min' => $minY, 'max' => $maxY],
			], 200);
		}
	]);
});

// ===== REST: search (filters + pagination) =====
add_action('rest_api_init', function () {
	register_rest_route('perfumes/v1', '/search', [
		'methods'  => 'GET',
		'permission_callback' => '__return_true',
		'callback' => function (WP_REST_Request $req) {
			$per_page = max(1, (int) $req->get_param('per_page'));
			$paged    = max(1, (int) $req->get_param('page'));
			$q        = trim((string) $req->get_param('q'));

			$pick = function($key) use ($req) {
				$v = $req->get_param($key);
				if (is_string($v)) { $v = $v === '' ? [] : explode(',', $v); }
				if (!is_array($v)) { $v = []; }
				return array_values(array_filter(array_map('trim', $v), fn($x)=>$x!==''));
			};

			$filters = [
				'top_note'         => $pick('top_note'),
				'middle_note'      => $pick('middle_note'),
				'base_note'        => $pick('base_note'),
				'countries'        => $pick('countries'),
				'concentration'    => $pick('concentration'),
				'fragrance_family' => $pick('fragrance_family'),
			];

			$year_min = (int) $req->get_param('year_min');
			$year_max = (int) $req->get_param('year_max');

			$meta_query = ['relation' => 'AND'];

			// Year range if provided
			if ($year_min || $year_max) {
				$yr = [
					'key'     => 'year',
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC',
					'value'   => [ $year_min ?: 0, $year_max ?: 9999 ],
				];
				$meta_query[] = $yr;
			}

			// For textareas that contain multiple tokens in one field, we LIKE-match each selected token.
			$like_block = function($meta_key, $values) {
				if (empty($values)) return null;
				$or = ['relation' => 'OR'];
				foreach ($values as $val) {
					$or[] = [
						'key'     => $meta_key,
						'value'   => $val,
						'compare' => 'LIKE',
					];
				}
				return $or;
			};

			foreach (['top_note','middle_note','base_note','countries'] as $k) {
				$blk = $like_block($k, $filters[$k]);
				if ($blk) { $meta_query[] = $blk; }
			}

			// Exact-ish text fields (match via LIKE to be forgiving)
			foreach (['concentration','fragrance_family'] as $k) {
				if (!empty($filters[$k])) {
					$or = ['relation' => 'OR'];
					foreach ($filters[$k] as $val) {
						$or[] = ['key'=>$k, 'value'=>$val, 'compare'=>'LIKE'];
					}
					$meta_query[] = $or;
				}
			}

			// ✅ Free-text search: now includes post title + all meta fields
if ($q !== '') {
    // 1. Let WP_Query also search in post_title (and content)
    $args['s'] = $q;

    
    // dimitris - disabled extended meta search
    // 2. Extend meta search (for notes, family, etc.)
//    $meta_or_for_q = ['relation' => 'OR'];
//    foreach (['top_note','middle_note','base_note','countries','concentration','fragrance_family'] as $k) {
//        $meta_or_for_q[] = [
//            'key'     => $k,
//            'value'   => $q,
//            'compare' => 'LIKE',
//        ];
//    }
//    $meta_query[] = $meta_or_for_q;
}


// ✅ Base args (DO NOT overwrite these later)
$base_args = [
    'post_type'      => 'perfume',
    'posts_per_page' => $per_page,
    'paged'          => $paged,
    'meta_query'     => count($meta_query) > 1 ? $meta_query : [],
];

// Add title search only when q is present
if ($q !== '') {
    $base_args['s'] = $q; // searches post_title (and content)
}

// ✅ Sorting (build only the sort-related keys)
$sort = $req->get_param('sort') ?: 'title_asc';
$sort_args = [];
switch ($sort) {
    case 'title_desc':
        $sort_args['orderby'] = 'title';
        $sort_args['order']   = 'DESC';
        break;
    case 'year_asc':
        $sort_args['meta_key'] = 'year';
        $sort_args['orderby']  = 'meta_value_num';
        $sort_args['order']    = 'ASC';
        break;
    case 'year_desc':
        $sort_args['meta_key'] = 'year';
        $sort_args['orderby']  = 'meta_value_num';
        $sort_args['order']    = 'DESC';
        break;
    default: // title_asc
        $sort_args['orderby'] = 'title';
        $sort_args['order']   = 'ASC';
        break;
}

// ✅ Final args = base + sort
$args = array_merge($base_args, $sort_args);

// Run query
$query = new WP_Query($args);

// ===== REST: dynamic facet counts (working version) =====
add_action('rest_api_init', function () {
	register_rest_route('perfumes/v1', '/facets_live', [
		'methods'  => 'GET',
		'permission_callback' => '__return_true',
		'callback' => function (WP_REST_Request $req) {

			$fields = ['top_note','middle_note','base_note','countries','concentration','fragrance_family'];
			$filters = [];
			foreach ($fields as $k) {
				$v = $req->get_param($k);
				if (is_string($v)) $v = $v ? explode(',', $v) : [];
				if (!is_array($v)) $v = [];
				$filters[$k] = array_filter(array_map('trim', $v));
			}

			$year_min = (int) $req->get_param('year_min');
			$year_max = (int) $req->get_param('year_max');
			$q        = trim((string) $req->get_param('q'));

			// === Build meta_query from all active filters ===
			$meta_query = ['relation' => 'AND'];

			if ($year_min || $year_max) {
				$meta_query[] = [
					'key' => 'year',
					'compare' => 'BETWEEN',
					'type' => 'NUMERIC',
					'value' => [ $year_min ?: 0, $year_max ?: 9999 ],
				];
			}

			$like_block = function($meta_key, $values) {
				if (empty($values)) return null;
				$or = ['relation' => 'OR'];
				foreach ($values as $val) {
					$or[] = ['key' => $meta_key, 'value' => $val, 'compare' => 'LIKE'];
				}
				return $or;
			};

			foreach (['top_note','middle_note','base_note','countries'] as $f) {
				$b = $like_block($f, $filters[$f]);
				if ($b) $meta_query[] = $b;
			}
			foreach (['concentration','fragrance_family'] as $f) {
				$b = $like_block($f, $filters[$f]);
				if ($b) $meta_query[] = $b;
			}
			if ($q !== '') {
				$qOr = ['relation' => 'OR'];
				foreach ($fields as $f) {
					$qOr[] = ['key'=>$f, 'value'=>$q, 'compare'=>'LIKE'];
				}
				$meta_query[] = $qOr;
			}

			// === Query all matching perfumes ===
			$qargs = [
				'post_type' => 'perfume',
				'posts_per_page' => -1,
				'fields' => 'ids',
				'meta_query' => count($meta_query)>1 ? $meta_query : [],
				'no_found_rows' => true,
			];
			$qry = new WP_Query($qargs);
			if (empty($qry->posts)) return new WP_REST_Response(['counts'=>[]]);

			// === Count facet values within current subset ===
			$counts = [];
			foreach ($fields as $f) $counts[$f] = [];

			foreach ($qry->posts as $pid) {
				foreach (['top_note','middle_note','base_note','countries'] as $f) {
					foreach (pf_tokenize(get_field($f, $pid)) as $val) {
						if (!$val) continue;
						$counts[$f][$val] = ($counts[$f][$val] ?? 0) + 1;
					}
				}
				foreach (['concentration','fragrance_family'] as $f) {
					$v = trim((string)get_field($f, $pid));
					if ($v) $counts[$f][$v] = ($counts[$f][$v] ?? 0) + 1;
				}
			}
			foreach ($counts as &$c) { ksort($c, SORT_NATURAL | SORT_FLAG_CASE); }
			return new WP_REST_Response(['counts'=>$counts], 200);
		}
	]);
});


$args = array_merge([
    'post_type'      => 'perfume',
    'posts_per_page' => $per_page,
    'paged'          => $paged,
    'meta_query'     => count($meta_query) > 1 ? $meta_query : [],
], $args);


			$query = new WP_Query($args);

			$html = '';
			if ($query->have_posts()) {
				foreach ($query->posts as $pid) {
					$html .= pf_render_card($pid);
				}
			}

			$total      = (int) $query->found_posts;
			$max_pages  = (int) $query->max_num_pages;

			return new WP_REST_Response([
				'html'      => $html,
				'total'     => $total,
				'page'      => $paged,
				'pages'     => $max_pages,
			], 200);
		}
	]);
});