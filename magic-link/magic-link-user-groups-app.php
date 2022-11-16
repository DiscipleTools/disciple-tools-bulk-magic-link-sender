<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.

/**
 * Class Disciple_Tools_Magic_Links_Magic_User_Groups_App
 */
class Disciple_Tools_Magic_Links_Magic_User_Groups_App extends Disciple_Tools_Magic_Links_Magic_User_Posts_Base {

    public $page_title = 'User Group Updates';
    public $page_description = 'An update summary of assigned groups.';
    public $type = 'user_groups_updates';
    public $sub_post_type = 'groups';
    public $sub_post_type_display = 'Groups';

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // End instance()

    public function __construct() {
        parent::__construct();

        $this->sub_post_type_display = __( 'Groups', 'disciple_tools' );

        // Add this to list of magic link types
        add_filter( 'dt_settings_apps_list', [ $this, 'dt_settings_apps_list' ], 10, 1 );

        parent::register_front_end();
    }

    /**
     * Writes javascript to the footer
     *
     * @see DT_Magic_Url_Base()->footer_javascript() for default state
     * @todo remove if not needed
     */
    public function footer_javascript() {
        parent::footer_javascript();
        ?>
        <script>
            window.format_post_row = (post) => {
                return `<tr onclick="get_assigned_post_details('${window.lodash.escape(post.id)}', '${window.lodash.escape(window.lodash.replace(post.name, "'", "&apos;"))}');">
                    <td>${window.lodash.escape(post.name)}</td>
                    <td>${post.group_status ? window.lodash.escape(post.group_status.label) : ''}</td>
                    <td class="last-update"><?php esc_html_e( "Updated", 'disciple_tools' ) ?>: ${window.lodash.escape(post.last_modified.formatted)}</td>
                </tr>`
            }
        </script>
        <?php
        return true;
    }
}

Disciple_Tools_Magic_Links_Magic_User_Groups_App::instance();
