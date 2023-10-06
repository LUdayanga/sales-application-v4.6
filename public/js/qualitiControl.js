$(document).ready(function () {

    $.ajaxSetup({
        headers:
            { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    //Date picker
    $('#qc_date').datetimepicker({
        format: 'YYYY-MM-DD',
        ignoreReadonly: true,
    });

    $(document).on('click', '.close_modal', function(){
        $.ajax({
            method: 'post',
            url: '/quality-control/delete_tempory_parameters',

            success: function (response) {
            }

        });
    });

    //open product parameter modal
    $(document).on('click', '.btn_parameter', function () {
        var header_txt = $(this).closest('tr').find("td:eq(3)").text();
        var product_lot = $(this).closest('tr').find("td:eq(4) input[type='text']").val();
        var split = header_txt.split(')');
        var quality_id = null;
        var product_id = $(this).attr("data-val");
        var is_qc_save = 'false';

        $.ajax({
            method: 'post',
            url: '/quality-control/load_parameters',
            data: { product_id: product_id, quality_id: quality_id, is_qc_save: is_qc_save },

            success: function (data) {
                $("#tbody_table_parameters").empty();
                data.forEach(function (row) {

                    var table_row = "<tr>";

                    if (row.is_tempory == 'true') {
                        table_row +=
                            "<td hidden><input type='text' class='form-control' value=" +
                            row.parent_parameter_id +
                            " name='parent_parameter_id[]'></td>";
                    } else {
                        table_row +=
                            "<td hidden><input type='text' class='form-control' value=" +
                            row.parameter_parent_id +
                            " name='parent_parameter_id[]'></td>";
                    }

                    table_row += "<td class='col-sm-4'>" + row.parameter_name +
                        "</td>";


                    if (row.is_tempory == 'true') {
                        if(row.qc_param_status == 'pass'){
                            table_row += "<td class='col-sm-2'><select class='form-control' name='parameter_status[]'><option value='pass' selected>Pass</option><option value='fail'>Fail</option></select></td>";
                        }else{
                            table_row += "<td class='col-sm-2'><select class='form-control' name='parameter_status[]'><option value='pass'>Pass</option><option value='fail' selected>Fail</option></select></td>";
                        }
            
                        table_row += "<td><input type='text' class='form-control' name='parameter_description[]' value=" + row.qc_param_description + "></td>";
                    } else {
                        table_row += "<td class='col-sm-2'><select class='form-control' name='parameter_status[]'><option value='pass'>Pass</option><option value='fail'>Fail</option></select></td>";
                        table_row += "<td><input type='text' class='form-control' name='parameter_description[]'></td>";
                    }

                    table_row += "</tr>";

                    $("#tbody_table_parameters").append(table_row);
                });
            }

        });

        $('#parameter_product_id').val(product_id);
        $('#parameter_product_lot').val(product_lot);
        $('.parameter_header').text(split[0] + ') / [lot no: ' + product_lot + '] - ' + split[1]);
        $("#modal_parameter").modal();
    });

    //save product parameters
    $(document).on('click', '.save_qc_parameter', function () {

        $.ajax({
            method: 'post',
            url: '/quality-control/tempory_save_parameters',
            data: $("#form_prodcut_parametres").serialize(),

            success: function (response) {
                if (response.success == true) {
                    $("#modal_parameter").modal('hide');
                } else {
                    toastr.error(response.msg);
                }
            }

        });
    });

    //load save qc modal
    $("#btn_check_qc").click(function () {

        $("#form_save_details")[0].reset();

        if ($("#qc_step_dropdown option:selected").val() != '') {
            if ($("#qc_step_dropdown option:selected").val() == 'qc_1' && $("#grn_dropdown option:selected").val() != '') {
                var id = $("#grn_dropdown").val();
                var status = "grn";
                $.ajax({
                    method: 'post',
                    url: '/quality-control/load_product',
                    data: { id: id, status: status },

                    success: function (data) {
                        $("#tbl_tbody_product").empty();
                        data.forEach(function (row) {
                            $("#tbl_tbody_product").append(
                                '<tr>\
                                <td hidden><input type="text" value=' + row.transaction_id + ' name="transaction_id[]"></td>\
                                <td hidden><input type="text" value=' + row.product_id + ' name="product_id[]"></td>\
                                <td hidden><input type="text" value=' + row.variation_id + ' name="variation_id[]"></td>\
                                <td class="col-sm-2">'+ row.name + ' (' + row.sku + ')' + '<button type="button" data-val=' + row.product_id + ' class="btn btn-danger btn-xs btn_parameter" style="margin-top: 5px;">Product Parameter</button></td>\
                                <td hidden><input type="text" value=' + row.lot_number + ' name="lot_number[]"></td>\
                                <td class="col-sm-2">'+ row.lot_number + '</td>\
                                <td class="col-sm-1">'+ row.quantity + '</td>\
                                <td hidden><input type="number" value=' + row.quantity + ' name="quantity[]" readonly></td>\
                                <td class="col-sm-1"><input type="number" class="form-control checked_qty" value=' + row.quantity + ' name="checked_qty[]" id="checked_qty" readonly required></td>\
                                <td class="col-sm-1"><input type="number" class="form-control passed_qty" value=' + row.quantity + ' name="passed_qty[]" id="passed_qty" required></td>\
                                <td class="col-sm-5"><input type="text" class="form-control" name="fail_descrpition[]"></td>\
                                </tr>'
                            );
                        });
                    }

                });
                $("#qc_type").val("purchase");
                $("#qc_step").val("qcs_1");
                $("#modal_header").text("Quality Control 1 - Grn No: " + $("#grn_dropdown option:selected").text());
                // $(".search_product_row").hide();
                $(".production_column").hide();
                $(".delete_field").hide();
                $("#qc_add_modal").modal();
            } else if ($("#qc_step_dropdown option:selected").val() == 'qc_2') {

                var id = $("#qc2_dropdown").val();
                var status = "qc_2";

                $.ajax({
                    method: 'post',
                    url: '/quality-control/load_product',
                    data: { id: id, status: status },

                    success: function (data) {
                        $("#tbl_tbody_product").empty();
                        data.forEach(function (row) {
                            $("#tbl_tbody_product").append(
                                '<tr>\
                                <td hidden><input type="text" value=' + row.transaction_id + ' name="transaction_id[]"></td>\
                                <td hidden><input type="text" value=' + row.product_id + ' name="product_id[]"></td>\
                                <td hidden><input type="text" value=' + row.variation_id + ' name="variation_id[]"></td>\
                                <td class="col-sm-2">'+ row.name + ' (' + row.sku + ')' + '<button type="button" data-val=' + row.product_id + ' class="btn btn-danger btn-xs btn_parameter" style="margin-top: 5px;">Product Parameter</button></td>\
                                <td hidden><input type="text" value=' + row.mrn_lot + ' name="lot_number[]"></td>\
                                <td class="col-sm-2">'+ row.mrn_lot + '</td>\
                                <td class="col-sm-1">'+ parseFloat(row.input_quantity) + '</td>\
                                <td hidden><input type="number" value=' + parseFloat(row.input_quantity) + ' name="quantity[]" readonly></td>\
                                <td class="col-sm-1"><input type="number" class="form-control checked_qty" value=' + parseFloat(row.input_quantity) + ' name="checked_qty[]" id="checked_qty" readonly required></td>\
                                <td class="col-sm-1"><input type="number" class="form-control passed_qty" name="passed_qty[]" id="passed_qty" required></td>\
                                <td class="col-sm-5"><input type="text" class="form-control" name="fail_descrpition[]"></td>\
                                </tr>'
                            );
                        });
                    }

                });

                $("#qc_type").val("stock_transfer");
                $("#qc_step").val("qcs_2");
                $("#production_sheet_no").val($("#qc2_dropdown option:selected").text());
                $("#modal_header").text($("#qc_step_dropdown option:selected").text() + ' -  Stock Transfer');
                $(".production_column").hide();
                $(".delete_field").hide();
                $("#qc_add_modal").modal();
            } else if (($("#qc_step_dropdown option:selected").val() == 'qc_3' && $("#qc3_dropdown option:selected").val() != '')) {

                var id = $("#qc3_dropdown").val();
                var status = 'qc_3';

                $.ajax({
                    method: 'post',
                    url: '/quality-control/load_product',
                    data: { id: id, status: status },

                    success: function (data) {
                        $("#product_table_header").empty();
                        $("#product_table_header").append(
                            '<th hidden>transaction_id</th>\
                            <th hidden>produc_id</th>\
                            <th hidden>variation_id</th>\
                            <th class="col-sm-2">Product</th>\
                            <th hidden>lot_no_input</th>\
                            <th class="col-sm-2">Badge No</th>\
                            <th class="col-sm-1 production_column">Production Quantity</th>\
                            <th class="col-sm-1">Checked Quantity</th>\
                            <th class="col-sm-1">Passed Quantity</th>\
                            <th class="col-sm-5">Description</th>'
                        );

                        $("#tbl_tbody_product").empty();
                        data.forEach(function (row) {
                            $("#tbl_tbody_product").append(
                                '<tr>\
                                <td hidden><input type="text" value=' + row.transaction_id + ' name="transaction_id[]"></td>\
                                <td hidden><input type="text" value=' + row.product_id + ' name="product_id[]"></td>\
                                <td hidden><input type="text" value=' + row.variation_id + ' name="variation_id[]"></td>\
                                <td class="col-sm-2">'+ row.name + ' (' + row.sku + ')' + '<button type="button" data-val=' + row.product_id + ' class="btn btn-danger btn-xs btn_parameter" style="margin-top: 5px;">Product Parameter</button></td>\
                                <td hidden><input type="text" value=' + row.lot_number + ' name="lot_number[]"></td>\
                                <td class="col-sm-2">'+ row.lot_number + '</td>\
                                <td class="col-sm-1 production_column"><input type="number" class="form-control production_qty" name="quantity[]" value=' + parseFloat(row.quantity) + ' readonly></td>\
                                <td class="col-sm-1"><input type="number" class="form-control checked_qty" value=' + parseFloat(row.quantity) + ' name="checked_qty[]" id="checked_qty" required></td>\
                                <td class="col-sm-1"><input type="number" class="form-control passed_qty" name="passed_qty[]" id="passed_qty" required></td>\
                                <td class="col-sm-5"><input type="text" class="form-control" name="fail_descrpition[]"></td>\
                                </tr>'
                            );
                        });
                    }

                });

                $("#qc_type").val("stock_transfer");
                if ($("#qc_step_dropdown option:selected").val() == 'qc_3') {
                    $("#qc_step").val("qcs_3");
                    $("#production_sheet_no").val($("#qc3_dropdown option:selected").text());
                } else if ($("#qc_step_dropdown option:selected").val() == 'qc_4') {
                    $("#qc_step").val("qcs_4");
                }
                $("#modal_header").text($("#qc_step_dropdown option:selected").text());
                $(".production_column").show();
                $(".delete_field").show();
                $("#qc_add_modal").modal();

            } else if (($("#qc_step_dropdown option:selected").val() == 'qc_4' && $("#qc4_dropdown option:selected").val() != '')) {
                var id = $("#qc4_dropdown").val();
                var status = 'qc_4';

                $.ajax({
                    method: 'post',
                    url: '/quality-control/load_product',
                    data: { id: id, status: status },

                    success: function (data) {
                        $("#product_table_header").empty();
                        $("#product_table_header").append(
                            '<th hidden>transaction_id</th>\
                            <th hidden>produc_id</th>\
                            <th hidden>variation_id</th>\
                            <th class="col-sm-2">Product</th>\
                            <th hidden>lot_no_input</th>\
                            <th class="col-sm-2">Badge No</th>\
                            <th hidden>input production qty</th>\
                            <th class="col-sm-1">Production Quantity</th>\
                            <th class="col-sm-1">Checked Quantity</th>\
                            <th class="col-sm-1">Passed Quantity</th>\
                            <th class="col-sm-5">Description</th>'
                        );

                        $("#tbl_tbody_product").empty();
                        data.forEach(function (row) {
                            $("#tbl_tbody_product").append(
                                '<tr>\
                                <td hidden><input type="text" value=' + row.transaction_id + ' name="transaction_id[]"></td>\
                                <td hidden><input type="text" value=' + row.product_id + ' name="product_id[]"></td>\
                                <td hidden><input type="text" value=' + row.variation_id + ' name="variation_id[]"></td>\
                                <td class="col-sm-2">'+ row.name + ' (' + row.sku + ')' + '<button type="button" data-val=' + row.product_id + ' class="btn btn-danger btn-xs btn_parameter" style="margin-top: 5px;">Product Parameter</button></td>\
                                <td hidden><input type="text" value=' + row.lot_number + ' name="lot_number[]"></td>\
                                <td class="col-sm-2">'+ row.lot_number + '</td>\
                                <td hidden><input type="number" value=' + parseFloat(row.quantity) + ' name="production_qty[]" readonly></td>\
                                <td class="col-sm-1">'+ row.quantity + '</td>\
                                <td class="col-sm-1"><input type="number" class="form-control checked_qty" value=' + parseFloat(row.quantity) + ' name="checked_qty[]" id="checked_qty" readonly required></td>\
                                <td class="col-sm-1"><input type="number" class="form-control passed_qty" name="passed_qty[]" id="passed_qty" required></td>\
                                <td class="col-sm-5"><input type="text" class="form-control" name="fail_descrpition[]"></td>\
                                </tr>'
                            );
                        });
                    }

                });

                $("#qc_type").val("stock_transfer");
                if ($("#qc_step_dropdown option:selected").val() == 'qc_3') {
                    $("#qc_step").val("qcs_3");
                    $("#production_sheet_no").val($("#qc3_dropdown option:selected").text());
                } else if ($("#qc_step_dropdown option:selected").val() == 'qc_4') {
                    $("#qc_step").val("qcs_4");
                }
                $("#modal_header").text($("#qc_step_dropdown option:selected").text());
                // $(".search_product_row").hide();
                $(".production_column").hide();
                $(".delete_field").hide();
                $("#qc_add_modal").modal();

            } else if (($("#qc_step_dropdown option:selected").val() == 'qc_5' && $("#qc5_dropdown option:selected").val() != '')) {
                var id = $("#qc5_dropdown").val();
                var status = 'qc_5';

                $.ajax({
                    method: 'post',
                    url: '/quality-control/load_product',
                    data: { id: id, status: status },

                    success: function (data) {
                        $("#product_table_header").empty();
                        $("#product_table_header").append(
                            '<th hidden>transaction_id</th>\
                            <th hidden>produc_id</th>\
                            <th hidden>variation_id</th>\
                            <th class="col-sm-2">Product</th>\
                            <th hidden>lot_no_input</th>\
                            <th class="col-sm-2">Lot No</th>\
                            <th class="col-sm-1">Received Quantity</th>\
                            <th class="col-sm-1">Checked Quantity</th>\
                            <th class="col-sm-1">Passed Quantity</th>\
                            <th class="col-sm-5">Description</th>'
                        );

                        $("#tbl_tbody_product").empty();
                        data.forEach(function (row) {
                            $("#tbl_tbody_product").append(
                                '<tr>\
                                <td hidden><input type="text" value=' + id + ' name="transaction_id[]"></td>\
                                <td hidden><input type="text" value=' + row.product_id + ' name="product_id[]"></td>\
                                <td hidden><input type="text" value=' + row.variation_id + ' name="variation_id[]"></td>\
                                <td class="col-sm-2">'+ row.name + ' (' + row.sku + ')' + '<button type="button" data-val=' + row.product_id + ' class="btn btn-danger btn-xs btn_parameter" style="margin-top: 5px;">Product Parameter</button></td>\
                                <td hidden><input type="text" value=' + row.mrn_lot + ' name="lot_number[]"></td>\
                                <td class="col-sm-2">'+ row.mrn_lot + '</td>\
                                <td class="col-sm-1"><input type="number" value="" class="form-control received_qty" name="received_qty[]"></td>\
                                <td class="col-sm-1"><input type="number" class="form-control checked_qty" value="" name="checked_qty[]" id="checked_qty" readonly required></td>\
                                <td class="col-sm-1"><input type="number" class="form-control passed_qty" name="passed_qty[]" id="passed_qty" required></td>\
                                <td class="col-sm-5"><input type="text" class="form-control" name="fail_descrpition[]"></td>\
                                </tr>'
                            );
                        });

                        $("#qc_type").val($("#qc5_dropdown option:selected").text());
                        $("#modal_header").text($("#qc_step_dropdown option:selected").text());
                        // $(".search_product_row").hide();
                        $("#qc_step").val("qcs_5");
                        $(".production_column").hide();
                        $(".delete_field").hide();
                        $("#qc_add_modal").modal();
                    }

                });
            }
        }
    });

    // show hide drop down
    $(document).on("change", "#qc_step_dropdown", function () {
        if ($("#qc_step_dropdown")[0].selectedIndex == 1) {
            $('#div_qc_2').css('display', 'none');
            $('#div_qc_3').css('display', 'none');
            $('#div_qc_4').css('display', 'none');
            $('#div_qc_5').css('display', 'none');
            $('.div_check_unapprove').hide();
            $('#div_grn').css('display', 'inline');
            $('#grn_dropdown option:eq(0)').prop('selected', true);
        } else if ($("#qc_step_dropdown")[0].selectedIndex == 2) {
            $('#div_grn').css('display', 'none');
            $('#div_qc_3').css('display', 'none');
            $('#div_qc_4').css('display', 'none');
            $('#div_qc_5').css('display', 'none');
            $('.div_check_unapprove').hide();
            $('#div_qc_2').css('display', 'inline');
        } else if ($("#qc_step_dropdown")[0].selectedIndex == 3) {
            $('#div_qc_2').css('display', 'none');
            $('#div_grn').css('display', 'none');
            $('#div_qc_4').css('display', 'none');
            $('#div_qc_5').css('display', 'none');
            $('#div_qc_3').css('display', 'inline');
            $('.div_check_unapprove').show();
            $('#qc3_dropdown option:eq(0)').prop('selected', true);
        } else if ($("#qc_step_dropdown")[0].selectedIndex == 4) {
            $('#div_grn').css('display', 'none');
            $('#div_qc_2').css('display', 'none');
            $('#div_qc_3').css('display', 'none');
            $('#div_qc_5').css('display', 'none');
            $('.div_check_unapprove').hide();
            $('#div_qc_4').css('display', 'inline');
            $('#qc4_dropdown option:eq(0)').prop('selected', true);
        } else if ($("#qc_step_dropdown")[0].selectedIndex == 5) {
            $('#div_grn').css('display', 'none');
            $('#div_qc_2').css('display', 'none');
            $('#div_qc_3').css('display', 'none');
            $('#div_qc_4').css('display', 'none');
            $('.div_check_unapprove').hide();
            $('#div_qc_5').css('display', 'inline');
            $('#qc5_dropdown option:eq(0)').prop('selected', true);
        } else {
            $('#div_grn').css('display', 'none');
            $('#div_qc_2').css('display', 'none');
            $('#div_qc_3').css('display', 'none');
            $('#div_qc_4').css('display', 'none');
            $('#div_qc_5').css('display', 'none');
        }
    });

    $(document).on("keyup", ".received_qty", function () {
        if ($("#qc_step_dropdown option:selected").val() == 'qc_5') {
            $(this).closest('tr').find("td:eq(7) input[type='number']").val($(this).val());
        }
    });

    //validate table input fields
    $(document).on("keyup", ".checked_qty", function () {
        if ($("#qc_step_dropdown option:selected").val() == 'qc_3') {
            $(this).closest('tr').find("td:eq(8) input[type='number']").val($(this).val());
            if ((parseFloat($(this).closest('tr').find("td:eq(6) input[type='number']").val()) < parseFloat($(this).closest('tr').find("td:eq(7) input[type='number']").val()))) {
                $(this).closest('tr').find("td:eq(7) input[type='number']").val('');
                $(this).closest('tr').find("td:eq(8) input[type='number']").val('');
            }
        } else if ($("#qc_step_dropdown option:selected").val() == 'qc_4') {
            if ((parseFloat($(this).closest('tr').find("td:eq(6) input[type='number']").val()) < parseFloat($(this).closest('tr').find("td:eq(7) input[type='number']").val()))) {
                $(this).closest('tr').find("td:eq(7) input[type='number']").val('');
                $(this).closest('tr').find("td:eq(8) input[type='number']").val('');
            }
        } else {
            $(this).closest('tr').find("td:eq(9) input[type='number']").val($(this).val());
            if (parseFloat($(this).closest('tr').find("td:eq(7) input[type='number']").val()) < parseFloat($(this).closest('tr').find("td:eq(8) input[type='number']").val())) {
                $(this).closest('tr').find("td:eq(8) input[type='number']").val('');
                $(this).closest('tr').find("td:eq(9) input[type='number']").val('');
            }
        }

    });

    //validate table input fields
    $(document).on("keyup", ".passed_qty", function () {
        if ($("#qc_step_dropdown option:selected").val() == 'qc_3') {
            if ((parseFloat($(this).closest('tr').find("td:eq(7) input[type='number']").val()) < parseFloat($(this).closest('tr').find("td:eq(8) input[type='number']").val()))) {
                $(this).closest('tr').find("td:eq(8) input[type='number']").val('');
            }
        } else if ($("#qc_step_dropdown option:selected").val() == 'qc_4') {
            if ((parseFloat($(this).closest('tr').find("td:eq(8) input[type='number']").val()) < parseFloat($(this).closest('tr').find("td:eq(9) input[type='number']").val()))) {
                $(this).closest('tr').find("td:eq(9) input[type='number']").val('');
            }
        } else if ($("#qc_step_dropdown option:selected").val() == 'qc_5') {
            if (parseFloat($(this).closest('tr').find("td:eq(7) input[type='number']").val()) < parseFloat($(this).closest('tr').find("td:eq(8) input[type='number']").val())) {
                $(this).closest('tr').find("td:eq(8) input[type='number']").val('');
            }
        } else {
            if (parseFloat($(this).closest('tr').find("td:eq(8) input[type='number']").val()) < parseFloat($(this).closest('tr').find("td:eq(9) input[type='number']").val())) {
                $(this).closest('tr').find("td:eq(9) input[type='number']").val('');
            }
        }
    });

    //save details
    $('#btn_save').click(function () {
        var isValide1 = true;
        var isValide2 = true;

        if ($("#qc_sheet_no").val() == '') {
            isValide1 = false;
        }

        if ($("#qc_step_dropdown option:selected").val() == 'qc_3') {
            $('#tbl_tbody_product tr').each(function () {
                if ($(this).find("td:eq(9) input[type='number']").val() == '' || $(this).find("td:eq(10) input[type='number']").val() == '') {
                    isValide2 = false;
                    return false;
                } else {
                    isValide2 = true;
                }
            });
        } else if ($("#qc_step_dropdown option:selected").val() == 'qc_5') {
            $('#tbl_tbody_product tr').each(function () {
                if ($(this).find("td:eq(6) input[type='number']").val() == '' || $(this).find("td:eq(8) input[type='number']").val() == '') {
                    isValide2 = false;
                    return false;
                } else {
                    isValide2 = true;
                }
            });
        } else {
            $('#tbl_tbody_product tr').each(function () {
                if ($(this).find("td:eq(8) input[type='number']").val() == '' || $(this).find("td:eq(9) input[type='number']").val() == '') {
                    isValide2 = false;
                    return false;
                } else {
                    isValide2 = true;
                }
            });
        }

        if (isValide1 == true && isValide2 == true) {
            $('#btn_save').prop('disabled', true);

            $.ajax({
                method: 'post',
                url: '/quality-control/save_deatils',
                data: $("#form_save_details").serialize(),

                success: function (response) {
                    if (response.success == true) {
                        $('#btn_save').prop('disabled', false);
                        toastr.success(response.msg);
                        window.location.href = "/quality-control";
                    } else {
                        $('#btn_save').prop('disabled', false);
                        toastr.error(response.msg);
                    }
                }

            });

        } else {
            toastr.error('Fill required fields');
        }
    });

    $(document).on('click', '.remove_product_row', function () {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $(this)
                    .closest('tr')
                    .remove();
                update_table_total();
            }
        });
    });

});