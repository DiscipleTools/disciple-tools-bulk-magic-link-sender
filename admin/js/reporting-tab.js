jQuery(function ($) {

  $(document).on('change', '#ml_main_col_available_reports_select', function () {
    display_report();
  });

  // Helper Functions
  function display_report() {
    $('#ml_main_col_report_section').fadeOut('fast', function () {

      let id = $('#ml_main_col_available_reports_select').val();
      let name = $('#ml_main_col_available_reports_select option:selected').text();

      // Determine which report needs to be displayed
      switch (id) {
        case 'sent-vs-updated':
          display_report_sent_vs_updated(id, name);
          break;
      }
    });
  }

  function display_report_sent_vs_updated(id, name) {
    $.ajax({
      url: window.dt_magic_links.dt_endpoint_report,
      method: 'GET',
      data: {
        id: id
      },
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      success: function (data) {

        if (data['success']) {

          // Set report title
          $('#ml_main_col_report_section_title').html(name);

          // Add required elements to DOM
          $('#ml_main_col_report_section_display').html(`
                <div id="ml_main_col_report_section_display_canvas" style="min-width: 100%; min-height: 500px;"></div><hr>
                <div id="ml_main_col_report_section_display_details"></div>`);

          // Build report
          build_report_sent_vs_updated(data['report'], function () {
            $('#ml_main_col_report_section').fadeIn('fast');
          });
        }

      },
      error: function (data) {
        console.log(data['responseText']);
      }
    });
  }

  function build_report_sent_vs_updated(report, callback) {

    // Ensure overwritten charts are automatically disposed.
    am4core.options.autoDispose = true;

    am4core.ready(function () {

      am4core.useTheme(am4themes_animated);

      let chart = am4core.create('ml_main_col_report_section_display_canvas', am4charts.XYChart)
      chart.colors.step = 2;

      chart.legend = new am4charts.Legend()
      chart.legend.position = 'top'
      chart.legend.paddingBottom = 20
      chart.legend.labels.template.maxWidth = 95

      let xAxis = chart.xAxes.push(new am4charts.CategoryAxis())
      xAxis.dataFields.category = 'category'
      xAxis.renderer.cellStartLocation = 0.1
      xAxis.renderer.cellEndLocation = 0.9
      xAxis.renderer.grid.template.location = 0;

      let yAxis = chart.yAxes.push(new am4charts.ValueAxis());
      yAxis.min = 0;

      // Generate chart data
      let chart_data = [];
      $.each(report, function (idx, data) {
        chart_data.push({
          category: window.lodash.escape(data['name']),
          updated: data['total_updated_contacts'],
          outstanding: data['total_contacts'] - data['total_updated_contacts'],
        });
      });

      chart.data = chart_data;
      createSeries('updated', 'Updated');
      createSeries('outstanding', 'Outstanding');

      function createSeries(value, name) {

        let series = chart.series.push(new am4charts.ColumnSeries())
        series.dataFields.valueY = value
        series.dataFields.categoryX = 'category'
        series.name = name

        series.events.on("hidden", arrangeColumns);
        series.events.on("shown", arrangeColumns);

        let bullet = series.bullets.push(new am4charts.LabelBullet())
        bullet.interactionsEnabled = false
        bullet.dy = 30;
        bullet.label.text = '{valueY}'
        bullet.label.fill = am4core.color('#ffffff')

        return series;
      }

      function arrangeColumns() {

        let series = chart.series.getIndex(0);

        let w = 1 - xAxis.renderer.cellStartLocation - (1 - xAxis.renderer.cellEndLocation);
        if (series.dataItems.length > 1) {
          let x0 = xAxis.getX(series.dataItems.getIndex(0), "categoryX");
          let x1 = xAxis.getX(series.dataItems.getIndex(1), "categoryX");
          let delta = ((x1 - x0) / chart.series.length) * w;
          if (am4core.isNumber(delta)) {
            let middle = chart.series.length / 2;

            let newIndex = 0;
            chart.series.each(function (series) {
              if (!series.isHidden && !series.isHiding) {
                series.dummyData = newIndex;
                newIndex++;
              } else {
                series.dummyData = chart.series.indexOf(series);
              }
            });
            let visibleCount = newIndex;
            let newMiddle = visibleCount / 2;

            chart.series.each(function (series) {
              let trueIndex = chart.series.indexOf(series);
              let newIndex = series.dummyData;

              let dx = (newIndex - trueIndex + middle - newMiddle) * delta

              series.animate({property: "dx", to: dx}, series.interpolationDuration, series.interpolationEasing);
              series.bulletsContainer.animate({
                property: "dx",
                to: dx
              }, series.interpolationDuration, series.interpolationEasing);
            });
          }
        }
      }

      // Generate details table
      let html = `
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Name</th>
                <th>Total Contacts</th>
                <th>Last Successful Send</th>
            </tr>
            </thead>
            <tbody>`;

      $.each(report, function (idx, data) {
        html += `
          <tr>
            <td>${window.lodash.escape(data['name'])}</td>
            <td>${window.lodash.escape(data['total_contacts'])}</td>
            <td>${window.lodash.escape(moment.unix(data['last_success_send']).utc().format('MMMM Do YYYY, h:mm:ss a'))}</td>
          </tr>`;
      });

      html += `</tbody>
        </table>`;
      $('#ml_main_col_report_section_display_details').html(html);

      // Execute callback function
      callback();

    });
  }

});
