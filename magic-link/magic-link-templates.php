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
        add_action( "after_setup_theme", function (){
            self::load_templates();
        }, 200 );
    }

    private function load_templates() {
        foreach ( fetch_magic_link_templates() ?? [] as $template ) {
            new Disciple_Tools_Magic_Links_Templates( $template );
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

    public function __construct( $template = null ) {

        /**
         * Assuming a valid template, then capture header values
         */

        if ( empty( $template ) ) {
            return;
        }

        $this->template         = $template;
        $this->post_type        = $template['post_type'];
        $this->type             = array_map( 'sanitize_key', wp_unslash( explode( '_', $template['id'] ) ) )[1];
        $this->type_name        = $template['name'];
        $this->page_title       = $template['name'];
        $this->page_description = '';

        /**
         * Specify metadata structure, specific to the processing of current
         * magic link class type.
         *
         * - meta:              Magic link plugin related data.
         *      - app_type:     Flag indicating type to be processed by magic link plugin.
         *      - class_type:   Flag indicating template class type.
         */

        $this->meta = [
            'app_type'   => 'magic_link',
            'class_type' => 'template'
        ];

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
         * Attempt to load sooner, rather than later; corresponding post record details.
         */

        $this->post = DT_Posts::get_post( $this->post_type, $this->parts['post_id'], true, false );

        /**
         * Attempt to load corresponding link object, if a valid incoming id has been detected.
         */

        $this->link_obj = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_obj( $this->fetch_incoming_link_param( 'id' ) );

        /**
         * Load if valid url
         */

        add_action( 'dt_blank_body', [ $this, 'body' ] );
        add_filter( 'dt_magic_url_base_allowed_css', [ $this, 'dt_magic_url_base_allowed_css' ], 10, 1 );
        add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );
        add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 100 );
        add_filter( 'dt_can_update_permission', [ $this, 'can_update_permission_filter' ], 10, 3 );
    }

    // Ensure template fields remain editable
    public function can_update_permission_filter( $has_permission, $post_id, $post_type ) {
        return true;
    }

    public function wp_enqueue_scripts() {
        // Support Geolocation APIs
        if ( DT_Mapbox_API::get_key() ) {
            DT_Mapbox_API::load_mapbox_header_scripts();
            DT_Mapbox_API::load_mapbox_search_widget();
        }

        // Support Typeahead APIs
        $path     = '/dt-core/dependencies/typeahead/dist/';
        $path_js  = $path . 'jquery.typeahead.min.js';
        $path_css = $path . 'jquery.typeahead.min.css';
        wp_enqueue_script( 'jquery-typeahead', get_template_directory_uri() . $path_js, [ 'jquery' ], filemtime( get_template_directory() . $path_js ) );
        wp_enqueue_style( 'jquery-typeahead-css', get_template_directory_uri() . $path_css, [], filemtime( get_template_directory() . $path_css ) );

        wp_enqueue_style( 'material-font-icons-css', 'https://cdn.jsdelivr.net/npm/@mdi/font@6.6.96/css/materialdesignicons.min.css', [], '6.6.96' );
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        // @todo add or remove js files with this filter
        // example: $allowed_js[] = 'your-enqueue-handle';
        $allowed_js[] = 'mapbox-gl';
        $allowed_js[] = 'mapbox-cookie';
        $allowed_js[] = 'mapbox-search-widget';
        $allowed_js[] = 'jquery-typeahead';

        return $allowed_js;
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        // @todo add or remove js files with this filter
        // example: $allowed_css[] = 'your-enqueue-handle';
        $allowed_css[] = 'mapbox-gl-css';
        $allowed_css[] = 'jquery-typeahead-css';
        $allowed_css[] = 'material-font-icons-css';

        return $allowed_css;
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
        <div class="section-subheader"><?php

            // Support custom field label translations; or simply default to initial label entry.
            $label = ( ! empty( $field['translations'] ) && isset( $field['translations'][ determine_locale() ] ) ) ? $field['translations'][ determine_locale() ]['translation'] : $field['label'];

            echo esc_html( $label ); ?></div>
        <input id="<?php echo esc_html( $field['id'] ); ?>" type="text" class="text-input" value="">
        <?php
    }

    private function adjust_template_title_translation( $title, $title_translations ) {
        return ( ! empty( $title_translations ) && isset( $title_translations[ determine_locale() ] ) ) ? $title_translations[ determine_locale() ]['translation'] : $title;
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
    }

    /**
     * Writes javascript to the header
     *
     * @see DT_Magic_Url_Base()->header_javascript() for default state
     * @todo remove if not needed
     */
    public function header_javascript() {
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
                'post'         => $this->post,
                'translations' => [
                    'regions_of_focus' => __( 'Regions of Focus', 'disciple_tools' ),
                    'all_locations'    => __( 'All Locations', 'disciple_tools' )
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

                                // If available, load previous post record locations
                                let typeahead = window.Typeahead[typeahead_field_input];
                                let post_locations = jsObject['post'][field_id];
                                if ((post_locations !== undefined) && typeahead) {
                                    jQuery.each(post_locations, function (idx, location) {
                                        typeahead.addMultiselectItemLayout({
                                            ID: location['id'],
                                            name: window.lodash.escape(location['label'])
                                        });
                                    });
                                }

                                break;

                            case 'multi_select':

                                /**
                                 * Handle Selections
                                 */

                                jQuery(tr).find('.dt_multi_select').on("click", function (evt) {
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

                                let date_config = {
                                    singleDatePicker: true,
                                    timePicker: true,
                                    locale: {
                                        format: 'MMMM D, YYYY'
                                    }
                                };
                                let post_date = jsObject['post'][field_id];
                                if (post_date !== undefined) {
                                    date_config['startDate'] = moment.unix(post_date['timestamp']);
                                }

                                jQuery(tr).find('#' + field_id).daterangepicker(date_config, function (start, end, label) {
                                    if (start) {
                                        field_meta.val(start.unix());
                                    }
                                });

                                // If post timestamp available, set default hidden meta field value
                                if (post_date !== undefined) {
                                    field_meta.val(post_date['timestamp']);
                                }

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

                            case 'tags':

                                /**
                                 * Activate
                                 */

                                // Hide new button and default to single entry
                                jQuery(tr).find('.create-new-tag').hide();

                                let typeahead_tags_field_input = '.js-typeahead-' + field_id;
                                if (!window.Typeahead[typeahead_tags_field_input]) {
                                    jQuery(tr).find(typeahead_tags_field_input).typeahead({
                                        input: typeahead_tags_field_input,
                                        minLength: 0,
                                        maxItem: 20,
                                        searchOnFocus: true,
                                        source: {
                                            tags: {
                                                display: ["name"],
                                                ajax: {
                                                    url: jsObject['root'] + `dt-posts/v2/${jsObject['post']['post_type']}/multi-select-values`,
                                                    data: {
                                                        s: "{{query}}",
                                                        field: field_id
                                                    },
                                                    beforeSend: function (xhr) {
                                                        xhr.setRequestHeader('X-WP-Nonce', jsObject['nonce']);
                                                    },
                                                    callback: {
                                                        done: function (data) {
                                                            return (data || []).map(tag => {
                                                                return {name: tag}
                                                            })
                                                        }
                                                    }
                                                }
                                            }
                                        },
                                        display: "name",
                                        templateValue: "{{name}}",
                                        emptyTemplate: function (query) {
                                            const {addNewTagText, tagExistsText} = this.node[0].dataset
                                            if (this.comparedItems.includes(query)) {
                                                return tagExistsText.replace('%s', query)
                                            }
                                            const liItem = jQuery('<li>')
                                            const button = jQuery('<button>', {
                                                class: "button primary",
                                                text: addNewTagText.replace('%s', query),
                                            })
                                            const tag = this.query
                                            button.on("click", function () {
                                                window.Typeahead[typeahead_tags_field_input].addMultiselectItemLayout({name: tag});
                                            })
                                            liItem.append(button);
                                            return liItem;
                                        },
                                        dynamic: true,
                                        multiselect: {
                                            matchOn: ["name"],
                                            data: function () {
                                                return (jsObject['post'][field_id] || []).map(t => {
                                                    return {name: t}
                                                })
                                            },
                                            callback: {
                                                onCancel: function (node, item, event) {
                                                    // Keep a record of deleted tags
                                                    let deleted_items = (field_meta.val()) ? JSON.parse(field_meta.val()) : [];
                                                    deleted_items.push(item);
                                                    field_meta.val(JSON.stringify(deleted_items));
                                                }
                                            },
                                            href: function (item) {
                                            },
                                        },
                                        callback: {
                                            onClick: function (node, a, item, event) {
                                                event.preventDefault();
                                                this.addMultiselectItemLayout({name: item.name});
                                            },
                                            onResult: function (node, query, result, resultCount) {
                                                let text = TYPEAHEADS.typeaheadHelpText(resultCount, query, result)
                                                jQuery(tr).find(`#${field_id}-result-container`).html(text);
                                            },
                                            onHideLayout: function () {
                                                jQuery(tr).find(`#${field_id}-result-container`).html("");
                                            },
                                            onShowLayout() {
                                            }
                                        }
                                    });
                                }

                                /**
                                 * Load
                                 */

                                    // If available, load previous post record tags
                                let typeahead_tags = window.Typeahead[typeahead_tags_field_input];
                                let post_tags = jsObject['post'][field_id];
                                if ((post_tags !== undefined) && typeahead_tags) {
                                    jQuery.each(post_tags, function (idx, tag) {
                                        typeahead_tags.addMultiselectItemLayout({
                                            name: window.lodash.escape(tag)
                                        });
                                    });
                                }

                                break;
                        }
                    }
                });
            };
            window.activate_field_controls();

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

                                case 'tags':
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
                                value: jQuery(tr).find(selector).val() ? jQuery(tr).find('.section-subheader').html() + ': ' + jQuery(tr).find(selector).val() : ''
                            });
                        }
                    });

                    // Submit data for post update
                    jQuery('#content_submit_but').prop('disabled', true);

                    jQuery.ajax({
                        type: "POST",
                        data: JSON.stringify(payload),
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
        $has_title = ! empty( $this->template ) && ( isset( $this->template['title'] ) && ! empty( $this->template['title'] ) );
        ?>
        <div id="custom-style"></div>
        <div id="wrapper">
            <div class="grid-x">
                <div class="cell center">
                    <h2 id="title">
                        <b>
                            <?php echo esc_html( $has_title ? $this->adjust_template_title_translation( $this->template['title'], $this->template['title_translations'] ) : '' ); ?>
                        </b>
                    </h2>
                </div>
            </div>
            <?php
            if ( $has_title ) {
                ?>
                <hr/>
                <?php
            }
            ?>
            <div id="content">

                <!-- TEMPLATE MESSAGE -->
                <p id="template_msg">
                    <?php echo nl2br( esc_html( ! empty( $this->template ) && isset( $this->template['message'] ) ? $this->template['message'] : '' ) ); ?>
                </p>

                <!-- ERROR MESSAGES -->
                <span id="error" style="color: red;"></span>
                <br>

                <h3>
                    <span id="contact_name" style="font-weight: bold">
                        <?php echo esc_html( ! empty( $this->post ) ? $this->post['name'] : '---' ); ?>
                    </span>
                </h3>
                <hr>
                <div class="grid-x" id="form-content">
                    <input id="post_id" type="hidden"
                           value="<?php echo esc_html( ! empty( $this->post ) ? $this->post['ID'] : '' ); ?>"/>
                    <input id="post_type" type="hidden"
                           value="<?php echo esc_html( ! empty( $this->post ) ? $this->post['post_type'] : '' ); ?>"/>
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

                        $this->post_field_settings = DT_Posts::get_post_field_settings( $this->post['post_type'], false );
                        if ( ! empty( $this->post ) && ! empty( $this->post_field_settings ) && ! empty( $this->template ) ) {

                            // Display selected fields
                            foreach ( $this->template['fields'] ?? [] as $field ) {
                                if ( $field['enabled'] && $this->is_link_obj_field_enabled( $field['id'] ) ) {

                                    $post_field_type = '';
                                    if ( $field['type'] === 'dt' && isset( $this->post_field_settings[$field['id']]['type'] ) ){
                                        $post_field_type = $this->post_field_settings[ $field['id'] ]['type'];
                                    }
                                    if ( empty( $post_field_type ) ){
                                        continue;
                                    }

                                    // Generate hidden values to assist downstream processing
                                    $hidden_values_html = '<input id="form_content_table_field_id" type="hidden" value="' . $field['id'] . '">';
                                    $hidden_values_html .= '<input id="form_content_table_field_type" type="hidden" value="' . $post_field_type . '">';
                                    $hidden_values_html .= '<input id="form_content_table_field_template_type" type="hidden" value="' . $field['type'] . '">';
                                    $hidden_values_html .= '<input id="form_content_table_field_meta" type="hidden" value="">';

                                    // Render field accordingly, based on template field type!
                                    switch ( $field['type'] ) {
                                        case 'dt':

                                            // Capture rendered field html
                                            ob_start();
                                            render_field_for_display( $field['id'], $this->post_field_settings, $this->post, true );
                                            $rendered_field_html = ob_get_contents();
                                            ob_end_clean();

                                            // Only display if valid html content has been generated
                                            if ( ! empty( $rendered_field_html ) ) {
                                                ?>
                                                <tr>
                                                    <?php
                                                    // phpcs:disable
                                                    echo $hidden_values_html;
                                                    // phpcs:enable
                                                    ?>
                                                    <td>
                                                        <?php
                                                        // phpcs:disable
                                                        echo $rendered_field_html;
                                                        // phpcs:enable
                                                        ?>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                            break;
                                        case 'custom':
                                            ?>
                                            <tr>
                                                <?php
                                                // phpcs:disable
                                                echo $hidden_values_html;
                                                // phpcs:enable
                                                ?>
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
                                                <?php echo esc_html( $comment['comment_author'] . ' @ ' . $comment['comment_date'] ); ?>
                                            </div>
                                            <?php echo esc_html( $comment['comment_content'] ); ?>
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
                    <?php esc_html_e( "Submit Update", 'disciple_tools' ) ?>
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
            $namespace, '/' . $this->type . '/update', [
                [
                    'methods'             => "POST",
                    'callback'            => [ $this, 'update_record' ],
                    'permission_callback' => function ( WP_REST_Request $request ) {
                        $magic = new DT_Magic_URL( $this->root );

                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
    }

    public function update_record( WP_REST_Request $request ) {
        $params = $request->get_params();
        if ( ! isset( $params['post_id'], $params['post_type'], $params['parts'], $params['action'], $params['fields'] ) ) {
            return new WP_Error( __METHOD__, "Missing core parameters", [ 'status' => 400 ] );
        }

        // Sanitize and fetch user id
        $params = dt_recursive_sanitize_array( $params );

        // Update logged-in user state, if required
        if ( ! is_user_logged_in() ) {
            $this->update_user_logged_in_state();
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
                    if ( $field['changed'] ) {
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
                        if ( $option['delete'] ) {
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

                case 'tags':
                    $tags = [];
                    foreach ( $field['value'] ?? [] as $tag ) {
                        $entry          = [];
                        $entry['value'] = $tag['name'];
                        $tags[]         = $entry;
                    }

                    // Capture any incoming deletions
                    foreach ( $field['deletions'] ?? [] as $tag ) {
                        $entry           = [];
                        $entry['value']  = $tag['name'];
                        $entry['delete'] = true;
                        $tags[]          = $entry;
                    }

                    // Package and append to global updates
                    if ( ! empty( $tags ) ) {
                        $updates[ $field['id'] ] = [
                            'values' => $tags
                        ];
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

    public function update_user_logged_in_state() {
        wp_set_current_user( 0 );
        $current_user = wp_get_current_user();
        $current_user->add_cap( "magic_link" );
        $current_user->display_name = __( 'Smart Link Submission', 'disciple_tools' );
    }
}
