<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\BusinessLocation;
use App\Transaction;
use App\MrnProducts;
use App\TransactionSellLine;
use DB;

class StoresController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('machines.view') && !auth()->user()->can('machines.create')) {
            abort(403, 'Unauthorized action.');
        }


        $pending_mrns = Transaction::where([
            ['type', '=', 'production_purchase'],
            ['status', '=', 'pending'],
            ['is_approved', '=', 1]
        ])->get();

        return view('stores.index')->with('pending_mrns', $pending_mrns);
    }

    public function load_ingrediants(Request $request)
    {
        $location = BusinessLocation::skip(1)->take(1)->first();
        $tr_sell_id = $request->tr_purchase_id + 1;

        $transaction_sell_line = TransactionSellLine::where('transaction_id', $tr_sell_id)
            ->leftJoin('products', 'products.id', '=', 'transaction_sell_lines.product_id')
            ->leftJoin('variation_location_details', 'variation_location_details.product_id', '=', 'transaction_sell_lines.product_id')
            ->where('variation_location_details.location_id', $location->id)
            ->where('variation_location_details.qty_available', '>', 0)
            ->orderBy('transaction_sell_lines.variation_id', 'asc')
            ->get();

        return response()->json($transaction_sell_line);
    }

    public function save_details(Request $request)
    {

        try{

            //get production purchase id
            $tr_purchase_id = '';
            $transaction_id = '';

            //save mrn products table
            for ($i = 0; $i < count($request->transaction_id); $i++) {

                //save mrn products table
                $MrnProducts = new MrnProducts;
                $MrnProducts->transaction_id = $request->transaction_id[$i];
                $MrnProducts->product_id = $request->product_id[$i];
                $MrnProducts->variation_id = $request->variation_id[$i];
                $MrnProducts->mrn_lot = $request->lot_number[$i];
                $MrnProducts->input_quantity = $request->input_qty[$i];
                $MrnProducts->save();
                $tr_purchase_id = $request->transaction_id[$i] - 1;
                $transaction_id = $request->transaction_id[$i];
            }
            
            //delete transacttion sell line details
            DB::table('transaction_sell_lines')->where('transaction_id', $transaction_id)->delete();

            //save transacttion sell line details for updated
            for ($i = 0; $i < count($request->transaction_id); $i++) {
                $TransactionSellLine = new TransactionSellLine;
                $TransactionSellLine->transaction_id = $request->transaction_id[$i];
                $TransactionSellLine->product_id = $request->product_id[$i];
                $TransactionSellLine->variation_id = $request->variation_id[$i];
                $TransactionSellLine->quantity = $request->input_qty[$i];
                $TransactionSellLine->tr_sell_lot_number = $request->lot_number[$i];

                $TransactionSellLine->mfg_waste_percent = $request->mfg_waste_percent[$i];
                $TransactionSellLine->mfg_ingredient_group_id = $request->mfg_ingredient_group_id[$i];
                $TransactionSellLine->quantity_returned = $request->quantity_returned[$i];
                $TransactionSellLine->unit_price_before_discount = $request->unit_price_before_discount[$i];
                $TransactionSellLine->unit_price = $request->unit_price[$i];
                $TransactionSellLine->line_discount_type = $request->line_discount_type[$i];
                $TransactionSellLine->line_discount_amount = $request->line_discount_amount[$i];
                $TransactionSellLine->unit_price_inc_tax = $request->unit_price_inc_tax[$i];
                $TransactionSellLine->item_tax = $request->item_tax[$i];
                // $TransactionSellLine->tax_id = $request->tax_id[$i];
                $TransactionSellLine->discount_id = $request->discount_id[$i];
                $TransactionSellLine->lot_no_line_id = $request->lot_no_line_id[$i];
                // $TransactionSellLine->sell_line_note = $request->sell_line_note[$i];
                $TransactionSellLine->so_line_id = $request->so_line_id[$i];
                $TransactionSellLine->so_quantity_invoiced = $request->so_quantity_invoiced[$i];
                $TransactionSellLine->woocommerce_line_items_id = $request->woocommerce_line_items_id[$i];
                $TransactionSellLine->res_service_staff_id = $request->res_service_staff_id[$i];
                $TransactionSellLine->res_line_order_status = $request->res_line_order_status[$i];
                $TransactionSellLine->parent_sell_line_id = $request->parent_sell_line_id[$i];
                $TransactionSellLine->children_type = 'modifier';
                $TransactionSellLine->sub_unit_id = $request->sub_unit_id[$i];
                $TransactionSellLine->ingredient_type = $request->ingredient_type[$i];

                $TransactionSellLine->save();
            }

            // update transaction status
            Transaction::where('id', $tr_purchase_id)
                            ->update([
                                'status' => 'stores_release',
                            ]);

            $output = [
                'success' => true,
                'msg' => __("lang_v1.added_success")
            ];


        }catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }
}
