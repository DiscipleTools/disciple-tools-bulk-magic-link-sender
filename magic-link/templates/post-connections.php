<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.

add_filter('dt_magic_link_template_types', function( $types ) {
    $types['contacts'][] = [
        'value' => 'post-connections',
        'text' => 'Post Connections (beta)',
    ];
    $types['default-options'][] = [
        'value' => 'post-connections',
        'text' => 'Post Connections (beta)',
    ];
    return $types;
});

add_action('dt_magic_link_template_load', function ( $template ) {
    if ( isset( $template['type'] ) && $template['type'] === 'post-connections' ) {
        new Disciple_Tools_Magic_Links_Template_Post_Connections( $template );
    }
} );

/**
 * Class Disciple_Tools_Magic_Links_Templates
 */
class Disciple_Tools_Magic_Links_Template_Post_Connections extends DT_Magic_Url_Base {

    protected $template_type = 'post-connections';
    public $page_title = 'Post Connections';
    public $page_description = 'Edit all connections to a given post';
    public $root = 'templates'; // @todo define the root of the url {yoursite}/root/type/key/action
    public $type = 'template_id'; // Placeholder to be replaced with actual template ids
    public $type_name = '';
    public $post_type = 'contacts'; // Main post type that the ML is linked to.
    public $record_post_type = 'groups'; // Child post type determined by the connection field selected
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

        // only handle this template type
        if ( empty( $template ) || $template['type'] !== $this->template_type ) {
            return;
        }

        $this->template         = $template;
        $this->post_type        = $template['post_type'];
        $this->record_post_type = isset( $template['record_type'] ) ? $template['record_type'] : $template['post_type'];
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

        $show_in_apps = false;

        if ( $template['post_type'] == 'contacts' ) {
            $show_in_apps = true;
        }

        $this->meta = [
            'app_type'   => 'magic_link',
            'class_type' => 'template',
            'show_in_home_apps' => $show_in_apps,
            'icon' => 'mdi mdi-account-network',
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

        if ( is_wp_error( $this->items ) ) {
            dt_write_log( $this->items );
        }

        /**
         * Attempt to load corresponding link object, if a valid incoming id has been detected.
         */

        $this->link_obj = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_obj( $this->fetch_incoming_link_param( 'id' ) );

        // Revert back to dt translations
        $this->hard_switch_to_default_dt_text_domain();

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
        $js_path = '../../assets/post-connections.js';
        $css_path = '../../assets/post-connections.css';

        wp_enqueue_style( 'ml-post-connections-css', plugin_dir_url( __FILE__ ) . $css_path, null, filemtime( plugin_dir_path( __FILE__ ) . $css_path ) );
        wp_enqueue_script( 'ml-post-connections-js', plugin_dir_url( __FILE__ ) . $js_path, null, filemtime( plugin_dir_path( __FILE__ ) . $js_path ) );

        $dtwc_version = '0.6.6';
//        wp_enqueue_style( 'dt-web-components-css', "https://cdn.jsdelivr.net/npm/@disciple.tools/web-components@$dtwc_version/styles/light.css", [], $dtwc_version );
        wp_enqueue_style( 'dt-web-components-css', "https://cdn.jsdelivr.net/npm/@disciple.tools/web-components@$dtwc_version/src/styles/light.css", [], $dtwc_version ); // remove 'src' after v0.7
        wp_enqueue_script( 'dt-web-components-js', "https://cdn.jsdelivr.net/npm/@disciple.tools/web-components@$dtwc_version/dist/index.js", $dtwc_version );
        add_filter( 'script_loader_tag', 'add_module_type_to_script', 10, 3 );
        function add_module_type_to_script( $tag, $handle, $src ) {
            if ( 'dt-web-components-js' === $handle ) {
                // @codingStandardsIgnoreStart
                $tag = '<script type="module" src="' . esc_url( $src ) . '"></script>';
                // @codingStandardsIgnoreEnd
            }
            return $tag;
        }
        wp_enqueue_script( 'dt-web-components-services-js', "https://cdn.jsdelivr.net/npm/@disciple.tools/web-components@$dtwc_version/dist/services.min.js", array( 'jquery' ), true ); // not needed after v0.7

        $mdi_version = '6.6.96';
        wp_enqueue_style( 'material-font-icons-css', "https://cdn.jsdelivr.net/npm/@mdi/font@$mdi_version/css/materialdesignicons.min.css", [], $mdi_version );

        Disciple_Tools_Bulk_Magic_Link_Sender_API::enqueue_magic_link_utilities_script();
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        $allowed_js[] = 'dt-web-components-js';
        $allowed_js[] = 'dt-web-components-services-js';
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

    /**
     * Writes javascript to the footer
     *
     * @see DT_Magic_Url_Base()->footer_javascript() for default state
     */
    public function footer_javascript() {
        $localized_template_field_settings = DT_ML_Helper::localized_template_selected_field_settings( $this->template );
        $localized_post_field_settings = DT_ML_Helper::localized_post_selected_field_settings( $this->post, $localized_template_field_settings, [ 'ID', 'post_type' ] );
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

            // initialize the list of items
            loadListItems();
        </script>
        <?php
        return true;
    }

    public function body() {
        $has_title = ! empty( $this->template ) && ( isset( $this->template['title'] ) && ! empty( $this->template['title'] ) );
        ?>
        <main>
            <div id="list" class="is-expanded">
                <header>
                    <h1><?php echo $has_title ? esc_html( $this->template['title'] ) : '&nbsp;' ?></h1>
                    <button type="button" class="mdi mdi-web" onclick="document.getElementById('post-locale-modal')._openModal()"></button>
                    <button type="button" class="mdi mdi-information-outline" onclick="document.getElementById('post-detail-modal')._openModal()"></button>
                </header>
                <div id="search-filter">
                    <div id="search-bar">
                    <input type="text" id="search" placeholder="Search" onkeyup="searchChange(<?php echo esc_html( $this->post['ID'] ) ?>)" />
                    <button id="clear-button" style="display: none;" class="clear-button mdi mdi-close" onclick="clearSearch(<?php echo esc_html( $this->post['ID'] ) ?>)"></button>
                        <button class="filter-button mdi mdi-filter-variant" onclick="toggleFilters()"></button>

                    </div>
                    <div class="filters hidden">
                        <div class="container">
                            <h3>Sort By</h3>
                            <label>
                                <input type="radio" name="sort" value="-updated_at" onclick="toggleFilters()" onchange="searchChange(<?php echo esc_html( $this->post['ID'] ) ?>)" checked />
                                Last Updated
                            </label>
                            <label>
                                <input type="radio" name="sort" value="name" onclick="toggleFilters()" onchange="searchChange(<?php echo esc_html( $this->post['ID'] ) ?>)" />
                                Name (A-Z)
                            </label>
                            <label>
                                <input type="radio" name="sort" value="-name" onclick="toggleFilters()" onchange="searchChange(<?php echo esc_html( $this->post['ID'] ) ?>)" />
                                Name (Z-A)
                            </label>
                        </div>
                    </div>
                </div>
                <ul id="list-items" class="items"></ul>
                <div id="spinner-div" style="justify-content: center; display: flex;">
                    <span id="temp-spinner" class="loading-spinner inactive" style="margin: 0; position: absolute; top: 50%; -ms-transform: translateY(-50%); transform: translateY(-50%); height: 100px; width: 100px; z-index: 100;"></span>
                </div>
                <template id="list-item-template">
                    <li>
                        <a href="javascript:loadPostDetail()"></a>
                    </li>
                </template>
            </div>
            <div id="detail" class="">
                <!-- <form onsubmit="saveItem(event)"> -->
                <form>
                    <header>
                        <button type="button" class="details-toggle mdi mdi-arrow-left" onclick="togglePanels()"></button>
                        <h2 id="detail-title"></h2>
                    </header>

                    <div id="detail-content"></div>
                    <footer>
                        <dt-button onclick="saveItem(event)" type="submit" context="primary"><?php esc_html_e( 'Submit Update', 'disciple_tools' ) ?></dt-button>
                    </footer>
                </form>

                <template id="comment-header-template">
                    <div class="comment-header">
                        <span><strong id="comment-author"></strong></span>
                        <span class="comment-date" id="comment-date"></span>
                    </div>
                </template>
                <template id="comment-content-template">
                    <div class="activity-text">
                        <div dir="auto" class="" data-comment-id="" id="comment-id">
                            <div class="comment-text" title="" dir="auto" id="comment-content">
                            </div>
                        </div>
                    </div>
                </template>

                <template id="post-detail-template">
                    <input type="hidden" name="id" id="post-id" />
                    <input type="hidden" name="type" id="post-type" />

                    <dt-tile id="all-fields" open>
                    <?php
                    $post_field_settings = DT_Posts::get_post_field_settings( $this->template['record_type'] );
                    if ( isset( $this->template['fields'] ) ) {
                        foreach ( $this->template['fields'] as $field ) {
                            if ( !$field['enabled'] || !$this->is_link_obj_field_enabled( $field['id'] ) ) {
                                continue;
                            }

                            if ( $field['type'] === 'dt' ) {
                                // display standard DT fields
                                $post_field_settings[$field['id']]['custom_display'] = false;
                                $post_field_settings[$field['id']]['readonly'] = !empty( $field['readonly'] ) && boolval( $field['readonly'] );

                                Disciple_Tools_Magic_Links_Helper::render_field_for_display( $field['id'], $post_field_settings, [] );
                            } else {
                                // display custom field for this magic link
                                $tag = isset( $field['custom_form_field_type'] ) && $field['custom_form_field_type'] == 'textarea'
                                    ? 'dt-textarea'
                                    : 'dt-text';
                                $label = ( ! empty( $field['translations'] ) && isset( $field['translations'][ determine_locale() ] ) ) ? $field['translations'][ determine_locale() ]['translation'] : $field['label'];
                                ?>
                                <<?php echo esc_html( $tag ) ?>
                                    id="<?php echo esc_html( $field['id'] ) ?>"
                                    name="<?php echo esc_html( $field['id'] ) ?>"
                                    data-type="<?php echo esc_attr( $field['type'] ) ?>"
                                    label="<?php echo esc_attr( $label ) ?>"
                                ></<?php echo esc_html( $tag ) ?>>
                                <?php
                            }
                        }
                    }
                    ?>
                    </dt-tile>

                    <dt-tile id="comments-tile" title="Comments">
                        <div>
                            <textarea id="comments-text-area"
                                      style="resize: none;"
                                      placeholder="<?php echo esc_html_x( 'Write your comment or note here', 'input field placeholder', 'disciple_tools' ) ?>"
                            ></textarea>
                        </div>
                        <div class="comment-button-container">
                            <button class="button loader" type="button" id="comment-button">
                                <?php esc_html_e( 'Submit comment', 'disciple_tools' ) ?>
                            </button>
                        </div>
                    </dt-tile>
                </template>
            </div>
            <div id="snackbar-area"></div>
            <template id="snackbar-item-template">
                <div class="snackbar-item"></div>
            </template>
        </main>
        <dt-modal id="post-detail-modal" buttonlabel="Open Modal" hideheader hidebutton closebutton>
            <span slot="content" id="post-detail-modal-content">
                <span class="post-name"><?php echo esc_html( $this->post['name'] ) ?></span>
                <span class="post-id">ID: <?php echo esc_html( $this->post['ID'] ) ?></span>
            </span>
        </dt-modal>
        <?php
        $lang = dt_get_available_languages();
        $current_lang = trim( wp_get_current_user()->locale );
        ?>
        <dt-modal id="post-locale-modal" buttonlabel="Open Modal" hideheader hidebutton closebutton>
            <span slot="content" id="post-locale-modal-content">
            <ul class="language-select">
                <?php
                foreach ( $lang as $language ) {
                    ?>
                    <li
                        class="<?php echo $language['language'] === $current_lang ? esc_attr( 'active' ) : null ?>"
                        onclick="assignLanguage('<?php echo esc_html( $language['language'] ); ?>')"
                    ><?php echo esc_html( $language['native_name'] ); ?></li>
                    <?php
                }
                ?>
                </ul>
            </span>
        </dt-modal>
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
                    'methods'             => 'POST',
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

                        $params = $request->get_params();

                        $denied = $this->check_permissions( $params['parts']['post_id'], $params['post_id'] );
                        if ( $denied ) {
                            return false;
                        }

                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
        register_rest_route(
            $namespace, '/' . $this->type . '/comment', [
                [
                    'methods'             => 'POST',
                    'callback'            => [ $this, 'new_comment' ],
                    'permission_callback' => function ( WP_REST_Request $request ) {
                        $magic = new DT_Magic_URL( $this->root );

                        $params = $request->get_params();

                        $denied = $this->check_permissions( $params['parts']['post_id'], $params['post_id'] );
                        if ( $denied ) {
                            return false;
                        }

                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
        register_rest_route(
            $namespace, '/' . $this->type . '/sort_post', [
                [
                    'methods'             => 'POST',
                    'callback'            => [ $this, 'sorted_list_posts' ],
                    'permission_callback' => function ( WP_REST_Request $request ) {
                        $magic = new DT_Magic_URL( $this->root );

                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
    }

    public function check_permissions( $post_id, $connection_id ) {
        //set query fields to search for our post_id
        $query_fields = [];
        if ( !empty( $this->template['connection_fields'] ) ) {
            foreach ( $this->template['connection_fields'] as $field ) {
                $query_fields[][$field] = [ $post_id ];
            }
        }

        //get related records that have our query fields
        $this->items = DT_Posts::list_posts( $this->record_post_type, [
            'limit' => 1000,
            'fields' => [
                $query_fields
            ]
        ], false );

        //return 'error' if the post_id in the request is not in the list
        foreach ( $this->items['posts'] as $item ) {
            if ( $connection_id === $item['ID'] ) {
                return false;
            }
        }
        return true;
    }

    public function get_post( WP_REST_Request $request ){
        $params = $request->get_params();
        if ( !isset( $params['post_type'], $params['post_id'], $params['parts'], $params['action'], $params['comment_count'] ) ){
            return new WP_Error( __METHOD__, 'Missing parameters', [ 'status' => 400 ] );
        }

        // Sanitize and fetch user/post id
        $params = dt_recursive_sanitize_array( $params );

        // Update logged-in user state if required accordingly, based on their sys_type
        if ( !is_user_logged_in() ) {
            DT_ML_Helper::update_user_logged_in_state();
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
            $response['post'] = DT_ML_Helper::localized_post_selected_field_settings( $post, DT_ML_Helper::localized_template_selected_field_settings( $this->template ), [ 'ID', 'post_type' ] );
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
        $params = dt_recursive_sanitize_array( $params );

        // Update logged-in user state, if required
        if ( !is_user_logged_in() ){
            DT_ML_Helper::update_user_logged_in_state();
        }

        $updates = [];

        foreach ( $params['fields']['dt'] ?? [] as $field ) {
            if ( $field['id'] === 'age' ) {
                $field['value'] = str_replace( '&lt;', '<', $field['value'] );
                $field['value'] = str_replace( '&gt;', '>', $field['value'] );
            }
            if ( isset( $field['value'] ) ) {
                switch ( $field['type'] ) {
                    case 'text':
                    case 'textarea':
                    case 'number':
                    case 'date':
                    case 'datetime':
                    case 'boolean':
                    case 'key_select':
                    case 'multi_select':
//                    case 'tags':
                        $updates[$field['id']] = $field['value'];
                        break;
                    case 'communication_channel':
                        $updates[$field['id']] = [
                            'values' => $field['value'],
                            'force_values' => true,
                        ];
                        break;
//                    case 'location':
                    case 'location_meta':
                        $values = array_map(function ( $value ) {
                            // try to send without grid_id to get more specific location
                            if ( isset( $value['lat'], $value['lng'], $value['label'], $value['level'], $value['source'] ) ) {
                                return array_intersect_key($value, array_fill_keys([
                                    'lat',
                                    'lng',
                                    'label',
                                    'level',
                                    'source',
                                ], null));
                            }
                            return array_intersect_key($value, array_fill_keys([
                                'lat',
                                'lng',
                                'label',
                                'level',
                                'source',
                                'grid_id'
                            ], null));
                        }, $field['value'] );
                        $updates[$field['id']] = [
                            'values' => $values,
                            'force_values' => true,
                        ];
                        break;
                    default:
                        // unhandled field types
                        dt_write_log( 'Unsupported field type: ' . $field['value'] );
                        break;
                }
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
            // dt_write_log( json_encode( $updates ) );
            $updated_post = DT_Posts::update_post( $params['post_type'], $params['post_id'], $updates, false, false );
        }

        if ( empty( $updated_post ) || is_wp_error( $updated_post ) ) {
            dt_write_log( $updated_post );
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
        if ( isset( $params['send_submission_notifications'] ) && $params['send_submission_notifications'] && isset( $updated_post['assigned_to'], $updated_post['assigned_to']['id'], $updated_post['assigned_to']['display'] ) ) {
            $default_comment = sprintf( __( '%s Updates Submitted', 'disciple_tools' ), $params['template_name'] );
            $submission_comment = '@[' . $updated_post['assigned_to']['display'] . '](' . $updated_post['assigned_to']['id'] . ') ' . $default_comment;
            DT_Posts::add_post_comment( $updated_post['post_type'], $updated_post['ID'], $submission_comment, 'comment', [], false );
        }

        // Finally, return successful response
        return [
            'success' => true,
            'message' => '',
            'post' => $updated_post,
        ];
    }

    public function new_comment( WP_REST_Request $request ){
        $params = $request->get_params();
        if ( !isset( $params['post_type'], $params['post_id'], $params['parts'], $params['action'] ) ){
            return new WP_Error( __METHOD__, 'Missing parameters', [ 'status' => 400 ] );
        }

        // Sanitize and fetch user id
        $params = dt_recursive_sanitize_array( $params );

        // Update logged-in user state, if required
        if ( !is_user_logged_in() ){
            DT_ML_Helper::update_user_logged_in_state();
        }

        $post = DT_Posts::get_post( $params['post_type'], $params['post_id'], false, false );
        //$params['comment']
        DT_Posts::add_post_comment( $post['post_type'], $post['ID'], $params['comment'], 'comment', [], false );

        return [
            'success' => true,
            'message' => '',
            'post' => $post,
        ];
    }

    public function sorted_list_posts( WP_REST_Request $request ){
        $params = $request->get_params();
        if ( !isset( $params['post_type'], $params['post_id'], $params['parts'], $params['action'] ) ){
            return new WP_Error( __METHOD__, 'Missing parameters', [ 'status' => 400 ] );
        }

        // Sanitize and fetch user id
        $params = dt_recursive_sanitize_array( $params );

        // Update logged-in user state, if required
        if ( !is_user_logged_in() ){
            DT_ML_Helper::update_user_logged_in_state();
        }

        $this->post = DT_Posts::get_post( $this->post_type, $params['post_id'], true, false );

        $query_fields = [];
        if ( !empty( $this->template['connection_fields'] ) ) {
            foreach ( $this->template['connection_fields'] as $field ) {
                $query_fields[][$field] = [ $params['post_id'] ];
            }
        }

        $sorted_items = DT_Posts::list_posts( $this->record_post_type, [
            'text' => $params['text'],
            'sort' => $params['sort'],
            'limit' => 1000,
            'fields' => [
                $query_fields
            ]
        ], false );

        return $sorted_items;
    }
}
