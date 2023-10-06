<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="modalTitle">{{ $product->name }}</h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table bg-gray">
                            <tr class="bg-green">
                                <th>Lot Numbers</th>
                                <th>Quantity</th>
                            </tr>
                            @foreach ($getQtys as $getQty)
                                <tr>
                                    <td>{{ $getQty->lot_number }}</td>
                                    <td>{{ $getQty->qty_available }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default no-print" data-dismiss="modal">@lang( 'messages.close'
                )</button>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        var element = $('div.view_modal');
        __currency_convert_recursively(element);
    });
</script>
