<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Operari\LoginController as OperariLoginController;
use App\Http\Middleware\Admin\EnsureAdminOrQc;
use App\Http\Middleware\Operari\EnsureOperari;
use App\Models\Equipment;
use App\Models\OrderFabrication;
use App\Models\Section;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/login', [AuthController::class, 'create'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');

Route::get('/operari/login', [OperariLoginController::class, 'create'])->middleware('guest')->name('operari.login');
Route::post('/operari/login', [OperariLoginController::class, 'login'])->middleware('guest');

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/admin/dashboard', function () {
    return Inertia::render('Admin/Dashboard');
})->middleware(['auth', EnsureAdminOrQc::class])->name('admin.dashboard');

Route::get('/admin/projects', function () {
    return Inertia::render('Admin/ProjectIndex');
})->middleware(['auth', EnsureAdminOrQc::class])->name('admin.projects');

Route::get('/admin/sections', function () {
    return Inertia::render('Admin/SectionIndex');
})->middleware(['auth', EnsureAdminOrQc::class])->name('admin.sections');

Route::get('/admin/sections/{section}/questions', function (Section $section) {
    return Inertia::render('Admin/QuestionIndex', ['sectionId' => $section->id]);
})->middleware(['auth', EnsureAdminOrQc::class])->name('admin.sections.questions');

Route::get('/admin/question-categories', function () {
    return Inertia::render('Admin/QuestionCategoryIndex');
})->middleware(['auth', EnsureAdminOrQc::class])->name('admin.question-categories');

Route::get('/admin/users', function () {
    return Inertia::render('Admin/UserIndex');
})->middleware(['auth', EnsureAdminOrQc::class])->name('admin.users');

Route::get('/admin/order-fabrications', function () {
    return Inertia::render('Admin/OrderFabricationIndex');
})->middleware(['auth', EnsureAdminOrQc::class])->name('admin.order-fabrications');

Route::get('/admin/order-fabrications/{orderFabrication}/equipment', function (OrderFabrication $orderFabrication) {
    return Inertia::render('Admin/EquipmentIndex', ['orderFabricationId' => $orderFabrication->id]);
})->middleware(['auth', EnsureAdminOrQc::class])->name('admin.order-fabrications.equipment');

Route::get('/operari', function () {
    return Inertia::render('Operari/ProjectSelector');
})->middleware(['auth', EnsureOperari::class])->name('operari.home');

Route::get('/operari/order-fabrications/{orderFabrication}/equipment-list', function (OrderFabrication $orderFabrication) {
    return Inertia::render('Operari/EquipmentList', ['orderFabricationId' => $orderFabrication->id]);
})->middleware(['auth', EnsureOperari::class])->name('operari.equipment-list');

Route::get('/operari/equipment/{equipment}/check', function (Equipment $equipment) {
    return Inertia::render('Operari/FormCheck', ['equipmentId' => $equipment->id]);
})->middleware(['auth', EnsureOperari::class])->name('operari.check');

Route::middleware(['auth', EnsureAdminOrQc::class])
    ->prefix('api')
    ->name('api.')
    ->group(base_path('routes/api.php'));

Route::middleware(['auth', EnsureOperari::class])
    ->prefix('operari/api')
    ->name('operari.api.')
    ->group(base_path('routes/operari-api.php'));
