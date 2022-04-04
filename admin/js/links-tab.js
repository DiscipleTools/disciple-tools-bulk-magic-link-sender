jQuery(function ($) {

  // Initial States
  $(document).ready(function () {
    let link_obj = window.dt_magic_links.dt_previous_updated_link_obj;
    if (link_obj) {
      $('#ml_main_col_available_link_objs_select').val(link_obj['id']).trigger('change');
    }
  });

  // Event Listeners
  $(document).on('click', '#ml_main_col_available_link_objs_new', function () {
    handle_new_link_obj_request();
  });

  $(document).on('change', '#ml_main_col_link_objs_manage_type', function () {
    display_magic_link_type_fields();
  });

  $(document).on('click', '#ml_main_col_assign_users_teams_add', function () {
    handle_add_users_teams_request(true, determine_assignment_user_select_id(), true, false);
  });

  $(document).on('click', '.ml-main-col-assign-users-teams-table-row-remove-but', function (e) {
    handle_remove_users_teams_request(e);
  });

  $(document).on('click', '#ml_main_col_update_but', function () {
    handle_update_request();
  });

  $(document).on('click', '#ml_main_col_delete_but', function () {
    handle_delete_request();
  });

  $(document).on('click', '#ml_main_col_assign_users_teams_update_but', function () {
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
  $(document).on('click', '#ml_main_col_assign_users_teams_links_but_refresh', function () {
    handle_assigned_user_links_management('refresh', function () {
    });
  });
  $(document).on('click', '#ml_main_col_assign_users_teams_links_but_delete', function () {
    handle_assigned_user_links_management('delete', function () {
    });
  });


  // Helper Functions
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
      reset_section_assign_users_teams([]);
    });

    reset_section(display, $('#ml_main_col_message'), function () {
      let default_msg = window.dt_magic_links.dt_default_message;
      reset_section_message(default_msg);
    });

    reset_section(display, $('#ml_main_col_schedules'), function () {
      let default_send_channel_id = window.dt_magic_links.dt_default_send_channel_id;
      reset_section_schedules(false, '1', 'hours', default_send_channel_id, '3', 'days', false, moment().unix(), '', '', true, '', '', false);
    });

    $('#ml_main_col_update_msg').html('').fadeOut('fast');

    if (display) {
      $('#ml_main_col_update_but').fadeIn('fast');
      $('#ml_main_col_delete_but').fadeIn('fast');
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
        handle_add_users_teams_request(false, element['id'], false, true);
      });
    }

    // Toggle management button states accordingly based on assigned table shape!
    toggle_assigned_user_links_manage_but_states();
  }

  function reset_section_message(message) {
    $('#ml_main_col_msg_textarea').val(message);
  }

  function reset_section_schedules(enabled, freq_amount, freq_time_unit, sending_channel, links_amount, links_time_unit, links_never_expires, links_base_ts, links_expire_on_ts, links_expire_on_ts_formatted, links_auto_refresh, last_schedule_run, last_success_send, send_now) {
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
    $('#ml_main_col_schedules_links_expire_on_ts').val(links_expire_on_ts);
    $('#ml_main_col_schedules_links_expire_on_ts_formatted').val(links_expire_on_ts_formatted);
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

  function toggle_assigned_user_links_manage_but_states() {
    let assigned_count = $('#ml_main_col_assign_users_teams_table').find('tbody > tr').length;
    let enabled = (assigned_count > 0);

    $('#ml_main_col_assign_users_teams_links_but_refresh').prop('disabled', !enabled);
    $('#ml_main_col_assign_users_teams_links_but_delete').prop('disabled', !enabled);
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

  function build_magic_link_type_field_html(type_key, id, label) {
    return `<tr>
              <input id="ml_main_col_ml_type_fields_table_row_field_id" type="hidden" value="${id}">
              <td>${window.lodash.escape(label)}</td>
              <td><input id="ml_main_col_ml_type_fields_table_row_field_enabled" type="checkbox" ${is_magic_link_type_field_enabled(type_key, id, fetch_link_obj($('#ml_main_col_available_link_objs_select').val())) ? 'checked' : ''}></td>
            </tr>`;
  }

  function display_magic_link_type_fields() {
    let fields_table = $('#ml_main_col_ml_type_fields_table');
    let type_key = $('#ml_main_col_link_objs_manage_type').val();

    // REfresh fields table
    fields_table.fadeOut('fast', function () {
      fields_table.find('tbody > tr').remove();

      // Distinguish between regular magic link types and templates
      if (is_template(type_key)) {

        let template = fetch_magic_link_template(type_key);
        if (template) {

          // Ignore disabled fields
          $.each(template['fields'], function (idx, field) {
            if (field['enabled']) {
              fields_table.find('tbody:last').append(build_magic_link_type_field_html(type_key, field['id'], field['label']));
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
              fields_table.find('tbody:last').append(build_magic_link_type_field_html(type_key, field['id'], field['label']));
            }
          });

          // Adjust assigned selector accordingly, based on type object contacts flag
          adjust_assigned_selector_by_magic_link_type(type_obj['meta']['contacts_only']);
        }
      }

      // Display fields table
      fields_table.fadeIn('fast');

    });
  }

  function adjust_assigned_selector_by_magic_link_type(contacts_only) {
    let users_teams_select = $('#ml_main_col_assign_users_teams_select');
    let users_teams_typeahead_div = $('#ml_main_col_assign_users_teams_typeahead_div');

    /**
     * CONTACTS ONLY ASSIGNMENTS
     */

    if (contacts_only) {

      // Display contacts typeahead input field
      users_teams_select.fadeOut('fast', function () {
        users_teams_typeahead_div.fadeOut('fast', function () {

          // Build typeahead object
          let typeahead_obj = build_assign_users_teams_typeahead_obj();
          users_teams_typeahead_div.html(typeahead_obj['html']);

          // Instantiate typeahead configuration
          $('#ml_main_col_assign_users_teams_typeahead_input').typeahead({
            order: "asc",
            accent: true,
            minLength: 0,
            maxItem: 10,
            dynamic: true,
            searchOnFocus: true,
            source: typeahead_obj['typeahead']['endpoint'](window.dt_magic_links.dt_wp_nonce),
            callback: {
              onClick: function (node, a, item, event) {
                let id = typeahead_obj['typeahead']['id_func'](item);
                if (id) {

                  // Fetch associated post record...
                  get_post_record_request('contacts', id, function (post) {

                    // ...and stringify for future downstream processing
                    $('#ml_main_col_assign_users_teams_typeahead_hidden').val(JSON.stringify(post));
                  });
                }
              }
            }
          });

          // Display recently generated html
          users_teams_typeahead_div.fadeIn('fast');
        });
      });

    } else {

      /**
       * REGULAR USERS, TEAMS & GROUPS ASSIGNMENTS
       */

      // Revert back to default dropdown select
      users_teams_select.fadeOut('fast', function () {
        users_teams_typeahead_div.fadeOut('fast', function () {
          users_teams_select.fadeIn('fast');
        });
      });
    }
  }

  function build_assign_users_teams_typeahead_obj() {
    let base_url = window.dt_magic_links.dt_base_url;

    let response = {};
    response['id'] = Date.now();

    let html = '<div class="typeahead__container"><div class="typeahead__field"><div class="typeahead__query">';
    html += `<input type="hidden" id="ml_main_col_assign_users_teams_typeahead_hidden">`;
    html += `<input type="text" class="dt-typeahead" autocomplete="off" placeholder="Start typing contact details..." style="min-width: 90%;" id="ml_main_col_assign_users_teams_typeahead_input">`;
    html += '</div></div></div>';
    response['html'] = html;

    response['typeahead'] = {
      endpoint: function (wp_nonce) {
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
      id_func: function (item) {
        if (item && item['ID']) {
          return item['ID'];
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

  function is_regular_assignment_user_select_element() {
    return $('#ml_main_col_assign_users_teams_select').is(':visible') && $('#ml_main_col_assign_users_teams_typeahead_div').is(':hidden');
  }

  function determine_assignment_user_select_id() {
    if (is_regular_assignment_user_select_element()) {
      return $('#ml_main_col_assign_users_teams_select').val();

    } else {
      let typeahead_hidden = $('#ml_main_col_assign_users_teams_typeahead_hidden');
      if (typeahead_hidden.val()) {
        let post_obj = JSON.parse(typeahead_hidden.val());

        return (post_obj) ? 'contacts+' + $.trim(post_obj['ID']) : null;
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

  function handle_add_users_teams_request(auto_update, selected_id, inc_default_members, load_contact_details) {
    // Ensure selection is valid and table does not already contain selection...
    if (selected_id && !already_has_users_teams(selected_id)) {

      // Add new row accordingly, based on selection type
      let html = null;
      if (selected_id.startsWith('users+')) {
        html = build_user_row_html(auto_update, selected_id);
      } else if (selected_id.startsWith('teams+')) {
        html = build_team_group_row_html(auto_update, selected_id, inc_default_members, 'Team');
      } else if (selected_id.startsWith('groups+')) {
        html = build_team_group_row_html(auto_update, selected_id, inc_default_members, 'Group');
      } else if (selected_id.startsWith('contacts+')) {

        /**
         * If add request has been triggered following a manual click, then generate
         * html the good old fashion way; otherwise, we need to get creative and go async;
         * with the use of callbacks and the like! ;)
         */
        if (load_contact_details === false) {
          html = build_contact_row_html(auto_update, selected_id);

        } else {
          // Invalidate html variable, as assigned table to be updated via request callbacks!
          html = null;
          build_contact_row_html_async(auto_update, selected_id);
        }
      }

      // If we have a valid html structure, then append to table listing
      if (html) {
        $('#ml_main_col_assign_users_teams_table').find('tbody:last').append(html);
      }
    }

    // Toggle management button states accordingly based on assigned table shape!
    toggle_assigned_user_links_manage_but_states();
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

  function build_user_row_html(auto_update, id) {
    let record = fetch_users_teams_record(id);
    if (record) {
      let sys_type = 'wp_user';
      return build_row_html(auto_update, id, id.split('+')[1], 'User', record['name'], sys_type, 'user', build_comms_html(record, 'phone'), build_comms_html(record, 'email'), build_link_html(record['links'], sys_type));
    }
    return null;
  }

  function build_contact_row_html(auto_update, id) {
    let post = fetch_users_teams_record(id);
    if (post) {
      let sys_type = 'post';
      return build_row_html(auto_update, id, id.split('+')[1], 'Contact', post['name'], sys_type, post['post_type'], build_comms_html(post, 'contact_phone'), build_comms_html(post, 'contact_email'), build_link_html(post['ml_links'], sys_type));
    }
    return null;
  }

  function build_contact_row_html_async(auto_update, id) {
    let post_id = id.split('+')[1];
    get_post_record_request('contacts', post_id, function (post) {
      if (post && post['ID']) {
        let sys_type = 'post';
        let async_html = build_row_html(auto_update, id, post_id, 'Contact', post['name'], sys_type, post['post_type'], build_comms_html(post, 'contact_phone'), build_comms_html(post, 'contact_email'), build_link_html(post['ml_links'], sys_type));

        // If we have a valid html structure, then append to table listing
        if (async_html) {
          $('#ml_main_col_assign_users_teams_table').find('tbody:last').append(async_html);
        }
      }

      // Toggle management button states accordingly based on assigned table shape!
      toggle_assigned_user_links_manage_but_states();
    });
  }

  function build_team_group_row_html(auto_update, id, inc_default_members, type) {
    let record = fetch_users_teams_record(id);
    let html = null;
    if (record) {
      let tokens = id.split('+');
      let is_parent = tokens.length === 2;

      if (is_parent) {

        let dt_id = tokens[1];
        html = build_row_html(auto_update, id, dt_id, type, record['name'], 'post', 'groups', '---', '---', '---');

        // Capture team members accordingly, based on flags!
        if (inc_default_members && record['members'] && record['members'].length > 0) {
          record['members'].forEach(function (member, idx) {
            html += build_row_html(auto_update, id + "+" + member['type_id'], member['type_id'], 'Member', member['post_title'], member['type'], member['post_type'], build_comms_html(member, 'phone'), build_comms_html(member, 'email'), build_link_html(member['links'], member['type']));
          });
        }

      } else { // Single member addition only! Usually resulting from a link object load!

        let member = fetch_member_record(record['members'], tokens[2]);
        html = build_row_html(auto_update, id, member['type_id'], 'Member', member['post_title'], member['type'], member['post_type'], build_comms_html(member, 'phone'), build_comms_html(member, 'email'), build_link_html(member['links'], member['type']));

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
    return new RegExp('^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,6}$').test(window.lodash.escape(email));
  }

  function build_link_html(links, sys_type) {
    // Ensure the correct link is used for given link object and selected magic link type.
    let link_obj_id = $('#ml_main_col_link_objs_manage_id').val();
    let ml_type = $('#ml_main_col_link_objs_manage_type').val();

    if (links && links[ml_type + '_' + link_obj_id]) {
      let link = links[ml_type + '_' + link_obj_id];
      if (link && $.trim(link).length > 0) {
        return `<a class="button" href="${append_magic_link_params(link, sys_type)}" target="_blank">View</a>`;
      }
    }

    return '---';
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
                  <td style="vertical-align: middle;" id="ml_main_col_assign_users_teams_table_row_td_link">${link}</td>
                  <td style="vertical-align: middle;">
                    <span style="float:right;">
                        <button type="submit" class="button float-right ml-main-col-assign-users-teams-table-row-remove-but">Remove</button>
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
          'post_type': post_type
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
    let dt_id = id.split('+')[1]; // dt_id always 2nd element...!

    if (is_user) {
      return fetch_record(dt_id, window.dt_magic_links.dt_users, 'user_id');
    } else if (is_team) {
      return fetch_record(dt_id, window.dt_magic_links.dt_teams, 'id');
    } else if (is_group) {
      return fetch_record(dt_id, window.dt_magic_links.dt_groups, 'id');
    } else if (is_contact) {
      return JSON.parse($('#ml_main_col_assign_users_teams_typeahead_hidden').val());
    }

    return null;
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
    let removed_rows = [];

    // Obtain handle onto deleted row
    let row = evt.currentTarget.parentNode.parentNode.parentNode;

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

        handle_assigned_general_management('delete', {
          'id': id,
          'dt_id': dt_id,
          'type': type,
          'name': name,
          'sys_type': sys_type,
          'post_type': post_type
        });
      });
    }

    // Toggle management button states accordingly based on assigned table shape!
    toggle_assigned_user_links_manage_but_states();
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
    let links_expire_within_base_ts = $('#ml_main_col_schedules_links_expire_base_ts').val();
    let links_expire_on_ts = $('#ml_main_col_schedules_links_expire_on_ts').val();
    let links_expire_on_ts_formatted = $('#ml_main_col_schedules_links_expire_on_ts_formatted').val();
    let links_never_expires = $('#ml_main_col_schedules_links_expire_never').prop('checked');
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
          'links_expire_within_base_ts': links_expire_within_base_ts,
          'links_expire_on_ts': links_expire_on_ts,
          'links_expire_on_ts_formatted': links_expire_on_ts_formatted,
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
      let sys_type = $(tr).find('#ml_main_col_assign_users_teams_table_row_sys_type').val();
      let post_type = $(tr).find('#ml_main_col_assign_users_teams_table_row_post_type').val();

      assigned.push({
        'id': id,
        'dt_id': dt_id,
        'type': type,
        'name': name,
        'sys_type': sys_type,
        'post_type': post_type
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
        reset_section_schedules(link_obj['schedule']['enabled'], link_obj['schedule']['freq_amount'], link_obj['schedule']['freq_time_unit'], link_obj['schedule']['sending_channel'], link_obj['schedule']['links_expire_within_amount'], link_obj['schedule']['links_expire_within_time_unit'], link_obj['schedule']['links_never_expires'], link_obj['schedule']['links_expire_within_base_ts'], link_obj['schedule']['links_expire_on_ts'], link_obj['schedule']['links_expire_on_ts_formatted'], link_obj['schedule']['links_expire_auto_refresh_enabled'], link_obj['schedule']['last_schedule_run'], link_obj['schedule']['last_success_send'], true);
      });

      $('#ml_main_col_update_msg').html('').fadeOut('fast');
      $('#ml_main_col_update_but').fadeIn('fast');
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
      links_expire_within_base_ts: $('#ml_main_col_schedules_links_expire_base_ts').val(),
      links_expire_within_amount: $('#ml_main_col_schedules_links_expire_amount').val(),
      links_expire_within_time_unit: $('#ml_main_col_schedules_links_expire_time_unit').val(),
      links_never_expires: $('#ml_main_col_schedules_links_expire_never').prop('checked')
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
      links_expire_within_amount: $('#ml_main_col_schedules_links_expire_amount').val(),
      links_expire_within_time_unit: $('#ml_main_col_schedules_links_expire_time_unit').val(),
      links_never_expires: $('#ml_main_col_schedules_links_expire_never').prop('checked')
    };

    // Disable management buttons, so as to avoid multiple clicks!
    $('#ml_main_col_assign_users_teams_links_but_refresh').prop('disabled', true);
    $('#ml_main_col_assign_users_teams_links_but_delete').prop('disabled', true);
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

          // Update global variables accordingly
          if (data['dt_users'] && data['dt_users'].length > 0) {
            window.dt_magic_links.dt_users = data['dt_users'];
          }
          if (data['dt_teams'] && data['dt_teams'].length > 0) {
            window.dt_magic_links.dt_teams = data['dt_teams'];
          }
          if (data['dt_groups'] && data['dt_groups'].length > 0) {
            window.dt_magic_links.dt_groups = data['dt_groups'];
          }

          // Refresh assigned table so as to display updated user link states!
          reset_section_assign_users_teams(data['assigned']);

          // Ensure to update expiration settings, if action is of type refresh!
          if (action === 'refresh' && data['links_expire_within_base_ts'] && data['links_expire_on_ts'] && data['links_expire_on_ts_formatted']) {
            $('#ml_main_col_schedules_links_expire_base_ts').val(data['links_expire_within_base_ts']);
            $('#ml_main_col_schedules_links_expire_on_ts').val(data['links_expire_on_ts']);
            $('#ml_main_col_schedules_links_expire_on_ts_formatted').val(data['links_expire_on_ts_formatted']);
          }

          // Friendly, reassuring message that all is still well in the world....!
          $('#ml_main_col_update_msg').html(data['message']).fadeIn('fast');

        } else {
          console.log(data);
          $('#ml_main_col_assign_users_teams_links_but_refresh').prop('disabled', false);
          $('#ml_main_col_assign_users_teams_links_but_delete').prop('disabled', false);
          $('#ml_main_col_update_msg').html('Server error, please see browser console for more details.').fadeIn('fast');
        }

        // Execute callback
        callback();
      },
      error: function (data) {
        console.log(data);
        $('#ml_main_col_assign_users_teams_links_but_refresh').prop('disabled', false);
        $('#ml_main_col_assign_users_teams_links_but_delete').prop('disabled', false);
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
          if (data['ml_links']) {
            refresh_assigned_record_link(record, data['ml_links']);
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
        $(hit[0]).find('#ml_main_col_assign_users_teams_table_row_td_link').html(build_link_html(links, record['sys_type']));
      }
    }
  }


});
