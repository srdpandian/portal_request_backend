<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{   
    protected $homeModel;
    protected $loginModel;
    public function __construct()
    {
        $this->loginModel = new \App\Models\LoginModel();
        $this->homeModel = new \App\Models\HomeModel();
    }
    public function login(Request $request){

        $user = $this->loginModel->Check_user_exists($request->wname);
        $response = [];
        if(count($user)>0){
            $ldaprdn = $request->get('wname', TRUE); 
            $ldappass = $request->get('wpwd', TRUE); 	
            $ldaprdn = $ldaprdn.'@comstarauto.com';   
            $ldapconn = ldap_connect("portalad") or die("Could not connect to LDAP server.");
            @$ldapbind = ldap_bind($ldapconn, $ldaprdn, $ldappass);
            if ($ldapbind) { 
                session()->put('uname',$user[0]->cs_emp_username);
                Session::put('empuname',$user[0]->cs_emp_username);
                Session::put('empid', $user[0]->cs_emp_id);
                Session::put('empname',$user[0]->cs_emp_name);
                Session::put('empemail',$user[0]->cs_emp_email);
                Session::put('empdeptid',$user[0]->cs_emp_dept_id);
                Session::put('deptname',$user[0]->cs_emp_dept);
                Session::put('hod',$user[0]->cs_emp_hod);
                Session::put('emp_hod',$user[0]->cs_emp_hod);
                Session::put('emp_ro',$user[0]->cs_emp_ro);
               
                Session::save();
                $response['status'] = "success";
                $response['statuscode'] = "200";
                $response['message'] = "user successfully login";		
                $response['userData'] = $user;
                
            } else { 
                $response['status'] = "failure";
                $response['statuscode'] = "400";
                 $response['message'] = "Password is in-correct";
            }
            Session::put('empuname',$user[0]->cs_emp_username);
            Session::put('empid', $user[0]->cs_emp_id);
            Session::put('empname',$user[0]->cs_emp_name);
            Session::put('empemail',$user[0]->cs_emp_email);
            Session::put('empdeptid',$user[0]->cs_emp_dept_id);
            Session::put('deptname',$user[0]->cs_emp_dept);
            Session::put('hod',$user[0]->cs_emp_hod);
            Session::put('emp_hod',$user[0]->cs_emp_hod);
            Session::put('emp_ro',$user[0]->cs_emp_ro);

            Session::save();
            
            $response['status'] = "success";
            $response['statuscode'] = "200";
            $response['message'] = "user successfully login";		
            $response['userData'] = $user;
        } else {
            $response['status'] = "failure";
            $response['statuscode'] = "400";
            $response['message'] = "please enter the correct username";		

        } 

        return json_encode($response);
        
    }
    public function getMyrequest(Request $request){
        // print_r(Session::get('empuname'));
        // exit;
        // session()->put('user',"pandi");
        $req_type = $request->radiotype;
        $pri      =  $request->priority;
        if($req_type == null &&  $pri == null){
            $qry ='1';
            $data = session()->get('uname');
        }
        $data = session()->get('uname'); 
        $req_type = $request['radiotype'];
        $pri      = $request['priority'];

        $query= $this->homeModel->getMyrecords($req_type,$pri);
        $j=1; 
        $new_array = array();
        foreach($query as $records) {    
            $title = $this->homeModel->getDetails('system_desc','system_desc_master','id='.$records->system_desc_id.'');  
            $emp = $this->homeModel->getDetails1('cs_emp_name','tbl__cs_employee','cs_emp_username = "'.$records->requested_by.'" '); 
            $projdept = $this->homeModel->getprojdept($records->system_desc_id);  
            $dept = $this->homeModel->getRaiseddept($records->requested_by);  
            $sts_name = $this->homeModel->Status_Color($records->status);
            $sts_app = $this->homeModel->status_aaproval($records->id);

            $newdata = [
                "si_no"=>$j, 
                "id" =>$records->id,
                "project_ref_no"=>$records->project_ref_no,
                "system"=>$records->system,
                "title" => $title,
                "dept_name"=>$projdept[0]->dept_name,
                "project_priority" => $records->project_priority,
                "requested_by"=>$emp,
                "requested_by_dept" =>$dept,
                "status" => $records->status,
                "color_code"=>$sts_name,
            ];
                
            array_push($new_array,$newdata);

          
        }
        $response["dataList"] = $data;
        $response["req_type"] =  $req_type;
        $response["pri"] = "";
        return json_encode($response);
    }
    
}
