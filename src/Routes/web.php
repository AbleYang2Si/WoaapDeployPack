<?php

Route::namespace('Woaap\Deploy\Controllers')->as('deploy::')->middleware('web')->group(function () {
    Route::get('_woaap/log/search', 'LogController@search');
});
