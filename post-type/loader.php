<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Test that DT_Module_Base has loaded
 */
if ( ! class_exists( 'DT_Module_Base' ) ) {
    dt_write_log( 'Disciple Tools System not loaded. Cannot load custom post type.' );
    return;
}

/**
 * Add any modules required or added for the post type
 */
add_filter( 'dt_post_type_modules', function( $modules ){

    /**
     * @todo Update the starter in the array below 'magic_links_base'. Follow the pattern.
     * @todo Add more modules by adding a new array element. i.e. 'magic_links_base_two'.
     */
    $modules["magic_links_base"] = [
        "name" => "Magic Links",
        "enabled" => true,
        "locked" => true,
        "prerequisites" => [ "contacts_base" ],
        "post_type" => "magic_links",
        "description" => "Default magic links functionality"
    ];

    return $modules;
}, 20, 1 );

require_once 'module-base.php';
Disciple_Tools_Magic_Links_Base::instance();

/**
 * @todo require_once and load additional modules
 */
