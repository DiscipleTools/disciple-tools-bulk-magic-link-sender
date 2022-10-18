<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Disciple_Tools_Bulk_Magic_Link_Sender_API
 */
class Disciple_Tools_Bulk_Magic_Link_Sender_API {

    public static $option_dt_magic_links_objects = 'dt_magic_links_objects';
    public static $option_dt_magic_links_all_scheduling_enabled = 'dt_magic_links_all_scheduling_enabled';
    public static $option_dt_magic_links_all_channels_enabled = 'dt_magic_links_all_channels_enabled';
    public static $option_dt_magic_links_logging = 'dt_magic_links_logging';
    public static $option_dt_magic_links_last_cron_run = 'dt_magic_links_last_cron_run';
    public static $option_dt_magic_links_local_time_zone = 'dt_magic_links_local_time_zone';
    public static $option_dt_magic_links_defaults_email = 'dt_magic_links_defaults_email';
    public static $option_dt_magic_links_templates = 'dt_magic_links_templates';
    public static $option_dt_magic_links_migration_number = 'dt_magic_links_migration_number';
    public static $option_dt_magic_links_migration_lock = 'dt_magic_links_migration_lock';
    public static $option_dt_magic_links_migration_error = 'dt_magic_links_migration_error';

    public static $schedule_last_schedule_run = 'last_schedule_run';
    public static $schedule_last_success_send = 'last_success_send';

    public static $channel_email_id = 'dt_channel_email';
    public static $channel_email_name = 'Email Sending Channel';

    public static $assigned_user_type_id_users = 'users';
    public static $assigned_user_type_id_contacts = 'contacts';
    public static $assigned_supported_types = [ 'user', 'member', 'contact' ];

    public static $prefix_templates_id = 'templates_';


    public static function fetch_magic_link_types(): array {
        $filtered_types = apply_filters( 'dt_magic_url_register_types', [] );

        // Only focus on magic link related types
        $magic_link_types = [];
        if ( ! empty( $filtered_types ) ) {
            foreach ( $filtered_types as $root ) {
                if ( ! empty( $root ) && is_array( $root ) ) {
                    foreach ( $root as $type ) {
                        if ( isset( $type['meta']['app_type'] ) && $type['meta']['app_type'] === 'magic_link' ) {

                            // Determine if field label refreshing is required
                            if ( isset( $type['meta']['fields_refresh'] ) && $type['meta']['fields_refresh']['enabled'] ) {

                                // Fetch corresponding field settings
                                $field_settings = DT_Posts::get_post_field_settings( $type['meta']['fields_refresh']['post_type'] );
                                if ( ! empty( $field_settings ) ) {

                                    $refreshed_fields = [];

                                    // Load all fields from post type settings
                                    if ( isset( $type['meta']['fields_refresh']['load_all'] ) && $type['meta']['fields_refresh']['load_all'] ) {
                                        $field_settings = DT_Posts::get_post_field_settings( $type['meta']['fields_refresh']['post_type'], false );
                                        $supported_types = [
                                            'number',
                                            'textarea',
                                            'text',
                                            'key_select',
                                            'date',
                                            'communication_channel',
                                            'multi_select',
                                            'tags',
                                            'location',
                                            'location_meta',
                                            'connection',
                                        ];
                                        $excluded_fields = [ 'last_modified', 'post_date' ];
                                        foreach ( $field_settings as $key => $value ) {
                                            // only allow supported field types
                                            if ( !in_array( $value['type'], $supported_types ) ) {
                                                continue;
                                            }
                                            // ignore hidden fields
                                            if ( isset( $value['hidden'] ) && $value['hidden'] ) {
                                                continue;
                                            }
                                            // ignore excluded system fields
                                            if ( in_array( $key, $excluded_fields ) ) {
                                                continue;
                                            }
                                            $refreshed_fields[] = [
                                                'id' => $key,
                                                'label' => $value['name'],
                                            ];
                                        }
                                    }

                                    // Refresh field label, assuming it is not to be ignored
                                    foreach ( $type['meta']['fields'] ?? [] as $field ) {
                                        if ( ! in_array( $field['id'], $type['meta']['fields_refresh']['ignore_ids'] ) ) {
                                            $field['label'] = $field_settings[ $field['id'] ]['name'];
                                        }
                                        $refreshed_fields[] = $field;
                                    }

                                    // Update type fields
                                    $type['meta']['fields'] = $refreshed_fields;
                                }
                            }

                            // Assign type to returning array
                            $magic_link_types[] = $type;
                        }
                    }
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

    public static function fetch_user_magic_links( $user_id ): array {
        $links = [];
        foreach ( self::fetch_option_link_objs() as $link_obj ) {
            if ( ! empty( $link_obj ) ) {
                $hash = get_user_option( self::generate_magic_link_type_key( $link_obj ), $user_id );
                if ( ! empty( $hash ) ) {
                    $magic_link_type = self::fetch_magic_link_type( $link_obj->type );
                    if ( ! empty( $magic_link_type ) ) {
                        $links[ self::generate_magic_link_type_key( $link_obj ) ][] = trailingslashit( trailingslashit( site_url() ) . $magic_link_type['url_base'] ) . $hash;
                    }
                }
            }
        }

        return $links;
    }

    public static function fetch_post_magic_links( $post_id ): array {
        $links = [];
        foreach ( self::fetch_option_link_objs() as $link_obj ) {
            if ( ! empty( $link_obj ) ) {
                $hash = get_post_meta( $post_id, self::generate_magic_link_type_key( $link_obj ), true );
                if ( ! empty( $hash ) ) {
                    $magic_link_type = self::fetch_magic_link_type( $link_obj->type );
                    if ( ! empty( $magic_link_type ) ) {

                        // When dealing with templates, ensure _magic_key suffix is removed!
                        $magic_link_url_base = $magic_link_type['url_base'];
                        if ( strpos( $magic_link_url_base, 'templates/' ) !== false ) {
                            $magic_link_url_base = str_replace( '_magic_key', '', $magic_link_url_base );
                        }

                        $links[ self::generate_magic_link_type_key( $link_obj ) ][] = trailingslashit( trailingslashit( site_url() ) . $magic_link_url_base ) . $hash;
                    }
                }
            }
        }

        return $links;
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
                        'email'      => self::fetch_wp_users_comms_info( $user['ID'], 'email' ),
                        'links'      => self::fetch_user_magic_links( $user['ID'] )
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
                    $updated_team = self::capture_member_ids( $team );

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

    public static function fetch_dt_groups(): array {
        global $wpdb;

        // Fetch group ids other than teams
        $group_ids = $wpdb->get_results( "
        SELECT DISTINCT(pm.post_id)
        FROM $wpdb->posts p
        LEFT JOIN $wpdb->postmeta as pm ON (p.ID = pm.post_id AND pm.meta_key = 'group_type')
        WHERE pm.meta_value != 'team';
        ", ARRAY_A );

        // Fetch group objects
        if ( ! empty( $group_ids ) ) {
            $groups = [];
            foreach ( $group_ids as $id ) {
                $group = DT_Posts::get_post( 'groups', $id['post_id'], true, false );
                if ( ! empty( $group ) && ! is_wp_error( $group ) && isset( $group['group_type']['key'] ) && $group['group_type']['key'] !== 'team' ) {

                    // Ensure group members also contain corresponding user ids
                    $updated_group = self::capture_member_ids( $group );

                    // Only capture what we need
                    $groups[] = [
                        'id'      => $updated_group['ID'],
                        'name'    => $updated_group['name'],
                        'members' => $updated_group['members']
                    ];
                }
            }

            return $groups;
        }

        return [];
    }

    private static function capture_member_ids( $members ): array {
        if ( ! empty( $members ) && isset( $members['members'] ) ) {
            foreach ( $members['members'] as $key => $member ) {

                // Fetch corresponding wp-user/contact ids for members; which currently default to post ids!
                $corresponds_to_user_id = get_post_meta( $member['ID'], "corresponds_to_user", true );
                $has_wp_user            = ( ! empty( $corresponds_to_user_id ) );

                $members['members'][ $key ]['type']    = $has_wp_user ? 'wp_user' : 'post';
                $members['members'][ $key ]['type_id'] = $has_wp_user ? $corresponds_to_user_id : $member['ID'];
                $members['members'][ $key ]['phone']   = self::fetch_dt_contacts_comms_info( $member['ID'], 'contact_phone' );
                $members['members'][ $key ]['email']   = $has_wp_user ? self::fetch_wp_users_comms_info( $corresponds_to_user_id, 'email' ) : self::fetch_dt_contacts_comms_info( $member['ID'], 'contact_email' );
                $members['members'][ $key ]['links']   = $has_wp_user ? self::fetch_user_magic_links( $corresponds_to_user_id ) : self::fetch_post_magic_links( $member['ID'] );
            }
        }

        return $members;
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

    private static function fetch_wp_users_comms_info( $user_id, $field_id ): array {
        $user_info = get_userdata( $user_id );

        $comms = [];
        if ( ! empty( $user_info ) && ! is_wp_error( $user_info ) ) {
            switch ( $field_id ) {
                case 'email':
                    if ( isset( $user_info->data->user_email ) ) {
                        $comms[] = $user_info->data->user_email;
                    }
                    break;
            }
        }

        return $comms;
    }

    public static function fetch_option_link_objs() {
        $option = get_option( self::$option_dt_magic_links_objects );

        // Ensure predicted expiration dates are kept accurate!
        if ( ! empty( $option ) ) {

            $link_objs = [];
            foreach ( json_decode( $option ) as $id => $link_obj ) {
                $link_objs[ $link_obj->id ] = $link_obj;
            }

            return (object) $link_objs;
        }

        return (object) [];
    }

    public static function delete_option_link_obj( $link_obj_id ) {
        $option_link_objs = self::fetch_option_link_objs();

        // Do we have a match?
        if ( isset( $option_link_objs->{$link_obj_id} ) ) {

            // Remove link object from options
            unset( $option_link_objs->{$link_obj_id} );

            // Save changes
            update_option( self::$option_dt_magic_links_objects, json_encode( $option_link_objs ) );
        }
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

    public static function is_already_assigned( $id, $link_obj ): bool {
        $assigned = false;

        foreach ( $link_obj->assigned ?? [] as $user ) {
            if ( $user->id === $id ) {
                $assigned = true;
            }
        }

        return $assigned;
    }

    public static function fetch_option( $option ) {
        return get_option( $option );
    }

    public static function update_option( $option, $value ) {
        update_option( $option, $value );
    }

    public static function option_exists( $option ): bool {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) );

        return is_object( $row );
    }

    public static function generate_magic_link_type_key( $link_obj ) {
        return ( ! empty( $link_obj ) && isset( $link_obj->id, $link_obj->type ) ) ? trim( $link_obj->type ) . '_' . $link_obj->id : null;
    }

    public static function update_magic_links( $link_obj, $assigned, $delete ) {
        $magic_key = self::generate_magic_link_type_key( $link_obj );
        if ( ! empty( $magic_key ) && ! empty( $assigned ) ) {
            foreach ( $assigned ?? [] as $user ) {

                // Only process types: user + members
                if ( isset( $user->type ) && in_array( strtolower( trim( $user->type ) ), self::$assigned_supported_types ) ) {
                    if ( isset( $user->dt_id, $user->sys_type ) ) {

                        // Delete/Create accordingly, based on various flags!
                        if ( $delete === true ) {
                            switch ( strtolower( trim( $user->sys_type ) ) ) {
                                case 'wp_user':
                                    delete_user_option( $user->dt_id, $magic_key );
                                    break;
                                case 'post':
                                    delete_post_meta( $user->dt_id, $magic_key );
                                    break;
                            }
                        } else {
                            switch ( strtolower( trim( $user->sys_type ) ) ) {
                                case 'wp_user':
                                    update_user_option( $user->dt_id, $magic_key, dt_create_unique_key() );
                                    break;
                                case 'post':
                                    update_post_meta( $user->dt_id, $magic_key, dt_create_unique_key() );
                                    break;
                            }
                        }
                    }
                }
            }
        }
    }

    public static function extract_assigned_user_deltas( $assigned_a, $assigned_b ) {
        if ( ! empty( $assigned_a ) && ! empty( $assigned_b ) ) {
            return array_udiff( $assigned_a, $assigned_b, function ( $user_a, $user_b ) {
                return $user_a->dt_id - $user_b->dt_id;
            } );
        }

        return [];
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

                // Determine if user's magic link should be refreshed beforehand
                if ( $link_obj->schedule->links_refreshed_before_send || $link_obj->link_manage->links_never_expires || ( $link_obj->link_manage->links_expire_auto_refresh_enabled && self::has_links_expired( $link_obj->link_manage->links_never_expires, $user->links_expire_within_base_ts, $link_obj->link_manage->links_expire_within_amount, $link_obj->link_manage->links_expire_within_time_unit ) ) ) {

                    // Ensure to handle non-expiring links a little differently
                    if ( ! $link_obj->link_manage->links_never_expires ) {
                        self::update_magic_links( $link_obj, [ $user ], false );
                        $user   = self::refresh_user_links_expiration_values( $user, time(), $link_obj->link_manage->links_expire_within_amount, $link_obj->link_manage->links_expire_within_time_unit, $link_obj->link_manage->links_never_expires );
                        $logs[] = self::logging_create( 'Refreshed user [' . $user->name . '] magic link.' );

                    } else {
                        $user = self::refresh_user_links_expiration_values( $user, $user->links_expire_within_base_ts, $link_obj->link_manage->links_expire_within_amount, $link_obj->link_manage->links_expire_within_time_unit, $link_obj->link_manage->links_never_expires );
                    }
                } else if ( self::has_links_expired( $link_obj->link_manage->links_never_expires, $user->links_expire_within_base_ts, $link_obj->link_manage->links_expire_within_amount, $link_obj->link_manage->links_expire_within_time_unit ) ) {

                    // Nuke any stale, expired magic links
                    self::update_magic_links( $link_obj, [ $user ], true );
                    $user->links_expire_within_base_ts  = '';
                    $user->links_expire_on_ts           = '';
                    $user->links_expire_on_ts_formatted = '';
                    $logs[]                             = self::logging_create( 'Deleted user [' . $user->name . '] magic link.' );

                }

                // Construct message to be sent
                $message = self::build_send_msg( $link_obj, $user, $magic_link_type['url_base'] );
                if ( $message !== '' ) {

                    // Dispatch message using specified sending channel
                    $send_result = call_user_func( $sending_channel['send'], [
                        'user'    => $user,
                        'message' => $message
                    ] );

                    // A successful outcome....?
                    if ( ! is_wp_error( $send_result ) && $send_result ) {

                        // Update last successful send timestamp
                        $updated_link_obj = self::update_schedule_settings( $link_obj->id, self::$schedule_last_success_send, time() );

                        // Capture updated sections of interest
                        if ( ! empty( $updated_link_obj ) ) {
                            $link_obj->schedule = $updated_link_obj->schedule;
                        }

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

        // Echo back link object & user; which may have been updated
        return [
            'link_obj' => $link_obj,
            'user'     => $user
        ];
    }

    public static function determine_assigned_user_type( $user ): string {
        if ( in_array( strtolower( trim( $user->type ) ), self::$assigned_supported_types ) ) {
            switch ( strtolower( trim( $user->sys_type ) ) ) {
                case 'wp_user':
                    return self::$assigned_user_type_id_users;
                case 'post':
                    return self::$assigned_user_type_id_contacts;
            }
        }

        return '';
    }

    public static function determine_assigned_user_name( $user ) {
        $name = '';
        switch ( self::determine_assigned_user_type( $user ) ) {
            case self::$assigned_user_type_id_users:

                $user_info = get_userdata( $user->dt_id );
                if ( ! empty( $user_info ) && ! is_wp_error( $user_info ) && isset( $user_info->data->display_name ) ) {
                    $name = $user_info->data->display_name;
                }
                break;

            case self::$assigned_user_type_id_contacts:

                $user_contact = DT_Posts::get_post( 'contacts', $user->dt_id, true, false );
                if ( ! empty( $user_contact ) && ! is_wp_error( $user_contact ) ) {
                    $name = $user_contact['title'] ?? '';
                }
                break;
        }

        return $name;
    }

    private static function build_send_msg( $link_obj, $user, $magic_link_url_base ): string {
        $msg = '';

        // First, build magic link url
        $link_url = self::build_magic_link_url( $link_obj, $user, $magic_link_url_base );
        if ( ! empty( $link_obj->message ) && ! empty( $link_url ) && $link_url !== '' ) {

            // Format expired date
            $expire_on = self::fetch_links_expired_formatted_date( $link_obj->link_manage->links_never_expires, $user->links_expire_within_base_ts, $link_obj->link_manage->links_expire_within_amount, $link_obj->link_manage->links_expire_within_time_unit );

            // Construct message, having replaced placeholders
            $msg = str_replace(
                [
                    '{{name}}',
                    '{{link}}',
                    '{{time}}',
                    '{{time_relative}}'
                ],
                [
                    self::determine_assigned_user_name( $user ),
                    $link_url,
                    $expire_on,
                    self::determine_relative_date( self::determine_links_expiry_point( $link_obj->link_manage->links_expire_within_amount, $link_obj->link_manage->links_expire_within_time_unit, $user->links_expire_within_base_ts ) )
                ],
                $link_obj->message
            );
        }

        return $msg;
    }

    public static function fetch_default_send_msg(): string {
        return 'Hello {{name}},

Please follow the link below and update records!

{{link}}

As a reminder, the above link will expire {{time_relative}} on {{time}}

Thanks!';
    }

    public static function fetch_default_email_subject(): string {
        return __( 'Smart Link', 'disciple_tools' );
    }

    public static function build_magic_link_url( $link_obj, $user, $magic_link_url_base, $with_params = true ): string {
        $hash           = '';
        $magic_link_key = self::generate_magic_link_type_key( $link_obj );
        switch ( strtolower( trim( $user->sys_type ) ) ) {
            case 'wp_user':
                $hash = get_user_option( $magic_link_key, $user->dt_id );
                break;
            case 'post':
                $hash = get_post_meta( $user->dt_id, $magic_link_key, true );
                break;
        }

        // Assuming a valid hash has been located, build url accordingly.
        if ( ! empty( $hash ) ) {

            // When dealing with templates, ensure _magic_key suffix is removed!
            if ( strpos( $magic_link_url_base, 'templates/' ) !== false ) {
                $magic_link_url_base = str_replace( '_magic_key', '', $magic_link_url_base );
            }

            // Proceed with url generation
            $url = trailingslashit( trailingslashit( site_url() ) . $magic_link_url_base ) . $hash;
            if ( $with_params === true ) {
                $url .= '?id=' . $link_obj->id . '&type=' . $user->sys_type;
            }

            return $url;
        }

        return '';
    }

    public static function update_schedule_settings( $link_obj_id, $setting, $value ) {
        $link_obj = self::fetch_option_link_obj( $link_obj_id );

        if ( ! empty( $link_obj ) ) {
            $link_obj->schedule->{$setting} = $value;
            self::update_option_link_obj( $link_obj );
        }

        return $link_obj;
    }

    public static function has_obj_expired( $never_expires, $expiry_point ): bool {
        if ( $never_expires === true ) {
            return false;
        }

        return $expiry_point < time(); // In the past!
    }

    public static function determine_links_expiry_point( $amt, $time_unit, $base_ts ) {
        return strtotime( '+' . $amt . ' ' . $time_unit, $base_ts );
    }

    public static function has_links_expired( $never_expires, $base_ts, $amt, $time_unit ): bool {
        if ( $never_expires === true || in_array( strtolower( $never_expires ), [ 'true' ] ) ) {
            return false;
        }

        $expiry_point = self::determine_links_expiry_point( $amt, $time_unit, $base_ts );

        return $expiry_point < time(); // In the past!
    }

    public static function fetch_links_expired_formatted_date( $never_expires, $base_ts, $amt, $time_unit ): string {
        if ( $never_expires === true || in_array( strtolower( $never_expires ), [ 'true' ] ) ) {
            return __( 'Never', 'disciple_tools' );
        }

        $expiry_point = self::determine_links_expiry_point( $amt, $time_unit, $base_ts );

        return self::format_timestamp_in_local_time_zone( $expiry_point );
    }

    public static function determine_relative_date( $ts ): string {
        if ( ! ctype_digit( $ts ) ) {
            $ts = strtotime( $ts );
        }

        $diff = time() - $ts;
        if ( $diff == 0 ) {
            return 'now';

        } elseif ( $diff > 0 ) {
            $day_diff = floor( $diff / 86400 );
            if ( $day_diff == 0 ) {
                if ( $diff < 60 ) {
                    return 'just now';
                }
                if ( $diff < 120 ) {
                    return '1 minute ago';
                }
                if ( $diff < 3600 ) {
                    return floor( $diff / 60 ) . ' minutes ago';
                }

                // compare with current time to see if it was posted yesterday
                if ( gmdate( 'H' ) < ( $diff / 3600 ) ) {
                    return 'Yesterday';
                }

                // if today
                if ( $diff < 7200 ) {
                    return '1 hour ago';
                }
                if ( $diff < 86400 ) {
                    return floor( $diff / 3600 ) . ' hours ago';
                }
            }
            if ( $day_diff == 1 ) {
                return 'Yesterday';
            }
            if ( $day_diff < 7 ) {
                return $day_diff . ' days ago';
            }
            if ( $day_diff < 31 ) {
                return ceil( $day_diff / 7 ) . ' weeks ago';
            }
            if ( $day_diff < 60 ) {
                return 'last month';
            }

            return gmdate( 'F Y', $ts );

        } else {

            $diff     = abs( $diff );
            $day_diff = floor( $diff / 86400 );
            if ( $day_diff == 0 ) {
                if ( $diff < 120 ) {
                    return 'in a minute';
                }
                if ( $diff < 3600 ) {
                    return 'in ' . floor( $diff / 60 ) . ' minutes';
                }
                if ( $diff < 7200 ) {
                    return 'in an hour';
                }
                if ( $diff < 86400 ) {
                    return 'in ' . floor( $diff / 3600 ) . ' hours';
                }
            }
            if ( $day_diff == 1 ) {
                return 'Tomorrow';
            }
            if ( $day_diff < 4 ) {
                return gmdate( 'l', $ts );
            }
            if ( $day_diff < 7 + ( 7 - gmdate( 'w' ) ) ) {
                return 'next week';
            }
            if ( ceil( $day_diff / 7 ) < 4 ) {
                return 'in ' . ceil( $day_diff / 7 ) . ' weeks';
            }
            if ( intval( gmdate( 'n', $ts ) ) == ( intval( gmdate( 'n' ) ) + 1 ) ) {
                return 'next month';
            }

            return gmdate( 'F Y', $ts );
        }
    }

    public static function format_timestamp_in_local_time_zone( $timestamp ): string {
        $option            = self::fetch_option( self::$option_dt_magic_links_local_time_zone );
        $default_time_zone = ! empty( $option ) ? $option : 'UTC';

        try {
            $dt = new DateTime();
            $dt->setTimezone( new DateTimeZone( $default_time_zone ) );
            $dt->setTimestamp( $timestamp );

            return $dt->format( 'F j, Y h:i:s A T' );

        } catch ( Exception $e ) {
            return dt_format_date( $timestamp, 'long' );
        }
    }

    public static function fetch_endpoint_send_now_url(): string {
        return trailingslashit( site_url() ) . 'wp-json/disciple_tools_magic_links/v1/send_now';
    }

    public static function fetch_endpoint_user_links_manage_url(): string {
        return trailingslashit( site_url() ) . 'wp-json/disciple_tools_magic_links/v1/user_links_manage';
    }

    public static function fetch_endpoint_assigned_manage_url(): string {
        return trailingslashit( site_url() ) . 'wp-json/disciple_tools_magic_links/v1/assigned_manage';
    }

    public static function fetch_endpoint_get_post_record_url(): string {
        return trailingslashit( site_url() ) . 'wp-json/disciple_tools_magic_links/v1/get_post_record';
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
                    'last_success_send'      => self::format_timestamp_in_local_time_zone( $link_obj->schedule->last_success_send ),
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
            if ( in_array( trim( strtolower( $user->type ) ), self::$assigned_supported_types ) ) {

                $posts = [];

                // Posts list to be generated accordingly, based on user type
                switch ( self::determine_assigned_user_type( $user ) ) {
                    case self::$assigned_user_type_id_users:

                        // Switch current user id status and fetch associated contacts list
                        wp_set_current_user( $user->dt_id );

                        // Fetch associated contacts list
                        $posts = DT_Posts::list_posts( 'contacts', [
                            'limit'  => 1000,
                            'fields' => [
                                [
                                    'assigned_to' => [ 'me' ],
                                    "subassigned" => [ 'me' ]
                                ],
                                "overall_status" => [
                                    "new",
                                    "unassigned",
                                    "assigned",
                                    "active"
                                ]
                            ]
                        ] );
                        break;

                    case self::$assigned_user_type_id_contacts:

                        $post = DT_Posts::get_post( 'contacts', $user->dt_id, true, false );
                        if ( ! empty( $post ) && ! is_wp_error( $post ) ) {
                            $posts['total']   = 1;
                            $posts['posts'][] = $post;
                        }
                        break;
                }

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

    public static function list_available_time_zones(): array {
        $time_zones = [
            'UTC',
            'Africa/Abidjan',
            'Africa/Accra',
            'Africa/Addis_Ababa',
            'Africa/Algiers',
            'Africa/Asmara',
            'Africa/Bamako',
            'Africa/Bangui',
            'Africa/Banjul',
            'Africa/Bissau',
            'Africa/Blantyre',
            'Africa/Brazzaville',
            'Africa/Bujumbura',
            'Africa/Cairo',
            'Africa/Casablanca',
            'Africa/Ceuta',
            'Africa/Conakry',
            'Africa/Dakar',
            'Africa/Dar_es_Salaam',
            'Africa/Djibouti',
            'Africa/Douala',
            'Africa/El_Aaiun',
            'Africa/Freetown',
            'Africa/Gaborone',
            'Africa/Harare',
            'Africa/Johannesburg',
            'Africa/Juba',
            'Africa/Kampala',
            'Africa/Khartoum',
            'Africa/Kigali',
            'Africa/Kinshasa',
            'Africa/Lagos',
            'Africa/Libreville',
            'Africa/Lome',
            'Africa/Luanda',
            'Africa/Lubumbashi',
            'Africa/Lusaka',
            'Africa/Malabo',
            'Africa/Maputo',
            'Africa/Maseru',
            'Africa/Mbabane',
            'Africa/Mogadishu',
            'Africa/Monrovia',
            'Africa/Nairobi',
            'Africa/Ndjamena',
            'Africa/Niamey',
            'Africa/Nouakchott',
            'Africa/Ouagadougou',
            'Africa/Porto-Novo',
            'Africa/Sao_Tome',
            'Africa/Tripoli',
            'Africa/Tunis',
            'Africa/Windhoek',
            'America/New_York',
            'America/Chicago',
            'America/Denver',
            'America/Phoenix',
            'America/Los_Angeles',
            'America/Anchorage',
            'America/Adak',
            'America/St_Johns',
            'America/Halifax',
            'America/Blanc-Sablon',
            'America/Toronto',
            'America/Atikokan',
            'America/Winnipeg',
            'America/Regina',
            'America/Edmonton',
            'America/Creston',
            'America/Vancouver',
            'America/Tijuana',
            'America/Mazatlan',
            'America/Chihuahua',
            'America/Mexico_City',
            'America/Matamoros',
            'America/Monterrey',
            'America/Merida',
            'America/Cancun',
            'America/Rio_branco',
            'America/Belem',
            'America/Bahia',
            'America/Sao_Paulo',
            'America/Cuiaba',
            'America/Fortaleza',
            'America/Recife',
            'America/Boa_Vista',
            'America/Maceio',
            'America/Araguaia',
            'America/Manaus',
            'America/Campo_Grande',
            'America/Porto_Velho',
            'Asia/Aden',
            'Asia/Almaty',
            'Asia/Amman',
            'Asia/Anadyr',
            'Asia/Aqtau',
            'Asia/Aqtobe',
            'Asia/Ashgabat',
            'Asia/Atyrau',
            'Asia/Baghdad',
            'Asia/Bahrain',
            'Asia/Baku',
            'Asia/Bangkok',
            'Asia/Barnaul',
            'Asia/Beirut',
            'Asia/Bishkek',
            'Asia/Brunei',
            'Asia/Chita',
            'Asia/Choibalsan',
            'Asia/Colombo',
            'Asia/Damascus',
            'Asia/Dhaka',
            'Asia/Dili',
            'Asia/Dubai',
            'Asia/Dushanbe',
            'Asia/Famagusta',
            'Asia/Gaza',
            'Asia/Hebron',
            'Asia/Ho_Chi_Minh',
            'Asia/Hong_Kong',
            'Asia/Hovd',
            'Asia/Irkutsk',
            'Asia/Jakarta',
            'Asia/Jayapura',
            'Asia/Jerusalem',
            'Asia/Kabul',
            'Asia/Kamchatka',
            'Asia/Karachi',
            'Asia/Kathmandu',
            'Asia/Khandyga',
            'Asia/Kolkata',
            'Asia/Krasnoyarsk',
            'Asia/Kuala_Lumpur',
            'Asia/Kuching',
            'Asia/Kuwait',
            'Asia/Macau',
            'Asia/Magadan',
            'Asia/Makassar',
            'Asia/Manila',
            'Asia/Muscat',
            'Asia/Nicosia',
            'Asia/Novokuznetsk',
            'Asia/Novosibirsk',
            'Asia/Omsk',
            'Asia/Oral',
            'Asia/Phnom_Penh',
            'Asia/Pontianak',
            'Asia/Pyongyang',
            'Asia/Qatar',
            'Asia/Qostanay',
            'Asia/Qyzylorda',
            'Asia/Riyadh',
            'Asia/Sakhalin',
            'Asia/Samarkand',
            'Asia/Seoul',
            'Asia/Shanghai',
            'Asia/Singapore',
            'Asia/Srednekolymsk',
            'Asia/Taipei',
            'Asia/Tashkent',
            'Asia/Tbilisi',
            'Asia/Tehran',
            'Asia/Thimphu',
            'Asia/Tokyo',
            'Asia/Tomsk',
            'Asia/Ulaanbaatar',
            'Asia/Urumqi',
            'Asia/Ust-Nera',
            'Asia/Vientiane',
            'Asia/Vladivostok',
            'Asia/Yakutsk',
            'Asia/Yangon',
            'Asia/Yekaterinburg',
            'Asia/Yerevan',
            'Atlantic/Azores',
            'Atlantic/Bermuda',
            'Atlantic/Canary',
            'Atlantic/Cape_Verde',
            'Atlantic/Faroe',
            'Atlantic/Madeira',
            'Atlantic/Reykjavik',
            'Atlantic/South_Georgia',
            'Atlantic/St_Helena',
            'Atlantic/Stanley',
            'Australia/Adelaide',
            'Australia/Brisbane',
            'Australia/Broken_Hill',
            'Australia/Darwin',
            'Australia/Eucla',
            'Australia/Hobart',
            'Australia/Lindeman',
            'Australia/Lord_Howe',
            'Australia/Melbourne',
            'Australia/Perth',
            'Australia/Sydney',
            'Europe/Amsterdam',
            'Europe/Athens',
            'Europe/Belgrade',
            'Europe/Berlin',
            'Europe/Bratislava',
            'Europe/Brussels',
            'Europe/Bucharest',
            'Europe/Budapest',
            'Europe/Copenhagen',
            'Europe/Dublin',
            'Europe/Gibraltar',
            'Europe/Guernsey',
            'Europe/Helsinki',
            'Europe/Isle_of_Man',
            'Europe/Istanbul',
            'Europe/Jersey',
            'Europe/Kaliningrad',
            'Europe/Kiev',
            'Europe/Lisbon',
            'Europe/London',
            'Europe/Luxembourg',
            'Europe/Madrid',
            'Europe/Malta',
            'Europe/Monaco',
            'Europe/Moscow',
            'Europe/Oslo',
            'Europe/Paris',
            'Europe/Prague',
            'Europe/Rome',
            'Europe/Samara',
            'Europe/Stockholm',
            'Europe/Vatican',
            'Europe/Vienna',
            'Europe/Warsaw',
            'Indian/Antananarivo',
            'Indian/Chagos',
            'Indian/Christmas',
            'Indian/Cocos',
            'Indian/Comoro',
            'Indian/Kerguelen',
            'Indian/Mahe',
            'Indian/Maldives',
            'Indian/Mauritius',
            'Indian/Mayotte',
            'Indian/Reunion',
            'Pacific/Pago_Pago',
            'Pacific/Chuuk',
            'Pacific/Guam',
            'Pacific/Honolulu',
            'Pacific/Majuro',
            'Pacific/Saipan',
            'Pacific/Palau'
        ];

        asort( $time_zones, SORT_STRING );

        return $time_zones;
    }

    public static function get_contact_id_by_user_id( $user_id ) {
        $contact_id = get_user_option( "corresponds_to_contact", $user_id );

        if ( ! empty( $contact_id ) && get_post( $contact_id ) ) {
            return (int) $contact_id;
        }
        $args     = [
            'post_type'  => 'contacts',
            'relation'   => 'AND',
            'meta_query' => [
                [
                    'key'   => "corresponds_to_user",
                    "value" => $user_id
                ],
                [
                    'key'   => "type",
                    "value" => "user"
                ],
            ],
        ];
        $contacts = new WP_Query( $args );
        if ( isset( $contacts->post->ID ) ) {
            update_user_option( $user_id, "corresponds_to_contact", $contacts->post->ID );

            return $contacts->post->ID;
        } else {
            return null;
        }
    }

    public static function get_link_submission_success_js_code() {
        return '
            Toastify({
                text: "' . __( 'Update Successful!', 'disciple-tools-bulk-magic-link-sender' ) . '",
                close: true,
                gravity: "bottom",
                callback: function() {
                    window.location.reload();
                }

            }).showToast();
        ';
    }

    public static function refresh_user_links_expiration_values( $user, $base_ts, $amt, $time_unit, $never_expires ) {
        $user->links_expire_within_base_ts  = $base_ts;
        $user->links_expire_on_ts           = self::determine_links_expiry_point( $amt, $time_unit, $base_ts );
        $user->links_expire_on_ts_formatted = self::fetch_links_expired_formatted_date( $never_expires, $base_ts, $amt, $time_unit );

        return $user;
    }
}
