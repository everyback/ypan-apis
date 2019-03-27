<?php

use Illuminate\Http\Request;

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

/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/


Route::group([

    'prefix' => 'auth',
    'middleware'=>'decode'

], function ($router) {

    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('me', 'AuthController@me');
});

Route::group([
    'middleware'=>'decode'
], function ($router) {

    Route::get('samename','RegisterController@samename');
    Route::get('sameemail','RegisterController@sameemail');
    Route::post('register','RegisterController@register')->middleware('register');
});

Route::middleware(['decode', 'folder'])->group(function ($router) {
    Route::group(['prefix'=>'folder'],function ($router){
        Route::post('rename','File\FolderController@renameFolder');
        Route::post('create','File\FolderController@createFolder');
        Route::post('move','File\FolderController@moveFolder');
        Route::post('delete','File\FolderController@deleteFolder');
        Route::get('list','File\FolderController@folderList');
        Route::post('copy','File\FolderController@copyFolder');
    });
});

Route::middleware(['decode', 'file'])->group(function ($router) {
    Route::group(['prefix'=>'file'],function ($router){
        Route::post('put','File\FileController@addfile');
        Route::post('delete','File\FileController@deletefile');
        Route::post('move','File\FileController@movefile');
        Route::post('rename','File\FileController@renamefile');
        Route::post('copy','File\FileController@copyfile');
      //  Route::post('rename','File\File');
        Route::get('show','File\FileController@showfiles');
        Route::post('createdownload','File\FileController@createdownloadpath');
        Route::get('countall','File\ShowFolderFrameworkController@count');
        Route::get('showpageinate','File\ShowFolderFrameworkController@showpageinate');
    });
});

/*Route::middleware(['decode', 'file'])->group(function ($router) {
    Route::group(['prefix'=>'file'],function ($rd'w's'q'd'f'r'ce's'w'f'v'g'c'r'd'se'g'b'vouter){
        Route::post('put','File\FileController@addfile');
        Route::post('delete','File\FileController@deletefile');
        Route::post('move','File\FileController@movefile');
        Route::post('rename','File\FileController@renamefile');
        //  Route::post('rename','File\File');
        Route::get('show','File\FileController@showfiles');
        Route::post('createdownload','File\FileController@createdownloadpath');

    });
});*/
Route::get('test','test@getrandpath');


Route::get('download/{downloadpath}','File\DownloadFileController@download')->middleware('downloadfile');



Route::post('getuser','AuthController@getAuthenticatedUser');


Route::post('/putfile','FilesController@putfiles');
Route::get('/downloadfile','FilesController@downloadfiles');
