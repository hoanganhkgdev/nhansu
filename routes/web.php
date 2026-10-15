<?php

use App\Http\Controllers\OfficialController;
use Illuminate\Support\Facades\Route;

// The public site has no listing or search: a person's page is reachable only through the link in their QR code.
Route::redirect('/', '/admin');

// Same URL shape as the old NukeViet site (including its ".html" suffix) so the QR codes already
// printed on cards keep working. Do not change this path.
Route::get('/nhan-su-cac-tinh-thanh/{part}/{official}', [OfficialController::class, 'show'])->name('officials.show');
