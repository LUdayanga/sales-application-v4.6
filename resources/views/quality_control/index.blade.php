@extends('layouts.app')
@section('title', __('Quality Control'))

@section('content')

    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('Filters')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('type', 'Quality control step:') !!}
                        {!! Form::select('type', ['qcs_1' => __('Receiving the quality check'), 'qcs_2' => __('Material approval for production'), 'qcs_3' => __('1st product approval'), 'qcs_4' => __('Final product quality approval'), 'qcs_5' => __('Material balance')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'filter_qc_step', 'placeholder' => __('lang_v1.all')]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('type', 'Quality control status:') !!}
                        {!! Form::select('type', ['Checked' => __('Pending'), 'Approved' => __('Approved'), 'Disapproved' => __('Disapproved')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'filter_qc_status', 'placeholder' => __('lang_v1.all')]) !!}
                    </div>
                </div>
                {{-- <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('qc_date_filter', __('Date Range') . ':') !!}
                        {!! Form::text('qc_date_filter', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']) !!}
                    </div>
                </div> --}}
            @endcomponent
        </div>
    </div>

    <!-- Content Header (Page header) -->
    <section class="content-header no-print">
        <h1>@lang('Quality Control')
        </h1>
    </section>

    <!-- Main content -->
    <section class="content no-print">
        @component('components.widget', ['class' => 'box-primary', 'title' => __('Quality Controll Details')])
            @slot('tool')
                <div class="box-tools">
                    @if (auth()->user()->can('qc.create'))
                        <button type="button" class="btn btn-primary" id="add_qc"><i class="fa fa-plus"></i>
                            @lang('messages.add')</button>
                    @endif
                </div>
            @endslot
            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view" id="qc_table">
                    <thead>
                        <tr>
                            <th>@lang('Date')</th>
                            <th>@lang('QC Sheet No')</th>
                            <th hidden>@lang('qc_step')</th>
                            <th>@lang('QC Step')</th>
                            <th>@lang('QC Status')</th>
                            <th>@lang('Reference Details')</th>
                            <th>@lang('Special Note')</th>
                            <th>@lang('Create By')</th>
                            <th>@lang('Action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcomponent
    </section>

    <!-- Qc Edit Modal -->
    <div class="modal fade" id="qc_edit_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Update Quality Control Details</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="post" id="form_save_details">
                        <div class="box box-primary">
                            <div class="box-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="">QC Sheet No:*</label>
                                                <input type="text" class="form-control" name="qc_sheet_no"
                                                    id="qc_sheet_no" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="">QC Date:*</label>
                                                <input type="text" class="form-control" value="<?php echo date('Y-m-d'); ?>"
                                                    name="qc_date" id="qc_date" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="box box-primary"><br>
                                {{-- <div class="box-header search_product_row">
                                    <h3 class="box-title">{{ __('stock_adjustment.search_products') }}</h3>
                                </div>
                                <div class="box-body">
                                    <div class="row search_product_row">
                                        <div class="col-sm-8 col-sm-offset-2">
                                            <div class="form-group">
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="fa fa-search"></i>
                                                    </span>
                                                    {!! Form::text('search_product', null, ['class' => 'form-control', 'id' => 'search_product_for_srock_adjustment', 'placeholder' => __('Search product'), '']) !!}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> --}}
                                <div class="row qc_product_table">
                                    <div class="col-md-12">
                                        <label for="">Selected Products:</label>
                                        <div class="table-responsive">
                                            <table class="table table-condensed table-bordered table-striped table-th-green"
                                                id="product_table">
                                                <thead>
                                                    <tr id="product_table_header">
                                                        <th hidden>transaction_id</th>
                                                        <th hidden>produc_id</th>
                                                        <th hidden>variation_id</th>
                                                        <th class="col-sm-2">Product</th>
                                                        <th hidden>lot_no_input</th>
                                                        <th class="col-sm-2">Lot No</th>
                                                        <th class="col-sm-1">Received Quantity</th>
                                                        <th hidden>received_quantity_input</th>
                                                        <th class="col-sm-1">Checked Quantity</th>
                                                        <th class="col-sm-1">Passed Quantity</th>
                                                        <th class="col-sm-5"></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbl_tbody_product"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="box box-primary">
                                <div class="box-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="">Special Note:</label>
                                                <textarea class="form-control" id="special_note" name="special_note" rows="4"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-check qc_finaliaze form-inline">
                                                @if (auth()->user()->can('qc.finalize'))
                                                    <div class="checkbox div_check_unapprove" style="margin-right: 25px;">
                                                        <input type="checkbox" class="input-icheck" id="qc_unapprove"
                                                            name="qc_unapprove" value="1">
                                                        <label for="">@lang('Disapprove & Hold Production')</label>
                                                    </div>
                                                    <div class="checkbox">
                                                        <label>
                                                            {!! Form::checkbox('qc_finalize', 1, false, ['class' => 'input-icheck', 'id' => 'qc_finalize']) !!} @lang('Approve')
                                                        </label>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <input type="text" name="qc_id" id="qc_id" placeholder="qc id" hidden>
                            <input type="text" name="qc_step" id="qc_step" placeholder="qc step" hidden>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" id="btn_save" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!--Qc View Modal -->
    <div class="modal fade" id="qc_view_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Quality Control Details</h4>
                    <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group form-inline" style="margin-bottom: 5px;">
                                <strong>@lang('QC Sheet No '):</strong>
                                <span for="" id="lable_sheet_no"></span>
                            </div>
                            <div class="form-group form-inline" style="margin-bottom: 25px;">
                                <strong>@lang('QC Date '):</strong>
                                <span for="" id="lable_qc_date"></span>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group form-inline" style="margin-bottom: 5px;">
                                <strong>@lang('QC Step '):</strong>
                                <span for="" id="lable_qc_step"></span>
                            </div>
                            <div class="form-group form-inline" style="margin-bottom: 5px;">
                                <strong>@lang('QC Status '):</strong>
                                <span for="" id="lable_qc_status"></span>
                            </div>
                            <div class="form-group form-inline" style="margin-bottom: 25px;">
                                <strong>@lang('Reference Details '):</strong>
                                <span for="" id="lable_refference"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row qc_product_table">
                        <div class="col-md-12">
                            <label for="" id="lable_sheet_no">Quality Control Products:</label>
                            <div class="table-responsive">
                                <table class="table table-condensed table-bordered table-striped" id="show_tbody_product">
                                    {{-- <thead>
                                        <tr>
                                            <th class="text-center col-sm-3">Product</th>
                                            <th class="text-center col-sm-2">Lot No</th>
                                            <th class="text-center col-sm-1">Recieved Quantity</th>
                                            <th class="text-center col-sm-1">Checked Quantity</th>
                                            <th class="text-center col-sm-1">Passed Quantity</th>
                                            <th class="text-center col-sm-3">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody id="show_tbody_product"></tbody> --}}
                                </table>
                            </div>
                        </div>
                    </div>
                    <br><br>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <h5 for="" style="font-weight: bold">Special Note:</h5>
                                <label for="" id="lable_special_note"></label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="" class="btn btn-primary no-print"
                        onclick="$(this).closest('div.modal-content').printThis();">Print</button>
                    <button type="button" class="btn btn-secondary no-print" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- parameter modal --}}
    <div class="modal fade" id="modal_parameter" data-backdrop="false" data-keyboard="false" tabindex="-1"
        role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title parameter_header" id="exampleModalLabel"></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="post" id="form_prodcut_parametres">
                        <div class="box box-primary"><br>
                            <table class="table table-bordered table-striped" id="" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th hidden>@lang('parameter id')</th>
                                        <th class="text-center col-sm-4">@lang('Parameter')</th>
                                        <th class="text-center col-sm-2">@lang('Status')</th>
                                        <th class="text-center">@lang('Description')</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody_table_parameters"></tbody>
                            </table>
                        </div>
                </div>
                <div class="modal-footer">
                    <input type="text" name="product_quality_id" id="product_quality_id" hidden>
                    <input type="text" name="parameter_product_id" id="parameter_product_id" hidden>
                    <input type="text" name="parameter_product_lot" id="parameter_product_lot" hidden>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary save_qc_parameter">Set Parameter</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    {{-- @include('stock_transfer.partials.update_status_modal') --}}
    <section id="receipt_section" class="print_section"></section>

    <!-- /.content -->
@stop
@section('javascript')
    <script>
        $(document).ready(function() {

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            var qc_table = $('#qc_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '/quality-control',
                buttons: [],
                "ajax": {
                    "url": "/quality-control",
                    "data": function(d) {
                        d.filter_qc_step = $('#filter_qc_step').val();
                        d.filter_qc_status = $('#filter_qc_status').val();

                        // var start = '';
                        // var end = '';
                        // if ($('#qc_date_filter').val()) {
                        //     start = $('input#qc_date_filter')
                        //         .data('daterangepicker')
                        //         .startDate.format('YYYY-MM-DD');
                        //     end = $('input#qc_date_filter')
                        //         .data('daterangepicker')
                        //         .endDate.format('YYYY-MM-DD');
                        // }
                        // d.start_date = start;
                        // d.end_date = end;

                        d = __datatable_ajax_callback(d);
                    }
                },
                columns: [{
                        data: 'qc_date',
                        name: 'qc_date'
                    },
                    {
                        data: 'qc_ref_no',
                        name: 'qc_ref_no'
                    },
                    {
                        "targets": 2,
                        data: 'input_qc_step',
                        name: 'input_qc_step',
                        'visible': false,
                    },
                    {

                        data: 'qc_step',
                        name: 'qc_step'
                    },
                    {

                        data: 'qc_status',
                        name: 'qc_status'
                    },
                    {

                        data: 'ref_details',
                        name: 'ref_details'
                    },
                    {

                        data: 'special_note',
                        name: 'special_note'
                    },
                    {

                        data: 'user',
                        name: 'user'
                    },
                    {
                        data: 'action',
                        name: 'action'
                    },
                ],
            });

            //Date range as a button
            // $('#qc_date_filter').daterangepicker(
            //     dateRangeSettings,
            //     function(start, end) {
            //         $('#qc_date_filter').val(start.format(moment_date_format) + ' ~ ' + end
            //             .format(
            //                 moment_date_format));
            //         qc_table.ajax.reload();
            //     }
            // );

            // filter dropdown change
            $(document).on('change', '#filter_qc_step', function() {
                qc_table.ajax.reload();
            });

            $(document).on('change', '#filter_qc_status', function() {
                qc_table.ajax.reload();
            });

            // $('#qc_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            //     $('#qc_date_filter').val('');
            //     qc_table.ajax.reload();
            // });

            //Date picker
            $('#qc_date').datetimepicker({
                format: 'YYYY-MM-DD',
                ignoreReadonly: true,
            });

            $(document).on("keyup", ".received_qty", function() {
                $(this).closest('tr').find("td:eq(8) input[type='number']").val($(this).val());
                $(this).closest('tr').find("td:eq(9) input[type='number']").val('');
            });

            //validate table input fields
            $(document).on("keyup", ".checked_qty", function() {
                $(this).closest('tr').find("td:eq(9) input[type='number']").val($(this).val());
                if (parseFloat($(this).closest('tr').find("td:eq(7) input[type='number']").val()) <
                    parseFloat($(this).closest('tr').find("td:eq(8) input[type='number']").val())) {
                    $(this).closest('tr').find("td:eq(8) input[type='number']").val('');
                    $(this).closest('tr').find("td:eq(9) input[type='number']").val('');
                }
            });

            //validate table input fields
            $(document).on("keyup", ".passed_qty", function() {
                if (parseFloat($(this).closest('tr').find("td:eq(8) input[type='number']").val()) <
                    parseFloat($(this).closest('tr').find("td:eq(9) input[type='number']").val())) {
                    $(this).closest('tr').find("td:eq(9) input[type='number']").val('');
                }
            });

            //update details
            $('#btn_save').click(function() {
                var isValide1 = true;
                var isValide2 = true;

                if ($("#qc_sheet_no").val() == '') {
                    isValide1 = false;
                }

                $('#tbl_tbody_product tr').each(function() {
                    if ($(this).find("td:eq(8) input[type='number']").val() == '' || $(this).find(
                            "td:eq(9) input[type='number']").val() == '') {
                        isValide2 = false;
                        return false;
                    } else {
                        isValide2 = true;
                    }
                });

                if (isValide1 == true && isValide2 == true) {

                    $.ajax({
                        method: 'post',
                        url: '/quality-control/update_deatils',
                        data: $("#form_save_details").serialize(),

                        success: function(response) {
                            if (response.success == true) {
                                toastr.success(response.msg);
                                $("#qc_edit_modal").modal('hide');
                                qc_table.ajax.reload();
                            } else {
                                toastr.error(response.msg);
                            }
                        }

                    });

                } else {
                    toastr.error('Fill required fields');
                }
            });

            // redirect create qc page
            $(document).on("click", "#add_qc", function() {
                window.location.href = "/quality-control/create";
            });

            //open product parameter modal
            $(document).on('click', '.btn_parameter', function() {
                var header_txt = $(this).closest('tr').find("td:eq(3)").text();
                var product_lot = $(this).closest('tr').find("td:eq(4) input[type='text']").val();
                var split = header_txt.split(')');
                var quality_id = $("#qc_id").val();
                var product_id = $(this).attr("data-val");
                var is_qc_save = 'true';

                $.ajax({
                    method: 'post',
                    url: '/quality-control/load_parameters',
                    data: {
                        product_id: product_id,
                        product_lot: product_lot,
                        quality_id: quality_id,
                        is_qc_save: is_qc_save
                    },

                    success: function(data) {
                        $("#tbody_table_parameters").empty();
                        data.forEach(function(row) {

                            var table_row = "<tr>";

                            if (row.is_tempory == 'true') {
                                table_row +=
                                    "<td hidden><input type='text' class='form-control' value=" +
                                    row.parent_parameter_id +
                                    " name='parent_parameter_id[]'></td>";
                            } else {
                                table_row +=
                                    "<td hidden><input type='text' class='form-control' value=" +
                                    row.parameter_parent_id +
                                    " name='parent_parameter_id[]'></td>";
                            }

                            table_row += "<td class='col-sm-4'>" + row.parameter_name +
                                "</td>";


                            if (row.is_tempory == 'true') {
                                if (row.qc_param_status == 'pass') {
                                    table_row +=
                                        "<td class='col-sm-2'><select class='form-control' name='parameter_status[]'><option value='pass' selected>Pass</option><option value='fail'>Fail</option></select></td>";
                                } else {
                                    table_row +=
                                        "<td class='col-sm-2'><select class='form-control' name='parameter_status[]'><option value='pass'>Pass</option><option value='fail' selected>Fail</option></select></td>";
                                }

                                table_row +=
                                    "<td><input type='text' class='form-control' name='parameter_description[]' value=" +
                                    row.qc_param_description + "></td>";
                            } else {
                                table_row +=
                                    "<td class='col-sm-2'><select class='form-control' name='parameter_status[]'><option value='pass'>Pass</option><option value='fail'>Fail</option></select></td>";
                                table_row +=
                                    "<td><input type='text' class='form-control' name='parameter_description[]'></td>";
                            }

                            table_row += "</tr>";

                            $("#tbody_table_parameters").append(table_row);
                        });
                    }

                });

                $('#parameter_product_id').val(product_id);
                $('#parameter_product_lot').val(product_lot);
                $('#product_quality_id').val(quality_id);
                $('.parameter_header').text(split[0] + ') / [lot no: ' + product_lot + '] - ' + split[1]);
                $("#modal_parameter").modal();
            });

            //save product parameters
            $(document).on('click', '.save_qc_parameter', function() {

                $.ajax({
                    method: 'post',
                    url: '/quality-control/update_product_parameters',
                    data: $("#form_prodcut_parametres").serialize(),

                    success: function(response) {
                        if (response.success == true) {
                            $("#modal_parameter").modal('hide');
                        } else {
                            toastr.error(response.msg);
                        }
                    }

                });
            });

            //display and load qc details when click qc edit button
            $(document).on("click", ".btn_edit_qc", function(e) {
                e.preventDefault();
                var id = $(this).attr("data-val");

                $.ajax({
                    method: 'post',
                    url: '/quality-control/load_edit_modal',
                    data: {
                        id: id
                    },

                    success: function(data) {
                        $("#tbl_tbody_product").empty();
                        data.forEach(function(row) {

                            var table_row = "<tr>";
                            if (row.qc_step != 'qcs_5') {
                                table_row += "<td hidden><input type='text' value=" +
                                    row
                                    .transaction_id + " name='transaction_id[]'></td>";
                                table_row += "<td hidden><input type='text' value=" +
                                    row
                                    .product_id + " name='product_id[]'></td>";
                                table_row += "<td hidden><input type='text' value=" +
                                    row
                                    .variation_id + " name='variation_id[]'></td>";
                                table_row += "<td class='col-sm-2'>" + row.name + ' (' +
                                    row.sku + ')' +
                                    "<button type='button' data-val=" + row.product_id +
                                    " class='btn btn-danger btn-xs btn_parameter' style='margin-top: 5px;'>Product Parameter</button></td>";
                                table_row += "<td hidden><input type='text' value=" +
                                    row
                                    .product_lot_no + " name='lot_number[]'></td>";
                                table_row += "<td class='col-sm-2'>" + row
                                    .product_lot_no +
                                    "</td>";
                                table_row += "<td class='col-sm-1'>" + row
                                    .recieved_qty +
                                    "</td>";
                                table_row += "<td hidden><input type='number' value=" +
                                    row
                                    .recieved_qty + " name='quantity[]' readonly></td>";
                                table_row +=
                                    "<td class='col-sm-1'><input type='number' class='form-control checked_qty' value=" +
                                    row.qc_checked_qty +
                                    " name='checked_qty[]' id='checked_qty' required></td>";
                                table_row +=
                                    "<td class='col-sm-1'><input type='number' class='form-control passed_qty' value=" +
                                    row.qc_pass_qty +
                                    " name='passed_qty[]' id='passed_qty' required></td>";

                                if (row.qc_fail_description != '') {
                                    table_row +=
                                        "<td class='col-sm-5'><input type='text' class='form-control' value=" +
                                        row.qc_fail_description +
                                        " name='fail_descrpition[]'></td>";
                                } else {
                                    table_row +=
                                        "<td class='col-sm-5'><input type='text' class='form-control' value='' name='fail_descrpition[]'></td>";
                                }
                            } else {
                                table_row += "<td hidden><input type='text' value=" +
                                    row
                                    .transaction_id + " name='transaction_id[]'></td>";
                                table_row += "<td hidden><input type='text' value=" +
                                    row
                                    .product_id + " name='product_id[]'></td>";
                                table_row += "<td hidden><input type='text' value=" +
                                    row
                                    .variation_id + " name='variation_id[]'></td>";
                                table_row += "<td class='col-sm-2'>" + row.name +
                                    "</td>";
                                table_row += "<td hidden><input type='text' value=" +
                                    row
                                    .product_lot_no + " name='lot_number[]'></td>";
                                table_row += "<td class='col-sm-2'>" + row
                                    .product_lot_no +
                                    "</td>";
                                table_row +=
                                    "<td class='col-sm-1'><input type='number' class='form-control received_qty' value=" +
                                    row
                                    .recieved_qty + " name='quantity[]'></td>";
                                table_row += "<td hidden><input type='number' value=" +
                                    row
                                    .recieved_qty + " readonly></td>";
                                table_row +=
                                    "<td class='col-sm-1'><input type='number' class='form-control checked_qty' value=" +
                                    row.qc_checked_qty +
                                    " name='checked_qty[]' id='checked_qty' required readonly></td>";
                                table_row +=
                                    "<td class='col-sm-1'><input type='number' class='form-control passed_qty' value=" +
                                    row.qc_pass_qty +
                                    " name='passed_qty[]' id='passed_qty' required></td>";

                                if (row.qc_fail_description != '') {
                                    table_row +=
                                        "<td class='col-sm-5'><input type='text' class='form-control' value=" +
                                        row.qc_fail_description +
                                        " name='fail_descrpition[]'></td>";
                                } else {
                                    table_row +=
                                        "<td class='col-sm-5'><input type='text' class='form-control' value='' name='fail_descrpition[]'></td>";
                                }
                            }

                            table_row += "</tr>";
                            $("#tbl_tbody_product").append(table_row);

                            $("#qc_date").val(row.qc_date);
                            $("#qc_sheet_no").val(row.qc_ref_no);
                            $("#special_note").val(row.special_note);

                            if (row.qc_status == "Approved") {
                                $('#qc_finalize').prop('checked', true);
                            } else {
                                $('#qc_finalize').prop('checked', false)
                            }

                            if (row.qc_step == 'qcs_3') {
                                $('.div_check_unapprove').show();
                            } else {
                                $('.div_check_unapprove').hide();
                            }

                            $("#qc_step").val(row.qc_step);
                        });
                        $("#qc_id").val(id);
                        $("#qc_edit_modal").modal();
                    }

                });
            });

            //show qc details
            $(document).on("click", ".btn_view_qc", function(e) {
                e.preventDefault();
                var qc_status = $(this).closest("tr").find('.btn_status').text();
                var lable_qc_date = $(this).closest('tr').find("td:eq(0)").text();
                var lable_sheet_no = $(this).closest('tr').find("td:eq(1)").text();
                var lable_qc_step = $(this).closest('tr').find("td:eq(2)").text();
                var lable_refference = $(this).closest('tr').find("td:eq(4)").text();
                var id = $(this).attr("data-val");

                $.ajax({
                    method: 'post',
                    url: '/quality-control/view_qc_deatils',
                    data: {
                        id: id
                    },

                    success: function(data) {
                        $("#show_tbody_product").html(data);
                        $("#lable_sheet_no").text(lable_sheet_no);
                        $("#lable_qc_date").text(lable_qc_date);
                        $("#lable_qc_step").text(lable_qc_step);
                        $("#lable_refference").text(lable_refference);
                        $("#lable_qc_status").text(qc_status);
                        $("#qc_view_modal").modal();
                    }

                });

            });

            $(document).on('click', '.delete_qc_btn', function() {
                swal({
                    title: 'Are you sure delete?',
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(willDelete => {
                    if (willDelete) {
                        var quality_id = $(this).attr("data-val");

                        $.ajax({
                            method: 'post',
                            url: '/quality-control/delete_record',
                            data: {
                                quality_id: quality_id,
                            },

                            success: function(response) {
                                if (response.success == true) {
                                    toastr.success('Successfully Deleted');
                                    qc_table.ajax.reload();
                                } else {
                                    toastr.error(response.msg);
                                }
                            }

                        });
                    }
                });
            });

            // filter dropdown change
            $(document).on('change', '#filter_condition', function() {
                machines_table.ajax.reload();
            });

            function load_parameter_details(quality_id, product_id, product_lot) {
                $.ajax({
                    method: 'post',
                    url: '/quality-control/view_quality_product_parameters',
                    data: {
                        quality_id: quality_id,
                        product_id: product_id,
                        product_lot: product_lot
                    },

                    success: function(data) {
                        data.forEach(function(row) {

                            var table_row = "<tr>";

                            if (row.is_tempory == 'true') {
                                table_row +=
                                    "<td hidden><input type='text' class='form-control' value=" +
                                    row.parent_parameter_id +
                                    " name='parent_parameter_id[]'></td>";
                            } else {
                                table_row +=
                                    "<td hidden><input type='text' class='form-control' value=" +
                                    row.parameter_parent_id +
                                    " name='parent_parameter_id[]'></td>";
                            }

                            table_row += "<td class='col-sm-4'>" + row.parameter_name +
                                "</td>";


                            if (row.is_tempory == 'true') {
                                if (row.qc_param_status == 'pass') {
                                    table_row +=
                                        "<td class='col-sm-2'><select class='form-control' name='parameter_status[]'><option value='pass' selected>Pass</option><option value='fail'>Fail</option></select></td>";
                                } else {
                                    table_row +=
                                        "<td class='col-sm-2'><select class='form-control' name='parameter_status[]'><option value='pass'>Pass</option><option value='fail' selected>Fail</option></select></td>";
                                }

                                table_row +=
                                    "<td><input type='text' class='form-control' name='parameter_description[]' value=" +
                                    row.qc_param_description + "></td>";
                            } else {
                                table_row +=
                                    "<td class='col-sm-2'><select class='form-control' name='parameter_status[]'><option value='pass'>Pass</option><option value='fail'>Fail</option></select></td>";
                                table_row +=
                                    "<td><input type='text' class='form-control' name='parameter_description[]'></td>";
                            }

                            table_row += "</tr>";

                            $("#show_tbody_product").append(table_row);
                        });
                    }

                });
            }

        });
    </script>
@endsection
