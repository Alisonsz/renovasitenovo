<?php

use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProfessionalController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        Route::get('/categories', [ProductCategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [ProductCategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [ProductCategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{productCategory}/edit', [ProductCategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{productCategory}', [ProductCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{productCategory}', [ProductCategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/blog-posts', [BlogPostController::class, 'index'])->name('blog-posts.index');
        Route::get('/blog-posts/create', [BlogPostController::class, 'create'])->name('blog-posts.create');
        Route::post('/blog-posts', [BlogPostController::class, 'store'])->name('blog-posts.store');
        Route::get('/blog-posts/{blogPost}/edit', [BlogPostController::class, 'edit'])->name('blog-posts.edit');
        Route::put('/blog-posts/{blogPost}', [BlogPostController::class, 'update'])->name('blog-posts.update');
        Route::delete('/blog-posts/{blogPost}', [BlogPostController::class, 'destroy'])->name('blog-posts.destroy');

        // Orders: full lifecycle management.
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
        Route::post('/orders/{order}/refund', [OrderController::class, 'refund'])->name('orders.refund');

        // Customers (CRM).
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::post('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
        Route::post('/customers/{customer}/treatments', [CustomerController::class, 'attachTreatment'])->name('customers.treatments.store');

        // Appointments (clinic calendar + flat list).
        Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
        Route::get('/appointments/list', [AppointmentController::class, 'list'])->name('appointments.list');
        Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
        Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
        Route::get('/appointments/{appointment}/edit', [AppointmentController::class, 'edit'])->name('appointments.edit');
        Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
        Route::put('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');
        Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');
        Route::get('/customers/{customer}/treatments-json', [AppointmentController::class, 'treatmentsForCustomer'])->name('appointments.customer-treatments');

        // Professionals.
        Route::get('/professionals', [ProfessionalController::class, 'index'])->name('professionals.index');
        Route::post('/professionals', [ProfessionalController::class, 'store'])->name('professionals.store');
        Route::put('/professionals/{professional}', [ProfessionalController::class, 'update'])->name('professionals.update');
        Route::delete('/professionals/{professional}', [ProfessionalController::class, 'destroy'])->name('professionals.destroy');

        // Coupons: full CRUD.
        Route::get('/coupons', [CouponController::class, 'index'])->name('coupons.index');
        Route::post('/coupons', [CouponController::class, 'store'])->name('coupons.store');
        Route::put('/coupons/{coupon}', [CouponController::class, 'update'])->name('coupons.update');
        Route::delete('/coupons/{coupon}', [CouponController::class, 'destroy'])->name('coupons.destroy');

        // Reports.
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

        // Store settings.
        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

        // Minha conta (admin profile + password).
        Route::get('/minha-conta', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/minha-conta', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/minha-conta/senha', [ProfileController::class, 'updatePassword'])->name('profile.password');

        // Usuários administradores.
        Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');
        Route::post('/usuarios', [UserController::class, 'store'])->name('users.store');
        Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
