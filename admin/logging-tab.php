<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Disciple_Tools_Bulk_Magic_Link_Sender_Tab_Logging
 */
class Disciple_Tools_Bulk_Magic_Link_Sender_Tab_Logging {
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
                <th>Logging</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_display_logging(); ?>
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

    public function main_column_display_logging() {
        ?>
        <table class="widefat striped">
            <thead>
            <tr>
                <th style="vertical-align: middle; text-align: left; min-width: 150px;">Timestamp</th>
                <th style="vertical-align: middle; text-align: left;">Log</th>
            </tr>
            </thead>
            <?php
            $logging = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_logging );
            $logs    = ! empty( $logging ) ? json_decode( $logging ) : [];
            if ( ! empty( $logs ) ) {
                $counter = 0;
                $limit   = 500;
                for ( $x = count( $logs ) - 1; $x > 0; $x-- ) {
                    if ( ++$counter <= $limit ) {
                        echo '<tr>';
                        echo '<td style="vertical-align: middle; text-align: left; min-width: 150px;">' . esc_attr( Disciple_Tools_Bulk_Magic_Link_Sender_API::format_timestamp_in_local_time_zone( $logs[ $x ]->timestamp ) ) . '</td>';
                        echo '<td style="vertical-align: middle; text-align: left;">' . esc_attr( $logs[ $x ]->log ) . '</td>';
                        echo '</td>';
                        echo '</tr>';
                    }
                }
            }
            ?>
        </table>
        <?php
    }
}
