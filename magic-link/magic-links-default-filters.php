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
        wp_mail( $this->email_to, $this->email_subject, $this->email_body, $this->email_headers );
    }
}

/**
 * EMAIL SENDING CHANNEL
 */


/**
 * LINK EXPIRY CHECKER
 */

add_filter( 'dt_magic_link_continue', 'dt_magic_link_continue', 10, 2 );
function dt_magic_link_continue( bool $response, array $args ){
    // Extract required parameters from args
    $meta_key = $args['meta_key'] ?? '';
    $public_key = $args['public_key'] ?? '';
    $post_id = $args['post_id'] ?? null;
    $post_type = $args['post_type'] ?? '';
    $link_obj_id = $args['instance_id'] ?? null;

    // Ensure we have minimum required data
    if ( empty( $meta_key ) || empty( $public_key ) || empty( $post_id ) ) {
        return $response;
    }

    // Determine system type (wp_user or post)
    $sys_type = ( $post_type === 'user' ) ? 'wp_user' : 'post';
    $id = (int) $post_id;

    // STEP 1: Check independent expiration from post_meta/user_option (if available)
    if ( class_exists( 'Disciple_Tools_Bulk_Magic_Link_Sender_API' ) ) {
        $expiration_data = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_link_expiration_from_meta( $meta_key, $id, $sys_type );

        // If we have independent expiration data, check it first
        if ( ! empty( $expiration_data ) && ( ! empty( $expiration_data['ts'] ) || ! empty( $expiration_data['ts_base'] ) || $expiration_data['links_never_expires'] === true ) ) {
            // Verify hash matches (ensure expiration data is for current link, not a stale reset)
            $current_hash = $expiration_data['current_hash'] ?? '';
            // If hash matches (or no hash stored yet), check expiration
            if ( empty( $current_hash ) || $current_hash === $public_key ) {
                // Hash matches (or no hash stored yet) - check expiration
                $never_expires = $expiration_data['links_never_expires'] ?? false;
                $base_ts = $expiration_data['ts_base'] ?? '';
                $amount = $expiration_data['links_expire_within_amount'] ?? '';
                $time_unit = $expiration_data['links_expire_within_time_unit'] ?? '';

                // Check if link has expired
                if ( Disciple_Tools_Bulk_Magic_Link_Sender_API::has_links_expired( $never_expires, $base_ts, $amount, $time_unit ) === true ) {
                    // Link has expired - return false to redirect to expired landing page
                    return false;
                }

                // Link is still valid - return true
                return true;
            }
            // Hash mismatch - link was reset, expiration data is stale - fall through to link_obj checking
        }
    }

    // STEP 2: Fallback to link_obj checking (backward compatibility)
    if ( ! empty( $link_obj_id ) && class_exists( 'Disciple_Tools_Bulk_Magic_Link_Sender_API' ) ) {
        $link_obj = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_obj( $link_obj_id );

        if ( !empty( $link_obj ) ){
            // Check if link_obj itself is enabled/expired
            if ( isset( $link_obj->enabled, $link_obj->never_expires, $link_obj->expires ) && ( ( $link_obj->enabled === false ) || Disciple_Tools_Bulk_Magic_Link_Sender_API::has_obj_expired( $link_obj->never_expires, $link_obj->expires ) ) ){
                return false;
            }

            // Identify corresponding user's link object expiry status.
            $has_expired = false;
            foreach ( $link_obj->assigned ?? [] as $assigned ){
                if ( $assigned->dt_id === $post_id ){
                    if ( isset( $link_obj->link_manage->links_never_expires, $assigned->links_expire_within_base_ts, $link_obj->link_manage->links_expire_within_amount, $link_obj->link_manage->links_expire_within_time_unit ) ){
                        if ( Disciple_Tools_Bulk_Magic_Link_Sender_API::has_links_expired( $link_obj->link_manage->links_never_expires, $assigned->links_expire_within_base_ts, $link_obj->link_manage->links_expire_within_amount, $link_obj->link_manage->links_expire_within_time_unit ) === true ){
                            $has_expired = true;

                            // Nuke any stale, expired magic links
                            Disciple_Tools_Bulk_Magic_Link_Sender_API::update_magic_links( $link_obj, [ $assigned ], true );
                        }
                    }
                }
            }

            return !$has_expired;
        }
    }

    // If no expiration checks apply, return original response
    return $response;
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
