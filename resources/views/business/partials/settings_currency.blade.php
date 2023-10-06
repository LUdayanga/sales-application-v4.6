<div class="pos-tab-content">
    <div class="row">
        <table class="table table-bordered table-striped" id="currency_table" style="width: 60%;">
            <thead>
                <tr class="bg-green">
                    <th class="text-center col-sm-4">Currency</th>
                    <th class="text-center col-sm-3">Symbol</th>
                    <th class="text-center col-sm-3">Rate</th>
                    <th class="col-sm-2"><button type="button" class="btn btn-primary" id="add_values">+</button></th>
                    <th class="text-center col-sm-1 hide">hidden</th>
                </tr>
            </thead>
            <tbody id="tbody_currency_table">
                @foreach ($system_curenncies as $system_curenncy)
                    <tr>
                        <td><input type="text" class="form-control" name="currency_name[]"
                                value="{{ $system_curenncy->currency_name }}" required></td>
                        <td><input type="text" class="form-control" name="currency_symbol[]"
                                value="{{ $system_curenncy->currency_symbol }}" required></td>
                        <td><input type="number" class="form-control" name="currency_rate[]"
                                value="{{ $system_curenncy->currency_rate }}" required></td>
                        @if ($system_curenncy->is_show_currency != 0)
                            <td><button type="button" class="btn btn-danger delete_currency_values">-</button></td>
                        @else
                        <td></td>    
                        @endif
                        <td hidden><input type="text" class="form-control" name="is_show_currency[]" value="{{ $system_curenncy->is_show_currency }}" required></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
