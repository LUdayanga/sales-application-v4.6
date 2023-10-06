@extends('layouts.app')
@section('title', 'Product Parameters')

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('Product Parameters')
            <small>@lang('Manage your product parameters')</small>
        </h1>
        <!-- <ol class="breadcrumb">
                        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
                        <li class="active">Here</li>
                    </ol> -->
    </section>

    <!-- Main content -->
    <section class="content">
        @component('components.widget', ['class' => 'box-primary', 'title' => __('All product parameters')])
            @can('brand.create')
                @slot('tool')
                    <div class="box-tools">
                        <button type="button" class="btn btn-block btn-primary btn-modal">
                            <i class="fa fa-plus"></i> @lang('messages.add')</button>
                    </div>
                @endslot
            @endcan
            @can('brand.view')
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="parameter_table">
                        <thead>
                            <tr>
                                <th class="text-center">@lang('Parameter Name')</th>
                                <th class="text-center">@lang('Description')</th>
                                <th class="text-center">@lang('messages.action')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            @endcan
        @endcomponent

        <div class="modal fade parameter_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
            <div class="modal-dialog" role="document">
                <div class="modal-content">

                    {{-- {!! Form::open(['url' => action('ProductParameterController@store'), 'method' => 'post', 'id' => 'brand_add_form']) !!} --}}

                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"></h4>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            {!! Form::label('name', __('Parameter name') . ':*') !!}
                            {!! Form::text('name', null, ['class' => 'form-control name', 'required', 'placeholder' => __('Parameter name')]) !!}
                        </div>

                        <div class="form-group">
                            {!! Form::label('description', __('Description') . ':') !!}
                            {!! Form::text('description', null, ['class' => 'form-control description', 'placeholder' => __('Description')]) !!}
                        </div>

                    </div>

                    <div class="modal-footer">
                        <input type="text" name="" class="parameter_id" hidden>
                        <button type="button" class="btn btn-primary btn_save"></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                    </div>

                    {{-- {!! Form::close() !!} --}}

                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div>

    </section>
    <!-- /.content -->

@endsection

@section('javascript')
    <script>
        $(document).ready(function() {
            var parameter_table = $('#parameter_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '/product-parameters',
                columns: [{
                        data: 'parameter_name',
                        name: 'parameter_name'
                    },
                    {
                        data: 'parameter_description',
                        name: 'parameter_description'
                    },
                    {
                        data: 'action',
                        name: 'action'
                    },
                ],
            });

            $(document).on("click", ".btn-modal", function() {
                $('.name').val('');
                $('.description').val('');
                $('.parameter_id').val('');
                $('.modal-title').text('Add Parameter');
                $('.btn_save').text('Save');
                $(".parameter_modal").modal();
            });

            $('.btn_save').click(function() {
                if ($('.name').val() != '') {
                    var name = $('.name').val();
                    var description = $('.description').val();
                    var parameter_id = $('.parameter_id').val();

                    $.ajax({
                        method: 'post',
                        url: '/product-parameters/store',
                        data: {
                            name: name,
                            description: description,
                            parameter_id: parameter_id
                        },

                        success: function(response) {
                            if (response.success == true) {
                                toastr.success(response.msg);
                                $(".parameter_modal").modal('hide');
                                parameter_table.ajax.reload();
                            } else {
                                toastr.error(response.msg);
                            }
                        }

                    });

                } else {
                    toastr.error('Please enter parameter name');
                }
            });

            $(document).on("click", ".edit_parameter_button", function(e) {
                e.preventDefault();
                var id = $(this).attr("data-val");
                var name = $(this).closest("tr").find('td:eq(0)').text();
                var description = $(this).closest("tr").find('td:eq(1)').text();

                $('.name').val('');
                $('.description').val('');
                $('.parameter_id').val('');

                $(".name").val(name);
                $(".description").val(description);
                $('.parameter_id').val(id);
                $('.modal-title').text('Edit Parameter');
                $('.btn_save').text('Update');
                $(".parameter_modal").modal();
            });

            $(document).on("click", ".delete_parameter_button", function(e) {
                e.preventDefault();
                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        var parameter_id = $(this).attr("data-val");
                        var delete_parameter = true;
                        $.ajax({
                            method: 'post',
                            url: '/product-parameters/store',
                            data: {
                                parameter_id: parameter_id,
                                delete_parameter: delete_parameter
                            },

                            success: function(response) {
                                if (response.success == true) {
                                    toastr.success('Successfully Deleted');
                                    parameter_table.ajax.reload();
                                } else {
                                    toastr.error(response.msg);
                                }
                            }

                        });
                    }
                });
            });

            // filter dropdown change
            $(document).on('change', '#filter_condition', function() {
                machines_table.ajax.reload();
            });

        });
    </script>
@endsection
