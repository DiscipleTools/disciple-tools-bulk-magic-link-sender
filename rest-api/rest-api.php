<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.

class Disciple_Tools_Magic_Links_Endpoints {
    /**
     * @todo Set the permissions your endpoint needs
     * @link https://github.com/DiscipleTools/Documentation/blob/master/theme-core/capabilities.md
     * @var string[]
     */
    public $permissions = [ 'access_contacts', 'dt_all_access_contacts', 'view_project_metrics' ];


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
            $namespace, '/user_links_manage', [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'user_links_manage' ],
                'permission_callback' => '__return_true',
            ]
        );
        register_rest_route(
            $namespace, '/send_now', [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'send_now' ],
                'permission_callback' => '__return_true',
            ]
        );
        register_rest_route(
            $namespace, '/report', [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_report' ],
                'permission_callback' => '__return_true',
            ]
        );
    }

    public function user_links_manage( WP_REST_Request $request ): array {

        // Prepare response payload
        $response = [];

        $params = $request->get_params();
        if ( isset( $params['action'], $params['assigned'], $params['link_obj_id'], $params['magic_link_type'] ) ) {

            // Adjust assigned array shape, to ensure it is processed accordingly further downstream
            $assigned = json_decode( json_encode( $params['assigned'] ) );

            // Execute accordingly, based on specified action
            switch ( $params['action'] ) {
                case 'refresh':
                    Disciple_Tools_Magic_Links_API::update_user_app_magic_links( $params['magic_link_type'], $assigned, false );

                    // Also update base timestamp and future expiration points
                    if ( isset( $params['links_expire_within_amount'], $params['links_expire_within_time_unit'], $params['links_never_expires'] ) ) {

                        $base_ts       = time();
                        $amt           = $params['links_expire_within_amount'];
                        $time_unit     = $params['links_expire_within_time_unit'];
                        $never_expires = $params['links_never_expires'];

                        $response['links_expire_within_base_ts']  = $base_ts;
                        $response['links_expire_on_ts']           = Disciple_Tools_Magic_Links_API::determine_links_expiry_point( $amt, $time_unit, $base_ts );
                        $response['links_expire_on_ts_formatted'] = Disciple_Tools_Magic_Links_API::fetch_links_expired_formatted_date( $never_expires, $base_ts, $amt, $time_unit );
                    }
                    break;

                case 'delete':
                    Disciple_Tools_Magic_Links_API::update_user_app_magic_links( $params['magic_link_type'], $assigned, true );
                    break;
            }

            // Ensure current user has sufficient capabilities/roles for the tasks ahead!
            $current_user = wp_get_current_user();
            if ( ! empty( $current_user ) && ! is_wp_error( $current_user ) && ! current_user_can( "access_contacts" ) ) {
                $current_user->add_role( 'access_contacts' );
            }

            // Return original assigned array + updated users & teams
            $response['success']  = true;
            $response['message']  = 'User links management action[' . $params['action'] . '] successfully executed.';
            $response['assigned'] = $assigned;
            $response['dt_users'] = Disciple_Tools_Magic_Links_API::fetch_dt_users();
            $response['dt_teams'] = Disciple_Tools_Magic_Links_API::fetch_dt_teams();

        } else {
            $response['success'] = false;
            $response['message'] = 'Unable to execute action, due to missing parameters.';
        }

        return $response;
    }

    public
    function send_now(
        WP_REST_Request $request
    ): array {

        // Prepare response payload
        $response = [];

        // Ensure link object id has been specified
        $link_obj_id = $request->get_param( 'link_obj_id' );
        if ( ! empty( $link_obj_id ) ) {

            // Load logs
            $logs   = Disciple_Tools_Magic_Links_API::logging_load();
            $logs[] = Disciple_Tools_Magic_Links_API::logging_create( '[SEND NOW REQUEST]' );

            // Attempt to load link object based on submitted id
            $link_obj = Disciple_Tools_Magic_Links_API::fetch_option_link_obj( $link_obj_id );
            if ( ! empty( $link_obj ) ) {

                $logs[] = Disciple_Tools_Magic_Links_API::logging_create( 'Processing Link Object: ' . $link_obj->name );

                // Loop over assigned users and members
                foreach ( $link_obj->assigned ?? [] as $assigned ) {

                    if ( in_array( strtolower( trim( $assigned->type ) ), [ 'user', 'member' ] ) ) {

                        // Process send request to assigned user, using available contact info
                        Disciple_Tools_Magic_Links_API::send( $link_obj, $assigned, $logs );
                    }
                }

                $response['success'] = true;
                $response['message'] = 'Send request completed - See logging tab for further details.';

            } else {
                $msg    = 'Unable to locate corresponding link object for id: ' . $link_obj_id;
                $logs[] = Disciple_Tools_Magic_Links_API::logging_create( $msg );

                $response['success'] = false;
                $response['message'] = $msg;
            }

            // Update logging information
            Disciple_Tools_Magic_Links_API::logging_update( $logs );

        } else {
            $response['success'] = false;
            $response['message'] = 'Unable to send any messages, due to unrecognizable link object id: ' . $link_obj_id;
        }

        return $response;
    }

    public
    function get_report(
        WP_REST_Request $request
    ): array {

        // Prepare response payload
        $response = [];

        if ( ! isset( $request->get_params()['id'] ) ) {
            $response['success'] = false;
            $response['message'] = 'Unable to detect required report id!';
            $response['report']  = null;

        } else {
            $id      = $request->get_params()['id'];
            $report  = Disciple_Tools_Magic_Links_API::fetch_report( $id );
            $success = ! empty( $report );

            $response['success'] = $success;
            $response['message'] = $success ? 'Loaded data for report id: ' . $id : 'Unable to load data for report id: ' . $id;
            $response['report']  = $success ? $report : null;
        }

        return $response;
    }

    private
    static $_instance = null;

    public
    static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // End instance()

    public
    function __construct() {
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
    }

    public
    function has_permission() {
        $pass = false;
        foreach ( $this->permissions as $permission ) {
            if ( current_user_can( $permission ) ) {
                $pass = true;
            }
        }

        return $pass;
    }
}

Disciple_Tools_Magic_Links_Endpoints::instance();
