<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly
/**
 * Configures the site link system for the network reporting
 */

class Disciple_Tools_Plugin_Starter_Template_Site_Links {
    public $type = 'disciple_tools_plugin_starter_template';

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        // Add the specific capabilities needed for the site to site linking.
        add_filter( 'site_link_type_capabilities', [ $this, 'site_link_capabilities' ], 10, 1 );

        // Adds the type to the site link system
        add_filter( 'site_link_type', [ $this, 'site_link_type' ], 10, 1 );
    }

    public function site_link_capabilities( $args ) {
        if ( $this->type === $args['connection_type'] ) {
            $args['capabilities'][] = 'create_' . $this->type;
            $args['capabilities'][] = 'update_any_' . $this->type;
            // @todo add other capabilities here
        }
        return $args;
    }

    public function site_link_type( $type ) {
        $type[$this->type] = __( 'Plugin Starter Template' );
        return $type;
    }
}
Disciple_Tools_Plugin_Starter_Template_Site_Links::instance();

