<?php

namespace App\Http\Controllers;

use Input;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Validator;
use DB;

use App\Payment;
use App\User;

class PaymentController extends Controller
{
	public function today(){
		$today = strtotime(date("Y-m-d H:i:s")) + ((60*60) * 6.5);
		return date('Y-m-d H:i:s', $today);
	}

	public function pay(){
		// create the validation rules ------------------------
        $rules = array(
            'amount'          	=> 'required|numeric',
            'vendor_id'         => 'required|exists:users,id'
        );
        // do the validation ----------------------------------
        // validate against the inputs from our form
        $validator = Validator::make(Input::all(), $rules);

        // check if the validator failed -----------------------
        if ($validator->fails()) {
            if($validator->errors()->has('amount'))
                return response()->json($validator->errors()->first('amount'), 400);
            if($validator->errors()->has('vendor_id'))
                return response()->json($validator->errors()->first('vendor_id'), 400);
        }

        // creating payment
        try {
        	DB::beginTransaction();
        	$payment = new Payment();
	        $payment->paid_date = $this->today();
	        $payment->amount = Input::get('amount');
	        $payment->vendor_id = Input::get('vendor_id');
	        $payment->status = false;
	        $payment->save();
	        if($payment){
	        	$payment->code = sprintf('%016d',($payment->id + rand()).$payment->id.$payment->vendor_id);
	        	$payment->update();
	        }
        	DB::commit();
        } catch (Exception $e) {
        	DB::rollBack();
        }
        
        return response()->json($payment);
	}

	public function confirm(){
		// create the validation rules ------------------------
        $rules = array(
            'code'          	=> 'required|exists:tbl_payment_transaction,code',
            'client_id'         => 'required|exists:users,id'
        );
        // do the validation ----------------------------------
        // validate against the inputs from our form
        $validator = Validator::make(Input::all(), $rules);

        // check if the validator failed -----------------------
        if ($validator->fails()) {
            if($validator->errors()->has('code'))
                return response()->json($validator->errors()->first('code'), 400);
            if($validator->errors()->has('client_id'))
                return response()->json($validator->errors()->first('client_id'), 400);
        }

        try {
        	DB::beginTransaction();

        	$payment = Payment::where('code', Input::get('code'))->first();

        	if($payment->status){
        		DB::rollBack();
        		return response()->json("This payment code[".Input::get('code')."] is already paid.", 400);
        	}
        	$payment->received_date = $this->today();
        	$payment->client_id = Input::get('client_id');
        	$payment->status = true;
        	$payment->update();

        	if($payment){
        		// Updating cash amount for payment vendor;
        		$vendor = User::whereid($payment->vendor_id)->first();
        		if($vendor){
                    $payment->vendor = $vendor;
        			$vendor->cash = $vendor->cash + $payment->amount;
        			$vendor->update();
        		}else{
        			DB::rollBack();
        			return response()->json("Invalid payment vendor id.", 400);
        		}

        		// Updating deposit amount for payment deposit;
        		$client = User::whereid(Input::get('client_id'))->first();
        		if($client){
                    $payment->client = $client;
        			$client->deposit = $vendor->deposit - $payment->amount;
        			$client->update();
        		}else{
        			DB::rollBack();
        			return response()->json("Invalid payment client id.", 400);
        		}
        	}

            $uri = new Uri("http://128.199.156.119/");
            $uri->post("api-v1/make_money_pay_success", ["payment_code"=>$payment->code]);

        	DB::commit();    	
        } catch (Exception $e) {
        	DB::rollBack();
        }
        return response()->json($payment);

	}
}