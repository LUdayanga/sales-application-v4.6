<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="modalTitle"> @lang('account.cheque_details') (<b>@lang('account.cheque_number')
                    :</b> #{{ $cheque->cheque_number }})
            </h4>
        </div>
        <div class="modal-body">
            <div class="row invoice-info">
                <div class="col-sm-4 invoice-col">
                    <b>@lang('account.cheque_number'):</b> #{{ $cheque->cheque_number }}<br/>
                    <b>@lang('messages.date'):</b> {{ @format_date($cheque->cheque_date) }}<br/>
                    <b>@lang('account.issued_date'):</b> {{ @format_date($cheque->cheque_issued_date) }}<br/>
                    <b>@lang('sale.status'):</b> {{ $cheque->cheque_status }}<br/>
                    <b>@lang('account.cheque_type'):</b> {{ $cheque->cheque_status }}<br/>
                    <b>@lang('sale.total_amount'):</b> {{ number_format($cheque->cheque_amount, 2) }}
                </div>
            </div>

            <br>
            <div class="row">
                <div class="col-xs-12">
                    <div class="table-responsive">
                        <table class="table bg-gray">
                            <tr class="bg-green">
                                <th>#</th>
                                <th>@lang('sale.invoice_no')</th>
                                <th>@lang('sale.total_amount')</th>
                                <th>@lang('messages.date')</th>
                                <th>@lang('sale.payment_status')</th>
                            </tr>
                            @php
                                $total = 0.00;
                            @endphp
                            @foreach($cheque_transactions as $cheque_transaction)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ !empty($cheque_transaction->invoice_no)?$cheque_transaction->invoice_no:$cheque_transaction->ref_no }}</td>
                                    <td>{{ number_format($cheque_transaction->final_total, 2) }}</td>
                                    <td>{{ $cheque_transaction->transaction_date }}</td>
                                    <td>{{ $cheque_transaction->payment_status }}</td>
                                </tr>
                                @php
                                    $total += $cheque_transaction->final_total;
                                @endphp
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
            <br>
            <div class="row">

                <div class="col-xs-12 col-md-6 col-md-offset-6">
                    <div class="table-responsive">
                        <table class="table">
                            <tr>
                                <th>@lang('purchase.net_total_amount'):</th>
                                <td></td>
                                <td><span class="display_currency pull-right"
                                          data-currency_symbol="true">{{ $total }}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary no-print" aria-label="Print"
                    onclick="$(this).closest('div.modal-content').printThis();"><i
                        class="fa fa-print"></i> @lang( 'messages.print' )
            </button>
            <button type="button" class="btn btn-default no-print"
                    data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>
    </div>
</div>