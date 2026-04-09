# Securecy LMS API Documentation

**Version:** 1.0.0  
**Base URL:** `http://localhost:8000/api/v1` (Development) | `https://api.securecy.com/api/v1` (Production)

## Table of Contents

1. [Authentication](#authentication)
2. [Endpoints Overview](#endpoints-overview)
3. [Authentication Endpoints](#authentication-endpoints)
4. [Courses](#courses)
5. [Modules & Lessons](#modules--lessons)
6. [Quizzes](#quizzes)
7. [Certificates](#certificates)
8. [Enrollments](#enrollments)
9. [Users & Roles](#users--roles)
10. [Media](#media)
11. [Error Handling](#error-handling)
12. [Rate Limiting](#rate-limiting)
13. [Data Models](#data-models)

## Authentication

The API uses **Laravel Sanctum** for token-based authentication. All authenticated requests must include the Authorization header:

```
Authorization: Bearer YOUR_TOKEN_HERE
```

### Key Points

- Tokens expire after **24 hours**
- Multi-tenant system requires tenant context from authenticated user
- System admins can specify tenant via `X-Tenant-ID` header or `tenant_slug` parameter
- Authentication errors return **401 Unauthorized**

## Endpoints Overview

| Category | Count | Authentication |
|----------|-------|-----------------|
| Authentication | 5 | Public & Protected |
| Courses | 8 | Protected |
| Modules | 5 | Protected |
| Lessons | 6 | Protected |
| Quizzes | 8 | Protected |
| Quiz Attempts | 4 | Protected |
| Certificates | 6 | Protected |
| Certificate Templates | 5 | Protected |
| Enrollments | 4 | Protected |
| Users | 5 | Protected |
| Roles | 2 | Protected |
| Media | 4 | Protected |
| Categories | 4 | Protected |
| Public | 1 | Public |

---

## Authentication Endpoints

### POST `/auth/login`

Authenticate user and receive a bearer token.

**Request:**
```json
{
  "email": "learner@securecy.com",
  "password": "password",
  "tenant_slug": "securecy"
}
```

**Response (200):**
```json
{
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "learner@securecy.com",
      "tenant_id": 1,
      "status": "active",
      "roles": [
        {
          "id": 4,
          "slug": "learner",
          "name": "Learner",
          "scope": "tenant"
        }
      ]
    }
  },
  "message": "Login successful"
}
```

**Rate Limit:** 5 requests per minute  
**Errors:** `401` Invalid credentials, `429` Rate limited

---

### POST `/auth/register`

Create a new user account. Requires `system_admin` or `tenant_admin` role.

**Request:**
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "securepassword"
}
```

**Response (201):**
```json
{
  "data": {
    "id": 5,
    "name": "Jane Doe",
    "email": "jane@example.com",
    "tenant_id": 1,
    "status": "active"
  },
  "message": "User registered successfully"
}
```

---

### POST `/auth/forgot-password`

Request a password reset email.

**Request:**
```json
{
  "email": "user@example.com",
  "tenant_slug": "securecy"
}
```

**Response (200):**
```json
{
  "message": "Password reset email sent"
}
```

**Rate Limit:** 5 requests per minute

---

### POST `/auth/reset-password`

Reset password using token from email.

**Request:**
```json
{
  "email": "user@example.com",
  "password": "newpassword",
  "password_confirmation": "newpassword",
  "token": "reset_token_from_email"
}
```

**Response (200):**
```json
{
  "message": "Password reset successfully"
}
```

---

### POST `/auth/logout`

Invalidate the current authentication token.

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Response (200):**
```json
{
  "message": "Logged out successfully"
}
```

---

### GET `/me`

Get the authenticated user's profile.

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "learner@securecy.com",
    "tenant_id": 1,
    "status": "active",
    "roles": [
      {
        "id": 4,
        "slug": "learner",
        "name": "Learner",
        "scope": "tenant",
        "permissions": [
          { "code": "courses.view", "description": "View courses" },
          { "code": "courses.enroll", "description": "Enroll in courses" }
        ]
      }
    ]
  },
  "message": "Profile retrieved"
}
```

---

## Courses

### GET `/courses`

List all courses with pagination and filtering.

**Query Parameters:**
```
page=1
per_page=15
search=python
status=published (draft|published|archived)
category_id=1
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "tenant_id": 1,
      "title": "Python Fundamentals",
      "description": "Learn Python basics",
      "status": "published",
      "visibility": "public",
      "category_id": 2,
      "duration_minutes": 480,
      "modules_count": 8,
      "created_at": "2026-04-01T10:00:00Z",
      "updated_at": "2026-04-08T15:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 42
  }
}
```

---

### POST `/courses`

Create a new course. Requires `courses.create` permission.

**Request:**
```json
{
  "title": "Advanced JavaScript",
  "description": "Master modern JavaScript patterns",
  "category_id": 2,
  "visibility": "public",
  "duration_minutes": 600
}
```

**Response (201):**
```json
{
  "data": {
    "id": 42,
    "title": "Advanced JavaScript",
    "status": "draft",
    "visibility": "public",
    "category_id": 2,
    "duration_minutes": 600,
    "modules_count": 0
  },
  "message": "Course created successfully"
}
```

---

### GET `/courses/{id}`

Get detailed course information with modules.

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "title": "Python Fundamentals",
    "description": "Learn Python basics",
    "status": "published",
    "modules": [
      {
        "id": 1,
        "title": "Getting Started",
        "description": "Course introduction",
        "position": 1,
        "lessons_count": 3
      }
    ]
  }
}
```

---

### PUT `/courses/{id}`

Update course details. Requires `courses.update` permission.

**Request:**
```json
{
  "title": "Python Fundamentals (Updated)",
  "description": "Updated course description",
  "category_id": 2,
  "certificate_template_id": 1
}
```

**Response (200):**
```json
{
  "data": { "id": 1, "title": "Python Fundamentals (Updated)" },
  "message": "Course updated successfully"
}
```

---

### DELETE `/courses/{id}`

Delete a course. Requires `courses.delete` permission.

**Response (200):**
```json
{
  "message": "Course deleted successfully"
}
```

---

### POST `/courses/{id}/publish`

Publish a course. Requires `courses.publish` permission.

**Response (200):**
```json
{
  "data": { "id": 1, "status": "published" },
  "message": "Course published successfully"
}
```

---

### POST `/courses/{id}/archive`

Archive a course. Requires `courses.publish` permission.

**Response (200):**
```json
{
  "data": { "id": 1, "status": "archived" },
  "message": "Course archived successfully"
}
```

---

### POST `/courses/{id}/duplicate`

Create a copy of the course with all modules and lessons.

**Response (201):**
```json
{
  "data": {
    "id": 43,
    "title": "Python Fundamentals (Copy)",
    "status": "draft"
  },
  "message": "Course duplicated successfully"
}
```

---

## Modules & Lessons

### GET `/courses/{courseId}/modules`

List all modules in a course.

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "course_id": 1,
      "title": "Getting Started",
      "description": "Course introduction",
      "position": 1,
      "lessons_count": 3,
      "created_at": "2026-04-01T10:00:00Z"
    }
  ]
}
```

---

### POST `/courses/{courseId}/modules`

Create a new module. Requires `modules.manage` permission.

**Request:**
```json
{
  "title": "Advanced Topics",
  "description": "Deep dive into advanced concepts"
}
```

**Response (201):**
```json
{
  "data": {
    "id": 2,
    "course_id": 1,
    "title": "Advanced Topics",
    "position": 2
  }
}
```

---

### POST `/courses/{courseId}/modules/reorder`

Reorder modules within a course.

**Request:**
```json
{
  "modules": [
    { "id": 2, "position": 1 },
    { "id": 1, "position": 2 }
  ]
}
```

**Response (200):**
```json
{
  "message": "Modules reordered successfully"
}
```

---

### POST `/modules/{moduleId}/lessons`

Create a lesson in a module. Requires `lessons.manage` permission.

**Request:**
```json
{
  "title": "Introduction to Python",
  "description": "Learn the basics",
  "lesson_type": "content",
  "duration_minutes": 45
}
```

**Response (201):**
```json
{
  "data": {
    "id": 1,
    "module_id": 1,
    "title": "Introduction to Python",
    "lesson_type": "content",
    "position": 1
  }
}
```

---

### GET `/lessons/{id}`

Get lesson details.

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "module_id": 1,
    "title": "Introduction to Python",
    "description": "Learn the basics",
    "lesson_type": "content",
    "duration_minutes": 45,
    "position": 1
  }
}
```

---

### GET `/lessons/{id}/content`

Get lesson content (HTML/markdown and media).

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "title": "Introduction to Python",
    "content_html": "<h2>Welcome to Python</h2><p>Python is...</p>",
    "media": [
      {
        "id": 1,
        "file_name": "intro.mp4",
        "mime_type": "video/mp4",
        "url": "https://s3.example.com/media/intro.mp4"
      }
    ]
  }
}
```

---

### PUT `/lessons/{id}`

Update lesson details. Requires `lessons.manage` permission.

**Request:**
```json
{
  "title": "Introduction to Python (Updated)",
  "duration_minutes": 50
}
```

**Response (200):**
```json
{
  "data": { "id": 1, "title": "Introduction to Python (Updated)" }
}
```

---

### DELETE `/lessons/{id}`

Delete a lesson. Requires `lessons.manage` permission.

**Response (200):**
```json
{
  "message": "Lesson deleted successfully"
}
```

---

## Quizzes

### POST `/quizzes`

Create a new quiz. Requires `assessments.manage` permission.

**Request:**
```json
{
  "title": "Python Basics Quiz",
  "description": "Test your knowledge",
  "lesson_id": 1,
  "passing_score": 70,
  "duration_minutes": 30,
  "allow_retakes": true,
  "show_correct_answers": true
}
```

**Response (201):**
```json
{
  "data": {
    "id": 1,
    "title": "Python Basics Quiz",
    "status": "draft",
    "passing_score": 70,
    "questions_count": 0
  }
}
```

---

### GET `/quizzes/{id}`

Get quiz details with all questions.

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "title": "Python Basics Quiz",
    "status": "published",
    "passing_score": 70,
    "duration_minutes": 30,
    "allow_retakes": true,
    "questions": [
      {
        "id": 1,
        "question_type": "multiple_choice",
        "content": "What is Python?",
        "points": 10,
        "position": 1,
        "options": [
          { "id": 1, "text": "A snake" },
          { "id": 2, "text": "A programming language" },
          { "id": 3, "text": "A fruit" }
        ]
      }
    ]
  }
}
```

---

### POST `/quizzes/{id}/questions`

Add a question to a quiz. Requires `assessments.manage` permission.

**Request:**
```json
{
  "question_type": "multiple_choice",
  "content": "What is a variable?",
  "points": 10,
  "options": [
    { "text": "A named memory location", "is_correct": true },
    { "text": "A function", "is_correct": false },
    { "text": "A constant", "is_correct": false }
  ]
}
```

**Response (201):**
```json
{
  "data": {
    "id": 2,
    "quiz_id": 1,
    "question_type": "multiple_choice",
    "content": "What is a variable?",
    "points": 10
  }
}
```

---

### POST `/quizzes/{id}/attempts`

Start a new quiz attempt.

**Response (201):**
```json
{
  "data": {
    "id": 1,
    "quiz_id": 1,
    "user_id": 1,
    "status": "in_progress",
    "score": null,
    "passed": null,
    "started_at": "2026-04-09T10:00:00Z",
    "completed_at": null
  }
}
```

---

### POST `/attempts/{id}/submit`

Submit quiz answers and get score.

**Request:**
```json
{
  "answers": [
    {
      "question_id": 1,
      "selected_option_id": 2
    },
    {
      "question_id": 2,
      "answer_text": "A named memory location"
    }
  ]
}
```

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "quiz_id": 1,
    "user_id": 1,
    "status": "completed",
    "score": 85,
    "passed": true,
    "completed_at": "2026-04-09T10:15:00Z",
    "answers": [
      {
        "question_id": 1,
        "selected_option_id": 2,
        "is_correct": true,
        "points_earned": 10
      }
    ]
  },
  "message": "Quiz attempt submitted successfully"
}
```

---

### GET `/attempts/{id}`

Get quiz attempt details with answers.

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "quiz_id": 1,
    "user_id": 1,
    "score": 85,
    "passed": true,
    "answers": []
  }
}
```

---

### GET `/my/attempts`

Get current user's quiz attempts (paginated).

**Query Parameters:**
```
page=1
per_page=10
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "quiz_id": 1,
      "score": 85,
      "passed": true,
      "quiz_title": "Python Basics Quiz",
      "completed_at": "2026-04-09T10:15:00Z"
    }
  ],
  "meta": { "current_page": 1, "total": 3 }
}
```

---

## Certificates

### GET `/my/certificates`

Get authenticated user's certificates.

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "course_id": 1,
      "course_title": "Python Fundamentals",
      "status": "issued",
      "verification_code": "CERT-2026-0001ABC",
      "issued_at": "2026-04-08T14:00:00Z",
      "url": "https://certificates.securecy.com/CERT-2026-0001ABC"
    }
  ]
}
```

---

### GET `/my/certificates/{id}/download`

Download certificate as PDF.

**Response (200):**
```
Content-Type: application/pdf
[Binary PDF data]
```

---

### GET `/certificates`

List all certificates (admin). Requires `certificates.view` permission.

**Query Parameters:**
```
page=1
user_id=1
course_id=1
status=issued
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "course_id": 1,
      "user_id": 1,
      "user_name": "John Doe",
      "course_title": "Python Fundamentals",
      "status": "issued",
      "verification_code": "CERT-2026-0001ABC",
      "issued_at": "2026-04-08T14:00:00Z"
    }
  ]
}
```

---

### POST `/certificates/{id}/revoke`

Revoke an issued certificate. Requires `certificates.issue` permission.

**Request:**
```json
{
  "reason": "User request to retake course"
}
```

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "status": "revoked",
    "revoked_at": "2026-04-09T10:00:00Z"
  },
  "message": "Certificate revoked successfully"
}
```

---

### GET `/certificate-templates`

List certificate templates. Requires `certificates.issue` permission.

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "tenant_id": 1,
      "name": "Standard Certificate",
      "status": "published",
      "layout": "landscape",
      "created_at": "2026-04-01T10:00:00Z"
    }
  ]
}
```

---

### POST `/certificate-templates`

Create a new certificate template. Requires `certificates.issue` permission.

**Request:**
```json
{
  "name": "Premium Certificate",
  "layout": "landscape",
  "background_image_url": "https://s3.example.com/cert-bg.jpg",
  "template_data": {
    "title": "Certificate of Completion",
    "subtitle": "{{course_name}}",
    "body": "has successfully completed the course"
  }
}
```

**Response (201):**
```json
{
  "data": {
    "id": 2,
    "name": "Premium Certificate",
    "status": "draft",
    "layout": "landscape"
  }
}
```

---

### GET `/certificate-templates/{id}/preview`

Generate a preview of the certificate template.

**Response (200):**
```
Content-Type: image/png
[Binary image data - screenshot of the certificate]
```

---

### GET `/certificates/verify/{verificationCode}`

**Public endpoint** to verify a certificate.

**Response (200):**
```json
{
  "data": {
    "valid": true,
    "certificate": {
      "id": 1,
      "status": "issued",
      "issued_at": "2026-04-08T14:00:00Z"
    },
    "user_name": "John Doe",
    "course_name": "Python Fundamentals",
    "instructor_name": "Jane Smith"
  }
}
```

**Response (404):**
```json
{
  "message": "Certificate not found",
  "errors": [
    {
      "code": "certificate_not_found",
      "message": "Invalid or expired verification code"
    }
  ]
}
```

---

## Enrollments

### GET `/enrollments`

List enrollments with filtering. Requires appropriate permission.

**Query Parameters:**
```
page=1
course_id=1
user_id=1
status=active (active|completed|dropped)
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "course_id": 1,
      "user_id": 1,
      "user_name": "John Doe",
      "course_title": "Python Fundamentals",
      "status": "active",
      "progress_percentage": 45,
      "enrolled_at": "2026-04-05T10:00:00Z"
    }
  ]
}
```

---

### POST `/enrollments`

Create a new enrollment. Requires `enrollments.create` permission.

**Request:**
```json
{
  "user_id": 1,
  "course_id": 1
}
```

**Response (201):**
```json
{
  "data": {
    "id": 5,
    "course_id": 1,
    "user_id": 1,
    "status": "active",
    "enrolled_at": "2026-04-09T10:00:00Z"
  },
  "message": "User enrolled successfully"
}
```

---

### GET `/enrollments/{id}`

Get enrollment details.

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "course_id": 1,
    "user_id": 1,
    "status": "active",
    "progress_percentage": 45,
    "course": {
      "id": 1,
      "title": "Python Fundamentals",
      "modules_count": 8
    },
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    }
  }
}
```

---

### DELETE `/enrollments/{id}`

Delete an enrollment. Requires `enrollments.delete` permission.

**Response (200):**
```json
{
  "message": "Enrollment deleted successfully"
}
```

---

## Learner Routes

### GET `/my/courses`

Get courses for the authenticated learner.

**Query Parameters:**
```
page=1
search=python
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Python Fundamentals",
      "status": "published",
      "enrollment": {
        "status": "active",
        "progress_percentage": 45
      }
    }
  ]
}
```

---

### GET `/my/courses/{courseId}`

Get detailed course view for learner.

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "title": "Python Fundamentals",
    "modules": [
      {
        "id": 1,
        "title": "Getting Started",
        "lessons": [
          {
            "id": 1,
            "title": "Introduction",
            "lesson_type": "content",
            "status": "not_started"
          }
        ]
      }
    ],
    "enrollment": {
      "progress_percentage": 45,
      "completed_lessons": 4,
      "total_lessons": 9
    }
  }
}
```

---

### POST `/my/lessons/{lessonId}/start`

Mark lesson as started.

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "status": "in_progress",
    "started_at": "2026-04-09T10:00:00Z"
  },
  "message": "Lesson started"
}
```

---

### POST `/my/lessons/{lessonId}/complete`

Mark lesson as completed.

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "status": "completed",
    "completed_at": "2026-04-09T10:45:00Z"
  },
  "message": "Lesson completed successfully"
}
```

---

### POST `/my/lessons/{lessonId}/heartbeat`

Send engagement heartbeat (for tracking session activity).

**Response (200):**
```json
{
  "message": "Heartbeat recorded"
}
```

---

### GET `/my/enrollments/{enrollmentId}/progress`

Get detailed progress for an enrollment.

**Response (200):**
```json
{
  "data": {
    "enrollment_id": 1,
    "course_id": 1,
    "progress_percentage": 45,
    "completed_lessons": 4,
    "total_lessons": 9,
    "modules": [
      {
        "id": 1,
        "title": "Module 1",
        "progress_percentage": 100,
        "lessons": [
          {
            "id": 1,
            "title": "Lesson 1",
            "status": "completed",
            "completed_at": "2026-04-07T10:00:00Z"
          }
        ]
      }
    ]
  }
}
```

---

## Users & Roles

### GET `/users`

List users. Requires `users.view` permission.

**Query Parameters:**
```
page=1
search=john
status=active (active|inactive|suspended)
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "status": "active",
      "roles": [
        {
          "id": 4,
          "slug": "learner",
          "name": "Learner"
        }
      ]
    }
  ]
}
```

---

### POST `/users`

Create a new user. Requires `users.create` permission.

**Request:**
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "secure_password"
}
```

**Response (201):**
```json
{
  "data": {
    "id": 2,
    "name": "Jane Doe",
    "email": "jane@example.com",
    "status": "active"
  }
}
```

---

### GET `/users/{id}`

Get user details. Requires `users.view` permission.

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "tenant_id": 1,
    "status": "active",
    "roles": [
      {
        "id": 4,
        "slug": "learner",
        "name": "Learner",
        "permissions": [
          { "code": "courses.view" },
          { "code": "courses.enroll" }
        ]
      }
    ]
  }
}
```

---

### PUT `/users/{id}`

Update user. Requires `users.update` permission.

**Request:**
```json
{
  "name": "John Doe Updated",
  "status": "inactive"
}
```

**Response (200):**
```json
{
  "data": { "id": 1, "name": "John Doe Updated" }
}
```

---

### DELETE `/users/{id}`

Delete user. Requires `users.delete` permission.

**Response (200):**
```json
{
  "message": "User deleted successfully"
}
```

---

### POST `/users/{id}/roles`

Assign a role to a user. Requires `roles.assign` permission.

**Request:**
```json
{
  "role_id": 3
}
```

**Response (200):**
```json
{
  "message": "Role assigned successfully"
}
```

---

### GET `/roles`

List all available roles. Requires `roles.view` permission.

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "slug": "system_admin",
      "name": "System Administrator",
      "scope": "system",
      "permissions_count": 100
    },
    {
      "id": 2,
      "slug": "tenant_admin",
      "name": "Tenant Administrator",
      "scope": "tenant"
    },
    {
      "id": 3,
      "slug": "content_manager",
      "name": "Content Manager",
      "scope": "tenant"
    },
    {
      "id": 4,
      "slug": "instructor",
      "name": "Instructor",
      "scope": "tenant"
    },
    {
      "id": 5,
      "slug": "learner",
      "name": "Learner",
      "scope": "tenant"
    }
  ]
}
```

---

### GET `/roles/{id}/permissions`

Get all permissions for a role. Requires `roles.view` permission.

**Response (200):**
```json
{
  "data": {
    "id": 4,
    "slug": "instructor",
    "name": "Instructor",
    "permissions": [
      {
        "code": "courses.view",
        "description": "View courses"
      },
      {
        "code": "enrollments.view",
        "description": "View enrollments"
      },
      {
        "code": "assessments.grade",
        "description": "Grade assessments"
      }
    ]
  }
}
```

---

## Media

### POST `/media/upload`

Upload a media file. Requires `lessons.manage` permission.

**Request:**
```
Content-Type: multipart/form-data

file: <binary file data>
visibility: private|public
```

**Response (201):**
```json
{
  "data": {
    "id": 1,
    "file_name": "video.mp4",
    "original_name": "course-intro.mp4",
    "mime_type": "video/mp4",
    "size_bytes": 50000000,
    "url": "https://s3.example.com/media/video.mp4",
    "visibility": "private",
    "created_at": "2026-04-09T10:00:00Z"
  },
  "message": "File uploaded successfully"
}
```

---

### GET `/media/{id}`

Get media file information.

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "file_name": "video.mp4",
    "mime_type": "video/mp4",
    "size_bytes": 50000000,
    "url": "https://s3.example.com/media/video.mp4",
    "visibility": "private",
    "created_at": "2026-04-09T10:00:00Z"
  }
}
```

---

### GET `/media/{id}/download`

Download media file.

**Response (200):**
```
Content-Type: <file mime type>
Content-Disposition: attachment; filename="video.mp4"
[Binary file data]
```

---

### DELETE `/media/{id}`

Delete a media file. Requires `lessons.manage` permission.

**Response (200):**
```json
{
  "message": "File deleted successfully"
}
```

---

## Error Handling

The API uses consistent error responses with machine-readable error codes.

### Error Response Format

```json
{
  "message": "Validation failed",
  "errors": [
    {
      "code": "validation_error",
      "message": "The email field is required",
      "field": "email"
    }
  ]
}
```

### Common Error Codes

| Code | HTTP Status | Description |
|------|:----------:|-------------|
| `invalid_credentials` | 401 | Email/password incorrect |
| `account_not_active` | 401 | User account is inactive |
| `unauthenticated` | 401 | Token missing or invalid |
| `account_suspended` | 403 | User account suspended |
| `permission_denied` | 403 | User lacks permission |
| `not_found` | 404 | Resource doesn't exist |
| `validation_error` | 422 | Request validation failed |
| `conflict` | 409 | Resource already exists |
| `rate_limit_exceeded` | 429 | Too many requests |

---

## Rate Limiting

- **Auth endpoints** (`/auth/login`, `/auth/forgot-password`): 5 requests per minute
- **Public endpoints**: 100 requests per minute per IP
- **Authenticated endpoints**: 1000 requests per minute per user

Rate limit headers:
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1617985200
```

---

## Data Models

### User

```json
{
  "id": 1,
  "tenant_id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "status": "active",
  "email_verified_at": "2026-04-01T10:00:00Z",
  "created_at": "2026-04-01T10:00:00Z",
  "updated_at": "2026-04-01T10:00:00Z"
}
```

### Course

```json
{
  "id": 1,
  "tenant_id": 1,
  "title": "Python Fundamentals",
  "description": "Learn Python basics",
  "status": "published",
  "visibility": "public",
  "category_id": 2,
  "certificate_template_id": 1,
  "duration_minutes": 480,
  "created_at": "2026-04-01T10:00:00Z",
  "updated_at": "2026-04-08T15:30:00Z"
}
```

### Quiz

```json
{
  "id": 1,
  "course_id": 1,
  "lesson_id": 5,
  "title": "Python Basics Quiz",
  "description": "Test your knowledge",
  "status": "published",
  "passing_score": 70,
  "duration_minutes": 30,
  "allow_retakes": true,
  "show_correct_answers": true,
  "created_at": "2026-04-05T10:00:00Z"
}
```

### Certificate

```json
{
  "id": 1,
  "course_id": 1,
  "user_id": 1,
  "template_id": 1,
  "status": "issued",
  "verification_code": "CERT-2026-0001ABC",
  "issued_at": "2026-04-08T14:00:00Z",
  "revoked_at": null
}
```

---

## Best Practices

1. **Always check response status codes** - Success: 200-201, Client error: 4xx, Server error: 5xx
2. **Store tokens securely** - Never log or expose auth tokens
3. **Handle rate limiting** - Check `X-RateLimit-Reset` header
4. **Validate on frontend AND backend** - Never trust client-side validation
5. **Use pagination** - Always implement for large datasets
6. **Cache appropriately** - Use ETags and caching headers
7. **Log errors** - Store error codes and messages for debugging
8. **Retry with backoff** - Use exponential backoff for failed requests

---

## Interactive Documentation

Access the interactive Swagger UI at:
```
http://localhost:8000/api/docs
```

Try out endpoints directly with the "Try it out" feature in Swagger UI!
