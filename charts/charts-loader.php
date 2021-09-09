<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Disciple_Tools_Magic_Links_Charts
{
    private static $_instance = null;
    public static function instance(){
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct(){

        require_once( 'one-page-chart-template.php' );
        new Disciple_Tools_Magic_Links_Chart_Template();

        /**
         * @todo add other charts like the pattern above here
         */

    } // End __construct
}
Disciple_Tools_Magic_Links_Charts::instance();
