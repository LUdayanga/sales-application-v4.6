<?php

namespace App\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use App\Cheque;
use App\ChequeTransaction;
use App\Contact;
use App\Events\TransactionPaymentAdded;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Log;

class ChequesController extends Controller
{
    /**
     * @var ModuleUtil
     */
    private $moduleUtil;
    private $transactionUtil;

    /**
     * ChequesController constructor.
     * @param ModuleUtil $moduleUtil
     * @param TransactionUtil $transactionUtil
     */
    public function __construct(ModuleUtil $moduleUtil, TransactionUtil $transactionUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        if (\request()->ajax()) {

            $cheques = Cheque::join('accounts', 'cheques.account_id', '=', 'accounts.id')
                ->leftJoin('cheque_transactions', 'cheque_transactions.cheque_id', '=', 'cheques.id')
                ->select(
                    'cheques.id',
                    'accounts.name',
//                    'accounts.cheque_return_fee',
                    'cheques.cheque_number',
                    'cheques.cheque_date',
                    'cheques.cheque_amount',
                    'cheques.cheque_status',
                    'cheques.cheque_type'
                )
                ->orderBy('cheques.id', 'desc')
                ->groupBy('cheques.id');
//                ->get();

//            filters
            $cheque_number = \request()->get('cheque_number');
            if ($cheque_number) {
                $cheques->where('cheque_number', $cheque_number);
            }
            $contact_id = \request()->get('contact_id');
            if ($contact_id) {
                $cheques->where('cheque_transactions.contact_id', $contact_id);
            }
            $date = \request()->get('date');
            if ($date) {
                $date = Carbon::parse($date)->format('Y-m-d h:m:s');
                $cheques->where('cheque_date', $date);
            }
            $account_id = \request()->get('account_id');
            if ($account_id) {
                $cheques->where('cheques.account_id', $account_id);
            }

            return datatables()->of($cheques)
                ->addColumn('action', function ($row) {
                    $html =
                        '<div class="btn-group"><button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">' . __("messages.actions") . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button><ul class="dropdown-menu dropdown-menu-left" role="menu">';
                    $html .=
                        '<li><a href="' . action('ChequesController@show', [$row->id]) . '" class="view-cheque"><i class="fa fa-eye"></i> ' . __("messages.view") . '</a></li>';

                    if ($row->cheque_status != 'paid') {
                        $html .=
                            '<li><a href="' . action('ChequesController@edit', [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                    }

                    if ($row->cheque_status == 'due') {
                        $html .=
                            '<li><a href="' . action('ChequesController@chequeMarkAsPaid', [$row->id]) . '" class="mark-as-paid"><i class="glyphicon glyphicon-edit"></i> ' . __("account.mark_as_paid") . '</a></li>';
                    } elseif ($row->cheque_status == 'paid') {
                        $html .=
                            '<li><a href="' . action('ChequesController@chequeMarkAsReturned', [$row->id]) . '" class="mark-as-returned"><i class="glyphicon glyphicon-edit"></i> ' . __("account.mark_as_returned") . '</a></li>';
                    }

                    if ($row->cheque_status != 'paid') {
                        $html .=
                            '<li><a href="' . action('ChequesController@destroy', [$row->id]) . '" class="delete-cheque"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';

                    }
                    $html .= '</ul></div>';

                    return $html;
                })
                ->editColumn('cheque_amount', function ($row) {
                    return number_format($row->cheque_amount, 2);
                })
                ->editColumn('cheque_status', function ($row) {
                    if ($row->cheque_status == 'due') {
                        return '<span class="label label-warning">Due</span>';
                    } elseif ($row->cheque_status == 'partial') {
                        return '<span class="label label-info">Partial</span>';
                    } elseif ($row->cheque_status == 'paid') {
                        return '<span class="label label-success">Paid</span>';
                    } else {
                        return '<span class="label label-danger">Returned</span>';
                    }
                })
                ->editColumn('cheque_type', function ($row) {
                    if ($row->cheque_type == 'giving') {
                        return '<span class="label label-info">Issued</span>';
                    } else {
                        return '<span class="label label-primary">Received</span>';
                    }
                })
//                ->editColumn('cheque_return_fee', function ($row) {
//                    return number_format($row->cheque_return_fee, 2);
//                })
                ->rawColumns(['action', 'cheque_status', 'cheque_type'])
                ->make(true);

        }

        $business_id = request()->session()->get('user.business_id');
        $contacts = Contact::contactDropdown($business_id, false, false);
        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }
        return view('cheque.index', compact('contacts', 'accounts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        $contacts = Contact::contactDropdown($business_id, false, false);
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }
        return view('cheque.create', compact('contacts', 'accounts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        try {

            DB::beginTransaction();
            $cheques = $request->get('cheques');
            $transactions = $request->get('transactions');
            foreach ($cheques as $cheque) {
                $cheque = Cheque::create([
                    'cheque_number' => $cheque['cheque_number'],
                    'cheque_date' => Carbon::parse($cheque['cheque_date'])->format('Y-m-d h:m:s'),
                    'cheque_issued_date' => Carbon::parse($cheque['cheque_issued_date'])->format('Y-m-d h:m:s'),
                    'cheque_amount' => $cheque['cheque_amount'],
                    'cheque_status' => 'due',
                    'cheque_type' => $cheque['cheque_type'],
                    'account_id' => $cheque['account_id']
                ]);

                foreach ($transactions as $transaction_id) {
                    ChequeTransaction::create([
                        'cheque_amount' => $cheque['cheque_amount'],
                        'cheque_id' => $cheque->id,
                        'transaction_id' => $transaction_id,
                        'contact_id' => $request->get('contact_id')
                    ]);
                }
            }

            DB::commit();

            $output = ['success' => true,
                'msg' => __("account.cheque_created_success")
            ];
        } catch (Exception $e) {
            DB::rollBack();
            // dd($e);
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return redirect()->route('cheque.index')->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return Application|Factory|Response|View
     */
    public function show($id)
    {
        $cheque = Cheque::find($id);
        $cheque_transactions = ChequeTransaction::join('transactions', 'cheque_transactions.transaction_id', '=', 'transactions.id')
            ->where('cheque_transactions.cheque_id', $cheque->id)
            ->select(
                'transactions.id',
                'transactions.invoice_no',
                'transactions.ref_no',
                'transactions.final_total',
                'transactions.transaction_date',
                'transactions.payment_status'
            )
            ->orderBy('transactions.id', 'desc')
            ->get();
        return \view('cheque.show', compact('cheque', 'cheque_transactions'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param $id
     * @return Application|Factory|View|void
     */
    public function edit($id)
    {
        try {
            $cheque = Cheque::find($id);
            $contact_id = ChequeTransaction::where('cheque_id', $cheque->id)->first()->contact_id;
            $cheque_transactions = ChequeTransaction::join('transactions', 'cheque_transactions.transaction_id', '=', 'transactions.id')
                ->where('cheque_transactions.cheque_id', $cheque->id)
                ->select(
                    'transactions.id',
                    'transactions.invoice_no',
                    'transactions.ref_no',
                    'transactions.final_total',
                    'transactions.transaction_date',
                    'transactions.payment_status'
                )
                ->orderBy('transactions.id', 'desc')
                ->get();


            $cheque['contact_id'] = $contact_id;
            $business_id = request()->session()->get('user.business_id');
            $contacts = Contact::contactDropdown($business_id, false, false);
            $accounts = [];
            if ($this->moduleUtil->isModuleEnabled('account')) {
                $accounts = Account::forDropdown($business_id, true, false);
            }
            return \view('cheque.edit', compact('cheque', 'accounts', 'contacts', 'cheque_transactions'));
        } catch (Exception $e) {

        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $inputs = $request->except(['_token', '_method', 'transactions', 'cheque-related-invoice-table_length', 'contact_id', 'q']);
            $cheque = Cheque::find($id);
            $inputs['cheque_date'] = Carbon::parse($inputs['cheque_date'])->format('Y-m-d h:m:s');
            $inputs['cheque_issued_date'] = Carbon::parse($inputs['cheque_issued_date'])->format('Y-m-d h:m:s');
            DB::beginTransaction();
            foreach ($cheque->cheque_transactions as $cheque_transaction) {
                $cheque_transaction->delete();
            }

            $cheque->update($inputs);
            $transactions = $request->get('transactions');
            foreach ($transactions as $transaction_id) {
                ChequeTransaction::create([
                    'cheque_amount' => $inputs['cheque_amount'],
                    'cheque_id' => $cheque->id,
                    'transaction_id' => $transaction_id,
                    'contact_id' => $request->get('contact_id')
                ]);
            }
            DB::commit();
            $output = ['success' => true,
                'msg' => __("account.cheque_updated_success")
            ];
        } catch (Exception $e) {
            DB::rollBack();
            // dd($e);
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }
        return redirect()->route('cheque.index')->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return array
     */
    public function destroy($id)
    {
        try {
            $cheque = Cheque::find($id);
            DB::beginTransaction();
            foreach ($cheque->cheque_transactions as $cheque_transaction) {
                $cheque_transaction->delete();
            }
            $account_transactions = AccountTransaction::where('cheque_id', $cheque->id)->get();
            foreach ($account_transactions as $account_transaction) {
                $account_transaction->delete();
            }
            $cheque->delete();
            DB::commit();
            $output = ['success' => true,
                'msg' => __("account.cheque_delete_success")
            ];
        } catch (Exception $e) {
            DB::rollBack();
            // dd($e);
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    public function getInvoicesRelatedToContact()
    {
        if (\request()->ajax()) {
         

            $contact_id = \request()->get('contact_id');
           
            if ($contact_id != 'all') {
                $business_id = request()->session()->get('user.business_id');

                $contact_related_transactions = Transaction::join('contacts', 'transactions.contact_id', '=', 'contacts.id')
                    ->leftJoin('transaction_payments', 'transaction_payments.transaction_id', '=', 'transactions.id')
                    ->where([['transactions.contact_id', $contact_id], ['transactions.business_id', $business_id]])
                    ->whereIn('transactions.payment_status', ['due', 'partial'])
                    ->select(
                        
                        'transactions.id',
                        'transactions.invoice_no',
                        'transactions.ref_no',
                        'transactions.final_total',
                        DB::raw('SUM(transaction_payments.amount) as paid_amount'),
                        'transactions.transaction_date',
                        'transactions.payment_status'
                    )
                    ->groupBy('transactions.id')
                    ->orderBy('transactions.id', 'desc');
                    // ->get();

                    $start_date = !empty(request()->input('start_date')) ? request()->input('start_date') : '';
                    $end_date = !empty(request()->input('end_date')) ? request()->input('end_date') : '';
                    
        
                    $start_date = request()->input('start_date');
                    $end_date = request()->input('end_date');
                    
                    if (!empty($start_date) && !empty($end_date)) {
                        $contact_related_transactions->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
                    }
        

                return datatables()->of($contact_related_transactions)
                    ->editColumn('invoice_no', function ($row) {
                        $html = '<input id="" name="transactions[]" value="' . $row->id . '" type="hidden">
                                <input id="invoice_total" value="' . $row->final_total . '" type="hidden">';
                        if (!empty($row->invoice_no)) {
                            return $html . $row->invoice_no;
                        }
                        return $html . $row->ref_no;
                    })
                    ->editColumn('final_total', function ($row) {
//                            $html = '<input id="final_total" type="hidden"value="'. number_format($row->final_total, 2) .'">';
                        $available_balance = $row->final_total - $row->paid_amount;
                        return number_format($available_balance, 2);
                    })
                    ->editColumn('payment_status', function ($row) {
                        $payment_status = $row->payment_status;
                        if ($payment_status == 'due') {
                            return '<span class="label label-warning">Due</span>';
                        } elseif ($payment_status == 'partial') {
                            return '<span class="label label-info">Partial</span>';
                        } elseif ($payment_status == 'paid') {
                            return '<span class="label label-success">Paid</span>';
                        }
                        return '<span class="label label-danger">Returned</span>';
                    })
                    ->addColumn('action', function () {
                        return '<button type="button" class="btn btn-sm btn-danger remove_invoice"><i class="fa fa-trash"></i></button>';
                    })
                    ->rawColumns(['invoice_no', 'final_total', 'payment_status', 'action'])
                    ->make(true);


            }

        }
    }


    public function chequeMarkAsPaid($id)
    {

        try {
            $cheque = Cheque::find($id);
            DB::beginTransaction();
            foreach ($cheque->cheque_transactions as $cheque_transaction) {
                $transaction = Transaction::find($cheque_transaction->transaction_id);
                $transaction_payment = TransactionPayment::where([['transaction_id', $transaction->id], ['business_id', $transaction->business_id]])->sum('amount');
                $available_balance = ($transaction->final_total - $transaction_payment);
//                create transaction payment
                if ($transaction->payment_status != 'paid') {
                    if ($cheque_transaction->cheque_amount == $available_balance){
                        $payment = $available_balance;
                    }elseif ($cheque_transaction->cheque_amount > $available_balance){
                        $payment = $available_balance;
                    }else {
                        $payment = $cheque_transaction->cheque_amount;
                    }
                    $inputs['amount'] = $payment;
                    $inputs['method'] = 'cheque';
                    $inputs['note'] = 'Payment from cheque number: ' . $cheque->cheque_number;
                    $inputs['paid_on'] = now();
                    $inputs['transaction_id'] = $transaction->id;
                    $inputs['business_id'] = $transaction->business_id;
                    $inputs['created_by'] = auth()->user()->id;
                    $inputs['payment_for'] = $transaction->contact_id;
//                    $inputs['account_id'] = $cheque->account_id;
                    $inputs['cheque_id'] = $cheque->id;

                    $prefix_type = 'purchase_payment';
                    if ($transaction->type == 'sell') {
                        $prefix_type = 'sell_payment';
                    }
                    $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
                    //Generate reference number
                    $inputs['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

                    if (!empty($inputs['amount'])) {
                        $transaction_payment = TransactionPayment::create($inputs);
//                        $inputs['transaction_type'] = $transaction->type;
//                        event(new TransactionPaymentAdded($transaction_payment, $inputs));
                    }
                    //update payment status
                    $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

//                    add payment for account
                    $account_transaction_inputs['account_id'] = $cheque->account_id;
                    $type = 'debit';
                    if ($transaction->type == 'sell') {
                        $type = 'credit';
                    }
                    $account_transaction_inputs['type'] = $type;
                    $account_transaction_inputs['amount'] = $payment;
                    $account_transaction_inputs['operation_date'] = now();
                    $account_transaction_inputs['created_by'] = auth()->user()->id;
                    $account_transaction_inputs['transaction_id'] = $transaction->id;
                    $account_transaction_inputs['transaction_payment_id'] = $transaction_payment->id;
                    $account_transaction_inputs['cheque_id'] = $cheque->id;
                    $account_transaction_inputs['note'] = 'Payment from cheque number: ' . $cheque->cheque_number;
                    AccountTransaction::create($account_transaction_inputs);

                }
            }
            $cheque->cheque_status = 'paid';
            $cheque->update();
            DB::commit();
            $output = ['success' => true,
                'msg' => __('account.mark_as_paid_success')
            ];
        } catch (Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }


    public function chequeMarkAsReturned($id)
    {

        try {
            $cheque = Cheque::find($id);
            DB::beginTransaction();
            $transaction_payments = TransactionPayment::where('cheque_id', $cheque->id)->get();
            foreach ($transaction_payments as $transaction_payment) {
                $transaction_payment->delete();
            }
            $account_transactions = AccountTransaction::where('cheque_id', $cheque->id)->get();
            foreach ($account_transactions as $account_transaction) {
                $account_transaction->delete();
            }
            foreach ($cheque->cheque_transactions as $cheque_transaction) {
                $transaction = Transaction::find($cheque_transaction->transaction_id);
                //update payment status
                $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
            }

            $cheque->cheque_status = 'return';
            $cheque->update();

//            add cheque return payment
            if ($cheque->cheque_type == 'giving') {
                $cheque_return_fee = Account::where('id', $cheque->account_id)->first()->cheque_return_fee;
                if ($cheque_return_fee) {
                    $account_transaction_inputs['type'] = 'debit';
                    $account_transaction_inputs['amount'] = $cheque_return_fee;
                    $account_transaction_inputs['operation_date'] = now();
                    $account_transaction_inputs['created_by'] = auth()->user()->id;
                    $account_transaction_inputs['cheque_id'] = $cheque->id;
                    $account_transaction_inputs['note'] = 'Cheque return fee for : ' . $cheque->cheque_number;
                    $account_transaction_inputs['account_id'] = $cheque->account_id;
                    AccountTransaction::create($account_transaction_inputs);
                }
            }

            DB::commit();
            $output = ['success' => true,
                'msg' => __('account.mark_as_returned')
            ];
        } catch (Exception $exception) {
            DB::rollBack();
            \Log::emergency("File:" . $exception->getFile() . "Line:" . $exception->getLine() . "Message:" . $exception->getMessage());

            $output = ['success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }


    public function getNewChequeRow(Request $request)
    {
        if ($request->ajax()){
            $index = $request->get('index');
            $business_id = request()->session()->get('user.business_id');
            $accounts = [];
            if ($this->moduleUtil->isModuleEnabled('account')) {
                $accounts = Account::forDropdown($business_id, true, false);
            }
            return \view('cheque.cheque_row', compact('accounts', 'index'));
        }
    }

}