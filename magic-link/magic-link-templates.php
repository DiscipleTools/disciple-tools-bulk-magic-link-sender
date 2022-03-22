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
        self::load_templates();
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
                'post_fields'  => DT_Posts::get_post_field_settings( $this->template['post_type'] ),
                'translations' => [
                    'regions_of_focus' => __( 'Regions of Focus', 'disciple_tools' ),
                    'all_locations'    => __( 'All Locations', 'disciple_tools' )
                ],
                'images'       => [
                    'small-add.svg' => get_template_directory_uri() . '/dt-assets/images/small-add.svg'
                ]
            ] ) ?>][0]

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
             * Display fields associated with specified template; governed by
             * link obj.
             */

            let link_obj = jsObject['link_obj_id'];

            let form_content_table = jQuery('.form-content-table');
            form_content_table.fadeOut('fast', function () {

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
                form_content_table.fadeIn('fast');
            });

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
                    window.get_contact(jsObject.parts.post_id);
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
                    jQuery('.form-content-table-field').each(function (idx, field_td) {

                        let field_id = jQuery(field_td).find('#form_content_table_field_id').val();
                        let field_type = jQuery(field_td).find('#form_content_table_field_type').val();
                        let field_template_type = jQuery(field_td).find('#form_content_table_field_template_type').val();

                        if (window.is_field_enabled(field_id)) {

                            let selector = '#field_' + field_id;

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
                                            value: jQuery(field_td).find(selector).val()
                                        });
                                        break;

                                    case 'communication_channel':
                                        let values = [];
                                        jQuery(field_td).find('.input-group').each(function () {
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
                                            deleted: JSON.parse(jQuery(field_td).find('#field_deleted_keys').val())
                                        });
                                        break;

                                    case 'multi_select':
                                        let options = [];
                                        jQuery(field_td).find('button').each(function () {
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
                                        let initial_val = JSON.parse(jQuery(field_td).find('#field_initial_state_' + field_id).val());
                                        let current_val = jQuery(field_td).find(selector).prop('checked');

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
                                            value: jQuery(field_td).find('#field_date_ts_' + field_id).val()
                                        });
                                        break;

                                    case 'location':
                                        let typeahead = window.Typeahead[selector];
                                        if (typeahead) {
                                            payload['fields']['dt'].push({
                                                id: field_id,
                                                dt_type: field_type,
                                                template_type: field_template_type,
                                                value: typeahead.items,
                                                deletions: JSON.parse(jQuery(field_td).find('#field_deletions_' + field_id).val())
                                            });
                                        }
                                        break;

                                    default:
                                        break;
                                }
                            } else {
                                payload['fields']['custom'].push({
                                    id: field_id,
                                    dt_type: field_type,
                                    template_type: field_template_type,
                                    value: jQuery(field_td).find(selector).val()
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

                <h3><?php esc_html_e( "Details", 'disciple_tools_bulk_magic_link_sender' ) ?> [ <span id="contact_name">---</span>
                    ]
                </h3>
                <hr>
                <div class="grid-x" id="form-content">
                    <input id="post_id" type="hidden"/>
                    <input id="post_type" type="hidden"/>
                    <?php
                    // Revert back to dt translations
                    $this->hard_switch_to_default_dt_text_domain();
                    ?>
                    <table style="display: none;" class="form-content-table">
                        <tbody></tbody>
                    </table>
                </div>
                <br>

                <!-- SUBMIT UPDATES -->
                <button id="content_submit_but" style="display: none; min-width: 100%;" class="button select-button">
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
