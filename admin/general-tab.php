<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Disciple_Tools_Bulk_Magic_Link_Sender_Tab_General
 */
class Disciple_Tools_Bulk_Magic_Link_Sender_Tab_General {

    public function __construct() {

        // Handle update submissions
        $this->process_updates();

        // Load scripts and styles
        $this->process_scripts();
    }

    private function process_scripts() {
        wp_enqueue_script( 'dt_magic_links_general_script', plugin_dir_url( __FILE__ ) . 'js/general-tab.js', [
            'jquery',
            'lodash'
        ], filemtime( dirname( __FILE__ ) . '/js/general-tab.js' ), true );

        wp_localize_script(
            'dt_magic_links_general_script', 'dt_magic_links', array(
                'dt_xyz' => ''
            )
        );
    }

    private function process_updates() {
        if ( isset( $_POST['ml_general_main_col_general_form_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ml_general_main_col_general_form_nonce'] ) ), 'ml_general_main_col_general_form_nonce' ) ) {
            if ( isset( $_POST['ml_general_main_col_general_form_global_name'] ) ) {
                $global_name = filter_var( wp_unslash( $_POST['ml_general_main_col_general_form_global_name'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES );
                Disciple_Tools_Bulk_Magic_Link_Sender_API::update_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_global_name, $global_name );
            }

            if ( isset( $_POST['ml_general_main_col_general_form_global_name_enabled'] ) ) {
                $global_name_enabled = filter_var( wp_unslash( $_POST['ml_general_main_col_general_form_global_name_enabled'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES );
                Disciple_Tools_Bulk_Magic_Link_Sender_API::update_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_global_name_enabled, $global_name_enabled );
            }

            if ( isset( $_POST['ml_general_main_col_general_form_all_scheduling_enabled'] ) ) {
                $all_scheduling_enabled = filter_var( wp_unslash( $_POST['ml_general_main_col_general_form_all_scheduling_enabled'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES );
                Disciple_Tools_Bulk_Magic_Link_Sender_API::update_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_all_scheduling_enabled, $all_scheduling_enabled );
            }

            if ( isset( $_POST['ml_general_main_col_general_form_all_channels_enabled'] ) ) {
                $all_channels_enabled = filter_var( wp_unslash( $_POST['ml_general_main_col_general_form_all_channels_enabled'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES );
                Disciple_Tools_Bulk_Magic_Link_Sender_API::update_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_all_channels_enabled, $all_channels_enabled );
            }

            if ( isset( $_POST['ml_general_main_col_general_form_default_time_zone'] ) ) {
                $default_time_zone = filter_var( wp_unslash( $_POST['ml_general_main_col_general_form_default_time_zone'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES );
                Disciple_Tools_Bulk_Magic_Link_Sender_API::update_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_local_time_zone, $default_time_zone );
            }
        }

        // As functionality is now hidden, force to an enabled state, using default server.
        $email_option = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_defaults_email );
        if ( !empty( $email_option ) ) {
            $email_obj = json_decode( $email_option, true );

            if ( !isset( $email_obj['enabled'] ) || ( isset( $email_obj['enabled'] ) && !$email_obj['enabled'] ) ) {
                $email_obj['enabled'] = true;
                $email_obj['use_default_server'] = true;
                Disciple_Tools_Bulk_Magic_Link_Sender_API::update_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_defaults_email, json_encode( $email_obj ) );
            }
        }
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

                        <?php $this->right_column() ?>

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
        <table class="widefat striped" id="ml_general_main_col_general">
            <thead>
            <tr>
                <th>General</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_general(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->

        <!-- Box -->
        <table class="widefat striped" id="ml_general_main_col_summary">
            <thead>
            <tr>
                <th>Scheduling Summary</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_summary(); ?>
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
        <table style="display: none;" id="ml_general_right_docs_section" class="widefat striped">
            <thead>
            <tr>
                <th id="ml_general_right_docs_title"></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td id="ml_general_right_docs_content"></td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php

        // Include helper documentation
        include 'general-tab-docs.php';
    }

    private function main_column_general() {
        ?>
        <table class="widefat striped">
            <tr>
                <td>
                    Magic Link Documentation
                </td>
                <td>
                    <a href='https://disciple.tools/user-docs/magic-links/' target='_blank'>Magic Links <img style="height: 20px" class="dt-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>"/></a>
                </td>
            </tr>
            <tr>
                <td>
                    Global Name [<a href="#" class="ml-general-docs"
                                    data-title="ml_general_right_docs_global_name_title"
                                    data-content="ml_general_right_docs_global_name_content">&#63;</a>]
                </td>
                <td>
                    <?php
                    $global_name_enabled_checked_html = 'checked';
                    if ( ! Disciple_Tools_Bulk_Magic_Link_Sender_API::option_exists( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_global_name_enabled ) ) {
                        // Ensure initial setup setting, is enabled by default
                        Disciple_Tools_Bulk_Magic_Link_Sender_API::update_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_global_name_enabled, '1' );
                    } else {
                        $global_name_enabled_checked_html = boolval( Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_global_name_enabled ) ) ? 'checked' : '';
                    }
                    $global_name_value = get_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_global_name, '' );
                    $global_name_disabled_html = ( $global_name_enabled_checked_html !== 'checked' ? 'disabled' : '' );
                    ?>
                    <input style="min-width: 70%;"
                           type="text"
                           id="ml_general_main_col_general_global_name"
                           value="<?php echo esc_attr( $global_name_value ); ?>"
                        <?php echo esc_attr( $global_name_disabled_html ); ?>
                    />

                    <input type="checkbox"
                           id="ml_general_main_col_general_global_name_enabled"
                           value=""
                        <?php echo esc_attr( $global_name_enabled_checked_html ); ?>
                    /> Enabled
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">All Scheduling Enabled [<a href="#" class="ml-general-docs"
                                                                               data-title="ml_general_right_docs_all_scheduling_enabled_title"
                                                                               data-content="ml_general_right_docs_all_scheduling_enabled_content">&#63;</a>]
                </td>
                <td>
                    <?php
                    $all_scheduling_checked_html = 'checked';
                    if ( ! Disciple_Tools_Bulk_Magic_Link_Sender_API::option_exists( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_all_scheduling_enabled ) ) {
                        // Ensure initial setup setting, is enabled by default
                        Disciple_Tools_Bulk_Magic_Link_Sender_API::update_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_all_scheduling_enabled, '1' );
                    } else {
                        $all_scheduling_checked_html = boolval( Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_all_scheduling_enabled ) ) ? 'checked' : '';
                    }
                    ?>
                    <input type="checkbox"
                           id="ml_general_main_col_general_all_scheduling_enabled" <?php echo esc_attr( $all_scheduling_checked_html ); ?>/>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">All Sending Channels Enabled [<a href="#" class="ml-general-docs"
                                                                                     data-title="ml_general_right_docs_all_send_channels_enabled_title"
                                                                                     data-content="ml_general_right_docs_all_send_channels_enabled_content">&#63;</a>]
                </td>
                <td>
                    <?php
                    $all_channels_checked_html = 'checked';
                    if ( ! Disciple_Tools_Bulk_Magic_Link_Sender_API::option_exists( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_all_channels_enabled ) ) {
                        // Ensure initial setup setting, is enabled by default
                        Disciple_Tools_Bulk_Magic_Link_Sender_API::update_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_all_channels_enabled, '1' );
                    } else {
                        $all_channels_checked_html = boolval( Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_all_channels_enabled ) ) ? 'checked' : '';
                    }
                    ?>
                    <input type="checkbox"
                           id="ml_general_main_col_general_all_channels_enabled" <?php echo esc_attr( $all_channels_checked_html ); ?>/>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Default Time Zone</td>
                <td>
                    <select id="ml_general_main_col_general_default_time_zone">
                        <?php
                        $option            = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_local_time_zone );
                        $default_time_zone = ! empty( $option ) ? $option : 'UTC';
                        foreach ( Disciple_Tools_Bulk_Magic_Link_Sender_API::list_available_time_zones() as $time_zone ) {
                            $selected = ( $default_time_zone === $time_zone ) ? 'selected' : '';
                            ?>
                            <option <?php echo esc_attr( $selected ) ?>
                                value="<?php echo esc_attr( $time_zone ) ?>"><?php echo esc_attr( $time_zone ) ?></option>
                            <?php
                        }
                        ?>
                    </select>
                </td>
            </tr>
        </table>

        <!-- [Submission Form] -->
        <form method="post" id="ml_general_main_col_general_form">
            <input type="hidden" id="ml_general_main_col_general_form_nonce"
                   name="ml_general_main_col_general_form_nonce"
                   value="<?php echo esc_attr( wp_create_nonce( 'ml_general_main_col_general_form_nonce' ) ) ?>"/>

            <input type="hidden" id="ml_general_main_col_general_form_global_name"
                   name="ml_general_main_col_general_form_global_name" value=""/>

            <input type="hidden" id="ml_general_main_col_general_form_global_name_enabled"
                   name="ml_general_main_col_general_form_global_name_enabled" value=""/>

            <input type="hidden" id="ml_general_main_col_general_form_all_scheduling_enabled"
                   name="ml_general_main_col_general_form_all_scheduling_enabled" value=""/>

            <input type="hidden" id="ml_general_main_col_general_form_all_channels_enabled"
                   name="ml_general_main_col_general_form_all_channels_enabled" value=""/>

            <input type="hidden" id="ml_general_main_col_general_form_default_time_zone"
                   name="ml_general_main_col_general_form_default_time_zone" value=""/>
        </form>

        <br>
        <span style="float:right;">
            <button type="submit" id="ml_general_main_col_general_update_but"
                    class="button float-right"><?php esc_html_e( 'Update', 'disciple_tools' ) ?></button>
        </span>
        <?php
    }

    private function main_column_summary() {
        ?>
        <table class="widefat striped" id="ml_general_main_col_summary_table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Last Cron Run</th>
                <th>Last Scheduled Run</th>
                <th>Last Successful Send</th>
                <th>Next Scheduled Run</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $link_objs = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_objs();
            if ( ! empty( $link_objs ) ) {
                foreach ( $link_objs as $id => $link_obj ) {
                    if ( $link_obj->enabled ) {

                        $last_cron_run      = Disciple_Tools_Bulk_Magic_Link_Sender_API::format_timestamp_in_local_time_zone( Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_last_cron_run ) );
                        $last_scheduled_run = !empty( $link_obj->schedule->last_schedule_run ) ? ( ( $link_obj->schedule->last_schedule_run > time() ) ? 'Manual Scheduling Detected' : Disciple_Tools_Bulk_Magic_Link_Sender_API::format_timestamp_in_local_time_zone( $link_obj->schedule->last_schedule_run ) ) : '---';
                        $last_success_send  = ! empty( $link_obj->schedule->last_success_send ) ? Disciple_Tools_Bulk_Magic_Link_Sender_API::format_timestamp_in_local_time_zone( $link_obj->schedule->last_success_send ) : '---';
                        $next_scheduled_run = '---';

                        if ( ( isset( $link_obj->schedule->enabled ) && $link_obj->schedule->enabled ) && ! empty( $link_obj->schedule->freq_amount ) && ! empty( $link_obj->schedule->freq_time_unit ) && ! empty( $link_obj->schedule->last_schedule_run ) ){
                            $next_run = strtotime( '+' . $link_obj->schedule->freq_amount . ' ' . $link_obj->schedule->freq_time_unit, $link_obj->schedule->last_schedule_run );

                            // Ensure next run is in the future.
                            if ( $next_run > time() ){
                                $next_scheduled_run = Disciple_Tools_Bulk_Magic_Link_Sender_API::format_timestamp_in_local_time_zone( $next_run );
                            }
                        }

                        ?>
                        <tr>
                            <td><?php echo esc_attr( $link_obj->name ); ?></td>
                            <td><?php echo esc_attr( $last_cron_run ); ?></td>
                            <td><?php echo esc_attr( $last_scheduled_run ); ?></td>
                            <td><?php echo esc_attr( $last_success_send ); ?></td>
                            <td><?php echo esc_attr( $next_scheduled_run ); ?></td>
                        </tr>
                        <?php
                    }
                }
            }
            ?>
            </tbody>
        </table>
        <?php
    }
}
