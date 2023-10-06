<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Job Card</title>
    <style>
        .f-20 {
            font-size: 20px;
            color: rgb(26, 182, 52);
        }

        .f-15 {
            font-size: 15px;
        }

        .table-border,
        .tr-border,
        .td-border {
            border: 1px solid black;
            border-collapse: collapse;
        }

        .text-center {
            text-align: center;
        }

    </style>
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <table class="table-border" style="width: 100%;">
                    <thead>
                    </thead>
                    <tbody>
                        <tr class="tr-border">
                            <td class="td-border">
                                <div class="text-center">
                                    Image
                                </div>
                            </td>
                            <td class="td-border">
                                <table style="width: 100%;">
                                    <thead></thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <div class="text-center">
                                                    <span class="f-20">VIRCO INTERNATIONAL (PVT) LTD.</span>
                                                    <br>
                                                    <span class="f-15" style="">Seegiriswatta, Mawathagama,
                                                        Sri
                                                        Lanka
                                                        60060</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr class="tr-border">
                                            <td class="td-border" colspan="4">
                                                <div class="text-center">
                                                    <span class="f-15">Job Card</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <table style="width: 100%;">
                                                <thead></thead>
                                                <tbody>
                                                    <tr>
                                                        <td class="td-border">Doc Index</td>
                                                        <td class="td-border">
                                                            {{ $production_purchase->doc_index }}</td>
                                                        <td class="td-border">Issued Date</td>
                                                        <td class="td-border">
                                                            {{ @format_date($production_purchase->created_at) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="td-border">Issue No</td>
                                                        <td class="td-border">
                                                            {{ $production_purchase->issue_no }}</td>
                                                        <td class="td-border">Recived Date</td>
                                                        <td class="td-border">-</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="td-border">Issued By</td>
                                                        @php
                                                            $user = App\User::find($production_purchase->created_by);
                                                        @endphp
                                                        <td class="td-border">
                                                            {{ $user->surname . ' ' . $user->first_name . ' ' . $user->last_name }}
                                                        </td>
                                                        <td class="td-border">Authorise By</td>
                                                        <td class="td-border">-</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-12" style="margin-top: 5px;">
                <table class="table-border" style="width: 100%;">
                    <thead></thead>
                    <tbody>
                        <tr class="tr-border">
                            <td class="td-border">Date</td>
                            <td colspan="3" class="td-border">
                                {{ @format_date($production_purchase->created_at) }}</td>
                        </tr>
                        
                        <tr class="tr-border">
                            <td class="td-border">Product</td>
                            <td class="td-border">{{ $main_product }}</td>
                            <td class="td-border">Section</td>
                            @php
                                $section = App\BusinessLocation::find($production_purchase->section_id);
                            @endphp
                            {{-- <td class="td-border">{{ $section->name ?? '-' }}</td> --}}
                            <td class="td-border">Virco</td>
                        </tr>
                        <tr class="tr-border">
                            <td class="td-border">Shipment No</td>
                            <td class="td-border">{{ $production_purchase->shipment_no }}</td>
                            <td class="td-border">Client Name</td>
                            @php
                                $client = App\Contact::find($production_purchase->contact_id);
                            @endphp
                            <td class="td-border">{{ $client->name ?? '-' }}</td>
                        </tr>
                        <tr class="tr-border">
                            <td class="td-border">Target Qty</td>
                            <td class="td-border">{{ $production_purchase->target_qty }}</td>
                            <td class="td-border">Day Production Qty</td>
                            <td class="td-border">{{ $production_purchase->day_production_qty }}</td>
                        </tr>
                        <tr class="tr-border">
                            <td class="td-border">Number of Workers</td>
                            <td class="td-border">{{ $production_purchase->no_workers }}</td>
                            <td class="td-border">Production Duration</td>
                            <td class="td-border">{{ $production_purchase->production_duration }}</td>
                        </tr>
                        <tr class="tr-border">
                            <td colspan="4" class="td-border">Packing Instructions</td>
                        </tr>
                        <tr class="tr-border">
                            <td colspan="4" class="td-border">{!! $production_purchase->packing_instructions !!}</td>
                        </tr>
                        <tr class="tr-border">
                            <td colspan="4" class="td-border">Raw Materials Supply</td>
                        </tr>
                        <tr class="tr-border">
                            <td colspan="4" class="td-border">
                                <table style="width: 100%;">
                                    <thead>
                                        <tr class="tr-border">
                                            <th style="width: 20%">@lang('manufacturing::lang.ingredient')</th>
                                            <th>@lang('manufacturing::lang.input_quantity')</th>
                                            <th>@lang('manufacturing::lang.waste_percent')</th>
                                            <th>@lang('manufacturing::lang.final_quantity')</th>
                                            <th>@lang('manufacturing::lang.total_price')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $total_ingredient_price = 0;
                                        @endphp
                                        @foreach ($ingredients as $ingredient)
                                            @if ($ingredient['ingredient_type'] == 'raw')
                                                <tr class="tr-border" >
                                                    <td style="width: 20%;text-align:center;">
                                                        {{ $ingredient['full_name'] }}
                                                        @if (!empty($ingredient['lot_numbers']))
                                                            <br>
                                                            <small> @lang('lang_v1.lot_n_expiry'):
                                                                {{ $ingredient['lot_numbers'] }}</small>
                                                        @endif
                                                    </td>
                                                    <td style="width: 20%;text-align:center;">{{ @format_quantity($ingredient['quantity']) }}
                                                        {{ $ingredient['unit'] }}</td>
                                                    <td style="width: 20%;text-align:center;">{{ @format_quantity($ingredient['waste_percent']) }} %</td>
                                                    <td style="width: 20%;text-align:center;">{{ @format_quantity($ingredient['final_quantity']) }}
                                                        {{ $ingredient['unit'] }}</td>
                                                    @php
                                                        $price = $ingredient['total_price'];
                                                        
                                                        $total_ingredient_price += $price;
                                                    @endphp
                                                    <td style="width: 20%;text-align:center;">
                                                        <span class="display_currency"
                                                            data-currency_symbol="true">{{ @num_format($price) }}</span>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                        @if (!empty($ingredient_groups))
                                            @foreach ($ingredient_groups as $ingredient_group)
                                                <tr>
                                                    <td colspan="5" class="bg-gray">
                                                        <strong>
                                                            {{ $ingredient_group['ig_name'] ?? '' }}
                                                        </strong>
                                                        @if (!empty($ingredient_group['ig_description']))
                                                            - {{ $ingredient_group['ig_description'] }}
                                                        @endif
                                                    </td>
                                                </tr>
                                                @foreach ($ingredient_group['ig_ingredients'] as $ingredient)
                                                    <tr>
                                                        <td>
                                                            {{ $ingredient['full_name'] }}
                                                            @if (!empty($ingredient['lot_numbers']))
                                                                <br>
                                                                <small> @lang('lang_v1.lot_n_expiry'):
                                                                    {{ $ingredient['lot_numbers'] }}</small>
                                                            @endif
                                                        </td>
                                                        <td>{{ @format_quantity($ingredient['quantity']) }}
                                                            {{ $ingredient['unit'] }}</td>
                                                        <td>{{ @format_quantity($ingredient['waste_percent']) }} %
                                                        </td>
                                                        <td>{{ @format_quantity($ingredient['final_quantity']) }}
                                                            {{ $ingredient['unit'] }}</td>
                                                        @php
                                                            $price = $ingredient['total_price'];
                                                            $total_ingredient_price += $price;
                                                        @endphp
                                                        <td>
                                                            <span class="display_currency"
                                                                data-currency_symbol="true">{{ $price }}</span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr class="tr-border">
                            <td colspan="4" class="td-border">Packing Materials Supply</td>
                        </tr>
                        <tr class="tr-border">
                            <td colspan="4" class="td-border">
                                <table style="width: 100%;">
                                    <thead>
                                        <tr class="tr-border">
                                            <th>@lang('manufacturing::lang.ingredient')</th>
                                            <th>@lang('manufacturing::lang.input_quantity')</th>
                                            <th>@lang('manufacturing::lang.waste_percent')</th>
                                            <th>@lang('manufacturing::lang.final_quantity')</th>
                                            <th>@lang('manufacturing::lang.total_price')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $total_ingredient_price = 0;
                                        @endphp
                                        @foreach ($ingredients as $ingredient)
                                            @if ($ingredient['ingredient_type'] == 'pack')
                                                <tr class="tr-border">
                                                    <td style="width: 20%;text-align:center;">
                                                        {{ $ingredient['full_name'] }}
                                                        @if (!empty($ingredient['lot_numbers']))
                                                            <br>
                                                            <small> @lang('lang_v1.lot_n_expiry'):
                                                                {{ $ingredient['lot_numbers'] }}</small>
                                                        @endif
                                                    </td>
                                                    <td style="width: 20%;text-align:center;">{{ @format_quantity($ingredient['quantity']) }}
                                                        {{ $ingredient['unit'] }}</td>
                                                    <td style="width: 20%;text-align:center;">{{ @format_quantity($ingredient['waste_percent']) }} %</td>
                                                    <td style="width: 20%;text-align:center;">{{ @format_quantity($ingredient['final_quantity']) }}
                                                        {{ $ingredient['unit'] }}</td>
                                                    @php
                                                        $price = $ingredient['total_price'];
                                                        
                                                        $total_ingredient_price += $price;
                                                    @endphp
                                                    <td style="width: 20%;text-align:center;">
                                                        <span class="display_currency"
                                                            data-currency_symbol="true">{{ $price }}</span>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                        @if (!empty($ingredient_groups))
                                            @foreach ($ingredient_groups as $ingredient_group)
                                                <tr>
                                                    <td colspan="5" class="bg-gray">
                                                        <strong>
                                                            {{ $ingredient_group['ig_name'] ?? '' }}
                                                        </strong>
                                                        @if (!empty($ingredient_group['ig_description']))
                                                            - {{ $ingredient_group['ig_description'] }}
                                                        @endif
                                                    </td>
                                                </tr>
                                                @foreach ($ingredient_group['ig_ingredients'] as $ingredient)
                                                    <tr>
                                                        <td>
                                                            {{ $ingredient['full_name'] }}
                                                            @if (!empty($ingredient['lot_numbers']))
                                                                <br>
                                                                <small> @lang('lang_v1.lot_n_expiry'):
                                                                    {{ $ingredient['lot_numbers'] }}</small>
                                                            @endif
                                                        </td>
                                                        <td>{{ @format_quantity($ingredient['quantity']) }}
                                                            {{ $ingredient['unit'] }}</td>
                                                        <td>{{ @format_quantity($ingredient['waste_percent']) }} %
                                                        </td>
                                                        <td>{{ @format_quantity($ingredient['final_quantity']) }}
                                                            {{ $ingredient['unit'] }}</td>
                                                        @php
                                                            $price = $ingredient['total_price'];
                                                            $total_ingredient_price += $price;
                                                        @endphp
                                                        <td>
                                                            <span class="display_currency"
                                                                data-currency_symbol="true">{{ $price }}</span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr class="tr-border">
                            <td class="td-border">MFD</td>
                            <td class="td-border">{{ @format_date($production_purchase->transaction_date) }}
                            </td>
                            <td class="td-border">EXP</td>
                            <td class="td-border">{{ @format_date($production_purchase->expire_date) }}</td>
                        </tr>
                        <tr class="tr-border">
                            <td class="td-border">Supervisor:</td>
                            <td class="td-border"></td>
                            <td class="td-border">Production Executive:</td>
                            <td class="td-border"></td>
                        </tr>
                        <tr class="tr-border">
                            <td class="td-border">Total Input Volume:</td>
                            <td class="td-border">{{ $production_sell->sell_lines->sum('quantity') }}</td>
                            <td class="td-border">Output Volume:
                                {{ $production_purchase->purchase_lines->sum('quantity') }}</td>
                            <td class="td-border">Recovery: {{ $production_purchase->recovery }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>
