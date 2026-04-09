# API Quick Reference

## Access the API Documentation

**Interactive Swagger UI:**
```
http://localhost:8000/api/docs
```

**OpenAPI JSON Specification:**
```
http://localhost:8000/api/docs.json
```

**Full Documentation:**
- See `docs/API_DOCUMENTATION.md` for complete reference
- See `docs/SWAGGER_SETUP.md` for setup instructions

## Base URL

- Development: `http://localhost:8000/api/v1`
- Production: `https://api.securecy.com/api/v1`

## Authentication

All endpoints (except public ones) require:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

### Get Your Token

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@securecy.com",
    "password": "password",
    "tenant_slug": "securecy"
  }'
```

Response includes:
```json
{
  "data": {
    "token": "YOUR_TOKEN_HERE",
    "user": { ... }
  }
}
```

## Common Endpoints

### Courses
```
GET    /courses                    # List courses
POST   /courses                    # Create course
GET    /courses/{id}               # Get course
PUT    /courses/{id}               # Update course
DELETE /courses/{id}               # Delete course
POST   /courses/{id}/publish       # Publish course
POST   /courses/{id}/archive       # Archive course
```

### Quizzes
```
POST   /quizzes                    # Create quiz
GET    /quizzes/{id}               # Get quiz
POST   /quizzes/{id}/questions     # Add question
POST   /quizzes/{id}/attempts      # Start attempt
POST   /attempts/{id}/submit       # Submit quiz
GET    /my/attempts                # Get my attempts
```

### Certificates
```
GET    /my/certificates            # My certificates
GET    /my/certificates/{id}/download  # Download PDF
GET    /certificates/verify/{code} # Verify cert (public)
```

### Users
```
GET    /users                      # List users
POST   /users                      # Create user
GET    /users/{id}                 # Get user
PUT    /users/{id}                 # Update user
DELETE /users/{id}                 # Delete user
```

### Enrollments
```
GET    /enrollments                # List enrollments
POST   /enrollments                # Create enrollment
GET    /enrollments/{id}           # Get enrollment
DELETE /enrollments/{id}           # Delete enrollment
```

### Learning
```
GET    /my/courses                 # My courses
GET    /my/courses/{id}            # Course detail
POST   /my/lessons/{id}/start      # Start lesson
POST   /my/lessons/{id}/complete   # Complete lesson
GET    /my/enrollments/{id}/progress  # Progress
```

## Default Credentials

After seeding the database:

| User | Email | Password | Tenant |
|------|-------|----------|--------|
| System Admin | admin@securecy.com | password | securecy |
| Learner | learner@securecy.com | password | securecy |

## Response Format

### Success
```json
{
  "data": { /* resource data */ },
  "message": "Operation successful"
}
```

### Error
```json
{
  "message": "Operation failed",
  "errors": [
    {
      "code": "error_code",
      "message": "Human-readable message",
      "field": "field_name"
    }
  ]
}
```

## Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK - Success |
| 201 | Created - Resource created |
| 400 | Bad Request - Invalid input |
| 401 | Unauthorized - Auth required/failed |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource doesn't exist |
| 409 | Conflict - Resource already exists |
| 422 | Unprocessable Entity - Validation error |
| 429 | Too Many Requests - Rate limited |
| 500 | Server Error - Internal error |

## Error Codes

| Code | Status | Meaning |
|------|--------|---------|
| invalid_credentials | 401 | Wrong email/password |
| account_not_active | 401 | Account inactive |
| account_suspended | 403 | Account suspended |
| permission_denied | 403 | User lacks permission |
| not_found | 404 | Resource missing |
| validation_error | 422 | Input validation failed |
| conflict | 409 | Resource exists |
| rate_limit_exceeded | 429 | Too many requests |

## Rate Limits

- Auth endpoints: 5 req/min
- Public endpoints: 100 req/min  
- Authenticated endpoints: 1000 req/min

Check headers:
```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1617985200
```

## Role Permissions

### System Admin
- Scope: system (global)
- Permissions: `*` (all)

### Tenant Admin  
- Scope: tenant
- Can manage users, roles, courses, certificates

### Content Manager
- Scope: tenant
- Can manage course content, modules, lessons, quizzes

### Instructor
- Scope: tenant
- Can view courses, enrollments, grade assessments

### Learner
- Scope: tenant
- Can view courses, certifications, enrollments

## Quick Testing

### In Swagger UI
1. Go to `http://localhost:8000/api/docs`
2. Click on an endpoint
3. Click "Try it out"
4. Fill in values
5. Click "Execute"
6. See response

### With curl
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

### With JavaScript
```javascript
const response = await fetch('http://localhost:8000/api/v1/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'admin@securecy.com',
    password: 'password',
    tenant_slug: 'securecy'
  })
});

const { data: { token } } = await response.json();

const courses = await fetch('http://localhost:8000/api/v1/courses', {
  headers: { 'Authorization': `Bearer ${token}` }
}).then(r => r.json());
```

### With Python
```python
import requests

r = requests.post('http://localhost:8000/api/v1/auth/login', json={
  'email': 'admin@securecy.com',
  'password': 'password',
  'tenant_slug': 'securecy'
})

token = r.json()['data']['token']
courses = requests.get('http://localhost:8000/api/v1/courses',
  headers={'Authorization': f'Bearer {token}'}).json()
```

## Data Models

### User
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "tenant_id": 1,
  "status": "active",
  "roles": []
}
```

### Course
```json
{
  "id": 1,
  "title": "Python Basics",
  "description": "Learn Python",
  "status": "published",
  "visibility": "public",
  "category_id": 1,
  "modules_count": 5
}
```

### Quiz
```json
{
  "id": 1,
  "title": "Quiz 1",
  "status": "published",
  "passing_score": 70,
  "duration_minutes": 30,
  "questions_count": 10
}
```

### Certificate
```json
{
  "id": 1,
  "course_id": 1,
  "user_id": 1,
  "status": "issued",
  "verification_code": "CERT-2026-ABC",
  "issued_at": "2026-04-08T14:00:00Z"
}
```

## Need Help?

- **Full API Reference:** `docs/API_DOCUMENTATION.md`
- **Setup Guide:** `docs/SWAGGER_SETUP.md`
- **Interactive UI:** `http://localhost:8000/api/docs`
- **JSON Spec:** `http://localhost:8000/api/docs.json`

---

**Last Updated:** April 9, 2026  
**API Version:** 1.0.0
