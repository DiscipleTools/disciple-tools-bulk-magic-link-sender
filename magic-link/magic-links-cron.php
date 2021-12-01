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
    Disciple_Tools_Magic_Links_API::logging_aged();
}

function execute_scheduled_link_objects() {

    // Determine if global scheduling has been enabled
    if ( boolval( Disciple_Tools_Magic_Links_API::fetch_option( Disciple_Tools_Magic_Links_API::$option_dt_magic_links_all_scheduling_enabled ) ) ) {

        // Load logs
        $logs = Disciple_Tools_Magic_Links_API::logging_load();

        // Load all available link objects and process those enabled
        $link_objs = Disciple_Tools_Magic_Links_API::fetch_option_link_objs();
        foreach ( $link_objs ?? (object) [] as $id => $link_obj ) {

            // Irrespective of link object state, always ensure to remove expired links!
            if ( Disciple_Tools_Magic_Links_API::has_links_expired( $link_obj->schedule->links_never_expires, $link_obj->schedule->links_expire_within_base_ts, $link_obj->schedule->links_expire_within_amount, $link_obj->schedule->links_expire_within_time_unit ) ) {
                // Nuke all assigned user links!
                Disciple_Tools_Magic_Links_API::update_magic_links( $link_obj->type, $link_obj->assigned, true );
            }

            // If enabled, proceed with processing
            if ( isset( $link_obj->enabled ) && $link_obj->enabled ) {

                $logs[] = Disciple_Tools_Magic_Links_API::logging_create( 'Processing Link Object: ' . $link_obj->name );
                if ( isset( $link_obj->schedule->enabled ) && $link_obj->schedule->enabled ) {

                    // Is link object able to run, based on last schedule run?
                    if ( Disciple_Tools_Magic_Links_API::is_scheduled_to_run( $link_obj ) ) {

                        // Ensure link object has not yet expired
                        if ( ! Disciple_Tools_Magic_Links_API::has_obj_expired( $link_obj->never_expires, $link_obj->expires ) ) {

                            // Ensure assigned user magic links have not yet expired
                            if ( ! Disciple_Tools_Magic_Links_API::has_links_expired( $link_obj->schedule->links_never_expires, $link_obj->schedule->links_expire_within_base_ts, $link_obj->schedule->links_expire_within_amount, $link_obj->schedule->links_expire_within_time_unit ) ) {

                                // Determine if usage of global sending channels is enabled
                                if ( boolval( Disciple_Tools_Magic_Links_API::fetch_option( Disciple_Tools_Magic_Links_API::$option_dt_magic_links_all_channels_enabled ) ) ) {

                                    // Loop over assigned users and members
                                    foreach ( $link_obj->assigned ?? [] as $assigned ) {

                                        if ( in_array( strtolower( trim( $assigned->type ) ), Disciple_Tools_Magic_Links_API::$assigned_supported_types ) ) {

                                            // Process send request to assigned user, using available contact info
                                            Disciple_Tools_Magic_Links_API::send( $link_obj, $assigned, $logs );
                                        }
                                    }
                                } else {
                                    $logs[] = Disciple_Tools_Magic_Links_API::logging_create( 'Global sending channels disabled; no further action to be taken!' );
                                }
                            } else {
                                $logs[] = Disciple_Tools_Magic_Links_API::logging_create( 'Assigned user magic links have now expired!' );

                                // Recreate links if auto-refresh has been enabled, else terminate everything!
                                if ( $link_obj->schedule->links_expire_auto_refresh_enabled ) {
                                    $logs[] = Disciple_Tools_Magic_Links_API::logging_create( 'Auto-refreshing all assigned user magic links.' );
                                    Disciple_Tools_Magic_Links_API::update_magic_links( $link_obj->type, $link_obj->assigned, false );

                                    // Update links expire base timestamp, in order to ensure next checks are relative to recent updates
                                    Disciple_Tools_Magic_Links_API::update_schedule_settings( $id, Disciple_Tools_Magic_Links_API::$schedule_links_expire_base_ts, time() );

                                } else {
                                    $logs[] = Disciple_Tools_Magic_Links_API::logging_create( 'Terminating all assigned user magic links!' );
                                    Disciple_Tools_Magic_Links_API::update_magic_links( $link_obj->type, $link_obj->assigned, true );
                                }
                            }
                        } else {
                            $logs[] = Disciple_Tools_Magic_Links_API::logging_create( 'Link object has now expired, terminating all assigned user magic links!' );

                            // Nuke all assigned user links!
                            Disciple_Tools_Magic_Links_API::update_magic_links( $link_obj->type, $link_obj->assigned, true );
                        }

                        // Update last schedule run timestamp
                        Disciple_Tools_Magic_Links_API::update_schedule_settings( $id, Disciple_Tools_Magic_Links_API::$schedule_last_schedule_run, time() );
                        $logs[] = Disciple_Tools_Magic_Links_API::logging_create( 'Last schedule run timestamp updated.' );

                    } else {
                        $logs[] = Disciple_Tools_Magic_Links_API::logging_create( 'Currently not scheduled to run!' );
                    }
                } else {
                    $logs[] = Disciple_Tools_Magic_Links_API::logging_create( 'Scheduling disabled!' );
                }
            }
        }

        // Update logging information
        Disciple_Tools_Magic_Links_API::logging_update( $logs );
    }

    // Update last cron run timestamp
    Disciple_Tools_Magic_Links_API::update_option( Disciple_Tools_Magic_Links_API::$option_dt_magic_links_last_cron_run, time() );
}
