@extends('layouts.app')
@section('title', __('Stores for MRN'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header no-print">
        <h1>@lang('Stores for MRN')
        </h1>
    </section>

    <!-- Main content -->
    <section class="content no-print">
        <form method="post" id="form_save_details">
            {!! csrf_field() !!}
            @component('components.widget', ['class' => 'box-primary', 'title' => __('')])
                @slot('tool')
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('type', 'Select Job Sheet No:') !!}
                            <select class="form-control" id="mrn_dropdown">
                                <option value="">Please Select</option>
                                @foreach ($pending_mrns as $pending_mrn)
                                    <option value="{{ $pending_mrn->id }}">
                                        {{ $pending_mrn->ref_no }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endslot
                <label for="">Available lots details</label>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped ajax_view" id="table_mrn_details">
                        <thead>
                            <tr>
                                <th hidden style="text-align: center;">@lang('transaction id')</th>
                                <th hidden style="text-align: center;">@lang('Product id')</th>
                                <th hidden style="text-align: center;">@lang('variation id')</th>
                                <th style="text-align: center;">@lang('Ingrediant')</th>
                                <th style="text-align: center;">@lang('MRN Quantity')</th>
                                <th hidden style="text-align: center;">@lang('input mrn quantity')</th>
                                <th style="text-align: center;">@lang('Lot Number')</th>
                                <th hidden style="text-align: center;">@lang('input lot number')</th>
                                <th style="text-align: center;">@lang('Available Lot Quantity')</th>
                                <th hidden style="text-align: center;">@lang('input available auantity')</th>
                                <th style="text-align: center;">@lang('Input Quantity')</th>
                                <th style="text-align: center;">@lang('')</th>

                                <th hidden style="text-align: center;">@lang('mfg_waste_percent')</th>
                                <th hidden style="text-align: center;">@lang('mfg_ingredient_group_id')</th>
                                <th hidden style="text-align: center;">@lang('quantity_returned')</th>
                                <th hidden style="text-align: center;">@lang('unit_price_before_discount')</th>
                                <th hidden style="text-align: center;">@lang('unit_price')</th>
                                <th hidden style="text-align: center;">@lang('line_discount_type')</th>
                                <th hidden style="text-align: center;">@lang('line_discount_amount')</th>
                                <th hidden style="text-align: center;">@lang('unit_price_inc_tax')</th>
                                <th hidden style="text-align: center;">@lang('item_tax')</th>
                                <th hidden style="text-align: center;">@lang('tax_id')</th>
                                <th hidden style="text-align: center;">@lang('discount_id')</th>
                                <th hidden style="text-align: center;">@lang('lot_no_line_id')</th>
                                <th hidden style="text-align: center;">@lang('sell_line_note')</th>
                                <th hidden style="text-align: center;">@lang('so_line_id')</th>
                                <th hidden style="text-align: center;">@lang('so_quantity_invoiced')</th>
                                <th hidden style="text-align: center;">@lang('woocommerce_line_items_id')</th>
                                <th hidden style="text-align: center;">@lang('res_service_staff_id')</th>
                                <th hidden style="text-align: center;">@lang('res_line_order_status')</th>
                                <th hidden style="text-align: center;">@lang('parent_sell_line_id')</th>
                                <th hidden style="text-align: center;">@lang('children_type')</th>
                                <th hidden style="text-align: center;">@lang('sub_unit_id')</th>
                                <th hidden style="text-align: center;">@lang('ingredient_type')</th>
                            </tr>
                        </thead>
                        <tbody id="tbl_mrn_details"></tbody>
                    </table>
                </div>
                <br>
                <div class="row">
                    <div class="col-sm-8 col-sm-offset-4">
                        <button type="button" id="btn_save" class="btn btn-primary pull-right">Save</button>
                    </div>
                </div>
            </form>
        @endcomponent
    </section>
    <section id="receipt_section" class="print_section"></section>

    <!-- /.content -->
@stop
@section('javascript')
    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $(document).on('change', '#mrn_dropdown', function() {
                var tr_purchase_id = $(this).val();

                $.ajax({
                    method: 'post',
                    url: '/StoresController/load_ingrediants',
                    data: {
                        tr_purchase_id: tr_purchase_id
                    },

                    success: function(data) {
                        $("#tbl_mrn_details").empty();
                        data.forEach(function(row) {
                            var table_row = "<tr>";
                            table_row += "<td hidden><input type='text' value=" + row
                                .transaction_id + " name='transaction_id[]'></td>";
                            table_row += "<td hidden><input type='text' value=" + row
                                .product_id + " name='product_id[]'></td>";
                            table_row += "<td hidden><input type='text' value=" + row
                                .variation_id + " name='variation_id[]'></td>";
                            table_row += "<td class='col-sm-2'>" + row.name + " (" +
                                row
                                .sku + ")" + "</td>";
                            table_row += "<td class='col-sm-2'>" + row.quantity +
                                "</td>";

                            table_row += "<td hidden><input type='number' value=" + row
                                .quantity + " name='mrn_qty[]'></td>";

                            table_row += "<td class='col-sm-2'>" + row.lot_number +
                                "</td>";

                            table_row += "<td hidden><input type='text' value=" + row
                                .lot_number + " name='lot_number[]'></td>";

                            table_row += "<td class='col-sm-2'>" + parseFloat(row
                                    .qty_available).toFixed(2) +
                                "</td>";

                            table_row += "<td hidden><input type='number' value=" + parseFloat(
                                    row
                                    .qty_available).toFixed(2) +
                                " name='available_qty[]'></td>";

                            table_row +=
                                "<td><input type='number' class='col-sm-2 form-control input_qty' value='' name='input_qty[]' required></td>";
                            table_row +=
                                "<td class='col-sm-1 text-center'><i class='fa fa-trash remove_product_row cursor-pointer' aria-hidden='true'></i></td>";
                                ////////////////////////////////////
                            table_row += "<td hidden><input type='text' class='col-sm-2 form-control' value=" + row.mfg_waste_percent + " name='mfg_waste_percent[]'></td>";
                            table_row += "<td hidden><input type='text' class='col-sm-2 form-control' value=" + row.mfg_ingredient_group_id + " name='mfg_ingredient_group_id[]'></td>";
                            table_row += "<td hidden><input type='text' class='col-sm-2 form-control' value=" + row.quantity_returned + " name='quantity_returned[]'></td>";
                            table_row += "<td hidden><input type='text' class='col-sm-2 form-control' value=" + row.unit_price_before_discount + " name='unit_price_before_discount[]'></td>";
                            table_row += "<td hidden><input type='text' class='col-sm-2 form-control' value=" + row.unit_price + " name='unit_price[]'></td>";
                            table_row += "<td hidden><input type='text' class='col-sm-2 form-control' value=" + row.line_discount_type + " name='line_discount_type[]'></td>";
                            table_row += "<td hidden><input type='text' class='col-sm-2 form-control' value=" + row.line_discount_amount + " name='line_discount_amount[]'></td>";
                            table_row += "<td hidden><input type='text' class='col-sm-2 form-control' value=" + row.unit_price_inc_tax + " name='unit_price_inc_tax[]'></td>";
                            table_row += "<td hidden><input type='text' class='col-sm-2 form-control' value=" + row.item_tax + " name='item_tax[]'></td>";
                            table_row += "<td hidden><input type='text' class='col-sm-2 form-control' value=" + row.tax_id + " name='tax_id[]'></td>";
                            table_row += "<td hidden><input type='text' class='col-sm-2 form-control' value=" + row.discount_id + " name='discount_id[]'></td>";
                            table_row += "<td hidden><input type='text' class='col-sm-2 form-control' value=" + row.lot_no_line_id + " name='lot_no_line_id[]'></td>";
                            table_row += "<td hidden><input type='text' class='col-sm-2 form-control' value=" + row.sell_line_note + " name='sell_line_note[]'></td>";
                            table_row += "<td hidden><input type='text' class='col-sm-2 form-control' value=" + row.so_line_id + " name='so_line_id[]'></td>";
                            table_row += "<td hidden><input type='text' class='col-sm-2 form-control' value=" + row.so_quantity_invoiced + " name='so_quantity_invoiced[]'></td>";
                            table_row += "<td hidden><input type='text' class='col-sm-2 form-control' value=" + row.woocommerce_line_items_id + " name='woocommerce_line_items_id[]'></td>";
                            table_row += "<td hidden><input type='text' class='col-sm-2 form-control' value=" + row.res_service_staff_id + " name='res_service_staff_id[]'></td>";
                            table_row += "<td hidden><input type='text' class='col-sm-2 form-control' value=" + row.res_line_order_status + " name='res_line_order_status[]'></td>";
                            table_row += "<td hidden><input type='text' class='col-sm-2 form-control' value=" + row.parent_sell_line_id + " name='parent_sell_line_id[]'></td>";
                            table_row += "<td hidden><input type='text' class='col-sm-2 form-control' value=" + row.children_type + " name='children_type[]'></td>";
                            table_row += "<td hidden><input type='text' class='col-sm-2 form-control' value=" + row.sub_unit_id + " name='sub_unit_id[]'></td>";
                            table_row += "<td hidden><input type='text' class='col-sm-2 form-control' value=" + row.ingredient_type + " name='ingredient_type[]'></td>";
                            
                            table_row += "</tr>";
                            $("#tbl_mrn_details").append(table_row);
                        });
                    }

                });
            });

            //validate table input fields
            $(document).on("keyup", ".input_qty", function() {
                if (parseFloat($(this).closest('tr').find("td:eq(5) input[type='number']").val()) <
                    parseFloat($(this).closest('tr').find("td:eq(10) input[type='number']").val()) ||
                    parseFloat($(this).closest('tr').find("td:eq(9) input[type='number']").val()) <
                    parseFloat($(this).closest('tr').find("td:eq(10) input[type='number']").val())) {
                    $(this).closest('tr').find("td:eq(10) input[type='number']").val('');
                }
            });

            //update details
            $('#btn_save').click(function() {
                var isValide = true;

                $('#tbl_mrn_details tr').each(function() {
                    if ($(this).find("td:eq(10) input[type='number']").val() == '') {
                        isValide = false;
                        return false;
                    } else {
                        isValide = true;
                    }
                });

                if (isValide == true) {

                    $.ajax({
                        method: 'post',
                        url: '/StoresController/save_details',
                        data: $("#form_save_details").serialize(),

                        success: function(response) {
                            if (response.success == true) {
                                toastr.success(response.msg);
                                location.reload();
                            } else {
                                toastr.error(response.msg);
                            }
                        }

                    });

                } else {
                    toastr.error('Fill input quantity');
                }
            });

            $(document).on('click', '.remove_product_row', function() {
                swal({
                    title: 'Are you sure remove product?',
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(willDelete => {
                    if (willDelete) {
                        $(this)
                            .closest('tr')
                            .remove();
                    }
                });
            });

        });
    </script>
@endsection
