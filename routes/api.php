<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\HomeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::post('/loginCheck', [LoginController::class, 'login']);
Route::get('/dashboard_all_dept', [HomeController::class, 'dashboard_all_dept']);
Route::get('/get_all_dept', [HomeController::class, 'get_all_dept']);
Route::post('/dashboard_dept', [HomeController::class, 'dashboard_dept_data']);
Route::get('/dashboard_user', [HomeController::class, 'dashboard_user']);
Route::get('/getSystemOld', [HomeController::class, 'getSystemOld']);
Route::post('/new_request', [HomeController::class, 'new_request']);
Route::post('/getMyrequest', [HomeController::class, 'getMyrequest']);
Route::get('/viewcreation/{id}', [HomeController::class, 'viewcreation']);
Route::get('/hodList', [HomeController::class, 'hodList']);
Route::get('/hodlsit_view/{id}', [HomeController::class, 'viewcreation']);

// Route::group(['prefix' => 'api', 'middleware' => ['cors']], function(){
//     Route::resource('/getMyrequest', [HomeController::class, 'getMyrequest']);
// });
Route::post('register', [HomeController::class, 'register']);
Route::post('login', [HomeController::class, 'login']);
Route::middleware('auth:api')->group( function () {
    Route::resource('users', HomeController::class);
});