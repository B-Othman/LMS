<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\ScormPlayerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CertificateTemplateController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\MyCourseController;
use App\Http\Controllers\MyCertificateController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\LessonContentController;
use App\Http\Controllers\MyNotificationController;
use App\Http\Controllers\MyNotificationPreferenceController;
use App\Http\Controllers\NotificationTemplateController;
use App\Http\Controllers\PublicCertificateVerificationController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizQuestionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\MyEnrollmentProgressController;
use App\Http\Controllers\MyLessonController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — all routes are prefixed with /api/v1
|--------------------------------------------------------------------------
*/

// Public auth routes (rate-limited)
Route::prefix('auth')->group(function () {
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:5,1');

    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:5,1');

    Route::post('/reset-password', [ResetPasswordController::class, 'store']);
});

Route::get('/certificates/verify/{verificationCode}', [PublicCertificateVerificationController::class, 'show']);

// Authenticated routes
Route::middleware(['auth:sanctum', 'tenant.resolve', 'tenant.active'])->group(function () {
    // Auth
    Route::post('/auth/register', [RegisterController::class, 'store'])
        ->middleware('role:system_admin,tenant_admin');

    Route::post('/auth/logout', [LogoutController::class, 'store']);

    // Profile
    Route::get('/me', [ProfileController::class, 'show']);

    // Learner courses
    Route::get('/my/courses', [MyCourseController::class, 'index'])
        ->middleware('permission:courses.view');
    Route::get('/my/courses/{courseId}', [MyCourseController::class, 'show'])
        ->middleware('permission:courses.view');
    Route::get('/my/certificates', [MyCertificateController::class, 'index'])
        ->middleware('permission:certificates.view');
    Route::get('/my/certificates/{id}/download', [MyCertificateController::class, 'download'])
        ->middleware('permission:certificates.view');
    Route::get('/my/enrollments/{enrollmentId}/progress', [MyEnrollmentProgressController::class, 'show'])
        ->middleware('permission:courses.view');
    Route::post('/my/lessons/{lessonId}/start', [MyLessonController::class, 'start'])
        ->middleware('permission:courses.view');
    Route::post('/my/lessons/{lessonId}/complete', [MyLessonController::class, 'complete'])
        ->middleware('permission:courses.view');
    Route::post('/my/lessons/{lessonId}/heartbeat', [MyLessonController::class, 'heartbeat'])
        ->middleware('permission:courses.view');
    Route::get('/my/attempts', [QuizAttemptController::class, 'index'])
        ->middleware('permission:courses.view');

    // Notifications (learner)
    Route::get('/my/notifications', [MyNotificationController::class, 'index']);
    Route::post('/my/notifications/{id}/read', [MyNotificationController::class, 'markRead']);
    Route::post('/my/notifications/read-all', [MyNotificationController::class, 'markAllRead']);
    Route::get('/my/notifications/unread-count', [MyNotificationController::class, 'unreadCount']);
    Route::get('/my/notification-preferences', [MyNotificationPreferenceController::class, 'index']);
    Route::put('/my/notification-preferences', [MyNotificationPreferenceController::class, 'update']);

    // Notification templates (admin)
    Route::get('/notification-templates', [NotificationTemplateController::class, 'index'])
        ->middleware('permission:settings.manage');
    Route::put('/notification-templates/{id}', [NotificationTemplateController::class, 'update'])
        ->middleware('permission:settings.manage');
    Route::post('/notification-templates/{id}/reset', [NotificationTemplateController::class, 'reset'])
        ->middleware('permission:settings.manage');

    // Roles
    Route::get('/roles', [RoleController::class, 'index'])
        ->middleware('permission:roles.view');
    Route::get('/roles/{id}/permissions', [RoleController::class, 'permissions'])
        ->middleware('permission:roles.view');

    // Users
    Route::get('/users', [UserController::class, 'index'])
        ->middleware('permission:users.view');
    Route::post('/users', [UserController::class, 'store'])
        ->middleware('permission:users.create');
    Route::get('/users/{id}', [UserController::class, 'show'])
        ->middleware('permission:users.view');
    Route::put('/users/{id}', [UserController::class, 'update'])
        ->middleware('permission:users.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])
        ->middleware('permission:users.delete');
    Route::post('/users/{id}/roles', [UserRoleController::class, 'store'])
        ->middleware('permission:roles.assign');

    // Courses
    Route::get('/courses', [CourseController::class, 'index'])
        ->middleware('permission:courses.view');
    Route::post('/courses', [CourseController::class, 'store'])
        ->middleware('permission:courses.create');
    Route::get('/courses/{id}', [CourseController::class, 'show'])
        ->middleware('permission:courses.view');
    Route::put('/courses/{id}', [CourseController::class, 'update'])
        ->middleware('permission:courses.update');
    Route::delete('/courses/{id}', [CourseController::class, 'destroy'])
        ->middleware('permission:courses.delete');
    Route::post('/courses/{id}/publish', [CourseController::class, 'publish'])
        ->middleware('permission:courses.publish');
    Route::post('/courses/{id}/archive', [CourseController::class, 'archive'])
        ->middleware('permission:courses.publish');
    Route::post('/courses/{id}/duplicate', [CourseController::class, 'duplicate'])
        ->middleware('permission:courses.create');

    // Certificate templates
    Route::get('/certificate-templates', [CertificateTemplateController::class, 'index'])
        ->middleware('permission:certificates.issue');
    Route::post('/certificate-templates', [CertificateTemplateController::class, 'store'])
        ->middleware('permission:certificates.issue');
    Route::get('/certificate-templates/{id}', [CertificateTemplateController::class, 'show'])
        ->middleware('permission:certificates.issue');
    Route::put('/certificate-templates/{id}', [CertificateTemplateController::class, 'update'])
        ->middleware('permission:certificates.issue');
    Route::delete('/certificate-templates/{id}', [CertificateTemplateController::class, 'destroy'])
        ->middleware('permission:certificates.issue');
    Route::get('/certificate-templates/{id}/preview', [CertificateTemplateController::class, 'preview'])
        ->middleware('permission:certificates.issue');

    // Certificates
    Route::get('/certificates', [CertificateController::class, 'index'])
        ->middleware('permission:certificates.view');
    Route::get('/certificates/{id}/download', [CertificateController::class, 'download'])
        ->middleware('permission:certificates.view');
    Route::post('/certificates/{id}/revoke', [CertificateController::class, 'revoke'])
        ->middleware('permission:certificates.issue');

    // Modules
    Route::get('/courses/{courseId}/modules', [ModuleController::class, 'index'])
        ->middleware('permission:courses.view');
    Route::post('/courses/{courseId}/modules', [ModuleController::class, 'store'])
        ->middleware('permission:modules.manage');
    Route::put('/modules/{id}', [ModuleController::class, 'update'])
        ->middleware('permission:modules.manage');
    Route::delete('/modules/{id}', [ModuleController::class, 'destroy'])
        ->middleware('permission:modules.manage');
    Route::post('/courses/{courseId}/modules/reorder', [ModuleController::class, 'reorder'])
        ->middleware('permission:modules.manage');

    // Lessons
    Route::post('/modules/{moduleId}/lessons', [LessonController::class, 'store'])
        ->middleware('permission:lessons.manage');
    Route::get('/lessons/{id}/content', [LessonContentController::class, 'show'])
        ->middleware('permission:courses.view');
    Route::get('/lessons/{id}', [LessonController::class, 'show'])
        ->middleware('permission:courses.view');
    Route::put('/lessons/{id}', [LessonController::class, 'update'])
        ->middleware('permission:lessons.manage');
    Route::delete('/lessons/{id}', [LessonController::class, 'destroy'])
        ->middleware('permission:lessons.manage');
    Route::post('/modules/{moduleId}/lessons/reorder', [LessonController::class, 'reorder'])
        ->middleware('permission:lessons.manage');

    // Quizzes
    Route::post('/quizzes', [QuizController::class, 'store'])
        ->middleware('permission:assessments.manage');
    Route::get('/quizzes/{id}', [QuizController::class, 'show'])
        ->middleware('permission:assessments.manage');
    Route::put('/quizzes/{id}', [QuizController::class, 'update'])
        ->middleware('permission:assessments.manage');
    Route::post('/quizzes/{id}/questions', [QuizQuestionController::class, 'store'])
        ->middleware('permission:assessments.manage');
    Route::post('/quizzes/{id}/questions/reorder', [QuizQuestionController::class, 'reorder'])
        ->middleware('permission:assessments.manage');
    Route::put('/questions/{id}', [QuizQuestionController::class, 'update'])
        ->middleware('permission:assessments.manage');
    Route::delete('/questions/{id}', [QuizQuestionController::class, 'destroy'])
        ->middleware('permission:assessments.manage');
    Route::post('/quizzes/{id}/attempts', [QuizAttemptController::class, 'store'])
        ->middleware('permission:courses.view');
    Route::post('/attempts/{id}/submit', [QuizAttemptController::class, 'submit'])
        ->middleware('permission:courses.view');
    Route::get('/attempts/{id}', [QuizAttemptController::class, 'show'])
        ->middleware('permission:courses.view');

    // Media
    Route::post('/media/upload', [MediaController::class, 'upload'])
        ->middleware('permission:lessons.manage');
    Route::get('/media/{id}', [MediaController::class, 'show'])
        ->middleware('permission:courses.view');
    Route::get('/media/{id}/download', [MediaController::class, 'download'])
        ->middleware('permission:courses.view');
    Route::delete('/media/{id}', [MediaController::class, 'destroy'])
        ->middleware('permission:lessons.manage');

    // Enrollments
    Route::get('/enrollments', [EnrollmentController::class, 'index']);
    Route::post('/enrollments', [EnrollmentController::class, 'store'])
        ->middleware('permission:enrollments.create');
    Route::get('/enrollments/{id}', [EnrollmentController::class, 'show']);
    Route::delete('/enrollments/{id}', [EnrollmentController::class, 'destroy'])
        ->middleware('permission:enrollments.delete');

    // Global search
    Route::get('/search', [SearchController::class, 'search']);

    // Audit logs
    Route::middleware('permission:users.view')->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
        Route::get('/audit-logs/{id}', [AuditLogController::class, 'show']);
        Route::get('/users/{userId}/audit-trail', [AuditLogController::class, 'userTrail']);
    });

    // Reports
    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/reports/overview', [ReportController::class, 'overview']);
        Route::get('/reports/completions', [ReportController::class, 'completions']);
        Route::get('/reports/learner-progress', [ReportController::class, 'learnerProgress']);
        Route::get('/reports/assessments', [ReportController::class, 'assessments']);
        Route::get('/reports/assessments/{quizId}/questions', [ReportController::class, 'questionBreakdown']);
        Route::get('/reports/courses/{courseId}/detail', [ReportController::class, 'courseDetail']);
        Route::get('/reports/exports', [ReportExportController::class, 'index']);
        Route::post('/reports/exports', [ReportExportController::class, 'store']);
        Route::get('/reports/exports/{id}', [ReportExportController::class, 'show']);
    });

    // SCORM packages (upload, review, publish)
    Route::post('/courses/{courseId}/packages', [PackageController::class, 'upload'])
        ->middleware('permission:lessons.manage');
    Route::get('/packages/{id}', [PackageController::class, 'show'])
        ->middleware('permission:courses.view');
    Route::post('/packages/{id}/publish', [PackageController::class, 'publish'])
        ->middleware('permission:lessons.manage');
    Route::delete('/packages/{id}', [PackageController::class, 'destroy'])
        ->middleware('permission:lessons.manage');

    // SCORM runtime
    Route::post('/packages/{packageVersionId}/launch', [ScormPlayerController::class, 'launch'])
        ->middleware('permission:courses.view');
    Route::get('/scorm/player/{sessionId}', [ScormPlayerController::class, 'player'])
        ->middleware('permission:courses.view')
        ->name('scorm.player');
    Route::get('/scorm/sessions/{sessionId}/state', [ScormPlayerController::class, 'state'])
        ->middleware('permission:courses.view');
    Route::post('/scorm/sessions/{sessionId}/commit', [ScormPlayerController::class, 'commit'])
        ->middleware('permission:courses.view');
    Route::post('/scorm/sessions/{sessionId}/finish', [ScormPlayerController::class, 'finish'])
        ->middleware('permission:courses.view');
    Route::get('/scorm/assets/{sessionId}/{path}', [ScormPlayerController::class, 'asset'])
        ->middleware('permission:courses.view')
        ->where('path', '.*')
        ->name('scorm.asset');

    // Categories
    Route::get('/categories', [CategoryController::class, 'index'])
        ->middleware('permission:courses.view');
    Route::post('/categories', [CategoryController::class, 'store'])
        ->middleware('permission:courses.create');
    Route::put('/categories/{id}', [CategoryController::class, 'update'])
        ->middleware('permission:courses.update');
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])
        ->middleware('permission:courses.delete');
});
