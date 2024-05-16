<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class HomeModel extends Model
{
    use HasFactory;
    public function getAlldepartment(){
        $result = DB::connection('mysql_por')->table('department')
        ->select('*')
        ->get();
		
		return $result;
    }
    public function getStatuscount($deptid,$sts) {
        $query = DB::select("select status,COUNT(id) as count from query_master where emp_deptid = ".$deptid." and ".$sts." group by status ");
        
        $total=0;
        foreach($query as $val) {
            $total += $val->count;
        }
        return $total;
    }
    
    public function getOverallcount($deptid) {
        $query = DB::select("select status,count(id) as count from query_master where emp_deptid = ".$deptid." and status !='Closed' group by status ");
        
        $total=0;
        foreach($query as $val) {
            $total += $val->count;
        }
        return $total;
    }
    public function gethodchart($deptid,$sts) {
        // print_r($deptid);
        // exit;
        if ($deptid == 0) {	
            $query = DB::select("select status,count(id) as count from query_master where status ='".$sts."' group by status");
        }
        else {
            $query = DB::select("select status,count(id) as count from query_master where emp_deptid =".$deptid." AND status ='".$sts."' group by status");
        }
        $row = $query;
        
        if(count($row) > 0) {
            return $row[0]->count;
        }
        else {
            return 0;
        }
    }
    public function getstatus() {
        $query = DB::select("select * FROM status_master where color_code !='' order by FIELD (status_desc, 'Submitted','Rerouted to User', 'HODApproved', 'Feasibility Checked', 'ITHead Approved', 'Inprocess', 'Awaiting for UAT', 'UAT Accepted','Rejected', 'Completed','Closed') ");
        return $query;
    }
    public function getempchart($empuname,$sts) { 
        $query = DB::select("select count(id) as count from query_master where requested_by = '".$empuname."' and status ='".$sts."' group by status");
        $row = $query;  
        
        if(count($row) > 0) {
            return $row[0]->count;
        }
        else {
            return 0;
        }
    }
    public function addnew_Creation($objArray)  {   // print_r($objArray); exit;

        $data = array (
            'system'=>$objArray['req_type'],
            'current_process' => $objArray['cur_process'],
            'proposed_process' => $objArray['pros_process'],
            '4fp' => $objArray['sel_4fp'],
            'outcome' => $objArray['outcome'],
        );
     
        if($objArray['editId'] =='') {
            $query = DB::select("select count(*) as req_ref_no FROM query_master");
            $result = $query; 	
        
            if(count($result) > 0 ){ 	
            $ref_id = $result[0]->req_ref_no + 1;        	
            $refNo1 = str_pad($ref_id, 4, '0', STR_PAD_LEFT); 
            $refNo = 'COM-ITAppRequest-'.$refNo1;
            }
            else {
                $refNo1 = str_pad(1, 4, '0', STR_PAD_LEFT);
                $refNo = 'COM-ITAppRequest-'.$refNo1;
            } 
        
            $data['project_ref_no'] = $refNo;
            $data['emp_deptid'] = Session::get('empdeptid');
            $data['requested_by'] = Session::get('empuname');
            $data['date'] = date("Y-m-d");
        
            if($objArray['upload_doc'] !='') { 
                $data['project_document'] = $objArray['upload_doc'];
            }
            
            if($objArray['req_type'] =='New One') {
                $data1 = array(
                    'system_desc' =>$objArray['proj_title'],
                    'dept_id' =>Session::get('empdeptid')
                );
                $insertid =  DB::connection('mysql')->table('system_desc_master')
                ->insertGetId($data1);
                // $this->db->insert('system_desc_master', $data1);  
                $sysid = $insertid; 
                $data['project_deptid'] = Session::get('empdeptid');			
                $data['system_desc_id']= $sysid;
            } 
            
            if($objArray['existing_name'] !='') { 
                $data['system_desc_id'] = $objArray['existing_name'];
                
                $query = DB::select("SELECT * FROM system_desc_master WHERE id =".$objArray['existing_name']." ");
                $fetch = $query;
                $dept_id = $fetch[0]->dept_id;
                
                $data['project_deptid'] = $dept_id;
            }
            else {
                $dept_id = Session::get('empdeptid');   
            }
            if((Session::get('hod') == Session::get('empuname')) &&(Session::get('empdeptid') == $dept_id)) {   
                $data['status'] = 'HODApproved';
                $data['hod_approved_date'] = date("Y-m-d");
            }
            $newid  = DB::connection('mysql')->table('query_master')
            ->insertGetId($data);
            // $this->db->insert('query_master',$data);
            // $newid = $this->db->insert_id();
        
            $data2 = array(
                'query_id' =>$newid,
                'approver_name' => Session::get('empuname'),
                'approved_date' => date("Y-m-d")
            );
            if((Session::get('hod') == Session::get('empuname')) &&(Session::get('empdeptid') == $dept_id)) {  
                $data2['status'] = 'HODApproved';
            }					
            else {
                $data2['status'] = 'Submitted';
            }
            DB::connection('mysql')->table('comment_master')
            ->insert($data2);
            // $this->db->insert('comment_master',$data2);
        }else { 
            
            if($objArray['upload_doc'] !='') { 
                $data['project_document'] = $objArray['upload_doc'];
            }
            
            if($objArray['req_type'] =='New One') {
                $data1 = array(
                    'system_desc' =>$objArray['proj_title'],
                    'dept_id' =>Session::get('empdeptid')
                );
                $sysid = DB::connection('mysql')->table('system_desc_master')
                ->insertGetId($data1); 

                print_r($sysid);

                $data['system_desc_id']= $sysid;
                $data['project_deptid'] = Session::get('empdeptid');
            } 
            
            if($objArray['req_type'] =='Existing' && $objArray['existing_name'] !='') { 
                $data['system_desc_id'] = $objArray['existing_name'];
                
                $query = DB::select("SELECT * FROM system_desc_master WHERE id='".$objArray['existing_name']."'");
                $fetch = $query; 
                $dept_id = $fetch['dept_id'];
                
                $data['project_deptid'] = $dept_id;
            }
            else {
                $dept_id = Session::get('empdeptid');   
            }
            if((Session::get('hod') == Session::get('empuname')) &&(Session::get('empdeptid') == $dept_id)) {   
                $data['status'] = 'HODApproved';
                $data['hod_approved_date'] = date("Y-m-d");
            }
            else {
                $data['status'] = 'Submitted';
            }
            DB::connection('mysql')->table('query_master')
            ->where('id',$objArray['editId'])
            ->update($data);
            
            $data2 = array(
                'query_id' =>$objArray['editId'],
                'approver_name' => Session::get('empuname'),
                'approved_date' => date("Y-m-d")
            );
            if((Session::get('hod') == Session::get('empuname')) &&(Session::get('empdeptid') == $dept_id)) {  
                $data2['status'] = 'HODApproved';
            }					
            else {
                $data2['status'] = 'Submitted';
            }
            DB::connection('mysql')->table('comment_master')
            ->insert($data2);
        }
        
        // *************  Direct to feasibility study When HOD Raise a Request 
        if($objArray['editId'] =='') {
            $query = DB::select("SELECT * FROM query_master WHERE id='".$newid."' ");	
            $result = $query; 
        }
        else { 
            $query=DB::select("SELECT * FROM query_master WHERE id='".$objArray['editId']."' ");	
            $result = $query; 
        }	
        // print_r($newid);
        $URL = config('app.front_url');
        $userDetails = $this->getUserDetails($result[0]->requested_by);
        $emp = $this->getDetails1('cs_emp_name','tbl__cs_employee','cs_emp_username = "'.$result[0]->requested_by.'" ');
        $title = $this->getDetails('system_desc','system_desc_master','id='.$result[0]->system_desc_id.'');
            
        if((Session::get('hod') == Session::get('empuname')) &&(Session::get('empdeptid') == $dept_id)) {
            
            $message='<div style="padding:5px;"><b>Dear Sir,</b></div><div style="padding:5px;"><br>The below mentioned project request has been approved and awaiting for Feasibility Study.</div><br>';
            $message.='<table width="55%" height="45%" cellpadding="2" cellspacing="2" border="1" align="center">';
            $message.='<tr><td colspan="2" align="left" style="background-color:purple; color:white; padding:5px; font-weight:bold"><b>Project Request Details </b></td></tr>';
            $message.='<tr><td width=20% align="right" style="padding:5px;font-weight:bold">Request Id</td><td width=30% style="padding:5px;color:red;">'.$result[0]->project_ref_no.'</td></tr>';
            $message.='<tr><td width="20%" align="right" style="padding:5px;font-weight:bold">Project Title</td><td width="30%" style="padding:5px;">'.$title.'</td></tr>';
            $message.='<tr><td width="20%" align="right" style="padding:5px;font-weight:bold">Requester Name</td><td width="30%" style="padding:5px;">'.$emp.'</td></tr>'; 
            $message.='<tr><td width="30%" align="right" style="padding:5px;font-weight:bold">Current Process</td><td width="30%" style="padding:5px;">'.$result[0]->current_process.'</td></tr>';
            $message.='<tr><td width="30%" align="right" style="padding:5px;font-weight:bold">Proposed Process</td><td width="30%" style="padding:5px;">'.$result[0]->proposed_process.'</td></tr>';
            $message.='<tr><td width="20%" align="right" style="padding:5px;"><b>4FP</b></td><td width="30%" style="padding:5px;">'.$result[0]->{'4fp'}.'</td></tr>';
            $message.='<tr><td width="10%" align="right" style="padding:5px;"><b>Outcome</b></td><td width="30%" style="padding:5px;">'.$result[0]->outcome.'</td></tr></table>';
            
            $message.='<br><a href="'.$URL.'login" style="color:red;text-decoration:underline;">Click here</a> to Login IT Ticketing System.';
            $message.='<br>&nbsp;';
            $message.='<br><b>Thanks & Regards,</b>';
            $message.='<br>'.Session::get('empname').'';
            $message.='<br>Comstar Automotive Technologies Pvt Ltd.,';
            
            $to = implode(',',Session::get('feasistudy'));
            //$message.='<br>To : '.$to ;
            //$to = "jsathees@comstarauto.com" ;
            $userDetails = $this->getUserDetails($result[0]->requested_by);
            $ccMail = array(Session::get('empemail'), $userDetails[0]->cs_emp_email, config('app.itemail'));
            $ccMail = array_unique($ccMail);
            $cc = implode(',', $ccMail);
            $subject ="Project Request - ".$result[0]->project_ref_no;
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=iso-8859-1" . "\r\n";
            $headers .= "From: ".Session::get('empemail'). "\r\n";
            $headers .= "Cc: ".$cc. "\r\n";
            
            // echo $to.'<br/>'.$subject.'<br/>'.$message.'<br/>'.$headers; exit;			
             
            $maildata= mail($to,$subject,$message,$headers);   // @ -> Doesnt throw an error
            
            if($maildata) { 
                $response['status']  = "success";
                $response['message'] = "Successfully Inserted";
                return json_encode($response); 
            }else{
                $response['status']  = "Error";
                $response['message'] = "Somrthing Went Wrong";
                return json_encode($response);
            }
        } else {
            $message='<div style="padding:5px;"><b>Dear Sir,</b></div><div style="padding:5px;"><br>The below mentioned project request has been created and awaiting for HOD Approval.</div><br>';
            $message.='<table width="55%" height="45%" cellpadding="2" cellspacing="2" border="1" align="center">';
            $message.='<tr><td colspan="2" align="left" style="background-color:purple; color:white; padding:5px; font-weight:bold"><b>Project Request Details </b></td></tr>';
            $message.='<tr><td width=20% align="right" style="padding:5px;font-weight:bold">Request Id</td><td width=30% style="padding:5px;color:red;">'.$result[0]->project_ref_no.'</td></tr>';
            $message.='<tr><td width="20%" align="right" style="padding:5px;font-weight:bold">Project Title</td><td width="30%" style="padding:5px;">'.$title.'</td></tr>';
            $message.='<tr><td width="20%" align="right" style="padding:5px;font-weight:bold">Requester Name</td><td width="30%" style="padding:5px;">'.$emp.'</td></tr>'; 
            $message.='<tr><td width="30%" align="right" style="padding:5px;font-weight:bold">Current Process</td><td width="30%" style="padding:5px;">'.$result[0]->current_process.'</td></tr>';
            $message.='<tr><td width="30%" align="right" style="padding:5px;font-weight:bold">Proposed Process</td><td width="30%" style="padding:5px;">'.$result[0]->proposed_process.'</td></tr>';
            $message.='<tr><td width="20%" align="right" style="padding:5px;"><b>4FP</b></td><td width="30%" style="padding:5px;">'.$result[0]->{'4fp'}.'</td></tr>';
            $message.='<tr><td width="10%" align="right" style="padding:5px;"><b>Outcome</b></td><td width="30%" style="padding:5px;">'.$result[0]->outcome.'</td></tr></table>';
            
            $message.='<br><a href="'.$URL.'login" style="color:red;text-decoration:underline;">Click here</a> to Login IT Ticketing System.';
            $message.='<br>&nbsp;';
            $message.='<br><b>Thanks & Regards,</b>';
            $message.='<br>'.Session::get('empname').'';
            $message.='<br>Comstar Automotive Technologies Pvt Ltd.,';
            
            $to = $this->getHOD($dept_id,'cs_emp_email'); 
            //$message.='<br>To : '.$to ;
            //$to = "jsathees@comstarauto.com" ;
            $userDetails = $this->getUserDetails($result[0]->requested_by);
            $ccMail = array(Session::get('empemail'), $userDetails[0]->cs_emp_email, config('app.itemail')[0]);
            $ccMail = array_unique($ccMail);
            $cc = implode(',', $ccMail);
            $subject ="Project Request - ".$result[0]->project_ref_no;
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=iso-8859-1" . "\r\n";
            $headers .= "From: ".Session::get('empemail'). "\r\n";
            $headers .= "Cc: ".$cc. "\r\n";
            
            // echo $to.'<br/>'.$subject.'<br/>'.$message.'<br/>'.$headers; exit;
            // echo $message;
            $maildata= mail($to,$subject,$message,$headers);   // @ -> Doesnt throw an error
            if($maildata) { 
                $response['status']  = "success";
                $response['message'] = "Successfully Inserted";
                return json_encode($response); 
            }else{
                $response['status']  = "Error";
                $response['message'] = "Somrthing Went Wrong";
                return json_encode($response);
            }
        }
    }
    public function getUserDetails($empuname) {
		$portaldb = DB::connection('mysql_por')->table('tbl__cs_employee')
		->select('*')
		->where('cs_emp_username', $empuname)
		->get();
		//echo $portaldb->last_query(); exit;
		return $portaldb;
	}
    public function getexisting_system(){
        $query = DB::select("select * from system_desc_master where active ='Y' order by system_desc asc");
        return $query;
    }
    public function getMyrecords($radio,$prior,$user) {   
        
        
        $qry ='1';
            
        if($radio!=''){  
            $qry .=' AND system_desc_id="'.$radio.'"';    // concatenating string 
        }
                    
        if($prior!=''){
            $qry .=' AND project_priority="'.$prior.'"';
        }
        // print_r($qry);
        // print_r(Session::get('empuname'));
        $query = DB::select("select * from query_master where ".$qry." and requested_by = '".$user."' order by id desc");
      
        
        // print_r($query); exit;
        //echo "SELECT * FROM query_master WHERE ".$where." AND requested_by = '".$this->session->userdata('empuname')."' ORDER BY id desc  "; exit;
        return $query;
    }
    public function getDetails($fld,$tab,$id) {
        // print_r($fld);
		$query = DB::select("select ".$fld." from ".$tab." where ".$id." "); 
        // print_r($query);
        // exit;
		$res = $query;
		
		if(count($res) > 0) {
			return $res[0]->$fld;
		}
		else {
			return '-';
		}
	}
    public function getDetails1($fld,$tab,$id) {
	
		$query = DB::connection('mysql_por')->select("select ".$fld." from ".$tab." where ".$id." "); 
		$res = $query;
		
		if(count($res) > 0) {
			return $res[0]->$fld;
		}
		else {
			return '-';
		}
	}
    public function getprojdept($id) {
		$query = DB::select("select dept_id FROM system_desc_master WHERE id=".$id." "); 
		$res = $query;
		
		$result = DB::connection('mysql_por')->select("select * from department where dept_id =".$res[0]->dept_id." ");
		return $result;
	}
    public function getRaiseddept($id) {  //echo $id; 
		$dept = $this->getDetails1('cs_emp_dept_id','tbl__cs_employee','cs_emp_username ="'.$id.'" '); 
		
		if($dept != '-' && $dept != '') {  
			// $portal = $this->load->database('portaldb', TRUE);
			$result = DB::connection('mysql_por')->select("select dept_name from department where dept_id =".$dept." ");
			$res = $result;
			
			if(count($res) > 0) {
				return $res[0]->dept_name;
			}
			else {
				return '-';
			}
		}
		
	}
    public function Status_Color($name) {
		$query = DB::select("SELECT * FROM status_master WHERE status_desc ='".$name."'");
		return $query;
	}
	
	function status_aaproval($id) { 
		$query = DB::select(" SELECT * FROM query_master WHERE id ='".$id."'");
		 //print_r($res); 
		
		switch($query[0]->status) { 
		
			case 'Submitted' :
				$dept = $this->getProjectid($query[0]->system_desc_id);
				
				if($dept !='-' && $dept !='0' ) { 
					$projhod = $this->getHOD($dept,'cs_emp_name');
					return $projhod;
				}
			break;
			
			case 'HODApproved' :
				$arrray = config('app.feasi');
				$search = array_search('intranet intranet',$arrray);
				unset($arrray[$search]);
				$name = implode(',',$arrray);
				return $name;
			break;
			
			case 'Feasibility Checked' :
				if($query[0]->dev_days <= 10) { 
					$arrray = config('app.itheadname');
					$search = array_search('intranet intranet',$arrray);
					unset($arrray[$search]);
					$name = implode(',',$arrray);
					return $name;
				}
				else {
					$arrray = config('app.ithead1name');
					$search = array_search('intranet intranet',$arrray);
					unset($arrray[$search]);
					$name = implode(',',$arrray);
					return $name;
				}
			break;
			
			case 'ITHead Approved' :
			case 'Inprocess' : 
			case 'UAT Accepted' :
				$name = $this->getDetails1('cs_emp_name','tbl__cs_employee','cs_emp_username = "'.$query[0]->project_developer.'" '); 
				return $name;
			break;
			
			case 'Rerouted to User' :
			case 'Awaiting for UAT' :
				$name = $this->getDetails1('cs_emp_name','tbl__cs_employee','cs_emp_username = "'.$query[0]->requested_by.'" '); 
				return $name;
			break;
			
			
		}
		
	}
    public function getProjectid($deptid) {
		$query = DB::select("select dept_id FROM system_desc_master WHERE id = ".$deptid." ");
		//echo $this->db->last_query();
		$res = $query;
		if(count($res) > 0) {
			return $res[0]->dept_id;
		}
		else {
			return '-';
		}
	}
    public function getHOD($id,$name) { 
		if($id !='') { 
			// $portal = $this->load->database('portaldb', TRUE);
		
			$query = DB::connection('mysql_por')->select("select dept_hod FROM department WHERE dept_id =".$id." ");
			$row = $query;
		
			$c='';
			$a = explode(',',$row[0]->dept_hod);     // a[0] ='ramesh' a[1]='rsjp'
			foreach($a as $b) {
				$c.=  "'".$b."',";     // converting array into string i.e 
			}                          // 'sramesh','rsjp',        
			$c = trim($c,',');        // ('ramesh',rsjp')
			
			$result = DB::connection('mysql_por')->select("select group_concat(".$name.") as name FROM tbl__cs_employee WHERE cs_emp_username IN($c)");
			$res = $result;
			
			if(count($res) > 0 ) {
				return $res[0]->name;
			}
			else {
                return '-';
            }
				
		}
	}
    public function getviewcode($id) {
        $query = DB::select("select * from query_master where id='".$id."' ");
        return $query;  
    }
    function getHodrecords() {
        $portaldb = DB::connection('mysql_por')->table('department')
        ->select('dept_id')
        ->where('dept_hod',Session::get('empuname'))
        ->get();
        $dept =array();
        foreach($portaldb as $row){
            array_push($dept,$row->dept_id);
        }

        $query = DB::connection('mysql')->table('query_master')
        ->select('*')
        ->whereIn('project_deptid',$dept)
        ->where('status','=',"Submitted")
        ->orderBy('id','desc')
        ->get();
        
        return $query;
        
    }
    
}
