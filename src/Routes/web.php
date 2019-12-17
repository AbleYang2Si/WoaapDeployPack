<?php

Route::namespace('Woaap\Deploy\Controllers')->as('deploy::')->middleware('web')->group(function () {
    Route::get('_woaap/log/search', 'LogController@search');
    Route::get('_woaap/sinfo/check', 'SinfoController@check');
});
