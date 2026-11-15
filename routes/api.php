<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HikCentralController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('/hikcentral/test', [HikCentralController::class, 'test']);
Route::get('/hikcentral/fetch-attendance-events', [HikCentralController::class, 'fetchAttendanceEvents']);
Route::get('/hikcentral/fetch-employees', [HikCentralController::class,'fetchEmployees']);
