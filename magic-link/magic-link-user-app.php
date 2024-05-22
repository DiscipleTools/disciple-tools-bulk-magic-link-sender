<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.


/**
 * Class Disciple_Tools_Magic_Links_Magic_User_App
 */
class Disciple_Tools_Magic_Links_Magic_User_App extends DT_Magic_Url_Base {

    public $page_title = 'User Contact Updates';
    public $page_description = 'An update summary of assigned contacts.';
    public $root = 'smart_links'; // @todo define the root of the url {yoursite}/root/type/key/action
    public $type = 'user_contacts_updates'; // @todo define the type
    public $post_type = 'user';
    private $meta_key = '';

    private static $_instance = null;
    public $meta = []; // Allows for instance specific data.
    public $translatable = [
        'query',
        'user',
        'contact'
    ]; // Order of translatable flags to be checked. Translate on first hit..!

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // End instance()

    public function __construct() {
        /**
         * As incoming requests could be for either valid wp users of contact
         * post records, ensure to adjust the $post_type accordingly; so as to
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
            'supports_create' => true,
            'fields'         => [
                [
                    'id'    => 'name',
                    'label' => ''
                ],
                [
                    'id'    => 'milestones',
                    'label' => ''
                ],
                [
                    'id'    => 'overall_status',
                    'label' => ''
                ],
                [
                    'id'    => 'faith_status',
                    'label' => ''
                ],
                [
                    'id'    => 'contact_phone',
                    'label' => ''
                ],
                [
                    'id'    => 'comments',
                    'label' => __( 'Comments', 'disciple_tools' ) // Special Case!
                ]
            ],
            'fields_refresh' => [
                'enabled'    => true,
                'post_type'  => 'contacts',
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

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        // @todo add or remove js files with this filter

        $allowed_js[] = 'toastify-js';
        $allowed_js[] = Disciple_Tools_Bulk_Magic_Link_Sender_API::get_magic_link_utilities_script_handle();

        return $allowed_js;
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        // @todo add or remove js files with this filter

        $allowed_css[] = 'toastify-js-css';

        return $allowed_css;
    }

    public function wp_enqueue_scripts() {
        wp_enqueue_style( 'toastify-js-css', 'https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.css', [], '1.12.0' );
        wp_enqueue_script( 'toastify-js', 'https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.js', [ 'jquery' ], '1.12.0' );

        Disciple_Tools_Bulk_Magic_Link_Sender_API::enqueue_magic_link_utilities_script();
    }

    /**
     * Builds magic link type settings payload:
     * - key:               Unique magic link type key; which is usually composed of root, type and _magic_key suffix.
     * - url_base:          URL path information to map with parent magic link type.
     * - label:             Magic link type name.
     * - description:       Magic link type description.
     * - settings_display:  Boolean flag which determines if magic link type is to be listed within frontend user profile settings.
     *
     * @param array $apps_list
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
        <script></script>
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
                'map_key'                 => DT_Mapbox_API::get_key(),
                'root'                    => esc_url_raw( rest_url() ),
                'nonce'                   => wp_create_nonce( 'wp_rest' ),
                'parts'                   => $this->parts,
                'milestones'              => DT_Posts::get_post_field_settings( 'contacts' )['milestones']['default'],
                'overall_status'          => DT_Posts::get_post_field_settings( 'contacts' )['overall_status']['default'],
                'faith_status'            => DT_Posts::get_post_field_settings( 'contacts' )['faith_status']['default'],
                'link_obj_id'             => Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_obj( $this->fetch_incoming_link_param( 'id' ) ),
                'sys_type'                => $this->fetch_incoming_link_param( 'type' ),
                'translations'            => [
                    'update_success' => __( 'Update Successful!', 'disciple-tools-bulk-magic-link-sender' )
                ]
            ] ) ?>][0]

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

                        let html = `<tr onclick="get_assigned_contact_details('${window.lodash.escape(v.id)}', '${window.lodash.escape(window.lodash.replace(v.name, "'", "&apos;"))}');">
                                <td>${window.lodash.escape(v.name)}</td>
                            </tr>`;

                        table.find('tbody').append(html);

                    });
                }
            };

            /**
             * Format comment @mentions.
             */

            let comments = jQuery('.dt-comment-content');
            if (comments) {
                jQuery.each(comments, function (idx, comment) {
                    let formatted_comment = window.SHAREDFUNCTIONS.formatComment(window.lodash.escape(jQuery(comment).html()));
                    jQuery(comment).html(formatted_comment);
                });
            }

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
                        if (data['success'] && data['post']) {

                            // Display submit button
                            jQuery('#content_submit_but').fadeIn('fast');

                            // ID
                            jQuery('#post_id').val(data['post']['ID']);

                            // NAME
                            let post_name = window.lodash.escape(data['post']['name']);
                            jQuery('#contact_name').html(post_name);
                            if (window.is_field_enabled('name')) {
                                jQuery('#form_content_name_td').html(`
                                <input id="post_name" type="text" value="${post_name}" />
                                `);
                            } else {
                                jQuery('#form_content_name_tr').hide();
                            }

                            // MILESTONES
                            if (window.is_field_enabled('milestones')) {
                                let html_milestones = ``;
                                jQuery.each(jsObject.milestones, function (idx, milestone) {

                                    // Determine button selection state
                                    let button_select_state = 'empty-select-button';
                                    if (data['post']['milestones'] && (data['post']['milestones'].indexOf(idx) > -1)) {
                                        button_select_state = 'selected-select-button';
                                    }

                                    // Build button widget
                                    html_milestones += `<button id="${window.lodash.escape(idx)}"
                                                            type="button"
                                                            data-field-key="milestones"
                                                            class="dt_multi_select ${button_select_state} button select-button">
                                                        <img class="dt-icon" src="${window.lodash.escape(milestone['icon'])}"/>
                                                        ${window.lodash.escape(milestone['label'])}
                                                    </button>`;
                                });
                                jQuery('#form_content_milestones_td').html(html_milestones);

                                // Respond to milestone button state changes
                                jQuery('.dt_multi_select').on("click", function (evt) {
                                    let milestone = jQuery(evt.currentTarget);
                                    if (milestone.hasClass('empty-select-button')) {
                                        milestone.removeClass('empty-select-button');
                                        milestone.addClass('selected-select-button');
                                    } else {
                                        milestone.removeClass('selected-select-button');
                                        milestone.addClass('empty-select-button');
                                    }
                                });
                            } else {
                                jQuery('#form_content_milestones_tr').hide();
                            }

                            // OVERALL_STATUS
                            if (window.is_field_enabled('overall_status')) {
                                let html_overall_status = `<select id="post_overall_status" class="select-field">`;
                                jQuery.each(jsObject.overall_status, function (idx, overall_status) {

                                    // Determine selection state
                                    let select_state = '';
                                    if (data['post']['overall_status'] && (String(data['post']['overall_status']['key']) === String(idx))) {
                                        select_state = 'selected';
                                    }

                                    // Add option
                                    html_overall_status += `<option value="${window.lodash.escape(idx)}" ${select_state}>${window.lodash.escape(overall_status['label'])}</option>`;
                                });
                                html_overall_status += `</select>`;
                                jQuery('#form_content_overall_status_td').html(html_overall_status);
                            } else {
                                jQuery('#form_content_overall_status_tr').hide();
                            }

                            // FAITH_STATUS
                            if (window.is_field_enabled('faith_status')) {
                                let html_faith_status = `<select id="post_faith_status" class="select-field">`;
                                html_faith_status += `<option value=""></option>`;
                                jQuery.each(jsObject.faith_status, function (idx, faith_status) {

                                    // Determine selection state
                                    let select_state = '';
                                    if (data['post']['faith_status'] && (String(data['post']['faith_status']['key']) === String(idx))) {
                                        select_state = 'selected';
                                    }

                                    // Add option
                                    html_faith_status += `<option value="${window.lodash.escape(idx)}" ${select_state}>${window.lodash.escape(faith_status['label'])}</option>`;
                                });
                                html_faith_status += `</select>`;
                                jQuery('#form_content_faith_status_td').html(html_faith_status);
                            } else {
                                jQuery('#form_content_faith_status_tr').hide();
                            }

                            // CONTACT_PHONE
                            if (window.is_field_enabled('contact_phone')) {
                                if (data['post']['contact_phone']) {

                                    let phone_numbers = [];
                                    data['post']['contact_phone'].forEach(phone => {
                                        phone_numbers.push(phone['value']);
                                    });

                                    jQuery('#form_content_contact_phone_td').html(phone_numbers.length > 0 ? phone_numbers.join(', ') : '');
                                }
                            } else {
                                jQuery('#form_content_contact_phone_tr').hide();
                            }

                            // COMMENTS
                            if (window.is_field_enabled('comments')) {
                                let counter = 0;
                                let html_comments = `<textarea></textarea><br>`;
                                if (data['comments']['comments']) {
                                    data['comments']['comments'].forEach(comment => {
                                        if (counter++ < comment_count) { // Enforce comment count limit..!
                                            html_comments += `<b>${window.lodash.escape(comment['comment_author'])} @ ${window.lodash.escape(comment['comment_date'])}</b><br>`;
                                            html_comments += `${window.SHAREDFUNCTIONS.formatComment(window.lodash.escape(comment['comment_content']))}<hr>`;
                                        }
                                    });
                                }
                                jQuery('#form_content_comments_td').html(html_comments);
                            } else {
                                jQuery('#form_content_comments_tr').hide();
                            }

                            // Display updated post fields
                            jQuery('.form-content-table').fadeIn('fast');

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
                case 'post':
                    // Bypass contacts list and directly fetch requested contact details
                    assigned_contacts_div.fadeOut('fast');
                    window.get_contact(jsObject.parts.post_id);
                    break;
                default: // wp_user
                    // Fetch assigned contacts for incoming user
                    assigned_contacts_div.fadeIn('fast');
                    window.get_magic();
                    break;
            }

            /**
             * Create new record
             */

            jQuery('#add_new').on('click', function () {
                window.get_contact(0);
            });

            /**
             * Submit contact details
             */
            jQuery('#content_submit_but').on("click", function () {
                let spinner = jQuery('.update-loading-spinner');
                let id = jQuery('#post_id').val();

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
                        post_id: id
                    }
                    if (window.is_field_enabled('name')) {
                        payload['name'] = String(jQuery('#post_name').val()).trim();
                    }
                    if (window.is_field_enabled('milestones')) {
                        let milestones = [];
                        jQuery('#form_content_milestones_td button').each(function () {
                            milestones.push({
                                'value': jQuery(this).attr('id'),
                                'delete': jQuery(this).hasClass('empty-select-button')
                            });
                        });

                        payload['milestones'] = milestones;
                    }
                    if (window.is_field_enabled('overall_status')) {
                        payload['overall_status'] = String(jQuery('#post_overall_status').val()).trim();
                    }
                    if (window.is_field_enabled('faith_status')) {
                        payload['faith_status'] = String(jQuery('#post_faith_status').val()).trim();
                    }
                    if (window.is_field_enabled('contact_phone')) {
                        // Ignored, as field currently shown in a read-only capacity!
                    }
                    if (window.is_field_enabled('comments')) {
                        payload['comments'] = jQuery('#form_content_comments_td').find('textarea').eq(0).val();
                    }

                    spinner.addClass('active');

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
                            if (typeof window.ml_utility_submit_success_function === "function") {
                                window.ml_utility_submit_success_function(jsObject.translations.update_success, function () {
                                    window.location.reload();
                                });
                            } else {
                                window.location.reload();
                            }

                        } else {
                            if (typeof window.ml_utility_submit_error_function === "function") {
                                window.ml_utility_submit_error_function(data['message'], function () {
                                    console.log(data);
                                    jQuery('#error').html('');
                                    jQuery('#content_submit_but').prop('disabled', false);
                                });
                            } else {
                                console.log(data);
                                jQuery('#error').html('');
                                jQuery('#content_submit_but').prop('disabled', false);
                            }
                        }

                    }).fail(function (e) {
                        if (typeof window.ml_utility_submit_error_function === "function") {
                            window.ml_utility_submit_error_function(e['responseJSON']['message'], function () {
                                console.log(e);
                                jQuery('#error').html('');
                                jQuery('#content_submit_but').prop('disabled', false);
                            });
                        } else {
                            console.log(e);
                            jQuery('#error').html('');
                            jQuery('#content_submit_but').prop('disabled', false);
                        }
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
        $link_obj = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_obj( $this->fetch_incoming_link_param( 'id' ) );

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
                <div id="assigned_contacts_div" style="display: none;">
                    <h3><?php esc_html_e( 'Contacts', 'disciple_tools' ) ?> [ <span id="total">0</span> ]</h3>
                    <hr>
                    <div class="grid-x api-content-div-style" id="api-content">
                        <table class="api-content-table">
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                    <br>

                    <?php if ( ( isset( $link_obj ) && ( property_exists( $link_obj, 'type_config' ) && property_exists( $link_obj->type_config, 'supports_create' ) && $link_obj->type_config->supports_create ) ) || ( ! property_exists( $link_obj, 'type_config' ) ) ): ?>
                        <button id="add_new" class="button select-button">
                            <?php esc_html_e( 'Add New', 'disciple_tools' ) ?>
                        </button>
                    <?php endif; ?>
                </div>

                <!-- ERROR MESSAGES -->
                <span id="error" style="color: red;"></span>
                <br>

                <h3><span id="contact_name"></span>
                </h3>
                <hr>
                <div class="grid-x" id="form-content">
                    <input id="post_id" type="hidden"/>
                    <?php
                    $field_settings = DT_Posts::get_post_field_settings( 'contacts', false );
                    ?>
                    <table style="display: none;" class="form-content-table">
                        <tbody>
                        <tr id="form_content_name_tr">
                            <td style="vertical-align: top;">
                                <b><?php echo esc_attr( $field_settings['name']['name'] ); ?></b></td>
                            <td id="form_content_name_td"></td>
                        </tr>
                        <tr id="form_content_milestones_tr">
                            <td style="vertical-align: top;">
                                <b><?php echo esc_attr( $field_settings['milestones']['name'] ); ?></b></td>
                            <td id="form_content_milestones_td"></td>
                        </tr>
                        <tr id="form_content_overall_status_tr">
                            <td style="vertical-align: top;">
                                <b><?php echo esc_attr( $field_settings['overall_status']['name'] ); ?></b></td>
                            <td id="form_content_overall_status_td"></td>
                        </tr>
                        <tr id="form_content_faith_status_tr">
                            <td style="vertical-align: top;">
                                <b><?php echo esc_attr( $field_settings['faith_status']['name'] ); ?></b></td>
                            <td id="form_content_faith_status_td"></td>
                        </tr>
                        <tr id="form_content_contact_phone_tr">
                            <td style="vertical-align: top;">
                                <b><?php echo esc_attr( $field_settings['contact_phone']['name'] ); ?></b></td>
                            <td id="form_content_contact_phone_td"></td>
                        </tr>
                        <tr id="form_content_comments_tr">
                            <td style="vertical-align: top;">
                                <b><?php esc_html_e( 'Comments', 'disciple_tools' ) ?></b>
                            </td>
                            <td id="form_content_comments_td"></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <br>

                <!-- SUBMIT UPDATES -->
                <button id="content_submit_but" style="display: none; min-width: 100%;" class="button select-button">
                    <?php esc_html_e( 'Submit Update', 'disciple_tools' ) ?>
                    <span class="update-loading-spinner loading-spinner" style="height: 17px; width: 17px; vertical-align: text-bottom;"></span>
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
                    'methods'             => 'GET',
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
                    'methods'             => 'GET',
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
                    'methods'             => 'GET',
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
            return new WP_Error( __METHOD__, 'Missing parameters', [ 'status' => 400 ] );
        }

        // Sanitize and fetch user id
        $params  = dt_recursive_sanitize_array( $params );
        $user_id = $params['parts']['post_id'];

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
                        'subassigned' => [ 'me' ]
                    ],
                    'overall_status' => [
                        'new',
                        'unassigned',
                        'assigned',
                        'active'
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
            return new WP_Error( __METHOD__, 'Missing parameters', [ 'status' => 400 ] );
        }

        // Sanitize and fetch user/post id
        $params = dt_recursive_sanitize_array( $params );

        // Update logged-in user state if required accordingly, based on their sys_type
        $this->update_user_logged_in_state( $params['sys_type'], $params['parts']['post_id'] );

        // Fetch corresponding contacts post record
        $response = [];
        if ( $params['post_id'] > 0 ){
            $post = DT_Posts::get_post( 'contacts', $params['post_id'], false );
        } else {
            $post = [
                'ID' => 0,
                'post_type' => 'contacts'
            ];
        }
        if ( ! empty( $post ) && ! is_wp_error( $post ) ) {
            $response['success']  = true;
            $response['post']     = $post;
            $response['comments'] = ( $post['ID'] > 0 ) ? DT_Posts::get_post_comments( 'contacts', $params['post_id'], false, 'all', [ 'number' => $params['comment_count'] ] ) : [];
        } else {
            $response['success'] = false;
        }

        return $response;
    }

    public function update_record( WP_REST_Request $request ) {
        $params = $request->get_params();
        if ( !isset( $params['post_id'], $params['parts'], $params['action'], $params['sys_type'] ) ){
            return new WP_Error( __METHOD__, 'Missing core parameters', [ 'status' => 400 ] );
        }

        // Sanitize and fetch user id
        $params = dt_recursive_sanitize_array( $params );

        // Update logged-in user state if required accordingly, based on their sys_type
        $this->update_user_logged_in_state( $params['sys_type'], $params['parts']['post_id'] );

        // Capture name, if present
        $updates = [];
        if ( isset( $params['name'] ) && !empty( $params['name'] ) ){
            $updates['name'] = $params['name'];
        }

        // Capture overall status
        if ( isset( $params['overall_status'] ) && !empty( $params['overall_status'] ) ){
            $updates['overall_status'] = $params['overall_status'];
        }

        // Capture faith status
        if ( isset( $params['faith_status'] ) ){
            $updates['faith_status'] = $params['faith_status'];
        }

        // Capture milestones
        if ( isset( $params['milestones'] ) ){
            $milestones = [];
            foreach ( $params['milestones'] ?? [] as $milestone ){
                $entry = [];
                $entry['value'] = $milestone['value'];
                if ( strtolower( trim( $milestone['delete'] ) ) === 'true' ){
                    $entry['delete'] = true;
                }
                $milestones[] = $entry;
            }
            if ( !empty( $milestones ) ){
                $updates['milestones'] = [
                    'values' => $milestones
                ];
            }
        }

        // Update specified post record
        if ( empty( $params['post_id'] ) ) {
            // if ID is empty ("0", 0, or generally falsy), create a new post
            $updates['type'] = 'access';

            // Assign new item to parent post record.
            if ( isset( $params['parts']['post_id'] ) ){
                $updates['assigned_to'] = 'user-' . $params['parts']['post_id'];
            }

            $updated_post = DT_Posts::create_post( 'contacts', $updates, false, false );
        } else {
            $updated_post = DT_Posts::update_post( 'contacts', $params['post_id'], $updates, false, false );
        }
        if ( empty( $updated_post ) || is_wp_error( $updated_post ) ) {
            return [
                'success' => false,
                'message' => 'Unable to update/create contact record details!'
            ];
        }

        // Add any available comments
        if ( isset( $params['comments'] ) && ! empty( $params['comments'] ) ) {
            $updated_comment = DT_Posts::add_post_comment( $updated_post['post_type'], $updated_post['ID'], $params['comments'], 'comment', [], false );
            if ( empty( $updated_comment ) || is_wp_error( $updated_comment ) ) {
                return [
                    'success' => false,
                    'message' => 'Unable to add comment to contact record details!'
                ];
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
                $current_user->add_cap( 'magic_link' );
                $current_user->display_name = sprintf( __( '%s Submission', 'disciple_tools' ), apply_filters( 'dt_magic_link_global_name', __( 'Magic Link', 'disciple_tools' ) ) );
                break;
            default: // wp_user
                wp_set_current_user( $user_id );
                break;

        }
    }
}

Disciple_Tools_Magic_Links_Magic_User_App::instance();
