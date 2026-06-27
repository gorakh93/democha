<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use DateTime;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

use App\Models\User;
use App\Models\Address;
use Google\Cloud\DocumentAI\V1\Client\DocumentProcessorServiceClient;
use Google\Cloud\DocumentAI\V1\ProcessRequest;
use Google\Cloud\DocumentAI\V1\RawDocument;


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
            $data['data'] = (object) [];
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
                $data['data'] = (object)[];
                $data['status'] = 401;

            }

        }else{

            $data['message'] = $email ? 'Email not found' : 'Phone number not found';
            $data['data'] = (object)[];
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
                $data['data'] = (object)[];
                $data['status'] = 204;
            }
        }else{

            $data['message'] = 'Phone number is register already';
            $data['data'] = (object)[];
            $data['status'] = 204;
        }

        return Response::json($data);
    }

    public function getProfile(Request $req){

        $userid = $req->input('userid');



         $billCount = DB::table('bills')->where('userid', $userid)->count();
                $gstSum = DB::table('bills')
                    ->where('userid', $userid)
                    ->selectRaw('COALESCE(SUM(COALESCE(cgst, 0) + COALESCE(sgst, 0) + COALESCE(igst, 0)), 0) as gst')
                    ->first();

        $total_gst = $gstSum ? floatval($gstSum->gst) : 0;
        
        DB::table('users')
                    ->where('id', $userid)
                    ->update([
                        'scan_bill' => $billCount,
                        'tax_identified' => $total_gst,
                    ]);

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
            $data['data'] = (object)[];
            $data['status'] = 404;
        }

        return Response::json($data);

    }

    public function updateProfile(Request $req){

        $userid = $req->input('userid');

        if (!$userid) {
            $data['message'] = 'User ID is required';
            $data['data'] = (object)[];
            $data['status'] = 400;
            return Response::json($data);
        }

        $mdata = User::find($userid);

        if (!$mdata) {
            $data['message'] = 'User not found';
            $data['data'] = (object)[];
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
            $data['data'] = (object)[];
            $data['status'] = 200;

        }else{

            $data['message'] = 'Profile not updated';
            $data['data'] = (object)[];
            $data['status'] = 204;
        }

        return Response::json($data);
    }


     public function getAddress(Request $req){

        $userid = $req->input('userid');

        if (!$userid) {
            $data['message'] = 'User ID is required';
            $data['data'] = (object)[];
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
                $response['data'] = (object)[];
                $response['status'] = 200;
        }else{

                $response['message'] = 'Opps address not saved';
                $response['data'] = (object)[];
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
                $response['data'] = (object)[];
                $response['status'] = 200;
            }else{

                $response['message'] = 'Opps address not updated';
                $response['data'] = (object)[];
                $response['status'] = 204;

            }

        }else{

            $response['message'] = 'Opps address not updated';
            $response['data'] = (object)[];
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
            ->orderByDesc(DB::raw("CONCAT(YEAR(bill_date), LPAD(MONTH(bill_date), 2, '0'))"))
            ->orderByDesc('created_at')
            ->get();

        if($bills->isEmpty()) {
            $data['message'] = 'No bills found for this user';
            $data['data'] = (object) [];
            $data['status'] = 204;
            return Response::json($data);
        }

        // Group by month-year from created_at or bill date column
        $grouped = [];
        foreach ($bills as $bill) {
            $date = isset($bill->bill_date) ? $bill->bill_date : null;
            if (!$date) {
                continue; // Skip if bill_date is not set
            }
            $dt = \Carbon\Carbon::parse($date);
            $key = $dt->format('Y-m');

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'month' => $dt->format('F'),
                    'year' => $dt->format('Y'),
                    'total' => 0,
                    'total_gst' => 0,
                    'bills' => []
                ];
            }
            

            $grouped[$key]['category'] = 'utilities';

            // Sum total_price for this group, fallback to amount or total
            if (isset($bill->total_amount)) {
                $grouped[$key]['total'] += floatval($bill->total_amount);
            }

            // Sum total_gst (cgst + sgst + igst) for this group
            $cgst = $bill->cgst !== null ? floatval($bill->cgst) : 0;
            $sgst = $bill->sgst !== null ? floatval($bill->sgst) : 0;
            $igst = $bill->igst !== null ? floatval($bill->igst) : 0;

            $bill->cgst = $cgst;
            $bill->sgst = $sgst;
            $bill->igst = $igst;

            $gst = ($cgst + $sgst + $igst);
            $bill->gst = $gst;

            $grouped[$key]['bills'][] = $bill;
            $grouped[$key]['total_gst'] += $gst;

            
        }

        // Keep descending order of keys
        krsort($grouped);

        $data['message'] = 'Bills retrieved successfully';
        $data['data'] = array_values($grouped); // return as array of month groups
        $data['status'] = 200;
        $data['base_url'] = url('/storage/');
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
        $sgst = $req->input('sgst');
        $phone = $req->input('phone');
        $email = $req->input('email');
        
        $bill_date = $req->input('bill_date');
        
        $gross_amount = $req->input('gross_amount');
        $order_number = $req->input('order_number');

        $cgst = ($cgst !== null && $cgst !== '') ? $cgst : 0;
        $sgst = ($sgst !== null && $sgst !== '') ? $sgst : 0;
        $igst = ($igst !== null && $igst !== '') ? $igst : 0;

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
                'cgst' => $cgst !== null && $cgst !== '' ? floatval($cgst) : 0,
                'igst' => $igst !== null && $igst !== '' ? floatval($igst) : 0,
                'sgst' => $sgst !== null && $sgst !== '' ? floatval($sgst) : 0,
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


    public function profileImageUpload(Request $req) {

        $base64_str = $req->input('imgstr');
        $userid = $req->input('userid');

        //decode base64 string

         if($base64_str != ''){
            $image = base64_decode($base64_str);

            $imageName = uniqid().'.'.'png';
            $resp = Storage::disk('public')->put('profile_pic/'.$imageName, $image);

            $profilePic = 'profile_pic/'.$imageName;
            
            User::where('id', $userid)->update(['profilePic' => $profilePic]);
        }

        $response['message'] = 'image uploaded successfully';
        $response['data'] = $profilePic;
        $response['status'] = 200;

        return Response::json($response);
    }

    public function billFileUpload(Request $req) {

        $base64_str = $req->input('filestr');
        $userid = $req->input('userid');

        //decode base64 string

         if($base64_str != ''){
            $file = base64_decode($base64_str);

            $fileName = uniqid().'.'.'pdf';
            $resp = Storage::disk('public')->put('bill_files/'.$fileName, $file);

            $bill_file = 'bill_files/'.$fileName;

            $billId = $this->getInvoiceInfo($userid, $bill_file);

        }

        $response['message'] = 'file uploaded and bill data extracted successfully';
        $response['data'] = ['bill_file' => $bill_file, 'bill_id' => $billId];
        $response['status'] = 200;

        return Response::json($response);
    }

    public function getInvoiceInfo($userid,$filePath){

        try {
            // Get file content from storage
            $content = Storage::disk('public')->get($filePath);

            // Create client
            //$client = new DocumentProcessorServiceClient();

                    $client = new DocumentProcessorServiceClient([
    'credentials' => storage_path('spendit.json')
]);

            $projectId = env('GOOGLE_CLOUD_PROJECT_ID', 'spendit-document-scan');
            $location = env('DOCUMENT_AI_LOCATION', 'us');
            $processorId = env('DOCUMENT_AI_PROCESSOR_ID', 'b7b554e1c7bb3c21');
            $processorName = $client->processorName($projectId, $location, $processorId);

            // Create raw document
            $rawDocument = new RawDocument();
            $rawDocument->setContent($content);
            $rawDocument->setMimeType('application/pdf');

            // Create process request
            $request = new ProcessRequest();
            $request->setName($processorName);
            $request->setRawDocument($rawDocument);

            // Process the document
            $response = $client->processDocument($request);
            $document = $response->getDocument();

            // Initialize extracted data
            $extracted = [
                'order_number' => null,
                'phone' => null,
                'gstnumber' => null,
                'cgst' => null,
                'igst' => null,
                'bill_number' => null,
                'sub_total' => null,
                'total_amount' => null,
                'bill_date' => null,
            ];
            
            //print_r($document->getEntities());die;

            $entities = $document->getEntities(); // This is a RepeatedField object

            $invoice_no_arr = ['invoice_id', 'bill_number'];
            $invoice_date_arr = ['invoice_date', 'date'];
            $total_amt_arr = ['total_amount', 'total'];

            $net_amt_arr = ['net_amount', 'subtotal', 'sub_total'];
            $gst_no_arr = ['supplier_tax_id', 'gst_number', 'gst'];
            $phone_arr = ['supplier_phone', 'phone', 'mobile'];

            $order_no_arr = ['order', 'purchase_order'];
            $cgst_arr = ['cgst', 'total_tax_amount'];
            $igst_arr = ['igst', 'total_tax_amount'];
            $sgst_arr = ['sgst', 'total_tax_amount'];

            $merchant_arr = ['supplier_name', 'merchant_name'];
             
            $bill_number = $bill_date = $total_amount = $sub_total = 
            $gstnumber = $phone = $order_number = $cgst = $igst = $sgst = $merchant_name = '';

            //print_r($entities);die;
            
            $invoiceData = [];
            foreach ($entities as $entity) {
                $invoiceData[$entity->getType()] = $entity->getMentionText();
            }
            
            $txt = json_encode($invoiceData);

             // Insert into bills logs table
            $logid = DB::table('bills_logs')->insertGetId([
                'userid' => $userid,
                'entity_txt' => $txt
            ]);

            foreach ($entities as $entity) {

                $type = strtolower($entity->getType());

                if (in_array($type, $invoice_no_arr)) {
                    $bill_number = $entity->getMentionText();
                }

                 if (in_array($type, $invoice_date_arr)) {
                    $bill_date = $entity->getMentionText();
                 }

                    if (in_array($type, $total_amt_arr)) {
                        $total_amount = $entity->getMentionText();
                    }

                    if (in_array($type, $net_amt_arr)) {
                        $sub_total = $entity->getMentionText();
                    }

                    if (in_array($type, $gst_no_arr)) {
                        $gstnumber = $entity->getMentionText();
                    }

                    if (in_array($type, $phone_arr)) {
                        $phone = $entity->getMentionText();
                    }

                    if (in_array($type, $order_no_arr)) {
                        $order_number = $entity->getMentionText();
                    }

                    if (in_array($type, $cgst_arr)) {
                        $cgst = $entity->getMentionText();
                    }

                    if (in_array($type, $igst_arr)) {
                        $igst = $entity->getMentionText();
                    }

                    if (in_array($type, $sgst_arr)) {
                        $sgst = $entity->getMentionText();
                    }

                    if (in_array($type, $merchant_arr)) {
                        $merchant_name = $entity->getMentionText();
                     }

            }

            // Extract data from entities
            // foreach ($document->getEntities() as $entity) {
            //     $type = strtolower($entity->getType());
            //     $text = $entity->getMentionText();

            //     if ($type == 'invoice_id' || strpos($type, 'bill_number') !== false) {
            //         $extracted['bill_number'] = $text;
            //     } elseif ($type == 'invoice_date' || strpos($type, 'date') !== false) {
            //         $extracted['bill_date'] = $text;
            //     } elseif ($type == 'total_amount' || $type == 'total') {
            //         $extracted['total_amount'] = $text;
            //     } elseif ($type == 'net_amount' || strpos($type, 'subtotal') !== false || $type == 'sub_total') {
            //         $extracted['sub_total'] = $text;
            //     } elseif ($type == 'supplier_tax_id' || strpos($type, 'gst') !== false) {
            //         $extracted['gstnumber'] = $text;
            //     } elseif ($type == 'supplier_phone' || strpos($type, 'phone') !== false || strpos($type, 'mobile') !== false) {
            //         $extracted['phone'] = $text;
            //     } elseif (strpos($type, 'order') !== false || $type == 'purchase_order') {
            //         $extracted['order_number'] = $text;
            //     } elseif (strpos($type, 'cgst') !== false || strpos($type, 'total_tax_amount') !== false) {
            //         $extracted['cgst'] = $text;
            //     } elseif (strpos($type, 'igst') !== false || strpos($type, 'total_tax_amount') !== false) {
            //         $extracted['igst'] = $text;
            //     }elseif (strpos($type, 'supplier_name') !== false) {
            //         $extracted['merchant_name'] = $text;
            //     }
            // }

            // Clean phone number
            if ($phone) {
                $phone = str_replace('+91', '', $phone);
                $phone = trim($phone);
            }

            // Parse bill_date if present
            $billDate = null;
            if ($bill_date) {
                try {
                $bill_date = DateTime::createFromFormat('d/m/y', $bill_date);
                $billDate = $bill_date->format('Y-m-d'); 

                    //$billDate = \Carbon\Carbon::parse($bill_date)->format('Y-m-d');
                } catch (\Exception $e) {
                    $billDate = null;
                }
            }

            // Determine processing status
            $isProcess = (is_null($gstnumber) || is_null($bill_number) || is_null($billDate)) ? 1 : 0;

            if ($gstnumber && !$this->isValidGST($gstnumber)) {
                $isProcess = 1; // Mark as needs processing if GST number is invalid
            }


            $merchant_type = 'utilities';
            
            // Insert into bills table
            $billId = DB::table('bills')->insertGetId([
                'merchant_type' => $merchant_type,
                'userid' => $userid,
                'gstnumber' => $gstnumber,
                'bill_number' => $bill_number,
                'cgst' => $cgst ? floatval($cgst) : null,
                'igst' => $igst ? floatval($igst) : null,
                'sgst' => $sgst ? floatval($sgst) : null,
                'phone' => $phone,
                'merchant_name' => $merchant_name,
                'bill_date' => $billDate,
                'total_amount' => $total_amount ? floatval($total_amount) : null,
                'gross_amount' => $sub_total ? floatval($sub_total) : null,
                'order_number' => $order_number,
                'bill_file' => $filePath,
                'is_process' => $isProcess,
                'created_at' => now()
            ]);

            return $billId;

        } catch (\Exception $e) {

            print_r($e);die;
            // If extraction fails, still save with minimal data
            // $billId = DB::table('bills')->insertGetId([
            //     'userid' => $userid,
            //     'bill_file' => $filePath,
            //     'is_process' => 1,
            //     'created_at' => now()
            // ]);

            //return $billId;
        }



    }

    public function isValidGST($gst) {
       // Regex for GSTIN format
        $pattern = "/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/";

        if (preg_match($pattern, $gst)) {
            return true;
        } else {
            return false;
        }
    }


    public function getGstNo(Request $req){

        $filePath = $req->input('file_path', 'bill_files/69dbefebbce8b.pdf');

        //echo $filePath;die;

        try {
            // Get file content from storage
            $content = Storage::disk('public')->get($filePath);

            // Create client
            $client = new DocumentProcessorServiceClient();
            $projectId = env('GOOGLE_CLOUD_PROJECT_ID', 'your-project-id');
            $location = env('DOCUMENT_AI_LOCATION', 'us');
            $processorId = env('DOCUMENT_AI_PROCESSOR_ID', 'your-processor-id');
            $processorName = $client->processorName($projectId, $location, $processorId);

            // Create raw document
            $rawDocument = new RawDocument();
            $rawDocument->setContent($content);
            $rawDocument->setMimeType('application/pdf');

            // Create process request
            $request = new ProcessRequest();
            $request->setName($processorName);
            $request->setRawDocument($rawDocument);

            // Process the document
            $response = $client->processDocument($request);
            $document = $response->getDocument();

            // Extract GST number - assuming it's in the entities
            $gstNumber = null;
            foreach ($document->getEntities() as $entity) {
                if ($entity->getType() == 'supplier_tax_id' || $entity->getType() == 'gst_number' || strpos(strtolower($entity->getType()), 'gst') !== false) {
                    $gstNumber = $entity->getMentionText();
                    break;
                }
            }

            if ($gstNumber) {
                $data['message'] = 'GST number extracted successfully';
                $data['data'] = ['gst_number' => $gstNumber];
                $data['status'] = 200;
            } else {
                $data['message'] = 'GST number not found';
                $data['data'] = [];
                $data['status'] = 204;
            }
        } catch (\Exception $e) {
            $data['message'] = 'Error processing document: ' . $e->getMessage();
            $data['data'] = [];
            $data['status'] = 500;
        }

        return Response::json($data);
    }

     public function HomePageGraphData(Request $req){
      
        $userid = $req->input('userid');
    
            if (!$userid) {
                $data['message'] = 'User ID is required';
                $data['data'] = [];
                $data['status'] = 400;
                return Response::json($data);
            }
    
            // Query bills by user and group by year/month
            $bills = DB::table('bills')
                ->select(
                    DB::raw("YEAR(bill_date) as year"),
                    DB::raw("MONTH(bill_date) as month"),
                    DB::raw("SUM(total_amount) as total"),
                    DB::raw("SUM(COALESCE(cgst, 0) + COALESCE(igst, 0) + COALESCE(sgst, 0)) as gst")
                )
                ->where('userid', $userid)
                ->whereNotNull('bill_date')
                ->groupBy(DB::raw("YEAR(bill_date)"), DB::raw("MONTH(bill_date)"))
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->get();

            if($bills->isEmpty()) {
                $data['message'] = 'No bills found for this user';
                $data['data'] = (object)[];
                $data['status'] = 204;
                return Response::json($data);
            }

            // Format data for graph
            $graphData = [];
            foreach ($bills as $bill) {
                $graphData[] = [
                    'year' => $bill->year,
                    'month' => $bill->month,
                    'total' => floatval($bill->total),
                    'gst' => floatval($bill->gst)
                ];
            }

            // Total spendings and GST for the current month
            $currentYear = date('Y');
            $currentMonth = date('m');
            $currentTotals = DB::table('bills')
                ->select(
                    DB::raw('COALESCE(SUM(total_amount), 0) as total'),
                    DB::raw('COALESCE(SUM(COALESCE(cgst, 0) + COALESCE(sgst, 0) + COALESCE(igst, 0)), 0) as gst'),
                    DB::raw('COALESCE(SUM(COALESCE(cgst, 0)), 0) as cgst'),
                    DB::raw('COALESCE(SUM(COALESCE(sgst, 0)), 0) as sgst'),
                    DB::raw('COALESCE(SUM(COALESCE(igst, 0)), 0) as igst')
                )
                ->where('userid', $userid)
                ->whereNotNull('bill_date')
                ->whereRaw('YEAR(bill_date) = ?', [$currentYear])
                ->whereRaw('MONTH(bill_date) = ?', [$currentMonth])
                ->first();

            $currentMonthTotals = [
                'total_spendings' => floatval($currentTotals->total),
                'total_gst' => floatval($currentTotals->gst),

                'total_savings' => 319,
                'year' => (int) $currentYear,
                'month' => (int) $currentMonth,
            ];


             $gstComp = [
                'cgst' => floatval($currentTotals->cgst),
                'sgst' => floatval($currentTotals->sgst),
                'igst' => floatval($currentTotals->igst)
            ];

            // calculate total amount and total GST categorized by merchant type
            $merchantTypeTotals = DB::table('bills')
                ->select(
                    'merchant_type',
                    DB::raw('COALESCE(SUM(total_amount), 0) as total_amount'),
                    DB::raw('COALESCE(SUM(COALESCE(cgst, 0) + COALESCE(sgst, 0) + COALESCE(igst, 0)), 0) as total_gst')
                )
                ->where('userid', $userid)
                ->whereNotNull('bill_date')
                ->groupBy('merchant_type')
                ->get()
                ->map(function ($row) {
                    return [
                        'merchant_type' => $row->merchant_type,
                        'total_amount' => floatval($row->total_amount),
                        'total_gst' => floatval($row->total_gst),
                        'tax_amount' => floatval($row->total_gst),
                    ];
                })
                ->toArray();

            $result = [
                'graph' => $graphData,
                'current_month' => $currentMonthTotals,
                'gst_components' => $gstComp,
                'category_breakdown' => $merchantTypeTotals
            ];
    
            $data['message'] = 'Graph data retrieved successfully';
            $data['data'] = $result;
            $data['status'] = 200;
            return Response::json($data);
     }


     public function BillBreakDown(Request $req){

        $userid = $req->input('userid');
        $month = $req->input('month');
        $year = $req->input('year');

        if (!$userid || !$month || !$year) {
            $data['message'] = 'User ID, month and year are required';
            $data['data'] = (object)[];
            $data['status'] = 400;
            return Response::json($data);
        }

        // Query bills by user and filter by month/year
        $bills = DB::table('bills')
            ->select(
                DB::raw("SUM(total_amount) as total"),
                DB::raw("SUM(COALESCE(gross_amount, 0)) as subtotal"),
                DB::raw("SUM(COALESCE(cgst, 0)) as cgst"),
                DB::raw("SUM(COALESCE(igst, 0)) as igst")
            )
            ->where('userid', $userid)
            ->whereRaw("YEAR(bill_date) = ?", [$year])
            ->whereRaw("MONTH(bill_date) = ?", [$month])
            ->first();

        if(!$bills || ($bills->total === null && $bills->subtotal === null)) {
            $data['message'] = 'No bills found for this month';
            $data['data'] = (object) [];
            $data['status'] = 204;
            return Response::json($data);
        }

        $response['message'] = 'Bill breakdown retrieved successfully';
        $response['data'] = [
            'total' => floatval($bills->total),
            'subtotal' => floatval($bills->subtotal),
            'cgst' => floatval($bills->cgst),
            'igst' => floatval($bills->igst)
        ];
        $response['status'] = 200;

        return Response::json($response);
     }

    public function getProductImage()
    {
        // The path relative to storage/app/public/
        $relativePath = 'offers/offer1.jpeg';

        // Check if the file actually exists
        if (Storage::disk('public')->exists($relativePath)) {
            
            // Generate the full absolute URL
            $imageUrl = Storage::disk('public')->url($relativePath);
            // Alternatively, you can use: $imageUrl = asset('storage/' . $relativePath);

            return response()->json([
                'success' => true,
                'message' => 'Image retrieved successfully.',
                'image_url' => $imageUrl
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Image not found.'
        ], 404);
    }


    public function saving_offer(Request $req){
        // Return all active saving offers
        $offers = DB::table('saving_offer')
            ->where('status', 1)
            ->get();

        if ($offers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active offers found.',
                'data' => (object) []
            ], 204);
        }

        $result = [];
        foreach ($offers as $offer) {
            $offer = (array) $offer;

            if (!empty($offer['image']) && Storage::disk('public')->exists($offer['image'])) {
                $offer['image_url'] = url('storage/' . ltrim($offer['image'], '/'));
            } else {
                $offer['image_url'] = null;
            }

            $result[] = $offer;
        }


        $total_amount = DB::table('bills')
            ->where('userid', $req->input('userid'))
            ->sum('total_amount');


        return response()->json([
            'success' => true,
            'message' => 'Active offers retrieved successfully.',
            'data' => $result,
            'total_amount' => $total_amount
        ], 200);
    }
    public function offerImageUpload(Request $req) {

        $base64_str = $req->input('imgstr');

        $offerPic = '';

        //decode base64 string

         if($base64_str != ''){
            $image = base64_decode($base64_str);

            $imageName = uniqid().'.'.'png';
            $resp = Storage::disk('public')->put('offers/'.$imageName, $image);

            $offerPic = 'offers/'.$imageName;
        }

        $response['message'] = 'image uploaded successfully';
        $response['data'] = $offerPic;
        $response['status'] = 200;

        return Response::json($response);
    }

    public function generateMonthlyBillsPDF(Request $req){

        $userid = $req->input('userid');
        $month = $req->input('month', null);
        $year = $req->input('year', date('Y'));

        if (!$userid) {
            $data['message'] = 'User ID is required';
            $data['data'] = (object) [];
            $data['status'] = 400;
            return Response::json($data);
        }

        // If month is not provided or set to 'all', generate for all months from Jan until current month
        if (is_null($month) || $month === 'all' || $month === 0) {
            $endMonth = (int) date('m');
            $months = range(1, $endMonth);
        } else {
            $months = [(int)$month];
        }

        $generated = [];
        $skipped = [];

        try {
            foreach ($months as $m) {
                // Query bills for the specific month and year
                $bills = DB::table('bills')
                    ->where('userid', $userid)
                    ->whereRaw("YEAR(bill_date) = ?", [$year])
                    ->whereRaw("MONTH(bill_date) = ?", [$m])
                    ->orderBy('bill_date', 'desc')
                    ->get();

                if($bills->isEmpty()) {
                    $skipped[] = $m;
                    continue;
                }

                // Generate PDF filename
                $fileName = 'bills_' . $userid . '_' . $year . '_' . str_pad($m, 2, '0', STR_PAD_LEFT) . '_' . uniqid() . '.pdf';
                $filePath = 'monthly_bills_pdf/' . $fileName;

                // Create PDF content as HTML
                $htmlContent = $this->generateBillsPDFContent($bills, $year, $m);

                // Convert HTML to PDF using DomPDF
                $pdf = \PDF::loadHTML($htmlContent);
                $pdfContent = $pdf->output();
                
                // Save PDF to storage
                Storage::disk('public')->put($filePath, $pdfContent);

                // Check if record already exists for this month/year
                $existing = DB::table('monthly_bills_pdf')
                    ->where('userid', $userid)
                    ->where('month', $m)
                    ->where('year', $year)
                    ->first();

                if($existing) {
                    // Delete old PDF
                    if (Storage::disk('public')->exists($existing->bills_pdf)) {
                        Storage::disk('public')->delete($existing->bills_pdf);
                    }
                    // Update record
                    DB::table('monthly_bills_pdf')
                        ->where('id', $existing->id)
                        ->update(['bills_pdf' => $filePath, 'updated_at' => now()]);
                    $pdfId = $existing->id;
                } else {
                    // Insert new record
                    $pdfId = DB::table('monthly_bills_pdf')->insertGetId([
                        'userid' => $userid,
                        'month' => $m,
                        'year' => $year,
                        'bills_pdf' => $filePath,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                $generated[] = [
                    'pdf_id' => $pdfId,
                    'pdf_url' => url('storage/' . $filePath),
                    'file_path' => $filePath,
                    'month' => $m,
                    'month_name' => \Carbon\Carbon::createFromFormat('Y-m-d', $year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT) . '-01')->format('F'),
                    'year' => $year
                ];
            }

            if (empty($generated)) {
                $data['message'] = 'No bills found for the requested months';
                $data['data'] = (object)[];
                $data['status'] = 204;
                return Response::json($data);
            }

            $data['message'] = 'Monthly bills PDFs generated successfully';
            $data['data'] = ['generated' => $generated, 'skipped_months' => $skipped];
            $data['status'] = 200;

        } catch (\Exception $e) {
            $data['message'] = 'Error generating PDF: ' . $e->getMessage();
            $data['data'] = (object)[];
            $data['status'] = 500;
        }

        return Response::json($data);
    }

    private function generateBillsPDFContent($bills, $year, $month) {
        $monthName = \Carbon\Carbon::createFromFormat('Y-m-d', "$year-$month-01")->format('F');
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                h2 { color: #333; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th { background-color: #007bff; color: white; padding: 10px; text-align: left; }
                td { border: 1px solid #ddd; padding: 8px; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                .total-row { background-color: #e9ecef; font-weight: bold; }
                .header { margin-bottom: 10px; }
                .currency { text-align: right; }
            </style>
        </head>
        <body>
            <h2>Monthly Bills Report</h2>
            <div class="header">
                <p><strong>Month:</strong> ' . $monthName . ', ' . $year . '</p>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Bill Date</th>
                        <th>Bill Number</th>
                        <th>Merchant</th>
                        <th class="currency">Amount</th>
                        <th class="currency">CGST</th>
                        <th class="currency">IGST</th>
                        <th class="currency">Total</th>
                    </tr>
                </thead>
                <tbody>';

        $totalAmount = 0;
        $totalCGST = 0;
        $totalIGST = 0;
        $totalGross = 0;

        foreach ($bills as $bill) {
            $billDate = isset($bill->bill_date) ? $bill->bill_date : 'N/A';
            $billNumber = $bill->bill_number ?? 'N/A';
            $merchant = $bill->merchant_name ?? 'N/A';
            $amount = floatval($bill->total_amount ?? 0);
            $cgst = floatval($bill->cgst ?? 0);
            $igst = floatval($bill->igst ?? 0);
            $gross = floatval($bill->gross_amount ?? 0);

            $totalAmount += $amount;
            $totalCGST += $cgst;
            $totalIGST += $igst;
            $totalGross += $gross;

            $html .= '
                    <tr>
                        <td>' . $billDate . '</td>
                        <td>' . $billNumber . '</td>
                        <td>' . $merchant . '</td>
                        <td class="currency">₹' . number_format($amount, 2) . '</td>
                        <td class="currency">₹' . number_format($cgst, 2) . '</td>
                        <td class="currency">₹' . number_format($igst, 2) . '</td>
                        <td class="currency">₹' . number_format($gross, 2) . '</td>
                    </tr>';
        }

        $html .= '
                    <tr class="total-row">
                        <td colspan="3">TOTAL</td>
                        <td class="currency">₹' . number_format($totalAmount, 2) . '</td>
                        <td class="currency">₹' . number_format($totalCGST, 2) . '</td>
                        <td class="currency">₹' . number_format($totalIGST, 2) . '</td>
                        <td class="currency">₹' . number_format($totalGross, 2) . '</td>
                    </tr>
                </tbody>
            </table>
        </body>
        </html>';

        return $html;
    }

    public function getMonthlyBillsPDFList(Request $req){

        $userid = $req->input('userid');

        if (!$userid) {
            $data['message'] = 'User ID is required';
            $data['data'] = (object) [];
            $data['status'] = 400;
            return Response::json($data);
        }

        try {
            // Get all monthly bill PDFs for the user
            $pdfList = DB::table('monthly_bills_pdf')
                ->where('userid', $userid)
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->get();

            if($pdfList->isEmpty()) {
                $data['message'] = 'No monthly bills PDFs found for this user';
                $data['data'] = (object) [];
                $data['status'] = 204;
                return Response::json($data);
            }

            $result = [];
            foreach ($pdfList as $pdf) {
                $monthName = \Carbon\Carbon::createFromFormat('Y-m-d', $pdf->year . '-' . str_pad($pdf->month, 2, '0', STR_PAD_LEFT) . '-01')->format('F');
                
                $result[] = [
                    'id' => $pdf->id,
                    'month' => $pdf->month,
                    'month_name' => $monthName,
                    'year' => $pdf->year,
                    'pdf_url' => url('storage/' . $pdf->bills_pdf),
                    'file_path' => $pdf->bills_pdf,
                    'created_at' => $pdf->created_at,
                    'updated_at' => $pdf->updated_at
                ];
            }

            $data['message'] = 'Monthly bills PDFs retrieved successfully';
            $data['data'] = $result;
            $data['status'] = 200;
            $data['base_url'] = url('/storage/');

        } catch (\Exception $e) {
            $data['message'] = 'Error retrieving PDFs: ' . $e->getMessage();
            $data['data'] = [];
            $data['status'] = 500;
        }

        return Response::json($data);
    }


    

}
