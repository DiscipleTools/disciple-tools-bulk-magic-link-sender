<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.

add_filter('dt_magic_link_template_types', function( $types ) {
    $types['contacts'][] = [
        'value' => 'list-sub-assigned-contacts',
        'text' => 'List Sub-Assigned Contacts',
    ];
    return $types;
});

add_action('dt_magic_link_template_load', function ( $template ) {
    if ( !empty( $template ) && isset( $template['type'] ) && $template['type'] === 'list-sub-assigned-contacts' ) {
        new Disciple_Tools_Magic_Links_Template_List_Sub_Assigned( $template );
    }
} );

/**
 * Class Disciple_Tools_Magic_Links_Templates
 */
class Disciple_Tools_Magic_Links_Template_List_Sub_Assigned extends Disciple_Tools_Magic_Links_Template_Single_Record {

    protected $template_type = 'list-sub-assigned-contacts';
    public $page_title = 'List Sub-Assigned Contacts';
    public $page_description = 'List Sub-Assigned Contacts Description';

    public function body() {
        $has_title = ! empty( $this->template ) && ( isset( $this->template['title'] ) && ! empty( $this->template['title'] ) );
        ?>
        <div id="custom-style"></div>
        <div id="wrapper">
            <div class="grid-x">
                <div class="cell center">
                    <h2 id="title">
                        <b>
                            <?php echo esc_html( $has_title ? $this->adjust_template_title_translation( $this->template['title'], $this->template['title_translations'] ) : '' ); ?>

                            <button class="mdi mdi mdi-web" onclick="document.getElementById('list-sub-assigned-locale-modal')._openModal()"></button>
                        </b>
                    </h2>
                </div>
            </div>
            <?php
            if ( $has_title ) {
                ?>
                <hr/>
                <?php
            }

                $lang = dt_get_available_languages();
                $current_lang = trim( wp_get_current_user()->locale );   
                            
            ?>
            <div id="content">
                <div id="alert_notice" style="display: none; border-style: solid; border-width: 2px; border-color: #4caf50; background-color: rgba(142,195,81,0.2); border-radius: 5px; padding: 2em; margin: 1em 0">
                    <div style="display: flex; grid-gap: 1em">
                        <div style="display: flex; align-items: center">
                            <img style="width: 2em; filter: invert(52%) sepia(77%) saturate(383%) hue-rotate(73deg) brightness(98%) contrast(83%);"
                                 src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'exclamation-circle.svg' ); ?>" alt="Exclamation Circle"/>
                        </div>
                        <div id="alert_notice_content" style="display: flex; align-items: center"></div>
                    </div>
                </div>

                <!-- TEMPLATE MESSAGE -->
                <p id="template_msg">
                    <?php echo nl2br( esc_html( ! empty( $this->template ) && isset( $this->template['message'] ) ? $this->template['message'] : '' ) ); ?>
                </p>

                <?php
                // Determine if template type list of assigned contacts is to be displayed.
                if ( isset( $this->template['type'] ) && ( $this->template['type'] == 'list-sub-assigned-contacts' ) && !empty( $this->post ) ){

                    // Build query fields.
                    $query_fields = [
                        'subassigned' => [ $this->post['ID'] ]
                    ];

                    if ( !empty( $this->post['corresponds_to_user'] ) ) {
                        $query_fields['assigned_to'] = [ $this->post['corresponds_to_user'] ];
                    }

                    // Fetch all assigned posts
                    $assigned_posts = DT_Posts::list_posts( $this->post['post_type'], [
                        'limit' => 1000,
                        'fields' => [
                            $query_fields
                        ]
                    ], false );

                    // Add primary recipient post as first element.
                    array_unshift( $assigned_posts['posts'], $this->post );

                    $assigned_posts['posts'] = apply_filters( 'dt_smart_links_filter_assigned_posts', $assigned_posts['posts'], $this->template );

                    $this->post = null;
                    if ( !empty( $assigned_posts['posts'] ) ) {
                        $this->post = $assigned_posts['posts'][0];
                    }

                    // Display only if there are valid hits!
                    if ( isset( $assigned_posts['posts'] ) && count( $assigned_posts['posts'] ) > 0 ){
                        ?>
                        <!-- LIST SUB-ASSIGNED CONTACTS -->
                        <div id="assigned_contacts_div">
                            <h3><?php esc_html_e( 'Subassigned', 'disciple_tools' ) ?> [ <span
                                    id="total"><?php echo esc_html( count( $assigned_posts['posts'] ) ); ?></span>
                                ]</h3>
                            <hr>
                            <div class="grid-x api-content-div-style" id="api-content">
                                <table class="api-content-table">
                                    <tbody>
                                    <?php
                                    foreach ( $assigned_posts['posts'] as $assigned ){
                                        ?>
                                        <tr onclick="get_assigned_details('<?php echo esc_html( $assigned['post_type'] ); ?>','<?php echo esc_html( $assigned['ID'] ); ?>','<?php echo esc_html( str_replace( "'", '&apos;', $assigned['name'] ) ); ?>')">
                                            <td><?php echo esc_html( $assigned['name'] ) ?></td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                            <br>
                        </div>
                        <?php
                    }

                    // Determine if new item creation is enabled.
                    if ( isset( $this->template['support_creating_new_items'] ) && $this->template['support_creating_new_items'] ) {
                        ?>
                        <br>
                        <button id="add_new" class="button select-button" data-post_type="<?php echo esc_attr( $this->post['post_type'] ) ?>" data-post_id="0" data-post_name="<?php esc_html_e( 'New Record', 'disciple_tools' ) ?>">
                            <?php esc_html_e( 'Add New', 'disciple_tools' ) ?>
                        </button>
                        <br>
                        <?php
                    }
                }
                ?>

                <!-- ERROR MESSAGES -->
                <span id="error" style="color: red;"></span>
                <br>

                <h3>
                    <span id="contact_name" style="font-weight: bold">
                        <?php echo esc_html( ! empty( $this->post ) ? $this->post['name'] : '---' ); ?>
                    </span>
                </h3>
                <hr>
                <div class="grid-x" id="form-content">
                    <input id="post_id" type="hidden"
                           value="<?php echo esc_html( ! empty( $this->post ) ? $this->post['ID'] : '' ); ?>"/>
                    <input id="post_type" type="hidden"
                           value="<?php echo esc_html( ! empty( $this->post ) ? $this->post['post_type'] : '' ); ?>"/>
                    <?php
                    // Revert back to dt translations
                    $this->hard_switch_to_default_dt_text_domain();
                    ?>
                    <table style="<?php echo( ! empty( $this->post ) ? '' : 'display: none;' ) ?>"
                           class="form-content-table">
                        <tbody>
                        <?php

                        /**
                         * If a valid post is present, then display fields accordingly,
                         * based on hidden flags!
                         */

                        $this->post_field_settings = DT_Posts::get_post_field_settings( $this->post_type, false );
                        if ( ! empty( $this->post ) && ! empty( $this->post_field_settings ) && ! empty( $this->template ) ) {

                            // Display selected fields
                            foreach ( $this->template['fields'] ?? [] as $field ) {
                                if ( $field['enabled'] && $this->is_link_obj_field_enabled( $field['id'] ) ) {

                                    $post_field_type = '';
                                    if ( $field['type'] === 'dt' && isset( $this->post_field_settings[ $field['id'] ]['type'] ) ) {
                                        $post_field_type = $this->post_field_settings[ $field['id'] ]['type'];
                                    }
                                    if ( $field['type'] === 'dt' && empty( $post_field_type ) ) {
                                        continue;
                                    }
                                    // Field types to be supported.
                                    if ( $field['type'] === 'dt' && ! in_array( $post_field_type, [
                                            'text',
                                            'textarea',
                                            'date',
                                            'boolean',
                                            'key_select',
                                            'multi_select',
                                            'number',
                                            'link',
                                            'communication_channel',
                                            'location',
                                            'location_meta'
                                    ] ) ) {
                                        continue;
                                    }

                                    // Generate hidden values to assist downstream processing
                                    $hidden_values_html = '<input id="form_content_table_field_id" type="hidden" value="' . $field['id'] . '">';
                                    $hidden_values_html .= '<input id="form_content_table_field_type" type="hidden" value="' . $post_field_type . '">';
                                    $hidden_values_html .= '<input id="form_content_table_field_template_type" type="hidden" value="' . $field['type'] . '">';
                                    $hidden_values_html .= '<input id="form_content_table_field_meta" type="hidden" value="">';

                                    // Render field accordingly, based on template field type!
                                    switch ( $field['type'] ) {
                                        case 'dt':

                                            // Capture rendered field html
                                            ob_start();
                                            $this->post_field_settings[$field['id']]['custom_display'] = false;
                                            $this->post_field_settings[$field['id']]['readonly'] = !empty( $field['readonly'] );
                                            render_field_for_display( $field['id'], $this->post_field_settings, $this->post, true );
                                            $rendered_field_html = ob_get_contents();
                                            ob_end_clean();

                                            // Only display if valid html content has been generated
                                            if ( ! empty( $rendered_field_html ) ) {
                                                ?>
                                                <tr>
                                                    <?php
                                                    // phpcs:disable
                                                    echo $hidden_values_html;
                                                    // phpcs:enable
                                                    ?>
                                                    <td>
                                                        <?php
                                                        // phpcs:disable
                                                        echo $rendered_field_html;
                                                        // phpcs:enable
                                                        ?>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                            break;
                                        case 'custom':
                                            ?>
                                            <tr>
                                                <?php
                                                // phpcs:disable
                                                echo $hidden_values_html;
                                                // phpcs:enable
                                                ?>
                                                <td>
                                                    <?php
                                                    $this->render_custom_field_for_display( $field );
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php
                                            break;
                                    }
                                }
                            }

                            // If requested, display recent comments
                            if ( $this->template['show_recent_comments'] ) {
                                $comment_count = is_bool( $this->template['show_recent_comments'] ) ? 2 : intval( $this->template['show_recent_comments'] );
                                $recent_comments = DT_Posts::get_post_comments( $this->post['post_type'], $this->post['ID'], false, 'all', [ 'number' => $comment_count ] );
                                foreach ( $recent_comments['comments'] ?? [] as $comment ) {
                                    ?>
                                    <tr class="dt-comment-tr">
                                        <td>
                                            <div class="section-subheader dt-comment-subheader">
                                                <?php echo esc_html( $comment['comment_author'] . ' @ ' . $comment['comment_date'] ); ?>
                                            </div>
                                            <span class="dt-comment-content"><?php echo esc_html( $comment['comment_content'] ); ?></span>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
                <br>

                <!-- SUBMIT UPDATES -->
                <button id="content_submit_but"
                        style="<?php echo( ! empty( $this->post ) ? '' : 'display: none;' ) ?> min-width: 100%;"
                        class="button select-button">
                    <?php esc_html_e( 'Submit Update', 'disciple_tools' ) ?>
                    <span class="update-loading-spinner loading-spinner" style="height: 17px; width: 17px; vertical-align: text-bottom;"></span>
                </button>
            </div>
        </div>
        <dt-modal id="list-sub-assigned-locale-modal" buttonLabel="Open Modal" hideheader hidebutton closebutton>
            <span slot="content" id="list-sub-assigned-locale-modal-content">
            <ul class="language-select">
                <?php
                foreach ($lang as $language) {
                ?>
                <li
                    class="<?php echo $language['language'] === $current_lang ? esc_attr('active') : null ?>"
                    onclick="assignLanguage('<?php echo esc_html( $language['language'] ); ?>')"
                ><?php echo $language['native_name']; ?></li>
                <?php
                }
                ?>
                </ul>
            </span>
        </dt-modal>        
        <?php
    }
}
