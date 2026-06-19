<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;

// Controllers
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProgramAdmin\TeacherLoadSettingController;

use App\Http\Controllers\ProgramController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SuperAdminSubjectController;

use App\Http\Controllers\TeacherAvailabilityController;

use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\CurriculumTermSubjectController;

use App\Http\Controllers\RoomController;
use App\Http\Controllers\SectionController;

use App\Http\Controllers\StudentsController;
use App\Http\Controllers\StudentApprovalController;

use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\ScheduleSectionController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\AdminDashboardController;

use App\Http\Controllers\Teacher\TeacherScheduleController;
use App\Http\Controllers\Teacher\EvaluationController;

use App\Http\Controllers\Student\StudentScheduleController;
use App\Http\Controllers\Student\StudentCurriculumController;

use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentClassController;
use App\Http\Controllers\Student\StudentCurrentSubjectController;

use App\Http\Controllers\ProgramAdmin\StudentCurriculumManagementController;
use App\Http\Controllers\ProgramAdmin\TeacherAvailabilityManagementController;


use Barryvdh\DomPDF\Facade\Pdf;

// Additional Controllers for role-based dashboard redirection
use App\Http\Controllers\Admin\SuperAdminDashboardController;
use App\Http\Controllers\ProgramAdmin\ProgramAdminDashboardController;
use App\Http\Controllers\Teacher\TeacherDashboardController;
use App\Http\Controllers\Student\StudentDashboardController;

use App\Http\Controllers\ProgramAdmin\TeacherPreferredSubjectController;

use App\Http\Controllers\ProgramAdmin\SectionDraftScheduleController;

Route::prefix('program-admin')
    ->name('program-admin.')
    ->middleware(['auth'])
    ->group(function () {
        Route::get('/sections/{section}/draft-schedule', [SectionDraftScheduleController::class, 'show'])
            ->name('sections.draft-schedule.show');

            Route::post(
    '/sections/{sectionId}/save-draft',
    [SectionDraftScheduleController::class, 'saveDraft']
)->name('program-admin.sections.save-draft');
    });

    Route::post('/program-admin/sections/{section}/generate-draft',
    [SectionDraftScheduleController::class, 'generateDraft']
)->name('program-admin.sections.generate-draft');


Route::get('/teachers/{teacher}/preferred-subjects', [TeacherPreferredSubjectController::class, 'show'])
    ->name('program-admin.teacher-preferred-subjects.show');

Route::post('/teachers/{teacher}/preferred-subjects', [TeacherPreferredSubjectController::class, 'store'])
    ->name('program-admin.teacher-preferred-subjects.store');

Route::patch('/teachers/{teacher}/preferred-subjects/{teacherPreferredSubject}', [TeacherPreferredSubjectController::class, 'update'])
    ->name('program-admin.teacher-preferred-subjects.update');

Route::delete('/teachers/{teacher}/preferred-subjects/{teacherPreferredSubject}', [TeacherPreferredSubjectController::class, 'destroy'])
    ->name('program-admin.teacher-preferred-subjects.destroy');

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::put('/curriculum-terms/{term}/dates', [CurriculumTermSubjectController::class, 'updateDates'])
    ->name('terms.update_dates');

Route::get('/admin/schedules/sections/{section}/pdf', [ScheduleSectionController::class, 'downloadPdf'])
    ->name('admin.schedules.sections.pdf');




Route::get('/', fn () => view('welcome'));

/*
|--------------------------------------------------------------------------
| Dashboard Redirect (Role-Based)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    $user = auth()->user();

    if (! $user) {
        return redirect()->route('login');
    }

    if ($user->hasRole('super_admin')) {
        return redirect()->route('admin.dashboard');
    }

    if ($user->hasRole('program_admin')) {
        return redirect()->route('program-admin.dashboard');
    }

    if ($user->hasRole('teacher')) {
        return redirect()->route('teacher.dashboard');
    }

    if ($user->hasRole('student')) {
        return redirect()->route('student.dashboard');
    }

    return redirect('/');
})->middleware(['auth', 'verified'])->name('dashboard');
/*
|--------------------------------------------------------------------------
| Auth / Profile
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile',   [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile',[ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::patch('/profile/email', [ProfileController::class, 'updateEmail'])
        ->name('profile.email.update');
});

// (If you really want to override the default Breeze/Jetstream registration routes)
Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [RegisteredUserController::class, 'store']);

/*
|--------------------------------------------------------------------------
| Test / Debug Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:super_admin'])->group(function () {
    Route::get('/admin/test', fn () => 'Only SUPER ADMIN can see this.');
});

Route::middleware(['auth', 'verified', 'role:program_admin|teacher'])->group(function () {
    Route::get('/staff/test', fn () => 'Program admins OR teachers can see this.');
});

Route::get('/debug/notify', function () {
    $user = User::first();
    if (! $user) {
        $user = new User;
        $user->first_name = 'Test';
        $user->last_name  = 'User';
        $user->email      = 'test@example.com';
        $user->password   = bcrypt('secret');
        $user->save();
    }

    // You removed actual notify() call in your snippet; add if needed:
    // $user->notify(new AccountDecisionNotification('approved'));

    return 'Notification sent. Check your Mailtrap Sandbox.';
})->name('debug.notify');

/*
|--------------------------------------------------------------------------
| Admin Routes (Super Admin + Program Admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:super_admin|program_admin'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {


             Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->name('dashboard');

        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Approvals (single organized group - removed your duplicate block)
        Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');

        Route::get('/approvals/{user}', [ApprovalController::class, 'show'])
            ->whereNumber('user')
            ->name('approvals.show');

        Route::get('/approvals/{user}/docs/{doc}', [ApprovalController::class, 'showDocument'])
            ->whereNumber(['user','doc'])
            ->name('approvals.document.show');

        Route::post('/approvals/{user}/approve', [ApprovalController::class, 'approve'])
            ->name('approvals.approve');

        Route::post('/approvals/{user}/decline', [ApprovalController::class, 'decline'])
            ->name('approvals.decline');

        // Scheduling - Sections
        Route::get('/schedules/sections', [ScheduleSectionController::class, 'index'])
            ->name('schedules.sections.index');

        Route::get('/schedules/sections/{section}', [ScheduleSectionController::class, 'show'])
            ->name('schedules.sections.show');

        Route::get('/schedules/report/pdf', [ScheduleSectionController::class, 'downloadScheduleReportPdf'])
        ->name('schedules.report.pdf');


        Route::get('/schedules/sections/{section}/students', [ScheduleSectionController::class, 'students'])
            ->name('schedules.sections.students');

        Route::post('/schedules/sections/{section}/students/batch-update', [ScheduleSectionController::class, 'batchUpdateStudents'])
            ->whereNumber('section')
            ->name('schedules.sections.students.batch-update');

        Route::post('/schedules/sections/{section}/offerings', [ScheduleSectionController::class, 'storeOffering'])
            ->name('schedules.sections.offerings.store');

        Route::get('/schedules/sections/{section}/available-teachers', [ScheduleSectionController::class, 'availableTeachers'])
            ->name('schedules.sections.available-teachers');

        Route::get('/schedules/sections/{section}/available-rooms', [ScheduleSectionController::class, 'availableRooms'])
            ->name('schedules.sections.available-rooms');

        Route::get('/schedules/sections/{section}/offerings/{offering}/edit', [ScheduleSectionController::class, 'editOffering'])
            ->name('schedules.sections.offerings.edit');

        Route::put('/schedules/sections/{section}/offerings/{offering}', [ScheduleSectionController::class, 'updateOffering'])
            ->name('schedules.sections.offerings.update');

        // Scheduling - Offerings (still admin group, role middleware not needed again but ok if you want)
        Route::get('/schedules/offerings', [ScheduleSectionController::class, 'offeringsIndex'])
            ->name('schedules.offerings.index');

        Route::get('/schedules/offerings/{offering}/status', [ScheduleSectionController::class, 'offeringStatus'])
            ->name('schedules.offerings.status');

        Route::post('/schedules/offerings/{offering}/unlock', [ScheduleSectionController::class, 'unlockOffering'])
            ->name('schedules.offerings.unlock');
    });

/*
|--------------------------------------------------------------------------
| Super Admin Only
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::resource('programs', ProgramController::class);
    Route::resource('superadminsubjects', SuperAdminSubjectController::class);

    Route::prefix('admin/users')->name('admin.users.')->group(function () {
        Route::get('/',            [UserManagementController::class, 'index'])->name('index');
        Route::get('/create',      [UserManagementController::class, 'create'])->name('create');
        Route::post('/',           [UserManagementController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserManagementController::class, 'edit'])->name('edit');
        Route::put('/{user}',      [UserManagementController::class, 'update'])->name('update');
        Route::put('/{user}/status',[UserManagementController::class, 'updateStatus'])->name('status');
    });
});

/*
|--------------------------------------------------------------------------
| Program Admin + Super Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:super_admin|program_admin'])->group(function () {

    Route::get('subjects/lookup', [SubjectController::class, 'lookup'])->name('subjects.lookup');

    Route::resource('subjects', SubjectController::class);

});

/*
|--------------------------------------------------------------------------
| Program Admin Only
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:program_admin'])->group(function () {
    Route::resource('curricula', CurriculumController::class)->only(['index','create','store','show']);

    Route::post('curriculum-terms/{term}/subjects', [CurriculumTermSubjectController::class,'store'])
        ->name('terms.subjects.store');
        // EDIT page
        Route::get('curriculum-terms/{term}/subjects/{cts}/edit', [CurriculumTermSubjectController::class,'edit'])
            ->name('terms.subjects.edit');

        // UPDATE action
        Route::put('curriculum-terms/{term}/subjects/{cts}', [CurriculumTermSubjectController::class,'update'])
            ->name('terms.subjects.update');


    Route::delete('curriculum-terms/{term}/subjects/{cts}', [CurriculumTermSubjectController::class,'destroy'])
        ->name('terms.subjects.destroy');

    Route::get('/teacher-load-settings/{teacher}', [TeacherLoadSettingController::class, 'show'])
        ->name('program-admin.teacher-load-settings.show');

    Route::put('/teacher-load-settings/{teacher}', [TeacherLoadSettingController::class, 'update'])
        ->name('program-admin.teacher-load-settings.update');

        Route::get('/teacher-load-settings/{teacher}', [TeacherLoadSettingController::class, 'show'])
    ->name('program-admin.teacher-load-settings.show');

Route::put('/teachers/{teacher}/load-settings', [TeacherLoadSettingController::class, 'update'])
    ->name('program-admin.teacher-load-settings.update');


});

/*
|--------------------------------------------------------------------------
| Shared Resources (Authenticated)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // Teacher availability (basic CRUD)
    Route::resource('teacher_availability', TeacherAvailabilityController::class);

    // Rooms
    Route::resource('rooms', RoomController::class);

    // Sections
    Route::get('/sections', [SectionController::class, 'index'])->name('sections.index');
    Route::get('/sections/create', [SectionController::class, 'create'])->name('sections.create');
    Route::post('/sections', [SectionController::class, 'store'])->name('sections.store');
    Route::get('/sections/{section}/edit', [SectionController::class, 'edit'])->name('sections.edit');
    Route::patch('/sections/{section}', [SectionController::class, 'update'])->name('sections.update');

    Route::post('/sections/{section}/promote', [SectionController::class, 'promote'])->name('sections.promote');
    Route::post('/sections/{section}/archive', [SectionController::class, 'archive'])->name('sections.archive');
    Route::post('/sections/{section}/restore', [SectionController::class, 'restore'])->name('sections.restore');

    // Students in section
    Route::get('/sections/{section}/students', [SectionController::class, 'students'])->name('sections.students');


    // Students approval flow
    Route::get('/students/pending', [StudentsController::class, 'pendingIndex'])->name('students.pending');
    Route::post('/students/approve-freshman', [StudentApprovalController::class, 'approveFreshman'])
        ->name('students.approve.freshman');
});

/*
|--------------------------------------------------------------------------
| Student Routes
|--------------------------------------------------------------------------
*/


Route::middleware(['auth', 'role:student'])->group(function () {


Route::get('/student/dashboard', [StudentDashboardController::class, 'index'])
    ->name('student.dashboard');

    Route::get('/student/schedule', [StudentScheduleController::class, 'show'])
        ->name('student.schedule.show');

    Route::get('/student/schedule/history', [StudentScheduleController::class, 'history'])
        ->name('student.schedule.history');

    Route::get('/student/subjects', [StudentCurrentSubjectController ::class, 'index'])
    ->name('student.subjects.index');

    Route::get('/student/curriculum', [StudentCurriculumController::class, 'index'])
        ->name('student.curriculum.index');

    //
Route::get('/student/schedule/pdf', [StudentScheduleController::class, 'downloadPdf'])
    ->name('student.schedule.pdf');

});

/*
|--------------------------------------------------------------------------
| Teacher Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:teacher'])
    ->prefix('teacher')
    ->name('teacher.')
    ->group(function () {

        Route::get('/dashboard', [teacherDashboardController::class, 'index'])
    ->name('dashboard');

        Route::get('/schedule', [TeacherScheduleController::class, 'index'])
            ->name('schedule.index');

        //teacher pdf download route
        Route::get('/teacher/schedule/pdf', [TeacherScheduleController::class, 'downloadPdf'])
            ->name('schedule.pdf');

        Route::get('/schedule/{classOffering}/students', [TeacherScheduleController::class, 'students'])
            ->name('schedule.students');

        Route::get('/evaluations', [EvaluationController::class, 'index'])
            ->name('evaluations.index');

        Route::get('/evaluations/class/{classOffering}', [EvaluationController::class, 'show'])
            ->name('evaluations.show');

        Route::post('/evaluations/class/{classOffering}', [EvaluationController::class, 'store'])
            ->name('evaluations.store');

        Route::post('/evaluations/class/{classOffering}/finalize', [EvaluationController::class, 'finalize'])
            ->name('evaluations.finalize');
    });

/*
|--------------------------------------------------------------------------
| Program Admin Panel Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:program_admin'])
    ->prefix('program-admin')
    ->name('program-admin.')
    ->group(function () {

    // Dashboard
        Route::get('/dashboard', [ProgramAdminDashboardController::class, 'index'])
                ->name('dashboard');

        // Students Management
        Route::get('students', [StudentController::class, 'index'])->name('students.index');
        Route::get('students/create', [StudentController::class, 'create'])->name('students.create');
        Route::post('students', [StudentController::class, 'store'])->name('students.store');

        Route::get('students/{user}/edit', [StudentController::class, 'edit'])->name('students.edit');
        Route::put('students/{user}', [StudentController::class, 'update'])->name('students.update');

        // Student Classes
        Route::get('students/{user}/classes', [StudentClassController::class, 'index'])
            ->name('students.classes.index');

        Route::post('students/{user}/classes', [StudentClassController::class, 'store'])
            ->name('students.classes.store');

        Route::delete('students/{user}/classes/{enrollment}', [StudentClassController::class, 'destroy'])
            ->name('students.classes.destroy');

        Route::get('students/{user}/schedule-history', [StudentClassController::class, 'history'])
            ->name('students.schedule.history');

        // Curriculum Management (deduplicated)
        Route::get('students/{student}/curriculum', [StudentCurriculumManagementController::class, 'edit'])
            ->name('students.curriculum.edit');

        Route::get('students/{student}/subjects/search', [StudentCurriculumManagementController::class, 'searchSubjects'])
    ->name('students.curriculum.subjects.search');


        Route::patch('students/{student}/curriculum', [StudentCurriculumManagementController::class, 'update'])
            ->name('students.curriculum.update');

        Route::post('students/{student}/curriculum/custom-subjects', [StudentCurriculumManagementController::class, 'storeCustom'])
            ->name('students.curriculum.custom.store');

        Route::patch('students/{student}/curriculum/custom-subjects/{custom}', [StudentCurriculumManagementController::class, 'updateCustom'])
            ->name('students.curriculum.custom.update');

        Route::delete('students/{student}/curriculum/custom-subjects/{custom}', [StudentCurriculumManagementController::class, 'destroyCustom'])
            ->name('students.curriculum.custom.destroy');

        // Teacher Availability Management
        Route::get('teacher-availabilities', [TeacherAvailabilityManagementController::class, 'index'])
            ->name('teacher-availabilities.index');

        Route::get('teacher-availabilities/{teacher}', [TeacherAvailabilityManagementController::class, 'show'])
            ->name('teacher-availabilities.show');

        Route::get('teacher-availabilities/{teacher}/create', [TeacherAvailabilityManagementController::class, 'create'])
            ->name('teacher-availabilities.create');

        Route::post('teacher-availabilities/{teacher}', [TeacherAvailabilityManagementController::class, 'store'])
            ->name('teacher-availabilities.store');

        Route::get('teacher-availabilities/{teacher}/{availability}/edit', [TeacherAvailabilityManagementController::class, 'edit'])
            ->name('teacher-availabilities.edit');

        Route::put('teacher-availabilities/{teacher}/{availability}', [TeacherAvailabilityManagementController::class, 'update'])
            ->name('teacher-availabilities.update');

        Route::delete('teacher-availabilities/{teacher}/{availability}', [TeacherAvailabilityManagementController::class, 'destroy'])
            ->name('teacher-availabilities.destroy');
    });

require __DIR__.'/auth.php';
