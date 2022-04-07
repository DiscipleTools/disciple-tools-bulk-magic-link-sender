<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.

function fetch_magic_link_templates(): array {

    $templates            = [];
    $magic_link_templates = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_templates );

    if ( ! empty( $magic_link_templates ) ) {
        foreach ( $magic_link_templates as $post_type ) {
            foreach ( $post_type ?? [] as $template ) {
                if ( $template['enabled'] ) {

                    // Populate url_base first...
                    $template['url_base'] = str_replace( 'templates_', 'templates/', $template['id'] );

                    // Capture updated template
                    $templates[] = $template;
                }
            }
        }
    }

    return $templates;
}

function can_instantiate_template( $template ): bool {

    // Ensure we have a valid template.
    if ( empty( $template ) || ! isset( $template['id'] ) ) {
        return false;
    }

    // Determine link object id based in incoming request.
    $link_obj_id = null;
    if ( isset( $_REQUEST['id'] ) ) {   // Initial frontend form request.
        $link_obj_id = sanitize_text_field( wp_unslash( $_REQUEST['id'] ) );

    } elseif ( isset( $_REQUEST['parts'], $_REQUEST['parts']['instance_id'] ) ) {  // Subsequent frontend form requests.

        $instance_id = sanitize_text_field( wp_unslash( $_REQUEST['parts']['instance_id'] ) );

        // Accommodate template only requests.
        if ( empty( $instance_id ) ) {
            return true;
        }

        // Accommodate template/link-object requests.
        $link_obj_id = $instance_id;

    } elseif ( isset( $_REQUEST['link_obj_id'] ) ) {   // Admin get post record request.
        $link_obj_id = sanitize_text_field( wp_unslash( $_REQUEST['link_obj_id'] ) );

    } elseif ( isset( $_SERVER['REQUEST_URI'] ) ) {

        /**
         * Determine if this is a frontend post type records
         * display request?
         */

        $haystack = trim( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
        $needle   = '/' . $template['post_type'] . '/';
        if ( strpos( $haystack, $needle ) !== false ) {

            // Extract post id
            $uri_parts = array_values( array_filter( explode( '/', $haystack ) ) );
            $uri_parts = array_map( 'sanitize_key', wp_unslash( $uri_parts ) );

            if ( count( $uri_parts ) !== 2 ) {
                return false;
            }

            try {
                $post_type = $uri_parts[0];
                $post_id   = $uri_parts[1];

                // Ensure post id is a valid numeric and not something like 'new'
                if ( ! is_numeric( $post_id ) ) {
                    return false;
                }

                // Fetch corresponding post
                $post = DT_Posts::get_post( $post_type, $post_id );

                // Determine if post is currently linked to template
                return ( ! empty( $post ) && isset( $post[ $template['id'] ] ) );

            } catch ( Exception $e ) {
                return false;
            }
        }

        /**
         * Next, determine if this is a frontend generated magic link
         * form display request; which does not contain additional link
         * object info, to aid selection process!
         */

        try {
            $template_url_parts = array_values( array_filter( explode( '_', $template['url_base'] ) ) );

            return ( strpos( $haystack, $template_url_parts[0] ) !== false );

        } catch ( Exception $e ) {
            return false;
        }
    }
    if ( empty( $link_obj_id ) ) {
        return false;
    }

    // Locate corresponding link object and see if we have a match!
    $link_obj = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_obj( $link_obj_id );

    return ( ! empty( $link_obj ) && isset( $link_obj->type ) && $link_obj->type == $template['id'] );
}

add_action( 'dt_magic_link_load_templates', 'dt_ml_load_templates_func', 20, 1 );
function dt_ml_load_templates_func( $post_type ) {
    foreach ( fetch_magic_link_templates() ?? [] as $template ) {
        if ( $template['post_type'] == $post_type ) {
            new Disciple_Tools_Magic_Links_Templates( $template );
        }
    }
}

/**
 * Class Disciple_Tools_Magic_Links_Templates_Loader
 */
class Disciple_Tools_Magic_Links_Templates_Loader {

    private static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // End instance()

    public function __construct() {
        self::load_templates();
    }

    private function load_templates() {
        foreach ( fetch_magic_link_templates() ?? [] as $template ) {
            if ( can_instantiate_template( $template ) ) {
                new Disciple_Tools_Magic_Links_Templates( $template );
            }
        }
    }
}

Disciple_Tools_Magic_Links_Templates_Loader::instance();

/**
 * Class Disciple_Tools_Magic_Links_Templates
 */
class Disciple_Tools_Magic_Links_Templates extends DT_Magic_Url_Base {

    public $page_title = 'Template Title';
    public $page_description = 'Template Title Description';
    public $root = "templates"; // @todo define the root of the url {yoursite}/root/type/key/action
    public $type = 'template_id'; // Placeholder to be replaced with actual template ids
    public $type_name = '';
    public $post_type = 'contacts'; // Support ML contacts (which can be any one of the DT post types) by default!
    private $post = null;
    private $post_field_settings = null;
    private $meta_key = '';

    public $show_bulk_send = true;
    public $show_app_tile = true;

    private static $_instance = null;
    public $meta = []; // Allows for instance specific data.
    public $translatable = [
        'query',
        'user',
        'contact'
    ]; // Order of translatable flags to be checked. Translate on first hit..!

    private $template = null;
    private $link_obj = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // End instance()

    public function __construct( $template = null ) {

        /**
         * Assuming a valid template, then capture header values
         */

        if ( empty( $template ) ) {
            return;
        }

        $this->type             = array_map( 'sanitize_key', wp_unslash( explode( '_', $template['id'] ) ) )[1];
        $this->type_name        = $template['name'];
        $this->page_title       = $template['name'];
        $this->page_description = '';

        /**
         * Register default filters & actions
         */

        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 30, 2 );

        /**
         * As incoming requests could be for either valid wp users of contact
         * post records, ensure to adjust the $post_type accordingly; to
         * fall in line with extended class functionality!
         */

        $this->adjust_global_values_by_incoming_sys_type( $this->fetch_incoming_link_param( 'type' ) );

        /**
         * Specify metadata structure, specific to the processing of current
         * magic link type.
         *
         * - meta:              Magic link plugin related data.
         *      - app_type:     Flag indicating type to be processed by magic link plugin.
         *      - post_type     Magic link type post type.
         *      - templates:    List of magic link templates.
         */

        $this->meta = [
            'app_type'     => 'magic_link',
            'post_type'    => $this->post_type,
            'class_type'   => 'template',
            'templates'    => fetch_magic_link_templates(),
            'get_template' => function ( $id ) {
                return $this->fetch_template_by_id( $id );
            }
        ];

        /**
         * If dealing with a specific templates request, then ensure global ml type placeholder
         * is replaced with actual incoming template id!
         */

        $this->adjust_global_values_by_incoming_template_id();

        /**
         * Attempt to load sooner, rather than later; corresponding post record details.
         */

        $this->post = $this->fetch_post_by_incoming_hash_key();
        if ( ! empty( $this->post ) && ! is_wp_error( $this->post ) ) {
            $this->post_field_settings = DT_Posts::get_post_field_settings( $this->post['post_type'] );
        }

        /**
         * Attempt to load corresponding link object, if a valid incoming id has been detected.
         */

        $this->link_obj = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_obj( $this->fetch_incoming_link_param( 'id' ) );

        /**
         * Once adjustments have been made, proceed with parent instantiation!
         */

        $this->meta_key = $this->root . '_' . $this->type;
        parent::__construct();
        add_action( 'rest_api_init', [ $this, 'add_endpoints' ] );

        /**
         * Test magic link parts are registered and have valid elements.
         */

        if ( ! $this->check_parts_match() ) {
            return;
        }

        /**
         * Load if valid url
         */

        add_action( 'dt_blank_body', [ $this, 'body' ] );
        add_filter( 'dt_magic_url_base_allowed_css', [ $this, 'dt_magic_url_base_allowed_css' ], 10, 1 );
        add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );
        add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 100 );
    }

    public function wp_enqueue_scripts() {
        // Support Geolocation APIs
        if ( DT_Mapbox_API::get_key() ) {
            DT_Mapbox_API::load_mapbox_header_scripts();
            DT_Mapbox_API::load_mapbox_search_widget();
        }
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        // @todo add or remove js files with this filter
        // example: $allowed_js[] = 'your-enqueue-handle';
        $allowed_js[] = 'mapbox-gl';
        $allowed_js[] = 'mapbox-cookie';
        $allowed_js[] = 'mapbox-search-widget';

        return $allowed_js;
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        // @todo add or remove js files with this filter
        // example: $allowed_css[] = 'your-enqueue-handle';
        $allowed_css[] = 'mapbox-gl-css';

        return $allowed_css;
    }

    private function adjust_global_values_by_incoming_sys_type( $type ) {
        if ( ! empty( $type ) ) {
            switch ( $type ) {
                case 'wp_user':
                    $this->post_type = 'user';
                    break;
                default: // 'post' or anything else!
                    $this->post_type = 'contacts';
                    break;
            }
        }
    }

    private function adjust_global_values_by_incoming_template_id() {
        if ( strpos( dt_get_url_path( true ), $this->root . '/' ) !== false ) {

            /**
             * Once identified as a templates request, extract corresponding template id
             */

            $url_path = dt_get_url_path( true );
            $parts    = explode( '/', $url_path );
            $parts    = array_map( 'sanitize_key', wp_unslash( $parts ) );

            /**
             * Ensure to adapt to different url shapes, based on request source! I.e. Initial requests
             * or subsequent internal form requests?
             */

            $idx = ( count( $parts ) === 3 ) ? 1 : 3;

            if ( isset( $parts[ $idx ] ) ) {

                // Ensure to drop any magic_key references; which will be re-assigned further downstream!
                $this->type = str_replace( '_magic_key', '', $parts[ $idx ] );

                // Update other global variables, such as page title, etc...
                $template_id    = 'templates_' . $this->type . '_magic_key';
                $this->template = $this->fetch_template_by_id( $template_id );
                if ( ! empty( $this->template ) ) {
                    $this->page_title       = $this->template['name'];
                    $this->page_description = '';
                }
            }
        }
    }

    private function fetch_template_by_id( $template_id ): array {
        foreach ( fetch_magic_link_templates() ?? [] as $template ) {
            if ( $template['id'] === $template_id ) {
                return $template;
            }
        }

        return [];
    }

    public function dt_details_additional_section( $section, $post_type ) {
        if ( ! $this->show_app_tile ) {
            return;
        }

        /**
         * Ensure to display template links for any assigned posts.
         */

        if ( $section === "apps" ) {

            // Return if no templates are found.
            $templates = fetch_magic_link_templates();
            if ( empty( $templates ) ) {
                return;
            }

            // Only display template link if current post has been assigned.
            $post               = DT_Posts::get_post( $post_type, get_the_ID() );
            $assigned_templates = [];
            foreach ( $templates as $template ) {
                if ( isset( $post[ $template['id'] ] ) ) {
                    $assigned_templates[] = $template;
                }
            }

            // Only proceed if assigned templates have been found.
            if ( ! empty( $assigned_templates ) ) {
                ?>
                <div class="section-subheader"><?php echo esc_html( __( "Templates", 'disciple_tools' ) ); ?></div>
                <div class="section-app-links">
                    <?php
                    foreach ( $assigned_templates as $template ) {

                        // Explode template id into parts -> {root}_{id/type}_magic_key
                        $parts = explode( '_', $template['id'] );
                        $link  = DT_Magic_URL::get_link_url( $parts[0], $parts[1], $post[ $template['id'] ] );
                        ?>
                        <a target="_blank" href="<?php echo esc_url( $link ); ?>" type="button"
                           class="empty-select-button select-button small button"><?php echo esc_html( $template['name'] ); ?></a>
                        <?php
                    }
                    ?>
                </div>
                <?php
            }
        }
    }

    private function fetch_post_by_incoming_hash_key() {
        if ( strpos( dt_get_url_path( true ), $this->root . '/' ) !== false ) {

            global $wpdb;

            // Extract request uri parts, in search of hash key
            $url_path = dt_get_url_path( true );
            $parts    = explode( '/', $url_path );
            $parts    = array_map( 'sanitize_key', wp_unslash( $parts ) );

            $meta_key   = ! empty( $this->template ) ? $this->template['id'] : '';
            $public_key = $parts[2];

            // Attempt to locate corresponding post id
            $post_id = $wpdb->get_var( $wpdb->prepare( "
                SELECT pm.post_id
                FROM $wpdb->postmeta as pm
                WHERE pm.meta_key LIKE %s
                  AND pm.meta_value = %s
                  ", $meta_key . '%', $public_key ) );

            // Assuming we have stuff, attempt to load post record details
            if ( ! empty( $post_id ) && ! is_wp_error( $post_id ) ) {
                return DT_Posts::get_post( $this->post_type, $post_id, true, false );
            }
        }

        return null;
    }

    private function is_link_obj_field_enabled( $field_id ): bool {
        if ( ! empty( $this->link_obj ) ) {
            foreach ( $this->link_obj->type_fields ?? [] as $field ) {
                if ( $field->id === $field_id ) {
                    return $field->enabled;
                }
            }
        }

        return true;
    }

    private function render_custom_field_for_display( $field ) {
        ?>
        <div class="section-subheader"><?php echo $field['label'] ?></div>
        <input id="<?php echo $field['id'] ?>" type="text" class="text-input" value="">
        <?php
    }

    /**
     * Writes custom styles to header
     *
     * @see DT_Magic_Url_Base()->header_style() for default state
     * @todo remove if not needed
     */
    public function header_style() {
        ?>
        <style>
            body {
                background-color: white;
                padding: 1em;
            }

            .api-content-div-style {
                height: 300px;
                overflow-x: hidden;
                overflow-y: scroll;
                text-align: left;
            }

            .api-content-table tbody {
                border: none;
            }

            .api-content-table tr {
                cursor: pointer;
                background: #ffffff;
                padding: 0px;
            }

            .api-content-table tr:hover {
                background-color: #f5f5f5;
            }
        </style>
        <?php
        $typeahead_uri = get_template_directory_uri() . "/dt-core/dependencies/typeahead/dist/jquery.typeahead.min.css";
        // phpcs:disable
        ?>
        <link rel="stylesheet" type="text/css" href="<?php esc_attr_e( $typeahead_uri ); ?>"/>
        <?php
        // phpcs:enable
    }

    /**
     * Writes javascript to the header
     *
     * @see DT_Magic_Url_Base()->header_javascript() for default state
     * @todo remove if not needed
     */
    public function header_javascript() {
        $typeahead_uri = get_template_directory_uri() . "/dt-core/dependencies/typeahead/dist/jquery.typeahead.min.js";
        // phpcs:disable
        ?>
        <script type="text/javascript" src="<?php esc_attr_e( $typeahead_uri ); ?>"></script>
        <?php
        // phpcs:enable
    }

    /**
     * Writes javascript to the footer
     *
     * @see DT_Magic_Url_Base()->footer_javascript() for default state
     * @todo remove if not needed
     */
    public function footer_javascript() {
        ?>
        <script>
            let jsObject = [<?php echo json_encode( [
                'root'         => esc_url_raw( rest_url() ),
                'nonce'        => wp_create_nonce( 'wp_rest' ),
                'parts'        => $this->parts,
                'link_obj_id'  => Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_obj( $this->fetch_incoming_link_param( 'id' ) ),
                'sys_type'     => $this->fetch_incoming_link_param( 'type' ),
                'template'     => $this->template,
                'post'         => $this->post,
                'post_fields'  => DT_Posts::get_post_field_settings( $this->template['post_type'] ),
                'translations' => [
                    'regions_of_focus' => __( 'Regions of Focus', 'disciple_tools' ),
                    'all_locations'    => __( 'All Locations', 'disciple_tools' )
                ],
                'images'       => [
                    'small-add.svg' => get_template_directory_uri() . '/dt-assets/images/small-add.svg'
                ],
                'mapbox'       => [
                    'map_key'        => DT_Mapbox_API::get_key(),
                    'google_map_key' => Disciple_Tools_Google_Geocode_API::get_key(),
                    'translations'   => [
                        'search_location' => __( 'Search Location', 'disciple_tools' ),
                        'delete_location' => __( 'Delete Location', 'disciple_tools' ),
                        'use'             => __( 'Use', 'disciple_tools' ),
                        'open_modal'      => __( 'Open Modal', 'disciple_tools' )
                    ]
                ]

            ] ) ?>][0]

            console.log(jsObject);

            /**
             * Activate various field controls.
             */

            window.activate_field_controls = () => {
                jQuery('.form-content-table > tbody > tr').each(function (idx, tr) {

                    let field_id = jQuery(tr).find('#form_content_table_field_id').val();
                    let field_type = jQuery(tr).find('#form_content_table_field_type').val();
                    let field_template_type = jQuery(tr).find('#form_content_table_field_template_type').val();
                    let field_meta = jQuery(tr).find('#form_content_table_field_meta');

                    if (field_template_type && field_template_type === 'dt') {
                        switch (field_type) {
                            case 'communication_channel':

                                /**
                                 * Add
                                 */

                                jQuery(tr).find('button.add-button').on('click', evt => {
                                    let field = jQuery(evt.currentTarget).data('list-class');
                                    let list = jQuery(tr).find(`#edit-${field}`);

                                    list.append(`
                                        <div class="input-group">
                                            <input type="text" data-field="${window.lodash.escape(field)}" class="dt-communication-channel input-group-field" dir="auto" />
                                            <div class="input-group-button">
                                                <button class="button alert input-height delete-button-style channel-delete-button delete-button new-${window.lodash.escape(field)}" data-key="new" data-field="${window.lodash.escape(field)}">&times;</button>
                                            </div>
                                        </div>`);
                                });

                                /**
                                 * Remove
                                 */

                                jQuery(document).on('click', '.channel-delete-button', evt => {
                                    let field = jQuery(evt.currentTarget).data('field');
                                    let key = jQuery(evt.currentTarget).data('key');

                                    // If needed, keep a record of key for future api removal.
                                    if (key !== 'new') {
                                        let deleted_keys = (field_meta.val()) ? JSON.parse(field_meta.val()) : [];
                                        deleted_keys.push(key);
                                        field_meta.val(JSON.stringify(deleted_keys));
                                    }

                                    // Final removal of input group
                                    jQuery(evt.currentTarget).parent().parent().remove();
                                });

                                break;

                            case 'location_meta':

                                /**
                                 * Load
                                 */

                                jQuery(tr).find('#mapbox-wrapper').empty().append(`
                                    <div id="location-grid-meta-results"></div>
                                    <div class="reveal" id="mapping-modal" data-v-offset="0" data-reveal>
                                      <div id="mapping-modal-contents"></div>
                                      <button class="close-button" data-close aria-label="Close modal" type="button">
                                        <span aria-hidden="true">&times;</span>
                                      </button>
                                    </div>
                                `);

                                // Display previously saved locations
                                let lgm_results = jQuery(tr).find('#location-grid-meta-results');
                                if (jsObject['post']['location_grid_meta'] !== undefined && jsObject['post']['location_grid_meta'].length !== 0) {
                                    jQuery.each(jsObject['post']['location_grid_meta'], function (i, v) {
                                        if (v.grid_meta_id) {
                                            lgm_results.append(`<div class="input-group">
                                                <input type="text" class="active-location input-group-field" id="location-${window.lodash.escape(v.grid_meta_id)}" dir="auto" value="${window.lodash.escape(v.label)}" readonly />
                                                <div class="input-group-button">
                                                  <button type="button" class="button success delete-button-style open-mapping-grid-modal" title="${window.lodash.escape(jsObject['mapbox']['translations']['open_modal'])}" data-id="${window.lodash.escape(v.grid_meta_id)}"><i class="fi-map"></i></button>
                                                  <button type="button" class="button alert delete-button-style delete-button mapbox-delete-button" title="${window.lodash.escape(jsObject['mapbox']['translations']['delete_location'])}" data-id="${window.lodash.escape(v.grid_meta_id)}">&times;</button>
                                                </div>
                                              </div>`);
                                        } else {
                                            lgm_results.append(`<div class="input-group">
                                                <input type="text" class="dt-communication-channel input-group-field" id="${window.lodash.escape(v.key)}" value="${window.lodash.escape(v.label)}" dir="auto" data-field="contact_address" />
                                                <div class="input-group-button">
                                                  <button type="button" class="button success delete-button-style open-mapping-address-modal"
                                                      title="${window.lodash.escape(jsObject['mapbox']['translation']['open_modal'])}"
                                                      data-id="${window.lodash.escape(v.key)}"
                                                      data-field="contact_address"
                                                      data-key="${window.lodash.escape(v.key)}">
                                                      <i class="fi-pencil"></i>
                                                  </button>
                                                  <button type="button" class="button alert input-height delete-button-style channel-delete-button delete-button" title="${window.lodash.escape(jsObject['mapbox']['translations']['delete_location'])}" data-id="${window.lodash.escape(v.key)}" data-field="contact_address" data-key="${window.lodash.escape(v.key)}">&times;</button>
                                                </div>
                                              </div>`);
                                        }
                                    })
                                }

                                /**
                                 * Add
                                 */

                                jQuery(tr).find('#new-mapbox-search').on('click', evt => {

                                    // Display search field with autosubmit disabled!
                                    if (jQuery(tr).find('#mapbox-autocomplete').length === 0) {
                                        jQuery(tr).find('#mapbox-wrapper').prepend(`
                                        <div id="mapbox-autocomplete" class="mapbox-autocomplete input-group" data-autosubmit="false">
                                            <input id="mapbox-search" type="text" name="mapbox_search" placeholder="${window.lodash.escape(jsObject['mapbox']['translations']['search_location'])}" autocomplete="off" dir="auto" />
                                            <div class="input-group-button">
                                                <button id="mapbox-spinner-button" class="button hollow" style="display:none;"><span class="loading-spinner active"></span></button>
                                                <button id="mapbox-clear-autocomplete" class="button alert input-height delete-button-style mapbox-delete-button" type="button" title="${window.lodash.escape(jsObject['mapbox']['translations']['delete_location'])}" >&times;</button>
                                            </div>
                                            <div id="mapbox-autocomplete-list" class="mapbox-autocomplete-items"></div>
                                        </div>`);
                                    }

                                    // Switch over to standard workflow, with autosubmit disabled!
                                    write_input_widget();
                                });

                                // Hide new button and default to single entry
                                jQuery(tr).find('#new-mapbox-search').hide();
                                jQuery(tr).find('#new-mapbox-search').trigger('click');

                                /**
                                 * Remove
                                 */

                                jQuery(document).on('click', '.mapbox-delete-button', evt => {
                                    let id = jQuery(evt.currentTarget).data('id');

                                    // If needed, keep a record of key for future api removal.
                                    if (id !== undefined) {
                                        let deleted_ids = (field_meta.val()) ? JSON.parse(field_meta.val()) : [];
                                        deleted_ids.push(id);
                                        field_meta.val(JSON.stringify(deleted_ids));

                                        // Final removal of input group
                                        jQuery(evt.currentTarget).parent().parent().remove();

                                    } else {

                                        // Remove global selected location
                                        window.selected_location_grid_meta = null;
                                    }
                                });

                                /**
                                 * Open Modal
                                 */

                                jQuery(tr).find('.open-mapping-grid-modal').on('click', evt => {
                                    let grid_meta_id = jQuery(evt.currentTarget).data('id');
                                    let post_location_grid_meta = jsObject['post']['location_grid_meta'];

                                    if (post_location_grid_meta !== undefined && post_location_grid_meta.length !== 0) {
                                        jQuery.each(post_location_grid_meta, function (i, v) {
                                            if (String(grid_meta_id) === String(v.grid_meta_id)) {
                                                return load_modal(v.lng, v.lat, v.level, v.label, v.grid_id);
                                            }
                                        });
                                    }
                                });

                                break;

                            case 'location':

                                /**
                                 * Load Typeahead
                                 */

                                let typeahead_field_input = '.js-typeahead-' + field_id;
                                if (!window.Typeahead[typeahead_field_input]) {
                                    jQuery(tr).find(typeahead_field_input).typeahead({
                                        input: typeahead_field_input,
                                        minLength: 0,
                                        accent: true,
                                        searchOnFocus: true,
                                        maxItem: 20,
                                        dropdownFilter: [{
                                            key: 'group',
                                            value: 'focus',
                                            template: window.lodash.escape(jsObject['translations']['regions_of_focus']),
                                            all: window.lodash.escape(jsObject['translations']['all_locations'])
                                        }],
                                        source: {
                                            focus: {
                                                display: "name",
                                                ajax: {
                                                    url: jsObject['root'] + 'dt/v1/mapping_module/search_location_grid_by_name',
                                                    data: {
                                                        s: "{{query}}",
                                                        filter: function () {
                                                            return window.lodash.get(window.Typeahead[typeahead_field_input].filters.dropdown, 'value', 'all');
                                                        }
                                                    },
                                                    beforeSend: function (xhr) {
                                                        xhr.setRequestHeader('X-WP-Nonce', jsObject['nonce']);
                                                    },
                                                    callback: {
                                                        done: function (data) {
                                                            return data.location_grid;
                                                        }
                                                    }
                                                }
                                            }
                                        },
                                        display: "name",
                                        templateValue: "{{name}}",
                                        dynamic: true,
                                        multiselect: {
                                            matchOn: ["ID"],
                                            data: function () {
                                                return [];
                                            }, callback: {
                                                onCancel: function (node, item) {

                                                    // Keep a record of deleted options
                                                    let deleted_items = (field_meta.val()) ? JSON.parse(field_meta.val()) : [];
                                                    deleted_items.push(item);
                                                    field_meta.val(JSON.stringify(deleted_items));

                                                }
                                            }
                                        },
                                        callback: {
                                            onClick: function (node, a, item, event) {
                                            },
                                            onReady() {
                                                this.filters.dropdown = {
                                                    key: "group",
                                                    value: "focus",
                                                    template: window.lodash.escape(jsObject['translations']['regions_of_focus'])
                                                };
                                                this.container
                                                    .removeClass("filter")
                                                    .find("." + this.options.selector.filterButton)
                                                    .html(window.lodash.escape(jsObject['translations']['regions_of_focus']));
                                            }
                                        }
                                    });
                                }

                                break;

                            case 'multi_select':

                                /**
                                 * Handle Selections
                                 */

                                jQuery('.dt_multi_select').on("click", function (evt) {
                                    let multi_select = jQuery(evt.currentTarget);
                                    if (multi_select.hasClass('empty-select-button')) {
                                        multi_select.removeClass('empty-select-button');
                                        multi_select.addClass('selected-select-button');
                                    } else {
                                        multi_select.removeClass('selected-select-button');
                                        multi_select.addClass('empty-select-button');
                                    }
                                });

                                break;

                            case 'date':

                                /**
                                 * Load Date Range Picker
                                 */

                                let post_date = jsObject['post'][field_id];
                                jQuery(tr).find('#' + field_id).daterangepicker({
                                    singleDatePicker: true,
                                    timePicker: true,
                                    startDate: (post_date !== undefined) ? moment.unix(post_date['timestamp']) : moment(),
                                    locale: {
                                        format: 'MMMM D, YYYY'
                                    }
                                }, function (start, end, label) {
                                    if (start) {
                                        field_meta.val(start.unix());
                                    }
                                });

                                // Set default hidden meta field value
                                field_meta.val((post_date !== undefined) ? post_date['timestamp'] : (jQuery.now() / 1000));

                                /**
                                 * Clear Date
                                 */

                                jQuery(tr).find('.clear-date-button').on('click', evt => {
                                    let input_id = jQuery(evt.currentTarget).data('inputid');

                                    if (input_id) {
                                        jQuery(tr).find('#' + input_id).val('');
                                        field_meta.val('');
                                    }
                                });

                                break;

                        }
                    }
                });
            };
            window.activate_field_controls();

            /**
             * Update form's title and initial header message, based on current
             * template details.
             */

            let template = jsObject['template'];
            jQuery('#title').html(`<b>${window.lodash.escape(template['name'])}</b>`);
            jQuery('#post_type').val(template['post_type']);

            // If specified, display header message
            if (template['message']) {
                let message = jQuery('#template_msg');
                message.html(`${window.lodash.escape(template['message'])}`);
                message.fadeIn('fast');
            }

            /**
             * Determine if field has been enabled
             */
            window.is_field_enabled = (field_id) => {

                // Enabled by default
                let enabled = true;

                // Iterate over type field settings
                if (jsObject.link_obj_id['type_fields']) {
                    jsObject.link_obj_id['type_fields'].forEach(field => {

                        // Is matched field enabled...?
                        if (String(field['id']) === String(field_id)) {
                            enabled = field['enabled'];
                        }
                    });
                }

                return enabled;
            };

            /**
             * Handle template field dynamic code generation - communication_channel
             */
            window.handle_template_field_code_generation_comms_channel = (field_id, key, value) => {
                return `
                    <div class="input-group">
                        <input
                            type="text"
                            data-field="${window.lodash.escape(field_id)}"
                            value="${window.lodash.escape(value)}"
                            class="dt-communication-channel input-group-field"
                            dir="auto"
                            style="max-width: 50%;"
                        />

                        <div class="input-group-button">
                            <button
                                class="button alert input-height delete-button-style channel-delete-button delete-button new-${window.lodash.escape(field_id)}"
                                data-field="${window.lodash.escape(field_id)}"
                                data-key="${window.lodash.escape(key)}"
                            >x</button>
                        </div>
                    </div>`;
            }

            /**
             * Handle template field dynamic code generation
             */
            window.handle_template_field_code_generation = (field) => {

                let post_fields = jsObject['post_fields'];
                let html = '';
                let callback = function () {
                };

                // Prepare hidden field meta info
                let field_meta_html = `
                <input id="form_content_table_field_id" type="hidden" value="${field['id']}">
                <input id="form_content_table_field_type" type="hidden" value="${(field['type'] === 'dt') ? post_fields[field['id']]['type'] : ''}">
                <input id="form_content_table_field_template_type" type="hidden" value="${field['type']}">
                `;

                // Generate field code, accordingly; based on dt post field type.
                if (field['type'] === 'dt') {
                    if (post_fields[field['id']]) {

                        let post_field = post_fields[field['id']];

                        switch (post_field['type']) {

                            case 'number':
                                html = `
                                <tr>
                                    <td style="vertical-align: top;"><b>${window.lodash.escape(post_field['name'])}</b></td>
                                    <td style="vertical-align: top; max-width: 50%;" class="form-content-table-field">${field_meta_html}<input id="field_${field['id']}" type="number" value="" /></td>
                                </tr>`;
                                break;

                            case 'textarea':
                            case 'text':
                                html = `
                                <tr>
                                    <td style="vertical-align: top;"><b>${window.lodash.escape(post_field['name'])}</b></td>
                                    <td style="vertical-align: top;" class="form-content-table-field">${field_meta_html}<input id="field_${field['id']}" type="text" value="" /></td>
                                </tr>`;
                                break;

                            case 'key_select':
                                html = `
                                <tr>
                                    <td style="vertical-align: top;"><b>${window.lodash.escape(post_field['name'])}</b></td>
                                    <td style="vertical-align: top;" class="form-content-table-field">${field_meta_html}
                                        <select id="field_${field['id']}" class="select-field">
                                            <option value=""></option>`;

                                jQuery.each(post_field['default'], function (key, option) {
                                    html += `<option value="${window.lodash.escape(key)}">${window.lodash.escape(option['label'])}</option>`;
                                });

                                html += `</select>
                                    </td>
                                </tr>`;
                                break;

                            case 'multi_select':
                                html = `
                                <tr>
                                    <td style="vertical-align: top;"><b>${window.lodash.escape(post_field['name'])}</b></td>
                                    <td style="vertical-align: top;" class="form-content-table-field">${field_meta_html}`;

                                jQuery.each(post_field['default'], function (key, option) {
                                    html += `<button id="${window.lodash.escape(key)}"
                                                    type="button"
                                                    data-field-key="field_${field['id']}"
                                                    class="dt_multi_select_${field['id']} empty-select-button button select-button">
                                                <img class="dt-icon" src="${window.lodash.escape(option['icon'])}"/>
                                                ${window.lodash.escape(option['label'])}
                                            </button>`;
                                });

                                html += `
                                    </td>
                                </tr>`;

                                // Specify housekeeping tasks, to be executed following appending of html to DOM!
                                callback = function () {
                                    jQuery('.dt_multi_select_' + field['id']).on("click", function (evt) {
                                        let multi_select = jQuery(evt.currentTarget);
                                        if (multi_select.hasClass('empty-select-button')) {
                                            multi_select.removeClass('empty-select-button');
                                            multi_select.addClass('selected-select-button');
                                        } else {
                                            multi_select.removeClass('selected-select-button');
                                            multi_select.addClass('empty-select-button');
                                        }
                                    });
                                };
                                break;

                            case 'boolean':
                                html = `
                                <tr>
                                    <td style="vertical-align: top;"><b>${window.lodash.escape(post_field['name'])}</b></td>
                                    <td style="vertical-align: top;" class="form-content-table-field">
                                        ${field_meta_html}
                                        <input id="field_initial_state_${field['id']}" type="hidden" value="false">
                                        <input id="field_${field['id']}" type="checkbox" value="" />
                                    </td>
                                </tr>`;
                                break;

                            case 'communication_channel':
                                html = `
                                <tr>
                                    <td style="vertical-align: top;">
                                        <b>${window.lodash.escape(post_field['name'])}</b>
                                        <button data-list-class="${field['id']}" class="add-button-${field['id']}" type="button">
                                            <img src="${jsObject['images']['small-add.svg']}" />
                                        </button>
                                    </td>
                                    <td style="vertical-align: top;" class="form-content-table-field" id="form_content_table_field_${field['id']}">
                                        ${field_meta_html}
                                        <input id="field_deleted_keys" type="hidden" value="[]"/>
                                        <div class="input-groups">${window.handle_template_field_code_generation_comms_channel(field['id'], 'new', '')}</div>
                                    </td>
                                </tr>`;

                                // Register add and removal listeners
                                callback = function () {

                                    // Add
                                    jQuery('button.add-button-' + field['id']).on('click', evt => {
                                        let input_groups = jQuery(evt.currentTarget).parent().parent().find('.input-groups');
                                        input_groups.append(window.handle_template_field_code_generation_comms_channel(field['id'], 'new', ''));
                                    });

                                    // Remove
                                    jQuery(document).on('click', '.channel-delete-button', evt => {

                                        let key = jQuery(evt.currentTarget).data('key');

                                        // If needed, keep a record of key for future api removal.
                                        if (key !== 'new') {
                                            let deleted_keys_input = jQuery(evt.currentTarget).parent().parent().parent().parent().find('#field_deleted_keys');
                                            let deleted_keys = JSON.parse(deleted_keys_input.val());
                                            deleted_keys.push(key);
                                            deleted_keys_input.val(JSON.stringify(deleted_keys));
                                        }

                                        // Final removal of input group
                                        jQuery(evt.currentTarget).parent().parent().remove();
                                    });
                                };
                                break;

                            case 'date':
                                html = `
                                <tr>
                                    <td style="vertical-align: top;"><b>${window.lodash.escape(post_field['name'])}</b></td>
                                    <td style="vertical-align: top;" class="form-content-table-field">${field_meta_html}
                                        <input id="field_date_popup_${field['id']}" type="text" value="" />
                                        <input id="field_date_ts_${field['id']}" type="hidden" value="" />
                                    </td>
                                </tr>`;

                                // Activate daterangepicker once html has been added to DOM
                                callback = function () {
                                    jQuery('#field_date_popup_' + field['id']).daterangepicker({
                                        singleDatePicker: true,
                                        timePicker: true,
                                        locale: {
                                            format: 'MMMM D, YYYY'
                                        }
                                    }, function (start, end, label) {
                                        if (start) {
                                            jQuery('#field_date_ts_' + field['id']).val(start.unix());
                                        }
                                    });
                                };
                                break;

                            case 'location':
                                html = `
                                <tr>
                                    <td style="vertical-align: top;"><b>${window.lodash.escape(post_field['name'])}</b></td>
                                    <td style="vertical-align: top;" class="form-content-table-field">${field_meta_html}
                                        <input id="field_deletions_${field['id']}" type="hidden" value="[]">
                                        <div class="typeahead__container">
                                            <div class="typeahead__field">
                                                <div class="typeahead__query">
                                                    <input id="field_${field['id']}" type="text" class="dt-typeahead" autocomplete="off" placeholder="" style="min-width: 90%;">
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>`;

                                // Instantiate typeahead configuration
                                callback = function () {
                                    let typeahead_field_input = '#field_' + field['id'];
                                    if (!window.Typeahead[typeahead_field_input]) {
                                        jQuery(typeahead_field_input).typeahead({
                                            input: typeahead_field_input,
                                            minLength: 0,
                                            accent: true,
                                            searchOnFocus: true,
                                            maxItem: 20,
                                            dropdownFilter: [{
                                                key: 'group',
                                                value: 'focus',
                                                template: window.lodash.escape(jsObject['translations']['regions_of_focus']),
                                                all: window.lodash.escape(jsObject['translations']['all_locations'])
                                            }],
                                            source: {
                                                focus: {
                                                    display: "name",
                                                    ajax: {
                                                        url: jsObject['root'] + 'dt/v1/mapping_module/search_location_grid_by_name',
                                                        data: {
                                                            s: "{{query}}",
                                                            filter: function () {
                                                                return window.lodash.get(window.Typeahead[typeahead_field_input].filters.dropdown, 'value', 'all');
                                                            }
                                                        },
                                                        beforeSend: function (xhr) {
                                                            xhr.setRequestHeader('X-WP-Nonce', jsObject['nonce']);
                                                        },
                                                        callback: {
                                                            done: function (data) {
                                                                return data.location_grid;
                                                            }
                                                        }
                                                    }
                                                }
                                            },
                                            display: "name",
                                            templateValue: "{{name}}",
                                            dynamic: true,
                                            multiselect: {
                                                matchOn: ["ID"],
                                                data: function () {
                                                    return [];
                                                }, callback: {
                                                    onCancel: function (node, item) {

                                                        // Keep a record of deleted options
                                                        let deletions_field = jQuery('#field_deletions_' + field['id']);
                                                        let deletions = JSON.parse(deletions_field.val());
                                                        deletions.push(item);
                                                        deletions_field.val(JSON.stringify(deletions));
                                                    }
                                                }
                                            },
                                            callback: {
                                                onClick: function (node, a, item, event) {
                                                },
                                                onReady() {
                                                    this.filters.dropdown = {
                                                        key: "group",
                                                        value: "focus",
                                                        template: window.lodash.escape(jsObject['translations']['regions_of_focus'])
                                                    };
                                                    this.container
                                                        .removeClass("filter")
                                                        .find("." + this.options.selector.filterButton)
                                                        .html(window.lodash.escape(jsObject['translations']['regions_of_focus']));
                                                }
                                            }
                                        });
                                    }
                                };
                                break;

                            default:
                                break;
                        }

                    }

                } else {

                    // Custom fields, shall be text inputs; which are captured as comments.
                    html = `
                    <tr>
                        <td style="vertical-align: top;"><b>${window.lodash.escape(field['label'])}</b></td>
                        <td style="vertical-align: top;" class="form-content-table-field">${field_meta_html}<input id="field_${field['id']}" type="text" value="" /></td>
                    </tr>
                    `;
                }

                return {
                    'html': html,
                    'callback': callback
                };
            };

            /**
             * Display fields associated with specified template; governed by
             * link obj.
             */

            let link_obj = jsObject['link_obj_id'];

            let form_content_table = jQuery('.form-content-table');
            /*TODO form_content_table.fadeOut('fast', function () {

                // Clear down existing table content.
                form_content_table.find('tbody tr').remove();

                // Iterate over fields, displaying accordingly; by link obj enabled flag!
                jQuery.each(template['fields'], function (idx, field) {
                    if (field['enabled'] && window.is_field_enabled(field['id'])) {
                        let field_code = window.handle_template_field_code_generation(field);
                        if (field_code['html']) {

                            // Render field html and execute callback
                            form_content_table.find('tbody').append(field_code['html']);
                            field_code['callback']();
                        }
                    }
                });

                // Display template fields
                //...form_content_table.fadeIn('fast');
            });

            /**
             * Fetch assigned contacts
             */
            window.get_magic = () => {
                jQuery.ajax({
                    type: "GET",
                    data: {
                        action: 'get',
                        parts: jsObject.parts
                    },
                    contentType: "application/json; charset=utf-8",
                    dataType: "json",
                    url: jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type,
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', jsObject.nonce)
                    }
                })
                    .done(function (data) {
                        window.load_magic(data)
                    })
                    .fail(function (e) {
                        console.log(e)
                        jQuery('#error').html(e)
                    })
            };

            /**
             * Display returned list of assigned contacts
             */
            window.load_magic = (data) => {
                let content = jQuery('#api-content');
                let table = jQuery('.api-content-table');
                let total = jQuery('#total');
                let spinner = jQuery('.loading-spinner');

                // Remove any previous entries
                table.find('tbody').empty()

                // Set total hits count
                total.html(data['total'] ? data['total'] : '0');

                // Iterate over returned posts
                if (data['posts']) {
                    data['posts'].forEach(v => {

                        let html = `<tr onclick="get_assigned_contact_details('${window.lodash.escape(v.id)}', '${window.lodash.escape(v.name)}');">
                                <td>${window.lodash.escape(v.name)}</td>
                            </tr>`;

                        table.find('tbody').append(html);

                    });
                }
            };

            /**
             * Fetch requested contact details
             */
            window.get_contact = (post_id) => {
                let comment_count = 2;

                jQuery('.form-content-table').fadeOut('fast', function () {

                    // Dispatch request call
                    jQuery.ajax({
                        type: "GET",
                        data: {
                            action: 'get',
                            parts: jsObject.parts,
                            sys_type: jsObject.sys_type,
                            post_id: post_id,
                            post_type: jQuery('#post_type').val(),
                            comment_count: comment_count,
                            ts: moment().unix() // Alter url shape, to force cache refresh!
                        },
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type + '/post',
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', jsObject.nonce);
                            xhr.setRequestHeader('Cache-Control', 'no-store');
                        }

                    }).done(function (data) {

                        // Was our post fetch request successful...?
                        if (data['success'] && data['post']) {

                            let post = data['post'];
                            let post_fields = jsObject['post_fields'];

                            // ID
                            jQuery('#post_id').val(post['ID']);
                            jQuery('#contact_name').html(post['title']);

                            // Iterate over template fields, to identify those enabled and with post values.
                            jQuery.each(template['fields'], function (idx, field) {
                                if (field['enabled'] && window.is_field_enabled(field['id'])) {

                                    // Ensure there are corresponding post fields.
                                    if (post_fields[field['id']] && post[field['id']]) {

                                        let settings_field = post_fields[field['id']];
                                        let post_field = post[field['id']];

                                        // Extract post value accordingly based on field type.
                                        switch (settings_field['type']) {

                                            case 'number':
                                            case 'textarea':
                                            case 'text':
                                                jQuery('#field_' + field['id']).val(window.lodash.escape(post_field));
                                                break;

                                            case 'key_select':
                                                jQuery('#field_' + field['id']).val(window.lodash.escape(post_field['key']));
                                                break;

                                            case 'multi_select':
                                                jQuery('.dt_multi_select_' + field['id']).each(function (idx, button) {
                                                    if (post_field.includes($(button).attr('id'))) {
                                                        $(button).removeClass('empty-select-button');
                                                        $(button).addClass('selected-select-button');

                                                    } else {
                                                        $(button).removeClass('selected-select-button');
                                                        $(button).addClass('empty-select-button');
                                                    }
                                                });
                                                break;

                                            case 'boolean':
                                                jQuery('#field_' + field['id']).prop('checked', post_field);
                                                jQuery('#field_initial_state_' + field['id']).val(post_field ? 'true' : 'false');
                                                break;

                                            case 'communication_channel':

                                                // First, obtain handle onto input groups div and empty contents
                                                let input_groups = jQuery('#form_content_table_field_' + field['id']).find('.input-groups');
                                                input_groups.empty();

                                                // Determine number of input fields to be created
                                                if (post_field.length > 0) {

                                                    // Generate inputs for each corresponding key/value pair
                                                    jQuery.each(post_field, function (idx, value) {
                                                        input_groups.append(window.handle_template_field_code_generation_comms_channel(field['id'], value['key'], value['value']));
                                                    });

                                                } else {
                                                    input_groups.append(window.handle_template_field_code_generation_comms_channel(field['id'], 'new', ''));
                                                }
                                                break;

                                            case 'date':
                                                jQuery('#field_date_ts_' + field['id']).val(post_field['timestamp']);
                                                jQuery('#field_date_popup_' + field['id']).data('daterangepicker').setStartDate(moment.unix(post_field['timestamp']));
                                                break;

                                            case 'location':
                                                let typeahead = window.Typeahead['#field_' + field['id']];
                                                if (typeahead) {

                                                    // Clear down existing typeahead arrays and containers
                                                    typeahead.items = [];
                                                    typeahead.comparedItems = [];
                                                    jQuery(typeahead.label.container).empty();

                                                    // Iterate over post locations and update typeahead multiselect list accordingly
                                                    jQuery.each(post_field, function (idx, location) {
                                                        typeahead.addMultiselectItemLayout({
                                                            ID: location['id'],
                                                            name: window.lodash.escape(location['label'])
                                                        });
                                                    });
                                                }
                                                break;

                                            default:
                                                break;
                                        }
                                    }
                                }
                            });

                            // COMMENTS -> If Specified
                            if (template['show_recent_comments']) {
                                let counter = 0;
                                if (data['comments']['comments']) {
                                    data['comments']['comments'].forEach(comment => {
                                        if (counter++ < comment_count) { // Enforce comment count limit..!
                                            let html_comments = `<tr>`;
                                            html_comments += `<td></td>`;
                                            html_comments += `<td style="vertical-align: top;"><b>${window.lodash.escape(comment['comment_author'])} @ ${window.lodash.escape(comment['comment_date'])}</b><br>`;
                                            html_comments += `${window.lodash.escape(comment['comment_content'])}</td>`;
                                            html_comments += `</tr>`;

                                            jQuery('.form-content-table').find('tbody').append(html_comments);
                                        }
                                    });
                                }
                            }

                            // Display submit button
                            jQuery('#content_submit_but').fadeIn('fast');

                            // Display updated post fields
                            jQuery('.form-content-table').fadeIn('fast');

                        }

                    }).fail(function (e) {
                        console.log(e);
                        jQuery('#error').html(e);
                    });
                });
            };

            /**
             * Handle fetch request for contact details
             */
            window.get_assigned_contact_details = (post_id, post_name) => {
                let contact_name = jQuery('#contact_name');

                // Update contact name
                contact_name.html(post_name);

                // Fetch requested contact details
                window.get_contact(post_id);
            };

            /**
             * Adjust visuals, based on incoming sys_type
             */
            let assigned_contacts_div = jQuery('#assigned_contacts_div');
            switch (jsObject.sys_type) {
                case 'wp_user':
                    // Fetch assigned contacts for incoming user
                    assigned_contacts_div.fadeIn('fast');
                    window.get_magic();
                    break;
                default: // 'post' or anything else!
                    // Bypass contacts list and directly fetch requested contact details
                    assigned_contacts_div.fadeOut('fast');
                    //TODO...window.get_contact(jsObject.parts.post_id);
                    break;
            }

            /**
             * Submit contact details
             */
            jQuery('#content_submit_but').on("click", function () {
                let id = jQuery('#post_id').val();
                let post_type = jQuery('#post_type').val();

                // Reset error message field
                let error = jQuery('#error');
                error.html('');

                // Sanity check content prior to submission
                if (!id || String(id).trim().length === 0) {
                    error.html('Invalid post id detected!');

                } else {

                    // Build payload accordingly, based on enabled states
                    let payload = {
                        'action': 'get',
                        'parts': jsObject.parts,
                        'sys_type': jsObject.sys_type,
                        'post_id': id,
                        'post_type': post_type,
                        'fields': {
                            'dt': [],
                            'custom': []
                        }
                    }

                    // Iterate over form fields, capturing values accordingly.
                    jQuery('.form-content-table > tbody > tr').each(function (idx, tr) {

                        let field_id = jQuery(tr).find('#form_content_table_field_id').val();
                        let field_type = jQuery(tr).find('#form_content_table_field_type').val();
                        let field_template_type = jQuery(tr).find('#form_content_table_field_template_type').val();
                        let field_meta = jQuery(tr).find('#form_content_table_field_meta');

                        if (window.is_field_enabled(field_id)) {
                            let selector = '#' + field_id;

                            if (field_template_type === 'dt') {
                                switch (field_type) {

                                    case 'number':
                                    case 'textarea':
                                    case 'text':
                                    case 'key_select':
                                        payload['fields']['dt'].push({
                                            id: field_id,
                                            dt_type: field_type,
                                            template_type: field_template_type,
                                            value: jQuery(tr).find(selector).val()
                                        });
                                        break;

                                    case 'communication_channel':
                                        let values = [];
                                        jQuery(tr).find('.input-group').each(function () {
                                            values.push({
                                                'key': jQuery(this).find('button').data('key'),
                                                'value': jQuery(this).find('input').val()
                                            });
                                        });
                                        payload['fields']['dt'].push({
                                            id: field_id,
                                            dt_type: field_type,
                                            template_type: field_template_type,
                                            value: values,
                                            deleted: field_meta.val() ? JSON.parse(field_meta.val()) : []
                                        });
                                        break;

                                    case 'multi_select':
                                        let options = [];
                                        jQuery(tr).find('button').each(function () {
                                            options.push({
                                                'value': jQuery(this).attr('id'),
                                                'delete': jQuery(this).hasClass('empty-select-button')
                                            });
                                        });
                                        payload['fields']['dt'].push({
                                            id: field_id,
                                            dt_type: field_type,
                                            template_type: field_template_type,
                                            value: options
                                        });
                                        break;

                                    case 'boolean':
                                        let initial_val = JSON.parse(jQuery(tr).find('#field_initial_state_' + field_id).val());
                                        let current_val = jQuery(tr).find(selector).prop('checked');

                                        payload['fields']['dt'].push({
                                            id: field_id,
                                            dt_type: field_type,
                                            template_type: field_template_type,
                                            value: current_val,
                                            changed: (initial_val !== current_val)
                                        });
                                        break;

                                    case 'date':
                                        payload['fields']['dt'].push({
                                            id: field_id,
                                            dt_type: field_type,
                                            template_type: field_template_type,
                                            value: field_meta.val()
                                        });
                                        break;

                                    case 'location':
                                        let typeahead = window.Typeahead['.js-typeahead-' + field_id];
                                        if (typeahead) {
                                            payload['fields']['dt'].push({
                                                id: field_id,
                                                dt_type: field_type,
                                                template_type: field_template_type,
                                                value: typeahead.items,
                                                deletions: field_meta.val() ? JSON.parse(field_meta.val()) : []
                                            });
                                        }
                                        break;

                                    case 'location_meta':
                                        payload['fields']['dt'].push({
                                            id: field_id,
                                            dt_type: field_type,
                                            template_type: field_template_type,
                                            value: (window.selected_location_grid_meta !== undefined) ? window.selected_location_grid_meta : '',
                                            deletions: field_meta.val() ? JSON.parse(field_meta.val()) : []
                                        });
                                        break;

                                    default:
                                        break;
                                }

                            } else if (field_template_type === 'custom') {
                                payload['fields']['custom'].push({
                                    id: field_id,
                                    dt_type: field_type,
                                    template_type: field_template_type,
                                    value: jQuery(tr).find(selector).val()
                                });
                            }
                        }
                    });

                    // Submit data for post update
                    jQuery('#content_submit_but').prop('disabled', true);

                    jQuery.ajax({
                        type: "GET",
                        data: payload,
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type + '/update',
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', jsObject.nonce)
                        }

                    }).done(function (data) {

                        // If successful, refresh page, otherwise; display error message
                        if (data['success']) {
                            window.location.reload();

                        } else {
                            jQuery('#error').html(data['message']);
                            jQuery('#content_submit_but').prop('disabled', false);
                        }

                    }).fail(function (e) {
                        console.log(e);
                        jQuery('#error').html(e);
                        jQuery('#content_submit_but').prop('disabled', false);
                    });
                }
            });
        </script>
        <?php
        return true;
    }

    public function body() {
        ?>
        <div id="custom-style"></div>
        <div id="wrapper">
            <div class="grid-x">
                <div class="cell center">
                    <h2 id="title"></h2>
                </div>
            </div>
            <hr>
            <div id="content">

                <!-- TEMPLATE MESSAGE -->
                <p id="template_msg" style="display: none;"></p>

                <div id="assigned_contacts_div" style="display: none;">
                    <h3><?php esc_html_e( "Assigned Contacts", 'disciple_tools_bulk_magic_link_sender' ) ?> [ <span
                            id="total">0</span> ]</h3>
                    <hr>
                    <div class="grid-x api-content-div-style" id="api-content">
                        <table class="api-content-table">
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                    <br>
                </div>

                <!-- ERROR MESSAGES -->
                <span id="error" style="color: red;"></span>
                <br>
                <br>

                <h3><?php esc_html_e( "Details", 'disciple_tools_bulk_magic_link_sender' ) ?>
                    [ <span
                        id="contact_name"><?php echo( ! empty( $this->post ) ? $this->post['name'] : '---' ) ?></span> ]
                </h3>
                <hr>
                <div class="grid-x" id="form-content">
                    <input id="post_id" type="hidden"
                           value="<?php echo( ! empty( $this->post ) ? $this->post['ID'] : '' ) ?>"/>
                    <input id="post_type" type="hidden"
                           value="<?php echo( ! empty( $this->post ) ? $this->post['post_type'] : '' ) ?>"/>
                    <?php
                    // Revert back to dt translations
                    $this->hard_switch_to_default_dt_text_domain();
                    ?>
                    <table style="<?php echo( ! empty( $this->post ) ? '' : 'display: none;' ) ?>"
                           class="form-content-table">
                        <tbody>
                        <?php

                        /**
                         * If a valid post is present, then display fields accordingly,
                         * based on hidden flags!
                         */

                        if ( ! empty( $this->post ) && ! empty( $this->post_field_settings ) && ! empty( $this->template ) ) {

                            // Display selected fields
                            foreach ( $this->template['fields'] ?? [] as $field ) {
                                if ( $field['enabled'] && $this->is_link_obj_field_enabled( $field['id'] ) ) {

                                    $post_field_type = '';
                                    if ( $field['type'] === 'dt' ) {
                                        $post_field_type = $this->post_field_settings[ $field['id'] ]['type'];
                                    }

                                    // Generate hidden values to assist downstream processing
                                    $hidden_values_html = '<input id="form_content_table_field_id" type="hidden" value="' . $field['id'] . '">';
                                    $hidden_values_html .= '<input id="form_content_table_field_type" type="hidden" value="' . $post_field_type . '">';
                                    $hidden_values_html .= '<input id="form_content_table_field_template_type" type="hidden" value="' . $field['type'] . '">';
                                    $hidden_values_html .= '<input id="form_content_table_field_meta" type="hidden" value="">';

                                    // Render field accordingly, based on template field type!
                                    switch ( $field['type'] ) {
                                        case 'dt':
                                            ?>
                                            <tr>
                                                <?php echo $hidden_values_html; ?>
                                                <td>
                                                    <?php
                                                    render_field_for_display( $field['id'], $this->post_field_settings, $this->post, true );
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php
                                            break;
                                        case 'custom':
                                            ?>
                                            <tr>
                                                <?php echo $hidden_values_html; ?>
                                                <td>
                                                    <?php
                                                    $this->render_custom_field_for_display( $field );
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php
                                            break;
                                    }
                                }
                            }

                            // If requested, display recent comments
                            if ( $this->template['show_recent_comments'] ) {
                                $recent_comments = DT_Posts::get_post_comments( $this->post['post_type'], $this->post['ID'], false, 'all', [ 'number' => 2 ] );
                                foreach ( $recent_comments['comments'] ?? [] as $comment ) {
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="section-subheader">
                                                <?php echo $comment['comment_author'] . ' @ ' . $comment['comment_date'] ?>
                                            </div>
                                            <?php echo $comment['comment_content'] ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
                <br>

                <!-- SUBMIT UPDATES -->
                <button id="content_submit_but"
                        style="<?php echo( ! empty( $this->post ) ? '' : 'display: none;' ) ?> min-width: 100%;"
                        class="button select-button">
                    <?php esc_html_e( "Submit Update", 'disciple_tools_bulk_magic_link_sender' ) ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Register REST Endpoints
     * @link https://github.com/DiscipleTools/disciple-tools-theme/wiki/Site-to-Site-Link for outside of wordpress authentication
     */
    public function add_endpoints() {
        $namespace = $this->root . '/v1';
        register_rest_route(
            $namespace, '/' . $this->type, [
                [
                    'methods'             => "GET",
                    'callback'            => [ $this, 'endpoint_get' ],
                    'permission_callback' => function ( WP_REST_Request $request ) {
                        $magic = new DT_Magic_URL( $this->root );

                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
        register_rest_route(
            $namespace, '/' . $this->type . '/post', [
                [
                    'methods'             => "GET",
                    'callback'            => [ $this, 'get_post' ],
                    'permission_callback' => function ( WP_REST_Request $request ) {
                        $magic = new DT_Magic_URL( $this->root );

                        /**
                         * Adjust global values accordingly, to accommodate both wp_user
                         * and post requests.
                         */
                        $this->adjust_global_values_by_incoming_sys_type( $request->get_params()['sys_type'] );

                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
        register_rest_route(
            $namespace, '/' . $this->type . '/update', [
                [
                    'methods'             => "GET",
                    'callback'            => [ $this, 'update_record' ],
                    'permission_callback' => function ( WP_REST_Request $request ) {
                        $magic = new DT_Magic_URL( $this->root );

                        /**
                         * Adjust global values accordingly, to accommodate both wp_user
                         * and post requests.
                         */
                        $this->adjust_global_values_by_incoming_sys_type( $request->get_params()['sys_type'] );

                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
    }

    public function endpoint_get( WP_REST_Request $request ) {
        $params = $request->get_params();
        if ( ! isset( $params['parts'], $params['action'] ) ) {
            return new WP_Error( __METHOD__, "Missing parameters", [ 'status' => 400 ] );
        }

        // Sanitize and fetch user id
        $params  = dt_recursive_sanitize_array( $params );
        $user_id = $params["parts"]["post_id"];

        // Fetch all assigned posts
        $data = [];
        if ( ! empty( $user_id ) ) {

            // Update logged-in user state as required
            $original_user = wp_get_current_user();
            wp_set_current_user( $user_id );

            // Fetch all assigned posts
            $posts = DT_Posts::list_posts( 'contacts', [
                'limit'  => 1000,
                'fields' => [
                    [
                        'assigned_to' => [ 'me' ],
                        "subassigned" => [ 'me' ]
                    ],
                    "overall_status" => [
                        "new",
                        "unassigned",
                        "assigned",
                        "active"
                    ]
                ]
            ] );

            // Revert to original user
            if ( ! empty( $original_user ) && isset( $original_user->ID ) ) {
                wp_set_current_user( $original_user->ID );
            }

            // Iterate and return valid posts
            if ( ! empty( $posts ) && isset( $posts['posts'], $posts['total'] ) ) {
                $data['total'] = $posts['total'];
                foreach ( $posts['posts'] ?? [] as $post ) {
                    $data['posts'][] = [
                        'id'   => $post['ID'],
                        'name' => $post['name']
                    ];
                }
            }
        }

        return $data;
    }

    public function get_post( WP_REST_Request $request ) {
        $params = $request->get_params();
        if ( ! isset( $params['post_id'], $params['parts'], $params['action'], $params['comment_count'], $params['sys_type'] ) ) {
            return new WP_Error( __METHOD__, "Missing parameters", [ 'status' => 400 ] );
        }

        // Sanitize and fetch user/post id
        $params = dt_recursive_sanitize_array( $params );

        // Update logged-in user state if required accordingly, based on their sys_type
        if ( ! is_user_logged_in() ) {
            $this->update_user_logged_in_state( $params['sys_type'], $params["parts"]["post_id"] );
        }

        // Fetch corresponding contacts post record
        $response = [];
        $post     = DT_Posts::get_post( 'contacts', $params['post_id'], false );
        if ( ! empty( $post ) && ! is_wp_error( $post ) ) {
            $response['success']  = true;
            $response['post']     = $post;
            $response['comments'] = DT_Posts::get_post_comments( 'contacts', $params['post_id'], false, 'all', [ 'number' => $params['comment_count'] ] );
        } else {
            $response['success'] = false;
        }

        return $response;
    }

    public function update_record( WP_REST_Request $request ) {
        $params = $request->get_params();
        if ( ! isset( $params['post_id'], $params['post_type'], $params['parts'], $params['action'], $params['sys_type'], $params['fields'] ) ) {
            return new WP_Error( __METHOD__, "Missing core parameters", [ 'status' => 400 ] );
        }

        // Sanitize and fetch user id
        $params = dt_recursive_sanitize_array( $params );

        // Update logged-in user state if required accordingly, based on their sys_type
        if ( ! is_user_logged_in() ) {
            $this->update_user_logged_in_state( $params['sys_type'], $params["parts"]["post_id"] );
        }

        $updates = [];

        // First, capture and package incoming DT field values
        foreach ( $params['fields']['dt'] ?? [] as $field ) {
            switch ( $field['dt_type'] ) {
                case 'number':
                case 'textarea':
                case 'text':
                case 'key_select':
                case 'date':
                    $updates[ $field['id'] ] = $field['value'];
                    break;

                case 'boolean':

                    // Only update if there has been a state change!
                    if ( $field['changed'] === 'true' ) {
                        $updates[ $field['id'] ] = $field['value'] === 'true';
                    }
                    break;

                case 'communication_channel':
                    $updates[ $field['id'] ] = [];

                    // First, capture additions and updates
                    foreach ( $field['value'] ?? [] as $value ) {
                        $comm          = [];
                        $comm['value'] = $value['value'];

                        if ( $value['key'] !== 'new' ) {
                            $comm['key'] = $value['key'];
                        }

                        $updates[ $field['id'] ][] = $comm;
                    }

                    // Next, capture deletions
                    foreach ( $field['deleted'] ?? [] as $delete_key ) {
                        $updates[ $field['id'] ][] = [
                            'delete' => true,
                            'key'    => $delete_key
                        ];
                    }
                    break;

                case 'multi_select':
                    $options = [];
                    foreach ( $field['value'] ?? [] as $option ) {
                        $entry          = [];
                        $entry['value'] = $option['value'];
                        if ( strtolower( trim( $option['delete'] ) ) === 'true' ) {
                            $entry['delete'] = true;
                        }
                        $options[] = $entry;
                    }
                    if ( ! empty( $options ) ) {
                        $updates[ $field['id'] ] = [
                            'values' => $options
                        ];
                    }
                    break;

                case 'location':
                    $locations = [];
                    foreach ( $field['value'] ?? [] as $location ) {
                        $entry          = [];
                        $entry['value'] = $location['ID'];
                        $locations[]    = $entry;
                    }

                    // Capture any incoming deletions
                    foreach ( $field['deletions'] ?? [] as $location ) {
                        $entry           = [];
                        $entry['value']  = $location['ID'];
                        $entry['delete'] = true;
                        $locations[]     = $entry;
                    }

                    // Package and append to global updates
                    if ( ! empty( $locations ) ) {
                        $updates[ $field['id'] ] = [
                            'values' => $locations
                        ];
                    }
                    break;

                case 'location_meta':
                    $locations = [];

                    // Capture selected location, if available; or prepare shape
                    if ( ! empty( $field['value'] ) && isset( $field['value'][ $field['id'] ] ) ) {
                        $locations[ $field['id'] ] = $field['value'][ $field['id'] ];

                    } else {
                        $locations[ $field['id'] ] = [
                            'values' => []
                        ];
                    }

                    // Capture any incoming deletions
                    foreach ( $field['deletions'] ?? [] as $id ) {
                        $entry                                 = [];
                        $entry['grid_meta_id']                 = $id;
                        $entry['delete']                       = true;
                        $locations[ $field['id'] ]['values'][] = $entry;
                    }

                    // Package and append to global updates
                    if ( ! empty( $locations[ $field['id'] ]['values'] ) ) {
                        $updates[ $field['id'] ] = $locations[ $field['id'] ];
                    }
                    break;
            }
        }

        // Update specified post record
        $updated_post = DT_Posts::update_post( $params['post_type'], $params['post_id'], $updates, false, false );
        if ( empty( $updated_post ) || is_wp_error( $updated_post ) ) {
            return [
                'success' => false,
                'message' => 'Unable to update contact record details!'
            ];
        }

        // Next, any identified custom fields, are to be added as comments
        foreach ( $params['fields']['custom'] ?? [] as $field ) {
            if ( ! empty( $field['value'] ) ) {
                $updated_comment = DT_Posts::add_post_comment( $updated_post['post_type'], $updated_post['ID'], $field['value'], 'comment', [], false );
                if ( empty( $updated_comment ) || is_wp_error( $updated_comment ) ) {
                    return [
                        'success' => false,
                        'message' => 'Unable to add comment to record details!'
                    ];
                }
            }
        }

        // Finally, return successful response
        return [
            'success' => true,
            'message' => ''
        ];
    }

    public function update_user_logged_in_state( $sys_type, $user_id ) {
        switch ( strtolower( trim( $sys_type ) ) ) {
            case 'post':
                wp_set_current_user( 0 );
                $current_user = wp_get_current_user();
                $current_user->add_cap( "magic_link" );
                $current_user->display_name = __( 'Smart Link Submission', 'disciple_tools' );
                break;
            default: // wp_user
                wp_set_current_user( $user_id );
                break;

        }
    }
}
