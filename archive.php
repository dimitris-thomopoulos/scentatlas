<?php
/**
 * The template for displaying archive pages.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package Astra
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header(); ?>

<?php if ( astra_page_layout() === 'left-sidebar' ) { get_sidebar(); } ?>

<div id="primary" <?php astra_primary_class(); ?>>

	<?php astra_primary_content_top(); ?>
	<?php astra_archive_header(); ?>

	<!-- Filters + Search -->
	<section id="perfume-filters" class="perfume-filters" style="margin-bottom:2rem">
		<div class="pf-row" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-start">
			<!-- Live search -->
			<div class="pf-control" style="min-width:280px;flex:1 1 320px">
				<label for="pf-search" style="display:block;font-weight:600;margin-bottom:6px">Search for fragrances</label>
				<input id="pf-search" type="search" placeholder="Search by fragrance name, fragrance note or fragrance family…" style="width:100%;padding:.6rem;border:1px solid #ddd;border-radius:8px" />
			</div>

			<!-- Dropdown templates (filled by JS) -->
			<div class="pf-dropdowns" style="display:flex;gap:8px;flex-wrap:wrap">
                            <div class="fragrance-filter">
                                <label>Top Notes</label>
				<div class="pf-dd" data-key="top_note"></div>
                            </div>
                            
                            <div class="fragrance-filter">
                                <label>Middle Notes</label>
				<div class="pf-dd" data-key="middle_note"></div>
                                </div>
                            
                            <div class="fragrance-filter">
                                <label>Base Notes</label>
				<div class="pf-dd" data-key="base_note"></div>
                            </div>
                            
                            <div class="fragrance-filter">
                                <label>Concentration</label>
				<div class="pf-dd" data-key="concentration"></div>
                            </div>
                            
                            <div class="fragrance-filter">
                                <label>Location</label>
				<div class="pf-dd" data-key="countries"></div>
                            </div>
                            
                            <div class="fragrance-filter">
                                <label>Fragrance Family</label>
				<div class="pf-dd" data-key="fragrance_family"></div>
                            </div>
			</div>
		</div>

		<!-- Year slider -->
		<div class="pf-year" style="margin-top:12px">
			<label style="display:block;font-weight:600;margin-bottom:6px">Year</label>
			<div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
				<input id="pf-year-max" type="range" min="1900" max="2100" value="2100" step="1"/>
                                <input id="pf-year-min" type="range" min="1900" max="2100" value="1900" step="1"/>
				<div>
					<span id="pf-year-min-label">1900</span> – <span id="pf-year-max-label">2100</span>
				</div>
			</div>
		</div>

		<!-- Clear -->
		<div style="margin-top:10px">
			<button id="pf-clear" type="button" class="button" style="padding:.8rem 1.2rem;border-radius:8px;cursor:pointer;">Clear filters</button>
		</div>
	</section>

	<!-- Results -->
	<section id="perfume-results">
            <div class="results-info">
		<div id="pf-count" style="margin-bottom:12px;font-weight:600"></div>
                <div class="sort-by">
                <span class="fragrance-filter">
                                <label>Sort by</label>
                                <div class="pf-dd" data-key="sort_order"></div>
                </span>
                </div>
            </div>
		<div id="pf-grid" class="pf-grid" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px"></div>
		<div id="pf-pagination" style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap"></div>
	</section>

	<?php astra_primary_content_bottom(); ?>

</div><!-- #primary -->

<?php if ( astra_page_layout() === 'right-sidebar' ) { get_sidebar(); } ?>

<?php get_footer(); ?>
