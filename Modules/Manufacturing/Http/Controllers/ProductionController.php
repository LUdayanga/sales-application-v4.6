<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\BusinessLocation;
use App\Contact;
use App\Transaction;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use App\Machines;
use App\PurchaseLine;
use App\QualityControll;
use App\TransactionSellLine;
use App\VariationLocationDetails;
use App\production_machines;
use App\QualityControllProducts;
use Barryvdh\DomPDF\PDF;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Manufacturing\Entities\MfgRecipe;
use Modules\Manufacturing\Utils\ManufacturingUtil;
use Yajra\DataTables\Facades\DataTables;
use Modules\Manufacturing\Entities\MfgIngredientGroup;

class ProductionController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $moduleUtil;
    protected $productUtil;
    protected $transactionUtil;
    protected $mfgUtil;
    protected $businessUtil;

    private $tansactionId;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil, ProductUtil $productUtil, TransactionUtil $transactionUtil, ManufacturingUtil $mfgUtil, BusinessUtil $businessUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->mfgUtil = $mfgUtil;
        $this->businessUtil = $businessUtil;
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module')) || !auth()->user()->can('manufacturing.access_production')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $productions = Transaction::join(
                'business_locations AS bl',
                'transactions.location_id',
                '=',
                'bl.id'
            )->join('purchase_lines as pl', 'pl.transaction_id', '=', 'transactions.id')
                ->leftJoin('units as su', 'pl.sub_unit_id', '=', 'su.id')
                ->join('variations as v', 'v.id', '=', 'pl.variation_id')
                ->join('product_variations as pv', 'pv.id', '=', 'v.product_variation_id')
                ->join('products as p', 'p.id', '=', 'v.product_id')
                ->join('units as u', 'p.unit_id', '=', 'u.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'production_purchase')
                ->select(
                    'transactions.id',
                    'transaction_date',
                    'ref_no',
                    'pl.lot_number',
                    'status',
                    'priority',
                    'bl.name as location_name',
                    DB::raw('IF(p.type="variable", 
                            CONCAT(p.name, " - ", pv.name, " - ", v.name, " (", v.sub_sku, ")"), 
                            CONCAT(p.name, " (", v.sub_sku, ")") 
                            ) as product_name'),
                    'pl.quantity',
                    'final_total',
                    'su.short_name as sub_unit_name',
                    'su.base_unit_multiplier',
                    'u.short_name as unit_name',
                    'mfg_is_final'
                )->groupBy('transactions.id');

            return Datatables::of($productions)
                ->addColumn('action', function ($row) {
                    $html = '<button data-href="' .  action('\Modules\Manufacturing\Http\Controllers\ProductionController@show', $row->id) . '" class="btn btn-info btn-xs btn-modal" data-container=".view_modal"><i class="fa fa-eye"></i> ' . __('messages.view') . '</button>';
                    $html .= ' <a target="_blank" href="' .  route('print.job_card', $row->id) . '" class="btn btn-primary btn-xs"><i class="fa fa-print"></i> ' . __('manufacturing::lang.print_job_card') . '</a>';
                    if ($row->mfg_is_final == 0 && $row->status != 'stores_release') {
                        $html .= ' <a href="' .  action('\Modules\Manufacturing\Http\Controllers\ProductionController@edit', $row->id) . '" class="btn btn-primary btn-xs"><i class="fa fa-edit"></i> ' . __('messages.edit') . '</a>';

                        $html .= ' <buttondata-href="' . action('\Modules\Manufacturing\Http\Controllers\ProductionController@destroy',  [$row->id]) . '" class="delete-production btn btn-xs btn-danger"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</button>';
                    }

                    return $html;
                })
                ->editColumn(
                    'final_total',
                    '<span class="display_currency final_total" data-currency_symbol="true" data-orig-value="{{ $final_total }}">{{ $final_total }}</span>'
                )
                ->addColumn('status', function ($row) {
                    if ($row->status == 'pending' || $row->status == 'stores_release') {
                        return '<button data-val=' . $row->id . ' data-href=' . $row->status . ' class="btn btn-xs btn-danger btn_update_modal">Pending</button>';
                    } else if ($row->status == 'hold') {
                        return '<button data-val=' . $row->id . ' data-href=' . $row->status . ' class="btn btn-xs btn-warning btn_update_modal">Hold Process</button>';
                    } else if ($row->status == 'qc_checked') {
                        return '<button data-val=' . $row->id . ' data-href=' . $row->status . ' class="btn btn-xs btn-info btn_update_modal">QC Checking</button>';
                    } else if ($row->status == 'qc_approved') {
                        return '<button data-val=' . $row->id . ' data-href=' . $row->status . ' class="btn btn-xs btn-primary btn_update_modal">QC Approved</button>';
                    } else if ($row->status == 'process') {
                        return '<button data-val=' . $row->id . ' data-href=' . $row->status . ' class="btn btn-xs btn-danger btn_update_modal">In Progress</button>';
                    } else if ($row->status == 'hold_production') {
                        return '<button data-val=' . $row->id . ' data-href=' . $row->status . ' class="btn btn-xs btn-danger btn_update_modal">Hold Production</button>';
                    } else {
                        return '<button data-val=' . $row->id . ' data-href=' . $row->status . ' class="btn btn-xs btn-success btn_update_modal">Complete</button>';
                    }
                })
                ->addColumn('priority', function ($row) {
                    if ($row->priority == 'non_urgent') {
                        return '<button data-val=' . $row->id . ' data-href=' . $row->priority . ' class="btn btn-xs btn-success btn_update_priority">Non Urgent</button>';
                    } else if ($row->priority == 'urgent') {
                        return '<button data-val=' . $row->id . ' data-href=' . $row->priority . ' class="btn btn-xs btn-warning btn_update_priority">Urgent</button>';
                    } else {
                        return '<button data-val=' . $row->id . ' data-href=' . $row->priority . ' class="btn btn-xs btn-danger btn_update_priority">Top Urgent</button>';
                    }
                })
                ->editColumn('badge_no', function ($row) {
                    return  $row->lot_number;
                })
                ->editColumn(
                    'quantity',
                    function ($row) {
                        $qty = empty($row->base_unit_multiplier) ? $row->quantity : $row->quantity / $row->base_unit_multiplier;
                        $unit = empty($row->sub_unit_name) ? $row->unit_name : $row->sub_unit_name;
                        return "<span class='display_currency' data-currency_symbol='false' data-orig-value='$qty' data-is_quantity='true'>$qty</span> $unit";
                    }
                )
                ->editColumn('transaction_date', '{{ @format_datetime($transaction_date) }}')
                ->rawColumns(['final_total', 'action', 'quantity', 'status', 'priority'])
                ->filterColumn('product_name', function ($query, $keyword) {
                    $query->whereRaw("CONCAT(p.name, ' - ', pv.name, ' - ', v.name, ' (', v.sub_sku, ')') like ?", ["%{$keyword}%"]);
                })
                ->make(true);
        }

        return view('manufacturing::production.index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module')) || !auth()->user()->can('manufacturing.access_production')) {
            abort(403, 'Unauthorized action.');
        }

        // $business_locations = BusinessLocation::forDropdown($business_id);

        $business_locations = BusinessLocation::skip(2)->take(1)->pluck('name', 'id')->toArray();

        $recipe_dropdown = MfgRecipe::forDropdown($business_id);

        // $contact = Contact::customersDropdown($business_id);

        $prepend_none = true;
        $append_id = true;

        $all_contacts = Contact::where('business_id', $business_id)
                        ->whereIn('type', ['customer', 'both'])
                        ->active();

        if ($append_id) {
            $all_contacts->select(
                DB::raw("IF(contact_id IS NULL OR contact_id='', CONCAT( COALESCE(supplier_business_name, ''), ' - ', name), CONCAT(COALESCE(supplier_business_name, ''), ' - ', name, ' (', contact_id, ')')) AS customer"),
                'id'
                );
        } else {
            $all_contacts->select('id', DB::raw("name as customer"));
        }

        if (auth()->check() && !auth()->user()->can('customer.view') && auth()->user()->can('customer.view_own')) {
            $all_contacts->where('contacts.created_by', auth()->user()->id);
        }

        $customers = $all_contacts->pluck('customer', 'id');

        //Prepend none
        // if ($prepend_none) {
        //     $customers = $customers->prepend(__('lang_v1.none'), '');
        // }

        //Get all machines
        $get_all_machines = Machines::pluck('machine_name', 'id')->toArray();

        return view('manufacturing::production.create')
            ->with(compact('business_locations', 'recipe_dropdown', 'customers', 'get_all_machines'));
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module')) || !auth()->user()->can('manufacturing.access_production')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $request->validate([
                'transaction_date' => 'required',
                'location_id' => 'required',
                'final_total' => 'required',
                'lot_number' => 'required'
            ]);

            //Create Production purchase
            $manufacturing_settings = $this->mfgUtil->getSettings($business_id);
            $user_id = $request->session()->get('user.id');

            $transaction_data = $request->only(['ref_no', 'transaction_date', 'location_id', 'final_total']);

            $is_final = !empty($request->input('finalize')) ? 1 : 0;
            $is_approve = !empty($request->input('approve')) ? 1 : 0;
            $transaction_data['business_id'] = $business_id;
            $transaction_data['created_by'] = $user_id;
            $transaction_data['type'] = 'production_purchase';
            $transaction_data['status'] = $is_final ? 'received' : 'pending';
            $transaction_data['payment_status'] = 'due';
            $transaction_data['transaction_date'] = $this->productUtil->uf_date($transaction_data['transaction_date'], true);
            $transaction_data['final_total'] = $this->productUtil->num_uf($transaction_data['final_total']);

            //Update reference count
            $ref_count = $this->productUtil->setAndGetReferenceCount($transaction_data['type']);
            //Generate reference number
            if (empty($transaction_data['ref_no'])) {
                $prefix = !empty($manufacturing_settings['ref_no_prefix']) ? $manufacturing_settings['ref_no_prefix'] : null;
                $transaction_data['ref_no'] = $this->productUtil->generateReferenceNumber($transaction_data['type'], $ref_count, null, $prefix);
            }
            $variation_id = $request->input('variation_id');
            $variation = Variation::where('id', $variation_id)
                ->with(['product'])
                ->first();
            $final_total = $request->input('final_total');
            $quantity = $request->input('quantity');
            $waste_units = $this->productUtil->num_uf($request->input('mfg_wasted_units'));
            $uf_qty = $this->productUtil->num_uf($quantity);
            if (!empty($waste_units)) {
                $new_qty = $uf_qty - $waste_units;
                $uf_qty = $new_qty;
                $quantity = $this->productUtil->num_f($new_qty);
            }

            $final_total_uf = $this->productUtil->num_uf($final_total);

            $unit_purchase_line_total = $final_total_uf / $uf_qty;

            $unit_purchase_line_total_f = $this->productUtil->num_f($unit_purchase_line_total);

            $transaction_data['mfg_wasted_units'] = $waste_units;
            $transaction_data['mfg_production_cost'] = $this->productUtil->num_uf($request->input('production_cost'));
            $transaction_data['mfg_production_cost_type'] = $request->input('mfg_production_cost_type');
            $transaction_data['mfg_is_final'] = $is_final;

            $transaction_data['doc_index'] = $request->get('doc_index');
            $transaction_data['issue_no'] = $request->get('issue_no');
            $transaction_data['section_id'] = $request->get('section_id');
            $transaction_data['shipment_no'] = $request->get('shipment_no');
            $transaction_data['contact_id'] = $request->get('contact_id');
            $transaction_data['target_qty'] = $request->get('target_qty');
            $transaction_data['day_production_qty'] = $request->get('day_production_qty');
            $transaction_data['no_workers'] = $request->get('no_workers');
            $transaction_data['production_duration'] = $request->get('production_duration');
            $transaction_data['expire_date'] = !empty($request->get('expire_date')) ? $this->productUtil->uf_date($request->get('expire_date'), true) : null;
            $transaction_data['recovery'] = $request->get('recovery');
            $transaction_data['packing_instructions'] = $request->get('packing_instructions');
            $transaction_data['priority'] = 'non_urgent';
            $transaction_data['is_approved'] = $is_approve;

            $purchase_line_data = [
                'variation_id' => $variation_id,
                'quantity' => $quantity,
                'qc_quantity' => $quantity,
                'product_id' => $variation->product_id,
                'product_unit_id' => $variation->product->unit_id,
                'pp_without_discount' => $unit_purchase_line_total_f,
                'discount_percent' => 0,
                'purchase_price' => $unit_purchase_line_total_f,
                'purchase_price_inc_tax' => $unit_purchase_line_total_f,
                'item_tax' => 0,
                'purchase_line_tax_id' => null,
                'mfg_date' => $this->transactionUtil->format_date($transaction_data['transaction_date'])
            ];

            $purchase_line_data['is_qc4_checked'] = 'hold';

            if (request()->session()->get('business.enable_lot_number') == 1) {
                $purchase_line_data['lot_number'] = $request->input('lot_number');
            }

            if (request()->session()->get('business.enable_product_expiry') == 1) {
                $purchase_line_data['exp_date'] = $request->input('exp_date');
            }

            if (!empty($request->input('sub_unit_id'))) {
                $purchase_line_data['sub_unit_id'] = $request->input('sub_unit_id');
            }

            DB::beginTransaction();

            $transaction = Transaction::create($transaction_data);

            $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);

            $update_product_price = !empty($manufacturing_settings['enable_updating_product_price']) && $is_final ? true : false;

            $this->productUtil->createOrUpdatePurchaseLines($transaction, [$purchase_line_data], $currency_details, $update_product_price);

            //Adjust stock over selling if found
            $this->productUtil->adjustStockOverSelling($transaction);

            //Create production sell
            $transaction_sell_data = [
                'business_id' => $business_id,
                'location_id' => $transaction->location_id,
                'transaction_date' => $transaction->transaction_date,
                'created_by' => $transaction->created_by,
                'status' => $is_final ? 'final' : 'draft',
                'type' => 'production_sell',
                'mfg_parent_production_purchase_id' => $transaction->id,
                'payment_status' => 'due',
                'final_total' => $transaction->final_total
            ];

            //save production machines
            if ($request->has('production_machines')) {
                $inputs = $request->all();
                $pms = $inputs['production_machines'];
                foreach ($pms as $pm) {
                    $production_machine = new production_machines;
                    $production_machine->transaction_id = $transaction->id;
                    $production_machine->timestamps = false;
                    $production_machine->machine_id = $pm;
                    $production_machine->save();
                }
            }

            $sell_lines = [];
            $ingredient_quantities = !empty($request->input('ingredients')) ? $request->input('ingredients') : [];

            //Get ingredient details to create sell lines
            $recipe = MfgRecipe::where('variation_id', $variation_id)->first();

            $all_variation_details = $this->mfgUtil->getIngredientDetails($recipe, $business_id);

            foreach ($all_variation_details as $variation_details) {
                $variation = $variation_details['variation'];

                $line_sub_unit_id = !empty($ingredient_quantities[$variation_details['id']]['sub_unit_id']) ?
                    $ingredient_quantities[$variation_details['id']]['sub_unit_id'] : null;
                $line_multiplier = !empty($line_sub_unit_id) ? $variation_details['sub_units'][$line_sub_unit_id]['multiplier'] : 1;

                $mfg_waste_percent = !empty($ingredient_quantities[$variation_details['id']]['mfg_waste_percent']) ? $this->productUtil->num_uf($ingredient_quantities[$variation_details['id']]['mfg_waste_percent']) : 0;

                $mfg_ingredient_group_id = !empty($ingredient_quantities[$variation_details['id']]['mfg_ingredient_group_id']) ? $ingredient_quantities[$variation_details['id']]['mfg_ingredient_group_id'] : null;
                $ingredient_type = !empty($ingredient_quantities[$variation_details['id']]['ingredient_type']) ? $ingredient_quantities[$variation_details['id']]['ingredient_type'] : null;

                $sell_lines[] = [
                    'product_id' => $variation->product_id,
                    'variation_id' => $variation->id,
                    'quantity' => $this->productUtil->num_uf($ingredient_quantities[$variation_details['id']]['quantity']),
                    'item_tax' => 0,
                    'tax_id' => null,
                    'unit_price' => $variation->dpp_inc_tax * $line_multiplier,
                    'unit_price_inc_tax' => $variation->dpp_inc_tax * $line_multiplier,
                    'enable_stock' => $variation_details['enable_stock'],
                    'product_unit_id' => $variation->product->unit_id,
                    'sub_unit_id' => $line_sub_unit_id,
                    'base_unit_multiplier' => $line_multiplier,
                    'mfg_waste_percent' => $mfg_waste_percent,
                    'mfg_ingredient_group_id' => $mfg_ingredient_group_id,
                    'ingredient_type' => $ingredient_type
                ];
            }

            //Create Sell Transfer transaction
            $production_sell = Transaction::create($transaction_sell_data);

            if (!empty($sell_lines)) {
                $this->transactionUtil->createOrUpdateSellLines($production_sell, $sell_lines, $transaction_sell_data['location_id'], null, null, ['mfg_waste_percent' => 'mfg_waste_percent', 'mfg_ingredient_group_id' => 'mfg_ingredient_group_id']);
            }

            // if ($production_sell->status == 'final') {
            // foreach ($sell_lines as $sell_line) {
            //     if ($sell_line['enable_stock']) {
            //         $line_qty = $sell_line['quantity'] * $sell_line['base_unit_multiplier'];
            //         $this->productUtil->decreaseProductQuantity(
            //             $sell_line['product_id'],
            //             $sell_line['variation_id'],
            //             $production_sell->location_id,
            //             $line_qty
            //         );
            //     }
            // }

            // $business_details = $this->businessUtil->getDetails($business_id);
            // $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

            //Map sell lines with purchase lines
            // $business = [
            //     'id' => $business_id,
            //     'accounting_method' => $request->session()->get('business.accounting_method'),
            //     'location_id' => $production_sell->location_id,
            //     'pos_settings' => $pos_settings
            // ];
            // $this->transactionUtil->mapPurchaseSell($business, $production_sell->sell_lines, 'production_purchase');
            // }

            DB::commit();

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.added_success')
            ];
        } catch (Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->action('\Modules\Manufacturing\Http\Controllers\ProductionController@index')->with('status', $output);
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module')) || !auth()->user()->can('manufacturing.access_production')) {
            abort(403, 'Unauthorized action.');
        }

        $production_purchase = Transaction::where('business_id', $business_id)
            ->where('type', 'production_purchase')
            ->with([
                'purchase_lines', 'purchase_lines.variations', 'purchase_lines.variations.product_variation', 'purchase_lines.variations.product',
                'purchase_lines.sub_unit', 'purchase_lines.variations.product.unit'
            ])
            ->findOrFail($id);

        $production_sell = Transaction::where('business_id', $business_id)
            ->where('type', 'production_sell')
            ->where('mfg_parent_production_purchase_id', $production_purchase->id)
            ->with([
                'sell_lines',
                'sell_lines.variations',
                'sell_lines.variations.product_variation',
                'sell_lines.variations.product',
                'sell_lines.sub_unit',
                'sell_lines.sell_line_purchase_lines',
                'sell_lines.sell_line_purchase_lines.purchase_line'
            ])
            ->first();

        $purchase_line = $production_purchase->purchase_lines[0];
        $base_unit_multiplier = !empty($purchase_line->sub_unit) ? $purchase_line->sub_unit->base_unit_multiplier : 1;
        $quantity = $purchase_line->quantity / $base_unit_multiplier;
        $quantity_wasted = 0;
        $unit_name = !empty($purchase_line->sub_unit) ?  $purchase_line->sub_unit->short_name : $purchase_line->variations->product->unit->short_name;
        if (!empty($production_purchase->mfg_wasted_units)) {
            $quantity_wasted = $production_purchase->mfg_wasted_units;
            $quantity += $quantity_wasted;
        }

        $actual_quantity = $quantity * $base_unit_multiplier;

        $ingredients = [];
        $ingredient_groups = [];
        $total_ingredients_price = 0;
        //Format sell lines
        foreach ($production_sell->sell_lines as $sell_line) {
            $variation = $sell_line->variations;
            $sell_line_qty = empty($sell_line->sub_unit) ? $sell_line->quantity : $sell_line->quantity / $sell_line->sub_unit->base_unit_multiplier;
            $unit = empty($sell_line->sub_unit) ? $variation->product->unit->short_name : $sell_line->sub_unit->short_name;

            $line_total_price = $variation->dpp_inc_tax * $sell_line->quantity;
            $total_ingredients_price += $line_total_price;

            $waste_percent = !empty($sell_line->mfg_waste_percent) ? $sell_line->mfg_waste_percent : 0;
            $wasted_qty = $this->moduleUtil->calc_percentage($sell_line_qty, $waste_percent);
            $final_quantity = $sell_line_qty - $wasted_qty;

            $lot_numbers = [];
            $tr_sell_lot_number = [];

            foreach ($sell_line->sell_line_purchase_lines as $slpl) {
                $lot_number = !empty($slpl->purchase_line->lot_number) ? $slpl->purchase_line->lot_number : '';
                if (!empty($slpl->purchase_line->exp_date)) {
                    $lot_number .= ' - ' . $this->moduleUtil->format_date($slpl->purchase_line->exp_date);
                }

                if ($lot_number != '') {
                    $lot_numbers[] = $lot_number;
                }
            }

            if (empty($sell_line->mfg_ingredient_group_id)) {
                $ingredients[] = [
                    'dpp_inc_tax' => $variation->dpp_inc_tax,
                    'quantity' => $sell_line_qty,
                    'full_name' => $variation->full_name,
                    'id' => $variation->id,
                    'unit' => $unit,
                    'allow_decimal' => $variation->product->unit->allow_decimal,
                    'variation' => $variation,
                    'enable_stock' => $variation->product->enable_stock,
                    'total_price' => $line_total_price,
                    'waste_percent' =>  $waste_percent,
                    'final_quantity' => $final_quantity,
                    'tr_sell_lot_number' => $sell_line->tr_sell_lot_number,
                    'lot_numbers' => implode(', ', $lot_numbers),
                ];
            } else {
                if (!isset($ingredient_groups[$sell_line->mfg_ingredient_group_id]['ig_name'])) {
                    $i_group = MfgIngredientGroup::find($sell_line->mfg_ingredient_group_id);
                    $ingredient_groups[$sell_line->mfg_ingredient_group_id]['ig_name'] = $i_group->name;
                    $ingredient_groups[$sell_line->mfg_ingredient_group_id]['ig_description'] = $i_group->description;
                }
                $ingredient_groups[$sell_line->mfg_ingredient_group_id]['ig_ingredients'][] = [
                    'dpp_inc_tax' => $variation->dpp_inc_tax,
                    'quantity' => $sell_line_qty,
                    'full_name' => $variation->full_name,
                    'id' => $variation->id,
                    'unit' => $unit,
                    'allow_decimal' => $variation->product->unit->allow_decimal,
                    'variation' => $variation,
                    'enable_stock' => $variation->product->enable_stock,
                    'total_price' => $line_total_price,
                    'waste_percent' =>  $waste_percent,
                    'final_quantity' => $final_quantity,
                    'tr_sell_lot_number' => $sell_line->tr_sell_lot_number,
                    'lot_numbers' => implode(', ', $lot_numbers)
                ];
            }
        }

        $total_production_cost = 0;
        if (!empty($production_purchase->mfg_production_cost)) {
            $total_production_cost = $production_purchase->mfg_production_cost;
            if ($production_purchase->mfg_production_cost_type == 'percentage') {
                $total_production_cost = $this->transactionUtil->calc_percentage($total_ingredients_price, $production_purchase->mfg_production_cost);
            } elseif ($production_purchase->mfg_production_cost_type == 'per_unit') {
                $total_production_cost = $production_purchase->mfg_production_cost * $quantity;
            }
        }

        return view('manufacturing::production.show')->with(compact('production_purchase', 'production_sell', 'purchase_line', 'ingredients', 'unit_name', 'quantity', 'quantity_wasted', 'actual_quantity', 'total_production_cost', 'ingredient_groups'));
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit($id)
    {
        $tansactionId = $id;
        $business_id = request()->session()->get('user.business_id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module')) || !auth()->user()->can('manufacturing.access_production')) {
            abort(403, 'Unauthorized action.');
        }

        $production_purchase = Transaction::where('business_id', $business_id)
            ->where('type', 'production_purchase')
            ->with(['purchase_lines', 'purchase_lines.variations', 'purchase_lines.variations.product_variation', 'purchase_lines.variations.product'])
            ->findOrFail($id);

        //Finalized production should not be editable
        if ($production_purchase->mfg_is_final == 1) {
            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
            return redirect()->action('\Modules\Manufacturing\Http\Controllers\ProductionController@index')->with('status', $output);
        }

        $production_sell = Transaction::where('business_id', $business_id)
            ->where('type', 'production_sell')
            ->where('mfg_parent_production_purchase_id', $production_purchase->id)
            ->with(['sell_lines', 'sell_lines.variations', 'sell_lines.variations.product_variation', 'sell_lines.variations.product', 'sell_lines.variations.product.unit'])
            ->first();
        $purchase_line = $production_purchase->purchase_lines[0];

        $recipe = MfgRecipe::where('variation_id', $purchase_line->variation_id)
            ->first();

        //Get all machines
        $get_all_machines = Machines::pluck('machine_name', 'id')->toArray();
        $machines = production_machines::get();

        $base_unit_multiplier = !empty($purchase_line->sub_unit) ? $purchase_line->sub_unit->base_unit_multiplier : 1;
        $quantity = $purchase_line->quantity / $base_unit_multiplier;
        $quantity_wasted = 0;

        if (!empty($production_purchase->mfg_wasted_units)) {
            $quantity_wasted = $production_purchase->mfg_wasted_units;
            $quantity += $quantity_wasted;
        }

        $actual_quantity = $quantity * $base_unit_multiplier;

        $sub_units = $this->moduleUtil->getSubUnits($business_id, $purchase_line->variations->product->unit->id);
        $unit_name = $purchase_line->variations->product->unit->short_name;
        $sub_unit_id = $purchase_line->sub_unit_id;

        $ingredients = [];
        $total_ingredients_price = 0;
        foreach ($production_sell->sell_lines as $sell_line) {
            $variation = $sell_line->variations;

            $line_sub_units = $this->moduleUtil->getSubUnits($business_id, $variation->product->unit->id);
            $is_line_sub_unit = false;
            $line_sub_unit_id = null;
            $multiplier = 1;
            $line_unit_name = $variation->product->unit->short_name;
            $allow_decimal = $variation->product->unit->allow_decimal;
            if (!empty($line_sub_units)) {
                foreach ($line_sub_units as $key => $value) {
                    if (!empty($sell_line->sub_unit_id) && $sell_line->sub_unit_id == $key) {
                        $line_sub_unit_id = $sell_line->sub_unit_id;
                        $multiplier = $value['multiplier'];
                        $allow_decimal = $value['allow_decimal'];
                        $line_unit_name = $value['name'];
                    }
                }
                $is_line_sub_unit = true;
            }


            $unit_quantity = $sell_line->quantity / $actual_quantity;

            $line_total_price = $variation->dpp_inc_tax * $sell_line->quantity;
            $total_ingredients_price += $line_total_price;

            $waste_percent = !empty($sell_line->mfg_waste_percent) ? $sell_line->mfg_waste_percent : 0;
            $wasted_qty = $this->moduleUtil->calc_percentage($sell_line->quantity, $waste_percent);
            $final_quantity = ($sell_line->quantity - $wasted_qty) / $multiplier;
            $ig_name = '';
            if (!empty($sell_line->mfg_ingredient_group_id)) {
                $i_group = MfgIngredientGroup::find($sell_line->mfg_ingredient_group_id);
                $ig_name = !empty($i_group->name) ? $i_group->name : '';
            }

            $ingredients[] = [
                'dpp_inc_tax' => $variation->dpp_inc_tax,
                'quantity' => $sell_line->quantity / $multiplier,
                'full_name' => $variation->full_name,
                'total_qty' => 1.0E+27,
                'variation_id' => $variation->id,
                'unit' => $line_unit_name,
                'allow_decimal' => $allow_decimal,
                'variation' => $variation,
                'enable_stock' => $variation->product->enable_stock,
                'is_sub_unit' => $is_line_sub_unit,
                'sub_units' => $line_sub_units,
                'sub_unit_id' => $line_sub_unit_id,
                'multiplier' => $multiplier,
                'unit_quantity' => $unit_quantity,
                'total_price' => $line_total_price,
                'waste_percent' =>  $waste_percent,
                'final_quantity' => $final_quantity,
                'id' => $sell_line->id,
                'mfg_ingredient_group_id' => $sell_line->mfg_ingredient_group_id,
                'ingredient_group_name' => $ig_name,
                'ingredient_type' => $sell_line->ingredient_type,
            ];
        }

        $total_production_cost = 0;
        if (!empty($recipe->extra_cost)) {
            $total_production_cost = $this->transactionUtil->calc_percentage($total_ingredients_price, $recipe->extra_cost);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        $variation_name = $purchase_line->variations->product->name;
        if ($purchase_line->variations->product->type == 'variable') {
            $variation_name .= ' - ' .
                $purchase_line->variations->product_variation->name .
                ' - ' . $purchase_line->variations->name;
        }
        $variation_name .= ' (' . $purchase_line->variations->sub_sku . ')';
        $recipe_dropdown = [$purchase_line->variation_id => $variation_name];

        $business_details = $this->businessUtil->getDetails($business_id);
        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

        $manufacturing_settings = $this->mfgUtil->getSettings($business_id);
        // $contact = Contact::customersDropdown($business_id);

        $prepend_none = true;
        $append_id = true;

        $all_contacts = Contact::where('business_id', $business_id)
                        ->whereIn('type', ['customer', 'both'])
                        ->active();

        if ($append_id) {
            $all_contacts->select(
                DB::raw("IF(contact_id IS NULL OR contact_id='', CONCAT( COALESCE(supplier_business_name, ''), ' - ', name), CONCAT(COALESCE(supplier_business_name, ''), ' - ', name, ' (', contact_id, ')')) AS customer"),
                'id'
                );
        } else {
            $all_contacts->select('id', DB::raw("name as customer"));
        }

        if (auth()->check() && !auth()->user()->can('customer.view') && auth()->user()->can('customer.view_own')) {
            $all_contacts->where('contacts.created_by', auth()->user()->id);
        }

        $customers = $all_contacts->pluck('customer', 'id');

        //Prepend none
        // if ($prepend_none) {
        //     $customers = $customers->prepend(__('lang_v1.none'), '');
        // }

        return view('manufacturing::production.edit')->with(compact('production_purchase', 'production_sell', 'business_locations', 'recipe_dropdown', 'ingredients', 'business_details', 'pos_settings', 'sub_units', 'quantity', 'quantity_wasted', 'actual_quantity', 'recipe', 'unit_name', 'sub_unit_id', 'total_production_cost', 'manufacturing_settings', 'customers', 'get_all_machines', 'machines'));
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request, $id)
    {

        $business_id = $request->session()->get('user.business_id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module')) || !auth()->user()->can('manufacturing.access_production')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $request->validate([
                'transaction_date' => 'required',
                'location_id' => 'required',
                'final_total' => 'required',
                'lot_number' => 'required'
            ]);

            //Create Production purchase
            $transaction_data = $request->only(['ref_no', 'transaction_date', 'location_id', 'final_total']);

            $is_final = !empty($request->input('finalize')) ? 1 : 0;
            $is_approve = !empty($request->input('approve')) ? 1 : 0;

            $manufacturing_settings = $this->mfgUtil->getSettings($business_id);

            // $transaction_data['status'] = $is_final ? 'received' : 'pending';
            $transaction_data['payment_status'] = 'due';
            $transaction_data['transaction_date'] = $this->productUtil->uf_date($transaction_data['transaction_date'], true);
            $transaction_data['final_total'] = $this->productUtil->num_uf($transaction_data['final_total']);

            $variation_id = $request->input('variation_id');
            $variation = Variation::where('id', $variation_id)
                ->with(['product'])
                ->first();
            $final_total = $request->input('final_total');
            $quantity = $request->input('quantity');
            $waste_units = $this->productUtil->num_uf($request->input('mfg_wasted_units'));
            $uf_qty = $this->productUtil->num_uf($quantity);
            if (!empty($waste_units)) {
                $new_qty = $uf_qty - $waste_units;
                $uf_qty = $new_qty;
                $quantity = $this->productUtil->num_f($new_qty);
            }

            $final_total_uf = $this->productUtil->num_uf($final_total);

            $unit_purchase_line_total = $final_total_uf / $uf_qty;

            $unit_purchase_line_total_f = $this->productUtil->num_f($unit_purchase_line_total);

            $transaction_data['mfg_wasted_units'] = $waste_units;
            $transaction_data['mfg_production_cost'] = $this->productUtil->num_uf($request->input('production_cost'));
            $transaction_data['mfg_production_cost_type'] = $request->input('mfg_production_cost_type');
            $transaction_data['mfg_is_final'] = $is_final;

            $transaction_data['doc_index'] = $request->get('doc_index');
            $transaction_data['issue_no'] = $request->get('issue_no');
            $transaction_data['section_id'] = $request->get('section_id');
            $transaction_data['shipment_no'] = $request->get('shipment_no');
            $transaction_data['contact_id'] = $request->get('contact_id');
            $transaction_data['target_qty'] = $request->get('target_qty');
            $transaction_data['day_production_qty'] = $request->get('day_production_qty');
            $transaction_data['no_workers'] = $request->get('no_workers');
            $transaction_data['production_duration'] = $request->get('production_duration');
            $transaction_data['expire_date'] = !empty($request->get('expire_date')) ? $this->productUtil->uf_date($request->get('expire_date'), true) : null;
            $transaction_data['recovery'] = $request->get('recovery');
            $transaction_data['packing_instructions'] = $request->get('packing_instructions');
            $transaction_data['is_approved'] = $is_approve;

            $purchase_line_data = [
                'variation_id' => $variation_id,
                'quantity' => $quantity,
                'qc_quantity' => $quantity,
                'product_id' => $variation->product_id,
                'product_unit_id' => $variation->product->unit_id,
                'pp_without_discount' => $unit_purchase_line_total_f,
                'discount_percent' => 0,
                'purchase_price' => $unit_purchase_line_total_f,
                'purchase_price_inc_tax' => $unit_purchase_line_total_f,
                'item_tax' => 0,
                'purchase_line_tax_id' => null,
                'mfg_date' => $this->transactionUtil->format_date($transaction_data['transaction_date'])
            ];

            if (request()->session()->get('business.enable_lot_number') == 1) {
                $purchase_line_data['lot_number'] = $request->input('lot_number');
            }

            if (request()->session()->get('business.enable_product_expiry') == 1) {
                $purchase_line_data['exp_date'] = $request->input('exp_date');
            }

            if (!empty($request->input('sub_unit_id'))) {
                $purchase_line_data['sub_unit_id'] = $request->input('sub_unit_id');
            }

            $transaction = Transaction::where('business_id', $business_id)
                ->where('type', 'production_purchase')
                ->findOrFail($id);

            //save production machines
            DB::table('production_machines')->where('transaction_id', $id)->delete();

            if ($request->has('production_machines')) {
                $inputs = $request->all();
                $pms = $inputs['production_machines'];
                foreach ($pms as $pm) {
                    $production_machine = new production_machines;
                    $production_machine->transaction_id = $id;
                    $production_machine->timestamps = false;
                    $production_machine->machine_id = $pm;
                    $production_machine->save();
                }
            }

            //Finalized production should not be editable
            if ($transaction->mfg_is_final == 1) {
                $output = [
                    'success' => 0,
                    'msg' => __('messages.something_went_wrong')
                ];
                return redirect()->action('\Modules\Manufacturing\Http\Controllers\ProductionController@index')->with('status', $output);
            }
            DB::beginTransaction();

            $transaction->update($transaction_data);

            $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);

            $update_product_price = !empty($manufacturing_settings['enable_updating_product_price']) && $is_final ? true : false;

            $this->productUtil->createOrUpdatePurchaseLines($transaction, [$purchase_line_data], $currency_details, $update_product_price);

            //Adjust stock over selling if found
            $this->productUtil->adjustStockOverSelling($transaction);

            $transaction_sell_data = [
                'transaction_date' => $transaction->transaction_date,
                'status' => $is_final ? 'final' : 'draft',
                'payment_status' => 'due',
                'final_total' => $transaction->final_total
            ];

            //Create Sell Transfer transaction
            $production_sell = Transaction::where('business_id', $business_id)
                ->where('type', 'production_sell')
                ->with('sell_lines', 'sell_lines.product', 'sell_lines.variations')
                ->where('mfg_parent_production_purchase_id', $transaction->id)
                ->first();

            $production_sell->update($transaction_sell_data);

            $sell_lines = [];
            $ingredient_quantities = $request->input('ingredients');

            foreach ($production_sell->sell_lines as $sell_line) {
                $variation = $sell_line->variations;

                $line_sub_unit_id = !empty($ingredient_quantities[$sell_line->id]['sub_unit_id']) ?
                    $ingredient_quantities[$sell_line->id]['sub_unit_id'] : null;
                $line_multiplier = 1;
                if (!empty($line_sub_unit_id)) {
                    $sub_units = $this->productUtil->getSubUnits($business_id, $sell_line->product->unit_id);
                    $line_multiplier = !empty($sub_units[$line_sub_unit_id]['multiplier']) ? $sub_units[$line_sub_unit_id]['multiplier'] : 1;
                }

                $mfg_waste_percent = !empty($ingredient_quantities[$sell_line->id]['mfg_waste_percent']) ? $this->productUtil->num_uf($ingredient_quantities[$sell_line->id]['mfg_waste_percent']) : 0;

                $mfg_ingredient_group_id = !empty($ingredient_quantities[$sell_line->id]['mfg_ingredient_group_id']) ? $ingredient_quantities[$sell_line->id]['mfg_ingredient_group_id'] : null;
                $ingredient_type = !empty($ingredient_quantities[$sell_line->id]['ingredient_type']) ? $ingredient_quantities[$sell_line->id]['ingredient_type'] : null;

                $sell_lines[] = [
                    'product_id' => $variation->product_id,
                    'variation_id' => $variation->id,
                    'quantity' => $this->productUtil->num_uf($ingredient_quantities[$sell_line->id]['quantity']),
                    'tr_sell_lot_number' => $sell_line->tr_sell_lot_number,
                    'item_tax' => 0,
                    'tax_id' => null,
                    'unit_price' => $variation->dpp_inc_tax * $line_multiplier,
                    'unit_price_inc_tax' => $variation->dpp_inc_tax * $line_multiplier,
                    'enable_stock' => $sell_line->product->enable_stock,
                    'product_unit_id' => $variation->product->unit_id,
                    'sub_unit_id' => $line_sub_unit_id,
                    'base_unit_multiplier' => $line_multiplier,
                    'mfg_waste_percent' => $mfg_waste_percent,
                    'mfg_ingredient_group_id' => $mfg_ingredient_group_id,
                    'ingredient_type' => $ingredient_type,
                ];
            }

            if (!empty($sell_lines)) {
                $this->transactionUtil->createOrUpdateSellLines($production_sell, $sell_lines, $transaction->location_id, false, 'draft', ['mfg_waste_percent' => 'mfg_waste_percent', 'mfg_ingredient_group_id' => 'mfg_ingredient_group_id']);
            }

            if ($transaction_sell_data['status'] == 'final') {

                $get_purchase_line_details = PurchaseLine::where('transaction_id', $id)->first();

                $location = BusinessLocation::select('id')->skip(2)->take(1)->first();

                //check if already lot

                $check_if_already = VariationLocationDetails::where('location_id', $location->id)
                    ->where('lot_number', $get_purchase_line_details->lot_number)
                    ->first();

                if (!empty($check_if_already)) {
                    //update production in production location
                    $vld_quantity = VariationLocationDetails::where('location_id', $location->id)
                        ->where('lot_number', $get_purchase_line_details->lot_number)
                        ->first();

                    $current_vld_qty = $vld_quantity->qty_available;
                    $increase_vld['qty_available'] = $current_vld_qty + $get_purchase_line_details->quantity;
                    $vld_quantity->update($increase_vld);
                } else {
                    //save production in production location

                    $variation = Variation::find($get_purchase_line_details->variation_id);

                    $insert_vld = new VariationLocationDetails;
                    $insert_vld->product_id = $get_purchase_line_details->product_id;
                    $insert_vld->product_variation_id = $variation->product_variation_id;
                    $insert_vld->variation_id = $get_purchase_line_details->variation_id;
                    $insert_vld->location_id = $location->id;
                    $insert_vld->qty_available = $get_purchase_line_details->quantity;
                    $insert_vld->lot_number = $get_purchase_line_details->lot_number;

                    $insert_vld->save();
                }

                //update transaction status
                Transaction::where('id', $id)
                    ->update([
                        'status' => 'received',
                    ]);

                //update purchase line qc 4 status
                PurchaseLine::where('transaction_id', $id)
                    ->update([
                        'is_production_qc' => 'available',
                        'is_qc4_checked' => 'available',
                    ]);

                // foreach ($sell_lines as $sell_line) {
                //     if ($sell_line['enable_stock']) {
                //         $line_qty = $sell_line['quantity'] * $sell_line['base_unit_multiplier'];
                //         $this->productUtil->decreaseProductQuantity(
                //             $sell_line['product_id'],
                //             $sell_line['variation_id'],
                //             $production_sell->location_id,
                //             $line_qty
                //         );
                //     }
                // }

                //Map sell lines with purchase lines
                // $business = [
                //     'id' => $business_id,
                //     'accounting_method' => $request->session()->get('business.accounting_method'),
                //     'location_id' => $production_sell->location_id,
                //     'pos_settings' => $request->session()->get('business.pos_settings')
                // ];
                // $this->transactionUtil->mapPurchaseSell($business, $production_sell->sell_lines, 'production_purchase');
            }

            DB::commit();

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.updated_success')
            ];
        } catch (Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->action('\Modules\Manufacturing\Http\Controllers\ProductionController@index')->with('status', $output);
    }


    public function update_status(Request $request)
    {
        $id = $request->id;
        $new_status = $request->new_status;
        $update_type = $request->update_type;

        try {
            if ($update_type == 'status') {
                Transaction::where('id', $id)
                    ->update([
                        'status' => $new_status,
                    ]);
            } else {
                Transaction::where('id', $id)
                    ->update([
                        'priority' => $new_status,
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

    // public function update_priority(Request $request)
    // {
    //     $id = $request->id;
    //     $new_status = $request->new_status;

    //     try {
    //         Transaction::where('id', $id)
    //             ->update([
    //                 'priority' => $new_status,
    //             ]);

    //         $output = [
    //             'success' => true,
    //             'msg' => __("lang_v1.added_success")
    //         ];
    //     } catch (\Exception $e) {
    //         \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

    //         $output = [
    //             'success' => false,
    //             'msg' => __("messages.something_went_wrong")
    //         ];
    //     }

    //     return $output;
    // }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module')) || !auth()->user()->can('manufacturing.access_production')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $transaction = Transaction::where('id', $id)
                    ->where('business_id', $business_id)
                    ->where('type', 'production_purchase')
                    ->where('mfg_is_final', 0)
                    ->delete();

                DB::table('production_machines')->where('transaction_id', $id)->delete();

                $output = [
                    'success' => true,
                    'msg' => __('lang_v1.deleted_success')
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output['success'] = false;
                $output['msg'] = trans("messages.something_went_wrong");
            }

            return $output;
        }
    }

    /**
     * Retrives data for manufacturing report.
     * @return Response
     */
    public function getManufacturingReport()
    {
        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $start_date = request()->get('start_date');
            $end_date = request()->get('end_date');
            $location_id = request()->get('location_id');

            $production_totals = $this->mfgUtil->getProductionTotals($business_id, $location_id, $start_date, $end_date);

            $total_sold = $this->mfgUtil->getTotalSold($business_id, $location_id, $start_date, $end_date);


            $output['total_production'] = $production_totals['total_production'];
            $output['total_production_cost'] = $production_totals['total_production_cost'];
            $output['total_sold'] = $total_sold;

            return $output;
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);
        return view('manufacturing::production.report')->with(compact('business_locations'));
    }

    public function printJobCard($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module')) || !auth()->user()->can('manufacturing.access_production')) {
            abort(403, 'Unauthorized action.');
        }

        $production_purchase = Transaction::where('business_id', $business_id)
            ->where('type', 'production_purchase')
            ->with([
                'purchase_lines', 'purchase_lines.variations', 'purchase_lines.variations.product_variation', 'purchase_lines.variations.product',
                'purchase_lines.sub_unit', 'purchase_lines.variations.product.unit',
            ])
            ->findOrFail($id);

        $production_sell = Transaction::where('business_id', $business_id)
            ->where('type', 'production_sell')
            ->where('mfg_parent_production_purchase_id', $production_purchase->id)
            ->with([
                'sell_lines',
                'sell_lines.variations',
                'sell_lines.product',
                'sell_lines.variations.product_variation',
                'sell_lines.variations.product',
                'sell_lines.sub_unit',
                'sell_lines.sell_line_purchase_lines',
                'sell_lines.sell_line_purchase_lines.purchase_line'
            ])
            ->first();

        $purchase_line = $production_purchase->purchase_lines[0];

        $base_unit_multiplier = !empty($purchase_line->sub_unit) ? $purchase_line->sub_unit->base_unit_multiplier : 1;
        $quantity = $purchase_line->quantity / $base_unit_multiplier;
        $quantity_wasted = 0;
        $unit_name = !empty($purchase_line->sub_unit) ?  $purchase_line->sub_unit->short_name : $purchase_line->variations->product->unit->short_name;
        if (!empty($production_purchase->mfg_wasted_units)) {
            $quantity_wasted = $production_purchase->mfg_wasted_units;
            $quantity += $quantity_wasted;
        }

        $actual_quantity = $quantity * $base_unit_multiplier;

        $ingredients = [];
        $ingredient_groups = [];
        $total_ingredients_price = 0;
        //Format sell lines
        foreach ($production_sell->sell_lines as $sell_line) {
            $variation = $sell_line->variations;
            $sell_line_qty = empty($sell_line->sub_unit) ? $sell_line->quantity : $sell_line->quantity / $sell_line->sub_unit->base_unit_multiplier;
            $unit = empty($sell_line->sub_unit) ? $variation->product->unit->short_name : $sell_line->sub_unit->short_name;

            $line_total_price = $variation->dpp_inc_tax * $sell_line->quantity;
            $total_ingredients_price += $line_total_price;

            $waste_percent = !empty($sell_line->mfg_waste_percent) ? $sell_line->mfg_waste_percent : 0;
            $wasted_qty = $this->moduleUtil->calc_percentage($sell_line_qty, $waste_percent);
            $final_quantity = $sell_line_qty - $wasted_qty;
            $ingredient_type = $sell_line->ingredient_type;

            $lot_numbers = [];

            foreach ($sell_line->sell_line_purchase_lines as $slpl) {
                $lot_number = !empty($slpl->purchase_line->lot_number) ? $slpl->purchase_line->lot_number : '';
                if (!empty($slpl->purchase_line->exp_date)) {
                    $lot_number .= ' - ' . $this->moduleUtil->format_date($slpl->purchase_line->exp_date);
                }

                if ($lot_number != '') {
                    $lot_numbers[] = $lot_number;
                }
            }

            if (empty($sell_line->mfg_ingredient_group_id)) {
                $ingredients[] = [
                    'dpp_inc_tax' => $variation->dpp_inc_tax,
                    'quantity' => $sell_line_qty,
                    'full_name' => $sell_line->product->name,
                    'id' => $variation->id,
                    'unit' => $unit,
                    'allow_decimal' => $variation->product->unit->allow_decimal,
                    'variation' => $variation,
                    'enable_stock' => $variation->product->enable_stock,
                    'total_price' => $line_total_price,
                    'waste_percent' =>  $waste_percent,
                    'final_quantity' => $final_quantity,
                    'lot_numbers' => implode(', ', $lot_numbers),
                    'ingredient_type' => $ingredient_type
                ];
            } else {
                if (!isset($ingredient_groups[$sell_line->mfg_ingredient_group_id]['ig_name'])) {
                    $i_group = MfgIngredientGroup::find($sell_line->mfg_ingredient_group_id);
                    $ingredient_groups[$sell_line->mfg_ingredient_group_id]['ig_name'] = $i_group->name;
                    $ingredient_groups[$sell_line->mfg_ingredient_group_id]['ig_description'] = $i_group->description;
                }
                $ingredient_groups[$sell_line->mfg_ingredient_group_id]['ig_ingredients'][] = [
                    'dpp_inc_tax' => $variation->dpp_inc_tax,
                    'quantity' => $sell_line_qty,
                    'full_name' => $variation->name,
                    'id' => $variation->id,
                    'unit' => $unit,
                    'allow_decimal' => $variation->product->unit->allow_decimal,
                    'variation' => $variation,
                    'enable_stock' => $variation->product->enable_stock,
                    'total_price' => $line_total_price,
                    'waste_percent' =>  $waste_percent,
                    'final_quantity' => $final_quantity,
                    'lot_numbers' => implode(', ', $lot_numbers),
                    'ingredient_type' => $ingredient_type
                ];
            }
        }

        $total_production_cost = 0;
        if (!empty($production_purchase->mfg_production_cost)) {
            $total_production_cost = $production_purchase->mfg_production_cost;
            if ($production_purchase->mfg_production_cost_type == 'percentage') {
                $total_production_cost = $this->transactionUtil->calc_percentage($total_ingredients_price, $production_purchase->mfg_production_cost);
            } elseif ($production_purchase->mfg_production_cost_type == 'per_unit') {
                $total_production_cost = $production_purchase->mfg_production_cost * $quantity;
            }
        }
        
        //get main production product
        $main_product = PurchaseLine::where('transaction_id', '=', $id)
        ->join('products', 'products.id', '=', 'purchase_lines.product_id')
        ->first();

        $main_product = $main_product->name;

        $pdf = \PDF::loadView('manufacturing::production.job_card', compact('production_purchase', 'production_sell', 'purchase_line', 'ingredients', 'unit_name', 'quantity', 'quantity_wasted', 'actual_quantity', 'total_production_cost', 'ingredient_groups', 'main_product'));
        return $pdf->stream();
        // return view('manufacturing::production.blade')->with(compact('production_purchase', 'production_sell', 'purchase_line', 'ingredients', 'unit_name', 'quantity', 'quantity_wasted', 'actual_quantity', 'total_production_cost', 'ingredient_groups'));
    }
}
