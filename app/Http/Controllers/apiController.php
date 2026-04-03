<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

use App\Models\User;
use App\Models\Address;


class apiController extends Controller
{
    
    function login(Request $req){

        $phone = $req->input('phone');
        $email = $req->input('email');
        $password = trim($req->input('password'));

        $loginField = $email ? 'email' : 'phone';
        $loginValue = $email ?: $phone;

        if (!$loginValue) {
            $data['message'] = 'Phone or email is required';
            $data['data'] = [];
            $data['status'] = 400;
            return Response::json($data);
        }

        $user = User::where($loginField, $loginValue)->where('status', 1)->first();

        if($user){
            if($user->password === md5($password)){

                unset($user->password);

                $data['message'] = 'data get successfully';
                $data['data'] = $user;
                $data['status'] = 200;

            }else{

                $data['message'] = 'Password is not correct';
                $data['data'] = [];
                $data['status'] = 401;

            }

        }else{

            $data['message'] = $email ? 'Email not found' : 'Phone number not found';
            $data['data'] = [];
            $data['status'] = 404;
        }

        return Response::json($data);

    }


    /*register api*/

     public function register(Request $req)
    {

        $user = new User;

        $name = $req->input('name');
        $phone = $req->input('phone');
        $password = trim($req->input('password'));

        $email = $req->input('email');

        $check = DB::select("select * from users where phone = '$phone' and status=1");

        if(empty($check)){

            //make user

            $user->name = $name;
            $user->phone = $phone;
            $user->password = md5($password);

            $user->email = $email;

            $save = $user->save();

            $userid = $user->id;

            if($save){

                $check_all = DB::select("select * from users where id = '$userid'");

                $userdata = $check_all[0];
                unset($userdata->password);

                $data['message'] = 'Data saved successfully';
                $data['data'] = $userdata;
                $data['status'] = 200;

            }else{

                $data['message'] = 'Data not saved';
                $data['data'] = [];
                $data['status'] = 204;
            }
        }else{

            $data['message'] = 'Phone number is register already';
            $data['data'] = [];
            $data['status'] = 204;
        }

        return Response::json($data);
    }

    public function getProfile(Request $req){

        $userid = $req->input('userid');

        //update status

        $check = DB::select("select * from users where id = '$userid' and status=1");

        if(!empty($check)){

                $user = $check[0];
                if (isset($user->password)) {
                    unset($user->password);
                }

                $data['message'] = 'data get successfully';
                $data['data'] = $user;
                $data['status'] = 200;

        }else{

            $data['message'] = 'User not found';
            $data['data'] = [];
            $data['status'] = 404;
        }

        return Response::json($data);

    }

    public function updateProfile(Request $req){

        $userid = $req->input('userid');

        if (!$userid) {
            $data['message'] = 'User ID is required';
            $data['data'] = [];
            $data['status'] = 400;
            return Response::json($data);
        }

        $mdata = User::find($userid);

        if (!$mdata) {
            $data['message'] = 'User not found';
            $data['data'] = [];
            $data['status'] = 404;
            return Response::json($data);
        }

        if ($req->has('name') && $req->input('name')) {
            $mdata->name = $req->input('name');
        }
        if ($req->has('phone') && $req->input('phone')) {
            $mdata->phone = $req->input('phone');
        }
        if ($req->has('email') && $req->input('email')) {
            $mdata->email = $req->input('email');
        }
        if ($req->has('address') && $req->input('address')) {
            $mdata->address = $req->input('address');
        }
        if ($req->has('password') && $req->input('password')) {
            $mdata->password = md5(trim($req->input('password')));
        }

        $save = $mdata->save();

        if($save){

            $data['message'] = 'Profile data updated successfully';
            $data['data'] = [];
            $data['status'] = 200;

        }else{

            $data['message'] = 'Profile not updated';
            $data['data'] = [];
            $data['status'] = 204;
        }

        return Response::json($data);
    }


     public function getAddress(Request $req){

        $userid = $req->input('userid');

        if (!$userid) {
            $data['message'] = 'User ID is required';
            $data['status'] = 400;
            return Response::json($data);
        }

        // Get address list for the user
        $addresses = Address::where('userid', $userid)->get();

        if(!empty($addresses)){
            $data['message'] = 'Address list retrieved successfully';
            $data['data'] = $addresses;
            $data['status'] = 200;
        }else{
            $data['message'] = 'No addresses found for this user';
            $data['status'] = 204;
        }

        return Response::json($data);

    }

    public function getAddressById(Request $req){

        $addressid = $req->input('address_id');

        if (!$addressid) {
            $data['message'] = 'Address ID is required';
            $data['status'] = 400;
            return Response::json($data);
        }

        // Get address details by ID
        $address = DB::select("select * from addresses where id = '$addressid'");

        if(!empty($address)){
            $data['message'] = 'Address details retrieved successfully';
            $data['data'] = $address[0];
            $data['status'] = 200;
        }else{
            $data['message'] = 'Address not found';
            $data['status'] = 404;
        }

        return Response::json($data);

    }


      public function add_address(Request $req){

        $address_model = new Address;

        $address = $req->input('address');
        $pincode = $req->input('pincode');
        $city = $req->input('city');
        $state = $req->input('state');
        $user_id = $req->input('user_id');
        $phone = $req->input('phone');

        $address_model->address = $req->input('address');
        $address_model->phone = $req->input('phone');
        $address_model->pincode = $req->input('pincode');
        $address_model->city = $req->input('city');
        $address_model->state = $req->input('state');
        $address_model->userid = $req->input('user_id');
        $save = $address_model->save();

        if($save){

                $response['message'] = 'address saved successfully';
                $response['data'] = [];
                $response['status'] = 200;
        }else{

                $response['message'] = 'Opps address not saved';
                $response['data'] = [];
                $response['status'] = 204;

        }

        return Response::json($response);
    }

    public function update_address(Request $req){

        $address_id = $req->input('address_id');

        $data = Address::find($address_id);

        $address = $req->input('address');
        $pincode = $req->input('pincode');
        $city = $req->input('city');
        $state = $req->input('state');
        $phone = $req->input('phone');

        if($data){

            $data->pincode = $pincode;
            $data->address = $address;
            $data->city = $city;
            $data->state = $state;
            $data->phone = $phone;

            $save = $data->save();

            if($save){

                $response['message'] = 'address updated successfully';
                $response['data'] = [];
                $response['status'] = 200;
            }else{

                $response['message'] = 'Opps address not updated';
                $response['data'] = [];
                $response['status'] = 204;

            }

        }else{

            $response['message'] = 'Opps address not updated';
            $response['data'] = [];
            $response['status'] = 204;

        }

        return Response::json($response);
    }


    public function get_bills(Request $req){

        $userid = $req->input('userid');

        if (!$userid) {
            $data['message'] = 'User ID is required';
            $data['data'] = [];
            $data['status'] = 400;
            return Response::json($data);
        }

        // Query bills by user
        $bills = DB::table('bills')
            ->where('userid', $userid)
            ->orderByDesc(DB::raw("CONCAT(YEAR(created_at), LPAD(MONTH(created_at), 2, '0'))"))
            ->orderByDesc('created_at')
            ->get();

        if($bills->isEmpty()) {
            $data['message'] = 'No bills found for this user';
            $data['data'] = [];
            $data['status'] = 204;
            return Response::json($data);
        }

        // Group by month-year from created_at or bill date column
        $grouped = [];
        foreach ($bills as $bill) {
            $date = isset($bill->created_at) ? $bill->created_at : null;
            if (!$date) {
                $date = now();
            }
            $dt = \Carbon\Carbon::parse($date);
            $key = $dt->format('Y-m');

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'month' => $dt->format('F'),
                    'year' => $dt->format('Y'),
                    'total' => 0,
                    'bills' => []
                ];
            }
            $grouped[$key]['bills'][] = $bill;

            // Sum total_price for this group, fallback to amount or total
            if (isset($bill->total_price)) {
                $grouped[$key]['total'] += floatval($bill->total_price);
            } elseif (isset($bill->amount)) {
                $grouped[$key]['total'] += floatval($bill->amount);
            } elseif (isset($bill->total)) {
                $grouped[$key]['total'] += floatval($bill->total);
            }
        }

        // Keep descending order of keys
        krsort($grouped);

        $data['message'] = 'Bills retrieved successfully';
        $data['data'] = array_values($grouped); // return as array of month groups
        $data['status'] = 200;

        return Response::json($data);
    }


    public function save_bills(Request $req){

        $userid = $req->input('userid');
        $merchant_name = $req->input('merchant_name');
        $total_amount = $req->input('total_amount');

        $gstnumber = $req->input('gstnumber');
        $bill_number = $req->input('bill_number');
        $cgst = $req->input('cgst');
        $igst = $req->input('igst');
        $phone = $req->input('phone');
        $email = $req->input('email');
        
        $bill_date = $req->input('bill_date');
        
        $gross_amount = $req->input('gross_amount');
        $order_number = $req->input('order_number');

        if (!$userid) {
            $data['message'] = 'User ID, amount and bill details are required';
            $data['data'] = [];
            $data['status'] = 400;
            return Response::json($data);
        }

        // Save bill to database
        try {
            DB::table('bills')->insert([
                'userid' => $userid,
                'gstnumber' => $gstnumber,
                'bill_number' => $bill_number,
                'cgst' => $cgst,
                'igst' => $igst,
                'phone' => $phone,
                'email' => $email,
                'merchant_name' => $merchant_name,
                'bill_date' => $bill_date,
                'total_amount' => $total_amount,
                'gross_amount' => $gross_amount,
                'order_number' => $order_number,
                'created_at' => now()
            ]);

            $data['message'] = 'Bill saved successfully';
            $data['data'] = [];
            $data['status'] = 200;
        } catch (\Exception $e) {
            $data['message'] = 'Error saving bill: ' . $e->getMessage();
            $data['data'] = [];
            $data['status'] = 500;
        }

        return Response::json($data);
    }


    

}
