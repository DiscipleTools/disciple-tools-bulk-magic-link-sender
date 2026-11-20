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
        'id'      => Disciple_Tools_Bulk_Magic_Link_Sender_API::$channel_email_id,
        'name'    => Disciple_Tools_Bulk_Magic_Link_Sender_API::$channel_email_name,
        'enabled' => dt_email_sending_channel_enabled(),
        'send'    => function ( $params = [] ) {
            return dt_email_sending_channel_send( $params );
        }
    ];

    return $channels;
}

function dt_email_sending_channel_get_config() {
    $option = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_defaults_email );

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
    switch ( Disciple_Tools_Bulk_Magic_Link_Sender_API::determine_assigned_user_type( $user ) ) {
        case Disciple_Tools_Bulk_Magic_Link_Sender_API::$assigned_user_type_id_users:

            $user_info = get_userdata( $user->dt_id );
            if ( ! empty( $user_info ) && ! is_wp_error( $user_info ) && isset( $user_info->data->user_email ) ) {
                $field[] = $user_info->data->user_email;
            }
            break;

        case Disciple_Tools_Bulk_Magic_Link_Sender_API::$assigned_user_type_id_contacts:

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
                    $email_subject = !empty( $params['message_subject'] ) ? $params['message_subject'] : ( $email_obj['subject'] ?? Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_default_email_subject() );
                    $email_body      = $params['message'];
                    $email_headers[] = 'Content-Type: text/plain; charset=UTF-8';
                    if ( ! empty( $email_obj['from_name'] ) && ! empty( $email_obj['from_email'] ) ) {
                        $email_headers[] = 'From: ' . $email_obj['from_name'] . ' <' . $email_obj['from_email'] . '>';
                    }

                    // Dispatch email notification
                    wp_queue()->push( new ML_Send_Email_Job( $email_to, $email_subject, $email_body, $email_headers ) );

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
 * Send emails that have been put in the email queue
 */

use WP_Queue\Job;

class ML_Send_Email_Job extends Job{

    public $email_to;
    public $email_subject;
    public $email_body;
    public $email_headers;

    /**
     * ML_Send_Email_Job constructor.
     *
     * @param mixed $email_to
     * @param string $email_subject
     * @param string $email_body
     * @param array $email_headers
     *
     */
    public function __construct( $email_to, $email_subject, $email_body, $email_headers ){
        $this->email_to = $email_to;
        $this->email_subject = $email_subject;
        $this->email_body = $email_body;
        $this->email_headers = $email_headers;
    }

    /**
     * Handle job logic.
     */
    public function handle(){
        // The plugin's mail server settings apply to magic link emails only, not to other site mail.
        add_action( 'phpmailer_init', 'dt_email_sending_channel_send_smtp_email' );
        wp_mail( $this->email_to, $this->email_subject, $this->email_body, $this->email_headers );
        remove_action( 'phpmailer_init', 'dt_email_sending_channel_send_smtp_email' );
    }
}

/**
 * EMAIL SENDING CHANNEL
 */


/**
 * LINK EXPIRY CHECKER
 */

add_filter( 'dt_magic_link_continue', 'dt_magic_link_continue', 10, 2 );
function dt_magic_link_continue( bool $response, array $args ) {
    if ( ! class_exists( 'Disciple_Tools_Bulk_Magic_Link_Sender_API' ) ) {
        return $response;
    }

    return Disciple_Tools_Bulk_Magic_Link_Sender_API::evaluate_magic_link_continue( $response, $args );
}

/**
 * LINK EXPIRY CHECKER
 */


/**
 * GLOBAL NAME
 */

add_filter( 'dt_magic_link_global_name', 'dt_magic_link_global_name', 10, 1 );
function dt_magic_link_global_name( $global_name ) {
    if ( boolval( get_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_global_name_enabled, false ) ) === true ) {
        return get_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_global_name, $global_name );
    }

    return $global_name;
}

/**
 * GLOBAL NAME
 */

/**
 * CDN SUBRESOURCE INTEGRITY
 */

add_filter( 'script_loader_tag', 'dt_magic_links_add_cdn_integrity', 10, 3 );
add_filter( 'style_loader_tag', 'dt_magic_links_add_cdn_integrity', 10, 3 );
function dt_magic_links_add_cdn_integrity( $tag, $handle, $src ) {
    $integrity = [
        'https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.js' => 'sha384-HV48j9xMNfX80v1dhiML90VeD4a38xrehnBK+dNSSe8P4fija/zP36u52q7TpB6O',
        'https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.css' => 'sha384-GSJP6bR4yv1SbLGhL+ntxb4LDi6RC5j8eM8OAh5lwPYdvj9cflIWJiFZdAFr2gyP',
        'https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css' => 'sha384-HphS8cQyN+eYiJ5PMbzShG6qZdRtvHPVLPkYb8JwMkmNgaIxrFVDhQe3jIbq3EZ2',
    ];
    $url = strtok( (string) $src, '?' );
    if ( isset( $integrity[ $url ] ) && strpos( $tag, ' integrity=' ) === false ) {
        $tag = preg_replace( '#\s(?:src|href)=(["\'])' . preg_quote( $url, '#' ) . '#', ' integrity="' . esc_attr( $integrity[ $url ] ) . '" crossorigin="anonymous"$0', $tag, 1 );
    }
    return $tag;
}
