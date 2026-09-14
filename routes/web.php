<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\LecturerPortalController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketingController::class, 'index'])->name('home');
Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->name('login.store');
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
});
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/docs', [DocsController::class, 'index'])->name('docs');
Route::get('/blog', [BlogController::class, 'index'])->name('blog');
Route::get('/blog/feed.xml', [BlogController::class, 'feed'])->name('blog.feed');
Route::get('/blog/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/program/{id}', [CatalogController::class, 'program'])->name('catalog.program');
Route::get('/course/{id}', [CatalogController::class, 'course'])->name('catalog.course');
Route::get('/faq', [CatalogController::class, 'faq'])->name('faq');
Route::get('/contact', [CatalogController::class, 'contact'])->name('contact');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

Route::middleware('auth')->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [PortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/krs', [PortalController::class, 'krs'])->name('krs');
    Route::get('/academic-record', [PortalController::class, 'academicRecord'])->name('academic-record');
    Route::get('/invoices', [PortalController::class, 'invoices'])->name('invoices');
});

Route::middleware('auth')->prefix('lecturer')->name('lecturer.')->group(function () {
    Route::get('/', [LecturerPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/schedule', [LecturerPortalController::class, 'schedule'])->name('schedule');
    Route::get('/classes', [LecturerPortalController::class, 'classes'])->name('classes');
    Route::get('/advisees', [LecturerPortalController::class, 'advisees'])->name('advisees');
});
Route::middleware('auth')->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
});
