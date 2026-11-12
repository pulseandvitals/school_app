<?php

use App\Enums\HabitatType;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AssessmentScoreController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GrowthRuleController;
use App\Http\Controllers\HabitatController;
use App\Http\Controllers\HabitatMemberController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    /*
     * Each game environment gets the same set of routes under its own prefix,
     * e.g. aquariums.show → /aquariums/{habitat}, gardens.show → /gardens/{habitat}.
     */
    foreach (HabitatType::cases() as $type) {
        $prefix = $type->routePrefix();

        Route::prefix($prefix)
            ->name($prefix.'.')
            ->middleware('habitat.type:'.$type->value)
            ->group(function () {
                Route::get('/', [HabitatController::class, 'index'])->name('index');
                Route::get('/create', [HabitatController::class, 'create'])->name('create');
                Route::post('/', [HabitatController::class, 'store'])->name('store');
                Route::get('/{habitat}', [HabitatController::class, 'show'])->name('show');
                Route::get('/{habitat}/edit', [HabitatController::class, 'edit'])->name('edit');
                Route::put('/{habitat}', [HabitatController::class, 'update'])->name('update');
                Route::patch('/{habitat}/archive', [HabitatController::class, 'archive'])->name('archive');
                Route::delete('/{habitat}', [HabitatController::class, 'destroy'])->name('destroy');

                Route::get('/{habitat}/report', ReportController::class)->name('report');

                Route::scopeBindings()->group(function () {
                    Route::get('/{habitat}/students/create', [HabitatMemberController::class, 'create'])->name('members.create');
                    Route::post('/{habitat}/students', [HabitatMemberController::class, 'store'])->name('members.store');
                    Route::get('/{habitat}/students/{member}', [HabitatMemberController::class, 'show'])->name('members.show');
                    Route::get('/{habitat}/students/{member}/edit', [HabitatMemberController::class, 'edit'])->name('members.edit');
                    Route::post('/{habitat}/students/{member}', [HabitatMemberController::class, 'update'])->name('members.update');
                    Route::delete('/{habitat}/students/{member}', [HabitatMemberController::class, 'destroy'])->name('members.destroy');

                    Route::get('/{habitat}/assessments', [AssessmentController::class, 'index'])->name('assessments.index');
                    Route::get('/{habitat}/assessments/create', [AssessmentController::class, 'create'])->name('assessments.create');
                    Route::post('/{habitat}/assessments', [AssessmentController::class, 'store'])->name('assessments.store');
                    Route::get('/{habitat}/assessments/{assessment}', [AssessmentController::class, 'show'])->name('assessments.show');
                    Route::get('/{habitat}/assessments/{assessment}/edit', [AssessmentController::class, 'edit'])->name('assessments.edit');
                    Route::put('/{habitat}/assessments/{assessment}', [AssessmentController::class, 'update'])->name('assessments.update');
                    Route::delete('/{habitat}/assessments/{assessment}', [AssessmentController::class, 'destroy'])->name('assessments.destroy');
                    Route::put('/{habitat}/assessments/{assessment}/scores', [AssessmentScoreController::class, 'update'])->name('assessments.scores.update');
                });
            });
    }

    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
    Route::post('/students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');

    Route::resource('growth-rules', GrowthRuleController::class)->except('show');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
