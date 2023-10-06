@extends('layouts.app')
@section('title', __('account.edit_cheque'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('account.edit_cheque')</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <form action="{{ route('cheque.update', $cheque->id) }}" method="post">
            @csrf
            @method('PUT')
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
                                    {!! Form::select('contact_id', $contacts, $cheque->contact_id, ['class' => 'form-control select2', 'id' => 'contact_id', 'required', 'disabled', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                                    <input type="hidden" name="contact_id" value="{{ $cheque->contact_id }}">
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
                            <input type="hidden" id="final_total" value="{{ $cheque_transactions->sum('final_total') }}">
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
                                @foreach($cheque_transactions as $cheque_transaction)
                                    <tr>
                                        <td>
                                            <input id="" name="transactions[]" value="{{ $cheque_transaction->id }}"
                                                   type="hidden">
                                            <input id="invoice_total" value="{{ $cheque_transaction->final_total }}" type="hidden">
                                            {{ !empty($cheque_transaction->invoice_no)?$cheque_transaction->invoice_no:$cheque_transaction->ref_no }}
                                        </td>
                                        <td>{{ number_format($cheque_transaction->final_total, 2) }}</td>
                                        <td>{{ $cheque_transaction->transaction_date }}</td>
                                        <td>
                                            @if($cheque_transaction->payment_status == 'due')
                                                <span class="label label-warning">Due</span>
                                            @elseif($cheque_transaction->payment_status == 'partial')
                                                <span class="label label-info">Partial</span>
                                            @elseif($cheque_transaction->payment_status == 'paid')
                                                <span class="label label-success">Paid</span>
                                            @else
                                                <span class="label label-danger">Returned</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-danger remove_invoice"><i
                                                        class="fa fa-trash"></i></button>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <th>@lang('sale.total_amount')</th>
                                    <th id="footer_final_total">{{ number_format($cheque_transactions->sum('final_total'), 2) }}</th>
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
                        </div>
                        <!-- /.box-header -->
                        <!-- form start -->
                        <div class="box-body">
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::label("account_id" , __('lang_v1.payment_account') . ':*') !!}
                                    {!! Form::select('account_id', $accounts, $cheque->account_id, ['class' => 'form-control select2', 'required', 'style' => 'width:100%']); !!}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="cheque_number">@lang('account.cheque_number'):*</label>
                                    <input type="text" class="form-control" id="cheque_number" name="cheque_number"
                                           placeholder="Enter Cheque Number" value="{{ $cheque->cheque_number }}"
                                           required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('account.date'):*</label>

                                    <div class="input-group date">
                                        <div class="input-group-addon">
                                            <i class="fa fa-calendar"></i>
                                        </div>
                                        <input type="text" name="cheque_date" class="form-control pull-right" id="date"
                                               value="{{ \Carbon\Carbon::parse($cheque->cheque_date)->format('m/d/Y') }}"
                                               required>
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
                                        <input type="text" name="cheque_issued_date" class="form-control pull-right"
                                               id="issued_date"
                                               value="{{ \Carbon\Carbon::parse($cheque->cheque_issued_date)->format('m/d/Y') }}"
                                               required>
                                    </div>
                                    <!-- /.input group -->
                                </div>
                                <!-- /.form group -->
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="total_amount">@lang('sale.total_amount'):*</label>
                                    <input type="text" class="form-control" name="cheque_amount" id="total_amount"
                                           placeholder="Enter Total" value="{{ $cheque->cheque_amount }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('account.cheque_type'):*</label>
                                    <select class="form-control select2" style="width: 100%;" name="cheque_type"
                                            required>
                                        <option value="giving" {{ ($cheque->cheque_type == 'giving')?"selected":'' }}>
                                            Issued
                                        </option>
                                        <option value="receiving" {{ ($cheque->cheque_type == 'receiving')?"selected":'' }}>
                                            Received
                                        </option>
                                    </select>
                                </div>
                                <!-- /.form-group -->
                            </div>
{{--                            <div class="clearfix"></div>--}}
{{--                            <div class="col-md-4">--}}
{{--                                <div class="form-group">--}}
{{--                                    <label>@lang('account.cheque_status'):*</label>--}}
{{--                                    <select class="form-control select2" style="width: 100%;" name="cheque_status"--}}
{{--                                            required>--}}
{{--                                        <option value="due" {{ ($cheque->cheque_status == 'due')?"selected":'' }}>Due--}}
{{--                                        </option>--}}
{{--                                        <option value="partial" {{ ($cheque->cheque_status == 'partial')?"selected":'' }}>--}}
{{--                                            Partial--}}
{{--                                        </option>--}}
{{--                                        <option value="paid" {{ ($cheque->cheque_status == 'paid')?"selected":'' }}>--}}
{{--                                            Paid--}}
{{--                                        </option>--}}
{{--                                        <option value="returned" {{ ($cheque->cheque_status == 'returned')?"selected":'' }}>--}}
{{--                                            Returned--}}
{{--                                        </option>--}}
{{--                                    </select>--}}
{{--                                </div>--}}
{{--                                <!-- /.form-group -->--}}
{{--                            </div>--}}
                        </div>
                        <!-- /.box-body -->
                        <div class="box-footer">
                            <button type="button" class="btn btn-default pull-right margin-left-10">Cancel</button>
                            <button id="create-button" type="submit" class="btn btn-info pull-right">Update Cheque</button>
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

            $('#cheque-related-invoice-table').DataTable();

            {{--let cheque_related_invoice_table = $('#cheque-related-invoice-table').DataTable({--}}
            {{--    processing: true,--}}
            {{--    serverSide: true,--}}
            {{--    ajax: {--}}
            {{--        method: 'GET',--}}
            {{--        url: "{{ action('ChequesController@getInvoicesRelatedToContact') }}",--}}
            {{--        data: function (d) {--}}
            {{--            d.contact_id = $('#contact_id').val();--}}
            {{--        }--}}
            {{--    },--}}
            {{--    columnDefs: [{--}}
            {{--        "targets": [4],--}}
            {{--        "orderable": true,--}}
            {{--        'searchable': false--}}
            {{--    }],--}}
            {{--    "footerCallback": function (row, data, start, end, display) {--}}
            {{--        var api = this.api(), data;--}}
            {{--        // converting to interger to find total--}}
            {{--        var intVal = function (i) {--}}
            {{--            return typeof i === 'string' ?--}}
            {{--                i.replace(/[\$,]/g, '') * 1 :--}}
            {{--                typeof i === 'number' ?--}}
            {{--                    i : 0;--}}
            {{--        };--}}

            {{--        // final_total--}}
            {{--        let final_total = api.column(1).data().reduce(function (a, b) {--}}
            {{--            return intVal(a) + intVal(b);--}}
            {{--        }, 0);--}}

            {{--        $(api.column(1).footer()).html(numFormat(final_total));--}}

            {{--    },--}}
            {{--    columns: [--}}
            {{--        {name: 'invoice_no', data: 'invoice_no'},--}}
            {{--        {name: 'final_total', data: 'final_total'},--}}
            {{--        {name: 'transaction_date', data: 'transaction_date'},--}}
            {{--        {name: 'payment_status', data: 'payment_status'},--}}
            {{--        {name: 'action', data: 'action', searchable: false}--}}
            {{--    ]--}}
            {{--});--}}

            {{--let numFormat = $.fn.dataTable.render.number('\,', '.', 2, '').display;--}}

            {{--$(document).on('change', '#contact_id', function () {--}}
            {{--    cheque_related_invoice_table.ajax.reload();--}}
            {{--});--}}

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

            $(document).on('input', '#total_amount', function () {
                let total_amount = $(this).val();
                let final_amount = $('#final_total').val();
                amountValidation(total_amount, final_amount);
            })

            function amountValidation(total_amount, final_amount) {

                if (parseFloat(final_amount) === parseFloat(total_amount)){
                    $('#create-button').attr('disabled', false);
                }else{
                    $('#create-button').attr('disabled', true);
                }
            }

        });
    </script>
@endsection