@extends('layouts.app')
@section('title', __('account.create_cheque'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('account.create_cheque')</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <form action="{{ route('cheque.store') }}" method="post">
            @csrf
            <div class="row">
                <div class="col-md-12">
                    <!-- general form elements -->
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-filter"
                                                     aria-hidden="true"></i> @lang('report.filters')
                            </h3>
                        </div>
                        <!-- /.box-header -->
                        <!-- form start -->
                        <div class="box-body">
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::label('contact_id',  __('contact.contact') . ':*') !!}
                                    {!! Form::select('contact_id', $contacts, null, ['class' => 'form-control select2', 'id' => 'contact_id', 'required', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                                </div>
                            </div>
                        </div>
                        <!-- /.box-body -->
                    </div>
                    <!-- /.box -->
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <!-- general form elements -->
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('account.invoices')</h3>
                            <input type="hidden" id="final_total" value="">
                        </div>
                        <!-- /.box-header -->
                        <!-- form start -->
                        <div class="box-body">
                            <table id="cheque-related-invoice-table" class="table table-bordered table-striped">
                                <thead>
                                <tr>
                                    <th>@lang('sale.invoice_no')</th>
                                    <th>@lang('sale.total_amount')</th>
                                    <th>@lang('messages.date')</th>
                                    <th>@lang('sale.payment_status')</th>
                                    <th>@lang('messages.action')</th>
                                </tr>
                                </thead>
                                <tbody>

                                </tbody>
                                <tfoot>
                                <tr>
                                    <th>@lang('sale.total_amount')</th>
                                    <th id="footer_final_total"></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                        <!-- /.box-body -->
                    </div>
                    <!-- /.box -->
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <!-- general form elements -->
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('account.cheque_details')</h3>
                            <div class="box-tools">
                                <input type="hidden" id="index" value="0">
                                <a id="add-cheque" class="btn btn-block btn-primary" href="javascript:void(0)">
                                    <i class="fa fa-plus"></i> @lang('account.add_cheque')</a>
                            </div>
                        </div>
                        <!-- /.box-header -->
                        <!-- form start -->
                        <div class="box-body" id="box-body">

                            <div class="row">
                                <div class="col-md-12">
                                    <!-- general form elements -->
                                    <div class="box box-primary">
                                        <div class="box-header with-border">
                                            <h3 class="box-title">@lang('account.cheque')</h3>
                                            {{--                                            <div class="box-tools">--}}
                                            {{--                                                <a id="add-cheque" class="btn btn-block btn-danger"--}}
                                            {{--                                                   href="javascript:void(0)">--}}
                                            {{--                                                    <i class="fa fa-trash"></i> @lang('account.remove_cheque')</a>--}}
                                            {{--                                            </div>--}}
                                        </div>
                                        <!-- /.box-header -->
                                        <!-- form start -->
                                        <div class="box-body">
                                            <div class="cheque">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        {!! Form::label("cheques[0][account_id]" , __('lang_v1.payment_account') . ':*') !!}
                                                        {!! Form::select('cheques[0][account_id]', $accounts, null, ['class' => 'form-control select2', 'required', 'style' => 'width:100%']); !!}
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="cheque_number">@lang('account.cheque_number')
                                                            :*</label>
                                                        <input type="text" class="form-control" id="cheque_number"
                                                               name="cheques[0][cheque_number]"
                                                               placeholder="Enter Cheque Number" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>@lang('account.date'):*</label>

                                                        <div class="input-group date">
                                                            <div class="input-group-addon">
                                                                <i class="fa fa-calendar"></i>
                                                            </div>
                                                            <input type="text" name="cheques[0][cheque_date]"
                                                                   autocomplete="off"
                                                                   class="form-control pull-right" id="date" required>
                                                        </div>
                                                        <!-- /.input group -->
                                                    </div>
                                                    <!-- /.form group -->
                                                </div>
                                                <div class="clearfix"></div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>@lang('account.issued_date'):*</label>

                                                        <div class="input-group date">
                                                            <div class="input-group-addon">
                                                                <i class="fa fa-calendar"></i>
                                                            </div>
                                                            <input type="text" name="cheques[0][cheque_issued_date]"
                                                                   autocomplete="off"
                                                                   class="form-control pull-right" id="issued_date"
                                                                   required>
                                                        </div>
                                                        <!-- /.input group -->
                                                    </div>
                                                    <!-- /.form group -->
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="total_amount">@lang('sale.total_amount'):*</label>
                                                        <input type="text" class="form-control"
                                                               name="cheques[0][cheque_amount]"
                                                               id="total_amount"
                                                               placeholder="Enter Total" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>@lang('account.cheque_type'):*</label>
                                                        <select class="form-control select2" style="width: 100%;"
                                                                name="cheques[0][cheque_type]" required>
                                                            <option value="giving" selected="Issued">Issued</option>
                                                            <option value="receiving">Received</option>
                                                        </select>
                                                    </div>
                                                    <!-- /.form-group -->
                                                </div>
                                            </div>
                                        </div>
                                        <!-- /.box-body -->
                                    </div>
                                    <!-- /.box -->
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="pull-right"><strong>@lang('lang_v1.balance'):</strong> <span
                                                class="balance_due">0.00</span></div>
                                </div>
                            </div>
                        </div>
                        <!-- /.box-body -->
                        <div class="box-footer">
                            <button type="button" class="btn btn-default pull-right margin-left-10">Cancel</button>
                            <button id="create-button" type="submit" class="btn btn-info pull-right">Create Cheque
                            </button>
                        </div>
                        <!-- /.box-footer -->
                    </div>
                    <!-- /.box -->
                </div>
            </div>
        </form>
    </section>
@endsection
@section('javascript')
    <script>
        $(document).ready(function () {
            //Date picker
            $('#date').datepicker({
                autoclose: true
            });

            $('#issued_date').datepicker({
                autoclose: true
            });

            let cheque_related_invoice_table = $('#cheque-related-invoice-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    method: 'GET',
                    url: "{{ action('ChequesController@getInvoicesRelatedToContact') }}",
                    data: function (d) {
                        d.contact_id = $('#contact_id').val();
                    }
                },
                columnDefs: [{
                    "targets": [4],
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
                    let final_total = api.column(1).data().reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);

                    $(api.column(1).footer()).html(numFormat(final_total));
                    $('#final_total').val(final_total);
                    $('#total_amount').val(final_total);

                },
                columns: [
                    {name: 'invoice_no', data: 'invoice_no'},
                    {name: 'final_total', data: 'final_total'},
                    {name: 'transaction_date', data: 'transaction_date'},
                    {name: 'payment_status', data: 'payment_status'},
                    {name: 'action', data: 'action', searchable: false}
                ]
            });

            let numFormat = $.fn.dataTable.render.number('\,', '.', 2, '').display;

            $(document).on('change', '#contact_id', function () {
                cheque_related_invoice_table.ajax.reload();
            });

            $(document).on('click', '.remove_invoice', function () {
                $(this).closest('tr').remove();
                getFinalTotal();
                let total_amount = $(this).val();
                let final_amount = $('#final_total').val();
                amountValidation(total_amount, final_amount);
            });


            function getFinalTotal() {
                let final_total = 0;
                $('table#cheque-related-invoice-table tbody tr #invoice_total').each(function () {
                    final_total += parseFloat($(this).val());
                });

                $('#final_total').val(final_total);
                $('#footer_final_total').text(__number_f(final_total));
            }

            function getAllInputAmount() {
                let totalInputAmount = 0;
                $('div#box-body #total_amount').each(function () {
                    if ($(this).val() !== '') {
                        totalInputAmount += parseFloat($(this).val());
                    }
                })
                return totalInputAmount;
            }

            $(document).on('input', '#total_amount', function () {
                let total_input_amount = getAllInputAmount();
                let final_amount = $('#final_total').val();
                let balance = final_amount - total_input_amount;
                amountValidation(total_input_amount, final_amount);
                $('.balance_due').text(__number_f(balance))
            });

            function amountValidation(total_amount, final_amount) {

                if (parseFloat(final_amount) === parseFloat(total_amount)) {
                    $('#create-button').attr('disabled', false);
                } else {
                    $('#create-button').attr('disabled', true);
                }
            }

            $(document).on('click', '#add-cheque', function () {
                let index = $('#index').val();
                index++;
                $.ajax({
                    method: 'POST',
                    url: "{{ action('ChequesController@getNewChequeRow') }}",
                    dataType: 'html',
                    data: {
                        _token: "{{ csrf_token() }}",
                        index: index
                    },
                    success: function (response) {
                        $('#box-body').prepend(response);
                        //Date picker
                        $('#date').datepicker({
                            autoclose: true
                        });

                        $('#issued_date').datepicker({
                            autoclose: true
                        });
                        $('.select2').select2();
                        $('#index').val(index);
                    }
                });
            });

            $(document).on('click', '#remove-cheque', function () {
                let index = $('#index').val();
                $(this).closest('.row').remove();
                index--;
                $('#index').val(index);
                let total_input_amount = getAllInputAmount();
                let final_amount = $('#final_total').val();
                amountValidation(total_input_amount, final_amount);
            });

        });
    </script>
@endsection