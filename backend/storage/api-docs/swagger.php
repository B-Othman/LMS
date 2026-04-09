<?php

/**
 * @OA\OpenApi(
 *     openapi="3.0.0",
 *     info=@OA\Info(
 *         version="1.0.0",
 *         title="Securecy LMS API",
 *         description="Enterprise Learning Management System API Documentation",
 *         contact=@OA\Contact(
 *             email="support@securecy.com"
 *         ),
 *         license=@OA\License(
 *             name="MIT"
 *         )
 *     ),
 *     servers={
 *         @OA\Server(
 *             url="http://localhost:8000/api/v1",
 *             description="Local Development Server"
 *         ),
 *         @OA\Server(
 *             url="https://api.securecy.com/api/v1",
 *             description="Production Server"
 *         )
 *     },
 *     @OA\SecurityScheme(
 *         type="apiKey",
 *         name="Authorization",
 *         in="header",
 *         scheme="Bearer",
 *         securityScheme="sanctum"
 *     )
 * )
 */

/**
 * ============================================================================
 * SCHEMAS / MODELS
 * ============================================================================
 */

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     required={"id", "name", "email", "tenant_id", "status"},
 *     @OA\Property(property="id", type="integer", description="User ID"),
 *     @OA\Property(property="tenant_id", type="integer", description="Tenant ID"),
 *     @OA\Property(property="name", type="string", description="User full name"),
 *     @OA\Property(property="email", type="string", format="email", description="User email"),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"}, description="User status"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="roles", type="array", @OA\Items(ref="#/components/schemas/Role"))
 * )
 */

/**
 * @OA\Schema(
 *     schema="Role",
 *     type="object",
 *     required={"id", "slug", "name", "scope"},
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="tenant_id", type="integer", nullable=true),
 *     @OA\Property(property="slug", type="string", description="Role slug (e.g., system_admin, tenant_admin, instructor, learner)"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="scope", type="string", enum={"system", "tenant"}),
 *     @OA\Property(property="permissions", type="array", @OA\Items(ref="#/components/schemas/Permission"))
 * )
 */

/**
 * @OA\Schema(
 *     schema="Permission",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="code", type="string", description="Permission code format: {resource}.{action}"),
 *     @OA\Property(property="description", type="string")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Course",
 *     type="object",
 *     required={"id", "tenant_id", "title", "description", "status"},
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="tenant_id", type="integer"),
 *     @OA\Property(property="title", type="string"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="status", type="string", enum={"draft", "published", "archived"}),
 *     @OA\Property(property="visibility", type="string", enum={"private", "public"}),
 *     @OA\Property(property="category_id", type="integer"),
 *     @OA\Property(property="certificate_template_id", type="integer", nullable=true),
 *     @OA\Property(property="duration_minutes", type="integer", nullable=true),
 *     @OA\Property(property="modules_count", type="integer"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Module",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="course_id", type="integer"),
 *     @OA\Property(property="title", type="string"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="position", type="integer"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Lesson",
 *     type="object",
 *     required={"id", "module_id", "title", "lesson_type"},
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="module_id", type="integer"),
 *     @OA\Property(property="title", type="string"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="lesson_type", type="string", enum={"content", "quiz", "assignment"}),
 *     @OA\Property(property="position", type="integer"),
 *     @OA\Property(property="duration_minutes", type="integer", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Quiz",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="lesson_id", type="integer", nullable=true),
 *     @OA\Property(property="title", type="string"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="status", type="string", enum={"draft", "published", "archived"}),
 *     @OA\Property(property="passing_score", type="integer", description="Percentage (0-100)"),
 *     @OA\Property(property="duration_minutes", type="integer", nullable=true),
 *     @OA\Property(property="allow_retakes", type="boolean"),
 *     @OA\Property(property="show_correct_answers", type="boolean"),
 *     @OA\Property(property="questions_count", type="integer")
 * )
 */

/**
 * @OA\Schema(
 *     schema="QuizQuestion",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="quiz_id", type="integer"),
 *     @OA\Property(property="question_type", type="string", enum={"multiple_choice", "true_false", "short_answer"}),
 *     @OA\Property(property="content", type="string"),
 *     @OA\Property(property="points", type="integer"),
 *     @OA\Property(property="position", type="integer"),
 *     @OA\Property(property="options", type="array", description="For multiple choice questions", @OA\Items(type="object"))
 * )
 */

/**
 * @OA\Schema(
 *     schema="QuizAttempt",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="quiz_id", type="integer"),
 *     @OA\Property(property="user_id", type="integer"),
 *     @OA\Property(property="status", type="string", enum={"in_progress", "completed", "abandoned"}),
 *     @OA\Property(property="score", type="integer", nullable=true),
 *     @OA\Property(property="passed", type="boolean", nullable=true),
 *     @OA\Property(property="started_at", type="string", format="date-time"),
 *     @OA\Property(property="completed_at", type="string", format="date-time", nullable=true)
 * )
 */

/**
 * @OA\Schema(
 *     schema="Certificate",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="course_id", type="integer"),
 *     @OA\Property(property="user_id", type="integer"),
 *     @OA\Property(property="template_id", type="integer"),
 *     @OA\Property(property="status", type="string", enum={"issued", "revoked"}),
 *     @OA\Property(property="verification_code", type="string"),
 *     @OA\Property(property="issued_at", type="string", format="date-time"),
 *     @OA\Property(property="revoked_at", type="string", format="date-time", nullable=true")
 * )
 */

/**
 * @OA\Schema(
 *     schema="CertificateTemplate",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="tenant_id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="status", type="string", enum={"draft", "published"}),
 *     @OA\Property(property="layout", type="string", enum={"standard", "landscape"}),
 *     @OA\Property(property="background_image_url", type="string", nullable=true),
 *     @OA\Property(property="template_data", type="object", description="Template structure with placeholders"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Enrollment",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="course_id", type="integer"),
 *     @OA\Property(property="user_id", type="integer"),
 *     @OA\Property(property="status", type="string", enum={"active", "completed", "dropped"}),
 *     @OA\Property(property="progress_percentage", type="integer"),
 *     @OA\Property(property="enrolled_at", type="string", format="date-time"),
 *     @OA\Property(property="completed_at", type="string", format="date-time", nullable=true")
 * )
 */

/**
 * @OA\Schema(
 *     schema="ApiResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object"),
 *     @OA\Property(property="message", type="string")
 * )
 */

/**
 * @OA\Schema(
 *     schema="ApiErrorResponse",
 *     type="object",
 *     @OA\Property(property="message", type="string"),
 *     @OA\Property(property="errors", type="array", @OA\Items(type="object",
 *         @OA\Property(property="code", type="string"),
 *         @OA\Property(property="message", type="string"),
 *         @OA\Property(property="field", type="string", nullable=true)
 *     ))
 * )
 */

/**
 * ============================================================================
 * AUTHENTICATION ENDPOINTS
 * ============================================================================
 */

/**
 * @OA\Post(
 *     path="/auth/login",
 *     tags={"Authentication"},
 *     summary="Login user",
 *     description="Authenticate user with email and password. Supports tenant_id or tenant_slug for multi-tenant resolution.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email", "password"},
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="password", type="string"),
 *             @OA\Property(property="tenant_id", type="integer", nullable=true),
 *             @OA\Property(property="tenant_slug", type="string", nullable=true)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Login successful",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="token", type="string"),
 *                 @OA\Property(property="user", ref="#/components/schemas/User")
 *             ),
 *             @OA\Property(property="message", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Invalid credentials",
 *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=429,
 *         description="Too many login attempts (rate limited to 5/minute)"
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/auth/register",
 *     tags={"Authentication"},
 *     summary="Register new user",
 *     description="Create new user account. Requires system_admin or tenant_admin role.",
 *     security={{"sanctum": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name", "email", "password"},
 *             @OA\Property(property="name", type="string"),
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="password", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="User created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", ref="#/components/schemas/User"),
 *             @OA\Property(property="message", type="string")
 *         )
 *     ),
 *     @OA\Response(response=422, description="Validation failed")
 * )
 */

/**
 * @OA\Post(
 *     path="/auth/forgot-password",
 *     tags={"Authentication"},
 *     summary="Request password reset",
 *     description="Send password reset email. Rate limited to 5/minute.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email"},
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="tenant_slug", type="string", nullable=true)
 *         )
 *     ),
 *     @OA\Response(response=200, description="Reset email sent"),
 *     @OA\Response(response=429, description="Rate limited")
 * )
 */

/**
 * @OA\Post(
 *     path="/auth/reset-password",
 *     tags={"Authentication"},
 *     summary="Reset password",
 *     description="Reset user password with reset token from email.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email", "password", "password_confirmation", "token"},
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="password", type="string"),
 *             @OA\Property(property="password_confirmation", type="string"),
 *             @OA\Property(property="token", type="string")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Password reset successfully")
 * )
 */

/**
 * @OA\Post(
 *     path="/auth/logout",
 *     tags={"Authentication"},
 *     summary="Logout user",
 *     description="Invalidate current authentication token.",
 *     security={{"sanctum": {}}},
 *     @OA\Response(response=200, description="Logged out successfully")
 * )
 */

/**
 * @OA\Get(
 *     path="/me",
 *     tags={"Authentication"},
 *     summary="Get current user profile",
 *     description="Retrieve authenticated user's profile with roles and permissions.",
 *     security={{"sanctum": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="User profile",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", ref="#/components/schemas/User"),
 *             @OA\Property(property="message", type="string")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated")
 * )
 */

/**
 * ============================================================================
 * COURSES ENDPOINTS
 * ============================================================================
 */

/**
 * @OA\Get(
 *     path="/courses",
 *     tags={"Courses"},
 *     summary="List courses",
 *     description="Retrieve paginated list of courses. Requires courses.view permission.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"draft", "published", "archived"})),
 *     @OA\Parameter(name="category_id", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="List of courses",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Course")),
 *             @OA\Property(property="meta", type="object",
 *                 @OA\Property(property="total", type="integer"),
 *                 @OA\Property(property="per_page", type="integer"),
 *                 @OA\Property(property="current_page", type="integer")
 *             )
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/courses",
 *     tags={"Courses"},
 *     summary="Create course",
 *     description="Create a new course. Requires courses.create permission.",
 *     security={{"sanctum": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"title", "description"},
 *             @OA\Property(property="title", type="string"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="category_id", type="integer"),
 *             @OA\Property(property="visibility", type="string", enum={"private", "public"}),
 *             @OA\Property(property="duration_minutes", type="integer")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Course created",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", ref="#/components/schemas/Course")
 *         )
 *     ),
 *     @OA\Response(response=422, description="Validation failed")
 * )
 */

/**
 * @OA\Get(
 *     path="/courses/{id}",
 *     tags={"Courses"},
 *     summary="Get course details",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Course details",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", ref="#/components/schemas/Course")
 *         )
 *     )
 * )
 */

/**
 * @OA\Put(
 *     path="/courses/{id}",
 *     tags={"Courses"},
 *     summary="Update course",
 *     description="Update course details. Requires courses.update permission.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="title", type="string"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="category_id", type="integer"),
 *             @OA\Property(property="certificate_template_id", type="integer")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Course updated")
 * )
 */

/**
 * @OA\Delete(
 *     path="/courses/{id}",
 *     tags={"Courses"},
 *     summary="Delete course",
 *     description="Delete a course. Requires courses.delete permission.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Course deleted")
 * )
 */

/**
 * @OA\Post(
 *     path="/courses/{id}/publish",
 *     tags={"Courses"},
 *     summary="Publish course",
 *     description="Publish a course to make it available to learners. Requires courses.publish permission.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Course published")
 * )
 */

/**
 * @OA\Post(
 *     path="/courses/{id}/archive",
 *     tags={"Courses"},
 *     summary="Archive course",
 *     description="Archive a course. Requires courses.publish permission.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Course archived")
 * )
 */

/**
 * @OA\Post(
 *     path="/courses/{id}/duplicate",
 *     tags={"Courses"},
 *     summary="Duplicate course",
 *     description="Create a copy of a course with all modules and lessons. Requires courses.create permission.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=201,
 *         description="Course duplicated",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", ref="#/components/schemas/Course")
 *         )
 *     )
 * )
 */

/**
 * ============================================================================
 * MODULES ENDPOINTS
 * ============================================================================
 */

/**
 * @OA\Get(
 *     path="/courses/{courseId}/modules",
 *     tags={"Modules"},
 *     summary="List course modules",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="courseId", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="List of modules",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Module"))
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/courses/{courseId}/modules",
 *     tags={"Modules"},
 *     summary="Create module",
 *     description="Create a new module in a course. Requires modules.manage permission.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="courseId", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"title"},
 *             @OA\Property(property="title", type="string"),
 *             @OA\Property(property="description", type="string")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Module created")
 * )
 */

/**
 * @OA\Put(
 *     path="/modules/{id}",
 *     tags={"Modules"},
 *     summary="Update module",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="title", type="string"),
 *             @OA\Property(property="description", type="string")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Module updated")
 * )
 */

/**
 * @OA\Delete(
 *     path="/modules/{id}",
 *     tags={"Modules"},
 *     summary="Delete module",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Module deleted")
 * )
 */

/**
 * @OA\Post(
 *     path="/courses/{courseId}/modules/reorder",
 *     tags={"Modules"},
 *     summary="Reorder modules",
 *     description="Update the order of modules within a course.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="courseId", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"modules"},
 *             @OA\Property(property="modules", type="array", @OA\Items(type="object",
 *                 @OA\Property(property="id", type="integer"),
 *                 @OA\Property(property="position", type="integer")
 *             ))
 *         )
 *     ),
 *     @OA\Response(response=200, description="Modules reordered")
 * )
 */

/**
 * ============================================================================
 * LESSONS ENDPOINTS
 * ============================================================================
 */

/**
 * @OA\Post(
 *     path="/modules/{moduleId}/lessons",
 *     tags={"Lessons"},
 *     summary="Create lesson",
 *     description="Create a lesson in a module. Requires lessons.manage permission.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="moduleId", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"title", "lesson_type"},
 *             @OA\Property(property="title", type="string"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="lesson_type", type="string", enum={"content", "quiz", "assignment"}),
 *             @OA\Property(property="duration_minutes", type="integer")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Lesson created")
 * )
 */

/**
 * @OA\Get(
 *     path="/lessons/{id}",
 *     tags={"Lessons"},
 *     summary="Get lesson details",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Lesson details",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", ref="#/components/schemas/Lesson")
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/lessons/{id}/content",
 *     tags={"Lessons"},
 *     summary="Get lesson content",
 *     description="Retrieve lesson's HTML/markdown content and media.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Lesson content",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="content_html", type="string"),
 *                 @OA\Property(property="media", type="array")
 *             )
 *         )
 *     )
 * )
 */

/**
 * @OA\Put(
 *     path="/lessons/{id}",
 *     tags={"Lessons"},
 *     summary="Update lesson",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="title", type="string"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="duration_minutes", type="integer")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Lesson updated")
 * )
 */

/**
 * @OA\Delete(
 *     path="/lessons/{id}",
 *     tags={"Lessons"},
 *     summary="Delete lesson",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Lesson deleted")
 * )
 */

/**
 * @OA\Post(
 *     path="/modules/{moduleId}/lessons/reorder",
 *     tags={"Lessons"},
 *     summary="Reorder lessons",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="moduleId", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"lessons"},
 *             @OA\Property(property="lessons", type="array", @OA\Items(type="object",
 *                 @OA\Property(property="id", type="integer"),
 *                 @OA\Property(property="position", type="integer")
 *             ))
 *         )
 *     ),
 *     @OA\Response(response=200, description="Lessons reordered")
 * )
 */

/**
 * ============================================================================
 * QUIZZES ENDPOINTS
 * ============================================================================
 */

/**
 * @OA\Post(
 *     path="/quizzes",
 *     tags={"Quizzes"},
 *     summary="Create quiz",
 *     description="Create a new quiz. Requires assessments.manage permission.",
 *     security={{"sanctum": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"title"},
 *             @OA\Property(property="title", type="string"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="lesson_id", type="integer", nullable=true),
 *             @OA\Property(property="passing_score", type="integer", description="Percentage 0-100"),
 *             @OA\Property(property="duration_minutes", type="integer"),
 *             @OA\Property(property="allow_retakes", type="boolean"),
 *             @OA\Property(property="show_correct_answers", type="boolean")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Quiz created")
 * )
 */

/**
 * @OA\Get(
 *     path="/quizzes/{id}",
 *     tags={"Quizzes"},
 *     summary="Get quiz details",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Quiz details with questions",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", ref="#/components/schemas/Quiz",
 *                 @OA\Property(property="questions", type="array", @OA\Items(ref="#/components/schemas/QuizQuestion"))
 *             )
 *         )
 *     )
 * )
 */

/**
 * @OA\Put(
 *     path="/quizzes/{id}",
 *     tags={"Quizzes"},
 *     summary="Update quiz",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="title", type="string"),
 *             @OA\Property(property="passing_score", type="integer"),
 *             @OA\Property(property="allow_retakes", type="boolean")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Quiz updated")
 * )
 */

/**
 * @OA\Post(
 *     path="/quizzes/{id}/questions",
 *     tags={"Quizzes"},
 *     summary="Add question to quiz",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"question_type", "content"},
 *             @OA\Property(property="question_type", type="string", enum={"multiple_choice", "true_false", "short_answer"}),
 *             @OA\Property(property="content", type="string"),
 *             @OA\Property(property="points", type="integer"),
 *             @OA\Property(property="options", type="array", @OA\Items(type="object",
 *                 @OA\Property(property="text", type="string"),
 *                 @OA\Property(property="is_correct", type="boolean")
 *             ))
 *         )
 *     ),
 *     @OA\Response(response=201, description="Question added")
 * )
 */

/**
 * @OA\Post(
 *     path="/quizzes/{id}/questions/reorder",
 *     tags={"Quizzes"},
 *     summary="Reorder quiz questions",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"questions"},
 *             @OA\Property(property="questions", type="array", @OA\Items(type="object",
 *                 @OA\Property(property="id", type="integer"),
 *                 @OA\Property(property="position", type="integer")
 *             ))
 *         )
 *     ),
 *     @OA\Response(response=200, description="Questions reordered")
 * )
 */

/**
 * @OA\Put(
 *     path="/questions/{id}",
 *     tags={"Quizzes"},
 *     summary="Update quiz question",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="content", type="string"),
 *             @OA\Property(property="points", type="integer"),
 *             @OA\Property(property="options", type="array")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Question updated")
 * )
 */

/**
 * @OA\Delete(
 *     path="/questions/{id}",
 *     tags={"Quizzes"},
 *     summary="Delete quiz question",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Question deleted")
 * )
 */

/**
 * ============================================================================
 * QUIZ ATTEMPTS ENDPOINTS
 * ============================================================================
 */

/**
 * @OA\Post(
 *     path="/quizzes/{id}/attempts",
 *     tags={"Quiz Attempts"},
 *     summary="Start quiz attempt",
 *     description="Initialize a new quiz attempt for the authenticated learner.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=201,
 *         description="Quiz attempt started",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", ref="#/components/schemas/QuizAttempt")
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/attempts/{id}/submit",
 *     tags={"Quiz Attempts"},
 *     summary="Submit quiz attempt",
 *     description="Submit answers and complete quiz attempt. Calculates score and pass/fail status.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"answers"},
 *             @OA\Property(property="answers", type="array", @OA\Items(type="object",
 *                 @OA\Property(property="question_id", type="integer"),
 *                 @OA\Property(property="answer_text", type="string", nullable=true),
 *                 @OA\Property(property="selected_option_id", type="integer", nullable=true)
 *             ))
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Answers submitted and scored",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", ref="#/components/schemas/QuizAttempt",
 *                 @OA\Property(property="score", type="integer"),
 *                 @OA\Property(property="passed", type="boolean"),
 *                 @OA\Property(property="answers", type="array")
 *             )
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/attempts/{id}",
 *     tags={"Quiz Attempts"},
 *     summary="Get quiz attempt",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Quiz attempt details",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", ref="#/components/schemas/QuizAttempt")
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/my/attempts",
 *     tags={"Quiz Attempts"},
 *     summary="List my quiz attempts",
 *     description="Get paginated list of authenticated user's quiz attempts.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="List of quiz attempts",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/QuizAttempt")),
 *             @OA\Property(property="meta", type="object")
 *         )
 *     )
 * )
 */

/**
 * ============================================================================
 * CERTIFICATES ENDPOINTS
 * ============================================================================
 */

/**
 * @OA\Get(
 *     path="/certificates",
 *     tags={"Certificates"},
 *     summary="List certificates",
 *     description="List issued certificates. Requires certificates.view permission.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="user_id", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="course_id", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="List of certificates",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Certificate"))
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/certificates/{id}/download",
 *     tags={"Certificates"},
 *     summary="Download certificate PDF",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Certificate PDF file"
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/certificates/{id}/revoke",
 *     tags={"Certificates"},
 *     summary="Revoke certificate",
 *     description="Revoke an issued certificate. Requires certificates.issue permission.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="reason", type="string")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Certificate revoked")
 * )
 */

/**
 * @OA\Get(
 *     path="/my/certificates",
 *     tags={"Certificates"},
 *     summary="List my certificates",
 *     description="Get authenticated user's certificates.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="List of user's certificates",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Certificate"))
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/my/certificates/{id}/download",
 *     tags={"Certificates"},
 *     summary="Download my certificate",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Certificate PDF"
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/certificate-templates",
 *     tags={"Certificate Templates"},
 *     summary="List certificate templates",
 *     description="List certificate templates for the tenant. Requires certificates.issue permission.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="List of templates",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CertificateTemplate"))
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/certificate-templates",
 *     tags={"Certificate Templates"},
 *     summary="Create certificate template",
 *     description="Create a new certificate template. Requires certificates.issue permission.",
 *     security={{"sanctum": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name", "layout"},
 *             @OA\Property(property="name", type="string"),
 *             @OA\Property(property="layout", type="string", enum={"standard", "landscape"}),
 *             @OA\Property(property="background_image_url", type="string", nullable=true),
 *             @OA\Property(property="template_data", type="object", description="Custom template structure")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Template created")
 * )
 */

/**
 * @OA\Get(
 *     path="/certificate-templates/{id}",
 *     tags={"Certificate Templates"},
 *     summary="Get certificate template",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Template details",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", ref="#/components/schemas/CertificateTemplate")
 *         )
 *     )
 * )
 */

/**
 * @OA\Put(
 *     path="/certificate-templates/{id}",
 *     tags={"Certificate Templates"},
 *     summary="Update certificate template",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="name", type="string"),
 *             @OA\Property(property="layout", type="string"),
 *             @OA\Property(property="template_data", type="object")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Template updated")
 * )
 */

/**
 * @OA\Delete(
 *     path="/certificate-templates/{id}",
 *     tags={"Certificate Templates"},
 *     summary="Delete certificate template",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Template deleted")
 * )
 */

/**
 * @OA\Get(
 *     path="/certificate-templates/{id}/preview",
 *     tags={"Certificate Templates"},
 *     summary="Preview certificate template",
 *     description="Generate a preview of the certificate template with sample data.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Preview image/PDF"
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/certificates/verify/{verificationCode}",
 *     tags={"Public"},
 *     summary="Verify certificate (public)",
 *     description="Public endpoint to verify a certificate using its verification code.",
 *     @OA\Parameter(name="verificationCode", in="path", required=true, @OA\Schema(type="string")),
 *     @OA\Response(
 *         response=200,
 *         description="Certificate verification result",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="valid", type="boolean"),
 *                 @OA\Property(property="certificate", ref="#/components/schemas/Certificate"),
 *                 @OA\Property(property="user_name", type="string"),
 *                 @OA\Property(property="course_name", type="string")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=404, description="Certificate not found or invalid")
 * )
 */

/**
 * ============================================================================
 * ENROLLMENTS ENDPOINTS
 * ============================================================================
 */

/**
 * @OA\Get(
 *     path="/enrollments",
 *     tags={"Enrollments"},
 *     summary="List enrollments",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="course_id", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="user_id", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"active", "completed", "dropped"})),
 *     @OA\Response(
 *         response=200,
 *         description="List of enrollments",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Enrollment"))
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/enrollments",
 *     tags={"Enrollments"},
 *     summary="Create enrollment",
 *     description="Enroll a user in a course. Requires enrollments.create permission.",
 *     security={{"sanctum": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"user_id", "course_id"},
 *             @OA\Property(property="user_id", type="integer"),
 *             @OA\Property(property="course_id", type="integer")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Enrollment created")
 * )
 */

/**
 * @OA\Get(
 *     path="/enrollments/{id}",
 *     tags={"Enrollments"},
 *     summary="Get enrollment",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Enrollment details",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", ref="#/components/schemas/Enrollment")
 *         )
 *     )
 * )
 */

/**
 * @OA\Delete(
 *     path="/enrollments/{id}",
 *     tags={"Enrollments"},
 *     summary="Delete enrollment",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Enrollment deleted")
 * )
 */

/**
 * ============================================================================
 * LEARNER ROUTES
 * ============================================================================
 */

/**
 * @OA\Get(
 *     path="/my/courses",
 *     tags={"Learner"},
 *     summary="List my courses",
 *     description="Get courses enrolled or authored by the authenticated user.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
 *     @OA\Response(
 *         response=200,
 *         description="List of user's courses",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Course"))
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/my/courses/{courseId}",
 *     tags={"Learner"},
 *     summary="Get my course detail",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="courseId", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Course detail with modules and progress",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer"),
 *                 @OA\Property(property="title", type="string"),
 *                 @OA\Property(property="modules", type="array", @OA\Items(ref="#/components/schemas/Module")),
 *                 @OA\Property(property="enrollment", ref="#/components/schemas/Enrollment")
 *             )
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/my/lessons/{lessonId}/start",
 *     tags={"Learner"},
 *     summary="Start lesson",
 *     description="Mark lesson as started and record the start time.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="lessonId", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Lesson started",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer"),
 *                 @OA\Property(property="started_at", type="string", format="date-time")
 *             )
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/my/lessons/{lessonId}/complete",
 *     tags={"Learner"},
 *     summary="Complete lesson",
 *     description="Mark lesson as completed. May trigger certificate eligibility check.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="lessonId", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Lesson completed",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer"),
 *                 @OA\Property(property="completed", type="boolean")
 *             )
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/my/lessons/{lessonId}/heartbeat",
 *     tags={"Learner"},
 *     summary="Lesson heartbeat",
 *     description="Send heartbeat to track user engagement/session activity.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="lessonId", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Heartbeat recorded")
 * )
 */

/**
 * @OA\Get(
 *     path="/my/enrollments/{enrollmentId}/progress",
 *     tags={"Learner"},
 *     summary="Get enrollment progress",
 *     description="Get detailed progress for a specific enrollment.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="enrollmentId", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Progress details",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="enrollment_id", type="integer"),
 *                 @OA\Property(property="progress_percentage", type="integer"),
 *                 @OA\Property(property="completed_lessons", type="integer"),
 *                 @OA\Property(property="total_lessons", type="integer"),
 *                 @OA\Property(property="modules", type="array")
 *             )
 *         )
 *     )
 * )
 */

/**
 * ============================================================================
 * USERS & ROLES ENDPOINTS
 * ============================================================================
 */

/**
 * @OA\Get(
 *     path="/users",
 *     tags={"Users"},
 *     summary="List users",
 *     description="List users in the tenant. Requires users.view permission.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"active", "inactive", "suspended"})),
 *     @OA\Response(
 *         response=200,
 *         description="List of users",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User"))
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/users",
 *     tags={"Users"},
 *     summary="Create user",
 *     description="Create a new user. Requires users.create permission.",
 *     security={{"sanctum": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name", "email", "password"},
 *             @OA\Property(property="name", type="string"),
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="password", type="string")
 *         )
 *     ),
 *     @OA\Response(response=201, description="User created")
 * )
 */

/**
 * @OA\Get(
 *     path="/users/{id}",
 *     tags={"Users"},
 *     summary="Get user",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="User details",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", ref="#/components/schemas/User")
 *         )
 *     )
 * )
 */

/**
 * @OA\Put(
 *     path="/users/{id}",
 *     tags={"Users"},
 *     summary="Update user",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="name", type="string"),
 *             @OA\Property(property="email", type="string"),
 *             @OA\Property(property="status", type="string")
 *         )
 *     ),
 *     @OA\Response(response=200, description="User updated")
 * )
 */

/**
 * @OA\Delete(
 *     path="/users/{id}",
 *     tags={"Users"},
 *     summary="Delete user",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="User deleted")
 * )
 */

/**
 * @OA\Post(
 *     path="/users/{id}/roles",
 *     tags={"Users"},
 *     summary="Assign role to user",
 *     description="Assign a role to a user. Requires roles.assign permission.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"role_id"},
 *             @OA\Property(property="role_id", type="integer")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Role assigned")
 * )
 */

/**
 * @OA\Get(
 *     path="/roles",
 *     tags={"Roles"},
 *     summary="List roles",
 *     description="List available roles. Requires roles.view permission.",
 *     security={{"sanctum": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="List of roles",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Role"))
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/roles/{id}/permissions",
 *     tags={"Roles"},
 *     summary="Get role permissions",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Role with permissions",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer"),
 *                 @OA\Property(property="name", type="string"),
 *                 @OA\Property(property="permissions", type="array", @OA\Items(ref="#/components/schemas/Permission"))
 *             )
 *         )
 *     )
 * )
 */

/**
 * ============================================================================
 * MEDIA ENDPOINTS
 * ============================================================================
 */

/**
 * @OA\Post(
 *     path="/media/upload",
 *     tags={"Media"},
 *     summary="Upload media file",
 *     description="Upload a media file (video, PDF, image). Requires lessons.manage permission.",
 *     security={{"sanctum": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         content={
 *             "multipart/form-data": @OA\MediaType(
 *                 schema=@OA\Schema(
 *                     type="object",
 *                     required={"file"},
 *                     @OA\Property(property="file", type="string", format="binary"),
 *                     @OA\Property(property="visibility", type="string", enum={"private", "public"})
 *                 )
 *             )
 *         }
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="File uploaded successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer"),
 *                 @OA\Property(property="file_name", type="string"),
 *                 @OA\Property(property="url", type="string"),
 *                 @OA\Property(property="mime_type", type="string"),
 *                 @OA\Property(property="size_bytes", type="integer")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=422, description="Validation failed (invalid file type or size)")
 * )
 */

/**
 * @OA\Get(
 *     path="/media/{id}",
 *     tags={"Media"},
 *     summary="Get media info",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Media details"
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/media/{id}/download",
 *     tags={"Media"},
 *     summary="Download media file",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="File download"
 *     )
 * )
 */

/**
 * @OA\Delete(
 *     path="/media/{id}",
 *     tags={"Media"},
 *     summary="Delete media file",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="File deleted")
 * )
 */

/**
 * ============================================================================
 * CATEGORIES ENDPOINTS
 * ============================================================================
 */

/**
 * @OA\Get(
 *     path="/categories",
 *     tags={"Categories"},
 *     summary="List categories",
 *     security={{"sanctum": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="List of categories",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="array", @OA\Items(type="object",
 *                 @OA\Property(property="id", type="integer"),
 *                 @OA\Property(property="name", type="string"),
 *                 @OA\Property(property="slug", type="string")
 *             ))
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/categories",
 *     tags={"Categories"},
 *     summary="Create category",
 *     security={{"sanctum": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name"},
 *             @OA\Property(property="name", type="string")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Category created")
 * )
 */

/**
 * @OA\Put(
 *     path="/categories/{id}",
 *     tags={"Categories"},
 *     summary="Update category",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="name", type="string")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Category updated")
 * )
 */

/**
 * @OA\Delete(
 *     path="/categories/{id}",
 *     tags={"Categories"},
 *     summary="Delete category",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Category deleted")
 * )
 */
