<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Register cron module.
 */

if ( ! wp_next_scheduled( 'dt_magic_links_cron' ) ) {
    wp_schedule_event( time(), '15min', 'dt_magic_links_cron' );
}

/**
 * Core Cron Logic.
 */

add_action( 'dt_magic_links_cron', 'dt_magic_links_cron_run' );
function dt_magic_links_cron_run() {

    // Process link objects currently scheduled for execution
    execute_scheduled_link_objects();

    // Age off any stale logs
    Disciple_Tools_Bulk_Magic_Link_Sender_API::logging_aged();
}

function execute_scheduled_link_objects() {

    // Determine if global scheduling has been enabled
    if ( boolval( Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_all_scheduling_enabled ) ) ) {

        // Load logs
        $logs = Disciple_Tools_Bulk_Magic_Link_Sender_API::logging_load();

        // Load all available link objects and process those enabled
        $link_objs = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_objs();
        foreach ( $link_objs ?? (object) [] as $id => $link_obj ) {

            // If enabled, proceed with processing
            if ( isset( $link_obj->enabled ) && $link_obj->enabled ) {

                $logs[] = Disciple_Tools_Bulk_Magic_Link_Sender_API::logging_create( 'Processing Link Object: ' . $link_obj->name );
                if ( isset( $link_obj->schedule->enabled ) && $link_obj->schedule->enabled ) {

                    // Is link object able to run, based on last schedule run?
                    if ( Disciple_Tools_Bulk_Magic_Link_Sender_API::is_scheduled_to_run( $link_obj ) ) {

                        // Ensure link object has not yet expired
                        if ( ! Disciple_Tools_Bulk_Magic_Link_Sender_API::has_obj_expired( $link_obj->never_expires, $link_obj->expires ) ) {

                            // Determine if usage of global sending channels is enabled
                            if ( boolval( Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_all_channels_enabled ) ) ) {

                                // Send messages to assigned users and members
                                execute_ml_send( $link_obj, $logs );

                            } else {
                                $logs[] = Disciple_Tools_Bulk_Magic_Link_Sender_API::logging_create( 'Global sending channels disabled; no further action to be taken!' );
                            }
                        } else {
                            $logs[] = Disciple_Tools_Bulk_Magic_Link_Sender_API::logging_create( 'Link object has now expired, terminating all assigned user magic links!' );

                            // Nuke all assigned user links!
                            Disciple_Tools_Bulk_Magic_Link_Sender_API::update_magic_links( $link_obj, $link_obj->assigned, true );

                            // Iterate over all assigned and reset their respective expiration timestamps
                            foreach ( $link_obj->assigned ?? [] as &$member ) {
                                $member->links_expire_within_base_ts  = '';
                                $member->links_expire_on_ts           = '';
                                $member->links_expire_on_ts_formatted = '';
                            }

                            // Save recent resets!
                            Disciple_Tools_Bulk_Magic_Link_Sender_API::update_option_link_obj( $link_obj );

                        }

                        // Update last schedule run timestamp
                        Disciple_Tools_Bulk_Magic_Link_Sender_API::update_schedule_settings( $id, Disciple_Tools_Bulk_Magic_Link_Sender_API::$schedule_last_schedule_run, time() );
                        $logs[] = Disciple_Tools_Bulk_Magic_Link_Sender_API::logging_create( 'Last schedule run timestamp updated.' );

                    } else {
                        $logs[] = Disciple_Tools_Bulk_Magic_Link_Sender_API::logging_create( 'Currently not scheduled to run!' );
                    }
                } else {
                    $logs[] = Disciple_Tools_Bulk_Magic_Link_Sender_API::logging_create( 'Scheduling disabled!' );
                }
            }
        }

        // Update logging information
        Disciple_Tools_Bulk_Magic_Link_Sender_API::logging_update( $logs );
    }

    // Update last cron run timestamp
    Disciple_Tools_Bulk_Magic_Link_Sender_API::update_option( Disciple_Tools_Bulk_Magic_Link_Sender_API::$option_dt_magic_links_last_cron_run, time() );
}

function execute_ml_send( $link_obj, &$logs ) {

    $updated_link_obj = null;
    $updated_assigned = [];
    foreach ( $link_obj->assigned ?? [] as $assigned ) {
        if ( in_array( strtolower( trim( $assigned->type ) ), Disciple_Tools_Bulk_Magic_Link_Sender_API::$assigned_supported_types ) ) {

            // Process send request to assigned user, using available contact info
            $send_response = Disciple_Tools_Bulk_Magic_Link_Sender_API::send( $link_obj, $assigned, $logs );

            // Capture any updates
            $updated_link_obj   = $send_response['link_obj'];
            $updated_assigned[] = $send_response['user'];

        } else {
            $updated_assigned[] = $assigned;
        }
    }

    // Capture any potentially changed assignments and save updated link object
    if ( ! empty( $updated_link_obj ) ) {
        $updated_link_obj->assigned = $updated_assigned;
        Disciple_Tools_Bulk_Magic_Link_Sender_API::update_option_link_obj( $updated_link_obj );
    }

}
