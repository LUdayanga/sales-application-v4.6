@extends('layouts.app')
@section('title', __('account.cheque_management'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('account.cheque_management')</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                @component('components.filters', ['title' => __('report.filters')])
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Cheque number:</label>
                            <div class="input-group input-group-sm">
                                <input id="cheque_number" name="cheque_number"
                                       value="" type="text" class="form-control">
                                <span class="input-group-btn">
                                    <button id="cheque_number_search_button" type="button"
                                            class="btn btn-info btn-flat"><i
                                                class="fa fa-search"></i></button>
                                </span>
                            </div>
                            <!-- /input-group -->
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('contact_id',  __('contact.contact') . ':') !!}
                            {!! Form::select('contact_id', $contacts, null, ['class' => 'form-control select2', 'id' => 'contact_id', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>@lang('account.date'):</label>

                            <div class="input-group date">
                                <div class="input-group-addon">
                                    <i class="fa fa-calendar"></i>
                                </div>
                                <input type="text" name="datepicker" class="form-control pull-right" id="datepicker">
                            </div>
                            <!-- /.input group -->
                        </div>
                        <!-- /.form group -->
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label("account_id" , __('lang_v1.payment_account') . ':') !!}
                            {!! Form::select('account_id', $accounts, null, ['class' => 'form-control select2', 'id' => 'account_id', 'style' => 'width:100%']); !!}
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['class' => 'box-primary', 'title' => __('account.all_cheques')])
                    @can('account.access')
                        @slot('tool')
                            <div class="box-tools">
                                <a class="btn btn-block btn-primary add-cheque"
                                   href="{{action('ChequesController@create')}}">
                                    <i class="fa fa-plus"></i> @lang('messages.add')</a>
                            </div>
                        @endslot
                    @endcan
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="cheque_table">
                            <thead>
                            <tr>
                                <th>@lang('messages.action')</th>
                                <th>@lang('account.account_name')</th>
                                <th>@lang('account.cheque_number')</th>
                                <th>@lang('messages.date')</th>
                                <th>@lang('sale.total_amount')</th>
                                <th>@lang('account.cheque_status')</th>
                                <th>@lang('account.cheque_type')</th>
{{--                                <th>@lang('account.return_fee')</th>--}}
                            </tr>
                            </thead>
                            {{--                            <tfoot>--}}
                            {{--                            <tr class="bg-gray font-17 text-center footer-total">--}}
                            {{--                                <td colspan="6"><strong>@lang('sale.total'):</strong></td>--}}
                            {{--                                <td id="footer_payment_status_count"></td>--}}
                            {{--                                <td></td>--}}
                            {{--                                <td><span class="display_currency" id="footer_expense_total" data-currency_symbol ="true"></span></td>--}}
                            {{--                                <td><span class="display_currency" id="footer_total_due" data-currency_symbol ="true"></span></td>--}}
                            {{--                                <td colspan="4"></td>--}}
                            {{--                            </tr>--}}
                            {{--                            </tfoot>--}}
                        </table>
                    </div>
                @endcomponent
            </div>
        </div>

    </section>
    <!-- /.content -->
    <!-- /.content -->
    <div class="modal fade" id="modal-default"></div><!-- /.modal -->
@stop
@section('javascript')
    <script>

        $(document).ready(function () {
            //Date picker
            $('#datepicker').datepicker({
                autoclose: true
            });

            let cheque_table = $('#cheque_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    method: 'GET',
                    url: "{{ action('ChequesController@index') }}",
                    data: function (d) {
                        d.cheque_number = $('#cheque_number').val();
                        d.contact_id = $('#contact_id').val();
                        d.date = $('#datepicker').val();
                        d.account_id = $('#account_id').val();
                    }
                },
                columnDefs: [{
                    "targets": [0, 1],
                    "orderable": true,
                    'searchable': false
                }],
                "footerCallback": function (row, data, start, end, display) {
                    var api = this.api(), data;
                    // converting to interger to find total
                    var intVal = function (i) {
                        return typeof i === 'string' ?
                            i.replace(/[\$,]/g, '') * 1 :
                            typeof i === 'number' ?
                                i : 0;
                    };

                    // final_total
                    // let final_total = api.column(1).data().reduce(function (a, b) {
                    //     return intVal(a) + intVal(b);
                    // }, 0);
                    //
                    // $(api.column(1).footer()).html(numFormat(final_total));

                },
                columns: [
                    {name: 'action', data: 'action', searchable: false},
                    {name: 'name', data: 'name'},
                    {name: 'cheque_number', data: 'cheque_number'},
                    {name: 'cheque_date', data: 'cheque_date'},
                    {name: 'cheque_amount', data: 'cheque_amount'},
                    {name: 'cheque_status', data: 'cheque_status'},
                    {name: 'cheque_type', data: 'cheque_type'},
                    // {name: 'cheque_return_fee', data: 'cheque_return_fee'}
                ]
            });

            // let numFormat = $.fn.dataTable.render.number('\,', '.', 2, '').display;

            $(document).on('click', '#cheque_number_search_button', function () {
                cheque_table.ajax.reload();
            });
            $(document).on('change', '#contact_id, #datepicker, #account_id', function () {
                cheque_table.ajax.reload();
            });

            $('table#cheque_table tbody').on('click', 'a.mark-as-paid', function (e) {
                e.preventDefault();
                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        var href = $(this).attr('href');
                        $.ajax({
                            method: "GET",
                            url: href,
                            success: function (result) {
                                if (result.success === true) {
                                    toastr.success(result.msg);
                                    cheque_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });

            $('table#cheque_table tbody').on('click', 'a.mark-as-returned', function (e) {
                e.preventDefault();
                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        var href = $(this).attr('href');
                        $.ajax({
                            method: "GET",
                            url: href,
                            success: function (result) {
                                if (result.success === true) {
                                    toastr.success(result.msg);
                                    cheque_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });

            $('table#cheque_table tbody').on('click', 'a.delete-cheque', function (e) {
                e.preventDefault();
                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        var href = $(this).attr('href');
                        $.ajax({
                            method: "DELETE",
                            url: href,
                            success: function (result) {
                                if (result.success === true) {
                                    toastr.success(result.msg);
                                    cheque_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });

            $('table#cheque_table tbody').on('click', 'a.view-cheque', function (e) {
                e.preventDefault();
                var href = $(this).attr('href');
                $('#modal-default').load(href,function(){
                    $('#modal-default').modal('show');
                });
            });

        });

    </script>
@endsection