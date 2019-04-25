<div class="wrapper">
    <h2>Reporting Data Grid</h2>

    <table id="reporting-data" class="display" style="width:100%">
    </table>

    <script>
        jQuery(document).ready(function() {

            var currencyFormatter = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 0
            });

            jQuery('#crestcom-reporting-data').DataTable({
                data: <?php echo json_encode($gridData); ?>,
                columnDefs: [{
                    render: function(data, type, row ){
                        //format as money
                        return currencyFormatter.format(data);
                    },
                    targets: 4

                }],
                columns: <?php echo json_encode($gridColumns); ?>,
                pageLength: 100,
                lengthMenu: [[50, 100, 200, -1], [50, 100, 200, "All"]]
            });
        } );
    </script>

    <style type="text/css">
        .reporting-center{
            text-align: center;
        }
    </style>

</div>