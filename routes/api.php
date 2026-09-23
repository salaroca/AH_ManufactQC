<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EquipmentController;
use App\Http\Controllers\Admin\FamilyController;
use App\Http\Controllers\Admin\OrderFabricationController;
use App\Http\Controllers\Admin\PhotoController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\QuestionCategoryController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\QuestionTemplateController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::apiResource('projects', ProjectController::class);
Route::apiResource('sections', SectionController::class);
Route::apiResource('questions', QuestionController::class);
Route::post('sections/{section}/questions/reorder', [QuestionController::class, 'reorder']);
Route::post('sections/{section}/questions/from-templates', [QuestionController::class, 'storeFromTemplates']);
Route::apiResource('question-templates', QuestionTemplateController::class)->except(['show']);
Route::post('question-categories/reorder', [QuestionCategoryController::class, 'reorder']);
Route::apiResource('question-categories', QuestionCategoryController::class)->except(['show']);
Route::apiResource('users', UserController::class);
Route::apiResource('order-fabrications', OrderFabricationController::class);
Route::apiResource('equipment', EquipmentController::class);
Route::apiResource('families', FamilyController::class)->only(['index', 'store', 'destroy']);

Route::get('dashboard', [DashboardController::class, 'index']);
Route::get('photos/{photo}', [PhotoController::class, 'show']);
