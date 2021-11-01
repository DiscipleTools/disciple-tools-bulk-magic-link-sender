<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * EMAIL SENDING CHANNEL
 */

add_filter( 'dt_sending_channels', 'dt_email_sending_channels', 10, 1 );
function dt_email_sending_channels( $channels ) {
    $channels[] = [
        'id'                => Disciple_Tools_Magic_Links_API::$channel_email_id,
        'name'              => Disciple_Tools_Magic_Links_API::$channel_email_name,
        'enabled'           => dt_email_sending_channel_enabled(),
        'has_own_message'   => true,
        'build_own_message' => function ( $params = [] ) {
            return dt_email_sending_channel_build_msg( $params );
        },
        'send'              => function ( $params = [] ) {
            return dt_email_sending_channel_send( $params );
        }
    ];

    return $channels;
}

function dt_email_sending_channel_get_config() {
    $option = Disciple_Tools_Magic_Links_API::fetch_option( Disciple_Tools_Magic_Links_API::$option_dt_magic_links_defaults_email );

    return ! empty( $option ) ? json_decode( $option, true ) : null;
}

function dt_email_sending_channel_enabled(): bool {
    $email_obj = dt_email_sending_channel_get_config();
    if ( ! empty( $email_obj ) ) {
        return isset( $email_obj['enabled'] ) && $email_obj['enabled'];
    }

    return false;
}

function dt_email_sending_channel_build_msg( $params ): string {
    if ( isset( $params['user_id'], $params['link_url'], $params['expiry_ts'] ) ) {
        $email_obj = dt_email_sending_channel_get_config();
        if ( ! empty( $email_obj ) ) {

            // Determine post type, based on selected email field; which should have the format: [post_type]+[field_id]
            $post_type = explode( '+', $email_obj['email_field'] )[0];

            // Fetch corresponding contacts post record
            $user_contact = DT_Posts::get_post( $post_type, Disciple_Tools_Magic_Links_API::get_contact_id_by_user_id( $params['user_id'] ), true, false );
            if ( ! empty( $user_contact ) && ! is_wp_error( $user_contact ) ) {
                $name = $user_contact['title'] ?? '';

            } else {
                $name = '';
            }

            // Return message, having replaced placeholders
            return str_replace(
                [
                    '{{name}}',
                    '{{link}}',
                    '{{time}}'
                ],
                [
                    $name,
                    $params['link_url'],
                    $params['expiry_ts']
                ],
                $email_obj['message']
            );
        }
    }

    return '';
}

function dt_email_sending_channel_send( $params ) {
    // Ensure required params and enabled state is present and correct
    if ( ! dt_email_sending_channel_enabled() || empty( $params['user_id'] ) || empty( $params['message'] ) ) {
        return false;
    }

    // Fetch email config settings
    $email_obj = dt_email_sending_channel_get_config();

    // Fetch and split contact email field option; which should have the following format: [post_type]+[field_id]
    $post_type = explode( '+', $email_obj['email_field'] )[0];
    $field_id  = explode( '+', $email_obj['email_field'] )[1];

    // Fetch corresponding contacts post record
    $user_contact = DT_Posts::get_post( $post_type, Disciple_Tools_Magic_Links_API::get_contact_id_by_user_id( $params['user_id'] ), true, false );
    if ( ! empty( $email_obj ) && ! empty( $user_contact ) && ! is_wp_error( $user_contact ) ) {

        // As field is expected to be of type communication_channel; then structure to be an array of email addresses!
        if ( is_array( $user_contact[ $field_id ] ) ) {

            // Iterate over email addresses
            foreach ( $user_contact[ $field_id ] as $email ) {
                if ( ! empty( $email['value'] ) ) {

                    try {

                        // Build and dispatch notification email!
                        $email_to      = $email['value'];
                        $email_subject = $email_obj['subject'];
                        $email_body    = $params['message']; // Ensure to select the updated, placeholder replaced message!
                        $email_headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
                        wp_mail( $email_to, $email_subject, $email_body, $email_headers );

                    } catch ( Exception $e ) {
                        return new WP_Error( __FUNCTION__, $e->getMessage(), [ 'status' => $e->getCode() ] );
                    }
                }
            }

            // Return true if this point has been reached
            return true;
        }
    }

    return false;
}

add_action( 'phpmailer_init', 'dt_email_sending_channel_send_smtp_email' );
function dt_email_sending_channel_send_smtp_email( $phpmailer ) {

    // Configure accordingly based on default mail server usage state
    $email_obj = dt_email_sending_channel_get_config();
    if ( ! empty( $email_obj ) && isset( $email_obj['use_default_server'] ) && ! $email_obj['use_default_server'] ) {
        // phpcs:disable
        $phpmailer->Host       = $email_obj['server_addr'];
        $phpmailer->Port       = $email_obj['server_port'];
        $phpmailer->SMTPSecure = $email_obj['encrypt_type'];
        $phpmailer->SMTPAuth   = $email_obj['auth_enabled'];
        $phpmailer->Username   = $email_obj['username'];
        $phpmailer->Password   = $email_obj['password'];
        $phpmailer->From       = $email_obj['from_email'];
        $phpmailer->FromName   = $email_obj['from_name'];
        $phpmailer->isSMTP();
        // phpcs:enable
    }
}

/**
 * EMAIL SENDING CHANNEL
 */
