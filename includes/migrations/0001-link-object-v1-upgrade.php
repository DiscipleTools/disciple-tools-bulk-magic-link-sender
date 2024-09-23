<?php
declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

require_once( 'abstract.php' );

/**
 * Class Disciple_Tools_Bulk_Magic_Link_Migration_0001
 */
class Disciple_Tools_Bulk_Magic_Link_Migration_0001 extends Disciple_Tools_Bulk_Magic_Link_Migration {

    public static $migration_schema_latest_version_id = 1;
    public static $migration_schema_type_map = 'map';
    public static $migration_schema_type_new = 'new';
    public static $migration_schema_type_new_array_element = 'new-array-element';

    /**
     * @throws \Exception  Got error when creating table $name.
     */
    public function up() {

        // Fetch migration mapping schema
        $migration_schema = self::build_migration_schema();

        // Scan existing link objects for migration candidates.
        foreach ( Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_objs() ?? [] as $link_obj ) {
            if ( ! empty( $link_obj ) ) {

                // Cast into a friendlier associative array.
                $link_obj = json_decode( json_encode( $link_obj ), true );

                // Determine link obj version.
                $link_obj_version = $link_obj['version'] ?? 0;

                // Is link object storage shape @ the latest version?
                if ( $link_obj_version != self::$migration_schema_latest_version_id ) {

                    // Proceed if schema enabled to do so.
                    if ( $migration_schema['enabled'] ) {

                        // Prepare new link object skeleton.
                        $new_link_obj = [];

                        // First, process all direct map based schema migration rules.
                        foreach ( $migration_schema['schema'] ?? [] as $rule ) {
                            if ( $rule['type'] == self::$migration_schema_type_map ) {

                                // Generate directional from/to indices.
                                $from_indices = explode( '.', $rule['from'] );
                                $to_indices   = explode( '.', $rule['to'] );

                                // Fetch from value, to be migrated.
                                $value = self::from_value( $link_obj, $from_indices );

                                // Migrate value to new link object.
                                self::to_value( $new_link_obj, $to_indices, $value );
                            }
                        }

                        // Next, process all standard new based schema migration rules.
                        foreach ( $migration_schema['schema'] ?? [] as $rule ) {
                            if ( $rule['type'] == self::$migration_schema_type_new ) {

                                // Generate directional to indices.
                                $to_indices = explode( '.', $rule['to'] );

                                // Create in new link object, using default.
                                self::to_value( $new_link_obj, $to_indices, $rule['default'] );
                            }
                        }

                        // Finally, process all new array element based schema migration rules.
                        foreach ( $migration_schema['schema'] ?? [] as $rule ) {
                            if ( $rule['type'] == self::$migration_schema_type_new_array_element ) {

                                // Generate directional to indices.
                                $to_indices = explode( '.', $rule['to'] );

                                // Create in new link object, using default.
                                self::to_array_value( $new_link_obj, $to_indices, $rule['default'] );
                            }
                        }

                        // Only persist changes if new migrated link object skeleton has been fleshed out.
                        if ( ! empty( $new_link_obj ) && isset( $new_link_obj['id'] ) ) {
                            Disciple_Tools_Bulk_Magic_Link_Sender_API::update_option_link_obj( json_decode( json_encode( $new_link_obj ), false ) );

                            // Log migration, from what was, to, what, now is! Just in case we need to revert!! ;)
                            dt_write_log( 'ML LINK OBJECT MIGRATION [FROM]' );
                            dt_write_log( $link_obj );

                            dt_write_log( 'ML LINK OBJECT MIGRATION [TO]' );
                            dt_write_log( json_decode( json_encode( $new_link_obj ), false ) );
                        }
                    }
                }
            }
        }
    }

    /**
     * @throws \Exception  Got error when dropping table $name.
     */
    public function down() {
    }

    /**
     * Test function
     */
    public function test() {
    }

    private function build_migration_schema(): array {
        return [
            'enabled' => true,
            'schema'  => [
                [
                    'type'    => self::$migration_schema_type_map,
                    'from'    => 'id',
                    'to'      => 'id',
                    'default' => ''
                ],
                [
                    'type'    => self::$migration_schema_type_map,
                    'from'    => 'enabled',
                    'to'      => 'enabled',
                    'default' => ''
                ],
                [
                    'type'    => self::$migration_schema_type_map,
                    'from'    => 'name',
                    'to'      => 'name',
                    'default' => ''
                ],
                [
                    'type'    => self::$migration_schema_type_map,
                    'from'    => 'expires',
                    'to'      => 'expires',
                    'default' => ''
                ],
                [
                    'type'    => self::$migration_schema_type_map,
                    'from'    => 'never_expires',
                    'to'      => 'never_expires',
                    'default' => ''
                ],
                [
                    'type'    => self::$migration_schema_type_map,
                    'from'    => 'type',
                    'to'      => 'type',
                    'default' => ''
                ],
                [
                    'type'    => self::$migration_schema_type_map,
                    'from'    => 'type_fields',
                    'to'      => 'type_fields',
                    'default' => ''
                ],
                [
                    'type'    => self::$migration_schema_type_new,
                    'from'    => '',
                    'to'      => 'type_config',
                    'default' => []
                ],
                [
                    'type'    => self::$migration_schema_type_map,
                    'from'    => 'assigned',
                    'to'      => 'assigned',
                    'default' => ''
                ],
                [
                    'type'    => self::$migration_schema_type_new_array_element,
                    'from'    => '',
                    'to'      => 'assigned.[].links_expire_within_base_ts',
                    'default' => ''
                ],
                [
                    'type'    => self::$migration_schema_type_new_array_element,
                    'from'    => '',
                    'to'      => 'assigned.[].links_expire_on_ts',
                    'default' => ''
                ],
                [
                    'type'    => self::$migration_schema_type_new_array_element,
                    'from'    => '',
                    'to'      => 'assigned.[].links_expire_on_ts_formatted',
                    'default' => ''
                ],
                [
                    'type'    => self::$migration_schema_type_map,
                    'from'    => 'schedule.links_expire_within_amount',
                    'to'      => 'link_manage.links_expire_within_amount',
                    'default' => ''
                ],
                [
                    'type'    => self::$migration_schema_type_map,
                    'from'    => 'schedule.links_expire_within_time_unit',
                    'to'      => 'link_manage.links_expire_within_time_unit',
                    'default' => ''
                ],
                [
                    'type'    => self::$migration_schema_type_map,
                    'from'    => 'schedule.links_never_expires',
                    'to'      => 'link_manage.links_never_expires',
                    'default' => ''
                ],
                [
                    'type'    => self::$migration_schema_type_map,
                    'from'    => 'schedule.links_expire_auto_refresh_enabled',
                    'to'      => 'link_manage.links_expire_auto_refresh_enabled',
                    'default' => ''
                ],
                [
                    'type'    => self::$migration_schema_type_map,
                    'from'    => 'message',
                    'to'      => 'message',
                    'default' => ''
                ],
                [
                    'type'    => self::$migration_schema_type_map,
                    'from'    => 'schedule.enabled',
                    'to'      => 'schedule.enabled',
                    'default' => ''
                ],
                [
                    'type'    => self::$migration_schema_type_map,
                    'from'    => 'schedule.freq_amount',
                    'to'      => 'schedule.freq_amount',
                    'default' => ''
                ],
                [
                    'type'    => self::$migration_schema_type_map,
                    'from'    => 'schedule.freq_time_unit',
                    'to'      => 'schedule.freq_time_unit',
                    'default' => ''
                ],
                [
                    'type'    => self::$migration_schema_type_map,
                    'from'    => 'schedule.sending_channel',
                    'to'      => 'schedule.sending_channel',
                    'default' => ''
                ],
                [
                    'type'    => self::$migration_schema_type_map,
                    'from'    => 'schedule.last_schedule_run',
                    'to'      => 'schedule.last_schedule_run',
                    'default' => ''
                ],
                [
                    'type'    => self::$migration_schema_type_map,
                    'from'    => 'schedule.last_success_send',
                    'to'      => 'schedule.last_success_send',
                    'default' => ''
                ],
                [
                    'type'    => self::$migration_schema_type_new,
                    'from'    => '',
                    'to'      => 'schedule.links_refreshed_before_send',
                    'default' => true
                ],
                [
                    'type'    => self::$migration_schema_type_new,
                    'from'    => '',
                    'to'      => 'version',
                    'default' => 1
                ]
            ]
        ];
    }

    private function from_value( $from_array, $from_indices ) {

        // Ensure there is data to be processed.
        if ( empty( $from_array ) || empty( $from_indices ) ) {
            return '';
        }

        // Shift first index element.
        $index = array_shift( $from_indices );
        if ( ! array_key_exists( $index, $from_array ) ) {
            return '';
        }

        // Has a valid value been found?
        $value = $from_array[ $index ];
        if ( empty( $from_indices ) ) {
            return $value;
        }

        // Terminate if the end of the road has been reached.
        if ( ! is_array( $value ) ) {
            return '';
        }

        // Recurse, if further searching is required.
        return self::from_value( $value, $from_indices );
    }

    private function to_value( &$to_array, $to_indices, $value ) {

        // Ensure there is data to be processed.
        if ( empty( $to_indices ) ) {
            return null;
        }

        // Shift first index element.
        $index = array_shift( $to_indices );

        // Has the end of the index road been reached?
        if ( empty( $to_indices ) ) {
            $to_array[ $index ] = $value;

        } else {

            // Create element if needed.
            if ( ! array_key_exists( $index, $to_array ) ) {
                $to_array[ $index ] = [];
            }

            // Recurse, if further searching is required.
            self::to_value( $to_array[ $index ], $to_indices, $value );
        }
    }

    private function to_array_value( &$to_array, $to_indices, $value ) {

        // Ensure there is data to be processed.
        if ( empty( $to_indices ) ) {
            return null;
        }

        // Shift first index element.
        $index = array_shift( $to_indices );

        // Has target array been encountered?
        if ( $index == '[]' && is_array( $to_array ) ) {

            // Extract new element index and populate.
            $element_index = array_shift( $to_indices );
            foreach ( $to_array as &$element ) {
                $element                   = json_decode( json_encode( $element ), true );
                $element[ $element_index ] = $value;
            }
        } else {

            // Create element if needed.
            if ( ( $index != '[]' ) && ! array_key_exists( $index, $to_array ) ) {
                $to_array[ $index ] = [];
            }

            // Recurse, if further searching is required.
            self::to_array_value( $to_array[ $index ], $to_indices, $value );
        }
    }
}
