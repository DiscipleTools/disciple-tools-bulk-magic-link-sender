jQuery(function ($) {

  // Event Listeners
  $(document).on('click', '#ml_general_main_col_general_update_but', function () {
    handle_update_request();
  });

  $(document).on('click', '.ml-general-docs', function (evt) {
    handle_docs_request($(evt.currentTarget).data('title'), $(evt.currentTarget).data('content'));
  });

  $(document).on('click', '#ml_general_main_col_general_global_name_enabled', function () {
    $('#ml_general_main_col_general_global_name').prop('disabled', !$(this).prop('checked'));
  });

  // Helper Functions
  function handle_update_request() {
    // Fetch values to be saved
    let global_name = $('#ml_general_main_col_general_global_name').val();
    let global_name_enabled = $('#ml_general_main_col_general_global_name_enabled').prop('checked');
    let all_scheduling_enabled = $('#ml_general_main_col_general_all_scheduling_enabled').prop('checked');
    let all_channels_enabled = $('#ml_general_main_col_general_all_channels_enabled').prop('checked');
    let default_time_zone = $('#ml_general_main_col_general_default_time_zone').val();

    // Update hidden form values
    $('#ml_general_main_col_general_form_global_name').val(global_name);
    $('#ml_general_main_col_general_form_global_name_enabled').val(global_name_enabled ? '1' : '0');
    $('#ml_general_main_col_general_form_all_scheduling_enabled').val(all_scheduling_enabled ? '1' : '0');
    $('#ml_general_main_col_general_form_all_channels_enabled').val(all_channels_enabled ? '1' : '0');
    $('#ml_general_main_col_general_form_default_time_zone').val(default_time_zone);

    // Submit form
    $('#ml_general_main_col_general_form').submit();
  }

  function handle_docs_request(title_div, content_div) {
    $('#ml_general_right_docs_section').fadeOut('fast', function () {
      $('#ml_general_right_docs_title').html($('#' + title_div).html());
      $('#ml_general_right_docs_content').html($('#' + content_div).html());

      $('#ml_general_right_docs_section').fadeIn('fast');
    });
  }

});
