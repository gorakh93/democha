<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

use App\Models\User;


class apiController extends Controller
{
    //


    function login(Request $req){

        $phone = $req->input('phone');
        $email = $req->input('email');
        $password = $req->input('password');

        $loginField = $email ? 'email' : 'phone';
        $loginValue = $email ?: $phone;

        if (!$loginValue) {
            $data['message'] = 'Phone or email is required';
            $data['data'] = [];
            $data['status'] = 400;
            return Response::json($data);
        }

        $check = DB::select("select * from users where $loginField = '$loginValue' and status=1");

        if(!empty($check)){
            $password = md5($password);

            $check_all = DB::select("select * from users where $loginField = '$loginValue' and password = '$password' and status=1");

            if(!empty($check_all)){

                $user = $check_all[0];
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
        $password = $req->input('password');

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

}
