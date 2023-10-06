<div class="modal-body">
    <style>
        @page {
            size: auto;
            margin: 0mm;
        }

    #table_print_po{
        width: 100%;
    }

     #table_print_po tbody tr td {
        font-weight: normal;
        padding: 3px;
     }

     #table_print_po th, #table_print_po td {
        padding: 5px;
     } 

     #table_print_po th{
        border-top: 1px solid #000;
        border-bottom: 1px solid #000;
     }

    </style>
    <div class="row">
        <div class="col-md-12">
            <img src="{{ asset('uploads/vrico-logo.PNG') }}" alt=""
                style="display: block; margin: auto; margin-bottom: -20px;">
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <h2 style="text-align: center; margin-bottom: -10px;"><b>Virco International (pvt) Ltd</h2>
            <h5 style="text-align: center; margin-bottom: -8px;">Seegiriswatta, Mawathagama, Sri Lanka 60060</h5>
            <h5 style="text-align: center; margin-bottom: -10px;">Tel: +94 77 270 0003, +94 37 229 6611</h5>
            <h5 style="text-align: center; margin-bottom: -10px;">Email: financevirco@gmail.com Web: www.virco.lk</h5>
            <hr style="margin-bottom: 3px;">
            @php
                $title = $purchase->type == 'purchase_order' ? __('PURCHASE ORDER') : __('purchase.purchase_details');
                $custom_labels = json_decode(session('business.custom_labels'), true);
            @endphp
            <h4 class="modal-title" id="modalTitle" style="text-align: center; margin-bottom: -20px;"><b>
                    {{ $title }}</h4>
            <hr style="margin-bottom: 10px;">
        </div>
    </div>
    <div class="row invoice-info">
        <div class="col-sm-8 invoice-col">

            <h5 style="margin-bottom: -6px;">Supplier Name: <b>{!! $purchase->contact->name !!}</h5>

            @if (!empty($purchase->contact->tax_number))
                <h5 style="margin-bottom: -6px;">Tax No: <b>{!! $purchase->contact->tax_number !!}</h5>
            @endif

            @if (!empty($purchase->contact->mobile))
                <h5 style="margin-bottom: -6px;">Mobile: <b>{!! $purchase->contact->mobile !!}</h5>
            @endif

            @if (!empty($purchase->contact->email))
                <h5 style="margin-bottom: -6px;">Email: <b>{!! $purchase->contact->email !!}</h5>
            @endif
        </div>

        <div class="col-sm-1 invoice-col">
        </div>

        <div class="col-sm-3 invoice-col">
            <h5 style="margin-bottom: -6px;">Order No: <b>{!! $purchase->ref_no !!}</h5>
            <h5 style="margin-bottom: -6px;">Order Date: <b>{!! $purchase->transaction_date !!}</h5>
        </div>
    </div>

    <div class="row" style="margin-top: 25px; height: 400px;">
        <div class="col-md-12">
            <table id="table_print_po">
                <thead>
                    <tr>
                        <th class="text-left">#</th>
                        <th class="text-left">@lang('product.product_name')</th>
                        <th class="text-left">@lang('product.sku')</th>
                        <th class="text-right">
                            @if ($purchase->type == 'purchase_order')
                                @lang('lang_v1.order_quantity')
                            @else
                                @lang('purchase.purchase_quantity')
                            @endif
                        </th>
                        <th class="text-right">@lang('Unit Price')</th>
                        <th class="text-right">@lang('Sub Total')</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $total_before_tax = 0.0;
                    @endphp
                    @foreach ($purchase->purchase_lines as $purchase_line)
                        <tr>
                            <td class="text-left">{{ $loop->iteration }}</td>
                            <td class="text-left">{{ $purchase_line->product->name }}</td>
                            <td class="text-left">{{ $purchase_line->product->sku }}</td>
                            <td class="text-right"><span class="display_currency" data-is_quantity="true"
                                    data-currency_symbol="false">{{ $purchase_line->quantity }}</span>
                                @if (!empty($purchase_line->sub_unit))
                                    {{ $purchase_line->sub_unit->short_name }}
                                @else
                                    {{ $purchase_line->product->unit->short_name }}
                                @endif
                            </td>
                            <td class="text-right"><span class="display_currency"
                                    data-currency_symbol="true">{{ $purchase_line->pp_without_discount }}</span></td>

                            <td class="text-right"><span class="display_currency"
                                    data-currency_symbol="true">{{ $purchase_line->purchase_price_inc_tax * $purchase_line->quantity }}</span>
                            </td>
                        </tr>
                        @php
                            $total_before_tax += $purchase_line->quantity * $purchase_line->purchase_price;
                        @endphp
                    @endforeach
                </tbody>
            </table>
            <table id="summery_table" style="width: 100%; margin-top: 10px;">
                <thead>
                    <tr class="col-sm-12">
                        <th class="" style="border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 3px;"><span style="margin-left: 575px; font-weight: normal; font-size: 15px;">Net Total: </span><span class="display_currency pull-right"
                            data-currency_symbol="true" style="font-size: 15px;">{{ $total_before_tax }}</span></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
    <div class="row" style="margin-top: 35px;">
        <div class="col-md-12">
            <h4><b>Instructions:</h4>
            <textarea name="" id="" rows="4" style="width: 100%"></textarea>
        </div>
    </div>
    <div class="row invoice-info" style="margin-top: 40px;">
        <div class="col-sm-3 invoice-col">
            <hr style="margin-bottom: 0px;">
            <h5 class="text-center">Authorized Signature</h5>
        </div>
        <div class="col-sm-3 invoice-col"></div>
        <div class="col-sm-3 invoice-col">
            <hr style="margin-bottom: 0px;">
            <h5 class="text-center">Customer Signature</h5>
        </div>
    </div>
    <div class="row" style="margin-top: 25px;">
        <div class="col-md-12">
            <table style="width: 100%">
                <thead>
                    <tr>
                        <th class="text-center" style="border-top: 1px solid #000; border-bottom: 1px solid #000;">Thank you very much</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
