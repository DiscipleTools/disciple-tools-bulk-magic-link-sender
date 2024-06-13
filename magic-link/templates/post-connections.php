<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.

/**
 * Class Disciple_Tools_Magic_Links_Templates
 */
class Disciple_Tools_Magic_Links_Template_Post_Connections extends DT_Magic_Url_Base {

    public $page_title = 'Post Connections';
    public $page_description = 'Template Title Description';
    public $root = 'templates'; // @todo define the root of the url {yoursite}/root/type/key/action
    public $type = 'template_id'; // Placeholder to be replaced with actual template ids
    public $type_name = '';
    public $post_type = 'contacts'; // Support ML contacts (which can be any one of the DT post types) by default!
    public $record_post_type = 'groups'; //todo: use this in the admin UI
    private $post = null;
    private $items = [];
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

        $query_fields = [];
        if ( !empty( $this->template['connection_fields'] ) ) {
            foreach ( $this->template['connection_fields'] as $field ) {
                $query_fields[][$field] = [ $this->post['ID'] ];
            }
        }
        $this->items = DT_Posts::list_posts( $this->record_post_type, [
            'limit' => 1000,
            'fields' => [
                $query_fields
            ]
        ], false );

        /**
         * Attempt to load corresponding link object, if a valid incoming id has been detected.
         */

        $this->link_obj = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_obj( $this->fetch_incoming_link_param( 'id' ) );

        /**
         * Load if valid url
         */

        add_action('dt_blank_body', [$this, 'body']);
        add_filter('dt_magic_url_base_allowed_css', [$this, 'dt_magic_url_base_allowed_css'], 10, 1);
        add_filter('dt_magic_url_base_allowed_js', [$this, 'dt_magic_url_base_allowed_js'], 10, 1);
        add_action('wp_enqueue_scripts', [$this, 'wp_enqueue_scripts'], 100);
        add_filter('dt_can_update_permission', [$this, 'can_update_permission_filter'], 10, 3);
        add_filter( 'script_loader_tag', [ $this, 'script_loader_tag' ], 10, 2 );
    }

    // Ensure template fields remain editable
    public function can_update_permission_filter( $has_permission, $post_id, $post_type ) {
        return true;
    }

    public function wp_enqueue_scripts() {
        $js_path = '../../assets/post-connections.js';
        $css_path = '../../assets/post-connections.css';

        wp_enqueue_style( 'ml-post-connections-css', plugin_dir_url( __FILE__ ) . $css_path, null, filemtime( plugin_dir_path( __FILE__ ) . $css_path ) );
        wp_enqueue_script( 'ml-post-connections-js', plugin_dir_url( __FILE__ ) . $js_path, null, filemtime( plugin_dir_path( __FILE__ ) . $js_path ) );

        $dtwc_version = '0.6.3';
        wp_enqueue_style( 'dt-web-components-css', "https://cdn.jsdelivr.net/npm/@disciple.tools/web-components@$dtwc_version/src/styles/light.css", [], $dtwc_version );
        wp_enqueue_script( 'dt-web-components-js', "https://cdn.jsdelivr.net/npm/@disciple.tools/web-components@$dtwc_version/dist/index.min.js", $dtwc_version );

        $mdi_version = '6.6.96';
        wp_enqueue_style( 'material-font-icons-css', "https://cdn.jsdelivr.net/npm/@mdi/font@$mdi_version/css/materialdesignicons.min.css", [], $mdi_version );

        Disciple_Tools_Bulk_Magic_Link_Sender_API::enqueue_magic_link_utilities_script();
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        $allowed_js[] = 'dt-web-components-js';
        $allowed_js[] = 'ml-post-connections-js';
        $allowed_js[] = Disciple_Tools_Bulk_Magic_Link_Sender_API::get_magic_link_utilities_script_handle();

        return $allowed_js;
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        $allowed_css[] = 'material-font-icons-css';
        $allowed_css[] = 'dt-web-components-css';
        $allowed_css[] = 'ml-post-connections-css';

        return $allowed_css;
    }

    public function script_loader_tag( $tag, $handle ) {
        // add type="module" to web components script tag
        if ( str_contains( $handle, 'dt-web-components' ) ) {
            $re = '/type=[\'"](.*?)[\'"]/m';

            preg_match_all( $re, $tag, $matches, PREG_SET_ORDER, 0 );

            if ( count( $matches ) > 0 ) {
                $tag = str_replace( $matches[0][0], 'type="module"', $tag );
            } else {
                $tag = str_replace( '<script ', '<script type="module" ', $tag );
            }

            return $tag;
        }

        return $tag;
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

    private function localized_template_selected_field_settings( $template ) {
        $post_type_field_settings = DT_Posts::get_post_field_settings( $template['post_type'], false );
        if ( !empty( $template['fields'] ) ) {

            $localized_selected_field_settings = [];
            foreach ( $template['fields'] as $template_field ) {
                if ( isset( $template_field['id'], $template_field['type'], $template_field['enabled'] ) ) {
                    if ( $template_field['enabled'] && ( $template_field['type'] === 'dt' ) && isset( $post_type_field_settings[ $template_field['id'] ] ) ) {
                        $localized_selected_field_settings[ $template_field['id'] ] = $post_type_field_settings[ $template_field['id'] ];
                    }
                }
            }
            return $localized_selected_field_settings;
        } else {
            return $post_type_field_settings;
        }
    }

    private function localized_post_selected_field_settings( $post, $localised_fields, $inc_post_fields ) {
        if ( !empty( $localised_fields ) ) {
            $localized_post = [];
            foreach ( $post as $post_key => $post_value ) {
                if ( array_key_exists( $post_key, $localised_fields ) || in_array( $post_key, $inc_post_fields ) ) {
                    $localized_post[ $post_key ] = $post_value;
                }
            }
            return $localized_post;
        } else {
            return $post;
        }
    }

    /**
     * Writes javascript to the footer
     *
     * @see DT_Magic_Url_Base()->footer_javascript() for default state
     */
    public function footer_javascript() {
        $localized_template_field_settings = $this->localized_template_selected_field_settings( $this->template );
        $localized_post_field_settings = $this->localized_post_selected_field_settings( $this->post, $localized_template_field_settings, [ 'ID', 'post_type' ] );
        ?>
        <script>
            let jsObject = [<?php echo json_encode( [
                'root'                    => esc_url_raw( rest_url() ),
                'nonce'                   => wp_create_nonce( 'wp_rest' ),
                'parts'                   => $this->parts,
                'post'                    => $localized_post_field_settings,
                'items'                   => $this->items,
                'template'                => $this->template,
                'fieldSettings' => $localized_template_field_settings, //todo: should be for sub-type
                'translations'            => [
                    'regions_of_focus' => __( 'Regions of Focus', 'disciple_tools' ),
                    'all_locations'    => __( 'All Locations', 'disciple_tools' ),
                    'update_success' => __( 'Update Successful!', 'disciple_tools' ),
                    'validation' => [
                        'number' => [
                            'out_of_range' => __( 'Value out of range!', 'disciple_tools' )
                        ]
                    ]
                ],
                'mapbox'                  => [
                    'map_key'        => DT_Mapbox_API::get_key(),
                    'google_map_key' => Disciple_Tools_Google_Geocode_API::get_key(),
                    'translations'   => [
                        'search_location' => __( 'Search Location', 'disciple_tools' ),
                        'delete_location' => __( 'Delete Location', 'disciple_tools' ),
                        'use'             => __( 'Use', 'disciple_tools' ),
                        'open_modal'      => __( 'Open Modal', 'disciple_tools' )
                    ]
                ]
            ] ) ?>][0];

            const listItems = new Map(jsObject.items.posts.map((obj) => [obj.ID.toString(), obj]));

        </script>
        <?php
        return true;
    }

    public function body() {
        $has_title = ! empty( $this->template ) && ( isset( $this->template['title'] ) && ! empty( $this->template['title'] ) );
        ?>
        <main>
            <div id="list" class="is-expanded">
                <div id="search-filter">
                    <div id="search-bar">
                        <input type="text" id="search" placeholder="Search" />
                        <button class="filter-button mdi mdi-filter-variant"></button>

                    </div>
                    <div class="filters hidden">
                        <div class="container">
                            <h3>Sort By</h3>
                            <label>
                                <input type="radio" name="sort" value="-updated_at" checked />
                                Last Updated
                            </label>
                            <label>
                                <input type="radio" name="sort" value="name" />
                                Name (A-Z)
                            </label>
                            <label>
                                <input type="radio" name="sort" value="-name" />
                                Name (Z-A)
                            </label>
                        </div>
                    </div>
                </div>
                <ul class="items">
                <?php if ( isset( $this->items['posts'] ) && count( $this->items['posts'] ) > 0 ): ?>
                <?php foreach ( $this->items['posts'] as $item ): ?>
                    <li>
                        <a href="javascript:loadPostDetail(<?php echo esc_attr( $item['ID'] ) ?>)">
                            <?php echo esc_html( $item['name'] ) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <?php endif; ?>
                </ul>
            </div>
            <div id="detail" class="-is-expanded">
                <header>
                    <button class="details-toggle mdi mdi-arrow-left" onclick="togglePanels()"></button>
                    <h2 id="detail-title"></h2>

                    <!--
                    <ul class="tabs">
                        <li><a href="#status">Status</a></li>
                        <li><a href="#details">Details</a></li>
                        <li><a href="#faith">Faith</a></li>
                        <li><a href="#other">Other</a></li>
                        <li><a href="#comments">Comments & Activity</a></li>
                    </ul>
                    -->
                </header>

                <div id="detail-content">
                </div>
                <?php /* <pre><code><?php echo json_encode( $this->template, JSON_PRETTY_PRINT ) ?></code></pre> */ ?>
                <template id="post-detail-template">
                    <dt-tile id="status" open>
                        <dt-text label="Name" name="name" />
                    </dt-tile>
                </template>
                <template id="post-loading-template">
                    <div>Loading...</div>
                </template>
            </div>
        </main>
        <?php
    }

    /**
     * Register REST Endpoints
     * @link https://github.com/DiscipleTools/disciple-tools-theme/wiki/Site-to-Site-Link for outside of wordpress authentication
     */
    public function add_endpoints() {
        $namespace = $this->root . '/v1';
        register_rest_route(
            $namespace, '/' . $this->type . '/post', [
                [
                    'methods'             => 'GET',
                    'callback'            => [ $this, 'get_post' ],
                    'permission_callback' => function ( WP_REST_Request $request ) {
                        $magic = new DT_Magic_URL( $this->root );

                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
        register_rest_route(
            $namespace, '/' . $this->type . '/update', [
                [
                    'methods'             => 'POST',
                    'callback'            => [ $this, 'update_record' ],
                    'permission_callback' => function ( WP_REST_Request $request ) {
                        $magic = new DT_Magic_URL( $this->root );

                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
    }

    public function get_post( WP_REST_Request $request ){
        $params = $request->get_params();
        if ( !isset( $params['post_type'], $params['post_id'], $params['parts'], $params['action'], $params['comment_count'] ) ){
            return new WP_Error( __METHOD__, 'Missing parameters', [ 'status' => 400 ] );
        }

        // Sanitize and fetch user/post id
        $params = dt_recursive_sanitize_array( $params );

        // Update logged-in user state if required accordingly, based on their sys_type
        if ( !is_user_logged_in() ){
            $this->update_user_logged_in_state();
        }

        // Fetch corresponding post record
        $response = [];
        if ( $params['post_id'] > 0 ){
            $post = DT_Posts::get_post( $params['post_type'], $params['post_id'], false, false );
        } else {
            $post = [
                'ID' => 0,
                'post_type' => $params['post_type']
            ];
        }
        if ( !empty( $post ) && !is_wp_error( $post ) ){
            $response['success'] = true;
            $response['post'] = $this->localized_post_selected_field_settings( $post, $this->localized_template_selected_field_settings( $this->template ), [ 'ID', 'post_type' ] );
            $response['comments'] = ( $post['ID'] > 0 ) ? DT_Posts::get_post_comments( $params['post_type'], $params['post_id'], false, 'all', [ 'number' => $params['comment_count'] ] ) : [];
        } else {
            $response['success'] = false;
        }

        return $response;
    }

    public function update_record( WP_REST_Request $request ){
        $params = $request->get_params();
        if ( !isset( $params['post_id'], $params['post_type'], $params['parts'], $params['action'], $params['fields'] ) ){
            return new WP_Error( __METHOD__, 'Missing core parameters', [ 'status' => 400 ] );
        }

        // Sanitize and fetch user id
//        $params = dt_recursive_sanitize_array( $params );

        // Update logged-in user state, if required
        if ( !is_user_logged_in() ){
            $this->update_user_logged_in_state();
        }

        $updates = [];

        // First, capture and package incoming DT field values
        foreach ( $params['fields']['dt'] ?? [] as $field ){
            switch ( $field['dt_type'] ) {
                case 'number':
                case 'textarea':
                case 'text':
                case 'date':
                case 'boolean':
                    $field = dt_recursive_sanitize_array( $field );
                    $updates[$field['id']] = $field['value'];
                    break;
                case 'key_select':
                    $updates[$field['id']] = $field['value'];
                    break;
                case 'communication_channel':
                    $field = dt_recursive_sanitize_array( $field );
                    $updates[$field['id']] = [];

                    // First, capture additions and updates
                    foreach ( $field['value'] ?? [] as $value ){
                        $comm = [];
                        $comm['value'] = $value['value'];

                        if ( $value['key'] !== 'new' ){
                            $comm['key'] = $value['key'];
                        }

                        $updates[$field['id']][] = $comm;
                    }

                    // Next, capture deletions
                    foreach ( $field['deleted'] ?? [] as $delete_key ){
                        $updates[$field['id']][] = [
                            'delete' => true,
                            'key' => $delete_key
                        ];
                    }
                    break;

                case 'multi_select':
                    $field = dt_recursive_sanitize_array( $field );
                    $options = [];
                    foreach ( $field['value'] ?? [] as $option ){
                        $entry = [];
                        $entry['value'] = $option['value'];
                        if ( $option['delete'] ){
                            $entry['delete'] = true;
                        }
                        $options[] = $entry;
                    }
                    if ( !empty( $options ) ){
                        $updates[$field['id']] = [
                            'values' => $options
                        ];
                    }
                    break;

                case 'location':
                    $field = dt_recursive_sanitize_array( $field );
                    $locations = [];
                    foreach ( $field['value'] ?? [] as $location ){
                        $entry = [];
                        $entry['value'] = $location['ID'];
                        $locations[] = $entry;
                    }

                    // Capture any incoming deletions
                    foreach ( $field['deletions'] ?? [] as $location ){
                        $entry = [];
                        $entry['value'] = $location['ID'];
                        $entry['delete'] = true;
                        $locations[] = $entry;
                    }

                    // Package and append to global updates
                    if ( !empty( $locations ) ){
                        $updates[$field['id']] = [
                            'values' => $locations
                        ];
                    }
                    break;

                case 'location_meta':
                    $field = dt_recursive_sanitize_array( $field );
                    $locations = [];

                    // Capture selected location, if available; or prepare shape
                    if ( !empty( $field['value'] ) && isset( $field['value'][$field['id']] ) ){
                        $locations[$field['id']] = $field['value'][$field['id']];

                    } else {
                        $locations[$field['id']] = [
                            'values' => []
                        ];
                    }

                    // Capture any incoming deletions
                    foreach ( $field['deletions'] ?? [] as $id ){
                        $entry = [];
                        $entry['grid_meta_id'] = $id;
                        $entry['delete'] = true;
                        $locations[$field['id']]['values'][] = $entry;
                    }

                    // Package and append to global updates
                    if ( !empty( $locations[$field['id']]['values'] ) ){
                        $updates[$field['id']] = $locations[$field['id']];
                    }
                    break;

                case 'tags':
                    $field = dt_recursive_sanitize_array( $field );
                    $tags = [];
                    foreach ( $field['value'] ?? [] as $tag ){
                        $entry = [];
                        $entry['value'] = $tag['name'];
                        $tags[] = $entry;
                    }

                    // Capture any incoming deletions
                    foreach ( $field['deletions'] ?? [] as $tag ){
                        $entry = [];
                        $entry['value'] = $tag['name'];
                        $entry['delete'] = true;
                        $tags[] = $entry;
                    }

                    // Package and append to global updates
                    if ( !empty( $tags ) ){
                        $updates[$field['id']] = [
                            'values' => $tags
                        ];
                    }
                    break;
            }
        }

        // Update specified post record
        if ( empty( $params['post_id'] ) ) {
            // if ID is empty ("0", 0, or generally falsy), create a new post
            $updates['type'] = 'access';

            // Subassign new item to parent post record.
            if ( isset( $params['parts']['post_id'] ) ){
                $updates['subassigned'] = [
                    'values' => [
                        [
                            'value' => $params['parts']['post_id']
                        ]
                    ]
                ];
            }

            $updated_post = DT_Posts::create_post( $params['post_type'], $updates, false, false );
        } else {
            $updated_post = DT_Posts::update_post( $params['post_type'], $params['post_id'], $updates, false, false );
        }

        if ( empty( $updated_post ) || is_wp_error( $updated_post ) ) {
            return [
                'success' => false,
                'message' => 'Unable to update/create contact record details!'
            ];
        }

        // Next, any identified custom fields, are to be added as comments
        foreach ( $params['fields']['custom'] ?? [] as $field ) {
            $field = dt_recursive_sanitize_array( $field );
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

        // Next, dispatch submission notification, accordingly; always send by default.
        if ( $params['send_submission_notifications'] && isset( $updated_post['assigned_to'], $updated_post['assigned_to']['id'], $updated_post['assigned_to']['display'] ) ) {
            $default_comment = sprintf( __( '%s Updates Submitted', 'disciple_tools' ), $params['template_name'] );
            $submission_comment = '@[' . $updated_post['assigned_to']['display'] . '](' . $updated_post['assigned_to']['id'] . ') ' . $default_comment;
            DT_Posts::add_post_comment( $updated_post['post_type'], $updated_post['ID'], $submission_comment, 'comment', [], false );
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
        $current_user->add_cap( 'magic_link' );
        $current_user->display_name = sprintf( __( '%s Submission', 'disciple_tools' ), apply_filters( 'dt_magic_link_global_name', __( 'Magic Link', 'disciple_tools' ) ) );
    }
}
