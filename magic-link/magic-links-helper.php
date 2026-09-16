<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.

class Disciple_Tools_Magic_Links_Helper
{
    // region Web Components
    /**
     * @param $field
     * @return void
     */
    public static function render_icon_slot( $field ) {
        if ( isset( $field['font-icon'] ) && !empty( $field['font-icon'] ) ): ?>
            <span slot="icon-start">
                <i class="dt-icon ' . esc_html( $field['font-icon'] ) . '"></i>
            </span>
        <?php endif;
    }

    /**
     * @param $options
     * @return array
     */
    private static function assoc_to_array( $options ): array
    {
        $keys = array_keys( $options );
        return array_map(function ( $key ) use ( $options ) {
            $options[$key]['id'] = $key;
            return $options[$key];
        }, $keys);
    }

    public static function localized_template_selected_field_settings( $template ) {
        $post_type_field_settings = DT_Posts::get_post_field_settings( $template['post_type'], false );
        if ( !empty( $template['fields'] ) ) {

            $localized_selected_field_settings = [];
            foreach ( $template['fields'] as $template_field ) {
                if ( isset( $template_field['id'], $template_field['type'], $template_field['enabled'] ) ) {
                    if ( $template_field['enabled'] && ( $template_field['type'] === 'dt' ) && isset( $post_type_field_settings[ $template_field['id'] ] ) ) {
                        $localized_selected_field_settings[ $template_field['id'] ] = $post_type_field_settings[ $template_field['id'] ];
                    }
                }
            }
            return $localized_selected_field_settings;
        } else {
            return $post_type_field_settings;
        }
    }

    public static function localized_post_selected_field_settings( $post, $localised_fields, $inc_post_fields ) {
        if ( !empty( $localised_fields ) ) {
            $localized_post = [];
            foreach ( $post as $post_key => $post_value ) {
                if ( array_key_exists( $post_key, $localised_fields ) || in_array( $post_key, $inc_post_fields ) ) {
                    $localized_post[ $post_key ] = $post_value;
                }
            }
            return $localized_post;
        } else {
            return $post;
        }
    }

    public static function update_user_logged_in_state() {
        wp_set_current_user( 0 );
        $current_user = wp_get_current_user();
        $current_user->add_cap( 'magic_link' );
        $current_user->display_name = sprintf( __( '%s Submission', 'disciple_tools' ), apply_filters( 'dt_magic_link_global_name', __( 'Magic Link', 'disciple_tools' ) ) );
    }
}
