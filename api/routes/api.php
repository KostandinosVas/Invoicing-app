<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\SeriesController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::get('/companies/{company}', [CompanyController::class, 'show']);
    Route::post('/companies', [CompanyController::class, 'store']);
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/{customer}', [CustomerController::class, 'show']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::get('/items', [ItemController::class, 'index']);
    Route::get('/items/{item}', [ItemController::class, 'show']);
    Route::post('/items', [ItemController::class, 'store']);
    Route::get('/series', [SeriesController::class, 'index']);
    Route::get('/series/{series}', [SeriesController::class, 'show']);
    Route::post('/series', [SeriesController::class, 'store']);
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::post('/invoices/{invoice}/issue', [InvoiceController::class, 'issue']);
    Route::put('/companies/{company}/credentials', [CompanyController::class, 'updateCredentials']);
    Route::post('/invoices/{invoice}/submit', [InvoiceController::class, 'submit']);
});
