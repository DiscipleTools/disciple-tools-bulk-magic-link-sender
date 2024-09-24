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
        add_action( 'after_setup_theme', function () {
            self::load_templates();
        }, 200 );
    }

    private function load_templates() {
        foreach ( fetch_magic_link_templates() ?? [] as $template ) {
            do_action( 'dt_magic_link_template_load', $template );
        }
    }
}

Disciple_Tools_Magic_Links_Templates_Loader::instance();
