<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DishController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Dành cho Mobile App (Flutter)
|--------------------------------------------------------------------------
|
| Tất cả API routes được prefix với /api
| Phiên bản API: v1 (có thể mở rộng thành v2, v3 sau này)
|
*/

// Nhóm routes cho API version 1
Route::prefix('v1')->group(function () {
    
    // ================== PUBLIC ROUTES ==================
    // Không cần đăng nhập, ai cũng có thể gọi
    
    // Categories - Lấy danh sách danh mục món ăn
    Route::get('/categories', [CategoryController::class, 'index'])
        ->name('api.v1.categories.index');
    
    // Menu - Lấy danh sách món ăn
    Route::get('/menu', [DishController::class, 'index'])
        ->name('api.v1.menu.index');
    
    // Featured Dishes - Món nổi bật
    Route::get('/dishes/featured', [DishController::class, 'featured'])
        ->name('api.v1.dishes.featured');
    
    // Search - Tìm kiếm món ăn (Đặt TRƯỚC {id} để tránh bị bắt nhầm)
    Route::get('/dishes/search', [DishController::class, 'search'])
        ->name('api.v1.dishes.search');
    
    // Dish Detail - Chi tiết món ăn (Đặt SAU các route cố định)
    Route::get('/dishes/{id}', [DishController::class, 'show'])
        ->name('api.v1.dishes.show');

});


// ============================================================
// Người 2 — Home + Branch + About + Contact (mock data inline)
// Thêm bên dưới, KHÔNG đụng vào routes của Người 1/3 ở trên.
// ============================================================

Route::prefix('v1')->group(function () {

    // Home
    Route::get('/home', [\App\Http\Controllers\Api\HomeController::class, 'index']);

    // Branches
    Route::get('/branches',      [\App\Http\Controllers\Api\BranchController::class, 'index']);
    Route::get('/branches/{id}', [\App\Http\Controllers\Api\BranchController::class, 'show'])
        ->whereNumber('id');

    // About
    Route::get('/about', [\App\Http\Controllers\Api\AboutController::class, 'index']);

    // Contact
    Route::post('/contact', [\App\Http\Controllers\Api\ContactController::class, 'store']);

});
