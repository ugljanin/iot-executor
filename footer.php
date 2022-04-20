              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- footer content -->
    <footer>
      <div class="pull-right">
        IoT Executor
      </div>
      <div class="clearfix"></div>
    </footer>
    <!-- /footer content -->
    </div>
    </div>
    <!-- Bootstrap -->
    <script src="/js/bootstrap.min.js"></script>
    <!-- FastClick -->
    <script src="/js/fastclick.js"></script>
    <!-- NProgress -->
    <script src="/js/nprogress.js"></script>

    <!-- Custom Theme Scripts -->
    <script src="/js/custom.min.js"></script>

    <script>
      $(document).ready(function() {
        $("#page").on('change', function(e) {

          var id = $(this).find("option:selected").data("id");
          var accesstoken = $(this).val();
          var name = $(this).find("option:selected").text();

          $.ajax({
            url: "configurepage.php",
            type: 'post',
            data: "action=change&id=" + id + "&accesstoken=" + accesstoken + "&name=" + name,
            beforeSend: function() {
              $('.alert-success').text("Waiting");
            },
            success: function(result) {
              $('.alert-success').text("Done");
              $('#selectedpage').text(name);

            }
          });

        });

      });
    </script>

    <script src="js/moment.js"></script>
    <script src="js/bootstrap-datetimepicker.js"></script>
    <script>
      $(function() {
        $('#datetimepicker3').datetimepicker({
          format: 'LT'
        });
        $('#datetimepicker4').datetimepicker({
          format: 'LT'
        });
        $('#datetimepicker8').datetimepicker().on('dp.change', function(event) {
          $('#form').submit();
        });
        $('#day').on('change', function(event) {
          $('#form').submit();
        });
        $('#hour').on('change', function(event) {
          $('#form').submit();
        });
        $('#minute').on('change', function(event) {
          $('#form').submit();
        });
        $('#datetimepicker8').datetimepicker();
        $('#datetimepicker1').datetimepicker();
        $('#datetimepicker2').datetimepicker({
          useCurrent: false //Important! See issue #1075
        });
        $("#datetimepicker1").on("dp.change", function(e) {
          $('#datetimepicker2').data("DateTimePicker").minDate(e.date);
        });
        $("#datetimepicker2").on("dp.change", function(e) {
          $('#datetimepicker1').data("DateTimePicker").maxDate(e.date);
        });
        $('#datetimepicker6').datetimepicker();
        $('#datetimepicker7').datetimepicker({
          useCurrent: false //Important! See issue #1075
        });
        $("#datetimepicker6").on("dp.change", function(e) {
          $('#datetimepicker7').data("DateTimePicker").minDate(e.date);
        });
        $("#datetimepicker7").on("dp.change", function(e) {
          $('#datetimepicker6').data("DateTimePicker").maxDate(e.date);
        });
      });
    </script>
  </body>

</html>
