<div class="row">
	<input type="hidden" class="payment_row_index" value="{{ $row_index}}">
	@php
		$col_class = 'col-md-6';
		if(!empty($accounts)){
			$col_class = 'col-md-4';
		}
		$readonly = $payment_line['method'] == 'advance' ? true : false;
	@endphp
	<div class="{{$col_class}}">
		<div class="form-group">
			{!! Form::label("amount" ,__('sale.amount') . ':*') !!}
			<div class="input-group">
				<span class="input-group-addon">
					<i class="fas fa-money-bill-alt"></i>
				</span>
				{!! Form::text("amount", @num_format($payment_line['amount']), ['class' => 'form-control payment-amount input_number', 'required', 'id' => "amount", 'placeholder' => __('sale.amount'), 'readonly' => $readonly]); !!}
			</div>
		</div>
	</div>
	@if(!empty($show_date))
	<div class="{{$col_class}}">
		<div class="form-group">
			{!! Form::label("paid_on" , __('lang_v1.paid_on') . ':*') !!}
			<div class="input-group">
              <span class="input-group-addon">
                <i class="fa fa-calendar"></i>
              </span>
              {!! Form::text("paid_on", isset($payment_line['paid_on']) ? @format_datetime($payment_line['paid_on']) : @format_datetime('now'), ['class' => 'form-control paid_on', 'readonly', 'required']); !!}
            </div>
		</div>
	</div>
	@endif
	<div class="{{$col_class}}">
		<div class="form-group">
			{!! Form::label("method" , __('lang_v1.payment_method') . ':*') !!}
			<div class="input-group">
				<span class="input-group-addon">
					<i class="fas fa-money-bill-alt"></i>
				</span>
				@php
					$_payment_method = empty($payment_line['method']) && array_key_exists('cash', $payment_types) ? 'cash' : $payment_line['method'];
				@endphp
				{!! Form::select("method", $payment_types, $_payment_method, ['class' => 'form-control col-md-12 payment_types_dropdown', 'required', 'id' => !$readonly ? "method" : "method_advance", 'style' => 'width:100%;', 'disabled' => $readonly]); !!}

				@if($readonly)
					{!! Form::hidden("method", $payment_line['method'], ['class' => 'payment_types_dropdown', 'required', 'id' => "method"]); !!}
				@endif
			</div>
		</div>
	</div>
	@if(!empty($accounts))
		<div class="{{$col_class}}">
			<div class="form-group @if($readonly) hide @endif">
				{!! Form::label("account" , __('lang_v1.payment_account') . ':') !!}
				<div class="input-group">
					<span class="input-group-addon">
						<i class="fas fa-money-bill-alt"></i>
					</span>
					{!! Form::select("account_id", $accounts, !empty($payment_line['account_id']) ? $payment_line['account_id'] : '' , ['class' => 'form-control select2 account-dropdown', 'id' => !$readonly ? "account" : "account_advance", 'style' => 'width:100%;', 'disabled' => $readonly]); !!}
				</div>
			</div>
		</div>
	@endif
	<div class="clearfix"></div>
		@include('bulk_payment.partials.payment_type_details')
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("note", __('sale.payment_note') . ':') !!}
			{!! Form::textarea("note", $payment_line['note'], ['class' => 'form-control', 'rows' => 3, 'id' => "note"]); !!}
		</div>
	</div>
</div>