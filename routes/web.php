<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::get('/prototype', 'PrototypeController@index');
Route::post('/prototype/users', 'PrototypeController@createUsers');
Route::post('/prototype/users-assign', 'PrototypeController@assignUsers');
Route::post('/prototype/advance-week', 'PrototypeController@advanceWeek');
