# ScentAtlas Technical Summary

## Overview

ScentAtlas is an interactive fragrance catalog and exploration platform built as a WordPress child theme. It allows users to browse, filter, and discover perfumes by various attributes such as fragrance notes, year of release, fragrance family, concentration, and origin of ingredients (countries). The project integrates a custom dataset of perfumes and leverages semantic web technologies and mapping to provide advanced features like an interactive fragrance notes map and similar fragrance recommendations.

## Data Sources and Structure

- **Original Dataset (original_dataset.csv):** This is the original dataset from GitHub, named '[Perfumes_Recommender](https://github.com/rawanalqarni/Perfumes_Recommender)'.
 ‎ 
- **Enriched Perfume Dataset (perfume_dataset.csv):** This is the updated version of the original dataset, with `top_note`, `middle_note` and `base_note` as separate columns, which will be needed for the import process into WordPress. This dataset still contains duplicate perfumes.
‎ 
- **Cleaned Perfume Dataset (cleaned_dataset_300_perfumes.csv):** This is the cleaned and final version of the dataset including 300 perfumes, with details like name, description, brand, year, fragrance family, concentration, top/middle/base notes, and similar perfume suggestions. Since some of the perfumes in the CSV had special characters in their names (`',-_;—`), it was hard to use them with the ontology parsing method that is explained later in this README file. Thus, for time management purposes, around 50 perfumes from the dataset were ignored, resulting in the final catalog of 248 perfumes available on the website. In future work, the code will be updated so that it supports these special characters.
‎ 
- **Fragrance Notes Origins (notes_origins.json):** A JSON file mapping of hundreds of fragrance notes (ingredients) to their origin countries. This data is used for the dynamic choropleth map, to visualize where each perfume's notes come from. This JSON file has been created with the use of Deepseek's generative AI model. We provided it with the dataset .csv file and the prompt seen below:
  ‎ 
  
  > "Use this CSV file to match every single fragrance note to one or more countries of origin. The output should be a JSON file. For the key of each JSON pair, use exactly the same fragrance note names as those found in the CSV file. For the value of each JSON pair, use country official names without parenthesis or explanations (e.g. accepted values are 'France', 'United Arab Emirates', 'USA' etc.). Do not use name of regions or geographic areas (e.g. 'Mediterranean', 'South America' etc.). If a fragrance note is found globally, do not write 'Global' as the value. Instead, provide the 4 most common countries where it is found. (e.g. "India", "Turkey", "Portugal", "Spain")."

  After asking the model about the resources which provided location information to create the generated file, we received a list of websites and repositories about fragrances and ingredients origins:
    - [Edamam](https://www.edamam.com/)
    - [Introduction to Open Food Facts API documentation](https://openfoodfacts.github.io/openfoodfacts-server/api/)
    - [COSMILE Europe](https://cosmileeurope.eu/inci/)
    - [GlobalCosIng](https://globalcosing.chemradar.com/tools/gcir)
    - [SourceReady](https://www.sourceready.com/blog/top-countries-source-perfume-fragrance)
    - [Nephron Pharmaceuticals](https://nephronpharm.com/about/api-warehouse/)
    - [Volza](https://www.volza.com/p/perfume-raw-material/export/)
    - [Foogle](https://github.com/MicroFish91/Foogle)
    - [RIFM Database](https://rifm.org/rifm-database/)

  ‎ 
  This JSON file was also converted to a .csv format which was used for creating the machine learning feature of the website and for adding countries into the ontology file.
  ‎ 

- **Countries World GeoJSON Map (countries.geojson):** Inner perfume pages ([example of a perfume page](https://scentatlas.dimitristho.com/perfume/memo-paris-cuirs-nomades-sicilian-leather-edp/)) support a dynamic ingredient map. This dynamic choropleth map was created using the [LeafLet Map](https://wordpress.org/plugins/leaflet-map/) WordPress plugin. This plugin uses the GeoJSON format to draw polygons or other shapes on top of a world map. To draw these geometric shapes, the plugin retrieves the coordinates (long and lat) of each point from the GeoJSON file. Drawing the shape of every country using multi-polygons would be a very time-consuming and ineffective approach. After some research online, we found a github repository named [geo-countries](https://github.com/datasets/geo-countries), which includes a single GeoJSON file defining the polygon of every country in the world. The file consists of key-value pairs, with keys being the string of the country name (e.g. "Japan") and values being the definition several polygon points that draw the country's shape (e.g. `"geometry": { "type": "MultiPolygon", "coordinates": [ [ 33.78183, 34.976223 ], [ 33.780935, 34.976345 ], [ 33.780191, 34.979313 ], [ 33.77554, 34.980088 ], ...]}`). Obviously, countries with more islands or greater detail in their borders had way more detailed polygons, meaning more points, which lead to more longitude and latitude pairs (e.g. Philippines, Canada, Norway etc.). To optimize performance, our approach was to automatically generate a separate GeoJSON file for each perfume page using a PHP function that clones the world GeoJSON file and only keeps the polygons of the countries associated with this specific perfume. More details on this function are described further below.
  ㅤ
- **Semantic Ontology (includes/perfume_ontology_final.ttl):** A Turtle-format ontology file defining each perfume as an entity with properties (e.g., `perfume:year`, `perfume:fragranceFamily`, `perfume:hasTopNote`, etc.) and relationships such as `perfume:similar_to` between perfumes. This ontology powers the inner perfume pages and ensures a consistent data source for perfume attributes.

## Architecture and Implementation

**WordPress Child Theme:** ScentAtlas is implemented as a child theme of the free [Astra](https://wpastra.com/) theme. It relies on WordPress with custom post types and fields to manage perfume entries. Key aspects of the implementation include:

- **Custom Post Type - perfume:** Perfumes are stored as a custom post type called `perfume`. This custom post type was created using the free version of [ACF](https://www.advancedcustomfields.com/) plugin.
- **Advanced Custom Fields (ACF):** Several custom fields are used to store perfume metadata, such as `image` (URL of perfume image), `description` (full description), `year`, `concentration` (e.g., Eau de Parfum), `fragrance_family` (scent family category), and note fields (`top_note`, `middle_note`, `base_note`). These fields are accessed in code via the ACF function `get_field()` (for example, `get_field('top_note')` in `functions.php`).
- **Products - Import from dataset into WordPress:** To import the perfumes found in the .csv dataset as post types into our website, we used [Ultimate CSV Importer](https://wordpress.org/plugins/wp-ultimate-csv-importer/), a plugin for importing CSV/XML data to a WordPress website.
- **WooCommerce Integration:** The theme hooks into WooCommerce to reuse its shop templates for perfume listings. In `functions.php`, a `pre_get_posts` filter adjusts the main WooCommerce shop query to display `perfume` posts instead of `product` posts when viewing the shop page (checking the `is_shop()` condition). The single perfume template (`single-perfume.php`) uses WooCommerce's layout (calling `get_header('shop')` and `get_footer('shop')`) and a custom content template.
- **Templates:**
  - `archive.php` is customized to display the perfume archive (the "Explore" or catalogue page) with a filter UI at the top and a results grid below.
  - `single-perfume.php` and `content-single-perfume.php` define the layout for individual perfume pages. They display perfume details and inject the interactive map and recommendations sections via shortcodes.
- **Shortcodes:** Three custom shortcodes are defined in `functions.php` to render dynamic content:
  - **`[perfume_grid]`:** Renders a grid of 8 random perfume items with their image, name, and a brief description. Implemented by the `show_perfumes_grid()` function, it queries perfume posts and outputs an HTML grid. This shortcode is used on the homepage to show a preview of perfumes, with a "View Catalogue" button linking to the full archive page.
  - **`[perfume-map]`:** Displays the interactive LeafLet choropleth map highlighting the origin countries of the current perfume's notes. The shortcode is handled by the `perfume_leaflet_map_shortcode()` function. When a single perfume page is loaded, this function:
    - Retrieves the perfume's top, middle, and base notes from ACF fields.
    - Loads the `notes_origins.json` mapping and a world countries GeoJSON (required for country shapes).
    - Determines which countries correspond to the perfume's notes and creates a filtered GeoJSON with only those countries, annotated with the number of notes (`note_count`) and types of notes present.
    - Saves this filtered data to a temporary GeoJSON file and uses a map shortcode (provided by the WordPress LeafLet Map plugin) to render the map. It uses `[leaflet-map]` and `[leaflet-geojson]` shortcodes, and a `[choropleth]` overlay to color countries by `note_count`.
  - **`[perfume_recommendations]`:** Displays a list of similar perfumes based on shared fragrance notes. Implemented by the `perfume_recommendations_shortcode()` function, which uses the ontology data via [EasyRDF](https://www.easyrdf.org/):
    - It loads the RDF graph from `perfume_ontology_final.ttl` (using the EasyRDF library).
    - Finds the current perfume resource and retrieves all `perfume:similar_to` relations (which represent perfumes with similar note profiles).
    - For each related perfume, it performs a WP query to find the corresponding perfume post by title and then displays its image, name, and fragrance family in a responsive grid.
    - This section is titled "Perfumes With Similar Fragrance Notes" and appears below the perfume's details on the page. Each item links to that perfume's page. A "Back to Perfumes" button is included to return to the main catalogue.
- **EasyRDF Integration:** We used the EasyRDF PHP library (autoloaded via `vendor/autoload.php`) to parse the ontology and query perfume data. This approach ensures the single perfume page displays authoritative data (e.g., notes, year, description) directly from the ontology. In `content-single-perfume.php`, the code uses EasyRDF to get the perfume's properties (`image`, `year`, `concentration`, `fragranceFamily`, `description`) and lists of notes (`hasTopNote`, `hasMiddleNote`, `hasBaseNote`) instead of relying solely on WordPress meta fields. This data is then rendered in the template as titles, texts and images. The integration of RDF data allows for semantic queries (like finding similar perfumes) and consistency between the displayed info and the recommendation engine.

## Interactive Perfume Filter and Search

One of ScentAtlas's core features is the interactive filtering interface on the perfume [archive page](https://scentatlas.dimitristho.com/explore/):

- **Faceted Filtering UI:** On the archive page, users can filter the perfume list by multiple criteria:
  - Live text search for searching by name, notes or fragrance family.
  - Fragrance note checkboxes for Top, Middle, and Base notes (populated into dropdowns labeled "Top Notes", "Middle Notes", "Base Notes").
  - Other attribute filters: Concentration, Fragrance Family, and Location (countries where notes originate).
  - A Year range slider (with dual handles for min/max year).
  - Sort dropdown with alphabetical and chronological order.
  - A "Clear filters" button to reset all filters.
- **Dynamic Updates:** The filtering interface is powered by AJAX requests through custom REST API endpoints. The theme registers several endpoints under the namespace `perfumes/v1` in `functions.php`:
  - **GET /perfumes/v1/facets:** Returns the list of all unique filter values (all notes, all concentrations, etc.) and the min/max year across all perfumes. This is used to populate the filter options when the page loads. In code, it queries all perfume posts (IDs only) and aggregates unique values from the meta fields defined with ACF (`top_note`, `middle_note`, `base_note`, `countries`, `concentration`, `fragrance_family`) to build the facets list.
  - **GET /perfumes/v1/search:** Returns filtered results based on query parameters. It accepts filter parameters (search query `q`, selected notes, selected `concentration`/`fragrance_family`, `year_min`/`year_max`, sort order, and page number for pagination). The backend constructs a `WP_Query` with corresponding `meta_query` filters (e.g., matching any selected notes in the respective fields, filtering by year range, etc.). It returns a JSON response containing HTML (`"html"`) for the filtered perfume cards, the total count, current page, and total pages. The HTML is generated by a helper function `pf_render_card()` which outputs a compact card (image, title, and a line of meta info like concentration, year, family) for each perfume.
  - **GET /perfumes/v1/facets_live:** Returns updated counts for each facet value given the current active filters. After each search, the front-end can call this to update how many results would include each filter option (useful to indicate which notes or categories are still relevant). It runs a query for all perfumes matching the active filters and then counts occurrences of each note and attribute within that subset.
- **Front-End Script (js/perfume-archive-filters.js):** This JavaScript file handles all client-side interaction for the filters:
  - It initializes the filter dropdowns by fetching the /facets data and building multi-select checkbox lists for each category (notes, family, etc.) and setting up the year slider bounds.
  - As the user selects filters or types in the search box, the script updates a state object and triggers new searches via the /search endpoint (using the Fetch API with the REST nonce for security).
  - Results are rendered dynamically into the grid without a full page reload. The total count of results is shown (in the `#pf-count` element), and pagination controls (`#pf-pagination`) are updated.
  - The script also calls `/facets_live` after getting search results to update the available options counts, enabling a refined faceted search experience.
  - Selected filters are displayed as removable "chips" within each dropdown button for clarity. For example, if multiple top notes are selected, the dropdown's button will show those note names with a small "✕" icon to remove each.
- **Pagination:** Results are paginated (with a default of 12 per page, as set in the script via `PF_API.perPage`). The pagination control is updated by the script, allowing navigation through result pages without reloading, which triggers the AJAX calls for the respective page.

## Perfume Detail Page Features

Each individual perfume page provides comprehensive information and interactive tools:

- **Perfume Details:** The single perfume page displays the perfume's image, name, and a metadata line (concentration, year, and fragrance family). This is followed by lists of its top, middle, and base notes. These notes are output in the template via the ontology data (each list is labeled and shows the notes). If a detailed description is available, it is shown under a "Description" section.
- **Notes Origin Map:** The "View Notes Map" button on the perfume page jumps to the embedded map (rendered by the `[perfume-map]` shortcode). The map highlights countries that are sources of the perfume's ingredients. Countries are shaded (using a green color scale) according to how many of the perfume's notes come from that region. This geospatial visualization helps users understand the global diversity of the fragrance's composition.
- **Similar Fragrance Recommendations:** Below the map, a "Perfumes With Similar Fragrance Notes" section (triggered by the `[perfume_recommendations]` shortcode) presents a grid of other perfumes that share significant notes or an olfactory profile with the current perfume. This uses the ontology's `similar_to` relationships to identify related scents. Each recommended item displays the perfume's image, name, and fragrance family, allowing users to explore it further by clicking. This feature helps users discover new perfumes based on their current interests.
- **Navigation and UX:** The detail page provides quick navigation links: a "VIEW NOTES MAP" button (scrolls to the map) and a "VIEW RECOMMENDATIONS" button (scrolls to the recommendations section). A "BACK TO PERFUMES" button is also provided after the recommendations to return to the main listing page. These ensure a smooth user experience when exploring details and then returning to browsing.

## Additional Technical Notes

- **Styling:** The `style.css` file contains custom styles that were added to the ScentAtlas interface, including layout adjustments and unique styling for elements like filter dropdowns and note chips. For example, the classes `.chip-top_note`, `.chip-middle_note`, etc., give each note type a distinct background color for easy identification. Responsive design tweaks (e.g., adjusting the grid columns on smaller screens) are also included to ensure the catalogue is mobile-friendly.
- **Dependencies:** To reproduce ScentAtlas, the following are required:
  - WordPress (tested with WordPress 6.x) with the Astra theme (install Astra and manually create a child theme folder using FTP).
  - WooCommerce plugin (for shop page functionality and template reuse, even if the site is not selling products).
  - Advanced Custom Fields (ACF) plugin to define the custom fields (`image`, `description`, `year`, `concentration`, `fragrance_family`, `top_note`, `middle_note`, `base_note`, `countries`) on the perfume post type. These should be set up to match the data types (e.g. text areas for notes that can hold comma-separated values).
  - Leaflet Map plugin for WordPress, which provides the `[leaflet-map]`, `[leaflet-geojson]`, and `[choropleth]` shortcodes.
  - PHP Composer to install dependencies. The EasyRDF library is used via Composer. Ensure to run composer install in the theme directory to generate the `vendor/` folder with required libraries (the code expects `vendor/autoload.php` to be present).
  - Data Initialization: The provided CSV (`cleaned_dataset_300_perfumes.csv`) can be used to import the perfume data into WordPress. One would need to create perfume posts and fill in the custom fields accordingly (either manually or via an import tool/script). Likewise, the `perfume_ontology_final.ttl` should be kept updated with those entries. The JSON file `notes_origins.json` and a `countries.geojson file` (for world country boundaries) should be placed in the appropriate location/path on the server.
- **Extensibility:** The architecture separates data from presentation. By using WordPress custom post types and an RDF ontology, the project can be extended or modified with minimal changes to code. For example, one could update the ontology with new perfumes or additional relationships (such as grouping perfumes by brand or shared notes) and reflect that in the WordPress site. The custom REST API endpoints also make the perfume data accessible, which could enable external applications or additional front-end features (e.g. a dedicated search page or integration with a JavaScript framework). The code is organized for clarity, with distinct functions in `functions.php` handling specific features (grid display, AJAX load-more, map generation, search API, recommendations), making it maintainable and adaptable for future development.

## Summary

ScentAtlas combines a traditional WordPress front-end with advanced data-driven features to create a rich user experience for perfume enthusiasts. It provides an informative and interactive "atlas" of scents, where users can filter perfumes by notes or other attributes, visualize the geographic origin of fragrance ingredients, and get recommendations for similar scents.

Through its well-integrated use of WordPress (for content management), custom code (for interactivity and logic), and semantic data (for knowledge representation and recommendations), ScentAtlas demonstrates a powerful and flexible architecture. By blending a CMS with custom APIs, data visualization, and semantic reasoning, the platform showcases how web technologies can be orchestrated to deliver a comprehensive and engaging tool for exploring perfumes.