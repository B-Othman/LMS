# API Documentation Setup

This document explains the API documentation setup for the Securecy LMS backend.

## Overview

The backend provides complete API documentation available through:

1. **Swagger UI** - Interactive documentation at `/api/docs`
2. **JSON OpenAPI Spec** - Machine-readable spec at `/api/docs.json`
3. **Markdown Docs** - Comprehensive guide in `docs/API_DOCUMENTATION.md`

## Quick Start

### 1. View Interactive Documentation

Start the backend server:
```bash
cd backend
php artisan serve
```

Then open your browser and navigate to:
```
http://localhost:8000/api/docs
```

### 2. Try out Endpoints

The Swagger UI provides a "Try it out" feature:
- Click "Try it out" on any endpoint
- Enter parameters and request body
- Click "Execute"
- See the response

### 3. Get the OpenAPI Specification

The machine-readable OpenAPI spec is available at:
```
http://localhost:8000/api/docs.json
```

Use this to:
- Generate client SDKs
- Integrate with tools like Postman, Insomnia
- Generate additional documentation
- Validate API contracts

## API Structure

### Base URL
- **Development:** `http://localhost:8000/api/v1`
- **Production:** `https://api.securecy.com/api/v1`

### Authentication

All endpoints (except public ones) require Bearer token authentication:

```
Authorization: Bearer YOUR_TOKEN_HERE
```

Get a token by logging in:
```bash
POST /auth/login
Content-Type: application/json

{
  "email": "admin@securecy.com",
  "password": "password",
  "tenant_slug": "securecy"
}
```

### Response Format

All responses follow a consistent envelope:

**Success (2xx):**
```json
{
  "data": { /* resource data */ },
  "message": "Operation successful",
  "meta": { /* pagination info if applicable */ }
}
```

**Error (4xx/5xx):**
```json
{
  "message": "Operation failed",
  "errors": [
    {
      "code": "error_code",
      "message": "Human-readable message",
      "field": "field_name" /* optional, for validation errors */
    }
  ]
}
```

## Endpoint Categories

### 📌 Authentication (5 endpoints)
- `POST /auth/login` - Login and get token
- `POST /auth/register` - Register new user
- `POST /auth/forgot-password` - Request password reset
- `POST /auth/reset-password` - Reset password with token
- `POST /auth/logout` - Logout and invalidate token
- `GET /me` - Get current user profile

### 📚 Courses (8 endpoints)
- `GET /courses` - List courses
- `POST /courses` - Create course
- `GET /courses/{id}` - Get course details
- `PUT /courses/{id}` - Update course
- `DELETE /courses/{id}` - Delete course
- `POST /courses/{id}/publish` - Publish course
- `POST /courses/{id}/archive` - Archive course
- `POST /courses/{id}/duplicate` - Duplicate course

### 📑 Modules (5 endpoints)
- `GET /courses/{id}/modules` - List course modules
- `POST /courses/{id}/modules` - Create module
- `PUT /modules/{id}` - Update module
- `DELETE /modules/{id}` - Delete module
- `POST /courses/{id}/modules/reorder` - Reorder modules

### 📖 Lessons (6 endpoints)
- `POST /modules/{id}/lessons` - Create lesson
- `GET /lessons/{id}` - Get lesson details
- `GET /lessons/{id}/content` - Get lesson content
- `PUT /lessons/{id}` - Update lesson
- `DELETE /lessons/{id}` - Delete lesson
- `POST /modules/{id}/lessons/reorder` - Reorder lessons

### 📝 Quizzes (8 endpoints)
- `POST /quizzes` - Create quiz
- `GET /quizzes/{id}` - Get quiz with questions
- `PUT /quizzes/{id}` - Update quiz
- `POST /quizzes/{id}/questions` - Add question
- `PUT /questions/{id}` - Update question
- `DELETE /questions/{id}` - Delete question
- `POST /quizzes/{id}/questions/reorder` - Reorder questions
- `POST /quizzes/{id}/attempts` - Start quiz attempt

### ✅ Quiz Attempts (4 endpoints)
- `POST /quizzes/{id}/attempts` - Start attempt
- `POST /attempts/{id}/submit` - Submit answers
- `GET /attempts/{id}` - Get attempt details
- `GET /my/attempts` - List user's attempts

### 🎖️ Certificates (6 endpoints)
- `GET /my/certificates` - List user's certificates
- `GET /my/certificates/{id}/download` - Download certificate
- `GET /certificates` - List all certificates (admin)
- `GET /certificates/{id}/download` - Download any certificate (admin)
- `POST /certificates/{id}/revoke` - Revoke certificate
- `GET /certificates/verify/{code}` - Verify certificate (public)

### 🎨 Certificate Templates (7 endpoints)
- `GET /certificate-templates` - List templates
- `POST /certificate-templates` - Create template
- `GET /certificate-templates/{id}` - Get template
- `PUT /certificate-templates/{id}` - Update template
- `DELETE /certificate-templates/{id}` - Delete template
- `GET /certificate-templates/{id}/preview` - Preview template
- [Public] `GET /certificates/verify/{code}` - Verify certificate

### 📋 Enrollments (4 endpoints)
- `GET /enrollments` - List enrollments
- `POST /enrollments` - Create enrollment
- `GET /enrollments/{id}` - Get enrollment
- `DELETE /enrollments/{id}` - Delete enrollment

### 👥 Users (5 endpoints)
- `GET /users` - List users
- `POST /users` - Create user
- `GET /users/{id}` - Get user details
- `PUT /users/{id}` - Update user
- `DELETE /users/{id}` - Delete user

### 🔐 Roles (2 endpoints)
- `GET /roles` - List available roles
- `GET /roles/{id}/permissions` - Get role permissions

### 👤 User Roles (1 endpoint)
- `POST /users/{id}/roles` - Assign role to user

### 📁 Media (4 endpoints)
- `POST /media/upload` - Upload file
- `GET /media/{id}` - Get file info
- `GET /media/{id}/download` - Download file
- `DELETE /media/{id}` - Delete file

### 🏷️ Categories (4 endpoints)
- `GET /categories` - List categories
- `POST /categories` - Create category
- `PUT /categories/{id}` - Update category
- `DELETE /categories/{id}` - Delete category

### 🎓 Learner Routes (5 endpoints)
- `GET /my/courses` - List user's courses
- `GET /my/courses/{id}` - Get course detail
- `POST /my/lessons/{id}/start` - Start lesson
- `POST /my/lessons/{id}/complete` - Complete lesson
- `POST /my/lessons/{id}/heartbeat` - Send heartbeat
- `GET /my/enrollments/{id}/progress` - Get enrollment progress

## Permissions and Roles

### Available Roles

| Role | Scope | Permissions |
|------|-------|------------|
| **system_admin** | system | `*` (all) |
| **tenant_admin** | tenant | users.*, roles.*, courses.*, enrollments.*, reports.* |
| **content_manager** | tenant | courses.*, modules.manage, lessons.manage, assessments.manage |
| **instructor** | tenant | courses.view, enrollments.view, assessments.grade |
| **learner** | tenant | courses.view, enrollments.view, certificates.view |

### Default Seed Credentials

After running migrations and seeders:

- **System Admin:** `admin@securecy.com` / `password`
- **Learner:** `learner@securecy.com` / `password`
- **Tenant Slug:** `securecy`

## Rate Limiting

The API implements rate limiting on certain endpoints:

- **Auth endpoints:** 5 requests/minute per IP
- **Public endpoints:** 100 requests/minute per IP
- **Authenticated endpoints:** 1000 requests/minute per user

When rate limited, you'll receive a `429 Too Many Requests` response with headers:
```
X-RateLimit-Limit: 5
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1617985200 (Unix timestamp)
```

## Error Codes

The API returns machine-readable error codes for client handling:

| Code | HTTP | Description |
|------|------|-------------|
| `invalid_credentials` | 401 | Email/password incorrect |
| `account_not_active` | 401 | User account inactive |
| `account_suspended` | 403 | User suspended |
| `permission_denied` | 403 | Insufficient permissions |
| `not_found` | 404 | Resource doesn't exist |
| `validation_error` | 422 | Request validation failed |
| `conflict` | 409 | Resource already exists |
| `rate_limit_exceeded` | 429 | Too many requests |

## Testing Endpoints

### Using cURL

Login:
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@securecy.com",
    "password": "password",
    "tenant_slug": "securecy"
  }'
```

List courses with token:
```bash
curl -X GET http://localhost:8000/api/v1/courses \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Using Postman

1. Open Postman
2. Create new request
3. Set method to POST
4. URL: `http://localhost:8000/api/v1/auth/login`
5. Go to Body tab, select raw JSON
6. Paste login credentials
7. Send - you'll get a token
8. Copy the token
9. For subsequent requests, go to Authorization tab
10. Type: Bearer Token
11. Token: Paste your token
12. The Authorization header will be added automatically

### Using Insomnia

1. Create new request
2. Method: POST
3. URL: `http://localhost:8000/api/v1/auth/login`
4. Body: JSON with login credentials
5. Send
6. Copy token from response
7. For next request, click Auth dropdown
8. Select "Bearer Token"
9. Paste token
10. Make requests

## Testing the Documentation

### Swagger UI Features

The interactive Swagger UI includes:

- **Request/Response examples** - See real-world usage
- **Parameter validation** - Get hints about required fields
- **Try it out** - Execute requests directly from the documentation
- **Authentication** - Add your Bearer token to test authenticated endpoints
- **Filtering** - Search and filter endpoints by tag
- **Schema details** - Click on schema names to see full definitions

### Steps to Test an Endpoint

1. Go to `http://localhost:8000/api/docs`
2. Find the endpoint in the list
3. Click to expand it
4. Click "Try it out"
5. Fill in path parameters if needed
6. Add query parameters or request body
7. For authenticated endpoints, add your Bearer token
8. Click "Execute"
9. View the response

## Accessing the API Programmatically

### With cURL

```bash
# Login
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@securecy.com","password":"password","tenant_slug":"securecy"}' \
  | jq -r '.data.token')

# Use token
curl -X GET http://localhost:8000/api/v1/courses \
  -H "Authorization: Bearer $TOKEN"
```

### With JavaScript/Fetch

```javascript
// Login
const loginResponse = await fetch('http://localhost:8000/api/v1/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'admin@securecy.com',
    password: 'password',
    tenant_slug: 'securecy'
  })
});

const { data: { token } } = await loginResponse.json();

// Request with token
const coursesResponse = await fetch('http://localhost:8000/api/v1/courses', {
  headers: { 'Authorization': `Bearer ${token}` }
});

const courses = await coursesResponse.json();
```

### With Python

```python
import requests

# Login
response = requests.post('http://localhost:8000/api/v1/auth/login', json={
    'email': 'admin@securecy.com',
    'password': 'password',
    'tenant_slug': 'securecy'
})

token = response.json()['data']['token']

# Request with token
headers = {'Authorization': f'Bearer {token}'}
courses = requests.get('http://localhost:8000/api/v1/courses', headers=headers)
print(courses.json())
```

## Generating Client SDKs

The OpenAPI specification can be used to generate client SDKs:

### With OpenAPI Generator

```bash
# Install openapi-generator-cli
npm install @openapitools/openapi-generator-cli -g

# Generate JavaScript client
openapi-generator-cli generate \
  -i http://localhost:8000/api/docs.json \
  -g javascript \
  -o ./api-client

# Generate Python client
openapi-generator-cli generate \
  -i http://localhost:8000/api/docs.json \
  -g python \
  -o ./api-client-python
```

## Troubleshooting

### Cannot access `/api/docs`

1. Make sure the backend server is running (`php artisan serve`)
2. Check the terminal for any errors
3. Verify the base URL is correct (default: `http://localhost:8000`)

### 404 on API endpoints

1. Verify you're using the correct base URL including `/api/v1`
2. Check the endpoint path is spelled correctly
3. Consult the documentation for the correct HTTP method (GET, POST, etc.)

### 401 Unauthorized

1. Your token may be expired (24-hour expiration)
2. Re-authenticate using the login endpoint
3. Ensure you're including the token in the `Authorization` header
4. Check the format is `Bearer TOKEN` (with space)

### 403 Forbidden

1. Your user account doesn't have the required permission
2. Check the endpoint documentation for required permissions
3. Ensure your user has the correct role assigned

### 422 Validation Error

1. Check that all required fields are included
2. Verify the data types match the schema
3. For enums, use the exact values listed in the documentation

## Documentation Files

- `docs/API_DOCUMENTATION.md` - Complete API reference with examples
- `backend/storage/api-docs/swagger.php` - OpenAPI annotation definitions
- `backend/config/l5-swagger.php` - Swagger UI configuration
- `backend/resources/views/swagger/ui.blade.php` - Swagger UI HTML template
- `backend/app/Http/Controllers/SwaggerDocsController.php` - Documentation endpoints

## Next Steps

1. ✅ Review the [API Documentation](API_DOCUMENTATION.md)
2. ✅ Access the interactive Swagger UI at `/api/docs`
3. ✅ Test endpoints using "Try it out" in Swagger UI
4. ✅ Set up client authentication (token management)
5. ✅ Implement error handling in your client
6. ✅ Handle rate limiting with appropriate backoff

## Additional Resources

- [Swagger/OpenAPI Specification](https://swagger.io/specification/)
- [Postman API Client](https://www.postman.com/)
- [Insomnia REST Client](https://insomnia.rest/)
- [OpenAPI Generator](https://openapi-generator.tech/)
