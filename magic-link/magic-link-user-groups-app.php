<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.


/**
 * Class Disciple_Tools_Magic_Links_Magic_User_Groups_App
 */
class Disciple_Tools_Magic_Links_Magic_User_Groups_App extends DT_Magic_Url_Base {

    public $page_title = 'User Group Updates';
    public $page_description = 'An update summary of assigned groups.';
    public $root = "smart_links"; // @todo define the root of the url {yoursite}/root/type/key/action
    public $type = 'user_groups_updates'; // @todo define the type
    public $post_type = 'user';
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

        $field_settings = DT_Posts::get_post_field_settings( 'groups', false );
        dt_write_log(json_encode($field_settings, JSON_PRETTY_PRINT));
        $supported_types = ['text'];
        $fields = [];
        foreach ( $field_settings as $key => $value ) {
            // only allow supported field types
            // ignore hidden or custom_display fields
            if ( !in_array( $value['type'], $supported_types ) || $value['hidden'] || $value['custom_display']) {
                continue;
            }
            $fields[] = [
                'id' => $key,
                'label' => $value['name'],
            ];
        }

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
        return $allowed_js;
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        // @todo add or remove js files with this filter
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
                'map_key'        => DT_Mapbox_API::get_key(),
                'root'           => esc_url_raw( rest_url() ),
                'nonce'          => wp_create_nonce( 'wp_rest' ),
                'parts'          => $this->parts,
                'link_obj_id'    => Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_obj( $this->fetch_incoming_link_param( 'id' ) ),
                'sys_type'       => $this->fetch_incoming_link_param( 'type' ),
                'translations'   => [
                    'add' => __( 'Add Magic', 'disciple-tools-bulk-magic-link-sender' ),
                ]
            ] ) ?>][0]

            /**
             * Fetch assigned groups
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
                                <td class="last-update">Last updated: ${window.lodash.escape(v.last_modified.formatted)}</td>
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
                            jQuery('#group_name').html(post_name);
                            if (window.is_field_enabled('name')) {
                                jQuery('#form_content_name_td').html(`
                                <input id="post_name" type="text" value="${post_name}" />
                                `);
                            } else {
                                jQuery('#form_content_name_tr').hide();
                            }

                            // COMMENTS
                            if (window.is_field_enabled('comments')) {
                                let counter = 0;
                                let html_comments = `<textarea></textarea><br>`;
                                if (data['comments']['comments']) {
                                    data['comments']['comments'].forEach(comment => {
                                        if (counter++ < comment_count) { // Enforce comment count limit..!
                                            html_comments += `<b>${window.lodash.escape(comment['comment_author'])} @ ${window.lodash.escape(comment['comment_date'])}</b><br>`;
                                            html_comments += `${window.lodash.escape(comment['comment_content'])}<hr>`;
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
                    if (window.is_field_enabled('comments')) {
                        payload['comments'] = jQuery('#form_content_comments_td').find('textarea').eq(0).val();
                    }

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

            // Revert to original user
            if ( ! empty( $original_user ) && isset( $original_user->ID ) ) {
                wp_set_current_user( $original_user->ID );
            }

            // Iterate and return valid posts
            if ( ! empty( $posts ) && isset( $posts['posts'], $posts['total'] ) ) {
                $data['total'] = $posts['total'];
                foreach ( $posts['posts'] ?? [] as $post ) {
                    $post['id'] = $post['ID'];
                    unset($post['ID']);
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

        // Fetch corresponding groups post record
        $response = [];
        $post     = DT_Posts::get_post( 'groups', $params['post_id'], false );
        if ( ! empty( $post ) && ! is_wp_error( $post ) ) {
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

        // Capture name, if present
        $updates = [];
        if ( isset( $params['name'] ) && ! empty( $params['name'] ) ) {
            $updates['name'] = $params['name'];
        }

        // Update specified post record
        $updated_post = DT_Posts::update_post( 'groups', $params['post_id'], $updates, false, false );
        if ( empty( $updated_post ) || is_wp_error( $updated_post ) ) {
            return [
                'success' => false,
                'message' => 'Unable to update group record details!'
            ];
        }

        // Add any available comments
        if ( isset( $params['comments'] ) && ! empty( $params['comments'] ) ) {
            $updated_comment = DT_Posts::add_post_comment( $updated_post['post_type'], $updated_post['ID'], $params['comments'], 'comment', [], false );
            if ( empty( $updated_comment ) || is_wp_error( $updated_comment ) ) {
                return [
                    'success' => false,
                    'message' => 'Unable to add comment to group record details!'
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
