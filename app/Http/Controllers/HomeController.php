<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Validator;


class HomeController extends Controller
{
    protected $homeModel;
    protected $loginModel;
    public function __construct()
    {
        $this->loginModel = new \App\Models\LoginModel();
        $this->homeModel = new \App\Models\HomeModel();
    }
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
   
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] =  $user->createToken('MyApp')->accessToken;
        $success['name'] =  $user->name;
   
        return $this->sendResponse($success, 'User register successfully.');
    }
    public function login(Request $request)
    {
        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){ 
            $user = Auth::user(); 
            $success['token'] =  $user->createToken('MyApp')-> accessToken; 
            $success['name'] =  $user->name;
   
            return $this->sendResponse($success, 'User login successfully.');
        } 
        else{ 
            return $this->sendError('Unauthorised.', ['error'=>'Unauthorised']);
        } 
    }
    public function index(){
        $response['users'] = User::all();
        return json_encode($response);
    }
    public function dashboard_all_dept(){
        $dept = $this->homeModel->getAlldepartment();
        $response = [];
        $deptname = [];
        $Oplan = array();
        $Cplan = array();
        $totalval = array();

        foreach($dept as $depart)  {


            $tot = $this->homeModel->getOverallcount($depart->dept_id);
            $sts = $this->homeModel->getStatuscount($depart->dept_id, 'status IN ("Submitted","Rerouted to User", "HODApproved", "Feasibility Checked", "ITHead Approved", "Inprocess", "Awaiting for UAT", "UAT Accepted") ');
            $stat = $this->homeModel->getStatuscount($depart->dept_id, 'status IN ("Rejected", "Completed")');
            if ($tot > 0 && $sts > 0 && $stat > 0) {
                array_push($totalval, $tot);
                array_push($deptname, $depart->dept_name);


                array_push($Oplan, $sts);

                array_push($Cplan, $stat);
            }
            
            
        }

        $response['deptname'] = $deptname;
        $response['Oplan'] = $Oplan;
        $response['Cplan'] = $Cplan;
        $response['totalval'] = $totalval;

        return json_encode($response) ;
    }
    public function get_all_dept(){
        $data = $this->homeModel->getAlldepartment();
        $response = [];
        if(count($data) > 0){
            $response['message'] = "success";
            $response['debtdata'] = $data;
        }else{
            $response['message'] = "No Record Found";
            $response['debtdata'] = '';
        }
        
        return json_encode($response);
    }
    public function dashboard_dept_data(Request $request){
        // print_r($request->dept_id);
        // exit;
        $list = $this->homeModel->getstatus();
        $i = 0;
        $stss  = array();
        $depdata = array();
        $response = [];
        foreach ($list as $rows) {
            array_push($stss,$rows->status_desc);
            $value = $this->homeModel->gethodchart($request->dept_id, $rows->status_desc);
            array_push($depdata,$value);
            $i++;
        }
        $response['stss'] = $stss;
        $response['depdata'] = $depdata;

        return json_encode($response);
    }
    public function dashboard_user(Request $request){
        
        $sts = $this->homeModel->getstatus();
		$i=0;
        $dataArr = array();
        $status = array();
        
        foreach($sts as $row)  { 
           array_push($status,$row->status_desc);
            $emp = $this->homeModel->getempchart(Session::get('empuname'),$row->status_desc); 
            array_push($dataArr,$emp);
            $i++; 
        }
        $response['userdata'] = $dataArr;
        $response['stss'] = $status;

        return json_encode($response);
    }
    public function new_request(Request $request) {  
        // print_r("Session::get('empuname')");
        // exit;
        $data['req_type'] = $request->get('req_type');		
        $data['proj_title'] = $request->get('proj_title');
        $data['existing_name'] = $request->get('existing_name');
        $data['cur_process'] = $request->get('cur_process');
        $data['pros_process'] = $request->get('pros_process');
        $data['sel_4fp'] = $request->get('sel_4fp');
        $data['outcome'] = $request->get('outcome');
        $data['editId'] = $request->get('editId');
        
                        
        if(isset($_FILES['docu']) && $_FILES['docu']['name']!='') {  
            $file = explode('.',strtolower($_FILES['docu']['name']));     
            $ary = end($file); 
            $tmp = $_FILES['docu']['tmp_name'];
            print_r($tmp);
            $filename = $file[0]."_".date('d-M-Y').".".$ary;
            $alert_file = 'Fileupload/'.$filename;
            $upl = move_uploaded_file($_FILES['docu']['tmp_name'],$alert_file);
            if($upl){	
                $data['upload_doc'] = $filename;
            }
        }  
        else {
            $data['upload_doc'] ='';
        }
    
        if($request->get('req_type')) {
            
            $Res= $this->homeModel->addnew_Creation($data);
            if ($Res == 1) {
            $data['error_message'] = "Successfully inserted";
            } else {
                $data['error_message'] = 'Error';
            }
            
        }
        return json_encode($data['error_message']); 
    }
    public function getSystemOld(){
        $data = $this->homeModel->getexisting_system();
        $response = [];
        if(count($data) > 0){
            $response['message'] = "success";
            $response['debtdata'] = $data;
        }else{
            $response['message'] = "No Record Found";
            $response['debtdata'] = '';
        }
        
        return json_encode($response);
    }
    public function getMyrequest(Request $request){
        // print_r(Session::get('empuname'));
        // exit;
        // session()->put('user',"pandi");
        if($request->all()){
            $input = $request->all();
            $req_type = $input['radiotype'];
            $pri      =  $input['priority'];
            $user = $input['user'];
            if($req_type == null &&  $pri == null){
                $qry ='1';
                $data = $user;
            }


            $query= $this->homeModel->getMyrecords($req_type,$pri,$user);

            $j=1; 
            $new_array = array();
            foreach($query as $records) {  ; 
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

              $j++;
            }
            $response["dataList"] = $new_array;
        }else{

            $req_type = "";
            $pri      = "";
            $user = session()->get('empuname');
            if($req_type == null &&  $pri == null){
                $qry ='1';
                $data = $user;
            }


            $query= $this->homeModel->getMyrecords($req_type,$pri,$user);
            $j=1; 
            $new_array = array();
            foreach($query as $records) {  ; 
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

                $j++;
              
            }
            $response["dataList"] =  $new_array;     
        }
        
        return json_encode($response);
    }
    public function viewcreation($id) {
        
        $row = $this->homeModel->getviewcode($id); 
        if(count($row) > 0){
            $response['title'] = $this->homeModel->getDetails('system_desc','system_desc_master','id='.$row[0]->system_desc_id.'');
		    $response['emp'] = $this->homeModel->getDetails1('cs_emp_name','tbl__cs_employee','cs_emp_username = "'.$row[0]->requested_by.'" ');
		    $response['projdept'] = $this->homeModel->getprojdept($row[0]->system_desc_id);
        }else{
            $response['title'] = "";
            $response['emp'] = "";
            $response['projdept'] ="";
        }
        $response['row'] = $row;
        
        return json_encode($response);
        
    }
    public function hodList(){
        $list = $this->homeModel->getHodrecords();
        if(count($list)>0){
            $response['list'] = $list;
        }else{
            $response['list'] = "No Record Found";
        }
        return json_encode($response);
    }
    
    
}
