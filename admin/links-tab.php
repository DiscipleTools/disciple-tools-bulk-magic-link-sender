<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Disciple_Tools_Magic_Links_Tab_Links
 */
class Disciple_Tools_Magic_Links_Tab_Links {

    public function __construct() {

        // Handle update submissions
        $this->process_updates();

        // Load scripts and styles
        $this->process_scripts();

    }

    private function process_scripts() {
        wp_register_style( 'daterangepicker-css', 'https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css', [], '3.1.0' );
        wp_enqueue_style( 'daterangepicker-css' );
        wp_enqueue_script( 'daterangepicker-js', 'https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js', [ 'moment' ], '3.1.0', true );

        wp_enqueue_script( 'dt_magic_links_script', plugin_dir_url( __FILE__ ) . 'js/links-tab.js', [
            'jquery',
            'lodash',
            'moment',
            'daterangepicker-js'
        ], filemtime( dirname( __FILE__ ) . '/js/links-tab.js' ), true );

        wp_localize_script(
            "dt_magic_links_script", "dt_magic_links", array(
                'dt_magic_link_types'           => Disciple_Tools_Magic_Links_API::fetch_magic_link_types(),
                'dt_users'                      => Disciple_Tools_Magic_Links_API::fetch_dt_users(),
                'dt_teams'                      => Disciple_Tools_Magic_Links_API::fetch_dt_teams(),
                'dt_groups'                     => Disciple_Tools_Magic_Links_API::fetch_dt_groups(),
                'dt_magic_link_objects'         => Disciple_Tools_Magic_Links_API::fetch_option_link_objs(),
                'dt_endpoint_send_now'          => Disciple_Tools_Magic_Links_API::fetch_endpoint_send_now_url(),
                'dt_endpoint_user_links_manage' => Disciple_Tools_Magic_Links_API::fetch_endpoint_user_links_manage_url(),
                'dt_default_message'            => Disciple_Tools_Magic_Links_API::fetch_default_send_msg(),
                'dt_default_send_channel_id'    => Disciple_Tools_Magic_Links_API::$channel_email_id
            )
        );
    }

    private function final_post_param_sanitization( $str ) {
        return str_replace( [ '&lt;', '&gt;' ], [ '<', '>' ], $str );
    }

    private function process_updates() {

        if ( isset( $_POST['ml_main_col_update_form_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ml_main_col_update_form_nonce'] ) ), 'ml_main_col_update_form_nonce' ) ) {
            if ( isset( $_POST['ml_main_col_update_form_link_obj'] ) ) {

                // Fetch newly updated link object
                $sanitized_input   = filter_var( wp_unslash( $_POST['ml_main_col_update_form_link_obj'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES );
                $updating_link_obj = json_decode( $this->final_post_param_sanitization( $sanitized_input ) );

                // Ensure we have something to work with
                if ( ! empty( $updating_link_obj ) && isset( $updating_link_obj->id ) ) {

                    // Attempt to locate an existing object with same id
                    $current_link_obj = Disciple_Tools_Magic_Links_API::fetch_option_link_obj( $updating_link_obj->id );

                    // In an attempt to be more surgical, identify and only focus
                    // on the deltas between previously saved and recently updated..!
                    $stale_users = Disciple_Tools_Magic_Links_API::extract_assigned_user_deltas( $current_link_obj->assigned ?? [], $updating_link_obj->assigned ?? [] );
                    $new_users   = Disciple_Tools_Magic_Links_API::extract_assigned_user_deltas( $updating_link_obj->assigned ?? [], $current_link_obj->assigned ?? [] );

                    // If this is the very first update, ensure all new users are identified
                    // and processed accordingly!
                    if ( ! isset( $current_link_obj->id ) && empty( $new_users ) && ! empty( $updating_link_obj->assigned ) ) {
                        $new_users = $updating_link_obj->assigned;
                    }

                    // Refresh user magic links accordingly; stale users to have links removed,
                    // whilst new users are to have links created and assigned.
                    Disciple_Tools_Magic_Links_API::update_magic_links( $current_link_obj->type ?? null, $stale_users, true );
                    Disciple_Tools_Magic_Links_API::update_magic_links( $updating_link_obj->type, $new_users, false );

                    // Save latest updates
                    Disciple_Tools_Magic_Links_API::update_option_link_obj( $updating_link_obj );
                }
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
        <table class="widefat striped" id="ml_main_col_available_link_objs">
            <thead>
            <tr>
                <th>Available Link Objects</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_available_link_objs(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->

        <!-- Box -->
        <table style="display: none;" class="widefat striped" id="ml_main_col_link_objs_manage">
            <thead>
            <tr>
                <th>Link Object Management</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_link_objs_manage(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->

        <!-- Box -->
        <table style="display: none;" class="widefat striped" id="ml_main_col_ml_type_fields">
            <thead>
            <tr>
                <th>Magic Link Type Fields</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_ml_type_fields(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->

        <!-- Box -->
        <table style="display: none;" class="widefat striped" id="ml_main_col_assign_users_teams">
            <thead>
            <tr>
                <th>Assigned Users & Teams [<a href="#" class="ml-links-docs"
                                               data-title="ml_links_right_docs_assign_users_teams_title"
                                               data-content="ml_links_right_docs_assign_users_teams_content">&#63;</a>]
                </th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td colspan="2">
                    <?php $this->main_column_assign_users_teams(); ?>
                </td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
                <td>
                    <button disabled style="max-width: 100%;" type="submit"
                            id="ml_main_col_assign_users_teams_links_but_refresh"
                            class="button float-right"><?php esc_html_e( "Refresh All Links", 'disciple_tools' ) ?></button>

                    <button disabled style="max-width: 100%;" type="submit"
                            id="ml_main_col_assign_users_teams_links_but_delete"
                            class="button float-right"><?php esc_html_e( "Delete All Links", 'disciple_tools' ) ?></button>
                </td>
                <td>
                    <span style="float:right;">
                        <button type="submit" id="ml_main_col_assign_users_teams_update_but"
                                class="button float-right"><?php esc_html_e( "Update", 'disciple_tools' ) ?></button>
                    </span>
                </td>
            </tr>
            </tfoot>
        </table>
        <br>
        <!-- End Box -->

        <!-- Box -->
        <table style="display: none;" class="widefat striped" id="ml_main_col_message">
            <thead>
            <tr>
                <th>Message [<a href="#" class="ml-links-docs"
                                data-title="ml_links_right_docs_message_title"
                                data-content="ml_links_right_docs_message_content">&#63;</a>]
                </th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_message(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->

        <!-- Box -->
        <table style="display: none;" class="widefat striped" id="ml_main_col_schedules">
            <thead>
            <tr>
                <th>Schedule Management</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_schedules(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->

        <!-- [Submission Form] -->
        <form method="post" id="ml_main_col_update_form">
            <input type="hidden" id="ml_main_col_update_form_nonce" name="ml_main_col_update_form_nonce"
                   value="<?php echo esc_attr( wp_create_nonce( 'ml_main_col_update_form_nonce' ) ) ?>"/>

            <input type="hidden" id="ml_main_col_update_form_link_obj"
                   name="ml_main_col_update_form_link_obj" value=""/>
        </form>

        <span style="float:left; display: none; font-weight: bold;" id="ml_main_col_update_msg"></span>

        <span style="float:right;">
            <button style="display: none;" type="submit" id="ml_main_col_update_but"
                    class="button float-right"><?php esc_html_e( "Update", 'disciple_tools' ) ?></button>
        </span>
        <?php
    }

    public function right_column() {
        ?>
        <!-- Box -->
        <table style="display: none;" id="ml_links_right_docs_section" class="widefat striped">
            <thead>
            <tr>
                <th id="ml_links_right_docs_title"></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td id="ml_links_right_docs_content"></td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php

        // Include helper documentation
        include 'links-tab-docs.php';
    }

    private function main_column_available_link_objs() {
        ?>
        <select style="min-width: 80%;" id="ml_main_col_available_link_objs_select">
            <option disabled selected value>-- select available link object --</option>

            <?php
            $option_link_objs = Disciple_Tools_Magic_Links_API::fetch_option_link_objs();
            foreach ( $option_link_objs ?? (object) [] as $id => $obj ) {
                echo '<option value="' . esc_attr( $id ) . '">' . esc_attr( $obj->name ) . '</option>';
            }
            ?>

        </select>

        <span style="float:right;">
            <button id="ml_main_col_available_link_objs_new" type="submit"
                    class="button float-right"><?php esc_html_e( "New", 'disciple_tools' ) ?></button>
        </span>
        <?php
    }

    private function main_column_link_objs_manage() {
        ?>
        <table class="widefat striped">
            <tr>
                <td style="vertical-align: middle;">Enabled</td>
                <td>
                    <input type="checkbox" id="ml_main_col_link_objs_manage_enabled"/>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Name</td>
                <td>
                    <input type="hidden" id="ml_main_col_link_objs_manage_id" value=""/>
                    <input style="min-width: 100%;" type="text" id="ml_main_col_link_objs_manage_name" value=""/>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Object Expires [<a href="#" class="ml-links-docs"
                                                                       data-title="ml_links_right_docs_obj_expires_title"
                                                                       data-content="ml_links_right_docs_obj_expires_content">&#63;</a>]
                </td>
                <td style="vertical-align: middle;">
                    <input type="hidden" id="ml_main_col_link_objs_manage_expires_ts" value=""/>
                    <input style="min-width: 70%;" type="text" id="ml_main_col_link_objs_manage_expires" value=""/>
                    &nbsp;&nbsp;&nbsp;
                    <input type="checkbox" id="ml_main_col_link_objs_manage_expires_never" value=""/> Never Expires
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Magic Link Type [<a href="#" class="ml-links-docs"
                                                                        data-title="ml_links_right_docs_magic_link_type_title"
                                                                        data-content="ml_links_right_docs_magic_link_type_content">&#63;</a>]
                </td>
                <td>
                    <select style="min-width: 100%;" id="ml_main_col_link_objs_manage_type">
                        <option disabled selected value>-- select magic link type to be sent --</option>

                        <?php
                        // Source available magic link types
                        $magic_link_types = Disciple_Tools_Magic_Links_API::fetch_magic_link_types();
                        if ( ! empty( $magic_link_types ) ) {
                            foreach ( $magic_link_types as $type ) {
                                echo '<option value="' . esc_attr( $type['key'] ) . '">' . esc_attr( $type['label'] ) . '</option>';
                            }
                        }
                        ?>

                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    private function main_column_ml_type_fields() {
        ?>
        <table class="widefat striped" id="ml_main_col_ml_type_fields_table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Enabled</th>
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
        <?php
    }

    private function main_column_assign_users_teams() {
        ?>
        <select style="min-width: 90%;" id="ml_main_col_assign_users_teams_select">
            <option disabled selected value>-- select users & teams to receive links --</option>

            <?php
            // Source available dt users
            $dt_users = Disciple_Tools_Magic_Links_API::fetch_dt_users();
            if ( ! empty( $dt_users ) ) {
                echo '<option disabled>-- users --</option>';
                foreach ( $dt_users as $user ) {
                    $value = 'users+' . $user['user_id'];
                    echo '<option value="' . esc_attr( $value ) . '">' . esc_attr( $user['name'] ) . '</option>';
                }
            }
            ?>

            <?php
            // Source available dt teams
            $dt_teams = Disciple_Tools_Magic_Links_API::fetch_dt_teams();
            if ( ! empty( $dt_teams ) ) {
                echo '<option disabled>-- teams --</option>';
                foreach ( $dt_teams as $team ) {
                    $value = 'teams+' . $team['id'];
                    echo '<option value="' . esc_attr( $value ) . '">' . esc_attr( $team['name'] ) . '</option>';
                }
            }
            ?>

            <?php
            // Source available dt groups
            $dt_groups = Disciple_Tools_Magic_Links_API::fetch_dt_groups();
            if ( ! empty( $dt_groups ) ) {
                echo '<option disabled>-- groups --</option>';
                foreach ( $dt_groups as $group ) {
                    $value = 'groups+' . $group['id'];
                    echo '<option value="' . esc_attr( $value ) . '">' . esc_attr( $group['name'] ) . '</option>';
                }
            }
            ?>

        </select>

        <span style="float:right;">
            <button id="ml_main_col_assign_users_teams_add" type="submit"
                    class="button float-right"><?php esc_html_e( "Add", 'disciple_tools' ) ?></button>
        </span>
        <br><br>

        Currently Assigned Users, Teams & Groups
        <hr>

        <table class="widefat striped" id="ml_main_col_assign_users_teams_table">
            <thead>
            <tr>
                <th>Type</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Link</th>
                <th></th>
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>

        <?php
    }

    private function main_column_message() {
        ?>
        <textarea style="min-width: 100%;" id="ml_main_col_msg_textarea" rows="10"></textarea>
        <?php
    }

    private function main_column_schedules() {
        ?>
        <table class="widefat striped">
            <tr>
                <td style="vertical-align: middle;">Scheduling Enabled</td>
                <td>
                    <input type="checkbox" id="ml_main_col_schedules_enabled"/>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Frequency [<a href="#" class="ml-links-docs"
                                                                  data-title="ml_links_right_docs_frequency_title"
                                                                  data-content="ml_links_right_docs_frequency_content">&#63;</a>]
                </td>
                <td>
                    <select style="min-width: 10%;" id="ml_main_col_schedules_frequency_amount">
                        <?php
                        for ( $x = 1; $x <= 12; $x ++ ) {
                            echo '<option value="' . esc_attr( $x ) . '">' . esc_attr( $x ) . '</option>';
                        }
                        ?>
                    </select>

                    <select style="min-width: 10%;" id="ml_main_col_schedules_frequency_time_unit">
                        <option value="hours">Hours</option>
                        <option value="days">Days</option>
                        <option value="weeks">Weeks</option>
                        <option value="months">Months</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Sending Channel [<a href="#" class="ml-links-docs"
                                                                        data-title="ml_links_right_docs_send_channel_title"
                                                                        data-content="ml_links_right_docs_send_channel_content">&#63;</a>]
                </td>
                <td>
                    <select style="min-width: 100%;" id="ml_main_col_schedules_sending_channels">
                        <option disabled selected value>-- select sending channel --</option>

                        <?php
                        // Source available sending channels
                        $sending_channels = Disciple_Tools_Magic_Links_API::fetch_sending_channels();
                        if ( ! empty( $sending_channels ) ) {
                            foreach ( $sending_channels as $channel ) {
                                echo '<option value="' . esc_attr( $channel['id'] ) . '">' . esc_attr( $channel['name'] ) . '</option>';
                            }
                        }
                        ?>

                    </select>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Links Expire Within [<a href="#" class="ml-links-docs"
                                                                            data-title="ml_links_right_docs_links_expire_title"
                                                                            data-content="ml_links_right_docs_links_expire_content">&#63;</a>]
                </td>
                <td style="vertical-align: middle;">
                    <select style="min-width: 10%;" id="ml_main_col_schedules_links_expire_amount">
                        <?php
                        for ( $x = 1; $x <= 12; $x ++ ) {
                            echo '<option value="' . esc_attr( $x ) . '">' . esc_attr( $x ) . '</option>';
                        }
                        ?>
                    </select>

                    <select style="min-width: 10%;" id="ml_main_col_schedules_links_expire_time_unit">
                        <option value="hours">Hours</option>
                        <option value="days">Days</option>
                        <option value="weeks">Weeks</option>
                        <option value="months">Months</option>
                    </select>

                    &nbsp;&nbsp;&nbsp;
                    <input type="checkbox" id="ml_main_col_schedules_links_expire_never" value=""/> Never Expires
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Links Expire On [<a href="#" class="ml-links-docs"
                                                                        data-title="ml_links_right_docs_links_expire_on_title"
                                                                        data-content="ml_links_right_docs_links_expire_on_content">&#63;</a>]
                </td>
                <td style="vertical-align: middle;">
                    <input type="hidden" id="ml_main_col_schedules_links_expire_base_ts" value=""/>
                    <input type="hidden" id="ml_main_col_schedules_links_expire_on_ts" value=""/>
                    <input style="min-width: 100%;" type="text" id="ml_main_col_schedules_links_expire_on_ts_formatted"
                           readonly/>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Links Expiry Auto-Refresh Enabled [<a href="#" class="ml-links-docs"
                                                                                          data-title="ml_links_right_docs_auto_refresh_title"
                                                                                          data-content="ml_links_right_docs_auto_refresh_content">&#63;</a>]
                </td>
                <td style="vertical-align: middle;">
                    <input type="checkbox" id="ml_main_col_schedules_links_expire_auto_refresh_enabled" value=""/>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;" colspan="2">
                    <input type="hidden" id="ml_main_col_schedules_last_schedule_run" value=""/>
                    <input type="hidden" id="ml_main_col_schedules_last_success_send" value=""/>

                    <button disabled style="min-width: 100%;" type="submit" id="ml_main_col_schedules_send_now_but"
                            class="button float-right"><?php esc_html_e( "Send Now", 'disciple_tools' ) ?></button>
                </td>
            </tr>
        </table>
        <?php
    }
}
