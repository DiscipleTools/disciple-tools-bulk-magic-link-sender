<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Disciple_Tools_Bulk_Magic_Link_Sender_Tab_Reporting
 */
class Disciple_Tools_Bulk_Magic_Link_Sender_Tab_Reporting {

    public function __construct() {

        // Load scripts and styles
        $this->process_scripts();

    }

    private function process_scripts() {

        wp_register_script( 'amcharts-core', 'https://cdn.amcharts.com/lib/4/core.js', false, '4' );
        wp_register_script( 'amcharts-charts', 'https://cdn.amcharts.com/lib/4/charts.js', false, '4' );
        wp_register_script( 'amcharts-themes-animated', 'https://cdn.amcharts.com/lib/4/themes/animated.js', false, '4' );

        wp_enqueue_script( 'dt_magic_reporting_script', plugin_dir_url( __FILE__ ) . 'js/reporting-tab.js', [
            'jquery',
            'lodash',
            'moment',
            'amcharts-core',
            'amcharts-charts',
            'amcharts-themes-animated'
        ], filemtime( dirname( __FILE__ ) . '/js/reporting-tab.js' ), true );

        wp_localize_script(
            "dt_magic_reporting_script", "dt_magic_links", array(
                'dt_endpoint_report' => Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_endpoint_report_url()
            )
        );
    }

    public function content() {
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main_column() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Right Column -->

                        <?php /*$this->right_column()*/ ?>

                        <!-- End Right Column -->
                    </div><!-- postbox-container 1 -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function main_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Available Reports</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_available_reports(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <!-- Box -->
        <table id="ml_main_col_report_section" style="display: none;" class="widefat striped">
            <thead>
            <tr>
                <th id="ml_main_col_report_section_title"></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <div id="ml_main_col_report_section_display"></div>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    public function right_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Information</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    Content
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    private function main_column_available_reports() {
        ?>
        <select style="min-width: 100%;" id="ml_main_col_available_reports_select">
            <option disabled selected value>-- select available report --</option>
            <option value="sent-vs-updated">Sent Messages vs Updated Contact Records</option>
        </select>
        <?php
    }
}
