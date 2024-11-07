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

    /**
     * Duplicate of theme function that could be replaced once web components are fully merged in
     * @param $field_key
     * @param $fields
     * @param $post
     * @param $show_extra_controls
     * @param $show_hidden
     * @param $field_id_prefix
     * @return void
     */
    public static function render_field_for_display( $field_key, $fields, $post, $show_extra_controls = false, $show_hidden = false, $field_id_prefix = '' ) {
        $disabled = $fields[$field_key]['readonly'] ? 'disabled' : '';
//        if ( isset( $post['post_type'] ) && isset( $post['ID'] ) && $post['ID'] !== 0 ) {
//            $can_update = DT_Posts::can_update( $post['post_type'], $post['ID'] );
//        } else {
//            $can_update = true;
//        }
//        if ( $can_update || isset( $post['assigned_to']['id'] ) && $post['assigned_to']['id'] == get_current_user_id() ) {
//            $disabled = '';
//        }
        $required_tag = ( isset( $fields[$field_key]['required'] ) && $fields[$field_key]['required'] === true ) ? 'required' : '';
        $field_type = isset( $fields[$field_key]['type'] ) ? $fields[$field_key]['type'] : null;
        $is_private = isset( $fields[$field_key]['private'] ) && $fields[$field_key]['private'] === true;
        $display_field_id = $field_key;
        if ( !empty( $field_id_prefix ) ) {
            $display_field_id = $field_id_prefix . $field_key;
        }
        if ( isset( $fields[$field_key]['type'] ) && empty( $fields[$field_key]['hidden'] ) ) {
            $allowed_types = apply_filters( 'dt_render_field_for_display_allowed_types', [ 'key_select', 'multi_select', 'date', 'datetime', 'text', 'textarea', 'number', 'connection', 'location', 'location_meta', 'communication_channel', 'tags', 'user_select' ] );
            if ( !in_array( $field_type, $allowed_types ) ){
                return;
            }
            if ( !dt_field_enabled_for_record_type( $fields[$field_key], $post ) ){
                return;
            }


            ?>
            <?php
            $icon = null;
            if ( isset( $fields[$field_key]['icon'] ) && !empty( $fields[$field_key]['icon'] ) ) {
                $icon = 'icon=' . esc_attr( $fields[$field_key]['icon'] );
            }

            $shared_attributes = '
                  id="' . esc_attr( $display_field_id ) . '"
                  name="' . esc_attr( $field_key ) .'"
                  label="' . esc_attr( $fields[$field_key]['name'] ) . '"
                  data-type="' . esc_attr( $field_type ) . '"
                  ' . esc_html( $icon ) . '
                  ' . esc_html( $required_tag ) . '
                  ' . esc_html( $disabled ) . '
                  ' . ( $is_private ? 'private privateLabel=' . esc_attr( _x( "Private Field: Only I can see it\'s content", 'disciple_tools' ) ) : null ) . '
            ';
            if ( $field_type === 'key_select' ) :
                ?>
                <dt-single-select class="select-field"
                    <?php echo wp_kses_post( $shared_attributes ) ?>
                                  value="<?php echo esc_attr( key_exists( $field_key, $post ) ? $post[$field_key]['key'] : null ) ?>"
                                  options="<?php echo esc_attr( json_encode( self::assoc_to_array( $fields[$field_key]['default'] ) ) ) ?>"
                >
                    <?php self::render_icon_slot( $fields[$field_key] ) ?>
                </dt-single-select>

            <?php elseif ( $field_type === 'tags' ) : ?>
                <?php $value = array_map(function ( $value ) {
                    return [
                        'id' => $value,
                        'label' => $value,
                    ];
                }, $post[$field_key] ?? []);
                ?>
                <dt-tags
                    <?php echo wp_kses_post( $shared_attributes ) ?>
                    value="<?php echo esc_attr( json_encode( $value ) ) ?>"
                    placeholder="<?php echo esc_html( sprintf( _x( 'Search %s', "Search 'something'", 'disciple_tools' ), $fields[$field_key]['name'] ) )?>"
                    allowAdd
                >
                    <?php self::render_icon_slot( $fields[$field_key] ) ?>
                </dt-tags>

            <?php elseif ( $field_type === 'multi_select' ) : ?>
                <?php $options = array_map(function ( $key, $value ) {
                    return [
                        'id' => $key,
                        'label' => $value['label'],
                    ];
                }, array_keys( $fields[$field_key]['default'] ), $fields[$field_key]['default']);
                $value = isset( $post[$field_key] ) ? $post[$field_key] : [];
                ?>
                <dt-multi-select
                    <?php echo wp_kses_post( $shared_attributes ) ?>
                    value="<?php echo esc_attr( json_encode( $value ) ) ?>"
                    options="<?php echo esc_attr( json_encode( $options ) ) ?>"
                    placeholder="<?php echo esc_attr( sprintf( _x( 'Search %s', "Search 'something'", 'disciple_tools' ), $fields[$field_key]['name'] ) )?>"
                    display="<?php echo esc_attr( isset( $fields[$field_key]['display'] ) ? $fields[$field_key]['display'] : 'typeahead' ) ?>"
                >
                    <?php self::render_icon_slot( $fields[$field_key] ) ?>
                </dt-multi-select>

            <?php elseif ( $field_type === 'text' ) :?>
                <dt-text
                    <?php echo wp_kses_post( $shared_attributes ) ?>
                    value="<?php echo esc_html( $post[$field_key] ?? '' ) ?>"
                >
                    <?php self::render_icon_slot( $fields[$field_key] ) ?>
                </dt-text>
            <?php elseif ( $field_type === 'textarea' ) :?>
                <dt-textarea
                    <?php echo wp_kses_post( $shared_attributes ) ?>
                    value="<?php echo esc_html( $post[$field_key] ?? '' ) ?>"
                >
                    <?php self::render_icon_slot( $fields[$field_key] ) ?>
                </dt-textarea>
            <?php elseif ( $field_type === 'number' ) :?>
                <dt-number
                    <?php echo wp_kses_post( $shared_attributes ) ?>
                    value="<?php echo esc_html( $post[$field_key] ?? '' ) ?>" <?php echo esc_html( $disabled ); ?>
                    <?php echo isset( $fields[$field_key]['min_option'] ) && is_numeric( $fields[$field_key]['min_option'] ) ? 'min="' . esc_html( $fields[$field_key]['min_option'] ?? '' ) . '"' : '' ?>
                    <?php echo isset( $fields[$field_key]['max_option'] ) && is_numeric( $fields[$field_key]['max_option'] ) ? 'max="' . esc_html( $fields[$field_key]['max_option'] ?? '' ) . '"' : '' ?>
                >
                    <?php self::render_icon_slot( $fields[$field_key] ) ?>
                </dt-number>
            <?php elseif ( $field_type === 'date' ) :?>
                <dt-date
                    <?php echo wp_kses_post( $shared_attributes ) ?>
                    timestamp="<?php echo esc_html( $post[$field_key]['timestamp'] ?? '' ) ?>"
                >
                    <?php self::render_icon_slot( $fields[$field_key] ) ?>
                </dt-date>

            <?php elseif ( $field_type === 'connection' ) :?>
                <?php $value = array_map(function ( $value ) {
                    return [
                        'id' => $value['ID'],
                        'label' => $value['post_title'],
                        'link' => $value['permalink'],
                        'status' => $value['status'],
                    ];
                }, $post[$field_key] ?? []);
                ?>
                <dt-connection
                    <?php echo wp_kses_post( $shared_attributes ) ?>
                    value="<?php echo esc_attr( json_encode( $value ) ) ?>"
                    data-posttype="<?php echo esc_attr( $fields[$field_key]['post_type'] ) ?>"
                    allowAdd
                >
                    <?php self::render_icon_slot( $fields[$field_key] ) ?>
                </dt-connection>

            <?php elseif ( $field_type === 'location' ) :?>
                <?php $value = array_map(function ( $value ) {
                    return [
                        'id' => strval( $value['id'] ),
                        'label' => $value['label'],
                    ];
                }, $post[$field_key] ?? []);
                $filters = [
                    [
                        'id' => 'focus',
                        'label' => __( 'Regions of Focus', 'disciple_tools' ),
                    ],
                    [
                        'id' => 'all',
                        'label' => __( 'All Locations', 'disciple_tools' )
                    ]
                ];
                ?>
                <dt-location
                    <?php echo wp_kses_post( $shared_attributes ) ?>
                    value="<?php echo esc_attr( json_encode( $value ) ) ?>"
                    filters="<?php echo esc_attr( json_encode( $filters ) ) ?>"
                    placeholder="<?php echo esc_html( sprintf( _x( 'Search %s', "Search 'something'", 'disciple_tools' ), $fields[$field_key]['name'] ) )?>"
                >
                    <?php self::render_icon_slot( $fields[$field_key] ) ?>
                </dt-location>

            <?php elseif ( $field_type === 'location_meta' ) :?>
                <?php
                $value = isset( $post[$field_key] ) ? $post[$field_key] : [];
                $mapbox_token = DT_Mapbox_API::get_key();
                $google_token = Disciple_Tools_Google_Geocode_API::get_key();
                ?>
                <dt-location-map
                    <?php echo wp_kses_post( $shared_attributes ) ?>
                    value="<?php echo esc_attr( json_encode( $value ) ) ?>"
                    mapbox-token="<?php echo esc_attr( $mapbox_token ) ?>"
                    google-token="<?php echo esc_attr( $google_token ) ?>"
                >
                    <?php self::render_icon_slot( $fields[$field_key] ) ?>
                </dt-location-map>

            <?php elseif ( $field_type === 'communication_channel' ): ?>
                <?php
                $value = isset( $post[$field_key] ) ? $post[$field_key] : [];
                ?>
                <dt-comm-channel
                    <?php echo wp_kses_post( $shared_attributes ) ?>
                    value="<?php echo esc_attr( json_encode( $value ) ) ?>"
                >
                    <?php self::render_icon_slot( $fields[$field_key] ) ?>
                </dt-comm-channel>
            <?php else : ?>
                <?php dt_write_log( "Skipping field type: $field_type" ); ?>
            <?php endif;
        }
    }

    // endregion

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
