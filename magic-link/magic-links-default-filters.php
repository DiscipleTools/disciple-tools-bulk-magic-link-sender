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
        'id'      => Disciple_Tools_Magic_Links_API::$channel_email_id,
        'name'    => Disciple_Tools_Magic_Links_API::$channel_email_name,
        'enabled' => dt_email_sending_channel_enabled(),
        'send'    => function ( $params = [] ) {
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

    // Enabled, by default!
    return true;
}

function dt_email_sending_channel_field( $user ): array {
    $field = [];
    switch ( Disciple_Tools_Magic_Links_API::determine_assigned_user_type( $user ) ) {
        case Disciple_Tools_Magic_Links_API::$assigned_user_type_id_users:

            $user_info = get_userdata( $user->dt_id );
            if ( ! empty( $user_info ) && ! is_wp_error( $user_info ) && isset( $user_info->data->user_email ) ) {
                $field[] = $user_info->data->user_email;
            }
            break;

        case Disciple_Tools_Magic_Links_API::$assigned_user_type_id_contacts:

            $user_contact = DT_Posts::get_post( 'contacts', $user->dt_id, true, false );
            if ( ! empty( $user_contact ) && ! is_wp_error( $user_contact ) && isset( $user_contact['contact_email'] ) ) {
                foreach ( $user_contact['contact_email'] as $email ) {
                    if ( ! empty( $email['value'] ) ) {
                        $field[] = $email['value'];
                    }
                }
            }
            break;
    }

    return $field;
}

function dt_email_sending_channel_send( $params ) {
    // Ensure required params and enabled state is present and correct
    if ( ! dt_email_sending_channel_enabled() || empty( $params['user'] ) || empty( $params['message'] ) ) {
        return false;
    }

    // Fetch email config settings
    $email_obj = dt_email_sending_channel_get_config();

    // Fetch email addresses to be used during dispatch
    $emails = dt_email_sending_channel_field( $params['user'] );

    // A quick sanity check prior to iteration!
    if ( ! empty( $emails ) ) {

        // Iterate over email addresses
        foreach ( $emails as $email ) {
            if ( ! empty( $email ) ) {

                try {

                    // Build and dispatch notification email!
                    $email_to        = $email;
                    $email_subject   = $email_obj['subject'] ?? Disciple_Tools_Magic_Links_API::fetch_default_email_subject();
                    $email_body      = $params['message'];
                    $email_headers[] = 'Content-Type: text/plain; charset=UTF-8';
                    if ( ! empty( $email_obj['from_name'] ) && ! empty( $email_obj['from_email'] ) ) {
                        $email_headers[] = 'From: ' . $email_obj['from_name'] . ' <' . $email_obj['from_email'] . '>';
                    }

                    // Dispatch email notification
                    wp_mail( $email_to, $email_subject, $email_body, $email_headers );

                } catch ( Exception $e ) {
                    return new WP_Error( __FUNCTION__, $e->getMessage(), [ 'status' => $e->getCode() ] );
                }
            }
        }

        // Return true if this point has been reached
        return true;
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

        if ( ! empty( $email_obj['from_name'] ) && ! empty( $email_obj['from_email'] ) ) {
            $phpmailer->From     = $email_obj['from_email'];
            $phpmailer->FromName = $email_obj['from_name'];
        }

        $phpmailer->isSMTP();
        // phpcs:enable
    }
}

/**
 * EMAIL SENDING CHANNEL
 */
