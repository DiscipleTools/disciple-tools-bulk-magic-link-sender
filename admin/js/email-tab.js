jQuery(function ($) {

  // Event Listeners
  $(document).ready(function () {
    let email_obj = window.dt_magic_links.dt_magic_link_default_email_obj;
    if (email_obj) {
      handle_field_updates(JSON.parse(email_obj));
    }
  });

  $(document).on('click', '.ml-email-docs', function (evt) {
    handle_docs_request($(evt.currentTarget).data('title'), $(evt.currentTarget).data('content'));
  });

  $(document).on('click', '#ml_email_main_col_config_use_default_server', function () {
    toggle_use_default_server_states();
  });

  $(document).on('click', '#ml_email_main_col_config_server_usr_show', function () {
    show_secrets($('#ml_email_main_col_config_server_usr'), $('#ml_email_main_col_config_server_usr_show'));
  });

  $(document).on('click', '#ml_email_main_col_config_server_pwd_show', function () {
    show_secrets($('#ml_email_main_col_config_server_pwd'), $('#ml_email_main_col_config_server_pwd_show'));
  });

  $(document).on('click', '#ml_email_main_col_update_but', function () {
    handle_update_request();
  });

  // Helper Functions
  function handle_field_updates(email_obj) {
    if (email_obj) {
      $('#ml_email_main_col_config_enabled').prop('checked', email_obj['enabled']);
      $('#ml_email_main_col_config_use_default_server').prop('checked', email_obj['use_default_server']);
      $('#ml_email_main_col_config_server_addr').val(email_obj['server_addr']);
      $('#ml_email_main_col_config_server_port').val(email_obj['server_port']);
      $('#ml_email_main_col_config_encrypt').val(email_obj['encrypt_type']);
      $('#ml_email_main_col_config_auth_enabled').prop('checked', email_obj['auth_enabled']);
      $('#ml_email_main_col_config_server_usr').val(email_obj['username']);
      $('#ml_email_main_col_config_server_pwd').val(email_obj['password']);
      $('#ml_email_main_col_config_from_email').val(email_obj['from_email']);
      $('#ml_email_main_col_config_from_name').val(email_obj['from_name']);
      $('#ml_email_main_col_config_email_field').val(email_obj['email_field']);
      $('#ml_email_main_col_msg_subject').val(email_obj['subject']);
      $('#ml_email_main_col_msg_textarea').val(email_obj['message']);
    }

    // Adjust element states accordingly
    toggle_use_default_server_states();
  }

  function handle_docs_request(title_div, content_div) {
    $('#ml_email_right_docs_section').fadeOut('fast', function () {
      $('#ml_email_right_docs_title').html($('#' + title_div).html());
      $('#ml_email_right_docs_content').html($('#' + content_div).html());

      $('#ml_email_right_docs_section').fadeIn('fast');
    });
  }

  function toggle_use_default_server_states() {
    let using_default_server = $('#ml_email_main_col_config_use_default_server').is(':checked');

    $('#ml_email_main_col_config_server_addr').prop('disabled', using_default_server);
    $('#ml_email_main_col_config_server_port').prop('disabled', using_default_server);
    $('#ml_email_main_col_config_encrypt').prop('disabled', using_default_server);
    $('#ml_email_main_col_config_auth_enabled').prop('disabled', using_default_server);
    $('#ml_email_main_col_config_server_usr').prop('disabled', using_default_server);
    $('#ml_email_main_col_config_server_usr_show').prop('disabled', using_default_server);
    $('#ml_email_main_col_config_server_pwd').prop('disabled', using_default_server);
    $('#ml_email_main_col_config_server_pwd_show').prop('disabled', using_default_server);
    $('#ml_email_main_col_config_from_email').prop('disabled', using_default_server);
    $('#ml_email_main_col_config_from_name').prop('disabled', using_default_server);
  }

  function show_secrets(input_ele, show_ele) {
    input_ele.attr('type', show_ele.is(':checked') ? 'text' : 'password');
  }

  function handle_update_request() {

    // Fetch values to be saved
    let enabled = $('#ml_email_main_col_config_enabled').prop('checked');
    let use_default_server = $('#ml_email_main_col_config_use_default_server').prop('checked');
    let server_addr = $('#ml_email_main_col_config_server_addr').val().trim();
    let server_port = $('#ml_email_main_col_config_server_port').val();
    let encrypt = $('#ml_email_main_col_config_encrypt').val();
    let auth_enabled = $('#ml_email_main_col_config_auth_enabled').prop('checked');
    let usr = $('#ml_email_main_col_config_server_usr').val().trim();
    let pwd = $('#ml_email_main_col_config_server_pwd').val().trim();
    let from_email = $('#ml_email_main_col_config_from_email').val().trim();
    let from_name = $('#ml_email_main_col_config_from_name').val().trim();
    let email_field = $('#ml_email_main_col_config_email_field').val();
    let subject = $('#ml_email_main_col_msg_subject').val().trim();
    let message = $('#ml_email_main_col_msg_textarea').val().trim();

    // Validate submitted values
    let update_msg = null;
    let update_msg_ele = $('#ml_email_main_col_update_msg');
    update_msg_ele.fadeOut('fast');

    if (enabled) {

      // Sanity check based on use_default_server flag!
      if (!use_default_server) {
        if (!server_addr) {
          update_msg = 'Please specify a valid server address.';
        } else if (auth_enabled && !usr) {
          update_msg = 'Please specify a valid username.';
        } else if (auth_enabled && !pwd) {
          update_msg = 'Please specify a valid password.';
        } else if (!is_email_format_valid(from_email)) {
          update_msg = 'Please specify a valid from email.';
        } else if (!from_name) {
          update_msg = 'Please specify a valid from name.';
        }
      }

      if (!email_field) {
        update_msg = 'Please specify a valid email field.';
      } else if (!subject) {
        update_msg = 'Please specify a valid email message subject.';
      } else if (!message) {
        update_msg = 'Please specify a valid email message body, with optional {{}} placeholders.';
      }
    }

    // Pause update, if errors have been detected
    if (update_msg) {

      update_msg_ele.fadeOut('fast', function () {
        update_msg_ele.html(update_msg);
        update_msg_ele.fadeIn('fast');
      });

    } else {

      // Proceed with packaging values into json structure, ready for saving
      let email_obj = {
        'enabled': enabled,
        'use_default_server': use_default_server,
        'server_addr': server_addr,
        'server_port': server_port,
        'encrypt_type': encrypt,
        'auth_enabled': auth_enabled,
        'username': usr,
        'password': pwd,
        'from_email': from_email,
        'from_name': from_name,
        'email_field': email_field,
        'subject': subject,
        'message': message
      };
      $('#ml_email_main_col_config_form_email_obj').val(JSON.stringify(email_obj));

      // Submit email object package for saving
      $('#ml_email_main_col_config_form').submit();

    }
  }

  function is_email_format_valid(email) {
    return new RegExp('^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,6}$').test(window.lodash.escape(email));
  }
});
