@extends('layouts.app')
@section('title', __('Product Requirement'))

@section('content')

    {{-- <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('Filter')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('type', 'Condition Type:') !!}
                        {!! Form::select('type', ['good' => __('business.machine_good'), 'medium' => __('business.machine_medium'), 'low' => __('business.machine_low'), 'need_to_repaire' => __('business.machine_need_to_repaire')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'filter_condition', 'placeholder' => __('lang_v1.all')]) !!}
                    </div>
                </div>
            @endcomponent
        </div>
    </div> --}}

    <!-- Content Header (Page header) -->
    <section class="content-header no-print">
        <h1>@lang('Product Requirement Report')
        </h1>
    </section>

    <!-- Main content -->
    <section class="content no-print">
        <div class="row">
            <div class="col-md-12">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="active">
                            <a href="#finish_product_tab" data-toggle="tab" aria-expanded="true"><i
                                    class="fa fa-list" aria-hidden="true"></i> @lang('Finish Product Requirement')</a>
                        </li>
                        <li>
                            <a href="#ingredient_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-list"
                                    aria-hidden="true"></i> @lang('Ingrediant Requirement')</a>
                        </li>
                    </ul>
                    <br><br>
                    <div class="tab-content">
                        <div class="tab-pane active" id="finish_product_tab">
                            <div class="table-responsive">
                                @if (session('business.enable_lot_number'))
                                    <input type="hidden" id="lot_enabled">
                                @endif
                                <table class="table table-bordered table-striped"
                                    id="finish_product_table" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th class="text-center">@lang('sale.product')</th>
                                            <th class="text-center">@lang('product.sku')</th>
                                            <th class="text-center">@lang('Current Stock')</th>
                                            <th class="text-center">@lang('Required Quantity (for complete sales orders)')</th>
                                            <th class="text-center">@lang('Remaining Quantity')</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane" id="ingredient_tab">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="ingredint_table" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th class="text-center">@lang('sale.product')</th>
                                            <th class="text-center">@lang('product.sku')</th>
                                            <th class="text-center">@lang('Current Stock')</th>
                                            <th class="text-center">@lang('Required Quantity (for complete sales orders)')</th>
                                            <th class="text-center">@lang('Remaining Quantity')</th>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Bottle</td>
                                            <td>0008</td>
                                            <td>920 Pc(s)</td>
                                            <td>400 Pc(s)</td>
                                            <td>0 Pc(s)</td>
                                        </tr>
                                        <tr>
                                            <td>Bottle</td>
                                            <td>0008</td>
                                            <td>920 Pc(s)</td>
                                            <td>400 Pc(s)</td>
                                            <td>0 Pc(s)</td>
                                        </tr>
                                        <tr>
                                            <td>Bottle</td>
                                            <td>0008</td>
                                            <td>820 Pc(s)</td>
                                            <td>750 Pc(s)</td>
                                            <td>0 Pc(s)</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- @include('stock_transfer.partials.update_status_modal') --}}
    <section id="receipt_section" class="print_section"></section>

    <!-- /.content -->
@stop
@section('javascript')
    <script>
        $(document).ready(function() {

            var finish_product_table = $('#finish_product_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '/reports/quick-requirement',
                // buttons: [],
                // "ajax": {
                //     "url": "/reports/quick-requirement",
                //     "data": function(d) {
                //         // d.condition = $('#filter_condition').val();
                //         // d = __datatable_ajax_callback(d);
                //     }
                // },
                // columnDefs: [{
                //     "targets": 2,
                //     "orderable": false,
                //     "searchable": false
                // }],
                columns: [{
                        data: 'product',
                        name: 'product'
                    },
                    {
                        data: 'sku',
                        name: 'sku'
                    },
                    {
                        data: 'current_stock',
                        name: 'current_stock'
                    },
                    {
                        data: 'order_quantity',
                        name: 'order_quantity'
                    },
                    {
                        data: 'remaining',
                        name: 'remaining'
                    },
                ],
            });

            var ingredint_table = $('#ingredint_table').DataTable({
                // processing: true,
                // serverSide: true,
                // ajax: '/reports/quick-requirement/ingredient-requirement',
                // buttons: [],
                // "ajax": {
                //     "url": "/machines",
                //     "data": function(d) {
                //         d.condition = $('#filter_condition').val();
                //         d = __datatable_ajax_callback(d);
                //     }
                // },
                // columnDefs: [{
                //     "targets": 2,
                //     "orderable": false,
                //     "searchable": false
                // }],
                // columns: [{
                //         data: 'product_name',
                //         name: 'product_name'
                //     },
                //     {
                //         data: 'product_sku',
                //         name: 'product_sku'
                //     },
                //     {
                //         data: 'product_current_stock',
                //         name: 'product_current_stock'
                //     },
                //     {
                //         data: 'product_order_quantity',
                //         name: 'product_order_quantity'
                //     },
                //     {
                //         data: 'product_remaining',
                //         name: 'product_remaining'
                //     },
                // ],
            });

            // filter dropdown change
            $(document).on('change', '#filter_condition', function() {
                machines_table.ajax.reload();
            });

        });
    </script>
@endsection
