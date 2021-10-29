<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Disciple_Tools_Magic_Links_Tab_Email
 */
class Disciple_Tools_Magic_Links_Tab_Email {

    public function __construct() {

        // Handle update submissions
        $this->process_updates();

        // Load scripts and styles
        $this->process_scripts();

    }

    private function process_scripts() {
        wp_enqueue_script( 'dt_magic_links_email_script', plugin_dir_url( __FILE__ ) . 'js/email-tab.js', [
            'jquery',
            'lodash'
        ], filemtime( dirname( __FILE__ ) . '/js/email-tab.js' ), true );

        wp_localize_script(
            "dt_magic_links_email_script", "dt_magic_links", array(
                'dt_magic_link_default_email_obj' => Disciple_Tools_Magic_Links_API::fetch_option( Disciple_Tools_Magic_Links_API::$option_dt_magic_links_defaults_email )
            )
        );
    }

    private function final_post_param_sanitization( $str ) {
        return str_replace( [ '&lt;', '&gt;' ], [ '<', '>' ], $str );
    }

    private function process_updates() {

        if ( isset( $_POST['ml_email_main_col_config_form_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ml_email_main_col_config_form_nonce'] ) ), 'ml_email_main_col_config_form_nonce' ) ) {
            if ( isset( $_POST['ml_email_main_col_config_form_email_obj'] ) ) {

                // Fetch newly updated email object
                $sanitized_input   = filter_var( wp_unslash( $_POST['ml_email_main_col_config_form_email_obj'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES );
                $updated_email_obj = json_decode( $this->final_post_param_sanitization( $sanitized_input ), true );

                // Ensure we have something to work with
                if ( ! empty( $updated_email_obj ) && isset( $updated_email_obj['enabled'] ) ) {
                    Disciple_Tools_Magic_Links_API::update_option( Disciple_Tools_Magic_Links_API::$option_dt_magic_links_defaults_email, json_encode( $updated_email_obj ) );
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
        <table class="widefat striped" id="ml_email_main_col_config">
            <thead>
            <tr>
                <th>Mail Server Configuration</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_config(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->

        <!-- Box -->
        <table class="widefat striped" id="ml_email_main_col_msg">
            <thead>
            <tr>
                <th>Message [<a href="#" class="ml-email-docs"
                                data-title="ml_email_right_docs_title_message"
                                data-content="ml_email_right_docs_content_message">&#63;</a>]
                </th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_msg(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->

        <br>
        <span id="ml_email_main_col_update_msg" style="font-weight: bold; color: red;"></span>
        <span style="float:right;">
            <button type="submit" id="ml_email_main_col_update_but"
                    class="button float-right"><?php esc_html_e( "Update", 'disciple_tools' ) ?></button>
        </span>
        <?php
    }

    public function right_column() {
        ?>
        <!-- Box -->
        <table style="display: none;" id="ml_email_right_docs_section" class="widefat striped">
            <thead>
            <tr>
                <th id="ml_email_right_docs_title"></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td id="ml_email_right_docs_content"></td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php

        // Include helper documentation
        include 'email-tab-docs.php';
    }

    private function main_column_config() {
        ?>
        <table class="widefat striped">
            <tr>
                <td style="vertical-align: middle;">Enabled</td>
                <td>
                    <input type="checkbox" id="ml_email_main_col_config_enabled"/>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Use Default Mail Server [<a href="#" class="ml-email-docs"
                                                                                data-title="ml_email_right_docs_title_use_default_server"
                                                                                data-content="ml_email_right_docs_content_use_default_server">&#63;</a>]
                </td>
                <td>
                    <input type="checkbox" id="ml_email_main_col_config_use_default_server"/>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Server Address [<a href="#" class="ml-email-docs"
                                                                       data-title="ml_email_right_docs_title_server_addr"
                                                                       data-content="ml_email_right_docs_content_server_addr">&#63;</a>]
                </td>
                <td>
                    <input style="min-width: 100%;" type="text" id="ml_email_main_col_config_server_addr"/>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Server Port [<a href="#" class="ml-email-docs"
                                                                    data-title="ml_email_right_docs_title_server_port"
                                                                    data-content="ml_email_right_docs_content_server_port">&#63;</a>]
                </td>
                <td>
                    <select style="min-width: 25%;" id="ml_email_main_col_config_server_port">
                        <option value="25">25</option>
                        <option value="465">465</option>
                        <option value="587">587</option>
                        <option value="2525">2525</option>
                        <option value="2526">2526</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Encryption Type [<a href="#" class="ml-email-docs"
                                                                        data-title="ml_email_right_docs_title_encrypt"
                                                                        data-content="ml_email_right_docs_content_encrypt">&#63;</a>]
                </td>
                <td>
                    <select style="min-width: 100%;" id="ml_email_main_col_config_encrypt">
                        <option value="none">None</option>
                        <option value="tls">Transport Layer Security (TLS)</option>
                        <option value="ssl">Secure Sockets Layer (SSL)</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Authentication Enabled [<a href="#" class="ml-email-docs"
                                                                               data-title="ml_email_right_docs_title_auth"
                                                                               data-content="ml_email_right_docs_content_auth">&#63;</a>]
                </td>
                <td>
                    <input type="checkbox" id="ml_email_main_col_config_auth_enabled"/>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Username [<a href="#" class="ml-email-docs"
                                                                 data-title="ml_email_right_docs_title_usr"
                                                                 data-content="ml_email_right_docs_content_usr">&#63;</a>]
                </td>
                <td>
                    <input style="min-width: 100%;" type="password" id="ml_email_main_col_config_server_usr"/><br>
                    <input type="checkbox" id="ml_email_main_col_config_server_usr_show">Show Username
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Password [<a href="#" class="ml-email-docs"
                                                                 data-title="ml_email_right_docs_title_pwd"
                                                                 data-content="ml_email_right_docs_content_pwd">&#63;</a>]
                </td>
                <td>
                    <input style="min-width: 100%;" type="password" id="ml_email_main_col_config_server_pwd"/><br>
                    <input type="checkbox" id="ml_email_main_col_config_server_pwd_show">Show Password
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">From Email [<a href="#" class="ml-email-docs"
                                                                   data-title="ml_email_right_docs_title_from_email"
                                                                   data-content="ml_email_right_docs_content_from_email">&#63;</a>]
                </td>
                <td>
                    <input style="min-width: 100%;" type="text" id="ml_email_main_col_config_from_email"/>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">From Name [<a href="#" class="ml-email-docs"
                                                                  data-title="ml_email_right_docs_title_from_name"
                                                                  data-content="ml_email_right_docs_content_from_name">&#63;</a>]
                </td>
                <td>
                    <input style="min-width: 100%;" type="text" id="ml_email_main_col_config_from_name"/>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Email Field [<a href="#" class="ml-email-docs"
                                                                    data-title="ml_email_right_docs_title_email_field"
                                                                    data-content="ml_email_right_docs_content_email_field">&#63;</a>]
                </td>
                <td>
                    <select style="min-width: 100%;" id="ml_email_main_col_config_email_field">
                        <option disabled selected value>-- select field containing email information --</option>

                        <?php
                        $filtered_fields = $this->fetch_filtered_email_fields();
                        foreach ( $filtered_fields ?? [] as $filtered ) {
                            if ( isset( $filtered['fields'] ) && ! empty( $filtered['fields'] ) ) {
                                echo '<option disabled value>-- ' . esc_attr( $filtered['name'] ) . ' --</option>';

                                // Display filtered fields
                                foreach ( $filtered['fields'] as $field ) {
                                    $value    = esc_attr( $filtered['post_type'] ) . '+' . esc_attr( $field['id'] );
                                    $selected = ! empty( $contact_field ) && $contact_field === $value ? 'selected' : '';
                                    echo '<option ' . esc_attr( $selected ) . ' value="' . esc_attr( $value ) . '">' . esc_attr( $field['name'] ) . '</option>';
                                }
                            }
                        }
                        ?>

                    </select>
                </td>
            </tr>
        </table>

        <!-- [Submission Form] -->
        <form method="post" id="ml_email_main_col_config_form">
            <input type="hidden" id="ml_email_main_col_config_form_nonce"
                   name="ml_email_main_col_config_form_nonce"
                   value="<?php echo esc_attr( wp_create_nonce( 'ml_email_main_col_config_form_nonce' ) ) ?>"/>

            <input type="hidden" id="ml_email_main_col_config_form_email_obj"
                   name="ml_email_main_col_config_form_email_obj" value=""/>
        </form>
        <?php
    }

    private function main_column_msg() {
        ?>
        <input style="min-width: 100%;" type="text" id="ml_email_main_col_msg_subject" placeholder="Email Subject..."/>
        <textarea style="min-width: 100%;" id="ml_email_main_col_msg_textarea" rows="10"></textarea>
        <?php
    }

    private function fetch_filtered_email_fields(): array {
        $filtered_fields = [];
        $supported_types = [
            'communication_channel'
        ];

        $post_types = DT_Posts::get_post_types();
        if ( ! empty( $post_types ) ) {
            foreach ( $post_types as $post_type ) {

                $fields             = [];
                $post_type_settings = DT_Posts::get_post_settings( $post_type );

                // Iterate over fields in search of comms fields
                foreach ( $post_type_settings['fields'] as $key => $field ) {
                    if ( isset( $field['type'] ) && in_array( $field['type'], $supported_types ) ) {

                        // Capture filtered field
                        $fields[] = [
                            'id'   => $key,
                            'name' => $field['name'],
                            'type' => $field['type']
                        ];
                    }
                }

                // If filtered fields detected, package and return...
                if ( ! empty( $fields ) ) {
                    $filtered_fields[ $post_type ] = [
                        'post_type' => $post_type,
                        'name'      => $post_type_settings['label_plural'],
                        'fields'    => $fields
                    ];
                }
            }
        }

        return $filtered_fields;
    }
}
