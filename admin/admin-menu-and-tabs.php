<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Disciple_Tools_Magic_Links_Menu
 */
class Disciple_Tools_Magic_Links_Menu {

    public $token = 'disciple_tools_magic_links';

    private static $_instance = null;

    /**
     * Disciple_Tools_Magic_Links_Menu Instance
     *
     * Ensures only one instance of Disciple_Tools_Magic_Links_Menu is loaded or can be loaded.
     *
     * @return Disciple_Tools_Magic_Links_Menu instance
     * @since 0.1.0
     * @static
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // End instance()


    /**
     * Constructor function.
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {

        add_action( "admin_menu", array( $this, "register_menu" ) );

    } // End __construct()


    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        add_submenu_page( 'dt_extensions', 'Magic Links', 'Magic Links', 'manage_dt', $this->token, [
            $this,
            'content'
        ] );
    }

    /**
     * Menu stub. Replaced when Disciple Tools Theme fully loads.
     */
    public function extensions_menu() {
    }

    /**
     * Builds page contents
     * @since 0.1
     */
    public function content() {

        if ( ! current_user_can( 'manage_dt' ) ) { // manage dt is a permission that is specific to Disciple Tools and allows admins, strategists and dispatchers into the wp-admin
            wp_die( 'You do not have sufficient permissions to access this page.' );
        }

        if ( isset( $_GET["tab"] ) ) {
            $tab = sanitize_key( wp_unslash( $_GET["tab"] ) );
        } else {
            $tab = 'general';
        }

        $link = 'admin.php?page=' . $this->token . '&tab=';

        ?>
        <div class="wrap">
            <h2>DISCIPLE TOOLS : MAGIC LINKS</h2>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_attr( $link ) . 'general' ?>"
                   class="nav-tab <?php echo esc_html( ( $tab == 'general' || ! isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>">General</a>
                <a href="<?php echo esc_attr( $link ) . 'links' ?>"
                   class="nav-tab <?php echo esc_html( ( $tab == 'links' || ! isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>">Links</a>
                <a href="<?php echo esc_attr( $link ) . 'logging' ?>"
                   class="nav-tab <?php echo esc_html( ( $tab == 'logging' || ! isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>">Logging</a>
                <a href="<?php echo esc_attr( $link ) . 'reporting' ?>"
                   class="nav-tab <?php echo esc_html( ( $tab == 'reporting' || ! isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>">Reporting</a>
            </h2>

            <?php
            switch ( $tab ) {
                case "general":
                    require( 'general-tab.php' );
                    $object = new Disciple_Tools_Magic_Links_Tab_General();
                    $object->content();
                    break;
                case "links":
                    require( 'links-tab.php' );
                    $object = new Disciple_Tools_Magic_Links_Tab_Links();
                    $object->content();
                    break;
                case "logging":
                    require( 'logging-tab.php' );
                    $object = new Disciple_Tools_Magic_Links_Tab_Logging();
                    $object->content();
                    break;
                case "reporting":
                    require( 'reporting-tab.php' );
                    $object = new Disciple_Tools_Magic_Links_Tab_Reporting();
                    $object->content();
                    break;
                default:
                    break;
            }
            ?>

        </div><!-- End wrap -->

        <?php
    }
}

Disciple_Tools_Magic_Links_Menu::instance();
