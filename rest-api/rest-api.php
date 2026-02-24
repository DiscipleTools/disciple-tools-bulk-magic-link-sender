<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.

class Disciple_Tools_Bulk_Magic_Link_Sender_Endpoints {
    /**
     * @todo Set the permissions your endpoint needs
     * @link https://github.com/DiscipleTools/Documentation/blob/master/theme-core/capabilities.md
     * @var string[]
     */
    public $permissions = [ 'manage_dt' ];


    /**
     * @todo define the name of the $namespace
     * @todo define the name of the rest route
     * @todo defne method (CREATABLE, READABLE)
     * @todo apply permission strategy. '__return_true' essentially skips the permission check.
     */
    //See https://github.com/DiscipleTools/disciple-tools-theme/wiki/Site-to-Site-Link for outside of wordpress authentication
    public function add_api_routes() {
        $namespace = 'disciple_tools_magic_links/v1';

        register_rest_route(
            $namespace, '/setup_payload', [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'setup_payload' ],
                'permission_callback' => function ( WP_REST_Request $request ) {
                    return $this->has_permission();
                }
            ]
        );
        register_rest_route(
            $namespace, '/get_post_record', [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_post_record' ],
                'permission_callback' => function ( WP_REST_Request $request ) {
                    return $this->has_permission();
                }
            ]
        );
        register_rest_route(
            $namespace, '/user_links_manage', [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'user_links_manage' ],
                'permission_callback' => function ( WP_REST_Request $request ) {
                    return $this->has_permission();
                }
            ]
        );
        register_rest_route(
            $namespace, '/assigned_manage', [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'assigned_manage' ],
                'permission_callback' => function ( WP_REST_Request $request ) {
                    return $this->has_permission();
                }
            ]
        );
        register_rest_route(
            $namespace, '/send_now', [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'send_now' ],
                'permission_callback' => function ( WP_REST_Request $request ) {
                    return $this->has_permission();
                }
            ]
        );
        register_rest_route(
            $namespace, '/next_scheduled_run', [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'next_scheduled_run' ],
                'permission_callback' => function ( WP_REST_Request $request ) {
                    return $this->has_permission();
                }
            ]
        );
        register_rest_route(
            $namespace, '/report', [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_report' ],
                'permission_callback' => function ( WP_REST_Request $request ) {
                    return $this->has_permission();
                }
            ]
        );
        register_rest_route(
            $namespace, '/references', [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'references' ],
                'permission_callback' => function ( WP_REST_Request $request ) {
                    return $this->has_permission();
                }
            ]
        );
        register_rest_route(
            $namespace, '/typeahead_users_teams_groups', [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'typeahead_users_teams_groups' ],
                'permission_callback' => function ( WP_REST_Request $request ) {
                    return $this->has_permission();
                }
            ]
        );
        register_rest_route(
            $namespace, '/set_link_expiration_direct', [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'set_link_expiration_direct' ],
                'permission_callback' => function ( WP_REST_Request $request ) {
                    return $this->has_permission();
                }
            ]
        );
        register_rest_route(
            $namespace, '/sync_expiration_hash', [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'sync_expiration_hash' ],
                'permission_callback' => function ( WP_REST_Request $request ) {
                    return $this->has_permission();
                }
            ]
        );
        register_rest_route(
            $namespace, '/clear_link_expiration', [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'clear_link_expiration' ],
                'permission_callback' => function ( WP_REST_Request $request ) {
                    return $this->has_permission();
                }
            ]
        );
    }

    public function setup_payload( WP_REST_Request $request ): array{
        $params = $request->get_params();
        $response = [];
        if ( isset( $params['dt_magic_link_types'] ) && filter_var( $params['dt_magic_link_types'], FILTER_VALIDATE_BOOLEAN ) ){
            $response['dt_magic_link_types'] = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_magic_link_types();
        }
        if ( isset( $params['dt_magic_link_templates'] ) && filter_var( $params['dt_magic_link_templates'], FILTER_VALIDATE_BOOLEAN ) ){
            $response['dt_magic_link_templates'] = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_templates );
        }
        if ( isset( $params['dt_magic_link_objects'] ) && filter_var( $params['dt_magic_link_objects'], FILTER_VALIDATE_BOOLEAN ) ){
            $response['dt_magic_link_objects'] = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_objs();
        }
        if ( isset( $params['dt_sending_channels'] ) && filter_var( $params['dt_sending_channels'], FILTER_VALIDATE_BOOLEAN ) ){
            $response['dt_sending_channels'] = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_sending_channels();
        }
        if ( isset( $params['dt_template_messages'] ) && filter_var( $params['dt_template_messages'], FILTER_VALIDATE_BOOLEAN ) ){
            $response['dt_template_messages'] = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_template_messages();
        }

        return $response;
    }

    public function get_post_record( WP_REST_Request $request ): array {

        // Prepare response payload
        $response = [];

        $params = $request->get_params();
        if ( isset( $params['post_type'], $params['post_id'] ) ) {

            // Ensure user, team & group requests, are handled accordingly.
            if ( in_array( $params['post_type'], [ 'dt_users', 'dt_teams', 'dt_groups' ] ) ) {
                switch ( $params['post_type'] ) {
                    case 'dt_users':
                        $response['post'] = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_dt_users( true, [
                            'type' => 'id',
                            'query' => $params['post_id']
                        ] );
                        break;
                    case 'dt_teams':
                        $response['post'] = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_dt_teams( true, [
                            'type' => 'id',
                            'query' => $params['post_id']
                        ] );
                        break;
                    case 'dt_groups':
                        $response['post'] = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_dt_groups( true, [
                            'type' => 'id',
                            'query' => $params['post_id']
                        ] );
                        break;
                }

                $response['success'] = true;
                $response['message'] = 'Successfully loaded ' . $params['post_type'] . ' post record for id: ' . $params['post_id'];
            } else {
                $post = DT_Posts::get_post( $params['post_type'], $params['post_id'], true, false, true );
                if ( ! empty( $post ) && ! is_wp_error( $post ) ) {

                    // Also, check for any associated magic links
                    $post['ml_links'] = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_post_magic_links( $post['ID'] );

                    // Update response payload
                    $response['post']    = $post;
                    $response['success'] = true;
                    $response['message'] = 'Successfully loaded ' . $params['post_type'] . ' post record for id: ' . $params['post_id'];

                } else {
                    $response['success'] = false;
                    $response['message'] = 'Unable to locate a valid ' . $params['post_type'] . ' post record for id: ' . $params['post_id'];
                }
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unable to execute request, due to missing parameters.';
        }

        return $response;
    }

    public function user_links_manage( WP_REST_Request $request ): array {

        // Prepare response payload
        $response = [];

        $params = $request->get_params();
        if ( isset( $params['action'], $params['assigned'], $params['link_obj_id'], $params['magic_link_type'] ) ) {

            // Adjust assigned array shape, to ensure it is processed accordingly further downstream
            $assigned = json_decode( json_encode( $params['assigned'] ) );

            // Attempt to load link object based on submitted id
            $link_obj = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_obj( $params['link_obj_id'] );

            // Execute accordingly, based on specified action
            switch ( $params['action'] ) {
                case 'refresh':
                    Disciple_Tools_Bulk_Magic_Link_Sender_API::update_magic_links( $link_obj, $assigned, false );

                    // Also update base timestamp and future expiration points
                    if ( isset( $params['links_expire_within_amount'], $params['links_expire_within_time_unit'], $params['links_never_expires'] ) ) {

                        $base_ts       = time();
                        $amt           = $params['links_expire_within_amount'];
                        $time_unit     = $params['links_expire_within_time_unit'];
                        $never_expires = in_array( strtolower( $params['links_never_expires'] ), [ 'true' ] );

                        // Iterate over all assigned and update their respective expiration timestamps
                        foreach ( $assigned ?? [] as &$member ) {
                            $member = Disciple_Tools_Bulk_Magic_Link_Sender_API::refresh_links_expiration_values( $member, $base_ts, $amt, $time_unit, $never_expires );

                            // Store expiration settings in member for sync
                            $member->links_expire_within_amount = $amt;
                            $member->links_expire_within_time_unit = $time_unit;
                            $member->links_never_expires = $never_expires;

                            // Sync expiration to post_meta/user_option for consistency
                            $this->sync_expiration_to_meta( $link_obj, $member );
                        }
                    }
                    break;

                case 'delete':
                    Disciple_Tools_Bulk_Magic_Link_Sender_API::update_magic_links( $link_obj, $assigned, true );

                    // Iterate over all assigned and reset their respective expiration timestamps
                    foreach ( $assigned ?? [] as &$member ) {
                        $member->links_expire_within_base_ts  = '';
                        $member->links_expire_on_ts           = '';
                        $member->links_expire_on_ts_formatted = '';

                        // Sync cleared expiration to post_meta/user_option
                        $this->sync_expiration_to_meta( $link_obj, $member );
                    }
                    break;
            }

            // Save updated link object
            $link_obj->assigned = $assigned;
            Disciple_Tools_Bulk_Magic_Link_Sender_API::update_option_link_obj( $link_obj );

            // Ensure current user has sufficient capabilities/roles for the tasks ahead!
            $current_user = wp_get_current_user();
            if ( ! empty( $current_user ) && ! is_wp_error( $current_user ) && ! current_user_can( 'access_contacts' ) ) {
                $current_user->add_role( 'access_contacts' );
            }

            // Return original assigned array + updated users, teams & groups
            $response['success']   = true;
            $response['message']   = 'User links management action[' . $params['action'] . '] successfully executed.';
            $response['assigned']  = $assigned;

        } else {
            $response['success'] = false;
            $response['message'] = 'Unable to execute action, due to missing parameters.';
        }

        return $response;
    }

    public function assigned_manage( WP_REST_Request $request ): array {

        // Prepare response payload
        $response = [];

        $params = $request->get_params();
        if ( isset( $params['action'], $params['record'], $params['link_obj_id'], $params['magic_link_type'] ) ) {

            // Adjust assigned array shape, to ensure it is processed accordingly further downstream
            $record = json_decode( json_encode( $params['record'] ) );

            // Execute accordingly, based on specified action
            switch ( $params['action'] ) {
                case 'add':

                    // Load and update link object with new assignment
                    $link_obj = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_obj( $params['link_obj_id'] );
                    if ( ! empty( $link_obj ) && isset( $link_obj->id ) && ! Disciple_Tools_Bulk_Magic_Link_Sender_API::is_already_assigned( $record->id, $link_obj ) ) {

                        // Update link object accordingly
                        $link_obj->type       = $params['magic_link_type'];

                        /**
                         * If record is of supported type, then also generate a new link
                         * which is also returned within response payload!
                         */
                        if ( in_array( strtolower( trim( $record->type ) ), Disciple_Tools_Bulk_Magic_Link_Sender_API::$assigned_supported_types ) ) {

                            // Create new magic link
                            Disciple_Tools_Bulk_Magic_Link_Sender_API::update_magic_links( $link_obj, [ $record ], false );

                            // Also update base timestamp and future expiration points
                            if ( isset( $params['links_expire_within_amount'], $params['links_expire_within_time_unit'], $params['links_never_expires'] ) ) {

                                $base_ts       = time();
                                $amt           = $params['links_expire_within_amount'];
                                $time_unit     = $params['links_expire_within_time_unit'];
                                $never_expires = in_array( strtolower( $params['links_never_expires'] ), [ 'true' ] );

                                $record = Disciple_Tools_Bulk_Magic_Link_Sender_API::refresh_links_expiration_values( $record, $base_ts, $amt, $time_unit, $never_expires );

                                // Store expiration settings in record for sync
                                $record->links_expire_within_amount = $amt;
                                $record->links_expire_within_time_unit = $time_unit;
                                $record->links_never_expires = $never_expires;

                                // Sync expiration to post_meta/user_option for consistency
                                $this->sync_expiration_to_meta( $link_obj, $record );

                            }

                            // Capture newly created magic link in url form
                            $magic_link_type = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_magic_link_type( $link_obj->type );
                            $response['ml_links'][Disciple_Tools_Bulk_Magic_Link_Sender_API::generate_magic_link_type_key( $link_obj )] = [
                                'url' => Disciple_Tools_Bulk_Magic_Link_Sender_API::build_magic_link_url( $link_obj, $record, $magic_link_type['url_base'], false ),
                                'expires' => [
                                    'ts' => $record->links_expire_on_ts ?? '',
                                    'ts_formatted' => $record->links_expire_on_ts_formatted ?? '---',
                                    'ts_base' => $record->links_expire_within_base_ts ?? ''
                                ]
                            ];
                        }

                        // Add record to the collective
                        $link_obj->assigned[] = $record;

                        // Save updated link object
                        Disciple_Tools_Bulk_Magic_Link_Sender_API::update_option_link_obj( $link_obj );

                        // All is well.. ;)
                        $response['success'] = true;
                        $response['record']  = $record;

                    } else {
                        $response['success'] = false;
                        $response['message'] = 'Unable to execute action[' . $params['action'] . '], due to invalid link object and/or record already assigned.';
                    }

                    break;

                case 'delete':

                    // Load and update link object with new assignment
                    $link_obj = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_obj( $params['link_obj_id'] );
                    if ( ! empty( $link_obj ) && isset( $link_obj->id ) && Disciple_Tools_Bulk_Magic_Link_Sender_API::is_already_assigned( $record->id, $link_obj ) ) {

                        // Update link object accordingly
                        $updated_assigned = [];
                        foreach ( $link_obj->assigned ?? [] as $already_assigned ) {
                            if ( $already_assigned->id !== $record->id ) {
                                $updated_assigned[] = $already_assigned;
                            }
                        }
                        $link_obj->assigned = $updated_assigned;
                        $link_obj->type     = $params['magic_link_type'];

                        // Save updated link object
                        Disciple_Tools_Bulk_Magic_Link_Sender_API::update_option_link_obj( $link_obj );

                        /**
                         * If record is of supported type, then also attempt to remove any
                         * associated magic links.
                         */
                        if ( in_array( strtolower( trim( $record->type ) ), Disciple_Tools_Bulk_Magic_Link_Sender_API::$assigned_supported_types ) ) {
                            Disciple_Tools_Bulk_Magic_Link_Sender_API::update_magic_links( $link_obj, [ $record ], true );
                        }

                        // All is well.. ;)
                        $response['success'] = true;

                    } else {
                        $response['success'] = false;
                        $response['message'] = 'Unable to execute action[' . $params['action'] . '], due to invalid link object and/or record not already assigned.';
                    }

                    break;

                case 'update_expiration':

                    // Load link object
                    $link_obj = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_obj( $params['link_obj_id'] );
                    if ( ! empty( $link_obj ) && isset( $link_obj->id ) && Disciple_Tools_Bulk_Magic_Link_Sender_API::is_already_assigned( $record->id, $link_obj ) ) {

                        // Find and update the matching assigned record
                        $updated = false;
                        foreach ( $link_obj->assigned ?? [] as &$assigned_record ) {
                            if ( $assigned_record->id === $record->id ) {

                                // Update expiration values if provided
                                if ( isset( $params['links_expire_within_amount'], $params['links_expire_within_time_unit'], $params['links_never_expires'] ) ) {

                                    $base_ts       = ! empty( $assigned_record->links_expire_within_base_ts ) ? $assigned_record->links_expire_within_base_ts : time();
                                    $amt           = $params['links_expire_within_amount'];
                                    $time_unit     = $params['links_expire_within_time_unit'];
                                    $never_expires = in_array( strtolower( $params['links_never_expires'] ), [ 'true' ] );

                                    // If never expires is false and we have amount/time_unit, use current time as base
                                    if ( ! $never_expires && ! empty( $amt ) && ! empty( $time_unit ) ) {
                                        $base_ts = time();
                                    }

                                    $assigned_record = Disciple_Tools_Bulk_Magic_Link_Sender_API::refresh_links_expiration_values( $assigned_record, $base_ts, $amt, $time_unit, $never_expires );

                                    // Sync expiration to post_meta/user_option for consistency
                                    $this->sync_expiration_to_meta( $link_obj, $assigned_record );

                                    $updated = true;
                                }

                                // Return updated expiration info
                                $magic_link_type = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_magic_link_type( $link_obj->type );
                                $response['ml_links'][Disciple_Tools_Bulk_Magic_Link_Sender_API::generate_magic_link_type_key( $link_obj )] = [
                                    'url' => Disciple_Tools_Bulk_Magic_Link_Sender_API::build_magic_link_url( $link_obj, $assigned_record, $magic_link_type['url_base'], false ),
                                    'expires' => [
                                        'ts' => $assigned_record->links_expire_on_ts ?? '',
                                        'ts_formatted' => $assigned_record->links_expire_on_ts_formatted ?? '---',
                                        'ts_base' => $assigned_record->links_expire_within_base_ts ?? ''
                                    ]
                                ];

                                $response['record'] = $assigned_record;
                                break;
                            }
                        }

                        if ( $updated ) {
                            // Save updated link object
                            Disciple_Tools_Bulk_Magic_Link_Sender_API::update_option_link_obj( $link_obj );

                            $response['success'] = true;
                            $response['message'] = 'Expiration updated successfully.';
                        } else {
                            $response['success'] = false;
                            $response['message'] = 'Unable to update expiration, due to missing parameters.';
                        }
                    } else {
                        $response['success'] = false;
                        $response['message'] = 'Unable to execute action[' . $params['action'] . '], due to invalid link object and/or record not assigned.';
                    }

                    break;

                case 'link':
                    // TODO...
                    break;
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unable to execute action, due to missing parameters.';
        }

        return $response;
    }

    public function send_now( WP_REST_Request $request ): array {

        // Prepare response payload
        $response = [];

        // Ensure required parameters have been specified
        $params = $request->get_params();
        if ( isset( $params['assigned'], $params['link_obj_id'], $params['links_expire_within_amount'], $params['links_expire_within_time_unit'], $params['links_never_expires'], $params['links_refreshed_before_send'], $params['links_expire_auto_refresh_enabled'] ) ) {

            // Load logs
            $logs   = Disciple_Tools_Bulk_Magic_Link_Sender_API::logging_load();
            $logs[] = Disciple_Tools_Bulk_Magic_Link_Sender_API::logging_create( '[SEND NOW REQUEST]' );

            // Attempt to load link object based on submitted id
            $link_obj = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_obj( $params['link_obj_id'] );
            if ( ! empty( $link_obj ) ) {

                $logs[] = Disciple_Tools_Bulk_Magic_Link_Sender_API::logging_create( 'Processing Link Object: ' . $link_obj->name );

                /**
                 * Update link object with most recent key settings!
                 */

                if ( empty( $link_obj->link_manage ) ) {
                    $link_obj->link_manage = (object) [];
                }
                $link_obj->link_manage->links_expire_within_amount        = $params['links_expire_within_amount'];
                $link_obj->link_manage->links_expire_within_time_unit     = $params['links_expire_within_time_unit'];
                $link_obj->link_manage->links_never_expires               = in_array( strtolower( $params['links_never_expires'] ), [ 'true' ] );
                $link_obj->schedule->links_refreshed_before_send       = in_array( strtolower( $params['links_refreshed_before_send'] ), [ 'true' ] );
                $link_obj->link_manage->links_expire_auto_refresh_enabled = in_array( strtolower( $params['links_expire_auto_refresh_enabled'] ), [ 'true' ] );

                /**
                 * If present, capture latest message text.
                 */

                if ( ! empty( $params['message_subject'] ) ) {
                    $link_obj->message_subject = $params['message_subject'];
                }

                if ( ! empty( $params['message'] ) ) {
                    $link_obj->message = $params['message'];
                }

                /**
                 * Loop over assigned users and members; which have been submitted and not loaded;
                 * as submitted params provide the most recent snapshot of link object shapes!
                 */

                $updated_assigned = [];
                foreach ( $params['assigned'] ?? [] as $assigned ) {

                    $assigned = (object) $assigned;
                    if ( in_array( strtolower( trim( $assigned->type ) ), Disciple_Tools_Bulk_Magic_Link_Sender_API::$assigned_supported_types ) ) {

                        // Process send request to assigned user, using available contact info
                        $send_response = Disciple_Tools_Bulk_Magic_Link_Sender_API::send( $link_obj, $assigned, $logs );

                        // Capture any updates
                        $link_obj           = $send_response['link_obj'];
                        $updated_assigned[] = $send_response['user'];

                    } else {
                        $updated_assigned[] = $assigned;
                    }
                }

                // Capture any potentially changed assignments and save updated link object
                $link_obj->assigned = $updated_assigned;
                Disciple_Tools_Bulk_Magic_Link_Sender_API::update_option_link_obj( $link_obj );

                // Return successful response and updated assigned list
                $response['success']  = true;
                $response['message']  = 'Send request completed - See logging tab for further details.';
                $response['assigned'] = $updated_assigned;

            } else {
                $msg    = 'Unable to locate corresponding link object for id: ' . $params['link_obj_id'];
                $logs[] = Disciple_Tools_Bulk_Magic_Link_Sender_API::logging_create( $msg );

                $response['success'] = false;
                $response['message'] = $msg;
            }

            // Update logging information
            Disciple_Tools_Bulk_Magic_Link_Sender_API::logging_update( $logs );

        } else {
            $response['success'] = false;
            $response['message'] = 'Unable to send any messages, due to unrecognizable parameters.';
        }

        return $response;
    }

    public function next_scheduled_run( WP_REST_Request $request ): array{

        // Prepare response payload
        $response = [];
        $response['success'] = false;

        $params = $request->get_params();
        if ( !isset( $params['link_obj_id'] ) ){
            $response['message'] = 'Unable to detect required link object id!';

        } else {
            $response['message'] = '';
            $response['next_run_ts'] = 0;
            $response['next_run_label'] = '---';
            $response['next_run_relative'] = '---';

            $link_obj = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_obj( $params['link_obj_id'] );
            if ( !empty( $link_obj ) && $link_obj->enabled ){
                if ( ( isset( $link_obj->schedule->enabled ) && $link_obj->schedule->enabled ) && !empty( $link_obj->schedule->freq_amount ) && !empty( $link_obj->schedule->freq_time_unit ) && !empty( $link_obj->schedule->last_schedule_run ) ){
                    $next_run = strtotime( '+' . $link_obj->schedule->freq_amount . ' ' . $link_obj->schedule->freq_time_unit, $link_obj->schedule->last_schedule_run );
                    $next_scheduled_run = Disciple_Tools_Bulk_Magic_Link_Sender_API::format_timestamp_in_local_time_zone( $next_run );
                    $next_scheduled_run_relative = Disciple_Tools_Bulk_Magic_Link_Sender_API::determine_relative_date( $next_run );

                    $response['success'] = true;
                    $response['next_run_ts'] = ( $next_run > time() ) ? $next_run : 0;
                    $response['next_run_label'] = ( $next_run > time() ) ? $next_scheduled_run : '---';
                    $response['next_run_relative'] = ( $next_run > time() ) ? $next_scheduled_run_relative : '---';
                }
            }
        }

        return $response;
    }

    public function get_report( WP_REST_Request $request ): array {

        // Prepare response payload
        $response = [];

        if ( ! isset( $request->get_params()['id'] ) ) {
            $response['success'] = false;
            $response['message'] = 'Unable to detect required report id!';
            $response['report']  = null;

        } else {
            $id      = $request->get_params()['id'];
            $report  = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_report( $id );
            $success = ! empty( $report );

            $response['success'] = $success;
            $response['message'] = $success ? 'Loaded data for report id: ' . $id : 'Unable to load data for report id: ' . $id;
            $response['report']  = $success ? $report : null;
        }

        return $response;
    }

    public function references( WP_REST_Request $request ): array{

        // Prepare response payload
        $response = [];

        $params = $request->get_params();
        if ( isset( $params['action'] ) ){

            // Execute accordingly, based on specified action
            switch ( $params['action'] ){
                case 'refresh':
                    $response['success'] = true;
                    $response['message'] = 'References action[' . $params['action'] . '] successfully executed.';
                    break;
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Unable to execute action, due to missing parameters.';
        }

        return $response;
    }

    public function typeahead_users_teams_groups( WP_REST_Request $request ): array {
        $query = $request->get_params()['s'] ?? null;

        $dt_users = [];
        $dt_teams = [];
        $dt_groups = [];

        if ( ! empty( $query ) ) {
            $dt_users = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_dt_users( true, [
                'type' => 'name',
                'query' => $query
            ] );
            $dt_teams = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_dt_teams( true, [
                'type' => 'name',
                'query' => $query
            ] );
            $dt_groups = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_dt_groups( true, [
                'type' => 'name',
                'query' => $query
            ] );
        }

        return [
            'dt_users' => $dt_users,
            'dt_teams' => $dt_teams,
            'dt_groups' => $dt_groups
        ];
    }

    public function set_link_expiration_direct( WP_REST_Request $request ): array {
        $response = [];
        $params = $request->get_params();

        // Validate required parameters
        if ( ! isset( $params['meta_key'], $params['sys_type'] ) ) {
            $response['success'] = false;
            $response['message'] = 'Missing required parameters: meta_key and sys_type.';
            return $response;
        }

        $meta_key = $params['meta_key'];
        $sys_type = $params['sys_type'];
        $id = null;

        // Determine ID based on sys_type
        if ( $sys_type === 'wp_user' ) {
            if ( ! isset( $params['user_id'] ) ) {
                $response['success'] = false;
                $response['message'] = 'Missing required parameter: user_id.';
                return $response;
            }
            $id = $params['user_id'];
        } elseif ( $sys_type === 'post' ) {
            if ( ! isset( $params['post_id'] ) ) {
                $response['success'] = false;
                $response['message'] = 'Missing required parameter: post_id.';
                return $response;
            }
            $id = $params['post_id'];
        } else {
            $response['success'] = false;
            $response['message'] = 'Invalid sys_type. Must be "post" or "wp_user".';
            return $response;
        }

        // Get expiration parameters (frontend no longer sends "never expires"; require amount + unit)
        $links_expire_within_amount = $params['links_expire_within_amount'] ?? '';
        $links_expire_within_time_unit = $params['links_expire_within_time_unit'] ?? '';
        $links_never_expires = false;

        if ( empty( $links_expire_within_amount ) || empty( $links_expire_within_time_unit ) || (int) $links_expire_within_amount < 1 ) {
            $response['success'] = false;
            $response['message'] = 'Please enter a valid expiration amount and time unit.';
            return $response;
        }

        // Calculate expiration values
        $base_ts = time();
        $expiration_data = [
            'links_expire_within_base_ts' => $base_ts,
            'links_expire_within_amount' => $links_expire_within_amount,
            'links_expire_within_time_unit' => $links_expire_within_time_unit,
            'links_never_expires' => $links_never_expires
        ];

        // Use refresh_links_expiration_values to calculate expiry timestamps
        $temp_user = (object) $expiration_data;
        $temp_user = Disciple_Tools_Bulk_Magic_Link_Sender_API::refresh_links_expiration_values(
            $temp_user,
            $base_ts,
            $links_expire_within_amount,
            $links_expire_within_time_unit,
            $links_never_expires
        );

        // Prepare expiration data for storage
        $expiration_data['links_expire_within_base_ts'] = $temp_user->links_expire_within_base_ts;
        $expiration_data['links_expire_on_ts'] = $temp_user->links_expire_on_ts;
        $expiration_data['links_expire_on_ts_formatted'] = $temp_user->links_expire_on_ts_formatted;

        // Update expiration in meta storage
        $success = Disciple_Tools_Bulk_Magic_Link_Sender_API::update_link_expiration_in_meta(
            $meta_key,
            $id,
            $sys_type,
            $expiration_data
        );

        if ( $success ) {
            // Also update link_obj if it exists (for consistency)
            $link_objs = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_objs();
            foreach ( $link_objs as $link_obj ) {
                $generated_key = Disciple_Tools_Bulk_Magic_Link_Sender_API::generate_magic_link_type_key( $link_obj );
                if ( $generated_key === $meta_key ) {
                    // Find matching assigned record and update expiration
                    $updated = false;
                    foreach ( $link_obj->assigned ?? [] as &$assigned_record ) {
                        if ( isset( $assigned_record->dt_id ) && $assigned_record->dt_id == $id ) {
                            $assigned_record->links_expire_within_base_ts = $expiration_data['links_expire_within_base_ts'];
                            $assigned_record->links_expire_on_ts = $expiration_data['links_expire_on_ts'];
                            $assigned_record->links_expire_on_ts_formatted = $expiration_data['links_expire_on_ts_formatted'];
                            $updated = true;
                            break;
                        }
                    }
                    if ( $updated ) {
                        Disciple_Tools_Bulk_Magic_Link_Sender_API::update_option_link_obj( $link_obj );
                    }
                    break;
                }
            }

            $response['success'] = true;
            $response['message'] = 'Expiration updated successfully.';
            $ts = $expiration_data['links_expire_on_ts'] ?? '';
            $response['expires'] = [
                'ts' => $ts,
                'ts_formatted' => $expiration_data['links_expire_on_ts_formatted'],
                'ts_formatted_short' => ! empty( $ts ) ? date_i18n( 'n/j/y G:i', (int) $ts ) : '',
                'ts_base' => $expiration_data['links_expire_within_base_ts']
            ];
        } else {
            $response['success'] = false;
            $response['message'] = 'Failed to update expiration.';
        }

        return $response;
    }

    public function sync_expiration_hash( WP_REST_Request $request ): array {
        $response = [];
        $params = $request->get_params();

        // Validate required parameters
        if ( ! isset( $params['meta_key'], $params['sys_type'], $params['old_hash'], $params['new_hash'] ) ) {
            $response['success'] = false;
            $response['message'] = 'Missing required parameters: meta_key, sys_type, old_hash, new_hash.';
            return $response;
        }

        $meta_key = $params['meta_key'];
        $sys_type = $params['sys_type'];
        $old_hash = $params['old_hash'];
        $new_hash = $params['new_hash'];
        $id = null;

        // Determine ID based on sys_type
        if ( $sys_type === 'wp_user' ) {
            $id = $params['user_id'] ?? null;
        } elseif ( $sys_type === 'post' ) {
            $id = $params['post_id'] ?? null;
        }

        if ( empty( $id ) ) {
            $response['success'] = false;
            $response['message'] = 'Missing required parameter: ' . ( $sys_type === 'wp_user' ? 'user_id' : 'post_id' ) . '.';
            return $response;
        }

        // Sync expiration hash
        $success = Disciple_Tools_Bulk_Magic_Link_Sender_API::sync_expiration_hash(
            $meta_key,
            $id,
            $sys_type,
            $old_hash,
            $new_hash
        );

        $response['success'] = $success;
        $response['message'] = $success ? 'Expiration hash synced successfully.' : 'Failed to sync expiration hash.';

        return $response;
    }

    public function clear_link_expiration( WP_REST_Request $request ): array {
        $response = [];
        $params = $request->get_params();

        // Validate required parameters
        if ( ! isset( $params['meta_key'], $params['sys_type'] ) ) {
            $response['success'] = false;
            $response['message'] = 'Missing required parameters: meta_key, sys_type.';
            return $response;
        }

        $meta_key = $params['meta_key'];
        $sys_type = $params['sys_type'];
        $id = null;

        // Determine ID based on sys_type
        if ( $sys_type === 'wp_user' ) {
            $id = $params['user_id'] ?? null;
        } elseif ( $sys_type === 'post' ) {
            $id = $params['post_id'] ?? null;
        }

        if ( empty( $id ) ) {
            $response['success'] = false;
            $response['message'] = 'Missing required parameter: ' . ( $sys_type === 'wp_user' ? 'user_id' : 'post_id' ) . '.';
            return $response;
        }

        // Clear expiration data
        $success = Disciple_Tools_Bulk_Magic_Link_Sender_API::delete_link_expiration_from_meta(
            $meta_key,
            $id,
            $sys_type
        );

        $response['success'] = $success;
        $response['message'] = $success ? 'Expiration cleared successfully.' : 'Failed to clear expiration.';

        return $response;
    }

    /**
     * Helper function to sync expiration from link_obj to post_meta/user_option
     *
     * @param object $link_obj Link object
     * @param object $member Assigned member record
     * @return void
     */
    private function sync_expiration_to_meta( $link_obj, $member ) {
        if ( empty( $link_obj ) || empty( $member ) || ! isset( $member->dt_id, $member->sys_type ) ) {
            return;
        }

        // Get meta_key for this link_obj
        $meta_key = Disciple_Tools_Bulk_Magic_Link_Sender_API::generate_magic_link_type_key( $link_obj );
        if ( empty( $meta_key ) ) {
            return;
        }

        // Get expiration settings from member if available, otherwise from link_obj
        $links_expire_within_amount = $member->links_expire_within_amount ?? ( $link_obj->link_manage->links_expire_within_amount ?? '' );
        $links_expire_within_time_unit = $member->links_expire_within_time_unit ?? ( $link_obj->link_manage->links_expire_within_time_unit ?? '' );
        $links_never_expires = isset( $member->links_never_expires ) ? $member->links_never_expires : ( $link_obj->link_manage->links_never_expires ?? false );

        // Prepare expiration data
        $expiration_data = [
            'links_expire_within_base_ts' => $member->links_expire_within_base_ts ?? '',
            'links_expire_on_ts' => $member->links_expire_on_ts ?? '',
            'links_expire_on_ts_formatted' => $member->links_expire_on_ts_formatted ?? '---',
            'links_expire_within_amount' => $links_expire_within_amount,
            'links_expire_within_time_unit' => $links_expire_within_time_unit,
            'links_never_expires' => $links_never_expires
        ];

        // Sync to meta storage
        Disciple_Tools_Bulk_Magic_Link_Sender_API::update_link_expiration_in_meta(
            $meta_key,
            $member->dt_id,
            $member->sys_type,
            $expiration_data
        );
    }

    private static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // End instance()

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
    }

    public function has_permission() {
        $pass = false;
        foreach ( $this->permissions as $permission ) {
            if ( current_user_can( $permission ) ) {
                $pass = true;
            }
        }

        return $pass;
    }
}

Disciple_Tools_Bulk_Magic_Link_Sender_Endpoints::instance();
