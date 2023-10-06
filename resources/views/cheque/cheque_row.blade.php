<div class="row">
    <div class="col-md-12">
        <!-- general form elements -->
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('account.cheque')</h3>
                <div class="box-tools">
                    <a id="remove-cheque" class="btn btn-block btn-danger"
                       href="javascript:void(0)">
                        <i class="fa fa-trash"></i> @lang('account.remove_cheque')</a>
                </div>
            </div>
            <!-- /.box-header -->
            <!-- form start -->
            <div class="box-body">
                <div class="cheque">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label("cheques[$index][account_id]" , __('lang_v1.payment_account') . ':*') !!}
                            {!! Form::select("cheques[$index][account_id]", $accounts, null, ['class' => 'form-control select2', 'required', 'style' => 'width:100%']); !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="cheque_number">@lang('account.cheque_number')
                                :*</label>
                            <input type="text" class="form-control" id="cheque_number"
                                   name="cheques[{{$index}}][cheque_number]"
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
                                <input type="text" name="cheques[{{$index}}][cheque_date]" autocomplete="off"
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
                                <input type="text" name="cheques[{{$index}}][cheque_issued_date]" autocomplete="off"
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
                            <input type="text" class="form-control" name="cheques[{{$index}}][cheque_amount]"
                                   id="total_amount"
                                   placeholder="Enter Total" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>@lang('account.cheque_type'):*</label>
                            <select class="form-control select2" style="width: 100%;"
                                    name="cheques[{{$index}}][cheque_type]" required>
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