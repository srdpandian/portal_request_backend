<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LoginModel extends Model
{
    use HasFactory;

    public function Check_user_exists($uname){ 
		$portaldb = DB::connection('mysql_por')->table('tbl__cs_employee as e')
        ->select('*')    
        ->join('department as d','e.cs_emp_dept_id','=','d.dept_id','inner')
        ->where('e.cs_emp_username','=',$uname)
		->where('e.cs_emp_status','=','Active')
        ->get();
		
        return $portaldb;

    }
}
