<?php
/**
 * Plugin Name: Disciple Tools - Bulk Magic Link Sender
 * Plugin URI: https://github.com/DiscipleTools/disciple-tools-bulk-magic-link-sender
 * Description: Disciple Tools - Bulk magic link sender for users, contacts, groups and teams assignment + schedule management for magic links dispatching over configured sending channels.
 * Text Domain: disciple-tools-bulk-magic-link-sender
 * Domain Path: /languages
 * Version:  1.6
 * Author URI: https://github.com/DiscipleTools
 * GitHub Plugin URI: https://github.com/DiscipleTools/disciple-tools-bulk-magic-link-sender
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 5.6
 *
 * @package Disciple_Tools
 * @link    https://github.com/DiscipleTools
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Gets the instance of the `Disciple_Tools_Bulk_Magic_Link_Sender` class.
 *
 * @return object|bool
 * @since  0.1
 * @access public
 */
function disciple_tools_bulk_magic_link_sender() {
    $disciple_tools_magic_links_required_dt_theme_version = '1.20';
    $wp_theme                                             = wp_get_theme();
    $version                                              = $wp_theme->version;

    /*
     * Check if the Disciple.Tools theme is loaded and is the latest required version
     */
    $is_theme_dt = class_exists( "Disciple_Tools" );
    if ( $is_theme_dt && version_compare( $version, $disciple_tools_magic_links_required_dt_theme_version, "<" ) ) {
        add_action( 'admin_notices', 'disciple_tools_magic_links_hook_admin_notice' );
        add_action( 'wp_ajax_dismissed_notice_handler', 'dt_hook_ajax_notice_handler' );

        return false;
    }
    if ( ! $is_theme_dt ) {
        return false;
    }
    /**
     * Load useful function from the theme
     */
    if ( ! defined( 'DT_FUNCTIONS_READY' ) ) {
        require_once get_template_directory() . '/dt-core/global-functions.php';
    }

    return Disciple_Tools_Bulk_Magic_Link_Sender::instance();

}

add_action( 'after_setup_theme', 'disciple_tools_bulk_magic_link_sender', 20 );

/**
 * Singleton class for setting up the plugin.
 *
 * @since  0.1
 * @access public
 */
class Disciple_Tools_Bulk_Magic_Link_Sender {

    private static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    private function __construct() {
        $is_rest = dt_is_rest();
        /**
         * @todo Decide if you want to use the REST API example
         * To remove: delete this following line and remove the folder named /rest-api
         */
        if ( $is_rest && strpos( dt_get_url_path(), 'disciple_tools_magic_links' ) !== false ) {
            require_once( 'rest-api/rest-api.php' ); // adds starter rest api class
        }

        /**
         * @todo Decide if you want to create a magic link
         * To remove: delete the line below and remove the folder named /magic-link
         */
        require_once( 'magic-link/magic-link-user-app.php' );
        require_once( 'magic-link/magic-links-api.php' );
        require_once( 'magic-link/magic-links-cron.php' );
        require_once( 'magic-link/magic-links-default-filters.php' );

        /**
         * @todo Decide if you want to add a custom admin page in the admin area
         * To remove: delete the 3 lines below and remove the folder named /admin
         */
        if ( is_admin() ) {
            require_once( 'admin/admin-menu-and-tabs.php' ); // adds starter admin page and section for plugin
        }

        /**
         * @todo Decide if you want to support localization of your plugin
         * To remove: delete the line below and remove the folder named /languages
         */
        $this->i18n();

        /**
         * @todo Decide if you want to customize links for your plugin in the plugin admin area
         * To remove: delete the lines below and remove the function named
         */
        if ( is_admin() ) { // adds links to the plugin description area in the plugin admin list.
            add_filter( 'plugin_row_meta', [ $this, 'plugin_description_links' ], 10, 4 );
        }

    }

    /**
     * Filters the array of row meta for each/specific plugin in the Plugins list table.
     * Appends additional links below each/specific plugin on the plugins page.
     */
    public function plugin_description_links( $links_array, $plugin_file_name, $plugin_data, $status ) {
        if ( strpos( $plugin_file_name, basename( __FILE__ ) ) ) {
            // You can still use `array_unshift()` to add links at the beginning.

            $links_array[] = '<a href="https://disciple.tools">Disciple.Tools Community</a>'; // @todo replace with your links.
            // @todo add other links here
        }

        return $links_array;
    }

    /**
     * Method that runs only when the plugin is activated.
     *
     * @return void
     * @since  0.1
     * @access public
     */
    public static function activation() {
        // add elements here that need to fire on activation
    }

    /**
     * Method that runs only when the plugin is deactivated.
     *
     * @return void
     * @since  0.1
     * @access public
     */
    public static function deactivation() {
        // add functions here that need to happen on deactivation
        delete_option( 'dismissed-disciple-tools-bulk-magic-link-sender' );
    }

    /**
     * Loads the translation files.
     *
     * @return void
     * @since  0.1
     * @access public
     */
    public function i18n() {
        $domain = 'disciple_tools_bulk_magic_link_sender';
        load_plugin_textdomain( $domain, false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) . 'languages' );
    }

    /**
     * Magic method to output a string if trying to use the object as a string.
     *
     * @return string
     * @since  0.1
     * @access public
     */
    public function __toString() {
        return 'disciple-tools-bulk-magic-link-sender';
    }

    /**
     * Magic method to keep the object from being cloned.
     *
     * @return void
     * @since  0.1
     * @access public
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to keep the object from being unserialized.
     *
     * @return void
     * @since  0.1
     * @access public
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to prevent a fatal error when calling a method that doesn't exist.
     *
     * @param string $method
     * @param array $args
     *
     * @return null
     * @since  0.1
     * @access public
     */
    public function __call( $method = '', $args = array() ) {
        _doing_it_wrong( "disciple_tools_magic_links::" . esc_html( $method ), 'Method does not exist.', '0.1' );
        unset( $method, $args );

        return null;
    }
}


// Register activation hook.
register_activation_hook( __FILE__, [ 'Disciple_Tools_Bulk_Magic_Link_Sender', 'activation' ] );
register_deactivation_hook( __FILE__, [ 'Disciple_Tools_Bulk_Magic_Link_Sender', 'deactivation' ] );


if ( ! function_exists( 'disciple_tools_magic_links_hook_admin_notice' ) ) {
    function disciple_tools_magic_links_hook_admin_notice() {
        global $disciple_tools_magic_links_required_dt_theme_version;
        $wp_theme        = wp_get_theme();
        $current_version = $wp_theme->version;
        $message         = "'Disciple Tools - Bulk Magic Link Sender' plugin requires 'Disciple Tools' theme to work. Please activate 'Disciple Tools' theme or make sure it is latest version.";
        if ( $wp_theme->get_template() === "disciple-tools-theme" ) {
            $message .= ' ' . sprintf( esc_html( 'Current Disciple Tools version: %1$s, required version: %2$s' ), esc_html( $current_version ), esc_html( $disciple_tools_magic_links_required_dt_theme_version ) );
        }
        // Check if it's been dismissed...
        if ( ! get_option( 'dismissed-disciple-tools-bulk-magic-link-sender', false ) ) { ?>
            <div class="notice notice-error notice-disciple-tools-bulk-magic-link-sender is-dismissible"
                 data-notice="disciple-tools-bulk-magic-link-sender">
                <p><?php echo esc_html( $message ); ?></p>
            </div>
            <script>
                jQuery(function ($) {
                    $(document).on('click', '.notice-disciple-tools-bulk-magic-link-sender .notice-dismiss', function () {
                        $.ajax(ajaxurl, {
                            type: 'POST',
                            data: {
                                action: 'dismissed_notice_handler',
                                type: 'disciple-tools-bulk-magic-link-sender',
                                security: '<?php echo esc_html( wp_create_nonce( 'wp_rest_dismiss' ) ) ?>'
                            }
                        })
                    });
                });
            </script>
        <?php }
    }
}

/**
 * AJAX handler to store the state of dismissible notices.
 */
if ( ! function_exists( "dt_hook_ajax_notice_handler" ) ) {
    function dt_hook_ajax_notice_handler() {
        check_ajax_referer( 'wp_rest_dismiss', 'security' );
        if ( isset( $_POST["type"] ) ) {
            $type = sanitize_text_field( wp_unslash( $_POST["type"] ) );
            update_option( 'dismissed-' . $type, true );
        }
    }
}

/**
 * Plugin Releases and updates
 * @todo Uncomment and change the url if you want to support remote plugin updating with new versions of your plugin
 * To remove: delete the section of code below and delete the file called version-control.json in the plugin root
 *
 * This section runs the remote plugin updating service, so you can issue distributed updates to your plugin
 *
 * @note See the instructions for version updating to understand the steps involved.
 * @link https://github.com/DiscipleTools/disciple-tools-bulk-magic-link-sender/wiki/Configuring-Remote-Updating-System
 *
 * @todo Enable this section with your own hosted file
 * @todo An example of this file can be found in (version-control.json)
 * @todo Github is a good option for delivering static json.
 */
/**
 * Check for plugin updates even when the active theme is not Disciple.Tools
 *
 * Below is the publicly hosted .json file that carries the version information. This file can be hosted
 * anywhere as long as it is publicly accessible. You can download the version file listed below and use it as
 * a template.
 * Also, see the instructions for version updating to understand the steps involved.
 * @see https://github.com/DiscipleTools/disciple-tools-version-control/wiki/How-to-Update-the-Starter-Plugin
 */
add_action( 'plugins_loaded', function () {
    if ( is_admin() && ! ( is_multisite() && class_exists( "DT_Multisite" ) ) || wp_doing_cron() ) {
        // Check for plugin updates
        if ( ! class_exists( 'Puc_v4_Factory' ) ) {
            if ( file_exists( get_template_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' ) ) {
                require( get_template_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' );
            }
        }
        if ( class_exists( 'Puc_v4_Factory' ) ) {
            Puc_v4_Factory::buildUpdateChecker(
                'https://raw.githubusercontent.com/DiscipleTools/disciple-tools-bulk-magic-link-sender/master/version-control.json',
                __FILE__,
                'disciple-tools-bulk-magic-link-sender'
            );

        }
    }
} );

/**
 * Require plugins with the TGM library.
 *
 * This defines the required and suggested plugins.
 */
add_action( 'tgmpa_register', function () {
    /**
     * Array of plugin arrays. Required keys are name and slug.
     * If the source is NOT from the .org repo, then source is also required.
     */
    $plugins = [
        [
            'name'     => 'Disciple Tools - Channels - Twilio',
            'slug'     => 'disciple-tools-channels-twilio',
            'source'   => 'https://github.com/DiscipleTools/disciple-tools-channels-twilio/releases/latest/download/disciple-tools-channels-twilio.zip',
            'required' => false,
            'version'  => '1.1'
        ]
    ];
    /**
     * Array of configuration settings. Amend each line as needed.
     *
     * Only uncomment the strings in the config array if you want to customize the strings.
     */
    $config = [
        'id'           => 'dt_magic_links',
        // Unique ID for hashing notices for multiple instances of TGMPA.
        'default_path' => '/includes/plugins/',
        // Default absolute path to bundled plugins.
        'menu'         => 'tgmpa-install-plugins',
        // Menu slug.
        'parent_slug'  => 'plugins.php',
        // Parent menu slug.
        'capability'   => 'manage_options',
        // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
        'has_notices'  => true,
        // Show admin notices or not.
        'dismissable'  => true,
        // If false, a user cannot dismiss the nag message.
        'dismiss_msg'  => 'These are recommended plugins to complement your disciple tools bulk magic link sender plugin.',
        // If 'dismissable' is false, this message will be output at top of nag.
        'is_automatic' => true,
        // Automatically activate plugins after installation or not.
        'message'      => '',
        // Message to output right before the plugins table.
    ];

    tgmpa( $plugins, $config );

} );
