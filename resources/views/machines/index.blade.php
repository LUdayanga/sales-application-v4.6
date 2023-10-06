@extends('layouts.app')
@section('title', __('lang_v1.machines'))

@section('content')

    <div class="row">
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
    </div>

    <!-- Content Header (Page header) -->
    <section class="content-header no-print">
        <h1>@lang('lang_v1.machines')
        </h1>
    </section>

    <!-- Main content -->
    <section class="content no-print">
        @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.all_machines')])
            @slot('tool')
                <div class="box-tools">
                    <button type="button" class="btn btn-primary btn-modal-add" data-container=".machine_modal"><i
                            class="fa fa-plus"></i> @lang( 'messages.add' )</button>
                </div>
            @endslot
            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view" id="machines_table">
                    <thead>
                        <tr>
                            <th class="">@lang('messages.image')</th>
                            <th class="text-center">@lang('messages.name')</th>
                            <th class="text-center">@lang('messages.condition')</th>
                            <th class="text-center">@lang('messages.action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcomponent
    </section>

    {{-- add modal --}}
    <div class="modal fade machine_add_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">

                <form action="{{ action('MachineController@store') }}" method="post" id="modal_add" enctype="multipart/form-data">
                    {!! csrf_field() !!}
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">@lang( 'lang_v1.add_machines' )</h4>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    {!! Form::label('machine_name', __('business.machine_name') . ':*') !!}
                                    {!! Form::text('machine_name', null, ['class' => 'form-control mechine_name', 'required', 'placeholder' => __('business.machine_name')]) !!}
                                </div>
                            </div>
                            <div class="clearfix"></div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    {!! Form::label('machine_condition', __('business.select_condition') . ':*') !!}
                                    {!! Form::select('machine_condition', ['' => __('business.choose_machine_condition'), 'good' => __('business.machine_good'), 'medium' => __('business.machine_medium'), 'low' => __('business.machine_low'), 'need_to_repaire' => __('business.machine_need_to_repaire')], !empty($user->marital_status) ? $user->marital_status : null, ['class' => 'form-control', 'required']) !!}
                                </div>
                            </div>
                            <div class="clearfix"></div>
                            <div class="row"></div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    {!! Form::label('image', __('lang_v1.product_image') . ':') !!}
                                    {!! Form::file('image', ['id' => 'upload_image', 'class' => 'upload_image', 'accept' => 'image/*']) !!}
                                    <small>
                                        <p class="help-block">@lang('purchase.max_file_size', ['size' =>
                                            (config('constants.document_size_limit') / 1000000)]) <br>
                                            @lang('lang_v1.aspect_ratio_should_be_1_1')</p>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close')</button>
                        <button type="submit" class="btn btn-primary update_mechine">@lang( 'messages.save' )</button>
                    </div>

                </form>

            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->

    </div>

    {{-- edit modal --}}
    <div class="modal fade machine_edit_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">

                <form action="{{ action('MachineController@updates') }}" method="post" enctype="multipart/form-data">
                    {!! csrf_field() !!}
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">@lang( 'lang_v1.add_machines' )</h4>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    {!! Form::label('machine_name', __('business.machine_name') . ':*') !!}
                                    {!! Form::text('machine_name', null, ['class' => 'form-control mechine_name', 'required', 'placeholder' => __('business.machine_name')]) !!}
                                </div>
                            </div>
                            <div class="clearfix"></div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    {!! Form::label('machine_condition', __('business.select_condition') . ':*') !!}
                                    {!! Form::select('machine_condition', ['' => __('business.choose_machine_condition'), 'good' => __('business.machine_good'), 'medium' => __('business.machine_medium'), 'low' => __('business.machine_low'), 'need_to_repaire' => __('business.machine_need_to_repaire')], !empty($user->marital_status) ? $user->marital_status : null, ['class' => 'form-control mechine_condition', 'required']) !!}
                                </div>
                            </div>
                            <div class="clearfix"></div>
                            <div class="row"></div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    {!! Form::label('image', __('lang_v1.product_image') . ':') !!}
                                    {!! Form::file('image', ['id' => 'upload_image', 'class' => 'upload_image', 'accept' => 'image/*']) !!}
                                    <small>
                                        <p class="help-block">
                                            @lang('lang_v1.previous_file_will_be_replaced')<br>
                                            @lang('purchase.max_file_size', ['size' =>
                                            (config('constants.document_size_limit') / 1000000)]) <br>
                                            @lang('lang_v1.aspect_ratio_should_be_1_1')
                                        </p>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <input type="text" id="id" name="id" hidden>
                        <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close')</button>
                        <button type="submit" class="btn btn-primary update_mechine">@lang( 'messages.save' )</button>
                    </div>

                </form>

            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->

    </div>
    {{-- @include('stock_transfer.partials.update_status_modal') --}}
    <section id="receipt_section" class="print_section"></section>

    <!-- /.content -->
@stop
@section('javascript')
    <script>
        $(document).ready(function() {
            var machines_table = $('#machines_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '/machines',
                buttons: [],
                "ajax": {
                    "url": "/machines",
                    "data": function ( d ) {
                        d.condition = $('#filter_condition').val();
                        d = __datatable_ajax_callback(d);
                    }
                },
                columnDefs: [{
                    "targets": 2,
                    "orderable": false,
                    "searchable": false
                }],
                columns: [
                    {
                        data: 'image',
                        name: 'image'
                    },
                    {
                        data: 'machine_name',
                        name: 'machine_name'
                    },
                    {

                        data: 'machine_condition',
                        name: 'machine_condition'
                    },
                    {
                        data: 'action',
                        name: 'action'
                    },
                ],
            });

            $(document).on("click", ".btn-modal-add", function() {
                $('#modal_add')[0].reset();
                $(".machine_add_modal").modal();
            });

            $(document).on("click", ".edit_machine_button", function(e) {
                e.preventDefault();
                var id = $(this).attr("data-val");
                var name = $(this).closest("tr").find('td:eq(1)').text();
                var condition = $.trim($(this).closest("tr").find('td:eq(2)').text());

                $(".mechine_name").val(name);
                if (condition == "100%") {
                    $('.mechine_condition option:eq(1)').prop('selected', true);
                } else if (condition == "75%") {
                    $('.mechine_condition option:eq(2)').prop('selected', true);
                } else if (condition == "50%") {
                    $('.mechine_condition option:eq(3)').prop('selected', true);
                } else {
                    $('.mechine_condition option:eq(4)').prop('selected', true);
                }
                $("#id").val(id);
                $(".machine_edit_modal").modal();
            });

            $('table#machines_table tbody').on('click', '.delete_machine_button', function(e) {
                e.preventDefault();
                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        var href = $(this).attr('data-href');
                        $.ajax({
                            method: "DELETE",
                            url: href,
                            dataType: "json",
                            success: function(result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                    machines_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });

            var img_fileinput_setting = {
                showUpload: false,
                showPreview: true,
                browseLabel: LANG.file_browse_label,
                removeLabel: LANG.remove,
                previewSettings: {
                    image: {
                        width: 'auto',
                        height: 'auto',
                        'max-width': '100%',
                        'max-height': '100%'
                    },
                },
            };

            $('.upload_image').fileinput(img_fileinput_setting);

            // filter dropdown change
            $(document).on('change', '#filter_condition', function() {
                machines_table.ajax.reload();
            });

        });
    </script>
@endsection
