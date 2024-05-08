jQuery(function ($) {

  /**
   * Initial States...
   */

  $(document).ready(function () {
    let template = window.dt_magic_links.dt_previous_updated_template;
    if (template) {

      let found_post_type = false;

      // Select corresponding post type
      $('#available_post_types_section_buttons_table').find('tbody tr td').each(function (idx, td) {
        let post_type_id = $(td).find('#available_post_types_section_post_type_id');
        if (post_type_id && (post_type_id.val() === template['post_type'])) {
          found_post_type = true;
          $(td).find('.available-post-types-section-buttons').trigger('click');
        }
      });

      // Only proceed if post type has been found!
      if (found_post_type) {
        $('#templates_management_section_table').find('tbody tr td').each(function (idx, td) {
          let template_id = $(td).find('#templates_management_section_table_template_id');
          if (template_id && (template_id.val() === template['id'])) {
            $(td).find('.templates-management-section-table-template-link').trigger('click');
          }
        });
      }
    }
  });

  /**
   * Event Listeners
   */

  $(document).on('click', '.available-post-types-section-buttons', function (evt) {
    handle_available_post_types_section_button_click(evt);
  });

  $(document).on('click', '#templates_management_section_new_but', function () {
    handle_new_template_request();
  });

  $(document).on('click', '.templates-management-section-table-template-link', function (evt) {
    handle_load_template_request($(evt.target).data('template_id'));
  });

  $(document).on('click', '#ml_main_col_template_details_fields_add', function () {
    let id = $('#ml_main_col_template_details_fields').val();
    let label = $('#ml_main_col_template_details_fields option:selected').text();

    handle_selected_field_addition(id, label, 'dt', true, {});
  });

  $(document).on('click', '#ml_main_col_template_details_custom_fields_add', function () {
    let id = moment().unix();
    let label = $('#ml_main_col_template_details_custom_fields').val();

    handle_selected_field_addition(id, label, 'custom', true, {});
  });

  $(document).on('click', '.connected-sortable-fields-remove-but', function (evt) {
    handle_selected_field_removal(evt);
  });

  $(document).on('click', '.connected-sortable-fields-translate-but', function (evt) {
    handle_selected_field_translation(evt);
  });

  $(document).on('click', '#ml_main_col_update_but', function () {
    handle_update_request();
  });

  $(document).on('click', '#ml_main_col_delete_but', function () {
    handle_delete_request();
  });

  $(document).on('click', '.ml-templates-docs', function (evt) {
    handle_docs_request($(evt.currentTarget).data('title'), $(evt.currentTarget).data('content'));
  });

  $(document).on('click', '.template-title-translate-but', function (evt) {
    handle_template_title_translation(evt);
  });

  /**
   * Helper Functions
   */

  function handle_docs_request(title_div, content_div) {
    $('#ml_templates_right_docs_section').fadeOut('fast', function () {
      $('#ml_templates_right_docs_title').html($('#' + title_div).html());
      $('#ml_templates_right_docs_content').html($('#' + content_div).html());

      $('#ml_templates_right_docs_section').fadeIn('fast');
    });
  }

  function handle_delete_request() {
    let template_post_type = $('#templates_management_section_selected_post_type').val();
    let template_id = $('#ml_main_col_template_details_id').val();
    let template_name = $('#ml_main_col_template_details_name').val();

    if (template_id && confirm(`Are you sure you wish to delete ${template_name}?`)) {
      $('#ml_main_col_delete_form_template_post_type').val(template_post_type);
      $('#ml_main_col_delete_form_template_id').val(template_id);
      $('#ml_main_col_delete_form').submit();
    }
  }

  function handle_available_post_types_section_button_click(evt) {

    // First, reset all buttons
    $('.available-post-types-section-buttons').each(function (idx, button) {
      $(button).removeClass('button-primary');
    });

    // Highlight selected button and display associated templates
    $(evt.currentTarget).addClass('button-primary');

    // Hide and reset various views back to default state
    template_views_update_and_delete_but(...Array(2), function () {
      template_views_message(...Array(3), function () {
        template_views_selected_fields(...Array(3), function () {
          template_views_template_details(...Array(3), function () {
            template_views_templates_management(...Array(3), function () {

              // Refresh post type fields list
              let post_types = window.dt_magic_links.dt_post_types;
              let post_type_id = $(evt.target).parent().find('#available_post_types_section_post_type_id').val();
              refresh_post_type_fields_list(post_types[post_type_id]['fields']);

              // Capture selected post type id for future reference
              $('#templates_management_section_selected_post_type').val(post_type_id);

              // Roll back to management view only, with corresponding post type templates
              template_views_templates_management(false, 'slow', {'post_type': post_type_id});

            });
          });
        });
      });
    });
  }

  function template_views_update_and_delete_but(fade_out = true, fade_speed = 'fast', callback = function () {
  }) {

    // Update Button
    let view_update_but = $('#ml_main_col_update_but');
    $('#ml_main_col_update_msg').fadeOut(fade_speed);
    fade_out ? view_update_but.fadeOut(fade_speed) : view_update_but.fadeIn(fade_speed);

    // Delete Button
    let view_delete_but = $('#ml_main_col_delete_but');
    fade_out ? view_delete_but.fadeOut(fade_speed) : view_delete_but.fadeIn(fade_speed);

    callback();
  }

  function template_views_message(fade_out = true, fade_speed = 'fast', data = {text: ''}, callback = function () {
  }) {
    let view_message = $('#ml_main_col_message');

    if (fade_out) {
      view_message.fadeOut(fade_speed, function () {
        $('#ml_main_col_msg_textarea').val(data.text);
        callback();
      });

    } else {
      $('#ml_main_col_msg_textarea').val(data.text);
      view_message.fadeIn(fade_speed, function () {
        callback();
      });
    }
  }

  function template_views_selected_fields(fade_out = true, fade_speed = 'fast', data = {fields: []}, callback = function () {
  }) {
    let view_selected_fields = $('#ml_main_col_selected_fields');

    if (fade_out) {
      view_selected_fields.fadeOut(fade_speed, function () {
        $(".connected-sortable-fields").empty();
        callback();
      });

    } else {

      // Reload previously selected fields
      $.each(data['fields'], function (idx, field) {
        $('.connected-sortable-fields').append(build_new_selected_field_html(field['id'], field['label'], field['type'], field['enabled'], (field['translations'] !== undefined) ? field['translations'] : {}, (field['custom_form_field_type'] !== undefined) ? field['custom_form_field_type'] : 'textfield', field['readonly']));
      });

      // Instantiate sortable fields capabilities
      $('.connected-sortable-fields').sortable({
        connectWith: '.connected-sortable-fields',
        placeholder: 'ui-state-highlight'
      }).disableSelection();

      view_selected_fields.fadeIn(fade_speed, function () {
        callback();
      });
    }
  }

  function template_views_template_details(fade_out = true, fade_speed = 'fast', data = {
    id: 'templates_' + moment().unix() + '_magic_key',
    enabled: true,
    name: '',
    title: '',
    title_translations: {},
    type: 'single-record',
    custom_fields: '',
    show_recent_comments: false,
    send_submission_notifications: true
  }, callback = function () {
  }) {

    // Adjust available type options based on selected post type.
    let type_option_adjustments = {
      'contacts': [
        {
          'value': 'single-record',
          'text': 'Single Record'
        },
        {
          'value': 'list-sub-assigned-contacts',
          'text': 'List Sub-Assigned Contacts'
        }
      ],
      'default-options': [
        {
          'value': 'single-record',
          'text': 'Single Record'
        }
      ]
    };

    let post_type = $('#templates_management_section_selected_post_type').val();
    let template_details_type_select = $('#ml_main_col_template_details_type');
    $(template_details_type_select).find('option').remove();

    let type_options = (post_type && type_option_adjustments[post_type]) ? type_option_adjustments[post_type] : type_option_adjustments['default-options'];
    $.each(type_options, function (idx, option) {
      $(template_details_type_select).append($('<option>', {
        value: option['value'],
        text: option['text']
      }));
    });

    // Proceed with updating template details accordingly.
    let view_template_details = $('#ml_main_col_template_details');
    if (fade_out) {
      view_template_details.fadeOut(fade_speed, function () {
        $('#ml_main_col_template_details_id').val(data.id);
        $('#ml_main_col_template_details_enabled').prop('checked', data.enabled);
        $('#ml_main_col_template_details_name').val(data.name);
        $('#ml_main_col_template_details_title').val(data.title);
        $('#ml_main_col_template_details_title_translate_but').data('field_translations', encodeURIComponent(JSON.stringify(data.title_translations)));
        $('#ml_main_col_template_details_type').val(data.type);
        $('.template-title-translate-but-label').text(Object.keys(data.title_translations).length);
        $('#ml_main_col_template_details_custom_fields').val(data.custom_fields);
        $('#ml_main_col_template_details_show_recent_comments').prop('checked', data.show_recent_comments);
        $('#ml_main_col_template_details_send_submission_notifications').prop('checked', data.send_submission_notifications);
        callback();
      });

    } else {
      $('#ml_main_col_template_details_id').val(data.id);
      $('#ml_main_col_template_details_enabled').prop('checked', data.enabled);
      $('#ml_main_col_template_details_name').val(data.name);
      $('#ml_main_col_template_details_title').val(data.title);
      $('#ml_main_col_template_details_title_translate_but').data('field_translations', encodeURIComponent(JSON.stringify(data.title_translations)));
      $('#ml_main_col_template_details_type').val(data.type);
      $('.template-title-translate-but-label').text(Object.keys(data.title_translations).length);
      $('#ml_main_col_template_details_custom_fields').val(data.custom_fields);
      $('#ml_main_col_template_details_show_recent_comments').prop('checked', data.show_recent_comments);
      $('#ml_main_col_template_details_send_submission_notifications').prop('checked', data.send_submission_notifications);
      view_template_details.fadeIn(fade_speed, function () {
        callback();
      });
    }
  }

  function template_views_templates_management(fade_out = true, fade_speed = 'fast', data = {}, callback = function () {
  }) {
    let view_templates_management = $('#ml_main_col_templates_management');
    $('#templates_management_section_table').find('tbody tr').remove();

    if (fade_out) {
      view_templates_management.fadeOut(fade_speed, function () {
        callback();
      });

    } else {
      // Populate management table accordingly with available/associated templates
      if (data.post_type && window.dt_magic_links.dt_magic_links_templates) {

        let templates = window.dt_magic_links.dt_magic_links_templates;
        if (templates[data.post_type]) {
          $.each(templates[data.post_type], function (id, template) {
            $('#templates_management_section_table').find('tbody').append(build_template_row_html(template));
          });
        }
      }
      view_templates_management.fadeIn(fade_speed, function () {
        callback();
      });
    }
  }

  function build_template_row_html(template) {
    return `
        <tr>
            <td style="max-width: 10px;">
                <input type="checkbox" disabled ${template['enabled'] ? 'checked' : ''}>
            </td>
            <td>
                <input type="hidden" id="templates_management_section_table_template_id" value="${window.lodash.escape(template['id'])}">
                <a href="#" class="templates-management-section-table-template-link" data-template_id="${window.lodash.escape(template['id'])}">${window.lodash.escape(template['name'])}</a>
            </td>
        </tr>
    `;
  }

  function refresh_post_type_fields_list(fields) {
    let fields_select = $('#ml_main_col_template_details_fields');

    // Empty existing list and insert initial select placeholder
    fields_select.empty();
    fields_select.append('<option disabled selected value>-- select field --</option>');

    // Iterate fields array and append corresponding options
    if (fields) {
      $.each(sort_by_field_name(fields), function (idx, field) {
        fields_select.append(`<option value="${field['id']}">${field['name']}</option>`);
      });
    }
  }

  function sort_by_field_name(fields) {
    return fields.sort(function (a, b) {
      let a_name = a.name.toLowerCase();
      let b_name = b.name.toLowerCase();
      return ((a_name < b_name) ? -1 : ((a_name > b_name) ? 1 : 0));
    });
  }

  function handle_new_template_request() {

    // Hide and reset main views accordingly
    template_views_update_and_delete_but(...Array(2), function () {
      template_views_message(...Array(3), function () {
        template_views_selected_fields(...Array(3), function () {
          template_views_template_details(...Array(3), function () {

            // Reset fields selection
            $('#ml_main_col_template_details_fields').val('');

            // Display refreshed views....
            template_views_template_details(false, 'slow');
            template_views_selected_fields(false, 'slow');
            template_views_message(false, 'slow');
            template_views_update_and_delete_but(false, 'slow');

          });
        });
      });
    });
  }

  function handle_load_template_request(template_id) {

    // Hide and reset main views accordingly - Ensure all resets have taken place prior to displaying loaded templete details!
    template_views_update_and_delete_but(...Array(2), function () {
      template_views_message(...Array(3), function () {
        template_views_selected_fields(...Array(3), function () {
          template_views_template_details(...Array(3), function () {

            // Reset fields selection
            $('#ml_main_col_template_details_fields').val('');

            // Fetch corresponding template details
            let post_type = $('#templates_management_section_selected_post_type').val();
            let template = fetch_template(post_type, template_id);
            if (template) {

              // Display refreshed views....
              template_views_template_details(false, 'slow', {
                id: template['id'],
                enabled: template['enabled'],
                name: template['name'],
                title: template['title'],
                title_translations: template['title_translations'] ?? {},
                type: template['type'] ?? 'single-record',
                custom_fields: '',
                show_recent_comments: template['show_recent_comments'],
                send_submission_notifications: template['send_submission_notifications'] ?? true
              });
              template_views_selected_fields(false, 'slow', {fields: template['fields']});
              template_views_message(false, 'slow', {text: template['message']});
              template_views_update_and_delete_but(false, 'slow');

            } else {
              let update_msg = 'Unable to locate template details..!';
              let update_msg_ele = $('#ml_main_col_update_msg');
              update_msg_ele.fadeOut('fast', function () {
                update_msg_ele.html(update_msg);
                update_msg_ele.fadeIn('fast');
              });
            }
          });
        });
      });
    });
  }

  function fetch_template(post_type, template_id) {
    let templates = window.dt_magic_links.dt_magic_links_templates;

    if (post_type && template_id && templates) {
      return templates[post_type][template_id];
    }

    return null;
  }

  function handle_selected_field_addition(field_id, field_label, field_type, field_enabled, field_translations) {
    if (field_id && field_label && !field_already_selected(field_id, field_label)) {
      $('.connected-sortable-fields').append(build_new_selected_field_html(field_id, field_label, field_type, field_enabled, field_translations));

      // Reset fields accordingly
      switch (field_type) {
        case 'dt' : {
          $('#ml_main_col_template_details_fields').val('');
          break;
        }
        case 'custom' : {
          $('#ml_main_col_template_details_custom_fields').val('');
          break;
        }
      }
    }
  }

  function handle_selected_field_removal(evt) {
    let field_div = $(evt.currentTarget).parent().parent().parent().parent().parent();
    field_div.remove();
  }

  function handle_selected_field_translation(evt) {

    // Obtain handle to translation button, to be used further downstream.
    let translate_but = $(evt.currentTarget);

    // Obtain handle to, config and display translations dialog.
    let dialog = $('#ml_main_col_selected_fields_sortable_field_dialog');
    dialog.dialog({
      modal: true,
      autoOpen: false,
      hide: 'fade',
      show: 'fade',
      height: 600,
      width: 350,
      resizable: false,
      title: translate_but.data('field_label') + ' Field Translation',
      buttons: {
        Update: function () {

          // Package list of available translations.
          let updated_translations = {};
          $('#ml_main_col_selected_fields_sortable_field_dialog_table').find('tbody tr input').each(function (idx, input) {

            // Only package populated translation field values.
            if ($(input).val()) {
              updated_translations[$(input).data('language')] = {
                language: $(input).data('language'),
                translation: $(input).val()
              };
            }
          });

          // Persist packaged translations.
          translate_but.data('field_translations', encodeURIComponent(JSON.stringify(updated_translations)));

          // Update button label's translation count.
          $(translate_but).find('.connected-sortable-fields-translate-but-label').text(Object.keys(updated_translations).length);

          // Close dialog.
          $(this).dialog('close');

          // Finally, auto save changes.
          handle_update_request();
        }
      }
    });

    // Clear-down and load existing field translations.
    let translations = JSON.parse(decodeURIComponent(translate_but.data('field_translations')));
    $('#ml_main_col_selected_fields_sortable_field_dialog_table').find('tbody tr input').each(function (idx, input) {
      $(input).val('');
      if (translations[$(input).data('language')]) {
        $(input).val($('<div>').html(translations[$(input).data('language')]['translation']).text());
      }
    });

    // Finally, display translation dialog
    dialog.dialog('open');
  }

  function handle_template_title_translation(evt) {

    // Obtain handle to translation button, to be used further downstream.
    let translate_but = $(evt.currentTarget);

    // Obtain handle to, config and display translations dialog.
    let dialog = $('#ml_main_col_selected_fields_sortable_field_dialog');
    dialog.dialog({
      modal: true,
      autoOpen: false,
      hide: 'fade',
      show: 'fade',
      height: 600,
      width: 350,
      resizable: false,
      title: 'Template Title Translation',
      buttons: {
        Update: function () {

          // Package list of available translations.
          let updated_translations = {};
          $('#ml_main_col_selected_fields_sortable_field_dialog_table').find('tbody tr input').each(function (idx, input) {

            // Only package populated translation field values.
            if ($(input).val()) {
              updated_translations[$(input).data('language')] = {
                language: $(input).data('language'),
                translation: $(input).val()
              };
            }
          });

          // Persist packaged translations.
          translate_but.data('field_translations', encodeURIComponent(JSON.stringify(updated_translations)));

          // Update button label's translation count.
          $(translate_but).find('.template-title-translate-but-label').text(Object.keys(updated_translations).length);

          // Close dialog.
          $(this).dialog('close');

          // Finally, auto save changes.
          handle_update_request();
        }
      }
    });

    // Clear-down and load existing field translations.
    let translations = JSON.parse(decodeURIComponent(translate_but.data('field_translations')));
    $('#ml_main_col_selected_fields_sortable_field_dialog_table').find('tbody tr input').each(function (idx, input) {
      $(input).val('');
      if (translations[$(input).data('language')]) {
        $(input).val($('<div>').html(translations[$(input).data('language')]['translation']).text());
      }
    });

    // Finally, display translation dialog
    dialog.dialog('open');
  }

  function field_already_selected(field_id, field_label) {
    let already_selected = false;

    $('.connected-sortable-fields').find('.ui-state-default').each(function (idx, field_div) {

      // Determine field's selected state based on type
      switch ($(field_div).find('#ml_main_col_selected_fields_sortable_field_type').val()) {
        case 'dt' : {
          if ($(field_div).find('#ml_main_col_selected_fields_sortable_field_id').val() === field_id) {
            already_selected = true;
          }
          break;
        }
        case 'custom' : {
          if ($(field_div).find('#ml_main_col_selected_fields_sortable_field_label').val() === field_label) {
            already_selected = true;
          }
          break;
        }
      }
    });

    return already_selected;
  }

  function build_new_selected_field_html(field_id, field_label, field_type, field_enabled, field_translations, field_custom_form_type = 'textfield', field_readonly = false) {

    // Ensure default field labels are disabled and cannot be overwritten, along with any other field type specific settings.
    let label_disabled_html = '';
    let final_form_custom_field_type_html = '';
    let field_readonly_html = '';
    switch (field_type) {
      case 'dt' : {
        label_disabled_html = 'disabled';
        field_readonly_html = `
            <select id="ml_main_col_selected_fields_sortable_field_readonly">
                <option value="" ${!field_readonly ? 'selected':''}>Editable</option>
                <option value="readonly" ${field_readonly ? 'selected':''}>Readonly</option>
            </select>
        `;
        break;
      }
      case 'custom' : {
        final_form_custom_field_type_html = `
            <select id="ml_main_col_selected_fields_sortable_form_custom_field_type">
                <option value="textfield" ${(field_custom_form_type === 'textfield') ? 'selected':''}>Textfield</option>
                <option value="textarea" ${(field_custom_form_type === 'textarea') ? 'selected':''}>Textarea</option>
            </select>
        `;
        break;
      }
    }

    return `
        <div class="ui-state-default" style="margin-bottom: 10px; background: #FFFFFF;">
            <input id="ml_main_col_selected_fields_sortable_field_id" type="hidden" value="${field_id}"/>
            <input id="ml_main_col_selected_fields_sortable_field_type" type="hidden" value="${field_type}"/>
            <table class="widefat striped">
                <tbody>
                <tr>
                    <td style="vertical-align: middle; text-align: center; max-width: 5%;">
                        <span class="ui-icon ui-icon-arrow-4"></span>
                    </td>
                    <td style="vertical-align: middle; text-align: center; max-width: 10%;">
                        <input id="ml_main_col_selected_fields_sortable_field_enabled" type="checkbox" ${field_enabled ? 'checked' : ''}/>
                    </td>
                    <td style="vertical-align: middle;">
                        <input id="ml_main_col_selected_fields_sortable_field_label" style="min-width: 100%;"
                               type="text" value="${field_label}" ${label_disabled_html}/>
                    </td>
                    <td style="text-align: right;">
                        ${field_readonly_html}
                        ${final_form_custom_field_type_html}
                        ${build_translation_button_html(field_id, field_label, field_type, field_translations, label_disabled_html)}
                        <button type="submit" class="button float-right connected-sortable-fields-remove-but">
                            Remove
                        </button>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    `;
  }

  function build_translation_button_html(field_id, field_label, field_type, field_translations, disabled_html) {
    return `
      <button type="submit"
      data-field_id="${field_id}"
      data-field_label="${field_label}"
      data-field_type="${field_type}"
      data-field_translations="${encodeURIComponent(JSON.stringify(field_translations))}"
      class="button float-right connected-sortable-fields-translate-but" ${disabled_html}>
          <img style="height: 15px; vertical-align: middle;" src="${window.lodash.escape(window.dt_magic_links.dt_languages_icon)}" />
          (<span class="connected-sortable-fields-translate-but-label">${Object.keys(field_translations).length}</span>)
      </button>
    `;
  }

  function handle_update_request() {

    // Fetch template object values to be saved
    let post_type = $('#templates_management_section_selected_post_type').val();
    let id = $('#ml_main_col_template_details_id').val();
    let enabled = $('#ml_main_col_template_details_enabled').prop('checked');
    let name = $('#ml_main_col_template_details_name').val();
    let title = $('#ml_main_col_template_details_title').val();
    let title_translations = JSON.parse(decodeURIComponent($('#ml_main_col_template_details_title_translate_but').data('field_translations')));
    let type = $('#ml_main_col_template_details_type').val();
    let show_recent_comments = $('#ml_main_col_template_details_show_recent_comments').prop('checked');
    let send_submission_notifications = $('#ml_main_col_template_details_send_submission_notifications').prop('checked');
    let message = $('#ml_main_col_msg_textarea').val();
    let fields = fetch_selected_fields();

    // Validate values, to ensure all is present and correct within that department! ;)
    let update_msg = null;
    let update_msg_ele = $('#ml_main_col_update_msg');
    update_msg_ele.fadeOut('fast');

    if (!post_type) {
      update_msg = 'Unable to locate a valid post type id..!';
    } else if (!id) {
      update_msg = 'Unable to locate a valid template id..!';
    } else if (!name) {
      update_msg = 'Please specify a valid template name.';
    }

    // Pause update, if errors have been detected
    if (update_msg) {

      update_msg_ele.fadeOut('fast', function () {
        update_msg_ele.html(update_msg);
        update_msg_ele.fadeIn('fast');
      });

    } else {

      // Proceed with packaging values into json structure, ready for saving
      let template_obj = {
        'id': id,
        'class_type': 'template', // TODO: Keep an eye on this one - Be sure to remove if not used!
        'url_base': '', // Populated further downstream by parent templates type class!
        'post_type': post_type,
        'enabled': enabled,
        'name': name,
        'title': title,
        'title_translations': title_translations,
        'type': type,
        'show_recent_comments': show_recent_comments,
        'send_submission_notifications': send_submission_notifications,
        'message': message,
        'fields': fields
      };
      $('#ml_main_col_update_form_template').val(JSON.stringify(template_obj));

      // Submit link object package for saving
      $('#ml_main_col_update_form').submit();

    }
  }

  function fetch_selected_fields() {

    let fields = [];
    $('.connected-sortable-fields').find('.ui-state-default').each(function (idx, field_div) {

      let id = $(field_div).find('#ml_main_col_selected_fields_sortable_field_id').val();
      let type = $(field_div).find('#ml_main_col_selected_fields_sortable_field_type').val();
      let enabled = $(field_div).find('#ml_main_col_selected_fields_sortable_field_enabled').prop('checked');
      let readonly = $(field_div).find('#ml_main_col_selected_fields_sortable_field_readonly').val() === 'readonly';
      let label = $(field_div).find('#ml_main_col_selected_fields_sortable_field_label').val();
      let translations = (type === 'dt') ? {} : JSON.parse(decodeURIComponent($(field_div).find('.connected-sortable-fields-translate-but').data('field_translations')));
      let custom_form_field_type = (type === 'custom') ? $(field_div).find('#ml_main_col_selected_fields_sortable_form_custom_field_type').val() : '';

      fields.push({
        'id': id,
        'type': type,
        'enabled': enabled,
        'readonly': readonly,
        'label': label,
        'translations': translations,
        'custom_form_field_type': custom_form_field_type
      });
    });

    return fields;
  }


});
