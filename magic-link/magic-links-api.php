<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Disciple_Tools_Magic_Links_API
 */
class Disciple_Tools_Magic_Links_API {

    public static $option_dt_magic_links_objects = 'dt_magic_links_objects';
    public static $option_dt_magic_links_all_scheduling_enabled = 'dt_magic_links_all_scheduling_enabled';
    public static $option_dt_magic_links_all_channels_enabled = 'dt_magic_links_all_channels_enabled';
    public static $option_dt_magic_links_logging = 'dt_magic_links_logging';
    public static $option_dt_magic_links_last_cron_run = 'dt_magic_links_last_cron_run';

    public static $schedule_last_schedule_run = 'last_schedule_run';
    public static $schedule_last_success_send = 'last_success_send';
    public static $schedule_links_expire_base_ts = 'links_expire_within_base_ts';

    public static function fetch_magic_link_types(): array {
        $filtered_types = apply_filters( 'dt_settings_apps_list', [] );

        // Only focus on magic link related app settings
        $magic_link_types = [];
        if ( ! empty( $filtered_types ) ) {
            foreach ( $filtered_types as $key => $app ) {
                if ( isset( $app['meta'] ) && $app['meta']['app_type'] === 'magic_link' ) {
                    $magic_link_types[] = $app;
                }
            }
        }

        return $magic_link_types;
    }

    public static function fetch_magic_link_type( $key ) {
        foreach ( self::fetch_magic_link_types() as $app ) {
            if ( $app['key'] === $key ) {
                return $app;
            }
        }

        return null;
    }

    public static function fetch_dt_users(): array {
        global $wpdb;

        // Fetch user ids
        $user_ids = $wpdb->get_results( "
            SELECT u.ID, u.display_name
            FROM $wpdb->users u
        ", ARRAY_A );

        if ( ! empty( $user_ids ) ) {
            $users = [];
            foreach ( $user_ids as $user ) {

                // Must have a corresponding contact_id
                $contact_id = Disciple_Tools_Users::get_contact_for_user( $user['ID'] );
                if ( ! empty( $contact_id ) && ! is_wp_error( $contact_id ) ) {
                    $users[] = [
                        'user_id'    => $user['ID'],
                        'contact_id' => $contact_id,
                        'name'       => $user['display_name'],
                        'phone'      => self::fetch_dt_contacts_comms_info( $contact_id, 'contact_phone' ),
                        'email'      => self::fetch_dt_contacts_comms_info( $contact_id, 'contact_email' )
                    ];
                }
            }

            return $users;
        }

        return [];
    }

    public static function fetch_dt_teams(): array {
        global $wpdb;

        // Fetch team ids
        $team_ids = $wpdb->get_results( "
        SELECT DISTINCT(pm.post_id)
        FROM $wpdb->posts p
        LEFT JOIN $wpdb->postmeta as pm ON (p.ID = pm.post_id AND pm.meta_key = 'group_type')
        WHERE pm.meta_value = 'team';
        ", ARRAY_A );

        // Fetch team objects
        if ( ! empty( $team_ids ) ) {
            $teams = [];
            foreach ( $team_ids as $id ) {
                $team = DT_Posts::get_post( 'groups', $id['post_id'], true, false );
                if ( ! empty( $team ) && ! is_wp_error( $team ) && isset( $team['group_type']['key'] ) && $team['group_type']['key'] === 'team' ) {

                    // Ensure team members also contain corresponding user ids
                    $updated_team = self::capture_team_member_user_ids( $team );

                    // Only capture what we need
                    $teams[] = [
                        'id'      => $updated_team['ID'],
                        'name'    => $updated_team['name'],
                        'members' => $updated_team['members']
                    ];
                }
            }

            return $teams;
        }

        return [];
    }

    private static function capture_team_member_user_ids( $team ): array {
        if ( ! empty( $team ) && isset( $team['members'] ) ) {
            foreach ( $team['members'] as $key => $member ) {

                // Fetch corresponding user ids for team members; which currently deal in contact post ids!
                $corresponds_to_user_id = get_post_meta( $member['ID'], "corresponds_to_user", true );
                if ( ! empty( $corresponds_to_user_id ) ) {
                    $team['members'][ $key ]['user_id'] = $corresponds_to_user_id;
                    $team['members'][ $key ]['phone']   = self::fetch_dt_contacts_comms_info( $member['ID'], 'contact_phone' );
                    $team['members'][ $key ]['email']   = self::fetch_dt_contacts_comms_info( $member['ID'], 'contact_email' );
                }
            }
        }

        return $team;
    }

    private static function fetch_dt_contacts_comms_info( $contact_id, $field_id ): array {
        $contact = DT_Posts::get_post( 'contacts', $contact_id, true, false );

        $comms = [];
        if ( ! empty( $contact ) && ! is_wp_error( $contact ) ) {
            foreach ( $contact[ $field_id ] ?? [] as $comm ) {
                $comms[] = $comm['value'];
            }
        }

        return $comms;
    }

    public static function fetch_option_link_objs() {
        $option = get_option( self::$option_dt_magic_links_objects );

        return ( ! empty( $option ) ) ? json_decode( $option ) : (object) [];
    }

    public static function fetch_option_link_obj( $link_obj_id ) {
        $option_link_objs = self::fetch_option_link_objs();

        return ( isset( $option_link_objs->{$link_obj_id} ) ) ? $option_link_objs->{$link_obj_id} : (object) [];
    }

    public static function update_option_link_obj( $link_obj ) {
        $option_link_objs = self::fetch_option_link_objs();

        $option_link_objs->{$link_obj->id} = $link_obj;

        // Save changes.
        update_option( self::$option_dt_magic_links_objects, json_encode( $option_link_objs ) );
    }

    public static function fetch_option( $option ) {
        return get_option( $option );
    }

    public static function update_option( $option, $value ) {
        update_option( $option, $value );
    }

    public static function update_user_app_magic_links( $magic_key, $assigned, $delete ) {
        if ( ! empty( $magic_key ) && ! empty( $assigned ) ) {
            foreach ( $assigned ?? [] as $user ) {

                // Only process types: user + members
                if ( isset( $user->type ) && in_array( strtolower( trim( $user->type ) ), [ 'user', 'member' ] ) ) {
                    if ( isset( $user->dt_id ) ) {

                        // Delete/Create accordingly, based on flag!
                        if ( $delete === true ) {
                            delete_user_option( $user->dt_id, $magic_key );
                        } else {
                            update_user_option( $user->dt_id, $magic_key, dt_create_unique_key() );
                        }
                    }
                }
            }
        }
    }

    public static function fetch_sending_channels(): array {
        $filtered_channels = apply_filters( 'dt_sending_channels', [] );

        // Only focus on enabled channels
        $sending_channels = [];
        if ( ! empty( $filtered_channels ) ) {
            foreach ( $filtered_channels as $key => $channel ) {
                if ( isset( $channel['enabled'] ) && $channel['enabled'] ) {
                    $sending_channels[] = $channel;
                }
            }
        }

        return $sending_channels;
    }

    public static function fetch_sending_channel( $id ) {
        foreach ( self::fetch_sending_channels() as $channel ) {
            if ( $channel['id'] === $id ) {
                return $channel;
            }
        }

        return null;
    }

    public static function logging_load(): array {
        return ! empty( get_option( self::$option_dt_magic_links_logging ) ) ? json_decode( get_option( self::$option_dt_magic_links_logging ) ) : [];
    }

    public static function logging_create( $msg ) {
        return (object) [
            'timestamp' => time(),
            'log'       => $msg
        ];
    }

    public static function logging_update( $logs ) {
        update_option( self::$option_dt_magic_links_logging, json_encode( $logs ) );
    }

    public static function logging_add( $log ) {
        $logs   = self::logging_load();
        $logs[] = self::logging_create( $log );
        self::logging_update( $logs );
    }

    public static function logging_aged() {
        // Remove entries older than specified aged period!
        $logs = self::logging_load();
        if ( ! empty( $logs ) ) {
            $cut_off_point_ts  = time() - ( 3600 * 1 ); // 1 hr ago!
            $cut_off_point_idx = 0;

            $count = count( $logs );
            for ( $x = 0; $x < $count; $x ++ ) {

                // Stale logs will typically be found at the start! Therefore, capture transition point!
                if ( $logs[ $x ]->timestamp > $cut_off_point_ts ) {
                    $cut_off_point_idx = $x;
                    $x                 = $count;
                }
            }

            // Age off any stale logs
            if ( $cut_off_point_idx > 0 ) {
                $stale_logs = array_splice( $logs, 0, $cut_off_point_idx );
                self::logging_update( $logs );
            }
        }
    }

    public static function is_scheduled_to_run( $link_obj ): bool {
        if ( ! isset( $link_obj->schedule->last_schedule_run ) || empty( $link_obj->schedule->last_schedule_run ) ) {
            return true;

        } else if ( isset( $link_obj->schedule->enabled ) && ! $link_obj->schedule->enabled ) {
            return false;
        }

        $frequency         = '+' . $link_obj->schedule->freq_amount . ' ' . $link_obj->schedule->freq_time_unit;
        $last_schedule_run = $link_obj->schedule->last_schedule_run;

        return strtotime( $frequency, $last_schedule_run ) < time(); // In the past!
    }

    public static function send( $link_obj, $user, &$logs ) {
        $logs[] = self::logging_create( 'Processing Assigned User: ' . $user->name );

        // Fetch magic link type details
        $magic_link_type = self::fetch_magic_link_type( $link_obj->type );
        if ( ! empty( $magic_link_type ) ) {

            // Fetch sending channel details
            $sending_channel = self::fetch_sending_channel( $link_obj->schedule->sending_channel );
            if ( ! empty( $sending_channel ) ) {

                // Ensure a valid message can be constructed
                $message = self::build_send_msg( $link_obj, $user->dt_id, $magic_link_type['url_base'] );
                if ( $message !== '' ) {

                    // Dispatch message using specified sending channel
                    $send_result = call_user_func( $sending_channel['send'], [
                        'user_id' => $user->dt_id,
                        'message' => $message
                    ] );

                    // A successful outcome....?
                    if ( ! is_wp_error( $send_result ) && $send_result ) {

                        // Update last successful send timestamp
                        self::update_schedule_settings( $link_obj->id, self::$schedule_last_success_send, time() );
                        $logs[] = self::logging_create( 'Last successful send timestamp updated.' );

                    } else {
                        $logs[] = self::logging_create( 'Unable to successfully send using sending channel id: ' . $link_obj->schedule->sending_channel );

                        // If WP Error, then extract exception information
                        if ( is_wp_error( $send_result ) ) {
                            $logs[] = self::logging_create( 'Exception: ' . $send_result->get_error_message() );
                        }
                    }
                } else {
                    $logs[] = self::logging_create( 'Unable to construct magic link message! Has the link expired?' );
                }
            } else {
                $logs[] = self::logging_create( 'Unable to fetch sending channel details for id: ' . $link_obj->schedule->sending_channel );
            }
        } else {
            $logs[] = self::logging_create( 'Unable to fetch magic link type details for key: ' . $link_obj->type );
        }
    }

    private static function build_send_msg( $link_obj, $user_id, $magic_link_url_base ): string {
        // First, locate current magic link hash
        $msg  = '';
        $hash = get_user_option( $link_obj->type, $user_id );
        if ( ! empty( $hash ) ) {

            // Construct current user's magic link url
            $url = trailingslashit( trailingslashit( site_url() ) . $magic_link_url_base ) . $hash;

            // Construct message body to be sent!
            $expire_on = self::fetch_links_expired_formatted_date( $link_obj->schedule->links_never_expires, $link_obj->schedule->links_expire_within_base_ts, $link_obj->schedule->links_expire_within_amount, $link_obj->schedule->links_expire_within_time_unit );
            $msg       = 'Hi, Please update records -> ' . $url . ' -> Link will expire on ' . $expire_on;
        }

        return $msg;
    }

    public static function update_schedule_settings( $link_obj_id, $setting, $value ) {
        $link_obj = self::fetch_option_link_obj( $link_obj_id );

        if ( ! empty( $link_obj ) ) {
            $link_obj->schedule->{$setting} = $value;
            self::update_option_link_obj( $link_obj );
        }
    }

    public static function has_obj_expired( $never_expires, $expiry_point ): bool {
        if ( $never_expires === true ) {
            return false;
        }

        return $expiry_point < time(); // In the past!
    }

    public static function has_links_expired( $never_expires, $base_ts, $amt, $time_unit ): bool {
        if ( $never_expires === true ) {
            return false;
        }

        $expiry_point = strtotime( '+' . $amt . ' ' . $time_unit, $base_ts );

        return $expiry_point < time(); // In the past!
    }

    public static function fetch_links_expired_formatted_date( $never_expires, $base_ts, $amt, $time_unit ): string {
        if ( $never_expires === true ) {
            return 'Never';
        }

        $expiry_point = strtotime( '+' . $amt . ' ' . $time_unit, $base_ts );

        return dt_format_date( $expiry_point, 'long' );
    }

    public static function fetch_endpoint_send_now_url(): string {
        return trailingslashit( site_url() ) . 'wp-json/disciple_tools_magic_links/v1/send_now';
    }

    public static function fetch_endpoint_report_url(): string {
        return trailingslashit( site_url() ) . 'wp-json/disciple_tools_magic_links/v1/report';
    }

    public static function fetch_report( $id ) {
        switch ( $id ) {
            case 'sent-vs-updated':
                return self::fetch_report_sent_vs_updated();
        }
    }

    private static function fetch_report_sent_vs_updated(): array {

        $report = [];

        // Loop through all active link objects
        $link_objs = self::fetch_option_link_objs();
        foreach ( $link_objs ?? [] as $id => $link_obj ) {

            // Ensure object is enabled, and we have a valid last successful send timestamp
            if ( $link_obj->enabled && ! empty( $link_obj->schedule->last_success_send ) ) {

                // Next, estimate total counts across all assigned user contacts
                $totals = self::report_sent_vs_updated_totals( $link_obj->assigned, $link_obj->schedule->last_success_send );

                // Package and return findings
                $report[] = [
                    'id'                     => $link_obj->id,
                    'name'                   => $link_obj->name,
                    'last_success_send'      => $link_obj->schedule->last_success_send,
                    'total_contacts'         => $totals['total_contacts'],
                    'total_updated_contacts' => $totals['total_updated_contacts']
                ];
            }
        }

        return $report;
    }

    private static function report_sent_vs_updated_totals( $users, $last_success_send ): array {

        $contacts      = [];
        $updated_count = 0;

        $original_user = wp_get_current_user();
        foreach ( $users ?? [] as $user ) {
            if ( in_array( trim( strtolower( $user->type ) ), [ 'user', 'member' ] ) ) {

                // Switch current user id status and fetch associated contacts list
                wp_set_current_user( $user->dt_id );

                // Fetch associated contacts list
                $posts = DT_Posts::list_posts( 'contacts', [ 'limit' => 1000 ], false );

                // Iterate and return valid posts
                if ( ! empty( $posts ) && isset( $posts['posts'], $posts['total'] ) ) {
                    foreach ( $posts['posts'] ?? [] as $post ) {

                        // Ensure post has not already been processed
                        $key = '_' . $post['ID'];
                        if ( ! array_key_exists( $key, $contacts ) ) {
                            $updated          = ( isset( $post['last_modified']['timestamp'] ) && ( intval( $post['last_modified']['timestamp'] ) > intval( $last_success_send ) ) ) ?? false;
                            $contacts[ $key ] = [
                                'id'      => $post['ID'],
                                'updated' => $updated
                            ];

                            // If required, increment updated count
                            if ( $updated ) {
                                $updated_count ++;
                            }
                        }
                    }
                }
            }
        }

        // Revert to original user
        if ( ! empty( $original_user ) && isset( $original_user->ID ) ) {
            wp_set_current_user( $original_user->ID );
        }

        return [
            'total_contacts'         => count( $contacts ),
            'total_updated_contacts' => $updated_count
        ];
    }

}
