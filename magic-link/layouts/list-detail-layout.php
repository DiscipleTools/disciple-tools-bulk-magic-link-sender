<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.

/**
 * Class Disciple_Tools_Magic_Links_Templates
 */
class Disciple_Tools_Magic_Links_Layout_List_Detail {

    private $post = null;
    private $template = null;
    private $link_obj = null;

    public $sort_options = [[
        'name' => 'Last Updated',
        'value' => '-updated_at',
    ], [
        'name' => 'Name (A-Z)',
        'value' => 'name',
    ], [
        'name' => 'Name (Z-A)',
        'value' => '-name',
    ]];

    public function __construct( $template = null, $post = null, $link_obj = null ) {

        // only handle this template type
        if ( empty( $template ) ) {
            return;
        }

        $this->template         = $template;
        $this->post             = $post;
        $this->link_obj         = $link_obj;
    }

    public function wp_enqueue_scripts(): void
    {
        $js_path = '../../assets/layout-list-detail.js';
        $css_path = '../../assets/layout-list-detail.css';

        wp_enqueue_style( 'ml-layout-list-detail-css', plugin_dir_url( __FILE__ ) . $css_path, null, filemtime( plugin_dir_path( __FILE__ ) . $css_path ) );
        wp_enqueue_script( 'ml-layout-list-detail-js', plugin_dir_url( __FILE__ ) . $js_path, null, filemtime( plugin_dir_path( __FILE__ ) . $js_path ) );

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
    }

    public function allowed_js( $allowed_js ) {
        $allowed_js[] = 'dt-web-components-js';
        $allowed_js[] = 'dt-web-components-services-js';
        $allowed_js[] = 'ml-layout-list-detail-js';

        return $allowed_js;
    }

    public function allowed_css( $allowed_css ) {
        $allowed_css[] = 'material-font-icons-css';
        $allowed_css[] = 'dt-web-components-css';
        $allowed_css[] = 'ml-layout-list-detail-css';

        return $allowed_css;
    }

    protected function is_link_obj_field_enabled( $field_id ): bool {
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
     */
    public function footer_javascript( $parts, $items ) {
        $localized_template_field_settings = DT_ML_Helper::localized_template_selected_field_settings( $this->template );
        $localized_post_field_settings = DT_ML_Helper::localized_post_selected_field_settings( $this->post, $localized_template_field_settings, [ 'ID', 'post_type' ] );
        ?>
        <script>
            let jsObject = [<?php echo json_encode( [
                'root'                    => esc_url_raw( rest_url() ),
                'nonce'                   => wp_create_nonce( 'wp_rest' ),
                'parts'                   => $parts,
                'post'                    => $localized_post_field_settings,
                'items'                   => $items,
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
    }

    /**
     * Render list panel header
     * @return void
     */
    public function list_header(): void
    {
        $has_title = ! empty( $this->template ) && ( isset( $this->template['title'] ) && ! empty( $this->template['title'] ) );
        ?>
        <header>
            <h1><?php echo $has_title ? esc_html( $this->template['title'] ) : '&nbsp;' ?></h1>
            <button type="button" class="mdi mdi-web" onclick="document.getElementById('post-locale-modal')._openModal()"></button>
            <button type="button" class="mdi mdi-information-outline" onclick="document.getElementById('post-detail-modal')._openModal()"></button>
        </header>
        <?php
    }

    /**
     * Render list panel filters
     * @return void
     */
    public function list_filters(): void
    {
        ?>
        <div id="search-filter">
            <div id="search-bar">
                <input type="text" id="search" placeholder="Search" onkeyup="searchChange()" />
                <button id="clear-button" style="display: none;" class="clear-button mdi mdi-close" onclick="clearSearch()"></button>
                <button class="filter-button mdi mdi-filter-variant" onclick="toggleFilters()"></button>
            </div>
            <div class="filters hidden">
                <div class="container">
                    <h3>Sort By</h3>
                    <?php
                    if ( is_array( $this->sort_options ) && !empty( $this->sort_options ) ) {
                        foreach ( $this->sort_options as $option ) { ?>
                            <label>
                                <input type="radio" name="sort" value="<?php echo esc_attr( $option['value'] ) ?>" onclick="toggleFilters()" onchange="searchChange()" checked />
                                <?php echo esc_html( $option['name'] ); ?>
                            </label>
                        <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render HTML template for list item.
     * Should not include the <template> tag.
     * @return void
     */
    public function list_item_template() {
        ?>
        <li>
            <a href="javascript:loadPostDetail()">
                <span class="post-id"></span>
                <span class="post-title"></span>
                <span class="post-updated-date"></span>
            </a>
        </li>
        <?php
    }

    /**
     * Render detail panel header
     * @return void
     */
    public function detail_header() {
        ?>
        <header>
            <button type="button" class="details-toggle mdi mdi-arrow-left" onclick="togglePanels()"></button>
            <h2 id="detail-title"></h2>
            <span id="detail-title-post-id"></span>
        </header>
        <?php
    }

    /**
     * Render HTML template for comment header.
     * Should not include the <template> tag.
     * @return void
     */
    public function comment_header_template() {
        ?>
        <div class="comment-header">
            <span><strong id="comment-author"></strong></span>
            <span class="comment-date" id="comment-date"></span>
        </div>
        <?php
    }

    /**
     * Render HTML template for comments.
     * Should not include the <template> tag.
     * @return void
     */
    public function comment_content_template()
    {
        ?>
        <div class="activity-text">
            <div dir="auto" class="" data-comment-id="" id="comment-id">
                <div class="comment-text" title="" dir="auto" id="comment-content">
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render HTML template for post fields tile in detail panel.
     * Should not include the <template> tag.
     * @return void
     */
    public function detail_fields_tile_template(): void
    {
        ?>
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
        <?php
    }

    /**
     * Render HTML template for comments tile in detail panel.
     * Should not include the <template> tag.
     * @return void
     */
    public function detail_comments_tile_template(): void
    {
        ?>
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
        <?php
    }

    /**
     * Render content of post detail modal.
     * @return void
     */
    public function post_detail_modal_content(): void
    {
        ?>
        <span slot="content" id="post-detail-modal-content">
            <span class="post-name"><?php echo esc_html( $this->post['name'] ) ?></span>
            <span class="post-id">ID: <?php echo esc_html( $this->post['ID'] ) ?></span>
        </span>
        <?php
    }

    public function body() {
        $lang = dt_get_available_languages();
        $current_lang = trim( wp_get_current_user()->locale );
        ?>
        <main>
            <div id="list" class="is-expanded">
                <?php $this->list_header(); ?>

                <?php $this->list_filters(); ?>

                <ul id="list-items" class="items"></ul>
                <div id="spinner-div" style="justify-content: center; display: flex;">
                    <span id="temp-spinner" class="loading-spinner inactive" style="margin: 0; position: absolute; top: 50%; -ms-transform: translateY(-50%); transform: translateY(-50%); height: 100px; width: 100px; z-index: 100;"></span>
                </div>
                <template id="list-item-template">
                    <?php $this->list_item_template() ?>
                </template>
            </div>
            <div id="detail" class="">
                <form>
                    <?php $this->detail_header() ?>

                    <div id="detail-content"></div>
                    <footer>
                        <dt-button onclick="saveItem(event)" type="submit" context="primary"><?php esc_html_e( 'Submit Update', 'disciple_tools' ) ?></dt-button>
                    </footer>
                </form>

                <template id="comment-header-template">
                    <?php $this->comment_header_template() ?>
                </template>
                <template id="comment-content-template">
                    <?php $this->comment_content_template() ?>
                </template>

                <template id="post-detail-template">
                    <input type="hidden" name="id" id="post-id" />
                    <input type="hidden" name="type" id="post-type" />

                    <?php $this->detail_fields_tile_template() ?>

                    <?php $this->detail_comments_tile_template() ?>
                </template>
            </div>
            <div id="snackbar-area"></div>
            <template id="snackbar-item-template">
                <div class="snackbar-item"></div>
            </template>
        </main>
        <dt-modal id="post-detail-modal" buttonlabel="Open Modal" hideheader hidebutton closebutton>
            <?php $this->post_detail_modal_content() ?>
        </dt-modal>

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
}
