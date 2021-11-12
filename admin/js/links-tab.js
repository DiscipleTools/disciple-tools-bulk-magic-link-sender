jQuery(function ($) {

  // Event Listeners
  $(document).on('click', '#ml_main_col_available_link_objs_new', function () {
    handle_new_link_obj_request();
  });

  $(document).on('change', '#ml_main_col_link_objs_manage_type', function () {
    display_magic_link_type_fields();
  });

  $(document).on('click', '#ml_main_col_assign_users_teams_add', function () {
    handle_add_users_teams_request($('#ml_main_col_assign_users_teams_select').val(), true);
  });

  $(document).on('click', '.ml-main-col-assign-users-teams-table-row-remove-but', function (e) {
    handle_remove_users_teams_request(e);
  });

  $(document).on('click', '#ml_main_col_update_but', function () {
    handle_update_request();
  });

  $(document).on('change', '#ml_main_col_available_link_objs_select', function () {
    handle_load_link_obj_request();
  });

  $(document).on('click', '#ml_main_col_link_objs_manage_expires_never', function () {
    toggle_never_expires_element_states(true);
  });

  $(document).on('click', '#ml_main_col_schedules_links_expire_never', function () {
    toggle_never_expires_element_states(false);
  });

  $(document).on('click', '#ml_main_col_schedules_send_now_but', function () {
    handle_send_now_request();
  });

  $(document).on('click', '.ml-links-docs', function (evt) {
    handle_docs_request($(evt.currentTarget).data('title'), $(evt.currentTarget).data('content'));
  });


  // Helper Functions
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
      reset_section_assign_users_teams([]);
    });

    reset_section(display, $('#ml_main_col_message'), function () {
      let default_msg = window.dt_magic_links.dt_default_message;
      reset_section_message(default_msg);
    });

    reset_section(display, $('#ml_main_col_schedules'), function () {
      let default_send_channel_id = window.dt_magic_links.dt_default_send_channel_id;
      reset_section_schedules(false, '1', 'hours', default_send_channel_id, '3', 'days', false, moment().unix(), true, '', '', false);
    });

    $('#ml_main_col_update_msg').html('').fadeOut('fast');

    if (display) {
      $('#ml_main_col_update_but').fadeIn('fast');
    }
  }

  function reset_section_available_link_objs() {
    $('#ml_main_col_available_link_objs_select').val('');
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

  function reset_section_ml_type_fields() {
    $('#ml_main_col_ml_type_fields_table').find('tbody tr').remove();
  }

  function reset_section_assign_users_teams(assigned_users_teams) {
    $('#ml_main_col_assign_users_teams_select').val('');
    $('#ml_main_col_assign_users_teams_table').find('tbody > tr').remove();

    if (assigned_users_teams && assigned_users_teams.length > 0) {
      assigned_users_teams.forEach(function (element, idx) {
        handle_add_users_teams_request(element['id'], false);
      });
    }
  }

  function reset_section_message(message) {
    $('#ml_main_col_msg_textarea').val(message);
  }

  function reset_section_schedules(enabled, freq_amount, freq_time_unit, sending_channel, links_amount, links_time_unit, links_never_expires, links_base_ts, links_auto_refresh, last_schedule_run, last_success_send, send_now) {
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

    $('#ml_main_col_schedules_links_expire_amount').val(links_amount);
    $('#ml_main_col_schedules_links_expire_time_unit').val(links_time_unit);
    $('#ml_main_col_schedules_links_expire_never').prop('checked', links_never_expires);
    $('#ml_main_col_schedules_links_expire_base_ts').val(links_base_ts);
    $('#ml_main_col_schedules_links_expire_auto_refresh_enabled').prop('checked', links_auto_refresh);

    toggle_never_expires_element_states(false);

    $('#ml_main_col_schedules_last_schedule_run').val(last_schedule_run);
    $('#ml_main_col_schedules_last_success_send').val(last_success_send);

    $('#ml_main_col_schedules_send_now_but').prop('disabled', !send_now);
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

  function toggle_never_expires_element_states(is_obj_level) {

    if (is_obj_level) { // Object Level
      let disabled = $('#ml_main_col_link_objs_manage_expires_never').prop('checked');
      $('#ml_main_col_link_objs_manage_expires').prop('disabled', disabled);

    } else { // Link Level
      let disabled = $('#ml_main_col_schedules_links_expire_never').prop('checked');
      $('#ml_main_col_schedules_links_expire_amount').prop('disabled', disabled);
      $('#ml_main_col_schedules_links_expire_time_unit').prop('disabled', disabled);
      $('#ml_main_col_schedules_links_expire_auto_refresh_enabled').prop('disabled', disabled);

    }

  }

  function display_magic_link_type_fields() {
    let fields_table = $('#ml_main_col_ml_type_fields_table');
    let type_key = $('#ml_main_col_link_objs_manage_type').val();

    if (type_key) {
      fields_table.fadeOut('fast', function () {

        // Clear down table records
        fields_table.find('tbody > tr').remove();

        // Refresh fields list accordingly
        let magic_link_types = window.dt_magic_links.dt_magic_link_types;
        if (magic_link_types) {
          magic_link_types.forEach(function (type, type_idx) {
            if (type['key'] === type_key) {
              type['meta']['fields'].forEach(function (field, field_idx) {
                if (field['id'] && field['label']) {
                  let html = `<tr>
                                <input id="ml_main_col_ml_type_fields_table_row_field_id" type="hidden" value="${field['id']}">
                                <td>${window.lodash.escape(field['label'])}</td>
                                <td><input id="ml_main_col_ml_type_fields_table_row_field_enabled" type="checkbox" ${is_magic_link_type_field_enabled(type_key, field['id'], fetch_link_obj($('#ml_main_col_available_link_objs_select').val())) ? 'checked' : ''}></td>
                              </tr>`;
                  fields_table.find('tbody:last').append(html);
                }
              });
            }
          });
        }

        // Display fields table
        fields_table.fadeIn('fast');
      });
    }
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

  function handle_add_users_teams_request(selected_users_teams_id, inc_default_team_members) {
    // Ensure selection is valid and table does not already contain selection...
    if (selected_users_teams_id && !already_has_users_teams(selected_users_teams_id)) {

      // Add new row accordingly, based on selection type
      let html = null;
      if (selected_users_teams_id.startsWith('users+')) {
        html = build_user_row_html(selected_users_teams_id);
      } else if (selected_users_teams_id.startsWith('teams+')) {
        html = build_team_row_html(selected_users_teams_id, inc_default_team_members);
      }

      // If we have a valid html structure, then append to table listing
      if (html) {
        $('#ml_main_col_assign_users_teams_table').find('tbody:last').append(html);
      }
    }
  }

  function already_has_users_teams(id) {
    let hits = $('#ml_main_col_assign_users_teams_table').find('tbody > tr').filter(function (idx) {
      return id === $(this).find('#ml_main_col_assign_users_teams_table_row_id').val();
    });

    return (hits && hits.size() > 0);
  }

  function build_user_row_html(id) {
    let record = fetch_users_teams_record(id);
    if (record) {
      return build_row_html(id, id.split('+')[1], 'User', record['name'], build_comms_html(record, 'phone'), build_comms_html(record, 'email'), build_link_html(record['link']));
    }
    return null;
  }

  function build_team_row_html(id, inc_default_team_members) {
    let record = fetch_users_teams_record(id);
    let html = null;
    if (record) {
      let tokens = id.split('+');
      let is_team = tokens.length === 2;

      if (is_team) {

        let dt_id = tokens[1];
        html = build_row_html(id, dt_id, 'Team', record['name'], '---', '---', '---');

        // Capture team members accordingly, based on flags!
        if (inc_default_team_members && record['members'] && record['members'].length > 0) {
          record['members'].forEach(function (member, idx) {
            html += build_row_html(id + "+" + member['user_id'], member['user_id'], 'Member', member['post_title'], build_comms_html(member, 'phone'), build_comms_html(member, 'email'), build_link_html(member['link']));
          });
        }

      } else { // Single member addition only! Usually resulting from a link object load!

        let member = fetch_team_member_record(record['members'], tokens[2]);
        html = build_row_html(id, member['user_id'], 'Member', member['post_title'], build_comms_html(member, 'phone'), build_comms_html(member, 'email'), build_link_html(member['link']));

      }
    }
    return html;
  }

  function fetch_team_member_record(members, id) {
    let record = null;
    if (members) {
      members.forEach(function (member, idx) {
        if (String(member['user_id']) === String(id)) {
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
        if (key === 'phone') {
          values.push(!is_phone_format_valid(val) ? '<span style="color:red">' + val + '</span>' : val);
        } else if (key === 'email') {
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
    return new RegExp('^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,6}$').test(window.lodash.escape(email));
  }

  function build_link_html(link) {
    if (link && $.trim(link).length > 0) {
      return `<a class="button" href="${append_magic_link_params(link)}" target="_blank">View</a>`;
    }

    return '---';
  }

  function append_magic_link_params(link) {
    let link_obj = fetch_link_obj($('#ml_main_col_available_link_objs_select').val());
    if (link_obj) {
      link += '?id=' + link_obj['id'];
    }

    return link;
  }

  function build_row_html(id, dt_id, type, name, phone, email, link) {
    if (id && dt_id && type && name && phone && email) {
      return `<tr>
                  <td style="vertical-align: middle;">
                  <input id="ml_main_col_assign_users_teams_table_row_id" type="hidden" value="${window.lodash.escape(id)}"/>
                  <input id="ml_main_col_assign_users_teams_table_row_dt_id" type="hidden" value="${window.lodash.escape(dt_id)}"/>
                  <input id="ml_main_col_assign_users_teams_table_row_type" type="hidden" value="${window.lodash.escape(String(type).trim().toLowerCase())}"/>
                  <input id="ml_main_col_assign_users_teams_table_row_name" type="hidden" value="${window.lodash.escape(name)}"/>
                  ${window.lodash.escape(type)}
                  </td>
                  <td style="vertical-align: middle;">${window.lodash.escape(name)}</td>
                  <td style="vertical-align: middle;">${phone}</td>
                  <td style="vertical-align: middle;">${email}</td>
                  <td style="vertical-align: middle;">${link}</td>
                  <td style="vertical-align: middle;">
                    <span style="float:right;">
                        <button type="submit" class="button float-right ml-main-col-assign-users-teams-table-row-remove-but">Remove</button>
                    </span>
                  </td>
                </tr>`;
    }
    return null;
  }

  function fetch_users_teams_record(id) {
    let is_user = id.startsWith('users+');
    let dt_id = id.split('+')[1]; // dt_id always 2nd element...!

    if (is_user) {
      return fetch_record(dt_id, window.dt_magic_links.dt_users, 'user_id');
    } else {
      return fetch_record(dt_id, window.dt_magic_links.dt_teams, 'id');
    }
  }

  function fetch_record(dt_id, array, key) {
    let record = null;
    if (array) {
      array.forEach(function (element, idx) {
        if (String(element[key]) === String(dt_id)) {
          record = element;
        }
      });
    }

    return record;
  }

  function handle_remove_users_teams_request(evt) {
    // Obtain handle onto deleted row
    let row = evt.currentTarget.parentNode.parentNode.parentNode;

    // Fetch required row values and remove accordingly, based on type
    let type = String($(row).find('#ml_main_col_assign_users_teams_table_row_type').val()).trim().toLowerCase();
    if (type === 'user' || type === 'member') {
      row.parentNode.removeChild(row);

    } else {

      // Team level removal - also delete associated members; which should start with the same id as team parent
      let id = $(row).find('#ml_main_col_assign_users_teams_table_row_id').val();
      let hits = $('#ml_main_col_assign_users_teams_table').find('tbody > tr').filter(function (idx) {
        return $(this).find('#ml_main_col_assign_users_teams_table_row_id').val().startsWith(id);
      });

      // We should have at least one hit, the actual parent team row...
      if (hits && hits.size() > 0) {
        hits.each(function (idx, hit) {
          hit.parentNode.removeChild(hit);
        });

      } else {

        // ...remove parent team row; which triggered removal event!
        row.parentNode.removeChild(row);
      }

    }

  }

  function handle_update_request() {
    // Fetch link object values to be saved
    let id = $('#ml_main_col_link_objs_manage_id').val();
    let enabled = $('#ml_main_col_link_objs_manage_enabled').prop('checked');
    let name = $('#ml_main_col_link_objs_manage_name').val();
    let expires = $('#ml_main_col_link_objs_manage_expires_ts').val();
    let never_expires = $('#ml_main_col_link_objs_manage_expires_never').prop('checked');
    let type = $('#ml_main_col_link_objs_manage_type').val();

    let type_fields = fetch_magic_link_type_field_updates();

    let assigned_users_teams = fetch_assigned_users_teams();

    let message = $('#ml_main_col_msg_textarea').val().trim();

    let scheduling_enabled = $('#ml_main_col_schedules_enabled').prop('checked');
    let freq_amount = $('#ml_main_col_schedules_frequency_amount').val();
    let freq_time_unit = $('#ml_main_col_schedules_frequency_time_unit').val();
    let sending_channel = $('#ml_main_col_schedules_sending_channels').val();
    let links_expire_within_amount = $('#ml_main_col_schedules_links_expire_amount').val();
    let links_expire_within_time_unit = $('#ml_main_col_schedules_links_expire_time_unit').val();
    let links_never_expires = $('#ml_main_col_schedules_links_expire_never').prop('checked');
    let links_expire_within_base_ts = $('#ml_main_col_schedules_links_expire_base_ts').val();
    let links_expire_auto_refresh_enabled = $('#ml_main_col_schedules_links_expire_auto_refresh_enabled').prop('checked');
    let last_schedule_run = $('#ml_main_col_schedules_last_schedule_run').val();
    let last_success_send = $('#ml_main_col_schedules_last_success_send').val();

    // Validate values so as to ensure all is present and correct within that department! ;)
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
        'id': id,
        'enabled': enabled,
        'name': name,
        'expires': expires,
        'never_expires': never_expires,
        'type': type,

        'type_fields': type_fields,

        'assigned': assigned_users_teams,

        'message': message,

        'schedule': {
          'enabled': scheduling_enabled,
          'freq_amount': freq_amount,
          'freq_time_unit': freq_time_unit,
          'sending_channel': sending_channel,
          'links_expire_within_amount': links_expire_within_amount,
          'links_expire_within_time_unit': links_expire_within_time_unit,
          'links_expire_within_base_ts': moment().unix(), // Always reset, so as to provide a sliding-forward starting point for elapsed time calculations
          'links_never_expires': links_never_expires,
          'links_expire_auto_refresh_enabled': links_expire_auto_refresh_enabled,
          'last_schedule_run': last_schedule_run,
          'last_success_send': last_success_send
        }
      };
      $('#ml_main_col_update_form_link_obj').val(JSON.stringify(link_obj));

      // Submit link object package for saving
      $('#ml_main_col_update_form').submit();
    }
  }

  function fetch_magic_link_type_field_updates() {
    let type_fields = [];
    $('#ml_main_col_ml_type_fields_table').find('tbody > tr').each(function (idx, tr) {
      let id = $(tr).find('#ml_main_col_ml_type_fields_table_row_field_id').val();
      let enabled = $(tr).find('#ml_main_col_ml_type_fields_table_row_field_enabled').prop('checked');

      type_fields.push({
        'id': id,
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

      assigned.push({
        'id': id,
        'dt_id': dt_id,
        'type': type,
        'name': name
      });
    });

    return assigned;
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
        reset_section_assign_users_teams(link_obj['assigned']);
      });

      reset_section(true, $('#ml_main_col_message'), function () {
        reset_section_message(link_obj['message']);
      });

      reset_section(true, $('#ml_main_col_schedules'), function () {
        reset_section_schedules(link_obj['schedule']['enabled'], link_obj['schedule']['freq_amount'], link_obj['schedule']['freq_time_unit'], link_obj['schedule']['sending_channel'], link_obj['schedule']['links_expire_within_amount'], link_obj['schedule']['links_expire_within_time_unit'], link_obj['schedule']['links_never_expires'], link_obj['schedule']['links_expire_within_base_ts'], link_obj['schedule']['links_expire_auto_refresh_enabled'], link_obj['schedule']['last_schedule_run'], link_obj['schedule']['last_success_send'], true);
      });

      $('#ml_main_col_update_msg').html('').fadeOut('fast');
      $('#ml_main_col_update_but').fadeIn('fast');
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

    // Dispatch send now request.
    $.ajax({
      url: window.dt_magic_links.dt_endpoint_send_now,
      method: 'POST',
      data: {
        link_obj_id: $('#ml_main_col_link_objs_manage_id').val()
      },
      success: function (data) {
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

  function handle_docs_request(title_div, content_div) {
    $('#ml_links_right_docs_section').fadeOut('fast', function () {
      $('#ml_links_right_docs_title').html($('#' + title_div).html());
      $('#ml_links_right_docs_content').html($('#' + content_div).html());

      $('#ml_links_right_docs_section').fadeIn('fast');
    });
  }


});
