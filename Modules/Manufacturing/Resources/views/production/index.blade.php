@extends('layouts.app')
@section('title', __('manufacturing::lang.production'))

@section('content')
@include('manufacturing::layouts.nav')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('manufacturing::lang.production')</h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-solid'])
        @slot('tool')
            <div class="box-tools">
                <a class="btn btn-block btn-primary" href="{{action('\Modules\Manufacturing\Http\Controllers\ProductionController@create')}}">
                    <i class="fa fa-plus"></i> @lang( 'messages.add' )</a>
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="productions_table">
                 <thead>
                    <tr>
                        <th class="text-center">@lang('Priority')</th>
                        <th class="text-center">@lang('messages.date')</th>
                        <th class="text-center">@lang('Job Sheet No')</th>
                        <th class="text-center">@lang('Badge No')</th>
                        <th class="text-center">@lang('sale.product')</th>
                        <th class="text-center">@lang('lang_v1.quantity')</th>
                        <th class="text-center">@lang('manufacturing::lang.total_cost')</th>
                        <th class="text-center">@lang('Status')</th>
                        <th class="text-center">@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>
<!-- /.content -->
<div class="modal fade" id="recipe_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

{{-- modal update status --}}
<div class="modal fade" id="modal_update_status" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Update status</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-1"></div>
                    <div class="col-md-10">
                        {!! Form::label('', __('Select Status') . ':') !!}
                        <select class="form-control" id="dropdown_status">
                            <option value="process">In Progress</option>
                            <option value="hold">Hold  Process</option>
                        </select>
                    </div>
                    <div class="col-md-1"></div>
                </div>
            </div>
            <div class="modal-footer">
                <input type="text" name="tr_id" id="tr_id" hidden>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btn_change_status">Save</button>
            </div>
        </div>
    </div>
</div>

{{-- modal update status --}}
<div class="modal fade" id="modal_update_priority" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Update priority</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-1"></div>
                    <div class="col-md-10">
                        {!! Form::label('', __('Select priority type') . ':') !!}
                        <select class="form-control" id="dropdown_priority">
                            <option value="non_urgent">Non Urgent</option>
                            <option value="urgent">Urgent</option>
                            <option value="top_urgent">Top Urgent</option>
                        </select>
                    </div>
                    <div class="col-md-1"></div>
                </div><br><br>
            </div>
            <div class="modal-footer">
                <input type="text" name="trs_id" id="trs_id" hidden>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btn_change_priority">Save</button>
            </div>
        </div>
    </div>
</div>

@stop
@section('javascript')
    @include('manufacturing::layouts.partials.common_script')
@endsection
