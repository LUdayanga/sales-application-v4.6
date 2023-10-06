<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\QualityControll;
use App\QualityControllProducts;
use App\Transaction;
use App\PurchaseLine;
use App\TransactionSellLine;
use App\BusinessLocation;
use App\VariationLocationDetails;
use App\Variation;
use App\MrnProducts;
use App\ProductHasParameters;
use App\QualityProductParameters;
use App\TemporyQualityProductParameters;
use Modules\Manufacturing\Entities\MfgRecipe;
use Modules\Manufacturing\Entities\MfgRecipeIngredient;
use Illuminate\Support\Facades\DB;
use Mpdf\Tag\Q;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\TransactionUtil;


class QualityController extends Controller
{
    protected $transactionUtil;

    public function __construct(TransactionUtil $transactionUtil)
    {
        $this->transactionUtil = $transactionUtil;
    }
    
    public function index()
    {
        if (!auth()->user()->can('qc.view') && !auth()->user()->can('qc.create')) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {
            $QualityControll = QualityControll::select('qc_id', 'surname', 'first_name', 'last_name', 'qc_date', 'qc_ref_no', 'qc_step', 'qc_status', 'special_note', 'ref_type', 'ref_doc_no', 'qc_round')
                ->join('users', 'quality_controlls.user_id', '=', 'users.id')
                ->orderBy('quality_controlls.updated_at', 'desc');
                // ->get();

            $filter_qc_step = request()->get('filter_qc_step', null);
            $filter_qc_status = request()->get('filter_qc_status', null);

            if (!empty($filter_qc_step)) {
                $QualityControll = $QualityControll->where('qc_step', $filter_qc_step);
            }
            if (!empty($filter_qc_status)) {
                $QualityControll = $QualityControll->where('qc_status', $filter_qc_status);
            }

            // if (!empty(request()->start_date) && !empty(request()->end_date)) {
            //     $start = request()->start_date;
            //     $end =  request()->end_date;
            //     $QualityControll = $QualityControll->whereDate('qc_date', '>=', $start)
            //         ->whereDate('qc_date', '<=', $end);
            // }

            return Datatables::of($QualityControll)
                ->addColumn(
                    'action',
                    '@can("qc.view")
                    <a><button data-val="{{ $qc_id }}" class="btn btn-xs btn-success btn_view_qc"><i class="fas fa-eye"></i>View</button></a>
                    &nbsp;
                    @endcan
                    @can("qc.update")
                    @if($qc_status != "Approved" && $qc_status != "Disapproved")
                    <a><button data-val="{{ $qc_id }}" class="btn btn-xs btn-warning btn_edit_qc"><i class="glyphicon glyphicon-edit"></i> Edit</button></a>
                    @endif
                    @endcan
                    &nbsp;
                    @can("qc.delete")
                    @if($qc_status != "Approved" && $qc_status != "Disapproved")
                    <a><button data-val="{{ $qc_id }}" class="btn btn-xs btn-danger delete_qc_btn"><i class="glyphicon glyphicon-trash"></i> Delete</button></a>
                    @endif
                    @endcan'

                )
                ->editColumn('qc_date', function ($row) {
                    return  $row->qc_date;
                })
                ->editColumn('qc_ref_no', function ($row) {
                    return  $row->qc_ref_no;
                })
                ->editColumn('input_qc_step', function ($row) {
                    return  $row->qc_step;
                })
                ->editColumn('qc_step', function ($row) {

                    if ($row->qc_step == 'qcs_1') {
                        return  'Receiving the quality check';
                    } else if ($row->qc_step == 'qcs_2') {
                        return  'Material approval for production';
                    } else if ($row->qc_step == 'qcs_3') {
                        // return  '1st product approval' . ' (' . $row->qc_round . ')';
                        return  '1st product approval';
                    } else if ($row->qc_step == 'qcs_4') {
                        return 'Final product quality approval';
                    } else {
                        return 'Material balance';
                    }
                })
                ->addColumn('qc_status', function ($row) {
                    if ($row->qc_status == 'Checked') {
                        return '<button data-val=' . $row->qc_id . ' data-href=' . $row->qc_status . ' class="btn btn-xs btn-info btn_status">QC Checked</button>';
                    } else if ($row->qc_status == 'Approved') {
                        return '<button data-val=' . $row->qc_id . ' data-href=' . $row->qc_status . ' class="btn btn-xs btn-success btn_status">QC Approved</button>';
                    } else {
                        return '<button data-val=' . $row->qc_id . ' data-href=' . $row->qc_status . ' class="btn btn-xs btn-danger btn_status">QC Disapproved</button>';
                    }
                })
                ->editColumn('special_note', function ($row) {
                    return  $row->special_note;
                })
                ->editColumn('ref_details', function ($row) {
                    if ($row->ref_doc_no != '') {
                        return  $row->ref_type . ' (' . $row->ref_doc_no . ')';
                    } else {
                        return $row->ref_type;
                    }
                })
                ->editColumn('user', function ($row) {
                    return  $row->surname . ' ' . $row->first_name . ' ' . $row->last_name;
                })
                ->removeColumn('qc_id')
                ->rawColumns(['action', 'input_qc_step', 'qc_status'])
                ->make(true);
        }

        return view('quality_control.index');
    }

    public function create()
    {
        $grn_numbers = Transaction::where([
            ['type', '=', 'purchase'],
            ['status', '=', 'pending']
        ])->get();

        $release_productions = Transaction::where([
            ['type', '=', 'production_purchase'],
            ['status', '=', 'stores_release']
        ])->get();

        $hold_productions = Transaction::where([
            ['type', '=', 'production_purchase'],
            ['status', '=', 'hold']
        ])->get();

        $production_completes = PurchaseLine::where([
            ['is_qc4_checked', '!=', ''],
            ['is_qc4_checked', '=', 'available']
        ])->get();

        $production_qcs = PurchaseLine::where([
            ['is_production_qc', '!=', ''],
            ['is_production_qc', '=', 'available']
        ])->get();

        return view('quality_control.create')->with(compact('grn_numbers', 'release_productions', 'hold_productions', 'production_completes', 'production_qcs'));
    }

    public function load_product(Request $request)
    {
        if ($request->status == 'grn') {
            $PurchaseLine = PurchaseLine::where('transaction_id', '=', $request->id)
                ->leftJoin('products', 'products.id', '=', 'purchase_lines.product_id')
                ->get();
            return response()->json($PurchaseLine);
        } else if ($request->status == 'qc_2' || $request->status == 'qc_5') {

            $id = ($request->id + 1);

            $mrn_products = MrnProducts::where('transaction_id', '=', $id)
                ->leftJoin('products', 'products.id', '=', 'mrn_products.product_id')
                ->orderBy('mrn_products.variation_id', 'asc')
                ->get();

            return response()->json($mrn_products);
        } else if ($request->status == 'qc_3' || $request->status == 'qc_4') {

            $PurchaseLine = PurchaseLine::where('transaction_id', '=', $request->id)
                ->leftJoin('products', 'products.id', '=', 'purchase_lines.product_id')
                ->get();
            return response()->json($PurchaseLine);
        }
    }

    public function save_deatils(Request $request)
    {
        try {
            $user_id = $request->session()->get('user.id');
            // qc  step 1
            if ($request->qc_step == "qcs_1") {

                $QualityControll = new QualityControll;
                $QualityControll->qc_date = $request->qc_date;
                $QualityControll->qc_ref_no = $request->qc_sheet_no;
                $QualityControll->qc_type = $request->qc_type;
                $QualityControll->qc_step = $request->qc_step;
                if (isset($request->qc_finalize)) {
                    $QualityControll->qc_status = 'Approved';
                } else {
                    $QualityControll->qc_status = 'Checked';
                }
                $QualityControll->special_note = $request->special_note;
                $QualityControll->user_id = $user_id;

                $QualityControll->save();

                $last_id = $QualityControll->id;

                //save quality control product parameters
                $this->save_product_parameters($last_id);

                $tr_id = '';
                for ($i = 0; $i < count($request->transaction_id); $i++) {
                    $tr_id = $request->transaction_id[$i];
                    $QualityControllProducts = new QualityControllProducts;
                    $QualityControllProducts->qc_id = $last_id;
                    $QualityControllProducts->product_id = $request->product_id[$i];
                    $QualityControllProducts->variation_id = $request->variation_id[$i];
                    $QualityControllProducts->transaction_id = $request->transaction_id[$i];
                    $QualityControllProducts->recieved_qty = $request->quantity[$i];
                    $QualityControllProducts->qc_checked_qty = $request->checked_qty[$i];
                    $QualityControllProducts->qc_pass_qty = $request->passed_qty[$i];
                    $QualityControllProducts->qc_fail_qty = ($request->checked_qty[$i] - $request->passed_qty[$i]);
                    $QualityControllProducts->product_lot_no = $request->lot_number[$i];
                    $QualityControllProducts->product_qc_step = $request->qc_step;
                    if ($request->fail_descrpition[$i] != '') {
                        $QualityControllProducts->qc_fail_description = $request->fail_descrpition[$i];
                    }
                    $QualityControllProducts->save();

                    if (isset($request->qc_finalize)) {
                        PurchaseLine::where('transaction_id', $request->transaction_id[$i])
                            ->where('product_id', $request->product_id[$i])
                            ->where('variation_id', $request->variation_id[$i])
                            ->where('lot_number', $request->lot_number[$i])
                            ->update([
                                'qc_qty' => $request->passed_qty[$i],
                            ]);

                        //update purchase order details
                        $purchase = PurchaseLine::where('transaction_id', $request->transaction_id[$i])
                            ->where('product_id', $request->product_id[$i])
                            ->where('variation_id', $request->variation_id[$i])
                            ->first();

                        $purchase_order = PurchaseLine::where('id', $purchase->purchase_order_line_id)
                            ->first();

                        $purchase_order->qc_qty += $request->passed_qty[$i];
                        $purchase_order->save();

                        //update transaction details
                        Transaction::where('id', $request->transaction_id[$i])->update(['status' => 'qc_approved']);
                    } else {
                        PurchaseLine::where('transaction_id', $request->transaction_id[$i])
                            ->where('product_id', $request->product_id[$i])
                            ->where('variation_id', $request->variation_id[$i])
                            ->where('lot_number', $request->lot_number[$i])
                            ->update([
                                'qc_qty' => 0,
                            ]);
                        Transaction::where('id', $request->transaction_id[$i])->update(['status' => 'qc_checked']);
                    }
                }


                $transaction_ref_no = Transaction::where('id', $tr_id)->first();

                QualityControll::where('qc_id', $last_id)
                    ->update([
                        'ref_doc_no' => $transaction_ref_no->ref_no,
                        'ref_type' => 'purchase',
                        'transaction_id' => $tr_id,
                    ]);

                // qc step 2        
            } else if ($request->qc_step == "qcs_2") {

                $business_id = request()->session()->get('user.business_id');

                $location_id = BusinessLocation::select('id')->skip(2)->take(1)->first();

                //save details for quality control
                $QualityControll = new QualityControll;
                $QualityControll->qc_date = $request->qc_date;
                $QualityControll->qc_ref_no = $request->qc_sheet_no;
                $QualityControll->qc_type = $request->qc_type;
                $QualityControll->qc_step = $request->qc_step;
                if (isset($request->qc_finalize)) {
                    $QualityControll->qc_status = 'Approved';
                } else {
                    $QualityControll->qc_status = 'Checked';
                }
                $QualityControll->special_note = $request->special_note;
                $QualityControll->user_id = $user_id;

                $QualityControll->save();

                $last_id = $QualityControll->id;

                //save quality control product parameters
                $this->save_product_parameters($last_id);

                $transaction = new Transaction;

                $transaction->business_id = $business_id;
                $transaction->location_id = $location_id->id;
                $transaction->type = 'qc_2_transfer';

                if (isset($request->qc_finalize)) {
                    $transaction->status = 'completed';
                } else {
                    $transaction->status = 'pending';
                }
                $transaction->ref_no = $request->qc_sheet_no;
                $transaction->transaction_date = $request->qc_date;
                $transaction->contact_id = $user_id;
                $transaction->created_by = $user_id;

                $transaction->save();

                $transaction_id = $transaction->id;

                $tr_id_sell = '';
                $tr_id_purchase = '';

                for ($i = 0; $i < count($request->product_id); $i++) {

                    $QualityControllProducts = new QualityControllProducts;
                    $QualityControllProducts->qc_id = $last_id;
                    $QualityControllProducts->product_id = $request->product_id[$i];
                    $QualityControllProducts->variation_id = $request->variation_id[$i];
                    $QualityControllProducts->transaction_id = $request->transaction_id[$i];
                    $QualityControllProducts->recieved_qty = $request->quantity[$i];
                    $QualityControllProducts->qc_checked_qty = $request->checked_qty[$i];
                    $QualityControllProducts->qc_pass_qty = $request->passed_qty[$i];
                    $QualityControllProducts->qc_fail_qty = ($request->checked_qty[$i] - $request->passed_qty[$i]);
                    $QualityControllProducts->product_lot_no = $request->lot_number[$i];
                    $QualityControllProducts->product_qc_step = $request->qc_step;
                    if ($request->fail_descrpition[$i] != '') {
                        $QualityControllProducts->qc_fail_description = $request->fail_descrpition[$i];
                    }
                    $QualityControllProducts->save();

                    $tr_id_sell = $request->transaction_id[$i];
                    $tr_id_purchase = $tr_id_sell - 1;

                    if (isset($request->qc_finalize)) {

                        $location_from = BusinessLocation::skip(1)->take(1)->first();

                        $previous_location_qty = VariationLocationDetails::select('qty_available')
                            ->where('location_id', $location_from->id)
                            ->where('product_id', $request->product_id[$i])
                            ->where('variation_id', $request->variation_id[$i])
                            ->where('lot_number', $request->lot_number[$i])
                            ->first();

                        $old_qty = $previous_location_qty['qty_available'];
                        $new_qty = $request->checked_qty[$i];
                        $diff = $old_qty - $new_qty;

                        $variation = Variation::find($request->variation_id[$i]);

                        VariationLocationDetails::where('location_id', $location_from->id)
                            ->where('product_id', $request->product_id[$i])
                            ->where('variation_id', $request->variation_id[$i])
                            ->where('product_variation_id', $variation->product_variation_id)
                            ->where('lot_number', $request->lot_number[$i])
                            ->update([
                                'qty_available' => $diff,
                            ]);
                    }
                }

                //update final total in transaction
                if (isset($request->qc_finalize)) {

                    //update final output after qc finalize in production
                    $main_product_variation = PurchaseLine::where('transaction_id', $tr_id_purchase)->first();

                    $recipe = MfgRecipe::where('variation_id', $main_product_variation->variation_id)->first();

                    $recipe_ingredients = MfgRecipeIngredient::where('mfg_recipe_id', $recipe->id)
                        ->orderBy('variation_id', 'asc')
                        ->get();
                    $data = [];

                    foreach ($recipe_ingredients as $recipe_ingredient) {

                        for ($i = 0; $i < count($request->transaction_id); $i++) {
                            if ($request->variation_id[$i] == $recipe_ingredient->variation_id) {
                                $output = ($request->quantity[$i] - ($request->checked_qty[$i] - $request->passed_qty[$i])) / $recipe_ingredient->quantity;
                                $data[] = [
                                    'output' => intval($output),
                                ];
                            }
                        }
                    }
                    $final_output = (min($data));

                    // update purchase line quantity
                    PurchaseLine::where('transaction_id', $tr_id_purchase)
                        ->update([
                            'quantity' => $final_output['output'],
                            'qc_qty' => $final_output['output'],
                        ]);

                    Transaction::where('id', $tr_id_purchase)
                        ->update([
                            'final_total' => ($main_product_variation->pp_without_discount * $final_output['output']),
                        ]);

                    Transaction::where('id', $tr_id_sell)
                        ->update([
                            'final_total' => ($main_product_variation->pp_without_discount * $final_output['output']),
                        ]);

                    //update sell quantity
                    foreach ($recipe_ingredients as $recipe_ingredient) {
                        for ($i = 0; $i < count($request->transaction_id); $i++) {
                            if ($request->variation_id[$i] == $recipe_ingredient->variation_id) {

                                $sell_quantity = TransactionSellLine::where('transaction_id', $tr_id_sell)
                                    ->where('product_id', $request->product_id[$i])
                                    ->where('variation_id', $request->variation_id[$i])
                                    ->where('tr_sell_lot_number', $request->lot_number[$i])
                                    ->first();

                                $current_sell_qty = $sell_quantity->quantity;
                                $sell_final_output['quantity'] = $final_output['output'] * $recipe_ingredient->quantity;
                                $sell_quantity->update($sell_final_output);

                                $variation = Variation::find($request->variation_id[$i]);

                                $vld_quantity = VariationLocationDetails::where('location_id', $location_from->id)
                                    ->where('product_id', $request->product_id[$i])
                                    ->where('product_variation_id', $variation->product_variation_id)
                                    ->where('variation_id', $request->variation_id[$i])
                                    ->where('lot_number', $request->lot_number[$i])
                                    ->first();

                                $current_vld_qty = $vld_quantity->qty_available;
                                $increase_vld['qty_available'] = '';

                                if (($final_output['output'] * $recipe_ingredient->quantity) < $request->passed_qty[$i]) {
                                    $increase_qty = $request->passed_qty[$i] - ($final_output['output'] * $recipe_ingredient->quantity);
                                    $increase_vld['qty_available'] = $current_vld_qty + $increase_qty;
                                    $vld_quantity->update($increase_vld);
                                }
                            }
                        }
                    }
                }

                QualityControll::where('qc_id', $last_id)
                    ->update([
                        'ref_doc_no' => $request->production_sheet_no,
                        'ref_type' => 'stores to production transfer',
                        'transaction_id' => $transaction_id,
                    ]);

                if (isset($request->qc_finalize)) {
                    Transaction::where('id', $tr_id_purchase)
                        ->update([
                            'status' => 'qc_approved',
                        ]);
                } else {
                    Transaction::where('id', $tr_id_purchase)
                        ->update([
                            'status' => 'qc_checked',
                        ]);
                }

                //  qc step 3        
            } else if ($request->qc_step == "qcs_3") {

                //check how much round as same production qc
                $qc_round = '';

                $check_if_already = QualityControll::where('qc_ref_no', $request->qc_sheet_no)->get();

                if (empty($check_if_already)) {
                    $qc_round =  '1 Round';
                } else {
                    $qc_round =  count($check_if_already) + 1 . ' Round';
                }

                $business_id = request()->session()->get('user.business_id');

                $location_id = BusinessLocation::select('id')->skip(2)->take(1)->first();

                //save quality control  detail in quality control table
                $QualityControll = new QualityControll;
                $QualityControll->qc_date = $request->qc_date;
                $QualityControll->qc_ref_no = $request->qc_sheet_no;
                $QualityControll->qc_type = $request->qc_type;
                $QualityControll->qc_step = $request->qc_step;

                if (isset($request->qc_finalize)) {
                    $QualityControll->qc_status = 'Approved';
                } else if (isset($request->qc_unapprove)) {
                    $QualityControll->qc_status = 'Disapproved';
                } else {
                    $QualityControll->qc_status = 'Checked';
                }

                $QualityControll->special_note = $request->special_note;
                $QualityControll->user_id = $user_id;

                $QualityControll->save();

                //get qc id
                $last_id = $QualityControll->id;

                //save quality control product parameters
                $this->save_product_parameters($last_id);

                // save new transaction
                $transaction = new Transaction;
                $transaction->business_id = $business_id;
                $transaction->location_id = $location_id->id;
                $transaction->type = 'qc_3_transfer';

                if (isset($request->qc_finalize) || isset($request->qc_unapprove)) {
                    $transaction->status = 'completed';
                } else {
                    $transaction->status = 'pending';
                }
                $transaction->ref_no = $request->qc_sheet_no;
                $transaction->transaction_date = $request->qc_date;
                $transaction->contact_id = $user_id;
                $transaction->created_by = $user_id;

                $transaction->save();

                $transaction_id = $transaction->id;

                $tr_id_sell = '';
                $tr_id_purchase = '';

                for ($i = 0; $i < count($request->transaction_id); $i++) {

                    $QualityControllProducts = new QualityControllProducts;
                    $QualityControllProducts->qc_id = $last_id;
                    $QualityControllProducts->product_id = $request->product_id[$i];
                    $QualityControllProducts->variation_id = $request->variation_id[$i];
                    $QualityControllProducts->transaction_id = $request->transaction_id[$i];
                    $QualityControllProducts->recieved_qty = $request->quantity[$i];
                    $QualityControllProducts->qc_checked_qty = $request->checked_qty[$i];
                    $QualityControllProducts->qc_pass_qty = $request->passed_qty[$i];
                    $QualityControllProducts->qc_fail_qty = ($request->checked_qty[$i] - $request->passed_qty[$i]);
                    $QualityControllProducts->product_lot_no = $request->lot_number[$i];
                    $QualityControllProducts->product_qc_step = $request->qc_step;

                    if ($request->fail_descrpition[$i] != '') {
                        $QualityControllProducts->qc_fail_description = $request->fail_descrpition[$i];
                    }

                    $QualityControllProducts->save();

                    //update transaction status
                    $tr_id_purchase = $request->transaction_id[$i];
                    $tr_id_sell = $tr_id_purchase + 1;

                    if (isset($request->qc_finalize)) {
                        Transaction::where('id', $tr_id_purchase)
                            ->update([
                                'status' => 'qc_approved',
                            ]);
                    } else if (isset($request->qc_unapprove)) {
                        Transaction::where('id', $tr_id_purchase)
                            ->update([
                                'status' => 'hold_production',
                            ]);
                    } else {
                        Transaction::where('id', $tr_id_purchase)
                            ->update([
                                'status' => 'qc_checked',
                            ]);
                    }

                    //update qc round
                    QualityControll::where('qc_id', $last_id)
                        ->update([
                            'ref_doc_no' => $request->production_sheet_no,
                            'qc_round' => $qc_round,
                            'ref_type' => 'first production checked',
                            'transaction_id' => $transaction_id,
                        ]);

                    //update final total in transaction
                    $fail_quantity = '';
                    if (isset($request->qc_finalize)) {
                        //update purchase line quantity
                        $fail_quantity = $request->checked_qty[$i] - $request->passed_qty[$i];
                    }

                    //get previous ingrediant cost before update quantity
                    if ($i == 0) {
                        $purchase_details = PurchaseLine::where('transaction_id', $tr_id_purchase)
                            ->first();

                        $ingrediant_cost = ($purchase_details->pp_without_discount);
                    }

                    //update purchase line details
                    if (isset($request->qc_finalize)) {
                        PurchaseLine::where('transaction_id', $tr_id_purchase)
                            ->update([
                                'quantity' => $request->quantity[$i] - $fail_quantity,
                                'qc_qty' => $request->quantity[$i] - $fail_quantity,
                            ]);
                    } else if (isset($request->qc_unapprove)) {
                        PurchaseLine::where('transaction_id', $tr_id_purchase)
                            ->update([
                                'quantity' => $request->passed_qty[$i],
                                'qc_qty' => $request->passed_qty[$i],
                            ]);
                    }

                    //update prices
                    $get_details = PurchaseLine::where('transaction_id', $tr_id_purchase)
                        ->first();

                    //update transaction final total
                    Transaction::where('id', $tr_id_purchase)
                        ->update([
                            'final_total' => $get_details->quantity * $ingrediant_cost,
                        ]);

                    Transaction::where('id', $tr_id_sell)
                        ->update([
                            'final_total' => $get_details->quantity * $ingrediant_cost,
                        ]);

                    //get mfg recipe details
                    $get_mfg_details = MfgRecipe::where('product_id', $get_details->product_id)
                        ->where('variation_id', $get_details->variation_id)
                        ->first();

                    $get_ingradient_details = MfgRecipeIngredient::where('mfg_recipe_id', $get_mfg_details->id)->get();

                    if (isset($request->qc_finalize)) {
                        foreach ($get_ingradient_details as $get_ingradient_detail) {
                            $final_quantity = $get_details->quantity * $get_ingradient_detail->quantity;

                            //update transaction sell line
                            TransactionSellLine::where('transaction_id', $tr_id_sell)
                                ->where('variation_id', $get_ingradient_detail->variation_id)
                                ->update([
                                    'quantity' => $final_quantity,
                                ]);
                        }
                    } else if (isset($request->qc_unapprove)) {

                        foreach ($get_ingradient_details as $get_ingradient_detail) {

                            $final_quantity = $get_details->quantity * $get_ingradient_detail->quantity;

                            //get before transaction sell line quantity
                            $tr_sell_details = TransactionSellLine::where('transaction_id', $tr_id_sell)
                                ->where('variation_id', $get_ingradient_detail->variation_id)
                                ->first();

                            //update transaction sell line quantity
                            $new_quantity['quantity'] = $final_quantity;
                            $tr_sell_details->update($new_quantity);
                        }
                    }
                }

                // qc step 4    
            } else if ($request->qc_step == "qcs_4") {
                $business_id = request()->session()->get('user.business_id');

                $location_id = BusinessLocation::select('id')->skip(2)->take(1)->first();

                //save quality control  detail in quality control table
                $QualityControll = new QualityControll;
                $QualityControll->qc_date = $request->qc_date;
                $QualityControll->qc_ref_no = $request->qc_sheet_no;
                $QualityControll->qc_type = $request->qc_type;
                $QualityControll->qc_step = $request->qc_step;
                if (isset($request->qc_finalize)) {
                    $QualityControll->qc_status = 'Approved';
                } else {
                    $QualityControll->qc_status = 'Checked';
                }
                $QualityControll->special_note = $request->special_note;
                $QualityControll->user_id = $user_id;

                $QualityControll->save();

                //get qc id
                $last_id = $QualityControll->id;

                //save quality control product parameters
                $this->save_product_parameters($last_id);

                // save new transaction
                $transaction = new Transaction;
                $transaction->business_id = $business_id;
                $transaction->location_id = $location_id->id;
                $transaction->type = 'qc_4_transfer';

                if (isset($request->qc_finalize)) {
                    $transaction->status = 'completed';
                } else {
                    $transaction->status = 'pending';
                }
                $transaction->ref_no = $request->qc_sheet_no;
                $transaction->transaction_date = $request->qc_date;
                $transaction->contact_id = $user_id;
                $transaction->created_by = $user_id;

                $transaction->save();

                $transaction_id = $transaction->id;

                $tr_id_sell = '';
                $tr_id_purchase = '';
                $ingrediant_cost = '';

                for ($i = 0; $i < count($request->transaction_id); $i++) {
                    $QualityControllProducts = new QualityControllProducts;
                    $QualityControllProducts->qc_id = $last_id;
                    $QualityControllProducts->product_id = $request->product_id[$i];
                    $QualityControllProducts->variation_id = $request->variation_id[$i];
                    $QualityControllProducts->transaction_id = $request->transaction_id[$i];
                    $QualityControllProducts->recieved_qty = $request->production_qty[$i];
                    $QualityControllProducts->qc_checked_qty = $request->checked_qty[$i];
                    $QualityControllProducts->qc_pass_qty = $request->passed_qty[$i];
                    $QualityControllProducts->qc_fail_qty = ($request->checked_qty[$i] - $request->passed_qty[$i]);
                    $QualityControllProducts->product_lot_no = $request->lot_number[$i];
                    $QualityControllProducts->product_qc_step = $request->qc_step;

                    if ($request->fail_descrpition[$i] != '') {
                        $QualityControllProducts->qc_fail_description = $request->fail_descrpition[$i];
                    }

                    $QualityControllProducts->save();

                    $tr_id_purchase = $request->transaction_id[$i];
                    $tr_id_sell = $tr_id_purchase + 1;

                    //update purchase line details
                    if (isset($request->qc_finalize)) {
                        PurchaseLine::where('transaction_id', $tr_id_purchase)
                            ->update([
                                'is_qc4_checked' => 'complete',
                            ]);
                    } else {
                        PurchaseLine::where('transaction_id', $tr_id_purchase)
                            ->update([
                                'is_qc4_checked' => 'checked',
                            ]);
                    }

                    if (isset($request->qc_finalize)) {

                        //update purchase line quantity and "is_qc4_checked" as complete
                        $fail_quantity = $request->checked_qty[$i] - $request->passed_qty[$i];

                        //get previous ingrediant cost before update quantity
                        if ($i == 0) {
                            $purchase_details = PurchaseLine::where('transaction_id', $tr_id_purchase)
                                ->first();

                            $ingrediant_cost = ($purchase_details->pp_without_discount);
                        }

                        //update purchase line details
                        PurchaseLine::where('transaction_id', $tr_id_purchase)
                            ->update([
                                'quantity' => $request->production_qty[$i] - $fail_quantity,
                                'qc_qty' => $request->production_qty[$i] - $fail_quantity,
                            ]);

                        //update prices
                        $get_details = PurchaseLine::where('transaction_id', $tr_id_purchase)
                            ->first();

                        //update transaction final total
                        Transaction::where('id', $tr_id_purchase)
                            ->update([
                                'final_total' => $get_details->quantity * $ingrediant_cost,
                            ]);

                        Transaction::where('id', $tr_id_sell)
                            ->update([
                                'final_total' => $get_details->quantity * $ingrediant_cost,
                            ]);

                        //get mfg recipe details
                        $get_mfg_details = MfgRecipe::where('product_id', $get_details->product_id)
                            ->where('variation_id', $get_details->variation_id)
                            ->first();

                        $get_ingradient_details = MfgRecipeIngredient::where('mfg_recipe_id', $get_mfg_details->id)->get();

                        $location_production = BusinessLocation::select('id')->skip(2)->take(1)->first();
                        $location_outlet = BusinessLocation::select('id')->skip(3)->take(1)->first();

                        foreach ($get_ingradient_details as $get_ingradient_detail) {
                            $final_quantity = $get_details->quantity * $get_ingradient_detail->quantity;

                            //update transaction sell line
                            TransactionSellLine::where('transaction_id', $tr_id_sell)
                                ->where('variation_id', $get_ingradient_detail->variation_id)
                                ->update([
                                    'quantity' => $final_quantity,
                                ]);
                        }

                        //decreate quantity in production location
                        $vld_details = VariationLocationDetails::where('location_id', $location_production->id)
                            ->where('product_id', $get_details->product_id)
                            ->where('variation_id', $get_details->variation_id)
                            ->where('lot_number', $get_details->lot_number)
                            ->first();

                        $current_vld_qty = $vld_details->qty_available;
                        $decrease_vld['qty_available'] = $current_vld_qty - $request->checked_qty[$i];
                        $vld_details->update($decrease_vld);

                        //save product outlet location
                        $check_if_already = VariationLocationDetails::where('location_id', $location_outlet->id)
                            ->where('product_id', $get_details->product_id)
                            ->where('variation_id', $get_details->variation_id)
                            ->where('lot_number', $get_details->lot_number)
                            ->first();

                        if (!empty($check_if_already)) {
                            //update production in outlet location
                            $current_qty = $check_if_already->qty_available;
                            $increase_vld['qty_available'] = $current_qty + $request->passed_qty[$i];
                            $check_if_already->update($increase_vld);
                        } else {
                            //save production in outlet location

                            $variation = Variation::find($request->variation_id[$i]);

                            $insert_vld = new VariationLocationDetails;
                            $insert_vld->product_id = $request->product_id[$i];
                            $insert_vld->product_variation_id = $variation->product_variation_id;
                            $insert_vld->variation_id = $request->variation_id[$i];
                            $insert_vld->location_id = $location_outlet->id;
                            $insert_vld->qty_available = $request->passed_qty[$i];
                            $insert_vld->lot_number = $request->lot_number[$i];

                            $insert_vld->save();
                        }
                    }

                    //update qc round
                    QualityControll::where('qc_id', $last_id)
                        ->update([
                            'ref_doc_no' => $request->lot_number[$i],
                            'ref_type' => 'production',
                            'transaction_id' => $transaction_id,
                        ]);
                }
            } else {
                $QualityControll = new QualityControll;
                $QualityControll->qc_date = $request->qc_date;
                $QualityControll->qc_ref_no = $request->qc_sheet_no;
                $QualityControll->qc_type = $request->qc_type;
                $QualityControll->qc_step = $request->qc_step;
                if (isset($request->qc_finalize)) {
                    $QualityControll->qc_status = 'Approved';
                } else {
                    $QualityControll->qc_status = 'Checked';
                }
                $QualityControll->special_note = $request->special_note;
                $QualityControll->user_id = $user_id;

                $QualityControll->save();

                $last_id = $QualityControll->id;

                //save quality control product parameters
                $this->save_product_parameters($last_id);

                $business_id = request()->session()->get('user.business_id');

                $location_id = BusinessLocation::select('id')->skip(2)->take(1)->first();

                // save new transaction
                $transaction = new Transaction;
                $transaction->business_id = $business_id;
                $transaction->location_id = $location_id->id;
                $transaction->type = 'qc_5_transfer';

                if (isset($request->qc_finalize)) {
                    $transaction->status = 'completed';
                } else {
                    $transaction->status = 'pending';
                }
                $transaction->ref_no = $request->qc_sheet_no;
                $transaction->transaction_date = $request->qc_date;
                $transaction->contact_id = $user_id;
                $transaction->created_by = $user_id;

                $transaction->save();

                $transaction_id = $transaction->id;

                $tr_id = '';

                for ($i = 0; $i < count($request->transaction_id); $i++) {
                    $tr_id = $request->transaction_id[$i];
                    $QualityControllProducts = new QualityControllProducts;
                    $QualityControllProducts->qc_id = $last_id;
                    $QualityControllProducts->product_id = $request->product_id[$i];
                    $QualityControllProducts->variation_id = $request->variation_id[$i];
                    $QualityControllProducts->transaction_id = $request->transaction_id[$i];
                    $QualityControllProducts->recieved_qty = $request->received_qty[$i];
                    $QualityControllProducts->qc_checked_qty = $request->checked_qty[$i];
                    $QualityControllProducts->qc_pass_qty = $request->passed_qty[$i];
                    $QualityControllProducts->qc_fail_qty = ($request->checked_qty[$i] - $request->passed_qty[$i]);
                    $QualityControllProducts->product_lot_no = $request->lot_number[$i];
                    $QualityControllProducts->product_qc_step = $request->qc_step;
                    if ($request->fail_descrpition[$i] != '') {
                        $QualityControllProducts->qc_fail_description = $request->fail_descrpition[$i];
                    }
                    $QualityControllProducts->save();

                    //update purchase line qc 4 status
                    if (isset($request->qc_finalize)) {
                        PurchaseLine::where('transaction_id', $tr_id)
                            ->update([
                                'is_production_qc' => 'complete',
                            ]);
                    } else {
                        PurchaseLine::where('transaction_id', $tr_id)
                            ->update([
                                'is_production_qc' => 'checked',
                            ]);
                    }

                    if (isset($request->qc_finalize)) {

                        $location_stores = BusinessLocation::select('id')->skip(1)->take(1)->first();

                        //update quantity in stores location
                        $vld_details = VariationLocationDetails::where('location_id', $location_stores->id)
                            ->where('product_id', $request->product_id[$i])
                            ->where('variation_id', $request->variation_id[$i])
                            ->where('lot_number', $request->lot_number[$i])
                            ->first();

                        $current_vld_qty = $vld_details->qty_available;
                        $increase['qty_available'] = $current_vld_qty + $request->passed_qty[$i];
                        $vld_details->update($increase);
                    }

                    //update qc round
                    QualityControll::where('qc_id', $last_id)
                        ->update([
                            'ref_doc_no' => $request->qc_type,
                            'ref_type' => 'production to stores transfer',
                            'transaction_id' => $transaction_id,
                        ]);
                }
            }

            $output = [
                'success' => true,
                'msg' => __("lang_v1.added_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    public function load_edit_modal(Request $request)
    {
        $QualityControls = QualityControll::where('quality_controlls.qc_id', '=', $request->id)
            ->leftJoin('quality_controll_products', 'quality_controlls.qc_id', '=', 'quality_controll_products.qc_id')
            ->leftJoin('products', 'products.id', '=', 'quality_controll_products.product_id')
            ->get();

        return response()->json($QualityControls);
    }

    public function view_qc_deatils(Request $request)
    {
        $QualityControls = QualityControll::where('quality_controlls.qc_id', '=', $request->id)
            ->leftJoin('quality_controll_products', 'quality_controlls.qc_id', '=', 'quality_controll_products.qc_id')
            ->leftJoin('products', 'products.id', '=', 'quality_controll_products.product_id')
            ->get();

        echo '
            <thead>
            <tr>
                <th class="text-center col-sm-3">Product</th>
                <th class="text-center col-sm-2">Lot No</th>
                <th class="text-center col-sm-2">Recieved Quantity</th>
                <th class="text-center col-sm-2">Checked Quantity</th>
                <th class="text-center col-sm-2">Passed Quantity</th>
                <th class="text-center col-sm-3">Description</th>
            </tr>
        </thead>
            ';

        foreach ($QualityControls as $QualityControl) {
            echo '<tbody>
                <tr>
                    <td class="col-sm-3"> ' . $QualityControl['name'] . ' </td>
                    <td class="col-sm-2"> ' . $QualityControl['product_lot_no'] . ' </td>
                    <td class="col-sm-2"> ' . $QualityControl['recieved_qty'] . ' </td>
                    <td class="col-sm-2"> ' . $QualityControl['qc_checked_qty'] . ' </td>
                    <td class="col-sm-2"> ' . $QualityControl['qc_pass_qty'] . ' </td>
                    <td class="col-sm-3"> ' . $QualityControl['qc_fail_description'] . ' </td>

            </tr>
            </tbody>
            <tr class="explode">
            <td colspan="12">
                <table class="table table-condensed table-bordered table-striped">
                <thead>
                    <tr style="background: #CCC;">
                        <th class="text-center class="col-sm-3">Parameter</th>
                        <th class="text-center class="col-sm-1">Status</th>
                        <th class="text-center class="col-sm-3">Description</th>
                    </tr>
                </thead>
              <tbody>';

            $get_parameters = QualityProductParameters::where('parent_qc_id', '=', $request->id)
                ->where('parent_product_id', '=', $QualityControl['product_id'])
                ->where('parameter_product_lot', '=', $QualityControl['product_lot_no'])
                ->leftJoin('product_parameters', 'product_parameters.id', '=', 'quality_product_parameters.parent_parameter_id')
                ->get();

            foreach ($get_parameters as $get_parameter) {
                echo  '<tr style="">
                  <td class="col-sm-4"> ' . $get_parameter['parameter_name'] . ' </td>';

                if ($get_parameter['qc_param_status'] == 'pass') {
                    echo '<td class="col-sm-1"><button type="button" class="btn btn-success btn-xs">Pass</button></td>';
                    // echo '<td class="col-sm-2"><span style="background: LimeGreen; color: white; padding: 4px; border-radius: 10px;">Pass</span></td>';
                } else {
                    echo '<td class="col-sm-1"><button type="button" class="btn btn-danger btn-xs">Fail</button></td>';
                    // echo '<td class="col-sm-2"><span style="background: OrangeRed; color: white; padding: 4px; border-radius: 10px;">Fail</span></td>';
                }

                echo  '<td class="col-sm-7"> ' . $get_parameter['qc_param_description'] . ' </td>
                </tr>';
            }

            echo '</tbody>
          </table>
         </td>
      </tr>';
        }
    }

    public function update_deatils(Request $request)
    {
        try {
            $user_id = $request->session()->get('user.id');
            $qc_status = '';
            if (isset($request->qc_finalize)) {
                $qc_status = 'Approved';
            } else if (isset($request->qc_unapprove)) {
                $qc_status = 'Disapproved';
            } else {
                $qc_status = 'Checked';
            }

            QualityControll::where('qc_id', $request->qc_id)
                ->update([
                    'qc_date' => $request->qc_date,
                    'qc_ref_no' => $request->qc_sheet_no,
                    'qc_status' => $qc_status,
                    'special_note' => $request->special_note,
                    'user_id' => $user_id,
                ]);

            $get_qc_step = QualityControll::where('qc_id', $request->qc_id)->first();

            DB::table('quality_controll_products')->where('qc_id', $request->qc_id)->delete();

            if ($get_qc_step->qc_step == "qcs_1") {
                for ($i = 0; $i < count($request->transaction_id); $i++) {
                    $QualityControllProducts = new QualityControllProducts;
                    $QualityControllProducts->qc_id = $request->qc_id;
                    $QualityControllProducts->product_id = $request->product_id[$i];
                    $QualityControllProducts->variation_id = $request->variation_id[$i];
                    $QualityControllProducts->transaction_id = $request->transaction_id[$i];
                    $QualityControllProducts->recieved_qty = $request->quantity[$i];
                    $QualityControllProducts->qc_checked_qty = $request->checked_qty[$i];
                    $QualityControllProducts->qc_pass_qty = $request->passed_qty[$i];
                    $QualityControllProducts->qc_fail_qty = ($request->checked_qty[$i] - $request->passed_qty[$i]);
                    $QualityControllProducts->product_lot_no = $request->lot_number[$i];
                    $QualityControllProducts->product_qc_step = $request->qc_step;
                    if ($request->fail_descrpition[$i] != '') {
                        $QualityControllProducts->qc_fail_description = $request->fail_descrpition[$i];
                    }
                    $QualityControllProducts->save();

                    if (isset($request->qc_finalize)) {
                        PurchaseLine::where('transaction_id', $request->transaction_id[$i])
                            ->where('product_id', $request->product_id[$i])
                            ->where('variation_id', $request->variation_id[$i])
                            ->update([
                                'qc_qty' => $request->passed_qty[$i],
                            ]);

                        //update purchase order details
                        $purchase = PurchaseLine::where('transaction_id', $request->transaction_id[$i])
                            ->where('product_id', $request->product_id[$i])
                            ->where('variation_id', $request->variation_id[$i])
                            ->first();

                        $purchase_order_id = PurchaseLine::where('id', $purchase->purchase_order_line_id)
                            ->first();

                        $purchase_order_id->qc_qty += $request->passed_qty[$i];
                        $purchase_order_id->save();

                        Transaction::where('id', $request->transaction_id[$i])->update(['status' => 'qc_approved']);
                    } else {
                        PurchaseLine::where('transaction_id', $request->transaction_id[$i])
                            ->where('product_id', $request->product_id[$i])
                            ->where('variation_id', $request->variation_id[$i])
                            ->update([
                                'qc_qty' => 0,
                            ]);

                        Transaction::where('id', $request->transaction_id[$i])->update(['status' => 'qc_checked']);
                    }
                }
            } else if ($get_qc_step->qc_step == "qcs_2") {

                $location_id = BusinessLocation::select('id')->skip(2)->take(1)->first();

                $tr_id_sell = '';
                $tr_id_purchase = '';

                for ($i = 0; $i < count($request->product_id); $i++) {
                    $QualityControllProducts = new QualityControllProducts;
                    $QualityControllProducts->qc_id = $request->qc_id;
                    $QualityControllProducts->product_id = $request->product_id[$i];
                    $QualityControllProducts->variation_id = $request->variation_id[$i];
                    $QualityControllProducts->transaction_id = $request->transaction_id[$i];
                    $QualityControllProducts->recieved_qty = $request->quantity[$i];
                    $QualityControllProducts->qc_checked_qty = $request->checked_qty[$i];
                    $QualityControllProducts->qc_pass_qty = $request->passed_qty[$i];
                    $QualityControllProducts->qc_fail_qty = ($request->checked_qty[$i] - $request->passed_qty[$i]);
                    $QualityControllProducts->product_lot_no = $request->lot_number[$i];
                    $QualityControllProducts->product_qc_step = $request->qc_step;
                    if ($request->fail_descrpition[$i] != '') {
                        $QualityControllProducts->qc_fail_description = $request->fail_descrpition[$i];
                    }
                    $QualityControllProducts->save();

                    $tr_id_sell = $request->transaction_id[$i];
                    $tr_id_purchase = $tr_id_sell - 1;

                    if (isset($request->qc_finalize)) {

                        $location_from = BusinessLocation::skip(1)->take(1)->first();

                        $previous_location_qty = VariationLocationDetails::select('qty_available')
                            ->where('location_id', $location_from->id)
                            ->where('product_id', $request->product_id[$i])
                            ->where('variation_id', $request->variation_id[$i])
                            ->where('lot_number', $request->lot_number[$i])
                            ->first();

                        $old_qty = $previous_location_qty['qty_available'];
                        $new_qty = $request->checked_qty[$i];
                        $diff = $old_qty - $new_qty;

                        $variation = Variation::find($request->variation_id[$i]);

                        VariationLocationDetails::where('location_id', $location_from->id)
                            ->where('product_id', $request->product_id[$i])
                            ->where('variation_id', $request->variation_id[$i])
                            ->where('product_variation_id', $variation->product_variation_id)
                            ->where('lot_number', $request->lot_number[$i])
                            ->update([
                                'qty_available' => $diff,
                            ]);
                    }
                }

                //update final total in transaction
                if (isset($request->qc_finalize)) {

                    //update final output after qc finalize in production
                    $main_product_variation = PurchaseLine::where('transaction_id', $tr_id_purchase)->first();

                    $recipe = MfgRecipe::where('variation_id', $main_product_variation->variation_id)->first();

                    $recipe_ingredients = MfgRecipeIngredient::where('mfg_recipe_id', $recipe->id)
                        ->orderBy('variation_id', 'asc')
                        ->get();
                    $data = [];

                    foreach ($recipe_ingredients as $recipe_ingredient) {

                        for ($i = 0; $i < count($request->transaction_id); $i++) {
                            if ($request->variation_id[$i] == $recipe_ingredient->variation_id) {
                                $output = ($request->quantity[$i] - ($request->checked_qty[$i] - $request->passed_qty[$i])) / $recipe_ingredient->quantity;
                                $data[] = [
                                    'output' => intval($output),
                                ];
                            }
                        }
                    }
                    $final_output = (min($data));

                    // update purchase line quantity
                    PurchaseLine::where('transaction_id', $tr_id_purchase)
                        ->update([
                            'quantity' => $final_output['output'],
                            'qc_qty' => $final_output['output'],
                        ]);

                    Transaction::where('id', $tr_id_purchase)
                        ->update([
                            'final_total' => ($main_product_variation->pp_without_discount * $final_output['output']),
                        ]);

                    Transaction::where('id', $tr_id_sell)
                        ->update([
                            'final_total' => ($main_product_variation->pp_without_discount * $final_output['output']),
                        ]);

                    //update sell quantity
                    foreach ($recipe_ingredients as $recipe_ingredient) {
                        for ($i = 0; $i < count($request->transaction_id); $i++) {
                            if ($request->variation_id[$i] == $recipe_ingredient->variation_id) {

                                $sell_quantity = TransactionSellLine::where('transaction_id', $tr_id_sell)
                                    ->where('product_id', $request->product_id[$i])
                                    ->where('variation_id', $request->variation_id[$i])
                                    ->where('tr_sell_lot_number', $request->lot_number[$i])
                                    ->first();

                                $current_sell_qty = $sell_quantity->quantity;
                                $sell_final_output['quantity'] = $final_output['output'] * $recipe_ingredient->quantity;
                                $sell_quantity->update($sell_final_output);

                                $variation = Variation::find($request->variation_id[$i]);

                                $vld_quantity = VariationLocationDetails::where('location_id', $location_from->id)
                                    ->where('product_id', $request->product_id[$i])
                                    ->where('product_variation_id', $variation->product_variation_id)
                                    ->where('variation_id', $request->variation_id[$i])
                                    ->where('lot_number', $request->lot_number[$i])
                                    ->first();

                                $current_vld_qty = $vld_quantity->qty_available;
                                $increase_vld['qty_available'] = '';

                                if (($final_output['output'] * $recipe_ingredient->quantity) < $request->passed_qty[$i]) {
                                    $increase_qty = $request->passed_qty[$i] - ($final_output['output'] * $recipe_ingredient->quantity);
                                    $increase_vld['qty_available'] = $current_vld_qty + $increase_qty;
                                    $vld_quantity->update($increase_vld);
                                }
                            }
                        }
                    }
                }

                //update production transaction status
                if (isset($request->qc_finalize)) {
                    Transaction::where('id', $tr_id_purchase)
                        ->update([
                            'status' => 'qc_approved',
                        ]);
                }

                //update qc 2 transaction status
                if (isset($request->qc_finalize)) {
                    Transaction::where('id', $get_qc_step->transaction_id)->update(['status' => 'completed']);
                } else {
                    Transaction::where('id', $get_qc_step->transaction_id)->update(['status' => 'pending']);
                }
            } else if ($get_qc_step->qc_step == "qcs_3") {

                $tr_id_sell = '';
                $tr_id_purchase = '';

                for ($i = 0; $i < count($request->transaction_id); $i++) {

                    $QualityControllProducts = new QualityControllProducts;
                    $QualityControllProducts->qc_id = $request->qc_id;
                    $QualityControllProducts->product_id = $request->product_id[$i];
                    $QualityControllProducts->variation_id = $request->variation_id[$i];
                    $QualityControllProducts->transaction_id = $request->transaction_id[$i];
                    $QualityControllProducts->recieved_qty = $request->quantity[$i];
                    $QualityControllProducts->qc_checked_qty = $request->checked_qty[$i];
                    $QualityControllProducts->qc_pass_qty = $request->passed_qty[$i];
                    $QualityControllProducts->qc_fail_qty = ($request->checked_qty[$i] - $request->passed_qty[$i]);
                    $QualityControllProducts->product_lot_no = $request->lot_number[$i];
                    $QualityControllProducts->product_qc_step = $request->qc_step;

                    if ($request->fail_descrpition[$i] != '') {
                        $QualityControllProducts->qc_fail_description = $request->fail_descrpition[$i];
                    }

                    $QualityControllProducts->save();

                    //update transaction status
                    $tr_id_purchase = $request->transaction_id[$i];
                    $tr_id_sell = $tr_id_purchase + 1;

                    if (isset($request->qc_finalize)) {
                        Transaction::where('id', $tr_id_purchase)
                            ->update([
                                'status' => 'qc_approved',
                            ]);
                    } else if (isset($request->qc_unapprove)) {
                        Transaction::where('id', $tr_id_purchase)
                            ->update([
                                'status' => 'hold_production',
                            ]);
                    } else {
                        Transaction::where('id', $tr_id_purchase)
                            ->update([
                                'status' => 'qc_checked',
                            ]);
                    }

                    //update final total in transaction
                    $fail_quantity = '';
                    if (isset($request->qc_finalize)) {
                        //update purchase line quantity
                        $fail_quantity = $request->checked_qty[$i] - $request->passed_qty[$i];
                    }

                    //get previous ingrediant cost before update quantity
                    if ($i == 0) {
                        $purchase_details = PurchaseLine::where('transaction_id', $tr_id_purchase)
                            ->first();

                        $ingrediant_cost = ($purchase_details->pp_without_discount);
                    }

                    //update purchase line details
                    if (isset($request->qc_finalize)) {
                        PurchaseLine::where('transaction_id', $tr_id_purchase)
                            ->update([
                                'quantity' => $request->quantity[$i] - $fail_quantity,
                                'qc_qty' => $request->quantity[$i] - $fail_quantity,
                            ]);
                    } else if (isset($request->qc_unapprove)) {
                        PurchaseLine::where('transaction_id', $tr_id_purchase)
                            ->update([
                                'quantity' => $request->passed_qty[$i],
                                'qc_qty' => $request->passed_qty[$i],
                            ]);
                    }

                    //update prices
                    $get_details = PurchaseLine::where('transaction_id', $tr_id_purchase)
                        ->first();

                    //update transaction final total
                    Transaction::where('id', $tr_id_purchase)
                        ->update([
                            'final_total' => $get_details->quantity * $ingrediant_cost,
                        ]);

                    Transaction::where('id', $tr_id_sell)
                        ->update([
                            'final_total' => $get_details->quantity * $ingrediant_cost,
                        ]);

                    //get mfg recipe details
                    $get_mfg_details = MfgRecipe::where('product_id', $get_details->product_id)
                        ->where('variation_id', $get_details->variation_id)
                        ->first();

                    $get_ingradient_details = MfgRecipeIngredient::where('mfg_recipe_id', $get_mfg_details->id)->get();

                    if (isset($request->qc_finalize)) {
                        foreach ($get_ingradient_details as $get_ingradient_detail) {
                            $final_quantity = $get_details->quantity * $get_ingradient_detail->quantity;

                            //update transaction sell line
                            TransactionSellLine::where('transaction_id', $tr_id_sell)
                                ->where('variation_id', $get_ingradient_detail->variation_id)
                                ->update([
                                    'quantity' => $final_quantity,
                                ]);
                        }
                    } else if (isset($request->qc_unapprove)) {

                        foreach ($get_ingradient_details as $get_ingradient_detail) {

                            $final_quantity = $get_details->quantity * $get_ingradient_detail->quantity;

                            //get before transaction sell line quantity
                            $tr_sell_details = TransactionSellLine::where('transaction_id', $tr_id_sell)
                                ->where('variation_id', $get_ingradient_detail->variation_id)
                                ->first();

                            //update transaction sell line quantity
                            $new_quantity['quantity'] = $final_quantity;
                            $tr_sell_details->update($new_quantity);
                        }
                    }
                }

                //update qc 2 transaction status
                if (isset($request->qc_finalize)) {
                    Transaction::where('id', $get_qc_step->transaction_id)->update(['status' => 'completed']);
                } else {
                    Transaction::where('id', $get_qc_step->transaction_id)->update(['status' => 'pending']);
                }
            } else if ($get_qc_step->qc_step == "qcs_4") {
                $location_id = BusinessLocation::select('id')->skip(2)->take(1)->first();

                $tr_id_sell = '';
                $tr_id_purchase = '';
                $ingrediant_cost = '';

                for ($i = 0; $i < count($request->transaction_id); $i++) {
                    $QualityControllProducts = new QualityControllProducts;
                    $QualityControllProducts->qc_id = $request->qc_id;
                    $QualityControllProducts->product_id = $request->product_id[$i];
                    $QualityControllProducts->variation_id = $request->variation_id[$i];
                    $QualityControllProducts->transaction_id = $request->transaction_id[$i];
                    $QualityControllProducts->recieved_qty = $request->quantity[$i];
                    $QualityControllProducts->qc_checked_qty = $request->checked_qty[$i];
                    $QualityControllProducts->qc_pass_qty = $request->passed_qty[$i];
                    $QualityControllProducts->qc_fail_qty = ($request->checked_qty[$i] - $request->passed_qty[$i]);
                    $QualityControllProducts->product_lot_no = $request->lot_number[$i];
                    $QualityControllProducts->product_qc_step = $request->qc_step;

                    if ($request->fail_descrpition[$i] != '') {
                        $QualityControllProducts->qc_fail_description = $request->fail_descrpition[$i];
                    }

                    $QualityControllProducts->save();

                    $tr_id_purchase = $request->transaction_id[$i];
                    $tr_id_sell = $tr_id_purchase + 1;

                    if (isset($request->qc_finalize)) {

                        //update purchase line quantity and "is_qc4_checked" as complete
                        $fail_quantity = $request->checked_qty[$i] - $request->passed_qty[$i];

                        //get previous ingrediant cost before update quantity
                        if ($i == 0) {
                            $purchase_details = PurchaseLine::where('transaction_id', $tr_id_purchase)
                                ->first();

                            $ingrediant_cost = ($purchase_details->pp_without_discount) / ($purchase_details->quantity);
                        }

                        //update purchase line details
                        if (isset($request->qc_finalize)) {
                            PurchaseLine::where('transaction_id', $tr_id_purchase)
                                ->update([
                                    'is_qc4_checked' => 'complete',
                                ]);
                        } else {
                            PurchaseLine::where('transaction_id', $tr_id_purchase)
                                ->update([
                                    'is_qc4_checked' => 'checked',
                                ]);
                        }

                        //update purchase line details
                        PurchaseLine::where('transaction_id', $tr_id_purchase)
                            ->update([
                                'quantity' => $request->checked_qty[$i] - $fail_quantity,
                                'qc_qty' => $request->checked_qty[$i] - $fail_quantity,
                            ]);

                        //update prices
                        $get_details = PurchaseLine::where('transaction_id', $tr_id_purchase)
                            ->first();

                        //update purchase line prices
                        PurchaseLine::where('transaction_id', $tr_id_purchase)
                            ->update([
                                'pp_without_discount' => $get_details->quantity * $ingrediant_cost,
                                'purchase_price' => $get_details->quantity * $ingrediant_cost,
                                'purchase_price_inc_tax' => $get_details->quantity * $ingrediant_cost,
                            ]);

                        //update transaction final total
                        Transaction::where('id', $tr_id_purchase)
                            ->update([
                                'final_total' => $get_details->quantity * $ingrediant_cost,
                            ]);

                        Transaction::where('id', $tr_id_sell)
                            ->update([
                                'final_total' => $get_details->quantity * $ingrediant_cost,
                            ]);

                        //get mfg recipe details
                        $get_mfg_details = MfgRecipe::where('product_id', $get_details->product_id)
                            ->where('variation_id', $get_details->variation_id)
                            ->first();

                        $get_ingradient_details = MfgRecipeIngredient::where('mfg_recipe_id', $get_mfg_details->id)->get();

                        $location_production = BusinessLocation::select('id')->skip(2)->take(1)->first();
                        $location_outlet = BusinessLocation::select('id')->skip(3)->take(1)->first();

                        foreach ($get_ingradient_details as $get_ingradient_detail) {
                            $final_quantity = $get_details->quantity * $get_ingradient_detail->quantity;

                            //update transaction sell line
                            TransactionSellLine::where('transaction_id', $tr_id_sell)
                                ->where('variation_id', $get_ingradient_detail->variation_id)
                                ->update([
                                    'quantity' => $final_quantity,
                                ]);
                        }

                        //decreate quantity in production location
                        $vld_details = VariationLocationDetails::where('location_id', $location_production->id)
                            ->where('product_id', $get_details->product_id)
                            ->where('variation_id', $get_details->variation_id)
                            ->where('lot_number', $get_details->lot_number)
                            ->first();

                        $current_vld_qty = $vld_details->qty_available;
                        $decrease_vld['qty_available'] = $current_vld_qty - $request->checked_qty[$i];
                        $vld_details->update($decrease_vld);

                        //save product outlet location
                        $check_if_already = VariationLocationDetails::where('location_id', $location_outlet->id)
                            ->where('product_id', $get_details->product_id)
                            ->where('variation_id', $get_details->variation_id)
                            ->where('lot_number', $get_details->lot_number)
                            ->first();

                        if (!empty($check_if_already)) {
                            //update production in outlet location
                            $current_qty = $check_if_already->qty_available;
                            $increase_vld['qty_available'] = $current_qty + $request->passed_qty[$i];
                            $check_if_already->update($increase_vld);
                        } else {
                            //save production in outlet location

                            $variation = Variation::find($request->variation_id[$i]);

                            $insert_vld = new VariationLocationDetails;
                            $insert_vld->product_id = $request->product_id[$i];
                            $insert_vld->product_variation_id = $variation->product_variation_id;
                            $insert_vld->variation_id = $request->variation_id[$i];
                            $insert_vld->location_id = $location_outlet->id;
                            $insert_vld->qty_available = $request->passed_qty[$i];
                            $insert_vld->lot_number = $request->lot_number[$i];

                            $insert_vld->save();
                        }
                    }
                }

                //update qc 2 transaction status
                if (isset($request->qc_finalize)) {
                    Transaction::where('id', $get_qc_step->transaction_id)->update(['status' => 'completed']);
                } else {
                    Transaction::where('id', $get_qc_step->transaction_id)->update(['status' => 'pending']);
                }
            } else {
                for ($i = 0; $i < count($request->transaction_id); $i++) {
                    $QualityControllProducts = new QualityControllProducts;
                    $QualityControllProducts->qc_id = $request->qc_id;
                    $QualityControllProducts->product_id = $request->product_id[$i];
                    $QualityControllProducts->variation_id = $request->variation_id[$i];
                    $QualityControllProducts->transaction_id = $request->transaction_id[$i];
                    $QualityControllProducts->recieved_qty = $request->quantity[$i];
                    $QualityControllProducts->qc_checked_qty = $request->checked_qty[$i];
                    $QualityControllProducts->qc_pass_qty = $request->passed_qty[$i];
                    $QualityControllProducts->qc_fail_qty = ($request->checked_qty[$i] - $request->passed_qty[$i]);
                    $QualityControllProducts->product_lot_no = $request->lot_number[$i];
                    $QualityControllProducts->product_qc_step = $request->qc_step;
                    if ($request->fail_descrpition[$i] != '') {
                        $QualityControllProducts->qc_fail_description = $request->fail_descrpition[$i];
                    }
                    $QualityControllProducts->save();

                    //update purchase line details
                    if (isset($request->qc_finalize)) {
                        PurchaseLine::where('transaction_id', $request->transaction_id[$i])
                            ->update([
                                'is_production_qc' => 'complete',
                            ]);
                    } else {
                        PurchaseLine::where('transaction_id', $request->transaction_id[$i])
                            ->update([
                                'is_production_qc' => 'checked',
                            ]);
                    }

                    if (isset($request->qc_finalize)) {

                        $location_stores = BusinessLocation::select('id')->skip(1)->take(1)->first();

                        //update quantity in stores location
                        $vld_details = VariationLocationDetails::where('location_id', $location_stores->id)
                            ->where('product_id', $request->product_id[$i])
                            ->where('variation_id', $request->variation_id[$i])
                            ->where('lot_number', $request->lot_number[$i])
                            ->first();

                        $current_vld_qty = $vld_details->qty_available;
                        $increase['qty_available'] = $current_vld_qty + $request->passed_qty[$i];
                        $vld_details->update($increase);
                    }

                    //update qc 2 transaction status
                    if (isset($request->qc_finalize)) {
                        Transaction::where('id', $get_qc_step->transaction_id)->update(['status' => 'completed']);
                    } else {
                        Transaction::where('id', $get_qc_step->transaction_id)->update(['status' => 'pending']);
                    }
                }
            }

            $output = [
                'success' => true,
                'msg' => __("lang_v1.added_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    public function load_parameters(Request $request)
    {
        if ($request->is_qc_save == 'false') {

            $if_exists_tempory = TemporyQualityProductParameters::where('parent_product_id', '=', $request->product_id)
                ->first();

            if (empty($if_exists_tempory)) {
                $get_parameters = ProductHasParameters::where('parameter_product_id', '=', $request->product_id)
                    ->leftJoin('product_parameters', 'product_parameters.id', '=', 'product_has_parameters.parameter_parent_id')
                    ->select(
                        DB::raw('"false" as is_tempory, 
                    product_parameters.parameter_name as parameter_name,
                    product_has_parameters.parameter_parent_id as parameter_parent_id
                ')
                    )
                    ->get();

                return response()->json($get_parameters);
            } else {

                $get_parameters = TemporyQualityProductParameters::where('parent_product_id', '=', $request->product_id)
                    ->leftJoin('product_parameters', 'product_parameters.id', '=', 'tempory_quality_product_parameters.parent_parameter_id')
                    ->select(
                        DB::raw('"true" as is_tempory, 
                    product_parameters.parameter_name as parameter_name,
                    tempory_quality_product_parameters.parent_parameter_id as parent_parameter_id,
                    tempory_quality_product_parameters.parent_product_id as parent_product_id,
                    tempory_quality_product_parameters.qc_param_status as qc_param_status,
                    tempory_quality_product_parameters.qc_param_description as qc_param_description
                ')
                    )
                    ->get();

                return response()->json($get_parameters);
            }
        } else {

            $get_parameters = QualityProductParameters::where('parent_qc_id', '=', $request->quality_id)
                ->where('parent_product_id', '=', $request->product_id)
                ->where('parameter_product_lot', '=', $request->product_lot)
                ->leftJoin('product_parameters', 'product_parameters.id', '=', 'quality_product_parameters.parent_parameter_id')
                ->select(
                    DB::raw('"true" as is_tempory, 
                    product_parameters.parameter_name as parameter_name,
                    quality_product_parameters.parent_parameter_id as parent_parameter_id,
                    quality_product_parameters.parent_product_id as parent_product_id,
                    quality_product_parameters.qc_param_status as qc_param_status,
                    quality_product_parameters.qc_param_description as qc_param_description
                ')
                )
                ->get();

            return response()->json($get_parameters);
        }
    }

    public function tempory_save_parameters(Request $request)
    {
        try {
            DB::table('tempory_quality_product_parameters')->where('parent_product_id', $request->parameter_product)->delete();

            for ($a = 0; $a < count($request->parent_parameter_id); $a++) {
                $temporyParameters = new TemporyQualityProductParameters;
                $temporyParameters->parent_parameter_id = $request->parent_parameter_id[$a];
                $temporyParameters->parent_product_id = $request->parameter_product_id;
                $temporyParameters->parameter_product_lot = $request->parameter_product_lot;
                $temporyParameters->qc_param_status = $request->parameter_status[$a];
                if ($request->parameter_description[$a] != '') {
                    $temporyParameters->qc_param_description = $request->parameter_description[$a];
                }
                $temporyParameters->save();
            }

            $output = [
                'success' => true,
                'msg' => __("lang_v1.added_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    public function delete_tempory_parameters()
    {
        DB::table('tempory_quality_product_parameters')->delete();
    }

    public function save_product_parameters($last_id)
    {
        $tempory_details = TemporyQualityProductParameters::get();

        if ($tempory_details != '') {
            foreach ($tempory_details as $tempory_detail) {
                $QualityProductParameters = new QualityProductParameters;
                $QualityProductParameters->parent_qc_id = $last_id;
                $QualityProductParameters->parent_parameter_id = $tempory_detail->parent_parameter_id;
                $QualityProductParameters->parent_product_id = $tempory_detail->parent_product_id;
                $QualityProductParameters->parameter_product_lot = $tempory_detail->parameter_product_lot;
                $QualityProductParameters->qc_param_status = $tempory_detail->qc_param_status;
                $QualityProductParameters->qc_param_description = $tempory_detail->qc_param_description;

                $QualityProductParameters->save();
            }

            DB::table('tempory_quality_product_parameters')->delete();
        }
    }

    //update quality contrl product parameters
    public function update_product_parameters(Request $request)
    {
        try {

            for ($i = 0; $i < count($request->parent_parameter_id); $i++) {

                QualityProductParameters::where('parent_qc_id', $request->product_quality_id)
                    ->where('parent_parameter_id', $request->parent_parameter_id[$i])
                    ->where('parent_product_id', $request->parameter_product_id)
                    ->where('parameter_product_lot', $request->parameter_product_lot)
                    ->update([
                        'qc_param_status' => $request->parameter_status[$i],
                        'qc_param_description' => $request->parameter_description[$i]
                    ]);
            }

            $output = [
                'success' => true,
                'msg' => __("lang_v1.added_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    public function view_parameters(Request $request)
    {
        $get_parameters = QualityProductParameters::where('parent_qc_id', '=', $request->quality_id)
            ->where('parent_product_id', '=', $request->product_id)
            ->where('parameter_product_lot', '=', $request->product_lot)
            ->leftJoin('product_parameters', 'product_parameters.id', '=', 'quality_product_parameters.parent_parameter_id')
            ->get();

        return response()->json($get_parameters);
    }

    public function delete_record(Request $request)
    {
        try {

            $get_save_details = QualityControll::where('qc_id', $request->quality_id)->first();

            if ($get_save_details->qc_step == 'qcs_1') {
                //update transaction status
                Transaction::where('id', $get_save_details->transaction_id)
                    ->update([
                        'status' => 'pending'
                    ]);
            } else if ($get_save_details->qc_step == 'qcs_2') {
                //get production transaction id
                $get_qc_products = QualityControllProducts::where('qc_id', $request->quality_id)->get();
                $transaction_id = '';

                foreach ($get_qc_products as $get_qc_product) {
                    $tr_id = $get_qc_product->transaction_id;
                    $transaction_id = $tr_id;
                }

                //delete mrn products
                DB::table('mrn_products')->where('transaction_id', $transaction_id)->delete();

                Transaction::where('id', $transaction_id - 1)
                    ->update([
                        'status' => 'pending'
                    ]);
            } else if ($get_save_details->qc_step == 'qcs_3') {
                //get production transaction id
                $get_qc_products = QualityControllProducts::where('qc_id', $request->quality_id)->get();
                $transaction_id = '';

                foreach ($get_qc_products as $get_qc_product) {
                    $tr_id = $get_qc_product->transaction_id;
                    $transaction_id = $tr_id;
                }

                Transaction::where('id', $transaction_id)
                    ->update([
                        'status' => 'hold_production'
                    ]);
            } else if ($get_save_details->qc_step == 'qcs_4') {
                //get production transaction id
                $get_qc_products = QualityControllProducts::where('qc_id', $request->quality_id)->first();

                //update purchase line qc 4 stats
                PurchaseLine::where('transaction_id', $get_qc_products->transaction_id)
                    ->update([
                        'is_qc4_checked' => 'available',
                    ]);
            } else {
                //get production transaction id
                $get_qc_products = QualityControllProducts::where('qc_id', $request->quality_id)->get();
                $transaction_id = '';

                foreach ($get_qc_products as $get_qc_product) {
                    $tr_id = $get_qc_product->transaction_id;
                    $transaction_id = $tr_id;
                }

                //update purchase line qc 5 status
                PurchaseLine::where('transaction_id', $transaction_id)
                    ->update([
                        'is_production_qc' => 'available',
                    ]);
            }

            //delete quality qcotrol record
            DB::table('quality_controlls')->where('qc_id', $request->quality_id)->delete();

            //delete quality control products
            DB::table('quality_controll_products')->where('qc_id', $request->quality_id)->delete();

            //delete quality control parameters
            DB::table('quality_product_parameters')->where('parent_qc_id', $request->quality_id)->delete();

            $output = [
                'success' => true,
                'msg' => __("lang_v1.added_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }
}
