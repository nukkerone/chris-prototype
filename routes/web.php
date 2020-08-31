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

Route::get('/prototype', 'PrototypeController@index')->name('prototype');
Route::get('/prototype/users/{id}', 'PrototypeController@showUser');
Route::post('/prototype/create-users', 'PrototypeController@createUsers');
Route::post('/prototype/assign-users', 'PrototypeController@assignUsers');
Route::post('/prototype/reset-database', 'PrototypeController@resetDatabase');
