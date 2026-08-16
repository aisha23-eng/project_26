<?php

use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Home')->name('home');
Route::inertia('pricing', 'Pricing')->name('pricing');
Route::inertia('contact', 'Contact')->name('contact');
Route::inertia('docs', 'Docs')->name('docs');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('expenses', [TransactionController::class, 'expenses'])->name('expenses');
    Route::get('income', [TransactionController::class, 'income'])->name('income');
    Route::get('reports', [ReportController::class, 'index'])->name('reports');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('categories', [CategoryController::class, 'index'])->name('categories');

    Route::get('budgets', [BudgetController::class, 'index'])->name('budgets');
    Route::post('budgets', [BudgetController::class, 'store'])->name('budgets.store');
    Route::patch('budgets/{budget}', [BudgetController::class, 'update'])->name('budgets.update');
    Route::delete('budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');

    Route::get('alerts', [AlertController::class, 'index'])->name('alerts');

    Route::get('assistant', [AssistantController::class, 'index'])->name('assistant');
    Route::post('assistant/stream', [AssistantController::class, 'stream'])->name('assistant.stream');

    Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::patch('transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::inertia('chat', 'Chat')->name('chat');
});

require __DIR__.'/settings.php';
