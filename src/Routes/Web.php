<?php

use Illuminate\Support\Facades\Route;

$prefix=config('modelsManager.routeGroupPrefix');
$middleware=config('modelsManager.routeGroupMiddleware');

Route::group(['prefix'=>$prefix,'middleware'=>$middleware,'namespace'=>'PeakTowerTech\ModelsManager\App\Http\Controllers'],function ()use($prefix){
    Route::get('/','DashboardController@index')->name($prefix.".index");
});
