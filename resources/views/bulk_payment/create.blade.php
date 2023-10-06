@extends('layouts.app')
@section('title', __('account.create_bulk_payment'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('account.create_bulk_payment')</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <form action="{{ route('bulk-payment.store') }}" method="post">
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
				                    {!! Form::hidden('location_id', !empty($default_location) ? $default_location->id : null , 
                                    ['id' => 'location_id']); !!}
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
                            <input type="hidden" id="final_total" value="0">
                            <input type="hidden" id="total_paying" value="0">
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

            @can('sell.payments')
                <div class="row">
                <div class="col-md-12">
                    <!-- general form elements -->
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('account.cheque_details')</h3>
                            <div class="box-tools">
                                <a id="add-payment-row" class="btn btn-block btn-primary" href="javascript:void(0)">
                                    <i class="fa fa-plus"></i> @lang('sale.add_payment_row')</a>
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
                                            <h3 class="box-title">@lang('lang_v1.payment')</h3>
                                        </div>
                                        <!-- /.box-header -->
                                        <!-- form start -->
                                        <div class="box-body">
                                            {{-- content goes here --}}
<div class="row">
					{{-- <div class="col-md-12 mb-12">
						<strong>@lang('lang_v1.advance_balance'):</strong> <span id="advance_balance_text"></span>
						{!! Form::hidden('advance_balance', null, ['id' => 'advance_balance', 'data-error-msg' => __('lang_v1.required_advance_balance_not_available')]); !!}
					</div> --}}
					<div class="col-md-12">
						<div class="row">
							<div id="payment_rows_div">
								@foreach($payment_lines as $payment_line)
									
									@if($payment_line['is_return'] == 1)
										@php
											$change_return = $payment_line;
										@endphp

										@continue
									@endif

									@include('sale_pos.partials.payment_row', ['removable' => !$loop->first, 'row_index' => $loop->index, 'payment_line' => $payment_line])
								@endforeach
							</div>
							<input type="hidden" id="payment_row_index" value="{{count($payment_lines)}}">
						</div>
						{{-- <div class="row">
							<div class="col-md-12">
								<button type="button" class="btn btn-primary btn-block" id="add-payment-row">@lang('sale.add_payment_row')</button>
							</div>
						</div> --}}
						<br>
						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									{!! Form::label('sale_note', __('sale.sell_note') . ':') !!}
									{!! Form::textarea('sale_note', !empty($transaction)? $transaction->additional_notes:null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => __('sale.sell_note')]); !!}
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group">
									{!! Form::label('staff_note', __('sale.staff_note') . ':') !!}
									{!! Form::textarea('staff_note', 
									!empty($transaction)? $transaction->staff_note:null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => __('sale.staff_note')]); !!}
								</div>
							</div>
						</div>
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
                            <button id="create-button" type="submit" class="btn btn-info pull-right">Create Bulk Payment
                            </button>
                        </div>
                        <!-- /.box-footer -->
                    </div>
                    <!-- /.box -->
                </div>
            </div>
            @endcan

        </form>
    </section>
@endsection
@section('javascript')
    <script>
        $(document).ready(function () {
            //Date picker
            $('.paid_on').datetimepicker({
                format: moment_date_format + ' ' + moment_time_format,
                ignoreReadonly: true,
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
                    $('#total_paying').val(final_total);
                    $('.payment-amount').val(final_total).focus().select();
                    amountValidation(final_total);

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

            $(document).on('click', '#add-payment-row', function() {
                var row_index = $('#payment_row_index').val();
                var location_id = $('input#location_id').val();
                $.ajax({
                    method: 'POST',
                    url: '/sells/pos/get_payment_row',
                    data: { row_index: row_index, location_id: location_id },
                    dataType: 'html',
                    success: function(result) {
                        if (result) {
                            
                            var appended = $('#payment_rows_div').append(result);

                            var total_payable = __read_number($('input#final_total'));
                            var total_paying = __read_number($('input#total_paying'));
                            var b_due = total_payable - total_paying;
                            $(appended)
                                .find('input.payment-amount')
                                .focus();
                            $(appended)
                                .find('input.payment-amount')
                                .last()
                                .val(b_due)
                                .change()
                                .select();
                            __select2($(appended).find('.select2'));
                            $(appended).find('#method_' + row_index).change();
                            $('#payment_row_index').val(parseInt(row_index) + 1);
                            amountValidation(total_payable);
                        }
                    },
                });
            })

            $(document).on('click', '.remove_invoice', function () {
                $(this).closest('tr').remove();
                getFinalTotal();
                let final_amount = $('#final_total').val();
                amountValidation(final_amount);
            });

            $(document).on('click', '.remove_payment_row', function() {
                swal({
                    title: LANG.sure,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(willDelete => {
                    if (willDelete) {
                        $(this)
                            .closest('.payment_row')
                            .remove();
                        // calculate_balance_due();
                        getFinalTotal();
                    }
                });
            });


            function getFinalTotal() {
                let final_total = 0;
                $('table#cheque-related-invoice-table tbody tr #invoice_total').each(function () {
                    final_total += parseFloat($(this).val());
                });
                
                $('#final_total').val(final_total);
                $('#total_paying').val(final_total);
                $('.payment-amount').val(final_total);
                $('#footer_final_total').text(__number_f(final_total));
            }

            $(document).on('input', '.payment-amount', function () {
                // let total_amount = $(this).val();
                let final_amount = $('#final_total').val();
                amountValidation(final_amount);
            })

            function amountValidation(final_amount) {
                let total_payment = 0;
                $('.payment-amount').each(function() {
                    if ($(this).val() !== '') {
                        total_payment += parseFloat($(this).val());
                    }
                });
            
                let balance = final_amount - total_payment;
                $('.balance_due').text(__number_f(balance))
                $('#total_paying').val(total_payment);
                if (parseFloat(final_amount) === parseFloat(total_payment)){
                    $('#create-button').attr('disabled', false);
                }else{
                    $('#create-button').attr('disabled', true);
                }
                if (parseFloat(final_amount) === 0) {
                    $('#create-button').attr('disabled', true);
                    
                }
            }

        });
    </script>
@endsection