<?php

namespace App\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use App\BusinessLocation;
use App\Cheque;
use App\ChequeTransaction;
use App\Contact;
use App\Events\TransactionPaymentAdded;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Log;

class BulkPaymentController extends Controller
{
    /**
     * @var ModuleUtil
     */
    private $moduleUtil;
    /**
     * @var TransactionUtil
     */
    private $transactionUtil;

    public function __construct(ModuleUtil $moduleUtil, TransactionUtil $transactionUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->transactionUtil = $transactionUtil;
        $this->dummyPaymentLine = ['method' => '', 'amount' => 0, 'note' => '', 'card_transaction_number' => '', 'card_number' => '', 'card_type' => '', 'card_holder_name' => '', 'card_month' => '', 'card_year' => '', 'card_security' => '', 'cheque_number' => '', 'bank_account_number' => '',
            'is_return' => 0, 'transaction_no' => ''];
    }


    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        $contacts = Contact::contactDropdown($business_id, false, false);
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }
        $payment_lines[] = $this->dummyPaymentLine;
        $payment_types = $this->transactionUtil->payment_types(null, true, $business_id);
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $business_locations = $business_locations['locations'];

        $default_location = null;
        foreach ($business_locations as $id => $name) {
            $default_location = BusinessLocation::findOrFail($id);
            break;
        }
        
        return view('bulk_payment.create', compact('contacts', 'accounts', 'payment_types',
            'payment_lines', 'default_location'));
    }


    public function store(Request $request)
    {
       
        try {
            $transactions = $request->get('transactions');
            $payments = $request->get('payment');
            DB::beginTransaction();
            foreach ($transactions as $transaction_id) {
                $transaction = Transaction::find($transaction_id);
                $transaction_payment = TransactionPayment::where([['transaction_id', $transaction->id], ['business_id', $transaction->business_id]])->sum('amount');
                $available_balance = ($transaction->final_total - $transaction_payment);
                $balance = $available_balance;
//                create transaction payment
                if ($transaction->payment_status != 'paid') {
                    foreach ($payments as $payment) {
                            // $available_amount = $payment['amount'];
                            if ($payment['method'] != 'cheque') {
                                if ($payment['amount'] == $balance) {
                                    $payment['amount'] = $balance;
                                } elseif ($payment['amount'] > $balance) {
                                    $payment['amount'] = $balance;
                                } else {
                                    // $payment = $payment['amount'];
                                }

                                if ($balance != 0) {

                                $inputs['amount'] = $payment['amount'];
                                $inputs['method'] = $payment['method'];
                                $inputs['note'] = $payment['note'];
                                $inputs['paid_on'] = now();
                                $inputs['transaction_id'] = $transaction->id;
                                $inputs['business_id'] = $transaction->business_id;
                                $inputs['created_by'] = auth()->user()->id;
                                $inputs['payment_for'] = $transaction->contact_id;
                                $inputs['account_id'] = $payment['account_id'];
                                $inputs['card_number'] = $payment['card_number'];
                                $inputs['card_holder_name'] = $payment['card_holder_name'];
                                // $inputs['card_transaction_number'] = $payment['card_transaction_number'];
                                // $inputs['card_type'] = $payment['card_type'];
                                // $inputs['card_month'] = $payment['card_month'];
                                // $inputs['card_year'] = $payment['card_year'];
                                // $inputs['card_security'] = $payment['card_security'];
                                $inputs['cheque_number'] = $payment['cheque_number'];
                                $inputs['bank_account_number'] = $payment['bank_account_number'];

                                if ($payment['method'] == 'custom_pay_1') {
                                    $inputs['transaction_no'] = $payment['transaction_no_1'];
                                } elseif ($payment['method'] == 'custom_pay_2') {
                                    $inputs['transaction_no'] = $payment['transaction_no_2'];
                                } elseif ($payment['method'] == 'custom_pay_3') {
                                    $inputs['transaction_no'] = $payment['transaction_no_3'];
                                }

                                if ($payment['method'] == 'card') {
                                    $amount = $payment['amount'];
                                    // dd($amount, $balance);
                                    $percentage = $payment['card_holder_name'];
                                    $added_value = ($amount * $percentage) / 100;
                                    $payment_amount = $amount + $added_value;
                                    $inputs['amount'] = $payment_amount;
                                    $inputs['note'] = "Card percentage payment added :" . $added_value;
                                    $transaction->final_total = $transaction->final_total + $added_value;
                                    $transaction->update();
                                }

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
                                if (!empty($payment['account_id'])) {
                                    $account_transaction_inputs['account_id'] = $payment['account_id'];
                                    $type = 'debit';
                                    if ($transaction->type == 'sell') {
                                        $type = 'credit';
                                    }
                                    $account_transaction_inputs['type'] = $type;
                                    $account_transaction_inputs['amount'] = $payment['amount'];
                                    $account_transaction_inputs['operation_date'] = now();
                                    $account_transaction_inputs['created_by'] = auth()->user()->id;
                                    $account_transaction_inputs['transaction_id'] = $transaction->id;
                                    $account_transaction_inputs['transaction_payment_id'] = $transaction_payment->id;
                                    $account_transaction_inputs['note'] = 'Bulk Payment';
                                    AccountTransaction::create($account_transaction_inputs);
                                }
                                $balance -= $payment['amount'];
                                }

                            } else {

                            if ($balance != 0) {
                                
                                $cheque_inputs['cheque_number'] = $payment['cheque_number'];
                                $cheque_inputs['cheque_date'] = $this->transactionUtil->uf_date($payment['cheque_issued_date'], true);
                                $cheque_inputs['cheque_issued_date'] = now();
                                $cheque_inputs['cheque_amount'] = $payment['amount'];
                                $cheque_inputs['cheque_status'] = 'due';
                                $cheque_inputs['cheque_type'] = $payment['cheque_type'];
                                $cheque_inputs['account_id'] = $payment['account_id'];
                                $cheque = Cheque::create($cheque_inputs);

                                ChequeTransaction::create([
                                    'cheque_amount' =>  $payment['amount'],
                                    'cheque_id' => $cheque->id,
                                    'transaction_id' => $transaction_id,
                                    'contact_id' => $transaction->contact_id
                                ]);

                                $transaction->staff_note = 'Payment will be pay by cheque number : ' . $payment['cheque_number'];
                                $transaction->update();
                            } 
                        }
                }
                }
            }
            DB::commit();
            $output = ['success' => true,
                'msg' => __('account.bulk_payment_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
           
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }
        return redirect()->back()->with('status', $output);
    }

}