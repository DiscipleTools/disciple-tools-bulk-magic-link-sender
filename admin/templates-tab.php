<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Disciple_Tools_Bulk_Magic_Link_Sender_Tab_Templates
 */
class Disciple_Tools_Bulk_Magic_Link_Sender_Tab_Templates {

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

        dt_theme_enqueue_script( 'typeahead-jquery', 'dt-core/dependencies/typeahead/dist/jquery.typeahead.min.js', array( 'jquery' ), true );
        dt_theme_enqueue_style( 'typeahead-jquery-css', 'dt-core/dependencies/typeahead/dist/jquery.typeahead.min.css', array() );

        wp_register_style( 'jquery-ui', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.css', [], '1.12.1' );
        wp_enqueue_style( 'jquery-ui' );

        wp_enqueue_script( 'dt_magic_links_script', plugin_dir_url( __FILE__ ) . 'js/templates-tab.js', [
            'jquery',
            'lodash',
            'moment',
            'daterangepicker-js',
            'typeahead-jquery',
            'jquery-ui-core',
            'jquery-ui-sortable'
        ], filemtime( dirname( __FILE__ ) . '/js/templates-tab.js' ), true );

        wp_localize_script(
            "dt_magic_links_script", "dt_magic_links", array(
                'dt_post_types'            => $this->fetch_post_types(),
                'dt_magic_links_templates' => Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_templates )
            )
        );
    }

    private function final_post_param_sanitization( $str ) {
        return str_replace( [ '&lt;', '&gt;' ], [ '<', '>' ], $str );
    }

    private function process_updates() {

        if ( isset( $_POST['ml_main_col_update_form_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ml_main_col_update_form_nonce'] ) ), 'ml_main_col_update_form_nonce' ) ) {
            if ( isset( $_POST['ml_main_col_update_form_template'] ) ) {

                // Fetch newly updated link object
                $sanitized_input = filter_var( wp_unslash( $_POST['ml_main_col_update_form_template'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES );
                $template        = json_decode( $this->final_post_param_sanitization( $sanitized_input ), true );

                // Ensure we have something to work with
                if ( ! empty( $template ) && isset( $template['id'] ) ) {

                    // Fetch existing templates
                    $templates = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_templates );

                    // Ensure post type placeholder is present
                    if ( empty( $templates ) || ! isset( $templates[ $template['post_type'] ] ) ) {
                        $templates[ $template['post_type'] ] = [];
                    }

                    // Update templates with the latest template version
                    $templates[ $template['post_type'] ][ $template['id'] ] = $template;

                    // Finally, save updates
                    Disciple_Tools_Bulk_Magic_Link_Sender_API::update_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_templates, $templates );

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
        <table class="widefat striped" id="ml_main_col_available_post_types">
            <thead>
            <tr>
                <th>Edit Templates</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_available_post_types(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->

        <!-- Box -->
        <table style="display: none;" class="widefat striped" id="ml_main_col_templates_management">
            <thead>
            <tr>
                <th>Add new templates or modify existing ones</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_templates_management(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->

        <!-- Box -->
        <table style="display: none;" class="widefat striped" id="ml_main_col_template_details">
            <thead>
            <tr>
                <th>Template Details</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_template_details(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->

        <!-- Box -->
        <table style="display: none;" class="widefat striped" id="ml_main_col_selected_fields">
            <thead>
            <tr>
                <th>Selected Fields</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_selected_fields(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->

        <!-- Box -->
        <table style="display: none;" class="widefat striped" id="ml_main_col_message">
            <thead>
            <tr>
                <th>Message [<a href="#" class="ml-templates-docs"
                                data-title="ml_templates_right_docs_message_title"
                                data-content="ml_templates_right_docs_message_content">&#63;</a>]
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

        <!-- [Submission Form] -->
        <form method="post" id="ml_main_col_update_form">
            <input type="hidden" id="ml_main_col_update_form_nonce" name="ml_main_col_update_form_nonce"
                   value="<?php echo esc_attr( wp_create_nonce( 'ml_main_col_update_form_nonce' ) ) ?>"/>

            <input type="hidden" id="ml_main_col_update_form_template"
                   name="ml_main_col_update_form_template" value=""/>
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
        <table style="display: none;" id="ml_templates_right_docs_section" class="widefat striped">
            <thead>
            <tr>
                <th id="ml_templates_right_docs_title"></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td id="ml_templates_right_docs_content"></td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php

        // Include helper documentation
        include 'templates-tab-docs.php';
    }

    private function main_column_available_post_types() {
        ?>
        <table style="min-width: 100%; border: 0;" id="available_post_types_section_buttons_table">
            <tbody>
            <tr>
                <td style="vertical-align: middle;">For which post type?</td>

                <?php
                foreach ( $this->fetch_post_types() ?? [] as $post_type ) {
                    ?>

                    <td>
                        <a class="button float-right available-post-types-section-buttons"><?php echo esc_attr( $post_type['name'] ); ?></a>
                        <input type="hidden" id="available_post_types_section_post_type_id"
                               value="<?php echo esc_attr( $post_type['id'] ) ?>">
                        <input type="hidden" id="available_post_types_section_post_type_name"
                               value="<?php echo esc_attr( $post_type['name'] ) ?>">
                    </td>

                    <?php
                }
                ?>

            </tr>
            </tbody>
        </table>
        <?php
    }

    private function main_column_templates_management() {
        ?>
        <input type="hidden" id="templates_management_section_selected_post_type" value=""/>
        <table style="min-width: 100%; border: 0;">
            <tbody>
            <tr>
                <td>
                        <span style="float:right;">
                            <a id="templates_management_section_new_but"
                               class="button float-left"><?php esc_html_e( "New Template", 'disciple_tools' ) ?></a>
                        </span>
                </td>
            </tr>
            <tr>
                <td>
                    <table style="min-width: 100%;" class="widefat striped" id="templates_management_section_table">
                        <thead>
                        <tr>
                            <th style="max-width: 10px;">Enabled</th>
                            <th>Template</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </td>
            </tr>
            </tbody>
        </table>
        <?php
    }

    private function main_column_template_details() {
        ?>
        <input type="hidden" id="ml_main_col_template_details_id" value=""/>
        <table class="widefat striped">
            <tr>
                <td style="vertical-align: middle;">Enabled</td>
                <td>
                    <input type="checkbox" id="ml_main_col_template_details_enabled"
                           value=""/>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Template Name</td>
                <td>
                    <input style="min-width: 100%;" type="text" id="ml_main_col_template_details_name"
                           value=""/>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Post Type Fields</td>
                <td>
                    <select style="min-width: 85%;" id="ml_main_col_template_details_fields">
                        <option disabled selected value>-- select field --</option>
                    </select>

                    <span style="float:right;">
                        <button id="ml_main_col_template_details_fields_add" type="submit"
                                class="button float-right"><?php esc_html_e( "Add", 'disciple_tools' ) ?></button>
                    </span>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Custom Fields [<a href="#" class="ml-templates-docs"
                                                                      data-title="ml_templates_right_docs_custom_fields_title"
                                                                      data-content="ml_templates_right_docs_custom_fields_content">&#63;</a>]
                </td>
                <td>
                    <input style="min-width: 85%;" type="text" id="ml_main_col_template_details_custom_fields"
                           value=""/>

                    <span style="float:right;">
                        <button id="ml_main_col_template_details_custom_fields_add" type="submit"
                                class="button float-right"><?php esc_html_e( "Add", 'disciple_tools' ) ?></button>
                    </span>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Show Recent Comments</td>
                <td>
                    <input type="checkbox" id="ml_main_col_template_details_show_recent_comments"
                           value=""/>
                </td>
            </tr>
        </table>
        <?php
    }

    private function main_column_selected_fields() {
        ?>
        <div class="connected-sortable-fields"></div>
        <?php
    }

    private function main_column_message() {
        ?>
        <textarea style="min-width: 100%;" id="ml_main_col_msg_textarea" rows="10"></textarea>
        <?php
    }

    private function ignore_field_types(): array {
        return [
            'array',
            'task',
            'location_meta',
            'post_user_meta',
            'datetime_series',
            'hash'
        ];
    }

    private function fetch_post_types(): array {

        $post_types = [];

        $dt_post_types = DT_Posts::get_post_types();
        if ( ! empty( $dt_post_types ) ) {

            $field_types_to_ignore = $this->ignore_field_types();

            foreach ( $dt_post_types as $dt_post_type ) {
                $dt_post_type_settings = DT_Posts::get_post_settings( $dt_post_type );

                $fields = [];
                foreach ( $dt_post_type_settings['fields'] as $key => $dt_field ) {

                    if ( ! in_array( $dt_field['type'], $field_types_to_ignore ) && ! ( $dt_field['hidden'] ?? false ) ) {
                        $fields[] = [
                            'id'        => $key,
                            'name'      => $dt_field['name'],
                            'type'      => $dt_field['type'],
                            'defaults'  => $dt_field['default'] ?? '',
                            'post_type' => $dt_field['post_type'] ?? ''
                        ];
                    }
                }

                $post_type                = $dt_post_type_settings['post_type'];
                $post_types[ $post_type ] = [
                    'id'       => $post_type,
                    'name'     => $dt_post_type_settings['label_plural'],
                    'fields'   => $fields,
                    'base_url' => rest_url(),
                    'wp_nonce' => esc_attr( wp_create_nonce( 'wp_rest' ) )
                ];
            }
        }

        return $post_types;
    }
}
