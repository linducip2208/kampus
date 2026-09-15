<?php

use App\Http\Controllers\AcademicDocumentController;
use App\Http\Controllers\Admin\ClassScheduleController;
use App\Http\Controllers\Admin\GradeApprovalController;
use App\Http\Controllers\AdminWorkspaceController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\LecturerAttendanceController;
use App\Http\Controllers\LecturerGradeController;
use App\Http\Controllers\LecturerLearningController;
use App\Http\Controllers\LecturerPortalController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\StudentLearningController;
use App\Http\Controllers\StudentQuizController;
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
Route::get('/verify/academic-document/{token}', [AcademicDocumentController::class, 'verify'])->middleware('throttle:60,1')->name('academic-documents.verify');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminWorkspaceController::class)->name('dashboard');
    Route::get('/schedules', [ClassScheduleController::class, 'index'])->name('schedules.index');
    Route::post('/schedules', [ClassScheduleController::class, 'store'])->name('schedules.store');
    Route::put('/schedules/{schedule}', [ClassScheduleController::class, 'update'])->name('schedules.update');
    Route::get('/grades', [GradeApprovalController::class, 'index'])->name('grades.index');
    Route::post('/grades/{grade}/transition', [GradeApprovalController::class, 'transition'])->name('grades.transition');
    Route::post('/grades/revisions/{revision}/review', [GradeApprovalController::class, 'reviewRevision'])->name('grades.revisions.review');
});

Route::middleware('auth')->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [PortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/krs', [PortalController::class, 'krs'])->name('krs');
    Route::get('/academic-record', [PortalController::class, 'academicRecord'])->name('academic-record');
    Route::get('/academic-record/print', [PortalController::class, 'academicRecordPrint'])->name('academic-record.print');
    Route::post('/academic-record/documents', [AcademicDocumentController::class, 'issue'])->name('academic-documents.issue');
    Route::get('/academic-record/documents/{document}', [AcademicDocumentController::class, 'show'])->name('academic-documents.show');
    Route::get('/invoices', [PortalController::class, 'invoices'])->name('invoices');
    Route::get('/attendance', [StudentAttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/{session}', [StudentAttendanceController::class, 'record'])->name('attendance.record');
    Route::get('/learning', [StudentLearningController::class, 'index'])->name('learning.index');
    Route::get('/assignments/{assignment}', [StudentLearningController::class, 'assignment'])->name('assignments.show');
    Route::post('/assignments/{assignment}', [StudentLearningController::class, 'submit'])->name('assignments.submit');
    Route::get('/quizzes/{quiz}', [StudentQuizController::class, 'show'])->name('quizzes.show');
    Route::post('/quizzes/{quiz}/start', [StudentQuizController::class, 'start'])->name('quizzes.start');
    Route::get('/quiz-attempts/{attempt}', [StudentQuizController::class, 'attempt'])->name('quiz-attempts.show');
    Route::post('/quiz-attempts/{attempt}/questions/{question}', [StudentQuizController::class, 'answer'])->name('quiz-attempts.answer');
    Route::post('/quiz-attempts/{attempt}/submit', [StudentQuizController::class, 'submit'])->name('quiz-attempts.submit');    Route::post('/learning/sections/{section}/discussions', [StudentLearningController::class, 'createDiscussion'])->name('discussions.store');
    Route::get('/discussions/{discussion}', [StudentLearningController::class, 'discussion'])->name('discussions.show');
    Route::post('/discussions/{discussion}/replies', [StudentLearningController::class, 'replyDiscussion'])->name('discussions.reply');
});

Route::middleware('auth')->prefix('lecturer')->name('lecturer.')->group(function () {
    Route::get('/', [LecturerPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/schedule', [LecturerPortalController::class, 'schedule'])->name('schedule');
    Route::get('/classes', [LecturerPortalController::class, 'classes'])->name('classes');
    Route::get('/advisees', [LecturerPortalController::class, 'advisees'])->name('advisees');
    Route::get('/attendance', [LecturerAttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/meetings/{meeting}/open', [LecturerAttendanceController::class, 'open'])->name('attendance.open');
    Route::post('/attendance/sessions/{session}/close', [LecturerAttendanceController::class, 'close'])->name('attendance.close');
    Route::post('/attendance/sessions/{session}/record', [LecturerAttendanceController::class, 'record'])->name('attendance.record');
    Route::get('/grades', [LecturerGradeController::class, 'index'])->name('grades.index');
    Route::post('/grades/sections/{section}/configure', [LecturerGradeController::class, 'configure'])->name('grades.configure');
    Route::post('/grades/items/{item}/components/{component}', [LecturerGradeController::class, 'score'])->name('grades.score');
    Route::post('/grades/items/{item}/submit', [LecturerGradeController::class, 'submit'])->name('grades.submit');
    Route::post('/grades/items/{item}/revision', [LecturerGradeController::class, 'requestRevision'])->name('grades.revision');
    Route::get('/learning', [LecturerLearningController::class, 'index'])->name('learning.index');
    Route::post('/learning/sections/{section}/modules', [LecturerLearningController::class, 'module'])->name('learning.modules.store');
    Route::post('/learning/modules/{module}/contents', [LecturerLearningController::class, 'content'])->name('learning.contents.store');
    Route::post('/learning/sections/{section}/assignments', [LecturerLearningController::class, 'assignment'])->name('learning.assignments.store');
    Route::post('/learning/submissions/{submission}/grade', [LecturerLearningController::class, 'grade'])->name('learning.submissions.grade');    Route::post('/learning/question-banks', [LecturerLearningController::class, 'questionBank'])->name('learning.question-banks.store');
    Route::post('/learning/question-banks/{bank}/questions', [LecturerLearningController::class, 'question'])->name('learning.questions.store');
    Route::post('/learning/sections/{section}/quizzes', [LecturerLearningController::class, 'quiz'])->name('learning.quizzes.store');
    Route::post('/learning/quiz-answers/{answer}/grade', [LecturerLearningController::class, 'gradeQuizAnswer'])->name('learning.quiz-answers.grade');    Route::post('/learning/sections/{section}/announcements', [LecturerLearningController::class, 'announcement'])->name('learning.announcements.store');
    Route::post('/learning/sections/{section}/discussions', [LecturerLearningController::class, 'createDiscussion'])->name('discussions.store');
    Route::get('/discussions/{discussion}', [LecturerLearningController::class, 'discussion'])->name('discussions.show');
    Route::post('/discussions/{discussion}/replies', [LecturerLearningController::class, 'replyDiscussion'])->name('discussions.reply');
    Route::post('/discussions/{discussion}/lock', [LecturerLearningController::class, 'lockDiscussion'])->name('discussions.lock');
});
Route::middleware('auth')->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
});
