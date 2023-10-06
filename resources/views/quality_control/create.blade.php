@extends('layouts.app')
@section('title', __('Quality Control'))

@section('content')
    <style type="text/css">

    </style>
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('Create Quality Control')</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        {!! Form::open(['url' => action('CashRegisterController@store'), 'method' => 'post', 'id' => 'add_cash_register_form']) !!}
        <div class="box box-solid">
            <div class="box-body">
                <br><br><br>
                <div class="row">
                    <div class="col-sm-6 col-sm-offset-3">
                        <div class="form-group">
                            {!! Form::label('', __('Quality Control Step') . ':*') !!}
                            <select class="form-control" id="qc_step_dropdown">
                                <option value="">Please Select</option>
                                @if (auth()->user()->can('qc.1'))
                                    <option value="qc_1">Receiving the quality check</option>
                                @endif

                                @if (auth()->user()->can('qc.2'))
                                    <option value="qc_2">Material approval for production</option>
                                @endif

                                @if (auth()->user()->can('qc.3'))
                                    <option value="qc_3">1st product approval </option>
                                @endif

                                @if (auth()->user()->can('qc.4'))
                                    <option value="qc_4">Final product quality approval</option>
                                @endif

                                @if (auth()->user()->can('qc.5'))
                                    <option value="qc_5">Material balance</option>
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6 col-sm-offset-3" id="div_grn" style="display: none;">
                        <div class="form-group">
                            {!! Form::label('location_id', __('Select Grn') . ':') !!}
                            <select class="form-control" id="grn_dropdown">
                                <option value="">Please Select</option>
                                @foreach ($grn_numbers as $grn_number)
                                    <option value="{{ $grn_number->id }}">{{ $grn_number->ref_no }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-6 col-sm-offset-3" id="div_qc_2" style="display: none" ;>
                        <div class="form-group">
                            {!! Form::label('location_id', __('Select MRN') . ':') !!}
                            <select class="form-control" id="qc2_dropdown">
                                <option value="">Please Select</option>
                                @foreach ($release_productions as $release_production)
                                    <option value="{{ $release_production->id }}">{{ $release_production->ref_no }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-6 col-sm-offset-3" id="div_qc_3" style="display: none" ;>
                        <div class="form-group">
                            {!! Form::label('location_id', __('Select Ref No') . ':') !!}
                            <select class="form-control" id="qc3_dropdown">
                                <option value="">Please Select</option>
                                @foreach ($hold_productions as $hold_production)
                                    <option value="{{ $hold_production->id }}">{{ $hold_production->ref_no }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-6 col-sm-offset-3" id="div_qc_4" style="display: none" ;>
                        <div class="form-group">
                            {!! Form::label('', __('Select Badge No') . ':') !!}
                            <select class="form-control" id="qc4_dropdown">
                                <option value="">Please Select</option>
                                @foreach ($production_completes as $production_complete)
                                    <option value="{{ $production_complete->transaction_id }}">
                                        {{ $production_complete->lot_number }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-6 col-sm-offset-3" id="div_qc_5" style="display: none" ;>
                        <div class="form-group">
                            {!! Form::label('location_id', __('Select Badge No') . ':') !!}
                            <select class="form-control" id="qc5_dropdown">
                                <option value="">Please Select</option>
                                @foreach ($production_qcs as $production_qc)
                                    <option value="{{ $production_qc->transaction_id }}">
                                        {{ $production_qc->lot_number }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6 col-sm-offset-3">
                        <button type="button" id="btn_check_qc" class="btn btn-primary pull-right">Check QC</button>
                    </div>
                </div>
            </div>
        </div>
        {!! Form::close() !!}
    </section>
@stop

<!-- Modal -->
<div class="modal fade" id="qc_add_modal" data-backdrop="false" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modal_header"></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="post" id="form_save_details">
                    {!! csrf_field() !!}
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
                                                <th class="col-sm-5">Description</th>
                                                <th class="delete_field">Action</th>
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
                                                <input type="checkbox" class="input-icheck" id="qc_finalize"
                                                    name="qc_finalize" value="2">
                                                <label for="">@lang('Approve')</label>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
                <input type="text" name="production_sheet_no" id="production_sheet_no" placeholder="" hidden>
                <input type="text" name="qc_type" id="qc_type" placeholder="qc type" hidden>
                <input type="text" name="qc_step" id="qc_step" placeholder="qc step" hidden>
                <button type="button" class="btn btn-secondary close_modal" data-dismiss="modal">Close</button>
                <button type="button" id="btn_save" class="btn btn-primary">Save</button>
            </div>
            </form>
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
                <input type="text" name="parameter_product_id" id="parameter_product_id" hidden>
                <input type="text" name="parameter_product_lot" id="parameter_product_lot" hidden>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary save_qc_parameter">Set Parameter</button>
            </div>
            </form>
        </div>
    </div>
</div>

@section('javascript')
    <script src="{{ asset('js/qualitiControl.js?v=' . $asset_v) }}"></script>
@endsection
