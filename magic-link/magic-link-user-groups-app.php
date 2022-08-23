<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.

function assoc_to_array( $options) {
    $keys = array_keys( $options );
    return array_map(function ( $key) use ( $options) {
        $options[$key]['id'] = $key;
        return $options[$key];
    }, $keys);
}

/**
 * Class Disciple_Tools_Magic_Links_Magic_User_Groups_App
 */
class Disciple_Tools_Magic_Links_Magic_User_Groups_App extends DT_Magic_Url_Base {

    public $page_title = 'User Group Updates';
    public $page_description = 'An update summary of assigned groups.';
    public $root = "smart_links"; // @todo define the root of the url {yoursite}/root/type/key/action
    public $type = 'user_groups_updates'; // @todo define the type
    public $post_type = 'user';
    private $post_field_settings = null;
    private $meta_key = '';

    private static $_instance = null;
    public $meta = []; // Allows for instance specific data.
    public $translatable = [
        'query',
        'user',
        'group'
    ]; // Order of translatable flags to be checked. Translate on first hit..!

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // End instance()

    public function __construct() {
        /**
         * As incoming requests could be for either valid wp users or contact
         * post records, ensure to adjust the $post_type accordingly; so as to
         * fall in line with extended class functionality!
         */
        $this->adjust_global_values_by_incoming_sys_type( $this->fetch_incoming_link_param( 'type' ) );

        $fields[] = [
            'id'    => 'comments',
            'label' => __( 'Comments', 'disciple_tools' ) // Special Case!
        ];

        /**
         * Specify metadata structure, specific to the processing of current
         * magic link type.
         *
         * - meta:              Magic link plugin related data.
         *      - app_type:     Flag indicating type to be processed by magic link plugin.
         *      - post_type     Magic link type post type.
         *      - contacts_only:    Boolean flag indicating how magic link type user assignments are to be handled within magic link plugin.
         *                          If True, lookup field to be provided within plugin for contacts only searching.
         *                          If false, Dropdown option to be provided for user, team or group selection.
         *      - fields:       List of fields to be displayed within magic link frontend form.
         *      - field_refreshes:  Support field label updating.
         */
        $this->meta = [
            'app_type'       => 'magic_link',
            'post_type'      => $this->post_type,
            'contacts_only'  => false,
            'fields'         => $fields,
            'fields_refresh' => [
                'enabled'    => true,
                'load_all'   => true,
                'post_type'  => 'groups',
                'ignore_ids' => [ 'comments' ]
            ]
        ];

        /**
         * Once adjustments have been made, proceed with parent instantiation!
         */
        $this->meta_key = $this->root . '_' . $this->type . '_magic_key';
        parent::__construct();

        /**
         * user_app and module section
         */
        add_filter( 'dt_settings_apps_list', [ $this, 'dt_settings_apps_list' ], 10, 1 );
        add_action( 'rest_api_init', [ $this, 'add_endpoints' ] );

        /**
         * tests if other URL
         */
        $url = dt_get_url_path();
        if ( strpos( $url, $this->root . '/' . $this->type ) === false ) {
            return;
        }
        /**
         * tests magic link parts are registered and have valid elements
         */
        if ( ! $this->check_parts_match() ) {
            return;
        }

        // load if valid url
        add_action( 'dt_blank_body', [ $this, 'body' ] );
        add_filter( 'dt_magic_url_base_allowed_css', [ $this, 'dt_magic_url_base_allowed_css' ], 10, 1 );
        add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );
        add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 100 );
        add_filter( 'script_loader_tag', [ $this, 'add_type_attribute' ], 10, 2 );
    }

    private function set_script_type_module( $script ) {
        $re = '/type=[\'"](.*?)[\'"]/m';

        preg_match_all( $re, $script, $matches, PREG_SET_ORDER, 0 );

        if ( count( $matches ) > 0 ) {
            $script = str_replace( $matches[0][0], 'type="module"', $script );
        } else {
            $script = str_replace( '<script ', '<script type="module" ', $script );
        }

        return $script;
    }

    public function add_type_attribute( $tag, $handle) {
        if ( !str_contains( $handle, 'dtwc' ) ) {
            return $tag;
        }

        // if script is a web component, we need to set the type to 'module' on the script tag
        return $this->set_script_type_module( $tag );
    }

    public function adjust_global_values_by_incoming_sys_type( $type ) {
        if ( ! empty( $type ) ) {
            switch ( $type ) {
                case 'wp_user':
                    $this->post_type = 'user';
                    break;
                case 'post':
                    $this->post_type = 'contacts';
                    break;
            }
        }
    }

    private function enqueue_web_component( $name, $rel_path ) {
        $path = '../assets/dtwc/dist/' . $rel_path;
        $css_path = '../assets/dtwc/src/styles/light.css';

        wp_enqueue_style( 'dtwc-light-css', plugin_dir_url( __FILE__ ) . $css_path, null, filemtime( plugin_dir_path( __FILE__ ) . $css_path ) );
        wp_enqueue_script( 'dtwc-' . $name, plugin_dir_url( __FILE__ ) . $path, null, filemtime( plugin_dir_path( __FILE__ ) . $path ) );
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

        $this->enqueue_web_component( 'form-components', 'form/index.js' );
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        // @todo add or remove js files with this filter
        // example: $allowed_js[] = 'your-enqueue-handle';
        $allowed_js[] = 'mapbox-gl';
        $allowed_js[] = 'mapbox-cookie';
        $allowed_js[] = 'mapbox-search-widget';
        $allowed_js[] = 'jquery-typeahead';
        $allowed_js[] = 'dtwc-form-components';

        return $allowed_js;
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        // @todo add or remove js files with this filter
        // example: $allowed_css[] = 'your-enqueue-handle';
        $allowed_css[] = 'mapbox-gl-css';
        $allowed_css[] = 'jquery-typeahead-css';
        $allowed_css[] = 'material-font-icons-css';
        $allowed_css[] = 'dtwc-light-css';

        return $allowed_css;
    }

    /**
     * Builds magic link type settings payload:
     * - key:               Unique magic link type key; which is usually composed of root, type and _magic_key suffix.
     * - url_base:          URL path information to map with parent magic link type.
     * - label:             Magic link type name.
     * - description:       Magic link type description.
     * - settings_display:  Boolean flag which determines if magic link type is to be listed within frontend user profile settings.
     *
     * @param $apps_list
     *
     * @return mixed
     */
    public function dt_settings_apps_list( $apps_list ) {
        $apps_list[ $this->meta_key ] = [
            'key'              => $this->meta_key,
            'url_base'         => $this->root . '/' . $this->type,
            'label'            => $this->page_title,
            'description'      => $this->page_description,
            'settings_display' => true
        ];

        return $apps_list;
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
        ?>
        <?php
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
                'map_key'        => DT_Mapbox_API::get_key(),
                'root'           => esc_url_raw( rest_url() ),
                'nonce'          => wp_create_nonce( 'wp_rest' ),
                'parts'          => $this->parts,
                'lang'           => $this->fetch_incoming_link_param( 'lang' ),
                'field_settings' => DT_Posts::get_post_field_settings( 'groups' ),
                'link_obj_id'    => Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_obj( $this->fetch_incoming_link_param( 'id' ) ),
                'sys_type'       => $this->fetch_incoming_link_param( 'type' ),
                'translations'   => [
                    'add' => __( 'Add Magic', 'disciple-tools-bulk-magic-link-sender' ),
                ]
            ] ) ?>][0];

            /**
             * Fetch assigned groups
             */
            window.get_magic = () => {
                jQuery.ajax({
                    type: "GET",
                    data: {
                        action: 'get',
                        parts: jsObject.parts,
                        lang: jsObject.lang,
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
             * Display returned list of assigned groups
             */
            window.load_magic = (data) => {
                let table = jQuery('.api-content-table');
                let total = jQuery('#total');

                // Remove any previous entries
                table.find('tbody').empty()

                // Set total hits count
                total.html(data['total'] ? data['total'] : '0');

                // Iterate over returned posts
                if (data['posts']) {
                    data['posts'].forEach(v => {

                        let html = `<tr onclick="get_assigned_group_details('${window.lodash.escape(v.id)}', '${window.lodash.escape(v.name)}');">
                                <td>${window.lodash.escape(v.name)}</td>
                                <td>${window.lodash.escape(v.group_status.label)}</td>
                                <td class="last-update"><?php esc_html_e( "Updated", 'disciple_tools' ) ?>: ${window.lodash.escape(v.last_modified.formatted)}</td>
                            </tr>`;

                        table.find('tbody').append(html);

                    });
                }
            };

            /**
             * Fetch requested group details
             */
            window.get_group = (post_id) => {
                let comment_count = 2;

                jQuery('.form-content-table').fadeOut('fast', function () {

                    // Dispatch request call
                    jQuery.ajax({
                        type: "GET",
                        data: {
                            action: 'get',
                            parts: jsObject.parts,
                            lang: jsObject.lang,
                            sys_type: jsObject.sys_type,
                            post_id: post_id,
                            comment_count: comment_count,
                            ts: moment().unix() // Alter url shape, so as to force cache refresh!
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
                        if (data['success'] && data['form_html']) {
                            if ( data['post'] ) {
                                jsObject.post = data['post'];
                            }

                            // Inject form HTML from response
                            jQuery('#form-content').replaceWith(data['form_html']);

                            // Display submit button
                            jQuery('#content_submit_but').fadeIn('fast');

                            // Display updated post fields
                            jQuery('.form-content-table').fadeIn('fast', function () {
                                window.activate_field_controls();
                            });
                        } else {
                            // TODO: Error Msg...!
                        }

                    }).fail(function (e) {
                        console.log(e);
                        jQuery('#error').html(e);
                    });
                });
            };

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
            }

            /**
             * Handle fetch request for group details
             */
            window.get_assigned_group_details = (post_id, post_name) => {
                let group_name = jQuery('#group_name');

                // Update group name
                group_name.html(post_name);

                // Fetch requested group details
                window.get_group(post_id);
            };

            /**
             * Activate various field controls.
             */
            window.activate_field_controls = () => {
                jQuery('.form-content-table > tbody > tr').each(function (idx, tr) {

                    let field_id = jQuery(tr).find('.form_content_table_field_id').val();
                    let field_type = jQuery(tr).find('.form_content_table_field_type').val();
                    let field_meta = jQuery(tr).find('.form_content_table_field_meta');

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
                });

                // General Typeaheads (from theme/dt-assets/js/new-record.js
                $(".typeahead__query input").each((key, el)=>{
                    let field_key = $(el).data('field')
                    let post_type = $(el).data('post_type')
                    let field_type = $(el).data('field_type')
                    let field_meta = $(el).data('field_meta')
                    typeaheadTotals = {}

                    // Determine field class name to be used.
                    let field_class = `.js-typeahead-${field_key}`;

                    if (!window.Typeahead[field_class]) {

                        if ( field_type === "connection"){

                            $.typeahead({
                                input: field_class,
                                minLength: 0,
                                accent: true,
                                searchOnFocus: true,
                                maxItem: 20,
                                template: window.TYPEAHEADS.contactListRowTemplate,
                                source: TYPEAHEADS.typeaheadPostsSource(post_type, field_key),
                                display: ["name", "label"],
                                templateValue: function() {
                                    if (this.items[this.items.length - 1].label) {
                                        return "{{label}}"
                                    } else {
                                        return "{{name}}"
                                    }
                                },
                                dynamic: true,
                                multiselect: {
                                    matchOn: ["ID"],
                                    data: function () {
                                        return (jsObject.post[field_key] || [] ).map(g=>{
                                            return {ID:g.ID, name:g.post_title, label: g.label}
                                        })
                                    },
                                    callback: {
                                        onCancel: function (node, item) {
                                            // Keep a record of deleted options
                                            let deleted_items = field_meta ? JSON.parse(field_meta) : [];
                                            deleted_items.push(item);
                                            $(el).data('field_meta', JSON.stringify(deleted_items));
                                        }
                                    }
                                },
                                callback: {
                                    onResult: function (node, query, result, resultCount) {
                                        let text = TYPEAHEADS.typeaheadHelpText(resultCount, query, result)
                                        $(`#${field_key}-result-container`).html(text);
                                    },
                                    onHideLayout: function () {
                                        $(`#${field_key}-result-container`).html("");
                                    },
                                    onClick: function (node, a, item, event ) {
                                        /*if (!new_post[field_key]) {
                                            new_post[field_key] = {values: []}
                                        }
                                        new_post[field_key].values.push({value: item.ID})*/

                                        //get list from opening again
                                        this.addMultiselectItemLayout(item)
                                        event.preventDefault()
                                        this.hideLayout();
                                        this.resetInput();
                                    }
                                }
                            });
                        } else if ( field_type === "location" ){
                            $.typeahead({
                                input: field_class,
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
                                                    return window.lodash.get(window.Typeahead[field_class].filters.dropdown, 'value', 'all')
                                                }
                                            },
                                            beforeSend: function (xhr) {
                                                xhr.setRequestHeader('X-WP-Nonce', window.wpApiShare.nonce);
                                            },
                                            callback: {
                                                done: function (data) {
                                                    return data.location_grid
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
                                        return (jsObject.post[field_key] || []).map(g => {
                                            return {ID: g.id, name: g.label}
                                        })
                                    },
                                    callback: {
                                        onCancel: function (node, item) {
                                            // Keep a record of deleted options
                                            let deleted_items = field_meta ? JSON.parse(field_meta) : [];
                                            deleted_items.push(item);
                                            $(el).data('field_meta', JSON.stringify(deleted_items));
                                        }
                                    }
                                },
                                callback: {
                                    onClick: function(node, a, item, event){
                                    },
                                    onReady(){
                                        this.filters.dropdown = {key: "group", value: "focus", template: window.lodash.escape(jsObject['translations']['regions_of_focus'])}
                                        this.container
                                            .removeClass("filter")
                                            .find("." + this.options.selector.filterButton)
                                            .html(window.lodash.escape(jsObject['translations']['regions_of_focus']));
                                    },
                                    onResult: function (node, query, result, resultCount) {
                                        resultCount = typeaheadTotals.location_grid
                                        let text = TYPEAHEADS.typeaheadHelpText(resultCount, query, result)
                                        $('#location_grid-result-container').html(text);
                                    },
                                    onHideLayout: function () {
                                        $('#location_grid-result-container').html("");
                                    }
                                }
                            });
                        } else if ( field_type === "user_select" ){
                            //todo: move init from above to here to mimic new-record.js
                        }
                    }
                });

                let connection_type = null
                //new record off a typeahead
                $('.create-new-record').on('click', function(){
                    connection_type = $(this).data('connection-key');
                    $('#create-record-modal').foundation('open');
                    $('.js-create-record .error-text').empty();
                    $(".js-create-record-button").attr("disabled", false).removeClass("alert")
                    $(".reveal-after-record-create").hide()
                    $(".hide-after-record-create").show()
                    $(".js-create-record input[name=title]").val('')
                    //create new record
                })
                $(".js-create-record").on("submit", function(e) {
                    e.preventDefault();
                    $(".js-create-record-button").attr("disabled", true).addClass("loading");
                    let title = $(".js-create-record input[name=title]").val()
                    if ( !connection_type){
                        $(".js-create-record .error-text").text(
                            "Something went wrong. Please refresh and try again"
                        );
                        return;
                    }
                    let update_field = connection_type;
                    API.create_post( jsObject.field_settings[update_field].post_type, {
                        title,
                        additional_meta: {
                            created_from: jsObject.parts.post_id,
                            add_connection: connection_type
                        }
                    }).then((newRecord)=>{
                        $(".js-create-record-button").attr("disabled", false).removeClass("loading");
                        $(".reveal-after-record-create").show()
                        $("#new-record-link").html(`<a href="${window.lodash.escape( newRecord.permalink )}">${window.lodash.escape( title )}</a>`)
                        $(".hide-after-record-create").hide()
                        $('#go-to-record').attr('href', window.lodash.escape( newRecord.permalink ));
                        // $( document ).trigger( "dt-post-connection-created", [ post, update_field ] );
                        if ( Typeahead[`.js-typeahead-${connection_type}`] ){
                            Typeahead[`.js-typeahead-${connection_type}`].addMultiselectItemLayout({ID:newRecord.ID.toString(), name:title})
                            // masonGrid.masonry('layout')
                        }
                    })
                        .catch(function(error) {
                            $(".js-create-record-button").removeClass("loading").addClass("alert");
                            $(".js-create-record .error-text").text(
                                window.lodash.get( error, "responseJSON.message", "Something went wrong. Please refresh and try again" )
                            );
                            console.error(error);
                        });
                })
            };

            /**
             * Adjust visuals, based on incoming sys_type
             */
            let assigned_groups_div = jQuery('#assigned_groups_div');
            switch (jsObject.sys_type) {
                case 'post':
                    // Bypass groups list and directly fetch requested group details
                    assigned_groups_div.fadeOut('fast');
                    window.get_group(jsObject.parts.post_id);
                    break;
                default: // wp_user
                    // Fetch assigned groups for incoming user
                    assigned_groups_div.fadeIn('fast');
                    window.get_magic();
                    break;
            }

            /**
             * Submit group details
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
                        action: 'get',
                        parts: jsObject.parts,
                        sys_type: jsObject.sys_type,
                        post_id: id,
                        post_type: post_type,
                        fields: [],
                    }

                    // Iterate over form fields, capturing values accordingly.
                    jQuery('.form-content-table > tbody > tr').each(function (idx, tr) {

                        let field_id = jQuery(tr).find('.form_content_table_field_id').val();
                        let field_type = jQuery(tr).find('.form_content_table_field_type').val();
                        let field_meta = jQuery(tr).find('.form_content_table_field_meta');
                        let field_meta_typeahead = jQuery(tr).find('.typeahead__query input').data('field_meta');

                        let selector = '#' + field_id;
                        switch (field_type) {

                            case 'number':
                            case 'textarea':
                            case 'text':
                            case 'key_select':
                            case 'date':
                                payload['fields'].push({
                                    id: field_id,
                                    type: field_type,
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
                                payload['fields'].push({
                                    id: field_id,
                                    type: field_type,
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
                                payload['fields'].push({
                                    id: field_id,
                                    type: field_type,
                                    value: options
                                });
                                break;

                            case 'boolean':
                                let initial_val = JSON.parse(jQuery(tr).find('#field_initial_state_' + field_id).val());
                                let current_val = jQuery(tr).find(selector).prop('checked');

                                payload['fields'].push({
                                    id: field_id,
                                    type: field_type,
                                    value: current_val,
                                    changed: (initial_val !== current_val)
                                });
                                break;


                            case 'tags':
                            case 'location':
                            case 'connection':
                                let typeahead = window.Typeahead['.js-typeahead-' + field_id];
                                if (typeahead) {
                                    payload['fields'].push({
                                        id: field_id,
                                        type: field_type,
                                        value: typeahead.items,
                                        deletions: field_meta_typeahead ? JSON.parse(field_meta_typeahead) : []
                                    });
                                }
                                break;

                            case 'location_meta':
                                payload['fields'].push({
                                    id: field_id,
                                    type: field_type,
                                    value: (window.selected_location_grid_meta !== undefined) ? window.selected_location_grid_meta : '',
                                    deletions: field_meta.val() ? JSON.parse(field_meta.val()) : []
                                });
                                break;

                            default:
                                break;
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
        // Revert back to dt translations
        $this->hard_switch_to_default_dt_text_domain();
        ?>
        <div id="custom-style"></div>
        <div id="wrapper">
            <div class="grid-x">
                <div class="cell center">
                    <h2 id="title"><b><?php esc_html_e( 'Updates Needed', 'disciple_tools' ) ?></b></h2>
                </div>
            </div>
            <hr>
            <div id="content">
                <div id="assigned_groups_div" style="display: none;">
                    <h3><?php esc_html_e( "Groups", 'disciple_tools' ) ?> [ <span id="total">0</span> ]</h3>
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

                <h3><span id="group_name"></span>
                </h3>
                <hr>
                <div class="grid-x" id="form-content">
                    <input id="post_id" type="hidden"/>
                    <?php
                    $field_settings = DT_Posts::get_post_field_settings( 'groups', false );
                    ?>
                    <table style="display: none;" class="form-content-table">
                        <tbody>
                        <tr id="form_content_name_tr">
                            <td style="vertical-align: top;">
                                <b><?php echo esc_attr( $field_settings['name']['name'] ); ?></b></td>
                            <td id="form_content_name_td"></td>
                        </tr>
                        <tr id="form_content_comments_tr">
                            <td style="vertical-align: top;">
                                <b><?php esc_html_e( "Comments", 'disciple_tools' ) ?></b>
                            </td>
                            <td id="form_content_comments_td"></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <br>

                <!-- SUBMIT UPDATES -->
                <button id="content_submit_but" style="display: none; min-width: 100%;" class="button select-button">
                    <?php esc_html_e( "Submit Update", 'disciple_tools' ) ?>
                </button>
            </div>
        </div>
        <div class="reveal" id="create-record-modal" data-reveal data-reset-on-close>

            <h3><?php esc_html_e( 'Create Record', 'disciple_tools' )?></h3>

            <form class="js-create-record hide-after-record-create">
                <label for="title">
                    <?php esc_html_e( "Name", "disciple_tools" ); ?>
                </label>
                <input name="title" type="text" placeholder="<?php echo esc_html__( "Name", 'disciple_tools' ); ?>" required aria-describedby="name-help-text">

                <div>
                    <button class="button loader js-create-record-button" type="submit"><?php echo esc_html__( "Create Record", 'disciple_tools' ); ?></button>
                    <button class="button button-cancel clear hide-after-record-create" data-close aria-label="Close reveal" type="button">
                        <?php echo esc_html__( 'Cancel', 'disciple_tools' )?>
                    </button>
                </div>
                <p style="color: red" class="error-text"></p>
            </form>

            <p class="reveal-after-record-create" style="display: none"><?php esc_html_e( "Record Created", 'disciple_tools' ) ?>: <span id="new-record-link"></span></p>


            <hr class="reveal-after-group-create" style="display: none">
            <div class="grid-x">
                <a class="button reveal-after-record-create" id="go-to-record" style="display: none">
                    <?php esc_html_e( 'Edit New Record', 'disciple_tools' )?>
                </a>
                <button class="button reveal-after-record-create button-cancel clear" data-close type="button" id="create-record-return" style="display: none">
                    <?php
                    echo esc_html( sprintf( _x( "Back to %s", "back to record", 'disciple_tools' ), DT_Posts::get_label_for_post_type( get_post_type( get_the_ID() ), true ) ) );
                    ?>
                </button>
                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Copied from theme to replace web component rendering. Can be removed if/when web-components are fully adopted in the theme.
     * @param $field_key
     * @param $fields
     * @param $post
     * @param bool $show_extra_controls
     * @param bool $show_hidden
     * @param string $field_id_prefix
     */
    public function render_field_as_web_component( $field_key, $fields, $post, $show_extra_controls = false, $show_hidden = false, $field_id_prefix = '' ) {
        $disabled = 'disabled';
        if ( isset( $post['post_type'] ) && isset( $post['ID'] ) ) {
            $can_update = DT_Posts::can_update( $post['post_type'], $post['ID'] );
        } else {
            $can_update = true;
        }
        if ( $can_update || isset( $post["assigned_to"]["id"] ) && $post["assigned_to"]["id"] == get_current_user_id() ) {
            $disabled = '';
        }
        $required_tag = ( isset( $fields[$field_key]["required"] ) && $fields[$field_key]["required"] === true ) ? 'required' : '';
        $field_type = isset( $fields[$field_key]["type"] ) ? $fields[$field_key]["type"] : null;
        $is_private = isset( $fields[$field_key]["private"] ) && $fields[$field_key]["private"] === true;
        $display_field_id = $field_key;
        if ( !empty( $field_id_prefix ) ) {
            $display_field_id = $field_id_prefix . $field_key;
        }
        if ( isset( $fields[$field_key]["type"] ) && empty( $fields[$field_key]["custom_display"] ) && empty( $fields[$field_key]["hidden"] ) ) {
            $allowed_types = apply_filters( 'dt_render_field_for_display_allowed_types', [ 'key_select', 'multi_select', 'date', 'datetime', 'text', 'textarea', 'number', 'connection', 'location', 'location_meta', 'communication_channel', 'tags', 'user_select' ] );
            if ( !in_array( $field_type, $allowed_types ) ){
                return;
            }
            if ( !dt_field_enabled_for_record_type( $fields[$field_key], $post ) ){
                return;
            }


            ?>
            <?php
            $icon = null;
            if ( isset( $fields[$field_key]["icon"] ) && !empty( $fields[$field_key]["icon"] ) ) {
                $icon = $fields[$field_key]["icon"];
            }

            $icon_slot = '';
            if ( isset( $field['font-icon'] ) && !empty( $field['font-icon'] ) ) {
                $icon_slot = '<span slot="icon-start">
                    <i class="dt-icon ' . esc_html( $field['font-icon'] ) . '"></i>
                </span>';
            }

            $shared_attributes = '
                  id="' . esc_html( $display_field_id ) . '"
                  name="' . esc_html( $field_key ) .'"
                  label="' . esc_html( $fields[$field_key]["name"] ) . '"
                  icon="' . esc_html( $icon ) . '"
                  ' . esc_html( $required_tag ) . '
                  ' . esc_html( $disabled ) . '
                  ' . ($is_private ? 'private privateLabel="' . esc_html( _x("Private Field: Only I can see it\'s content", 'disciple_tools') ) : null) . '
            ';
            if ( $field_type === "key_select" ) :
                ?>
                <dt-single-select class="select-field"
                                  <?php echo $shared_attributes ?>
                                  value="<?php echo esc_attr( key_exists( $field_key, $post ) ? $post[$field_key]["key"] : null ) ?>"
                                  options="<?php echo esc_attr( json_encode( assoc_to_array( $fields[$field_key]["default"] ) ) ) ?>"
                              >
                    <?php echo $icon_slot; ?>
                </dt-single-select>

            <?php elseif ( $field_type === "text" ) :?>
                <dt-text
                    <?php echo $shared_attributes ?>
                    value="<?php echo esc_html( $post[$field_key] ?? "" ) ?>"
                >
                    <?php echo $icon_slot ?>
                </dt-text>
            <?php elseif ( $field_type === "textarea" ) :?>
                <dt-textarea
                    <?php echo $shared_attributes ?>
                    value="<?php echo esc_html( $post[$field_key] ?? "" ) ?>"
                >
                    <?php echo $icon_slot ?>
                </dt-textarea>
            <?php elseif ( $field_type === "number" ) :?>
                <dt-number
                    <?php echo $shared_attributes ?>
                    value="<?php echo esc_html( $post[$field_key] ?? "" ) ?>" <?php echo esc_html( $disabled ); ?>
                    <?php echo is_numeric( $fields[$field_key]["min_option"] ) ? 'min="' . esc_html( $fields[$field_key]["min_option"] ?? "" ) . '"' : '' ?>
                    <?php echo is_numeric( $fields[$field_key]["max_option"] ) ? 'max="' . esc_html( $fields[$field_key]["max_option"] ?? "" ) . '"' : '' ?>
                >
                    <?php echo $icon_slot ?>
                </dt-number>
            <?php elseif ( $field_type === "date" ) :?>
                <dt-date
                    <?php echo $shared_attributes ?>
                    timestamp="<?php echo esc_html( $post[$field_key]["timestamp"] ?? '' ) ?>"
                >
                    <?php echo $icon_slot ?>
                </dt-date>

            <?php endif;
        }
    }

    public function post_form( $post, $fields ) {

        $post_settings = DT_Posts::get_post_settings( 'groups' );
        $post_tiles = DT_Posts::get_post_tiles( 'groups' );
        $this->post_field_settings = $post_settings['fields'];

        $wc_types = [ 'key_select', 'text', 'textarea', 'number', 'date' ];
        if ( !empty( $fields ) && !empty( $this->post_field_settings ) ) {
            // Sort fields based on tile settings
            foreach ( $fields as &$field ) {
                $priority = 999;
                if ( !empty( $post_tiles ) && key_exists( $field['id'], $this->post_field_settings ) ) {
                    $field_setting = $this->post_field_settings[$field['id']];
                    if ( !empty( $field_setting['tile'] ) && key_exists( $field_setting['tile'], $post_tiles )) {
                        $tile = $post_tiles[$field_setting['tile']];
                        if ( !empty( $tile ) && isset( $tile['tile_priority'] ) && isset( $tile['order'] )) {
                            $field_order = array_search( $field['id'], $tile['order'] );
                            $priority = ( $tile['tile_priority'] * 10 ) + $field_order;
                        }
                    }
                }
                $field['priority'] = $priority;
            }
            unset( $field ); // https://stackoverflow.com/questions/7158741/why-php-iteration-by-reference-returns-a-duplicate-last-record

            usort( $fields, function( $a, $b) {
                return $a['priority'] - $b['priority'];
            });
        }
        ?>
        <div class="grid-x" id="form-content">
            <input id="post_id" type="hidden"
                   value="<?php echo esc_html( ! empty( $post ) ? $post['ID'] : '' ); ?>"/>
            <input id="post_type" type="hidden"
                   value="<?php echo esc_html( ! empty( $post ) ? $post['post_type'] : '' ); ?>"/>
            <?php
            // Revert back to dt translations
            $this->hard_switch_to_default_dt_text_domain();
            ?>
            <table style="<?php echo( ! empty( $post ) ? '' : 'display: none;' ) ?>" class="form-content-table">
                <tbody>
                <?php

                /**
                 * If a valid post is present, then display fields accordingly,
                 * based on hidden flags!
                 */

                $this->post_field_settings = DT_Posts::get_post_field_settings( $post['post_type'], false );
                if ( ! empty( $post ) && ! empty( $this->post_field_settings ) && ! empty( $fields ) ) {

                    $excluded_fields = [ 'comments' ];
                    $show_comments = false;

                    // Display selected fields
                    foreach ( $fields as $field ) {
                        $show_comments = $show_comments || ( $field['id'] === 'comments' && $field['enabled'] );
                        if ( $field['enabled'] && !in_array( $field['id'], $excluded_fields ) ) {

                            $post_field = $this->post_field_settings[ $field['id'] ];
                            $post_field_type = $post_field['type'];

                            // Generate hidden values to assist downstream processing
                            $hidden_values_html = '<input class="form_content_table_field_id" type="hidden" value="' . $field['id'] . '">';
                            $hidden_values_html .= '<input class="form_content_table_field_type" type="hidden" value="' . $post_field_type . '">';
                            $hidden_values_html .= '<input class="form_content_table_field_meta" type="hidden" value="">';

                            // Capture rendered field html
                            ob_start();
                            if (in_array( $post_field_type, $wc_types ) ) {
                                $this->render_field_as_web_component( $field['id'], $this->post_field_settings, $post, true );
                            } else {
                                render_field_for_display( $field['id'], $this->post_field_settings, $post, true );
                            }
                            $rendered_field_html = ob_get_clean();


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
                        }
                    }

                    // If requested, display recent comments
                    if ( $show_comments ) {
                        ?>
                        <tr>
                            <input class="form_content_table_field_id" type="hidden" value="comments">
                            <input class="form_content_table_field_type" type="hidden" value="textarea">
                            <input class="form_content_table_field_meta" type="hidden" value="">
                            <td>
                                <div class="section-subheader"><?php esc_html_e( "Comments", 'disciple_tools' ) ?></div>
                                <textarea id="comments"></textarea>
                            </td>
                        </tr>
                        <?php
                        $recent_comments = DT_Posts::get_post_comments( $post['post_type'], $post['ID'], false, 'all', [ 'number' => 2 ] );
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
            <pre style="display:none;"><code style="display: block;">
            <?php print_r( $post ); ?>
            </code></pre>
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
                         * Adjust global values accordingly, so as to accommodate both wp_user
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
                         * Adjust global values accordingly, so as to accommodate both wp_user
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
            $posts = DT_Posts::list_posts( 'groups', [
                'limit'  => 1000,
                'fields' => [
                    [
                        'assigned_to' => [ 'me' ]
                    ],
                ]
            ] );

            $this->determine_language_locale( $params["parts"] );

            // Revert to original user
            if ( ! empty( $original_user ) && isset( $original_user->ID ) ) {
                wp_set_current_user( $original_user->ID );
            }

            // Iterate and return valid posts
            if ( ! empty( $posts ) && isset( $posts['posts'], $posts['total'] ) ) {
                $data['total'] = $posts['total'];
                foreach ( $posts['posts'] ?? [] as $post ) {
                    $post['id'] = $post['ID'];
                    unset( $post['ID'] );
                    $data['posts'][] = (object) $post;
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
        $this->determine_language_locale( $params["parts"] );

        $link_obj = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_obj( $params["parts"]["instance_id"] );

        // Fetch corresponding groups post record
        $response = [];
        $post     = DT_Posts::get_post( 'groups', $params['post_id'], false );
        if ( ! empty( $post ) && ! is_wp_error( $post ) ) {
            // start output buffer to capture markup output
            ob_start();
            $this->post_form( $post, json_decode( json_encode( $link_obj->type_fields ), true ) );
            $response['form_html'] = ob_get_clean();

            $response['success']  = true;
            $response['post']     = $post;
            $response['comments'] = DT_Posts::get_post_comments( 'groups', $params['post_id'], false, 'all', [ 'number' => $params['comment_count'] ] );
        } else {
            $response['success'] = false;
        }

        return $response;
    }

    public function update_record( WP_REST_Request $request ) {
        $params = $request->get_params();
        if ( ! isset( $params['post_id'], $params['parts'], $params['action'], $params['sys_type'] ) ) {
            return new WP_Error( __METHOD__, "Missing core parameters", [ 'status' => 400 ] );
        }

        // Sanitize and fetch user id
        $params = dt_recursive_sanitize_array( $params );

        // Update logged-in user state if required accordingly, based on their sys_type
        if ( ! is_user_logged_in() ) {
            $this->update_user_logged_in_state( $params['sys_type'], $params["parts"]["post_id"] );
        }

        $updates = [];
        $comments = [];

        // First, capture and package incoming DT field values
        foreach ( $params['fields'] ?? [] as $field ) {
            // Comments are handled separately, so pull them out and handle later
            if ( $field['id'] == 'comments') {
                $comments[] = $field['value'];
                continue;
            }

            switch ( $field['type'] ) {
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
                case 'connection':
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
            if ( is_wp_error( $updated_post ) ) {
                dt_write_log( $updated_post );
            }
            return [
                'success' => false,
                'message' => 'Unable to update record details!'
            ];
        }

        // Add any available comments
        if ( !empty( $comments ) ) {
            foreach ( $comments as $comment ) {
                if ( !empty( $comment ) ) {
                    $updated_comment = DT_Posts::add_post_comment( $updated_post['post_type'], $updated_post['ID'], $comment, 'comment', [], false );
                    if (empty( $updated_comment ) || is_wp_error( $updated_comment )) {
                        return [
                            'success' => false,
                            'message' => 'Unable to add comment to record details!'
                        ];
                    }
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

Disciple_Tools_Magic_Links_Magic_User_Groups_App::instance();
