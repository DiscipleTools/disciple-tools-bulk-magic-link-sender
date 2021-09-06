<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Class Disciple_Tools_Plugin_Starter_Template_Settings_Tile
 *
 * This class will add navigation and a custom section to the Settings page in Disciple Tools.
 * The dt_profile_settings_page_menu function adds a navigation link to the bottom of the nav section in Settings.
 * The dt_profile_settings_page_sections function adds a custom content tile to the bottom of the page.
 *
 * It is likely modifications through this section will leverage a custom REST end point to process changes.
 * @see /rest-api/ in this plugin for a custom REST endpoint
 */

class Disciple_Tools_Plugin_Starter_Template_Settings_Tile
{
    private static $_instance = null;
    public static function instance() {
        if (is_null( self::$_instance )) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        if ( 'settings' === dt_get_url_path() ) {
            add_action( 'dt_profile_settings_page_menu', [ $this, 'dt_profile_settings_page_menu' ], 100, 4 );
            add_action( 'dt_profile_settings_page_sections', [ $this, 'dt_profile_settings_page_sections' ], 100, 4 );
            add_action( 'dt_modal_help_text', [ $this, 'dt_modal_help_text' ], 100 );
        }
    }

    /**
     * Adds menu item
     *
     * @param $dt_user WP_User object
     * @param $dt_user_meta array Full array of user meta data
     * @param $dt_user_contact_id bool/int returns either id for contact connected to user or false
     * @param $contact_fields array Array of fields on the contact record
     */
    public function dt_profile_settings_page_menu( $dt_user, $dt_user_meta, $dt_user_contact_id, $contact_fields ) {
        ?>
        <li><a href="#disciple_tools_plugin_starter_template_settings_id"><?php esc_html_e( 'Custom Settings Section', 'disciple-tools-plugin-starter-template' )?></a></li>
        <?php
    }

    /**
     * Adds custom tile
     *
     * @param $dt_user WP_User object
     * @param $dt_user_meta array Full array of user meta data
     * @param $dt_user_contact_id bool/int returns either id for contact connected to user or false
     * @param $contact_fields array Array of fields on the contact record
     */
    public function dt_profile_settings_page_sections( $dt_user, $dt_user_meta, $dt_user_contact_id, $contact_fields ) {
        ?>
        <div class="cell bordered-box" id="disciple_tools_plugin_starter_template_settings_id" data-magellan-target="disciple_tools_plugin_starter_template_settings_id">
            <button class="help-button float-right" data-section="disciple-tools-plugin-starter-template-help-text">
                <img class="help-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>"/>
            </button>
            <span class="section-header"><?php esc_html_e( 'Custom Settings Section', 'disciple-tools-plugin-starter-template' )?></span>
            <hr/>

            <!-- replace with your custom content -->
            <p>Replace with your custom content</p>

        </div>
        <?php
    }

    /**
     * @see disciple-tools-theme/dt-assets/parts/modals/modal-help.php
     */
    public function dt_modal_help_text(){
        ?>
        <div class="help-section" id="disciple-tools-plugin-starter-template-help-text" style="display: none">
            <h3><?php echo esc_html_x( "Custom Settings Section", 'Optional Documentation', 'disciple-tools-plugin-starter-template' ) ?></h3>
            <p><?php echo esc_html_x( "Add your own help information into this modal.", 'Optional Documentation', 'disciple-tools-plugin-starter-template' ) ?></p>
        </div>
        <?php
    }
}

Disciple_Tools_Plugin_Starter_Template_Settings_Tile::instance();
