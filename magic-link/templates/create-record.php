<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.

add_filter('dt_magic_link_template_types', function( $types ) {
    $types['contacts'][] = [
        'value' => 'create-record',
        'text' => 'Create Record',
    ];
    $types['default-options'][] = [
        'value' => 'create-record',
        'text' => 'Create Record',
    ];
    return $types;
});



add_action('dt_magic_link_template_load', function ( $template ) {
    if ( !empty( $template ) && isset( $template['type'] ) && $template['type'] === 'create-record' ) {
        new Disciple_Tools_Magic_Links_Template_Create_Record( $template );
    }
} );



/**
 * Class Disciple_Tools_Magic_Links_Template_Create_Record
 */
class Disciple_Tools_Magic_Links_Template_Create_Record extends DT_Magic_Url_Base {

    protected $template_type = 'create-record';
    public $page_title = 'Create Record';
    public $page_description = 'Create New Record';
    public $root = 'templates';
    public $type = 'template_id'; // Placeholder to be replaced with actual template ids
    public $type_name = '';
    public $post_type = 'contacts'; // Support ML contacts (which can be any one of the DT post types) by default!
    protected $post = null;
    protected $post_field_settings = null;
    protected $meta_key = '';

    public $show_app_tile = false;

    private static $_instance = null;
    public $meta = []; // Allows for instance specific data.
    public $translatable = [
        'query',
        'user',
        'contact'
    ]; // Order of translatable flags to be checked. Translate on first hit..!

    private $template = null;
    private $link_obj = null;
    private $record_type = null;

    public function __construct( $template = null )
    {
        // only handle this template type
        if ( empty( $template ) || $template['type'] !== $this->template_type ) {
            return;
        }

        $this->template         = $template;
        $this->post_type        = $template['post_type'];
        $this->record_type      = $template['record_type'] ?? $template['post_type'];

        $this->type = array_map( 'sanitize_key', wp_unslash( explode( '/', $template['url_base'] ) ) )[1];

        $this->type_name        = $template['name'];
        $this->page_title       = $template['name'];
        $this->page_description = 'Create new ' . $this->record_type;

        /**
         * Specify metadata structure, specific to the processing of current
         * magic link class type.
         *
         * - meta:              Magic link plugin related data.
         *      - app_type:     Flag indicating type to be processed by magic link plugin.
         *      - class_type:   Flag indicating template class type.
         */

        $this->meta = [
            'app_type'   => 'magic_link',
            'class_type' => 'template',
            'show_in_home_apps' => true,
            'icon' => $template['icon'] ?? 'mdi mdi-plus-circle',
        ];

        /**
         * Once adjustments have been made, proceed with parent instantiation!
         */

        $this->meta_key = $this->root . '_' . $this->type . '_magic_key';
        parent::__construct();

        // Get field settings for the record type
        $this->post_field_settings = DT_Posts::get_post_field_settings( $this->record_type, false );

        add_action( 'rest_api_init', [ $this, 'add_endpoints' ] );

        /**
         * Test magic link parts are registered and have valid elements.
         */
        if ( ! $this->check_parts_match( false ) ) {
            return;
        }

        /**
         * If this is accessed without a magic link key (i.e., without proper user binding),
         * require user login
         */
        if ( ! is_user_logged_in() ) {
            $request_uri = !empty( $_SERVER['REQUEST_URI'] ) ? sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
            wp_redirect( wp_login_url( $request_uri ) );
            exit;
        }

        /**
         * Initialize empty post for new record creation - no existing post needed
         */

        $this->post = [
            'ID' => 0,
            'post_type' => $this->record_type,
        ];

        /**
         * Attempt to load corresponding link object, if a valid incoming id has been detected.
         */

        $this->link_obj = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_obj( $this->fetch_incoming_link_param( 'id' ) );

        /**
         * Load if valid url
         */

        add_action( 'dt_blank_body', [ $this, 'body' ] );
        add_filter( 'dt_magic_url_base_allowed_css', [ $this, 'dt_magic_url_base_allowed_css' ], 10, 1 );
        add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );
        add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 200 );
        add_filter( 'dt_settings_apps_list', [ $this, 'dt_settings_apps_list' ], 10, 1 );
    }

    public function wp_enqueue_scripts() {
        // Support Geolocation APIs
        if ( DT_Mapbox_API::get_key() ) {
            DT_Mapbox_API::load_mapbox_header_scripts();
            DT_Mapbox_API::load_mapbox_search_widget();
        }

        Disciple_Tools_Bulk_Magic_Link_Sender_API::enqueue_magic_link_utilities_script();

        wp_localize_script(
            'jquery',
            'settingseouo',
            [
                'root' => esc_url_raw( rest_url() )
            ]
        );
    }

    public function dt_settings_apps_list( $apps_list ) {
        $apps_list[$this->meta_key] = [
            'key' => $this->meta_key,
            'url_base' => $this->root . '/' . $this->type,
            'label' => $this->page_title,
            'description' => $this->page_description,
            'settings_display' => true
        ];
        return $apps_list;
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        $allowed_js = [];
        $allowed_js[] = 'jquery';
        $allowed_js[] = 'mapbox-gl';
        $allowed_js[] = 'mapbox-cookie';
        $allowed_js[] = 'mapbox-search-widget';
        $allowed_js[] = 'google-search-widget';
        $allowed_js[] = 'typeahead-jquery';


        $allowed_js[] = 'web-components';
        $allowed_js[] = 'wpApiShare';

        $allowed_js[] = Disciple_Tools_Bulk_Magic_Link_Sender_API::get_magic_link_utilities_script_handle();

        return $allowed_js;
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        $allowed_css[] = 'mapbox-gl-css';
        $allowed_css[] = 'typeahead-jquery-css';
        $allowed_css[] = 'material-font-icons';
        $allowed_css[] = 'web-components-css';

        return $allowed_css;
    }

    protected function is_link_obj_field_enabled( $field_id ): bool {
        if ( ! empty( $this->link_obj ) ) {
            foreach ( $this->link_obj->type_fields ?? [] as $field ) {
                if ( $field->id === $field_id ) {
                    return $field->enabled;
                }
            }
        }

        return true;
    }

    protected function render_custom_field_for_display( $field ) {
        ?>
        <div class="section-subheader"><?php

            // Support custom field label translations; or simply default to initial label entry.
            $label = ( ! empty( $field['translations'] ) && isset( $field['translations'][ determine_locale() ] ) ) ? $field['translations'][ determine_locale() ]['translation'] : $field['label'];

            echo esc_html( $label ); ?>
        </div>
        <?php
        if ( isset( $field['custom_form_field_type'] ) && $field['custom_form_field_type'] == 'textarea' ){
            ?>
            <textarea id="<?php echo esc_html( $field['id'] ); ?>"></textarea>
            <?php
        } else {
            ?>
            <input id="<?php echo esc_html( $field['id'] ); ?>" type="text" class="text-input" value="">
            <?php
        }
    }

    protected function adjust_template_title_translation( $title, $title_translations ) {
        return ( ! empty( $title_translations ) && isset( $title_translations[ determine_locale() ] ) ) ? $title_translations[ determine_locale() ]['translation'] : $title;
    }

    /**
     * Writes custom styles to header
     */
    public function header_style() {
        ?>
        <style>
            html {
                height: 100%;
            }
            body {
                background-color: white;
                padding: 1em;
                height: 100%;
            }

            .create-form {
                max-width: 600px;
                margin: 0 auto;
                padding: 2em;
            }

            .form-field {
                margin-bottom: 1.5em;
            }

            .form-field label {
                display: block;
                margin-bottom: 0.5em;
                font-weight: bold;
            }

            .form-field input,
            .form-field textarea,
            .form-field select {
                width: 100%;
                padding: 0.5em;
                border: 1px solid #ddd;
                border-radius: 4px;
            }

            .form-field textarea {
                height: 100px;
                resize: vertical;
            }
        </style>
        <?php
    }

    /**
     * Writes javascript to the footer
     */
    public function footer_javascript() {
        ?>
        <script>
            let jsObject = [<?php echo json_encode( [
                'home_url'                => home_url(),
                'root'                    => esc_url_raw( rest_url() ),
                'nonce'                   => wp_create_nonce( 'wp_rest' ),
                'parts'                   => $this->parts,
                'template'                => $this->template,
                'post_type'               => $this->post_type,
                'record_type'             => $this->record_type,
                'field_settings'          => $this->post_field_settings,
                'translations'            => [
                    'create_success' => __( 'Thank you! Your new record has been created successfully.', 'disciple_tools' ),
                    'name_required' => __( 'Name is required', 'disciple_tools' ),
                    'error_occurred' => __( 'An error occurred while creating the record', 'disciple_tools' ),
                ],
                'mapbox'                  => [
                    'map_key'        => DT_Mapbox_API::get_key(),
                    'google_map_key' => Disciple_Tools_Google_Geocode_API::get_key(),
                    'translations'   => [
                        'search_location' => __( 'Search Location', 'disciple_tools' ),
                        'delete_location' => __( 'Delete Location', 'disciple_tools' ),
                        'use'             => __( 'Use', 'disciple_tools' ),
                        'open_modal'      => __( 'Open Modal', 'disciple_tools' )
                    ]
                ]
            ] ) ?>][0]
            // Add missing window functions that DT fields expect
            window.lodash = {
                escape: function(string) {
                    var map = {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#x27;',
                        "/": '&#x2F;',
                    };
                    var reg = /[&<>"'/]/ig;
                    return string.replace(reg, function(match){
                        return map[match];
                    });
                }
            };

            window.SHAREDFUNCTIONS = {
                formatDate: function(timestamp) {
                    return new Date(timestamp * 1000).toLocaleDateString();
                },
                formatComment: function(comment) {
                    return comment; // Simple implementation
                },
                addLink: function(event) {
                    // Handle add link functionality for link fields
                    const linkType = jQuery(event.target).data('link-type');
                    const fieldKey = jQuery(event.target).data('field-key');
                    if (!linkType || !fieldKey) {
                        return;
                    }

                    // Find the template for this link type
                    const template = jQuery(`#link-template-${fieldKey}-${linkType}`);
                    if (template.length === 0) {
                        return;
                    }

                    // Clone the template content
                    const newLinkInput = template.html();

                    // Find the target section for this link type
                    const targetSection = jQuery(`.link-section--${linkType}`);
                    if (targetSection.length === 0) {
                        return;
                    }

                    // Append the new input to the target section
                    targetSection.append(newLinkInput);

                    // Focus the new input
                    targetSection.find('input').last().focus();
                }
            };

            /**
             * Link field event handlers
             */
            // Handle add link option clicks
            jQuery(document).on('click', '.add-link__option', function(event) {
                window.SHAREDFUNCTIONS.addLink(event);
                jQuery(event.target).parent().hide();
                setTimeout(() => {
                    event.target.parentElement.removeAttribute('style');
                }, 100);
            });

            // Handle link delete button clicks
            jQuery(document).on('click', '.link-delete-button', function() {
                jQuery(this).closest('.link-section').remove();
            });

            // Handle add button clicks for link fields
            jQuery(document).on('click', 'button.add-button', function(e) {
                const field = jQuery(e.currentTarget).data('list-class');
                const fieldType = jQuery(e.currentTarget).data('field-type');

                if (fieldType === 'link') {
                    const addLinkForm = jQuery(`.add-link-${field}`);
                    addLinkForm.show();

                    jQuery(`#cancel-link-button-${field}`).on('click', () => addLinkForm.hide());
                }
            });

            /**
             * Submit new record
             */
            jQuery(document).ready(function() {
                jQuery('#content_submit_but').on("click", function () {
                    const alertNotice = jQuery('#alert_notice');
                    const spinner = jQuery('.update-loading-spinner');
                    const submitBtn = jQuery('#content_submit_but');

                    // Hide previous alerts
                    alertNotice.hide().removeClass('error');

                    // Build payload using the same approach as create-contact.php
                    let payload = {
                        'action': 'create_record',
                        'parts': jsObject.parts,
                        'post_type': jsObject.post_type,
                        'record_type': jsObject.record_type,
                        'fields': {
                            'dt': [],
                            'custom': []
                        }
                    };

                    // Iterate over form fields, capturing values from DT web components
                    jQuery('.form-field[data-template-type="dt"]').each(function (idx, fieldDiv) {
                        let field_id = jQuery(fieldDiv).data('field-id');
                        let field_type = jQuery(fieldDiv).data('field-type');
                        let field_template_type = jQuery(fieldDiv).data('template-type');

                        if (field_template_type === 'dt') {
                            // Find the DT web component in this field div
                            let dtComponent = jQuery(fieldDiv).find('[id="' + field_id + '"]');
                            if (dtComponent.length === 0) {
                                return;
                            }

                            let rawValue = dtComponent.attr('value') || dtComponent.val();

                            if (!rawValue) {
                                return;
                            }

                            switch (field_type) {
                                case 'text':
                                case 'number':
                                case 'textarea':
                                    // For dt-text, value is a simple string
                                    if (rawValue && rawValue.trim() !== '') {
                                        payload['fields']['dt'].push({
                                            id: field_id,
                                            dt_type: field_type,
                                            template_type: field_template_type,
                                            value: rawValue.trim()
                                        });
                                    }
                                    break;

                                case 'key_select':
                                    // For dt-single-select, value is the selected key
                                    if (rawValue && rawValue.trim() !== '') {
                                        payload['fields']['dt'].push({
                                            id: field_id,
                                            dt_type: field_type,
                                            template_type: field_template_type,
                                            value: rawValue.trim()
                                        });
                                    }
                                    break;

                                case 'communication_channel':
                                    // For dt-comm-channel, value is JSON array of objects
                                    try {
                                        let parsedValues = JSON.parse(rawValue);
                                        if (Array.isArray(parsedValues) && parsedValues.length > 0) {
                                            let values = [];
                                            parsedValues.forEach(function(item) {
                                                if (item.value && item.value.trim() !== '') {
                                                    values.push({
                                                        'value': item.value.trim()
                                                    });
                                                }
                                            });
                                            if (values.length > 0) {
                                                payload['fields']['dt'].push({
                                                    id: field_id,
                                                    dt_type: field_type,
                                                    template_type: field_template_type,
                                                    value: values
                                                });
                                            }
                                        }
                                    } catch (e) {
                                        // Silently handle parsing errors
                                    }
                                    break;

                                case 'multi_select':
                                    // For dt-multi-select, value is JSON array of selected keys
                                    try {
                                        let parsedValues = JSON.parse(rawValue);
                                        if (Array.isArray(parsedValues) && parsedValues.length > 0) {
                                            let options = [];
                                            parsedValues.forEach(function(selectedKey) {
                                                if (selectedKey && selectedKey.trim() !== '') {
                                                    options.push({
                                                        'value': selectedKey.trim(),
                                                        'delete': false
                                                    });
                                                }
                                            });
                                            if (options.length > 0) {
                                                payload['fields']['dt'].push({
                                                    id: field_id,
                                                    dt_type: field_type,
                                                    template_type: field_template_type,
                                                    value: options
                                                });
                                            }
                                        }
                                    } catch (e) {
                                        // Silently handle parsing errors
                                    }
                                    break;

                                case 'date':
                                    // For dt-date, value is already in 'yyyy-mm-dd' format
                                    if (rawValue && rawValue.trim() !== '') {
                                        payload['fields']['dt'].push({
                                            id: field_id,
                                            dt_type: field_type,
                                            template_type: field_template_type,
                                            value: rawValue.trim()
                                        });
                                    }
                                    break;

                                case 'number':
                                    // For dt-number, value should be a numeric value
                                    if (rawValue && rawValue.trim() !== '') {
                                        let numericValue = parseFloat(rawValue.trim());
                                        if (!isNaN(numericValue)) {
                                            payload['fields']['dt'].push({
                                                id: field_id,
                                                dt_type: field_type,
                                                template_type: field_template_type,
                                                value: numericValue
                                            });
                                        }
                                    }
                                    break;

                                case 'link':
                                    // For link fields, value is a URL string
                                    if (rawValue && rawValue.trim() !== '') {
                                        payload['fields']['dt'].push({
                                            id: field_id,
                                            dt_type: field_type,
                                            template_type: field_template_type,
                                            value: rawValue.trim()
                                        });
                                    }
                                    break;

                                default:
                                    // Handle other field types as needed
                                    break;
                            }
                        }
                    });

                    // Handle link input fields (created by addLink function)
                    jQuery('.link-input').each(function(index, entry) {
                        let fieldKey = jQuery(entry).data('field-key');
                        let type = jQuery(entry).data('type');
                        if (jQuery(entry).val()) {
                            // Find if this field already exists in payload
                            let existingField = payload['fields']['dt'].find(f => f.id === fieldKey);
                            if (!existingField) {
                                existingField = {
                                    id: fieldKey,
                                    dt_type: 'link',
                                    template_type: 'dt',
                                    value: { values: [] }
                                };
                                payload['fields']['dt'].push(existingField);
                            }
                            if (!existingField.value.values) {
                                existingField.value = { values: [] };
                            }
                            existingField.value.values.push({
                                value: jQuery(entry).val(),
                                type: type
                            });
                        }
                    });

                    // Handle custom fields
                    jQuery('.form-field[data-template-type="custom"]').each(function (idx, fieldDiv) {
                        let field_id = jQuery(fieldDiv).data('field-id');
                        let fieldInput = jQuery(fieldDiv).find('input, textarea');

                        if (fieldInput.length > 0) {
                            let value = fieldInput.val();
                            if (value && value.trim() !== '') {
                                payload['fields']['custom'].push({
                                    id: field_id,
                                    template_type: 'custom',
                                    value: value, // Don't trim newlines for custom fields (especially textareas)
                                    field_type: fieldInput.is('textarea') ? 'textarea' : 'text'
                                });
                            }
                        }
                    });

                    // Validate required fields (name is required)
                    let hasName = false;
                    payload['fields']['dt'].forEach(function(field) {
                        if (field.id === 'name' && field.value) {
                            hasName = true;
                        }
                    });

                    if (!hasName) {
                        showAlert(jsObject.translations.name_required, true);
                        return;
                    }

                    // Disable button and show spinner
                    submitBtn.prop('disabled', true);
                    spinner.addClass('active');

                    // Submit request
                    jQuery.ajax({
                        type: "POST",
                        data: JSON.stringify(payload),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type + '/create',
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', jsObject.nonce)
                        }
                    }).done(function (data) {
                        if (data.success) {
                            let successMessage = jsObject.translations.create_success;
                            if (data.record_id) {
                                const recordUrl = jsObject.home_url + '/' + jsObject.record_type + '/' + data.record_id;
                                successMessage += '  <a href="' + recordUrl + '" target="_blank">View Record</a>';
                            }
                            showAlert(successMessage, false);
                            // Clear form by resetting DT web component values and regular inputs
                            jQuery('.form-field dt-text').attr('value', '');
                            jQuery('.form-field dt-textarea').attr('value', '');
                            jQuery('.form-field dt-multi-text').attr('value', '[]');
                            // Reset dt-single-select to first option
                            jQuery('.form-field dt-single-select').each(function() {
                                const fieldId = jQuery(this).attr('id');
                                const fieldSettings = jsObject.field_settings[fieldId];
                                if (fieldSettings && fieldSettings.default) {
                                    const firstOptionKey = Object.keys(fieldSettings.default)[0];
                                    if (firstOptionKey) {
                                        jQuery(this).attr('value', firstOptionKey);
                                    } else {
                                        jQuery(this).attr('value', '');
                                    }
                                } else {
                                    jQuery(this).attr('value', '');
                                }
                            });
                            jQuery('.form-field dt-multi-select-button-group').attr('value', '[]');
                            jQuery('.form-field dt-tags').attr('value', '[]');
                            document.querySelector('dt-date').updateTimestamp('')
                            jQuery('.form-field dt-number').attr('value', '');
                            jQuery('.form-field dt-location').attr('value', '');
                            jQuery('.form-field input, .form-field textarea').val('');


                            // Clear any link input fields that were dynamically added
                            jQuery('.link-input').val('');
                            jQuery('.link-section').remove();
                        } else {
                            showAlert(data.message || jsObject.translations.error_occurred, true);
                        }
                    }).fail(function (e) {
                        const errorMsg = e.responseJSON?.message || jsObject.translations.error_occurred;
                        showAlert(errorMsg, true);
                    }).always(function() {
                        // Re-enable button and hide spinner
                        submitBtn.prop('disabled', false);
                        spinner.removeClass('active');
                        document.documentElement.scrollTop = 0;
                    });
                });

                function showAlert(message, isError) {
                    const alertNotice = jQuery('#alert_notice');
                    alertNotice.find('#alert_notice_content').html(message);
                    if (isError) {
                        alertNotice.css('border-color', '#f44336');
                        alertNotice.css('background-color', 'rgba(244,67,54,0.2)');
                    } else {
                        alertNotice.css('border-color', '#4caf50');
                        alertNotice.css('background-color', 'rgba(142,195,81,0.2)');
                    }
                    alertNotice.fadeIn('slow');
                }
            });
        </script>
        <?php
        return true;
    }

    public function body() {
        $has_title = ! empty( $this->template ) && ( isset( $this->template['title'] ) && ! empty( $this->template['title'] ) );
        ?>
        <div id="custom-style"></div>
        <div id="wrapper">
            <div class="grid-x">
                <div class="cell center">
                    <h2 id="title">
                        <b>
                            <?php echo esc_html( $has_title ? $this->adjust_template_title_translation( $this->template['title'], $this->template['title_translations'] ) : 'Create New ' . ucfirst( $this->record_type ) ); ?>
                        </b>
                    </h2>
                </div>
            </div>
            <?php
            if ( $has_title ) {
                ?>
                <hr/>
                <?php
            }
            ?>
            <div id="content">
                <div id="alert_notice" style="display: none; border-style: solid; border-width: 2px; border-color: #4caf50; background-color: rgba(142,195,81,0.2); border-radius: 5px; padding: 2em; margin: 1em 0">
                    <div style="display: flex; grid-gap: 1em">
                        <div style="display: flex; align-items: center">
                            <img style="width: 2em; filter: invert(52%) sepia(77%) saturate(383%) hue-rotate(73deg) brightness(98%) contrast(83%);"
                                 src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'exclamation-circle.svg' ); ?>" alt="Exclamation Circle"/>
                        </div>
                        <div id="alert_notice_content" style="text-align: center; flex: 1;"></div>
                    </div>
                </div>

                <!-- TEMPLATE MESSAGE -->
                <p id="template_msg" style="text-align: center;">
                    <?php echo nl2br( esc_html( ! empty( $this->template ) && isset( $this->template['message'] ) ? $this->template['message'] : 'Use this form to create a new ' . $this->record_type . ' record.' ) ); ?>
                </p>

                <div class="create-form">
                    <?php
                    // Create empty post for field rendering (like template-new-post.php)
                    $empty_post = [
                        'post_type' => $this->record_type
                    ];

                    // Revert back to dt translations like in single-record.php
                    $this->hard_switch_to_default_dt_text_domain();

                    // Display selected fields from template configuration
                    if ( isset( $this->template['fields'] ) && is_array( $this->template['fields'] ) ) {
                        foreach ( $this->template['fields'] as $field ) {
                            if ( !$field['enabled'] || !$this->is_link_obj_field_enabled( $field['id'] ) ) {
                                continue;
                            }

                            $field_id = $field['id'];

                            if ( $field['type'] === 'dt' ) {
                                // Handle DT fields
                                if ( isset( $this->post_field_settings[$field_id] ) ) {
                                    $field_type = $this->post_field_settings[$field_id]['type'];

                                    // Field types to be supported (same as other templates)
                                    if ( ! in_array( $field_type, [
                                        'text',
                                        'textarea',
                                        'date',
                                        'boolean',
                                        'key_select',
                                        'multi_select',
                                        'number',
                                        'link',
                                        'communication_channel',
                                        'location',
                                        'location_meta'
                                    ] ) ) {
                                        continue;
                                    }

                                    ?>
                                    <div class="form-field" data-field-id="<?php echo esc_attr( $field_id ); ?>" data-field-type="<?php echo esc_attr( $field_type ); ?>" data-template-type="dt">
                                        <?php
                                        // Capture rendered field html
                                        $this->post_field_settings[$field_id]['custom_display'] = false;
                                        $this->post_field_settings[$field_id]['readonly'] = false;

                                        // Set required flag for name field
                                        if ( $field_id === 'name' ) {
                                            $this->post_field_settings[$field_id]['required'] = true;
                                        }

                                        // Check if function exists
                                        if ( function_exists( 'render_field_for_display' ) ) {
                                            render_field_for_display( $field_id, $this->post_field_settings, $empty_post, null, null, null, [] );
                                        } else {
                                            echo '<p>Error: Field rendering function not found</p>';
                                        }
                                        ?>
                                    </div>
                                    <?php
                                }
                            } else if ( $field['type'] === 'custom' ) {
                                // Handle custom fields
                                ?>
                                <div class="form-field" data-field-id="<?php echo esc_attr( $field_id ); ?>" data-template-type="custom">
                                    <?php $this->render_custom_field_for_display( $field ); ?>
                                </div>
                                <?php
                            }
                        }
                    } else {
                        // Fallback: if no fields configured, show at least name field
                        if ( isset( $this->post_field_settings['name'] ) ) {
                            ?>
                            <div class="form-field" data-field-id="name" data-field-type="text" data-template-type="dt">
                                <?php
                                $this->post_field_settings['name']['custom_display'] = false;
                                $this->post_field_settings['name']['readonly'] = false;
                                $this->post_field_settings['name']['required'] = true;

                                if ( function_exists( 'render_field_for_display' ) ) {
                                    render_field_for_display( 'name', $this->post_field_settings, $empty_post );
                                } else if ( class_exists( 'Disciple_Tools_Magic_Links_Helper' ) ) {
                                    Disciple_Tools_Magic_Links_Helper::render_field_for_display( 'name', $this->post_field_settings, $empty_post );
                                }
                                ?>
                            </div>
                            <?php
                        }
                    }
                    ?>

                    <!-- SUBMIT NEW RECORD -->
                    <button id="content_submit_but" style="min-width: 100%;" class="button select-button">
                        <?php echo esc_html( 'Create ' . ucfirst( $this->record_type ) ); ?>
                        <span class="update-loading-spinner loading-spinner" style="height: 17px; width: 17px; vertical-align: text-bottom;"></span>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Register REST Endpoints
     */
    public function add_endpoints() {
        $namespace = $this->root . '/v1';
        register_rest_route(
            $namespace, '/' . $this->type . '/create', [
                [
                    'methods'             => 'POST',
                    'callback'            => [ $this, 'create_record' ],
                    'permission_callback' => function ( WP_REST_Request $request ) {
                        $magic = new DT_Magic_URL( $this->root );

                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
    }

    public function create_record( WP_REST_Request $request ){
        $params = $request->get_params();

        if ( !isset( $params['fields'] ) || !isset( $params['fields']['dt'] ) ) {
            return new WP_Error( __METHOD__, 'Missing field data', [ 'status' => 400 ] );
        }

        if ( !is_user_logged_in() ){
            return new WP_Error( __METHOD__, 'User not logged in', [ 'status' => 401 ] );
        }

        // Determine the actual record type to create
        $record_type = $params['record_type'] ?? $this->record_type;

        // Prepare record fields for DT_Posts::create_post
        $updates = [];

        // Process DT field values using the same approach as create-contact.php
        foreach ( $params['fields']['dt'] ?? [] as $field ) {
            switch ( $field['dt_type'] ) {
                case 'text':
                case 'key_select':
                    if ( !empty( $field['value'] ) ) {
                        $updates[$field['id']] = sanitize_text_field( $field['value'] );
                    }
                    break;

                case 'textarea':
                    if ( !empty( $field['value'] ) ) {
                        // For textarea, preserve newlines and use sanitize_textarea_field
                        $updates[$field['id']] = sanitize_textarea_field( $field['value'] );
                    }
                    break;

                case 'communication_channel':
                    if ( !empty( $field['value'] ) && is_array( $field['value'] ) ) {
                        $updates[$field['id']] = [];
                        foreach ( $field['value'] as $value ) {
                            if ( !empty( $value['value'] ) ) {
                                $updates[$field['id']][] = [
                                    'value' => sanitize_text_field( $value['value'] )
                                ];
                            }
                        }
                    }
                    break;

                case 'multi_select':
                    if ( !empty( $field['value'] ) && is_array( $field['value'] ) ) {
                        $options = [];
                        foreach ( $field['value'] as $option ) {
                            if ( !empty( $option['value'] ) && !$option['delete'] ) {
                                $options[] = [
                                    'value' => sanitize_text_field( $option['value'] )
                                ];
                            }
                        }
                        if ( !empty( $options ) ) {
                            $updates[$field['id']] = [ 'values' => $options ];
                        }
                    }
                    break;

                case 'date':
                    if ( !empty( $field['value'] ) && is_string( $field['value'] ) ) {
                        // Handle date field - value should be in 'yyyy-mm-dd' format
                        $date_value = sanitize_text_field( $field['value'] );

                        // Validate date format and store as yyyy-mm-dd
                        if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_value ) ) {
                            // Verify it's a valid date
                            $timestamp = strtotime( $date_value );
                            if ( $timestamp && $timestamp > 0 ) {
                                $updates[$field['id']] = $date_value;
                            }
                        }
                    }
                    break;

                case 'number':
                    if ( isset( $field['value'] ) && is_numeric( $field['value'] ) ) {
                        // Handle number field - store as numeric value
                        $updates[$field['id']] = floatval( $field['value'] );
                    }
                    break;

                case 'link':
                    if ( !empty( $field['value'] ) ) {
                        // Handle link field - can be single URL or array of links
                        if ( is_string( $field['value'] ) ) {
                            // Simple URL string
                            $updates[$field['id']] = sanitize_text_field( $field['value'] );
                        } elseif ( is_array( $field['value'] ) && isset( $field['value']['values'] ) ) {
                            // DT format with values array
                            $links = [];
                            foreach ( $field['value']['values'] as $link ) {
                                if ( !empty( $link['value'] ) ) {
                                    $links[] = [
                                        'value' => sanitize_text_field( $link['value'] ),
                                        'type' => sanitize_text_field( $link['type'] ?? '' )
                                    ];
                                }
                            }
                            if ( !empty( $links ) ) {
                                $updates[$field['id']] = [
                                    'values' => $links
                                ];
                            }
                        }
                    }
                    break;

                default:
                    // Handle other field types as needed
                    break;
            }
        }

        // Handle custom fields by saving them as comments
        $custom_field_comments = [];
        foreach ( $params['fields']['custom'] ?? [] as $field ) {
            if ( !empty( $field['value'] ) ) {
                // Use appropriate sanitization based on field type
                if ( isset( $field['field_type'] ) && $field['field_type'] === 'textarea' ) {
                    $sanitized_value = sanitize_textarea_field( $field['value'] );
                } else {
                    $sanitized_value = sanitize_text_field( $field['value'] );
                }

                // Get field label from template configuration
                $field_label = $field['id']; // Default to ID if label not found
                if ( isset( $this->template['fields'] ) && is_array( $this->template['fields'] ) ) {
                    foreach ( $this->template['fields'] as $template_field ) {
                        if ( (int) $template_field['id'] === $field['id'] && $template_field['type'] === 'custom' ) {
                            // Support custom field label translations; or simply default to initial label entry.
                            $field_label = ( ! empty( $template_field['translations'] ) && isset( $template_field['translations'][ determine_locale() ] ) ) ? $template_field['translations'][ determine_locale() ]['translation'] : $template_field['label'];
                            break;
                        }
                    }
                }

                $custom_field_comments[] = $field_label . ': ' . $sanitized_value;
            }
        }

        // Validate that we have a name
        if ( empty( $updates['name'] ) ) {
            return new WP_Error( __METHOD__, 'Record name is required', [ 'status' => 400 ] );
        }

        // Create the record
        $result = DT_Posts::create_post( $record_type, $updates );

        if ( is_wp_error( $result ) ) {
            return [
                'success' => false,
                'message' => $result->get_error_message()
            ];
        }

        // Add custom field comments if any
        if ( !empty( $custom_field_comments ) ) {
            // Add each custom field as a separate comment
            foreach ( $custom_field_comments as $comment ) {
                DT_Posts::add_post_comment( $record_type, $result['ID'], $comment );
            }
        }

        return [
            'success' => true,
            'record_id' => $result['ID'],
            'message' => 'Record created successfully!'
        ];
    }
}
