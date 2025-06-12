jQuery(function ($) {

  // Initial States
  $(document).ready(function () {
    setup_widgets(true, function () {
      let link_obj = window.dt_magic_links.dt_previous_updated_link_obj;
      if (link_obj) {
        $('#ml_main_col_available_link_objs_select').val(link_obj['id']).trigger('change');
      }
    });
  });

  // Event Listeners
  $(document).on('click', '#ml_main_col_available_link_objs_new', function () {
    handle_new_link_obj_request();
  });

  $(document).on('change', '#ml_main_col_link_objs_manage_type', function () {
    display_magic_link_type_fields();
  });

  $(document).on('click', '#ml_main_col_assign_users_teams_add', function () {
    handle_add_users_teams_request(true, determine_assignment_user_select_id(), true, false, function () {
      sort_assign_users_teams_table();
    });
  });

  $(document).on('change', '.ml-main-col-assign-users-teams-table-row-options', function (e) {
    handle_assigned_users_teams_table_row_options(e);
  });

  $(document).on('click', '#ml_main_col_delete_but', function () {
    handle_delete_request();
  });

  $(document).on('click', '.ml-links-update-but', function () {
    handle_update_request();
  });

  $(document).on('change', '#ml_main_col_available_link_objs_select', function () {
    handle_load_link_obj_request();
  });

  $(document).on('click', '#ml_main_col_link_objs_manage_expires_never', function () {
    toggle_never_expires_element_states(true);
  });

  $(document).on('click', '#ml_main_col_link_manage_links_expire_never', function () {
    toggle_never_expires_element_states(false);
  });

  $(document).on('click', '#ml_main_col_schedules_enabled', function () {
    toggle_schedule_manage_element_states();
  });

  $(document).on('click', '#ml_main_col_schedules_send_now_but', function () {
    handle_send_now_request();
  });

  $(document).on('click', '.ml-links-docs', function (evt) {
    handle_docs_request($(evt.currentTarget).data('title'), $(evt.currentTarget).data('content'));
  });

  $(document).on('click', '#ml_main_col_link_manage_links_but_refresh', function () {
    handle_assigned_user_links_management('refresh', function () {
    });
  });

  $(document).on('click', '#ml_main_col_link_manage_links_but_delete', function () {
    handle_assigned_user_links_management('delete', function () {
    });
  });

  $(document).on('click', '.enable_connection_fields input:checkbox', function (evt) {
    handle_enable_connection_fields_config_selection(evt.currentTarget);
  });

  // Helper Functions
  function setup_widgets(refresh = true, callback) {
    $('#ml_main_col_assign_users_teams_table_notice').fadeOut('fast');

    // Specify setup data to be returned.
    let payload = {
      'dt_magic_link_types': !window.dt_magic_links.dt_magic_link_types || refresh,
      'dt_magic_link_templates': !window.dt_magic_links.dt_magic_link_templates || refresh,
      'dt_magic_link_objects': !window.dt_magic_links.dt_magic_link_objects || refresh,
      'dt_sending_channels': !window.dt_magic_links.dt_sending_channels || refresh,
      'dt_template_messages': !window.dt_magic_links.dt_template_messages || refresh
    };
    $.ajax({
      url: window.dt_magic_links.dt_endpoint_setup_payload,
      method: 'POST',
      data: payload,
      beforeSend: (xhr) => {
        xhr.setRequestHeader("X-WP-Nonce", window.dt_admin_scripts.nonce);
      },
      success: function (data) {
        if ( data['dt_magic_link_objects'] ) {
          refresh_section_available_link_objs( data['dt_magic_link_objects'] );
        }
        if ( data['dt_magic_link_types'] || data['dt_magic_link_templates'] ) {
          refresh_section_link_objs_manage_types( data['dt_magic_link_types'], data['dt_magic_link_templates'] );
        }
        if ( data['dt_sending_channels'] ) {
          reset_section_schedules_sending_channels( data['dt_sending_channels'] );
        }
        if ( data['dt_template_messages'] ) {
          reset_section_msg_template_messages( data['dt_template_messages'] );
        }

        callback();
      },
      error: function (data) {
        console.log(data);
      }
    });
  }

  function sort_assign_users_teams_table() {
    let assigned_table = $('#ml_main_col_assign_users_teams_table');
    let sorted = assigned_table.find('tbody > tr').sort(function (a, b) {
      return $(a).find('#ml_main_col_assign_users_teams_table_row_name').val().toLowerCase().localeCompare($(b).find('#ml_main_col_assign_users_teams_table_row_name').val().toLowerCase());
    });

    // Refresh table with sorted rows.
    assigned_table.find('tbody > tr').remove();
    assigned_table.find('tbody').append(sorted);
  }

  function handle_delete_request() {
    let id = $('#ml_main_col_link_objs_manage_id').val();
    let name = $('#ml_main_col_link_objs_manage_name').val();

    if (id && confirm(`Are you sure you wish to delete ${name}?`)) {
      $('#ml_main_col_delete_form_link_obj_id').val(id);
      $('#ml_main_col_delete_form').submit();
    }
  }

  function handle_new_link_obj_request() {
    reset_sections(true);
  }

  function reset_sections(display) {
    reset_section_available_link_objs();

    reset_section(display, $('#ml_main_col_link_objs_manage'), function () {
      reset_section_link_objs_manage(moment().unix(), true, '', moment().unix(), true, '');
    });

    reset_section(display, $('#ml_main_col_ml_type_fields'), function () {
      reset_section_ml_type_fields();
    });

    reset_section(display, $('#ml_main_col_assign_users_teams'), function () {
      reset_section_assign_users_teams([], function () {
      });
    });

    reset_section(display, $('#ml_main_col_link_manage'), function () {
      reset_section_link_manage('3', 'days', false, false);
    });

    reset_section(display, $('#ml_main_col_message'), function () {
      let default_msg_subject = window.dt_magic_links.dt_default_message_subject;
      let default_msg = window.dt_magic_links.dt_default_message;
      reset_section_message(default_msg_subject, default_msg);
    });

    reset_section(display, $('#ml_main_col_schedules'), function () {
      let default_send_channel_id = window.dt_magic_links.dt_default_send_channel_id;
      reset_section_schedules(false, '1', 'hours', default_send_channel_id, moment().unix(), '', true, false);
    });

    $('#ml_main_col_update_msg').html('').fadeOut('fast');

    if (display) {
      $('#ml_main_col_delete_but').fadeIn('fast');
    }
  }

  function reset_section_available_link_objs() {
    $('#ml_main_col_available_link_objs_select').val('');
  }

  function refresh_section_available_link_objs(link_objs = {}) {
    let link_objs_select = $('#ml_main_col_available_link_objs_select');
    $(link_objs_select).empty();
    $(link_objs_select).append($('<option/>').prop('disabled', true).prop('selected', true).val('').text('-- select available link object --'));

    $.each(link_objs, function (id, link_obj) {
      $(link_objs_select).append($('<option/>').val(window.dt_admin_shared.escape(id)).text(window.dt_admin_shared.escape(link_obj.name)));
    });

    $(link_objs_select).val('');

    // Update global variable.
    window.dt_magic_links.dt_magic_link_objects = link_objs;
  }

  function reset_section_link_objs_manage(id, enabled, name, expires_ts_secs, never_expires, type) {
    $('#ml_main_col_link_objs_manage_id').val(id);
    $('#ml_main_col_link_objs_manage_enabled').prop('checked', enabled);
    $('#ml_main_col_link_objs_manage_name').val(name);
    $('#ml_main_col_link_objs_manage_expires_ts').val(expires_ts_secs);
    $('#ml_main_col_link_objs_manage_expires').daterangepicker({
      singleDatePicker: true,
      timePicker: true,
      startDate: moment.unix(expires_ts_secs),
      locale: {
        format: 'YYYY-MM-DD hh:mm A'
      }
    }, function (start, end, label) {
      // As we are in single date picker mode, just focus on start date and convert to epoch timestamp.
      if (start) {
        $('#ml_main_col_link_objs_manage_expires_ts').val(start.unix());
      }
    });
    $('#ml_main_col_link_objs_manage_expires_never').prop('checked', never_expires);
    $('#ml_main_col_link_objs_manage_type').val(type);

    toggle_never_expires_element_states(true);
  }

  function refresh_section_link_objs_manage_types(link_types = [], link_templates = {}) {
    let link_types_select = $('#ml_main_col_link_objs_manage_type');
    $(link_types_select).empty();
    $(link_types_select).append($('<option/>').prop('disabled', true).prop('selected', true).text('-- select magic link type to be sent --'));

    // Source available magic link types, ignoring templates at this stage
    if ( link_types ) {
      $.each(link_types, function (idx, type) {

        /**
         * Filter out master template class; which, in itself, is only the shepherd of child templates!
         * Actual child magic link templates are extracted in the code block below; from options table.
         */

        if (!type['meta']['class_type'] || !['template'].includes(type['meta']['class_type'])) {
          $(link_types_select).append($('<option/>').val(window.dt_admin_shared.escape(type['key'])).text(window.dt_admin_shared.escape(type['label'])));
        }

      });
    }

    // Source available magic link templates
    if ( link_templates ) {
      let supported_template_post_types = window.dt_magic_links.dt_supported_template_post_types;
      $(link_types_select).append($('<option/>').prop('disabled', true).text('-- templates --'));
      $.each(link_templates, function (post_type, templates) {
        if ( supported_template_post_types.includes( post_type ) ) {
          $.each(templates, function (template_key, template) {
            if ( template['enabled'] && template['enabled'] === true ) {
              $(link_types_select).append($('<option/>').val(window.dt_admin_shared.escape(template['id'])).text(window.dt_admin_shared.escape(template['name'])));
            }
          });
        }
      });
    }

    // Update global variables.
    window.dt_magic_links.dt_magic_link_types = link_types;
    window.dt_magic_links.dt_magic_link_templates = link_templates;
  }

  function reset_section_ml_type_fields() {
    $('#ml_main_col_ml_type_fields_table').find('tbody tr').remove();
  }

  function reset_section_assign_users_teams(assigned_users_teams, callback) {
    $('#ml_main_col_assign_users_teams_table').find('tbody > tr').remove();

    if (assigned_users_teams && assigned_users_teams.length > 0) {

      // Ensure to filter out team & group members and sort.
      assigned_users_teams = assigned_users_teams.filter((assigned) => assigned?.type !== 'member')
      .sort((a, b) => {
        if (a?.name.toLowerCase() < b?.name.toLowerCase()) {
          return -1;
        }

        if (a?.name.toLowerCase() > b?.name.toLowerCase()) {
          return 1;
        }

        return 0;
      });

      // Manage assignment notice display.
      let counter = 0;
      const assign_users_teams_notice = $('#ml_main_col_assign_users_teams_table_notice');
      $(assign_users_teams_notice).find('span').show();
      $(assign_users_teams_notice).fadeIn('fast');

      // Once filtered, proceed with assigned table build, including all team & group members.
      assigned_users_teams.forEach(function (element, idx) {
        handle_add_users_teams_request(false, element['id'], true, true, function () {

          // Retrospectively update link expiration details
          if (element['links_expire_within_base_ts'] && element['links_expire_on_ts'] && element['links_expire_on_ts_formatted']) {
            $('#ml_main_col_assign_users_teams_table').find('tbody > tr').each(function (tr_idx, tr) {

              // Identify corresponding tr element
              let id = $(tr).find('#ml_main_col_assign_users_teams_table_row_id').val();
              if (id && new String(id).valueOf() === new String(element['id']).valueOf()) {
                $(tr).find('#ml_main_col_assign_users_teams_table_row_td_link_expires_base_ts').val(element['links_expire_within_base_ts']);
                $(tr).find('#ml_main_col_assign_users_teams_table_row_td_link_expires_on_ts').val(element['links_expire_on_ts']);
                $(tr).find('#ml_main_col_assign_users_teams_table_row_td_link_expires_on_ts_formatted').html(element['links_expire_on_ts_formatted']);
              }
            });
          }

          // Execute callback
          callback();

          // Hide notice on final assignment.
          if ( ++counter >= assigned_users_teams.length ) {
            $(assign_users_teams_notice).fadeOut('fast');
          }
        });
      });
    }

    // Toggle management button states accordingly based on assigned table shape!
    toggle_assigned_user_links_manage_but_states();
  }

  function reset_section_link_manage(links_expire_within_amount, links_expire_within_time_unit, links_never_expires, links_expire_auto_refresh_enabled) {
    $('#ml_main_col_link_manage_links_expire_amount').val(links_expire_within_amount);
    $('#ml_main_col_link_manage_links_expire_time_unit').val(links_expire_within_time_unit);
    $('#ml_main_col_link_manage_links_expire_never').prop('checked', new String(links_never_expires).valueOf().toLowerCase() === 'true');
    $('#ml_main_col_link_manage_links_expire_auto_refresh_enabled').prop('checked', new String(links_expire_auto_refresh_enabled).valueOf().toLowerCase() === 'true');

    if ($('#ml_main_col_link_manage_links_expire_never').prop('checked')) {
      toggle_never_expires_element_states(false);
    }
  }

  function reset_section_message(subject, message, template_message = '') {
    $('#ml_main_col_msg_textarea_subject').val(subject);
    $('#ml_main_col_msg_textarea').val(message);
    $('#ml_main_col_msg_template_messages').val(template_message);
  }

  function reset_section_schedules(enabled, freq_amount, freq_time_unit, sending_channel, last_schedule_run, last_success_send, links_refreshed_before_send, send_now) {
    $('#ml_main_col_schedules_enabled').prop('checked', enabled);
    $('#ml_main_col_schedules_frequency_amount').val(freq_amount);
    $('#ml_main_col_schedules_frequency_time_unit').val(freq_time_unit);

    let sending_channel_option_present = false;
    $('#ml_main_col_schedules_sending_channels option')
      .filter(function (idx, element) {
        if ($(element).val() === sending_channel) {
          sending_channel_option_present = true;
        }
      });
    $('#ml_main_col_schedules_sending_channels').val(sending_channel_option_present ? sending_channel : '');

    //toggle_never_expires_element_states(false);
    toggle_schedule_manage_element_states();

    $('#ml_main_col_schedules_last_schedule_run').val(last_schedule_run);
    $('#ml_main_col_schedules_last_success_send').val(last_success_send);

    $('#ml_main_col_schedules_links_refreshed_before_send').prop('checked', new String(links_refreshed_before_send).valueOf().toLowerCase() === 'true');

    $('#ml_main_col_schedules_send_now_but').prop('disabled', !send_now);

    // Activate next schedule run date picker.
    $('#ml_main_col_schedules_next_schedule_run_date_picker').daterangepicker({
      singleDatePicker: true,
      timePicker: true,
      minDate: moment(),
      locale: {
        format: 'dddd, MMMM Do YYYY, h:mm A Z'
      }
    }, function (start, end, label) {

      // Adjust last schedule run, in order to accommodate manually specified next schedule runs!
      let freq_amount = $('#ml_main_col_schedules_frequency_amount').val();
      let freq_time_unit = $('#ml_main_col_schedules_frequency_time_unit').val();
      let adjusted_last_schedule_run = start.subtract(freq_amount, freq_time_unit);
      if (adjusted_last_schedule_run) {
        $('#ml_main_col_schedules_last_schedule_run').val(adjusted_last_schedule_run.unix());
      }

      // Clear relative time info; as next scheduled date has been manually adjusted!
      $('#ml_main_col_schedules_next_schedule_run_relative_time').html('');
    });
  }

  function reset_section_schedules_sending_channels(sending_channels) {
    let sending_channels_select = $('#ml_main_col_schedules_sending_channels');
    $(sending_channels_select).empty();
    $(sending_channels_select).append($('<option/>').prop('disabled', true).prop('selected', true).val('').text('-- select sending channel --'));

    if ( sending_channels ) {
      sending_channels.sort(function (a, b) {
        return a['name'].toLowerCase().localeCompare(b['name'].toLowerCase());
      });

      $.each(sending_channels, function (idx, channel) {
        $(sending_channels_select).append($('<option/>').val(window.dt_admin_shared.escape(channel['id'])).text(window.dt_admin_shared.escape(channel['name'])));
      });
    }

    $(sending_channels_select).val('');

    // Update global variables.
    window.dt_magic_links.dt_sending_channels = sending_channels;
  }

  function reset_section_msg_template_messages(template_messages) {
    let template_messages_select = $('#ml_main_col_msg_template_messages');
    $(template_messages_select).empty();
    $(template_messages_select).append($('<option/>').prop('disabled', false).prop('selected', true).val('').text('-- select template message --'));

    if ( template_messages ) {
      for (const [key, value] of Object.entries(template_messages)) {
        $(template_messages_select).append($('<option/>').val(window.dt_admin_shared.escape(value['id'])).text(window.dt_admin_shared.escape(value['name'])));
      }
    }

    $(template_messages_select).val('');

    // Update global variables.
    window.dt_magic_links.dt_template_messages = template_messages;
  }

  function reset_section(display, section, reset_element_func) {
    section.fadeOut('fast', function () {

      // Reset elements
      reset_element_func();

      // If flagged to do so, display sub-section
      if (display) {
        section.fadeIn('fast');
      }
    });
  }

  function toggle_schedule_manage_element_states() {
    let checked = $('#ml_main_col_schedules_enabled').prop('checked');

    $('#ml_main_col_schedules_frequency_amount').prop('disabled', !checked);
    $('#ml_main_col_schedules_frequency_time_unit').prop('disabled', !checked);
    $('#ml_main_col_schedules_next_schedule_run_date_picker').prop('disabled', !checked);
    $('#ml_main_col_schedules_next_schedule_run_relative_time').html('');
  }

  function toggle_never_expires_element_states(is_obj_level) {

    if (is_obj_level) { // Object Level
      let disabled = $('#ml_main_col_link_objs_manage_expires_never').prop('checked');
      $('#ml_main_col_link_objs_manage_expires').prop('disabled', disabled);

    } else { // Link Level
      let disabled = $('#ml_main_col_link_manage_links_expire_never').prop('checked');
      $('#ml_main_col_link_manage_links_expire_amount').prop('disabled', disabled);
      $('#ml_main_col_link_manage_links_expire_time_unit').prop('disabled', disabled);
      $('#ml_main_col_link_manage_links_expire_auto_refresh_enabled').prop('disabled', disabled);

      // Uncheck relevant widgets accordingly
      if (disabled) {
        $('#ml_main_col_link_manage_links_expire_auto_refresh_enabled').prop('checked', false);
      }
    }

  }

  function toggle_assigned_user_links_manage_but_states() {
    let assigned_count = $('#ml_main_col_assign_users_teams_table').find('tbody > tr').length;
    let enabled = (assigned_count > 0);

    $('#ml_main_col_link_manage_links_but_refresh').prop('disabled', !enabled);
    $('#ml_main_col_link_manage_links_but_delete').prop('disabled', !enabled);
  }

  function fetch_magic_link_type_obj(type_key) {
    let type_obj = null;

    if (type_key) {
      let magic_link_types = window.dt_magic_links.dt_magic_link_types;
      if (magic_link_types) {
        magic_link_types.forEach(function (type, type_idx) {
          if (type['key'] === type_key) {
            type_obj = type;
          }
        });
      }
    }

    return type_obj;
  }

  function fetch_magic_link_template(type_key) {
    let template = null;

    if (type_key) {
      let magic_link_templates = window.dt_magic_links.dt_magic_link_templates;
      if (magic_link_templates) {

        // Parse templates in search of required template
        $.each(magic_link_templates, function (post_type_id, post_type) {
          $.each(post_type, function (template_id, template_obj) {

            // Match, keeping in mind template_ prefix
            if (type_key.includes(template_obj['id'])) {
              template = template_obj;
            }
          });
        });
      }
    }

    return template;
  }

  function is_template(type_key) {
    return (type_key && type_key.includes('templates_'));
  }

  function build_magic_link_type_field_html(type_key, id, label, field_type) {
    return `<tr>
              <input id="ml_main_col_ml_type_fields_table_row_field_type" type="hidden" value="${field_type}">
              <input id="ml_main_col_ml_type_fields_table_row_field_id" type="hidden" value="${id}">
              <td>${window.lodash.escape(label)}</td>
              <td><input id="ml_main_col_ml_type_fields_table_row_field_enabled" type="checkbox" ${is_magic_link_type_field_enabled(type_key, id, fetch_link_obj($('#ml_main_col_available_link_objs_select').val())) ? 'checked' : ''}></td>
            </tr>`;
  }

  function display_magic_link_type_fields() {
    let fields_table = $('#ml_main_col_ml_type_fields_table');
    let config_table = $('#ml_main_col_ml_type_config_table');
    let type_key = $('#ml_main_col_link_objs_manage_type').val();

    // Refresh fields table
    fields_table.fadeOut('fast', function () {
      fields_table.find('tbody > tr').remove();
      config_table.show();

      // Hide all, but ensure default configs still show
      config_table.find('tbody > tr').hide();

      // Distinguish between regular magic link types and templates
      if (is_template(type_key)) {

        let template = fetch_magic_link_template(type_key);
        if (template) {

          // Ignore disabled fields
          $.each(template['fields'], function (idx, field) {
            if (field['enabled']) {
              fields_table.find('tbody:last').append(build_magic_link_type_field_html(type_key, field['id'], field['label'], ''));
            }
          });

          // Adjust assigned selector accordingly, defaulting to contacts only
          adjust_assigned_selector_by_magic_link_type(true);
        }

      } else {

        let type_obj = fetch_magic_link_type_obj(type_key);
        if (type_obj) {

          // Refresh fields list accordingly
          type_obj['meta']['fields'].forEach(function (field, field_idx) {
            if (field['id'] && field['label']) {
              fields_table.find('tbody:last').append(build_magic_link_type_field_html(type_key, field['id'], field['label'], field['field_type']));
            }
          });

          // Adjust assigned selector accordingly, based on type object contacts flag
          adjust_assigned_selector_by_magic_link_type(type_obj['meta']['contacts_only']);

          // Show config settings
          let has_config = false;
          let link_obj = fetch_link_obj($('#ml_main_col_available_link_objs_select').val());

          // enable_connection_fields
          let enable_connection_fields_tr = config_table.find('tr.enable_connection_fields');
          let enable_connection_fields_tr_checkbox = config_table.find('tr.enable_connection_fields input[type=checkbox]');
          if (link_obj && link_obj['type_config']) {
            has_config = true;
            enable_connection_fields_tr.show();
            enable_connection_fields_tr_checkbox.prop('checked', link_obj['type_config']['enable_connection_fields']);
          }

          // Adjust connection field enabled states accordingly
          handle_enable_connection_fields_config_selection(enable_connection_fields_tr_checkbox, (link_obj && link_obj['type_fields']) ? link_obj['type_fields']:[]);

          // supports_create
          if (type_obj['meta']['supports_create']) {
            config_table.find('tr.supports_create').show();
            let checked = false;
            if (link_obj && link_obj['type_config'] && link_obj['type_config']['supports_create']) {
              checked = true;
              has_config = true;
            }
            config_table.find('tr.supports_create input[type=checkbox]').prop('checked', checked);
          }

          if (type_obj['meta']['supports_logo'] ) {
            config_table.find('tr.display_logo').show();
            let checked = false;
            let link_obj = fetch_link_obj($('#ml_main_col_available_link_objs_select').val());
            if (link_obj && link_obj['type_config'] && link_obj['type_config']['display_logo']) {
              checked = true;
              has_config = true;
            }
            config_table.find('tr.display_logo input[type=checkbox]').prop('checked', checked);
          }

          if (has_config) {
            config_table.show();
          }
        }
      }

      // Final config table setting adjustments.
      adjust_config_table_settings();

      // Display fields table
      fields_table.fadeIn('fast');

    });
  }

  function adjust_config_table_settings() {
    let config_table = $('#ml_main_col_ml_type_config_table');
    let fields_table = $('#ml_main_col_ml_type_fields_table');

    // Display all default config settings.
    config_table.find('tbody > tr.default_config').show();

    // Determine if enable_connection_fields config should be displayed - Are there any connection fields?
    let display_config_enable_connection_fields = false;
    fields_table.find('tbody > tr > #ml_main_col_ml_type_fields_table_row_field_type').each(function (idx, field_type) {
      if ($(field_type).val() === 'connection') {
        display_config_enable_connection_fields = true;
      }
    });
    if (!display_config_enable_connection_fields) {
      config_table.find('tbody > tr.enable_connection_fields').hide();
    }
  }

  function adjust_assigned_selector_by_magic_link_type(contacts_only) {
    let users_teams_typeahead_div = $('#ml_main_col_assign_users_teams_typeahead_div');

    // Display contacts typeahead input field
    users_teams_typeahead_div.fadeOut('fast', function () {

      // Build typeahead object
      let typeahead_obj = build_assign_users_teams_typeahead_obj(contacts_only);
      users_teams_typeahead_div.html(typeahead_obj['html']);

      // Instantiate typeahead configuration
      $('#ml_main_col_assign_users_teams_typeahead_input').typeahead({
        order: "asc",
        accent: true,
        minLength: 0,
        maxItem: contacts_only ? 10 : 20,
        dynamic: true,
        searchOnFocus: true,
        source: typeahead_obj['typeahead'][contacts_only ? 'endpoint_contacts_only' : 'endpoint_users_teams_groups'](window.dt_magic_links.dt_wp_nonce),
        callback: {
          onClick: function (node, a, item, event) {
            let id = typeahead_obj['typeahead']['id_func'](item);
            if (id) {
              if (contacts_only) {
                // Fetch associated post record...
                get_post_record_request('contacts', id, function (post) {

                  // ...and stringify for future downstream processing
                  $('#ml_main_col_assign_users_teams_typeahead_hidden').val(JSON.stringify(post));
                });
              } else {

                // Stringify directly, as user, team & group responses; come already self-packaged.
                $('#ml_main_col_assign_users_teams_typeahead_hidden').val(JSON.stringify(item));

              }
            }
          }
        }
      });

      // Display recently generated html
      users_teams_typeahead_div.fadeIn('fast');
    });
  }

  function build_assign_users_teams_typeahead_obj(contacts_only) {
    let base_url = window.dt_magic_links.dt_base_url;

    let response = {};
    response['id'] = Date.now();

    let html = '<div class="typeahead__container"><div class="typeahead__field"><div class="typeahead__query">';
    html += `<input type="hidden" id="ml_main_col_assign_users_teams_typeahead_hidden">`;
    html += `<input type="text" class="dt-typeahead" autocomplete="off" placeholder="Start typing ${(contacts_only ? `contact` : `user, team or group`)} details..." style="min-width: 90%;" id="ml_main_col_assign_users_teams_typeahead_input">`;
    html += '</div></div></div>';
    response['html'] = html;

    response['typeahead'] = {
      endpoint_contacts_only: function (wp_nonce) {
        return {
          connections: {
            display: ["name", "ID"],
            template: "<span>{{name}}</span>",
            ajax: {
              url: base_url + 'dt-posts/v2/contacts/compact',
              data: {
                s: '{{query}}'
              },
              beforeSend: function (xhr) {
                xhr.setRequestHeader("X-WP-Nonce", wp_nonce);
              },
              callback: {
                done: function (response) {
                  return (response['posts']) ? response['posts'] : [];
                }
              }
            }
          }
        }
      },
      endpoint_users_teams_groups: function (wp_nonce) {
        return {
          connections: {
            display: ["name", "id", "dt_type"],
            template: "<span>{{name}} [{{dt_type}}]</span>",
            ajax: {
              url: window.dt_magic_links.dt_endpoint_typeahead_users_teams_groups,
              data: {
                s: '{{query}}'
              },
              beforeSend: function (xhr) {
                xhr.setRequestHeader("X-WP-Nonce", wp_nonce);
              },
              callback: {
                done: function (response) {

                  // Merge response into a single flattened array.
                  let merged_results = [];
                  if ( response['dt_users'] ) {
                    merged_results = merged_results.concat( response['dt_users'] );
                  }

                  if ( response['dt_teams'] ) {
                    merged_results = merged_results.concat( response['dt_teams'] );
                  }

                  if ( response['dt_groups'] ) {
                    merged_results = merged_results.concat( response['dt_groups'] );
                  }

                  return merged_results;
                }
              }
            }
          }
        }
      },
      id_func: function (item) {
        if (item) {
          if (item['ID']) {
            return item['ID'];
          }
          if (item['id']) {
            return item['id'];
          }
        }
        return null;
      }
    };

    return response;
  }

  function get_post_record_request(post_type, post_id, callback) {

    // Build request payload
    let payload = {
      post_type: post_type,
      post_id: post_id,
      link_obj_id: $('#ml_main_col_link_objs_manage_id').val()
    };

    // Dispatch request.
    $.ajax({
      url: window.dt_magic_links.dt_endpoint_get_post_record,
      method: 'GET',
      data: payload,
      beforeSend: (xhr) => {
        xhr.setRequestHeader("X-WP-Nonce", window.dt_admin_scripts.nonce);
      },
      success: function (data) {
        if (data && data['success'] === true) {
          callback(data['post']);

        } else {
          console.log(data);
        }
      },
      error: function (data) {
        console.log(data);
      }
    });
  }

  function determine_assignment_user_select_id() {
    let typeahead_hidden = $('#ml_main_col_assign_users_teams_typeahead_hidden');
    if (typeahead_hidden.val()) {
      let obj = JSON.parse(typeahead_hidden.val());

      // Structure id accordingly, based on dt type.
      if (obj) {
        if (obj['dt_type']) {
          switch (obj['dt_type']) {
            case 'user':
              return 'users+' + $.trim(obj['id']);
            case 'team':
              return 'teams+' + $.trim(obj['id']);
            case 'group':
              return 'groups+' + $.trim(obj['id']);
          }
        } else {
          return 'contacts+' + $.trim(obj['ID']);
        }
      }
    }

    return null;
  }

  function is_magic_link_type_field_enabled(type, field_id, link_obj) {

    // Enabled by default
    let enabled = true;

    // Ensure we have a valid link object
    if (link_obj) {

      // Ensure there is a magic link type match
      if (link_obj['type'] && String(link_obj['type']) === String(type)) {

        // Ensure there are stored type field settings
        if (link_obj['type_fields']) {
          link_obj['type_fields'].forEach(function (field, field_idx) {

            // Assuming we have a match, determine field's current enabled state
            if (field['id'] && String(field['id']) === String(field_id)) {
              enabled = field['enabled'];
            }
          });
        }
      }
    }

    return enabled;
  }

  function handle_add_users_teams_request(auto_update, selected_id, inc_default_members, load_assigned_details, callback) {

    // Flag to determine callback execution
    let execute_callback = true;

    // Ensure selection is valid and table does not already contain selection...
    if (selected_id && !already_has_users_teams(selected_id)) {

      /**
       * If add request has been triggered following a manual click, then generate
       * html the good old fashion way; otherwise, we need to get creative and go async;
       * with the use of callbacks and the like! ;)
       */

      // Add new row accordingly, based on selection type
      let html = null;
      if (selected_id.startsWith('users+')) {
        if (load_assigned_details === false) {
          html = build_user_row_html(auto_update, selected_id);
        } else {
          html = null;
          execute_callback = false;
          build_assigned_row_html_async('dt_users', auto_update, selected_id, inc_default_members, callback);
        }
      } else if (selected_id.startsWith('teams+')) {
        if (load_assigned_details === false) {
          html = build_team_group_row_html(auto_update, selected_id, inc_default_members, 'Team');
        } else {
          html = null;
          execute_callback = false;
          build_assigned_row_html_async('dt_teams', auto_update, selected_id, inc_default_members, callback);
        }
      } else if (selected_id.startsWith('groups+')) {
        if (load_assigned_details === false) {
          html = build_team_group_row_html(auto_update, selected_id, inc_default_members, 'Group');
        } else {
          html = null;
          execute_callback = false;
          build_assigned_row_html_async('dt_groups', auto_update, selected_id, inc_default_members, callback);
        }
      } else if (selected_id.startsWith('contacts+')) {
        if (load_assigned_details === false) {
          html = build_contact_row_html(auto_update, selected_id);
        } else {

          // Ensure callback is executed following async return
          execute_callback = false;

          // Invalidate html variable, as assigned table to be updated via request callbacks!
          html = null;
          build_assigned_row_html_async('contacts', auto_update, selected_id, inc_default_members, callback);
        }
      }

      // If we have a valid html structure, then append to table listing
      if (html) {
        $('#ml_main_col_assign_users_teams_table').find('tbody:last').append(html);
      }
    }

    // Toggle management button states accordingly based on assigned table shape!
    toggle_assigned_user_links_manage_but_states();

    // Execute specified callback, unless told otherwise!
    if (execute_callback) {
      callback();
    }
  }

  function already_has_users_teams(id) {
    let hits = $('#ml_main_col_assign_users_teams_table').find('tbody > tr').filter(function (idx) {
      return id === $(this).find('#ml_main_col_assign_users_teams_table_row_id').val();
    });

    return (hits && hits.size() > 0);
  }

  function fetch_assigned_record_row(id) {
    let hits = $('#ml_main_col_assign_users_teams_table').find('tbody > tr').filter(function (idx) {
      return id === $(this).find('#ml_main_col_assign_users_teams_table_row_id').val();
    });

    return (hits && hits.size() > 0) ? hits : null;
  }

  function build_user_row_html(auto_update, id, record = null) {

    // If record is not provided, then attempt to fetch.
    if (!record) {
      record = fetch_users_teams_record(id);
    }

    if (record) {
      let sys_type = 'wp_user';
      return build_row_html(auto_update, id, id.split('+')[1], 'User', record['name'], sys_type, 'user', build_comms_html(record, 'phone'), build_comms_html(record, 'email'), extract_link_parts(record['links'], sys_type));
    }
    return null;
  }

  function build_contact_row_html(auto_update, id) {
    let post = fetch_users_teams_record(id);
    if (post) {
      let sys_type = 'post';
      return build_row_html(auto_update, id, id.split('+')[1], 'Contact', post['name'], sys_type, post['post_type'], build_comms_html(post, 'contact_phone'), build_comms_html(post, 'contact_email'), extract_link_parts(post['ml_links'], sys_type));
    }
    return null;
  }

  function build_assigned_row_html_async(post_type, auto_update, id, inc_default_members, callback) {
    let post_id = id.split('+')[1];
    get_post_record_request(post_type, post_id, function (post) {
      if (post) {
        switch (post_type) {
          case 'contacts': {
            if (post['ID']) {
              let sys_type = 'post';
              let async_html = build_row_html(auto_update, id, post_id, 'Contact', post['name'], sys_type, post['post_type'], build_comms_html(post, 'contact_phone'), build_comms_html(post, 'contact_email'), extract_link_parts(post['ml_links'], sys_type));

              // If we have a valid html structure, then append to table listing
              if (async_html) {
                $('#ml_main_col_assign_users_teams_table').find('tbody:last').append(async_html);
              }
            }
            break;
          }
          case 'dt_users':
          case 'dt_teams':
          case 'dt_groups': {

            // Unpack from array wrapping.
            post = post[0];

            // Build corresponding html.
            let async_html = null;
            if ( post_type === 'dt_users' ) {
              async_html = build_user_row_html(auto_update, id, post);

            } else if ( post_type === 'dt_teams' ) {
              async_html = build_team_group_row_html(auto_update, id, inc_default_members, 'Team', post);

            } else if ( post_type === 'dt_groups' ) {
              async_html = build_team_group_row_html(auto_update, id, inc_default_members, 'Group', post);
            }

            // If we have a valid html structure, then append to table listing
            if (async_html) {
              $('#ml_main_col_assign_users_teams_table').find('tbody:last').append(async_html);
            }
            break;
          }
        }
      }

      // Toggle management button states accordingly based on assigned table shape!
      toggle_assigned_user_links_manage_but_states();

      // Execute specified callback
      callback();
    });
  }

  function build_team_group_row_html(auto_update, id, inc_default_members, type, record = null) {
    let html = null;

    // If record is not provided, then attempt to fetch.
    if (!record) {
      record = fetch_users_teams_record(id);
    }

    if (record) {
      let tokens = id.split('+');
      let is_parent = tokens.length === 2;

      if (is_parent) {

        let dt_id = tokens[1];
        html = build_row_html(auto_update, id, dt_id, type, record['name'], 'post', 'groups', '---', '---', '---');

        // Capture team members accordingly, based on flags!
        if (inc_default_members && record['members'] && record['members'].length > 0) {

          // Remove duplicate record members.
          let members = Array.from( new Set( record['members'].map( JSON.stringify ) ) ).map( JSON.parse );

          // Sort record members.
          members = members.sort((a, b) => {
            if (a?.post_title.toLowerCase() < b?.post_title.toLowerCase()) {
              return -1;
            }

            if (a?.post_title.toLowerCase() > b?.post_title.toLowerCase()) {
              return 1;
            }

            return 0;
          });

          // Proceed with member html row build.
          members.forEach(function (member, idx) {
            html += build_row_html(auto_update, id + "+" + member['type_id'], member['type_id'], 'Member', member['post_title'], member['type'], member['post_type'], build_comms_html(member, 'phone'), build_comms_html(member, 'email'), extract_link_parts(member['links'], member['type']));
          });
        }

      } else { // Single member addition only! Usually resulting from a link object load!

        let member = fetch_member_record(record['members'], tokens[2]);
        if (member) {
          html = build_row_html(auto_update, id, member['type_id'], 'Member', member['post_title'], member['type'], member['post_type'], build_comms_html(member, 'phone'), build_comms_html(member, 'email'), extract_link_parts(member['links'], member['type']));
        }
      }
    }
    return html;
  }

  function fetch_member_record(members, id) {
    let record = null;
    if (members) {
      members.forEach(function (member, idx) {
        if (String(member['type_id']) === String(id)) {
          record = member;
        }
      });
    }
    return record;
  }

  function build_comms_html(record, key) {
    let values = [];
    if (record[key] && record[key].length > 0) {
      $.each(record[key], function (idx, val) {

        // Determine val shape so as to extract actual value accordingly
        if ((key === 'contact_phone') || (key === 'contact_email')) {
          val = val['value'];
        }

        // Format val accordingly, based on determined content
        if ((key === 'phone') || (key === 'contact_phone')) {
          values.push(!is_phone_format_valid(val) ? '<span style="color:red">' + val + '</span>' : val);
        } else if ((key === 'email') || (key === 'contact_email')) {
          values.push(!is_email_format_valid(val) ? '<span style="color:red">' + val + '</span>' : val);
        }
      });
    }

    return (values.length > 0) ? values.join('; ') : '---';
  }

  function is_phone_format_valid(phone) {
    return new RegExp('^\\+[1-9]\\d{1,14}$').test(window.lodash.escape(phone));
  }

  function is_email_format_valid(email) {
    return new RegExp('^[\u0600-\u06FFA-Za-z0-9._%+-]+@[\u0600-\u06FFA-Za-z0-9.-]+\\.[A-Za-z]{2,6}$').test(window.lodash.escape(email));
  }

  function extract_link_parts(links, sys_type) {

    // Ensure the correct link is used for given link object and selected magic link type.
    let link_obj_id = $('#ml_main_col_link_objs_manage_id').val();
    let ml_type = $('#ml_main_col_link_objs_manage_type').val();
    let link_key = ml_type + '_' + link_obj_id;

    // Determine link html.
    let link_html = '---';
    if (($(links).length > 0) && links && links[link_key] && links[link_key]['url']) {
      let link = links[link_key]['url'];
      if (link && $.trim(link).length > 0) {
        link_html = `<a class="button" href="${append_magic_link_params(link, sys_type)}" target="_blank">View</a>`;
      }
    }

    // Extract expiry parts.
    let expiry_parts = {
      ts: '',
      ts_formatted: '---',
      ts_base: ''
    };
    if (($(links).length > 0) && links && links[link_key] && links[link_key]['expires']) {
      let expires = links[link_key]['expires'];
      expiry_parts['ts'] = expires['ts'];
      expiry_parts['ts_formatted'] = expires['ts_formatted'];
      expiry_parts['ts_base'] = expires['ts_base'];
    }

    return {
      'html': link_html,
      'expires': expiry_parts
    };
  }

  function append_magic_link_params(link, sys_type) {
    let link_obj = fetch_link_obj($('#ml_main_col_available_link_objs_select').val());
    if (link_obj) {
      link += '?id=' + link_obj['id'] + '&type=' + sys_type;
    }

    return link;
  }

  function build_row_html(auto_update, id, dt_id, type, name, sys_type, post_type, phone, email, link) {
    if (id && dt_id && type && name && phone && email) {
      let html = `<tr>
                  <td style="vertical-align: middle;">
                  <input id="ml_main_col_assign_users_teams_table_row_id" type="hidden" value="${window.lodash.escape(id)}"/>
                  <input id="ml_main_col_assign_users_teams_table_row_dt_id" type="hidden" value="${window.lodash.escape(dt_id)}"/>
                  <input id="ml_main_col_assign_users_teams_table_row_type" type="hidden" value="${window.lodash.escape(String(type).trim().toLowerCase())}"/>
                  <input id="ml_main_col_assign_users_teams_table_row_name" type="hidden" value="${window.lodash.escape(name)}"/>
                  <input id="ml_main_col_assign_users_teams_table_row_sys_type" type="hidden" value="${window.lodash.escape(sys_type)}"/>
                  <input id="ml_main_col_assign_users_teams_table_row_post_type" type="hidden" value="${window.lodash.escape(post_type)}"/>
                  ${window.lodash.escape(type)}
                  </td>
                  <td style="vertical-align: middle;">${window.lodash.escape(name)}</td>
                  <td style="vertical-align: middle;">${phone}</td>
                  <td style="vertical-align: middle;">${email}</td>
                  <td style="vertical-align: middle;" id="ml_main_col_assign_users_teams_table_row_td_link">${(link['html']) ? link['html']:'---'}</td>
                  <td style="vertical-align: middle;" id="ml_main_col_assign_users_teams_table_row_td_link_expires">
                    <input id="ml_main_col_assign_users_teams_table_row_td_link_expires_base_ts" type="hidden" value="${(link['expires'] && link['expires']['ts_base']) ? link['expires']['ts_base']:''}"/>
                    <input id="ml_main_col_assign_users_teams_table_row_td_link_expires_on_ts" type="hidden" value="${(link['expires'] && link['expires']['ts']) ? link['expires']['ts']:''}"/>
                    <span id="ml_main_col_assign_users_teams_table_row_td_link_expires_on_ts_formatted">${(link['expires'] && link['expires']['ts_formatted']) ? link['expires']['ts_formatted']:'---'}</span>
                  </td>
                  <td style="vertical-align: middle;">
                    <span style="float:right;">
                        <select class="ml-main-col-assign-users-teams-table-row-options">
                            <option value="" selected>...</option>
                            ${((String(type).trim().toLowerCase()!=='group') && (String(type).trim().toLowerCase()!=='team')) ? '<option value="view">View</option>':''}
                            <option value="remove">Remove</option>
                            ${((String(type).trim().toLowerCase()!=='group') && (String(type).trim().toLowerCase()!=='team')) ? '<option value="extend">Extend</option>':''}
                            ${((String(type).trim().toLowerCase()==='group') || (String(type).trim().toLowerCase()==='team')) ? '<option value="refresh">Refresh</option>':''}
                        </select>
                    </span>
                  </td>
                </tr>`;

      /**
       * In required, Inform backend of new record, for additional async processing...
       */
      if (auto_update) {
        handle_assigned_general_management('add', {
          'id': id,
          'dt_id': dt_id,
          'type': type,
          'name': name,
          'sys_type': sys_type,
          'post_type': post_type,
          'links_expire_within_base_ts': '',
          'links_expire_on_ts': '',
          'links_expire_on_ts_formatted': ''
        });
      }

      // Return freshly generated html
      return html;
    }
    return null;
  }

  function fetch_users_teams_record(id) {
    let is_user = id.startsWith('users+');
    let is_team = id.startsWith('teams+');
    let is_group = id.startsWith('groups+');
    let is_contact = id.startsWith('contacts+');

    let typeahead_hidden = $('#ml_main_col_assign_users_teams_typeahead_hidden');
    if ( $(typeahead_hidden).val() && ( is_user || is_team || is_group || is_contact ) ) {
      return JSON.parse($(typeahead_hidden).val());
    }

    return null;
  }

  function handle_assigned_users_teams_table_row_options(evt) {

    // Obtain various handles.
    let row = evt.currentTarget.parentNode.parentNode.parentNode;
    let options = $(evt.currentTarget);

    // Determine suitable option action to be taken.
    switch (options.val()) {
      case 'view': {

        // Attempt to get a handle onto existing link view button.
        let link = $(row).find('#ml_main_col_assign_users_teams_table_row_td_link a[href != ""]');
        if (link && $(link).attr('href')) {
          window.open($(link).attr('href'), '_blank');
        }
        break;
      }
      case 'remove': {
        let name = $(row).find('#ml_main_col_assign_users_teams_table_row_name').val();
        if (confirm('Are you sure you wish to remove ' + name + '?')) {
          handle_remove_users_teams_request(row, function () {
          });
        }
        break;
      }
      case 'extend': {
        let dialog = $('#ml_main_col_assign_users_teams_table_dialog');

        // Fetch existing timestamps.
        let link_expires = $(row).find('#ml_main_col_assign_users_teams_table_row_td_link_expires');
        let link_expires_base_ts = $(link_expires).find('#ml_main_col_assign_users_teams_table_row_td_link_expires_base_ts');
        let link_expires_on_ts = $(link_expires).find('#ml_main_col_assign_users_teams_table_row_td_link_expires_on_ts');
        let link_expires_on_ts_formatted = $(link_expires).find('#ml_main_col_assign_users_teams_table_row_td_link_expires_on_ts_formatted');

        // Generate dialog html.
        let html = `
            <span>Select a new expiration date to be assigned to magic link.</span><br><br>
            <input style="min-width: 100%;" type="text" id="ml_main_col_assign_users_teams_table_dialog_extend_date" value=""/>
            <input type="hidden" id="ml_main_col_assign_users_teams_table_dialog_extend_date_ts" value=""/>`;

        // Update dialog div
        $(dialog).empty().append(html);

        // Refresh dialog config
        dialog.dialog({
          modal: true,
          autoOpen: false,
          hide: 'fade',
          show: 'fade',
          height: 300,
          width: 450,
          resizable: true,
          title: 'Extend Link Expiration Date',
          buttons: [
            {
              text: 'Cancel',
              icon: 'ui-icon-close',
              click: function () {
                $(this).dialog('close');
              }
            },
            {
              text: 'Extend',
              icon: 'ui-icon-copy',
              click: function () {

                // Revise updated expiration timestamps.
                let extend_date_ts = $('#ml_main_col_assign_users_teams_table_dialog_extend_date_ts').val();
                if (extend_date_ts) {

                  // First, adjust respective timestamps.
                  let adjusted_timestamps = adjust_assigned_users_links_expire_timestamps(moment().unix(), extend_date_ts, '---');
                  $(link_expires_base_ts).val(adjusted_timestamps['base_ts']);
                  $(link_expires_on_ts).val(adjusted_timestamps['expires_on_ts']);
                  $(link_expires_on_ts_formatted).html(adjusted_timestamps['expires_on_ts_formatted']);
                }
                $(this).dialog('close');
              }
            }
          ],
          open: function (event, ui) {

            // Activate expiration extension date widget.
            $('#ml_main_col_assign_users_teams_table_dialog_extend_date').daterangepicker({
              singleDatePicker: true,
              timePicker: true,
              startDate: (link_expires_on_ts && link_expires_on_ts.val()) ? moment.unix(link_expires_on_ts.val()) : moment(),
              locale: {
                format: 'YYYY-MM-DD hh:mm A'
              }
            }, function (start, end, label) {
              // As we are in single date picker mode, just focus on start date and convert to epoch timestamp.
              if (start) {
                $('#ml_main_col_assign_users_teams_table_dialog_extend_date_ts').val(start.unix());
              }
            });
          }
        });

        // Display updated dialog
        dialog.dialog('open');

        break;

      }
      case 'refresh': {

        // Ensure we are dealing with either a group or team row type.
        let type = $(row).find('#ml_main_col_assign_users_teams_table_row_type').val();
        if (type==='group' || type==='team') {
          let name = $(row).find('#ml_main_col_assign_users_teams_table_row_name').val();
          if (confirm('Are you sure you wish to refresh ' + name + '?')) {

            // First, remove all group members.....
            handle_remove_users_teams_request(row, function () {

              // ....then, re-assign; detecting recently removed/added members.
              let id = $(row).find('#ml_main_col_assign_users_teams_table_row_id').val();
              handle_add_users_teams_request(true, id, true, false, function () {

                // Not forgetting to re-sort refreshed table entries.
                sort_assign_users_teams_table();

              });
            });
          }

        } else {
          alert('Unable to detect parent Group or Team row!');
        }
        break;
      }

    }

    // Reset options select element
    options.val('');
  }

  function handle_remove_users_teams_request(row, callback) {
    let removed_rows = [];

    // Fetch required row values and remove accordingly, based on type
    let type = String($(row).find('#ml_main_col_assign_users_teams_table_row_type').val()).trim().toLowerCase();
    if (type === 'user' || type === 'member' || type === 'contact') {
      row.parentNode.removeChild(row);
      removed_rows.push(row);

    } else {

      // Team/Group level removal - also delete associated members; which should start with the same id as parent
      let id = $(row).find('#ml_main_col_assign_users_teams_table_row_id').val();
      let hits = $('#ml_main_col_assign_users_teams_table').find('tbody > tr').filter(function (idx) {
        return $(this).find('#ml_main_col_assign_users_teams_table_row_id').val().startsWith(id);
      });

      // We should have at least one hit, the actual parent team row...
      if (hits && hits.size() > 0) {
        hits.each(function (idx, hit) {
          hit.parentNode.removeChild(hit);
          removed_rows.push(hit);
        });

      } else {

        // ...remove parent team row; which triggered removal event!
        row.parentNode.removeChild(row);
        removed_rows.push(row);
      }

    }

    /**
     * In required, Inform backend of record removals, for additional async processing...
     */
    if (removed_rows.length > 0) {
      removed_rows.forEach(function (removed_row, idx) {
        let id = $(removed_row).find('#ml_main_col_assign_users_teams_table_row_id').val();
        let dt_id = $(removed_row).find('#ml_main_col_assign_users_teams_table_row_dt_id').val();
        let type = $(removed_row).find('#ml_main_col_assign_users_teams_table_row_type').val();
        let name = $(removed_row).find('#ml_main_col_assign_users_teams_table_row_name').val();
        let sys_type = $(removed_row).find('#ml_main_col_assign_users_teams_table_row_sys_type').val();
        let post_type = $(removed_row).find('#ml_main_col_assign_users_teams_table_row_post_type').val();
        let links_expire_within_base_ts = $(removed_row).find('#ml_main_col_assign_users_teams_table_row_td_link_expires_base_ts').val();
        let links_expire_on_ts = $(removed_row).find('#ml_main_col_assign_users_teams_table_row_td_link_expires_on_ts').val();
        let links_expire_on_ts_formatted = $(removed_row).find('#ml_main_col_assign_users_teams_table_row_td_link_expires_on_ts_formatted').html();

        handle_assigned_general_management('delete', {
          'id': id,
          'dt_id': dt_id,
          'type': type,
          'name': name,
          'sys_type': sys_type,
          'post_type': post_type,
          'links_expire_within_base_ts': links_expire_within_base_ts,
          'links_expire_on_ts': links_expire_on_ts,
          'links_expire_on_ts_formatted': links_expire_on_ts_formatted
        });
      });
    }

    // Toggle management button states accordingly based on assigned table shape!
    toggle_assigned_user_links_manage_but_states();

    callback();
  }

  function handle_update_request() {
    // Fetch link object values to be saved
    let id = $('#ml_main_col_link_objs_manage_id').val();
    let enabled = $('#ml_main_col_link_objs_manage_enabled').prop('checked');
    let name = $('#ml_main_col_link_objs_manage_name').val();
    let expires = $('#ml_main_col_link_objs_manage_expires_ts').val();
    let never_expires = $('#ml_main_col_link_objs_manage_expires_never').prop('checked');
    let type = $('#ml_main_col_link_objs_manage_type').val();

    let type_config = fetch_magic_link_type_config_updates();
    let type_fields = fetch_magic_link_type_field_updates();

    let assigned_users_teams = fetch_assigned_users_teams();

    let message_subject = $('#ml_main_col_msg_textarea_subject').val().trim();
    let template_message_id = $('#ml_main_col_msg_template_messages').val();
    let message = $('#ml_main_col_msg_textarea').val().trim();

    let scheduling_enabled = $('#ml_main_col_schedules_enabled').prop('checked');
    let freq_amount = $('#ml_main_col_schedules_frequency_amount').val();
    let freq_time_unit = $('#ml_main_col_schedules_frequency_time_unit').val();
    let sending_channel = $('#ml_main_col_schedules_sending_channels').val();
    let links_expire_within_amount = $('#ml_main_col_link_manage_links_expire_amount').val();
    let links_expire_within_time_unit = $('#ml_main_col_link_manage_links_expire_time_unit').val();
    let links_never_expires = $('#ml_main_col_link_manage_links_expire_never').prop('checked');
    let links_refreshed_before_send = $('#ml_main_col_schedules_links_refreshed_before_send').prop('checked');
    let links_expire_auto_refresh_enabled = $('#ml_main_col_link_manage_links_expire_auto_refresh_enabled').prop('checked');
    let last_schedule_run = $('#ml_main_col_schedules_last_schedule_run').val();
    let last_success_send = $('#ml_main_col_schedules_last_success_send').val();

    // If scheduling is coming out of a disabled state, ensure last scheduled run is reset to now.
    let old_link_obj = fetch_link_obj(id);
    if (old_link_obj && scheduling_enabled && !old_link_obj['schedule']['enabled']) {
      last_schedule_run = moment().unix();
    }

    // Validate values, to ensure all is present and correct within that department! ;)
    let update_msg = null;
    let update_msg_ele = $('#ml_main_col_update_msg');
    update_msg_ele.fadeOut('fast');

    if (!id) {
      update_msg = 'Unable to locate a valid link object id..!';
    } else if (!name) {
      update_msg = 'Please specify a valid link object name.';
    } else if (!never_expires && !expires) {
      update_msg = 'Please specify a valid object expiration date.';
    } else if (!type) {
      update_msg = 'Please specify a valid magic link type.';
    } else if (!message) {
      update_msg = 'Please specify a valid message, with optional {{}} placeholders.';
    } else if (scheduling_enabled && (!freq_amount || !freq_time_unit)) {
      update_msg = 'Please specify a valid scheduling frequency.';
    } else if (scheduling_enabled && !sending_channel) {
      update_msg = 'Please specify a valid sending channel.';
    } else if (scheduling_enabled && !links_never_expires && (!links_expire_within_amount || !links_expire_within_time_unit)) {
      update_msg = 'Please specify a valid links expire within setting.';
    }

    // Pause update, if errors have been detected
    if (update_msg) {

      update_msg_ele.fadeOut('fast', function () {
        update_msg_ele.html(update_msg);
        update_msg_ele.fadeIn('fast');
      });

    } else {

      // Proceed with packaging values into json structure, ready for saving
      let link_obj = {
        'version': 1,
        'id': id,
        'enabled': enabled,
        'name': name,
        'expires': expires,
        'never_expires': never_expires,
        'type': type,

        'type_config': type_config,
        'type_fields': type_fields,

        'assigned': assigned_users_teams,

        'link_manage': {
          'links_expire_within_amount': links_expire_within_amount,
          'links_expire_within_time_unit': links_expire_within_time_unit,
          'links_never_expires': links_never_expires,
          'links_expire_auto_refresh_enabled': links_expire_auto_refresh_enabled
        },

        'message_subject': message_subject,
        'template_message_id': template_message_id,
        'message': message,

        'schedule': {
          'enabled': scheduling_enabled,
          'freq_amount': freq_amount,
          'freq_time_unit': freq_time_unit,
          'sending_channel': sending_channel,
          'last_schedule_run': last_schedule_run,
          'last_success_send': last_success_send,
          'links_refreshed_before_send': links_refreshed_before_send
        }
      };
      $('#ml_main_col_update_form_link_obj').val(JSON.stringify(link_obj));

      // Submit link object package for saving
      $('#ml_main_col_update_form').submit();
    }
  }

  function fetch_magic_link_type_config_updates() {
    let type_config = {};
    $('#ml_main_col_ml_type_config_table').find('tbody > tr').each(function (idx, tr) {
      let id = $(tr).find('#ml_main_col_ml_type_config_table_row_field_id').val();
      let enabled = $(tr).find('#ml_main_col_ml_type_config_table_row_field_enabled').prop('checked');

      type_config[id] = enabled;
    });

    return type_config;
  }

  function fetch_magic_link_type_field_updates() {
    let type_fields = [];
    $('#ml_main_col_ml_type_fields_table').find('tbody > tr').each(function (idx, tr) {
      let id = $(tr).find('#ml_main_col_ml_type_fields_table_row_field_id').val();
      let type = $(tr).find('#ml_main_col_ml_type_fields_table_row_field_type').val();
      let enabled = $(tr).find('#ml_main_col_ml_type_fields_table_row_field_enabled').prop('checked');

      type_fields.push({
        'id': id,
        'type': type,
        'enabled': enabled
      });
    });

    return type_fields;
  }

  function fetch_assigned_users_teams() {
    let assigned = [];
    $('#ml_main_col_assign_users_teams_table').find('tbody > tr').each(function (idx, tr) {
      let id = $(tr).find('#ml_main_col_assign_users_teams_table_row_id').val();
      let dt_id = $(tr).find('#ml_main_col_assign_users_teams_table_row_dt_id').val();
      let type = $(tr).find('#ml_main_col_assign_users_teams_table_row_type').val();
      let name = $(tr).find('#ml_main_col_assign_users_teams_table_row_name').val();
      let sys_type = $(tr).find('#ml_main_col_assign_users_teams_table_row_sys_type').val();
      let post_type = $(tr).find('#ml_main_col_assign_users_teams_table_row_post_type').val();
      let link_expires_base_ts = $(tr).find('#ml_main_col_assign_users_teams_table_row_td_link_expires_base_ts').val();
      let link_expires_on_ts = $(tr).find('#ml_main_col_assign_users_teams_table_row_td_link_expires_on_ts').val();
      let link_expires_on_ts_formatted = $(tr).find('#ml_main_col_assign_users_teams_table_row_td_link_expires_on_ts_formatted').html();

      // Ensure to adjust respective timestamps.
      let adjusted_timestamps = adjust_assigned_users_links_expire_timestamps(link_expires_base_ts, link_expires_on_ts, link_expires_on_ts_formatted);

      assigned.push({
        'id': id,
        'dt_id': dt_id,
        'type': type,
        'name': name,
        'sys_type': sys_type,
        'post_type': post_type,
        'links_expire_within_base_ts': adjusted_timestamps['base_ts'],
        'links_expire_on_ts': adjusted_timestamps['expires_on_ts'],
        'links_expire_on_ts_formatted': adjusted_timestamps['expires_on_ts_formatted']
      });
    });

    return assigned;
  }

  function adjust_assigned_users_links_expire_timestamps(link_expires_base_ts, link_expires_on_ts, link_expires_on_ts_formatted) {
    let adjusted_timestamps = {
      'base_ts': link_expires_base_ts,
      'expires_on_ts': link_expires_on_ts,
      'expires_on_ts_formatted': link_expires_on_ts_formatted
    };

    // Adjust link expire timestamps accordingly, based on existing expire_within settings.
    let links_expire_within_amount = $('#ml_main_col_link_manage_links_expire_amount').val();
    let links_expire_within_time_unit = $('#ml_main_col_link_manage_links_expire_time_unit').val();
    if (links_expire_within_amount && links_expire_within_time_unit && link_expires_on_ts) {
      adjusted_timestamps['base_ts'] = moment.unix(link_expires_on_ts).subtract(links_expire_within_amount, links_expire_within_time_unit).unix();
      adjusted_timestamps['expires_on_ts'] = parseInt(link_expires_on_ts);
      adjusted_timestamps['expires_on_ts_formatted'] = moment.unix(link_expires_on_ts).format('MMMM DD, YYYY hh:mm:ss A');
    }

    return adjusted_timestamps;
  }

  function handle_load_link_obj_request() {
    let link_obj = fetch_link_obj($('#ml_main_col_available_link_objs_select').val());
    if (link_obj) {

      reset_section(true, $('#ml_main_col_link_objs_manage'), function () {
        reset_section_link_objs_manage(link_obj['id'], link_obj['enabled'], link_obj['name'], link_obj['expires'], link_obj['never_expires'], link_obj['type']);
      });

      reset_section(true, $('#ml_main_col_ml_type_fields'), function () {
        reset_section_ml_type_fields();
        display_magic_link_type_fields();
      });

      reset_section(true, $('#ml_main_col_assign_users_teams'), function () {
        reset_section_assign_users_teams(link_obj['assigned'], function () {
        });
      });

      reset_section(true, $('#ml_main_col_link_manage'), function () {
        if (link_obj['link_manage']) {
          reset_section_link_manage(link_obj['link_manage']['links_expire_within_amount'], link_obj['link_manage']['links_expire_within_time_unit'], link_obj['link_manage']['links_never_expires'], link_obj['link_manage']['links_expire_auto_refresh_enabled']);
        }
      });

      reset_section(true, $('#ml_main_col_message'), function () {
        reset_section_message( link_obj['message_subject'], link_obj['message'], link_obj['template_message_id'] ? link_obj['template_message_id'] : '' );
      });

      reset_section(true, $('#ml_main_col_schedules'), function () {
        if (link_obj['schedule']) {
          reset_section_schedules(link_obj['schedule']['enabled'], link_obj['schedule']['freq_amount'], link_obj['schedule']['freq_time_unit'], link_obj['schedule']['sending_channel'], link_obj['schedule']['last_schedule_run'], link_obj['schedule']['last_success_send'], link_obj['schedule']['links_refreshed_before_send'], true);
          handle_next_scheduled_run_request();
        }
      });

      $('#ml_main_col_update_msg').html('').fadeOut('fast');
      $('#ml_main_col_delete_but').fadeIn('fast');
    }
  }

  function fetch_link_obj(id) {
    let link_obj = null;

    $.each(window.dt_magic_links.dt_magic_link_objects, function (idx, obj) {
      if (String(idx).trim() === String(id).trim()) {
        link_obj = obj;
      }
    });

    return link_obj;
  }

  function handle_send_now_request() {

    // First, disable button during a send now request and reset message.
    $('#ml_main_col_schedules_send_now_but').prop('disabled', true);
    $('#ml_main_col_update_msg').html('').fadeOut('fast');

    // Create request payload
    let payload = {
      assigned: fetch_assigned_users_teams(),
      link_obj_id: $('#ml_main_col_link_objs_manage_id').val(),
      links_expire_within_amount: $('#ml_main_col_link_manage_links_expire_amount').val(),
      links_expire_within_time_unit: $('#ml_main_col_link_manage_links_expire_time_unit').val(),
      links_never_expires: $('#ml_main_col_link_manage_links_expire_never').prop('checked'),
      links_refreshed_before_send: $('#ml_main_col_schedules_links_refreshed_before_send').prop('checked'),
      links_expire_auto_refresh_enabled: $('#ml_main_col_link_manage_links_expire_auto_refresh_enabled').prop('checked'),
      message_subject: $('#ml_main_col_msg_textarea_subject').val(),
      message: $('#ml_main_col_msg_textarea').val()
    };

    // Dispatch send now request.
    $.ajax({
      url: window.dt_magic_links.dt_endpoint_send_now,
      method: 'POST',
      data: payload,
      beforeSend: (xhr) => {
        xhr.setRequestHeader("X-WP-Nonce", window.dt_admin_scripts.nonce);
      },
      success: function (data) {

        // Refresh assigned table so as to display updated user link states!
        if (data['assigned']) {
          reset_section_assign_users_teams(data['assigned'], function () {
          });
        }

        // Enable send now button, on response and display payload message
        $('#ml_main_col_schedules_send_now_but').prop('disabled', false);
        $('#ml_main_col_update_msg').html(data['message']).fadeIn('fast');
      },
      error: function (data) {
        console.log(data);
        $('#ml_main_col_schedules_send_now_but').prop('disabled', false);
        $('#ml_main_col_update_msg').html('Server error, please see logging tab for more details.').fadeIn('fast');

      }
    });
  }

  function handle_next_scheduled_run_request() {

    // Create request payload
    let payload = {
      link_obj_id: $('#ml_main_col_link_objs_manage_id').val()
    };

    // Dispatch next scheduled run request.
    $.ajax({
      url: window.dt_magic_links.dt_endpoint_next_scheduled_run,
      method: 'POST',
      data: payload,
      beforeSend: (xhr) => {
        xhr.setRequestHeader("X-WP-Nonce", window.dt_admin_scripts.nonce);
      },
      success: function (data) {

        // If needed, update date range picker to the projected next scheduled run.
        if (data && data['success'] && data['next_run_ts']) {
          $('#ml_main_col_schedules_next_schedule_run_date_picker').data('daterangepicker').setStartDate(moment.unix(data['next_run_ts']));
        }

        // Update relative time accordingly.
        $('#ml_main_col_schedules_next_schedule_run_relative_time').html((data && data['success'] && data['next_run_relative']) ? '<br>' + window.lodash.escape(data['next_run_relative']):'');

      },
      error: function (data) {
        console.log(data);
      }
    });

  }

  function handle_docs_request(title_div, content_div) {
    $('#ml_links_right_docs_section').fadeOut('fast', function () {
      $('#ml_links_right_docs_title').html($('#' + title_div).html());
      $('#ml_links_right_docs_content').html($('#' + content_div).html());

      $('#ml_links_right_docs_section').fadeIn('fast');
    });
  }

  function handle_assigned_user_links_management(action, callback) {

    /**
     * Base following logic on the current state of things; which may not
     * have been saved as yet! Therefore, try to stay ahead of the game! ;-)
     */

    let payload = {
      action: action,
      assigned: fetch_assigned_users_teams(),
      link_obj_id: $('#ml_main_col_link_objs_manage_id').val(),
      magic_link_type: $('#ml_main_col_link_objs_manage_type').val(),
      links_expire_within_amount: $('#ml_main_col_link_manage_links_expire_amount').val(),
      links_expire_within_time_unit: $('#ml_main_col_link_manage_links_expire_time_unit').val(),
      links_never_expires: $('#ml_main_col_link_manage_links_expire_never').prop('checked')
    };

    // Disable management buttons, so as to avoid multiple clicks!
    $('#ml_main_col_link_manage_links_but_refresh').prop('disabled', true);
    $('#ml_main_col_link_manage_links_but_delete').prop('disabled', true);
    $('#ml_main_col_update_msg').html('').fadeOut('fast');

    // Dispatch links management request
    $.ajax({
      url: window.dt_magic_links.dt_endpoint_user_links_manage,
      method: 'POST',
      data: payload,
      beforeSend: (xhr) => {
        xhr.setRequestHeader("X-WP-Nonce", window.dt_admin_scripts.nonce);
      },
      success: function (data) {
        if (data && data['success']) {

          // Refresh assigned table, to display updated user link states!
          reset_section_assign_users_teams(data['assigned'], function () {
            // Automatically save updates....
            handle_update_request();
          });

          // Friendly, reassuring message that all is still well in the world....!
          $('#ml_main_col_update_msg').html(data['message']).fadeIn('fast');

        } else {
          console.log(data);
          $('#ml_main_col_link_manage_links_but_refresh').prop('disabled', false);
          $('#ml_main_col_link_manage_links_but_delete').prop('disabled', false);
          $('#ml_main_col_update_msg').html('Server error, please see browser console for more details.').fadeIn('fast');
        }

        // Execute callback
        callback();
      },
      error: function (data) {
        console.log(data);
        $('#ml_main_col_link_manage_links_but_refresh').prop('disabled', false);
        $('#ml_main_col_link_manage_links_but_delete').prop('disabled', false);
        $('#ml_main_col_update_msg').html('Server error, please see browser console for more details.').fadeIn('fast');
      }
    });

  }

  function handle_assigned_general_management(action, record) {

    let payload = {
      action: action,
      record: record,
      link_obj_id: $('#ml_main_col_link_objs_manage_id').val(),
      magic_link_type: $('#ml_main_col_link_objs_manage_type').val(),
      links_expire_within_amount: $('#ml_main_col_link_manage_links_expire_amount').val(),
      links_expire_within_time_unit: $('#ml_main_col_link_manage_links_expire_time_unit').val(),
      links_never_expires: $('#ml_main_col_link_manage_links_expire_never').prop('checked')
    };

    $.ajax({
      url: window.dt_magic_links.dt_endpoint_assigned_manage,
      method: 'POST',
      data: payload,
      beforeSend: (xhr) => {
        xhr.setRequestHeader("X-WP-Nonce", window.dt_admin_scripts.nonce);
      },
      success: function (data) {
        if (data && data['success']) {

          // Update record accordingly, if a refreshed magic link has been returned!
          if (data['record'] && data['ml_links']) {
            refresh_assigned_record_link(data['record'], data['ml_links']);
          }

        } else {
          console.log(data);
        }

      },
      error: function (data) {
        console.log(data);
      }
    });
  }

  function refresh_assigned_record_link(record, links) {
    if (record && links) {
      let hit = fetch_assigned_record_row(record['id']);

      // Should only expect to have a single hit...!
      if (hit && hit.size() > 0) {
        $(hit[0]).find('#ml_main_col_assign_users_teams_table_row_td_link').html(extract_link_parts(links, record['sys_type'])['html']);

        // Retrospectively update link expiration details
        if (record['links_expire_within_base_ts'] && record['links_expire_on_ts'] && record['links_expire_on_ts_formatted']) {
          $(hit[0]).find('#ml_main_col_assign_users_teams_table_row_td_link_expires_base_ts').val(record['links_expire_within_base_ts']);
          $(hit[0]).find('#ml_main_col_assign_users_teams_table_row_td_link_expires_on_ts').val(record['links_expire_on_ts']);
          $(hit[0]).find('#ml_main_col_assign_users_teams_table_row_td_link_expires_on_ts_formatted').html(record['links_expire_on_ts_formatted']);
        }
      }
    }
  }

  function handle_enable_connection_fields_config_selection(config_checkbox, fields = []) {
    let enabled = $(config_checkbox).prop('checked');
    $('#ml_main_col_ml_type_fields_table').find('tbody > tr').each(function (idx, tr) {
      if ($(tr).find('#ml_main_col_ml_type_fields_table_row_field_type').val() === 'connection') {

        // Determine fields checked state.
        let checked = enabled;
        let field_id = $(tr).find('#ml_main_col_ml_type_fields_table_row_field_id').val();
        let field = fields.find(x => x.id === field_id);
        if (field) {
          checked = enabled && field.enabled;
        }

        // Adjust connection field enabled states accordingly
        $(tr).find('#ml_main_col_ml_type_fields_table_row_field_enabled').prop('checked', checked);
        $(tr).find('#ml_main_col_ml_type_fields_table_row_field_enabled').prop('disabled', !enabled);
      }
    });
  }

});
