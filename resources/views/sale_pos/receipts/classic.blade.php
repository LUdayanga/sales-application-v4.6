<style>
    @media print {
        @page {
            /* size: 80mm size: portrait; */
            size: auto;
            margin: 0mm;
        }
    }

    @page {
        /* margin: 0 size: 80mm; */
        size: portrait;
    }

    .font-family {
        font-family: "Times New Roman", Georgia, Serif;
    }

    .inv_table th {
        border-top: 2px solid #000;
        border-bottom: 2px solid #000;
        padding-top: 1px;
        padding-bottom: 1px;
    }

    .inv_table td {
        font-weight: normal;
        font-size: 16px;
    }

    .bank_details_table td{
        font-weight: normal;
    }
</style>

<div class="row invoice-info" style="margin-left: 5px;">
    <div class="col-sm-6 invoice-col" style="margin-top: 10px;">
        <img src="{{ asset('uploads/vrico-logo.PNG') }}" alt=""
            style="display: block; margin: auto; margin-bottom: -10px;">
        <h4 class="font-family" style="margin-left: 20px; margin-bottom: -10px;">Virco International (pvt) Ltd</h4>
    </div>

    <div class="col-sm-2 invoice-col"></div>

    <div class="col-sm-2 invoice-col" style="margin-top: 20px;">
        @if($receipt_details->transaction_status == 'final')
            <h4 class="font-family"><b>INVOICE</h4>
        @endif

        @if($receipt_details->transaction_status == 'draft')
            <h4 class="font-family"><b>DRAFT</h4>
        @endif

        @if($receipt_details->transaction_status == 'quotation')
            <h4 class="font-family"><b>QUOTATION</h4>
        @endif

        @if($receipt_details->transaction_status == 'proforma')
            <h4 class="font-family"><b>PROFORMA INVOICE</h4>
        @endif
        
        <h5 class="font-family">Invoice No : {{ $receipt_details->invoice_no }}</h5>
        <h5 class="font-family">Date: {{ $receipt_details->invoice_date }}</h5>
        <h5 class="font-family">VAT No: -</h5>
        <h5 class="font-family">SVAT No: -</h5>
    </div>
</div>

<div class="row invoice-info" style="margin-top: 20px; margin-left: 10px;">
    <div class="col-sm-8 invoice-col">
        <h5 class="font-family"><b>CUSTOMER DETAILS</h5>
        <hr style="margin-left: 1px; margin-top: -10px; margin-bottom: 0px; width: 145px;">
        <h5 class="font-family">Name : {{ $receipt_details->customer_name }} </h5>
        <h5 class="font-family">Address : {{ $receipt_details->customer_info_address }} </h5>
        <h5 class="font-family">Tel/Mobile : {{ $receipt_details->customer_mobile_info }} </h5>
        <h5 class="font-family">Email : {{ $receipt_details->customer_email }} </h5>
    </div>

    <div class="col-sm-2 invoice-col"></div>

    <div class="col-sm-6 invoice-col">
        <h5 class="font-family"><b>DELIVERY ADDRESS</h5>
        <hr style="margin-left: 1px; margin-top: -10px; margin-bottom: 0px; width: 145px;">

        @if(!empty($receipt_details->customer_delivery_address_1))
        <h5 class="font-family" style="margin-bottom: -8px;"> {{ $receipt_details->customer_delivery_address_1 }},
        </h5>
        @endif

        @if(!empty($receipt_details->customer_delivery_address_2))
        <h5 class="font-family" style="margin-bottom: -8px;"> {{ $receipt_details->customer_delivery_address_2 }},
        </h5>
        @endif

        @if(!empty($receipt_details->customer_delivery_address_3))
        <h5 class="font-family" style="margin-bottom: -8px;"> {{ $receipt_details->customer_delivery_address_3 }}
        </h5>
        @endif
    </div>
</div>
<div class="col-12" style="margin-top: 15px;">
    <table class="inv_table" style="margin: 0 auto; width: 95%">
        <thead>
            <tr>
                <th class="text-left font-family" style="font-size: 15px; width: 3%;">#</th>
                <th class="text-left font-family" style="font-size: 15px; width: 10%;">Item Code</th>
                <th class="text-left font-family" style="font-size: 15px; width: 30%;">Description</th>
                <th class="text-left font-family" style="font-size: 15px; width: 5%;">UOM</th>
                <th class="text-left font-family" style="font-size: 15px; width: 5%;">QTY</th>
                <th class="text-left font-family" style="font-size: 15px; width: 10%;">Rate (LKR)</th>
                <th class="text-right font-family" style="font-size: 15px; width: 10%;">Amount (LKR)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($receipt_details->lines as $line)
                <tr>
                    <td class="text-left font-family" style="padding-top: 8px; padding-bottom: 8px;">1</td>
                    <td class="text-left font-family">{{ $line['sub_sku'] }}</td>
                    <td class="text-left font-family">{{ $line['name'] }}</td>
                    <td class="text-left font-family">{{ $line['units'] }}</td>
                    <td class="text-left font-family">{{ $line['quantity'] }}</td>
                    <td class="text-left font-family">{{ $line['unit_price_before_discount'] }}</td>
                    <td class="text-right font-family">{{ $line['line_total'] }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="8">
                    <hr style="border: 1px solid black;">
                </td>
            </tr>
            <tr>
                <td class="description"></td>
                <td class="price text-right" style="font-family: Times New Roman" colspan="5"><b>Subtotal: </td>
                <td class="text-right" style="font-family: Times New Roman" colspan="6"><b>
                        {{ $receipt_details->subtotal }}</td>
            </tr>
            <tr>
                <td class="description"></td>
                <td class="price text-right" style="font-family: Times New Roman" colspan="5">Discount: </td>
                <td class="text-right" style="font-family: Times New Roman" colspan="6">
                    Rs.{{ number_format($receipt_details->total_discount, 2) }}</td>
            </tr>
            <tr>
                <td class="description"></td>
                <td class="price text-right" style="font-family: Times New Roman" colspan="5">Tax (VAT 8%): </td>
                <td class="text-right" style="font-family: Times New Roman" colspan="6"> - </td>

            </tr>
            <tr>
                <td class="description"></td>
                <td class="price text-right" style="font-family: Times New Roman" colspan="5">Other Charges:
                </td>
                <td class="text-right" style="font-family: Times New Roman" colspan="6"> - </td>

            </tr>
            <tr>
                <td class="description"></td>
                <td class="price text-right" style="font-family: Times New Roman" colspan="5"><b>Total: </td>
                <td class="text-right" style="font-family: Times New Roman" colspan="6"><b>
                        {{ $receipt_details->total }}</td>

            </tr>
        </tbody>
    </table>
    <hr style="margin: 0 auto; width: 95%">
    <div class="col-xs-12">
        <p>{!! nl2br($receipt_details->additional_notes) !!}</p>
    </div>
</div>
</div>

<div class="row invoice-info" style="margin-top: 20px; margin-left: 20px;">
    <h5 class="font-family"><b>Terms & Conditions</h5>
    <hr style="margin-left: 1px; margin-top: -10px; margin-bottom: 0px; width: 125px;">
    <h5 class="font-family" style="margin-bottom: -8px;"> Payments: 7 days credit
    </h5>
</div>

<div class="col-12" style="margin-top: 30px; margin-left: 20px;">
    <h5 class="font-family"><b>Bank Details</h5>
    <hr style="margin-left: 1px; margin-top: -10px; margin-bottom: 10px; width: 80px;">

    <table class="bank_details_table" style="width: 95%">
        <tr>
            <td class="text-left font-family" style="width: 4%;">Account Name</td>
            <td class="text-left font-family" style="width: 20%;">: Virco International (PVT)LTD</td>
        </tr>
        <tr>
            <td class="text-left font-family" style="width: 4%;">Bank & Branch</td>
            <td class="text-left font-family" style="width: 20%;">: Sampath Bank PLC - Mawathagama</td>
        </tr>
        <tr>
            <td class="text-left font-family" style="width: 4%;">Account No</td>
            <td class="text-left font-family" style="width: 20%;">: 0109 1000 0727</td>
        </tr>
        <tr>
            <td class="text-left font-family" style="width: 4%;">Bank Address</td>
            <td class="text-left font-family" style="width: 20%;">: No.95, Kurunegala Road, Mawathagama</td>
        </tr>
        <tr>
            <td class="text-left font-family" style="width: 4%;">Bank & Branch Code</td>
            <td class="text-left font-family" style="width: 20%;">: 7278 - 109</td>
        </tr>
        <tr>
            <td class="text-left font-family" style="width: 4%;">SWIFT</td>
            <td class="text-left font-family" style="width: 20%;">: BSAMLKLX</td>
        </tr>
    </table>
</div>

<div class="text-center" style="margin-top: 30px;">
    <h5 class="text-center font-family" style="font-weight: normal;">This is a computer generated invoice and no signature is required</h5>
</div>

<hr style="margin: 0 auto; width: 95%">

<div class="row invoice-info" style="margin-left: 10px;">
    <div class="col-sm-6 invoice-col">
        <img src="{{ asset('uploads/invoice-referrence.PNG') }}" alt=""
            style="display: block; margin-top: 40px;">
    </div>

    <div class="col-sm-2 invoice-col"></div>

    <div class="col-sm-2 invoice-col" style="margin-top: 10px;">
        <h5 class="font-family" style="margin-bottom: -10px;">Virco International (PVT)LTD</h5>
        <h5 class="font-family" style="margin-bottom: -10px;">Seegiriswattha, Mawathagama</h5>
        <h5 class="font-family" style="margin-bottom: -10px;">Whatsapp: (+94) 772 700 003</h5>
        <h5 class="font-family" style="margin-bottom: -10px;">Telephone: (+94) 372 296 611</h5>
        <h5 class="font-family" style="margin-bottom: -10px;">Email: info@virco.lk</h5>
        <h5 class="font-family" style="margin-bottom: -10px;">Website: www.vrico.lk</h5>
    </div>
</div>


<div class="text-center" style="margin-top: 50px;">
    <small class="text-center" style="font-fize:8px">POS BY - CLICKY IT SOLUTIONS | 0777 780 0067</small>
</div>


