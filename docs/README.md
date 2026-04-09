# 📚 Securecy LMS API Documentation

Complete API documentation for the Securecy Learning Management System backend.

## 🚀 Quick Start

**Access the interactive documentation here:**
```
http://localhost:8000/api/docs
```

**Get the OpenAPI specification:**
```
http://localhost:8000/api/docs.json
```

## 📋 Documentation Files

### Main Documentation
1. **[SWAGGER_DOCUMENTATION_SUMMARY.md](SWAGGER_DOCUMENTATION_SUMMARY.md)** ⭐ START HERE
   - Overview of what was created
   - How to use the documentation
   - Quick commands and examples
   - Troubleshooting guide
   - **Read time:** 10 minutes

2. **[API_QUICK_REFERENCE.md](API_QUICK_REFERENCE.md)** 🏃 Quick Lookup
   - Common endpoints at a glance
   - Status codes and error codes
   - Default credentials
   - Simple code examples
   - **Read time:** 5 minutes

3. **[API_DOCUMENTATION.md](API_DOCUMENTATION.md)** 📖 Complete Reference
   - All 70+ endpoints documented
   - Detailed request/response examples
   - All data models
   - Rate limiting info
   - Best practices
   - **Read time:** 30+ minutes

4. **[SWAGGER_SETUP.md](SWAGGER_SETUP.md)** 🛠️ Setup & Testing Guide
   - How to access the documentation
   - How to test endpoints
   - Examples for cURL, Postman, Insomnia
   - How to generate client SDKs
   - Detailed troubleshooting
   - **Read time:** 20 minutes

## 📊 What's Documented

### API Coverage
- **70+ endpoints** across 15 categories
- **11 data models** with full schema descriptions
- **Complete request/response examples** for each endpoint
- **Error codes and meanings** for all API responses

### Categories
- **Authentication** (6 endpoints) - Login, register, password reset
- **Courses** (8 endpoints) - Course CRUD operations
- **Modules** (5 endpoints) - Course module management
- **Lessons** (6 endpoints) - Lesson content management
- **Quizzes** (8 endpoints) - Quiz creation and attempts
- **Quiz Attempts** (4 endpoints) - Student quiz interactions
- **Certificates** (6 endpoints) - Certificate management
- **Certificate Templates** (7 endpoints) - Certificate design
- **Enrollments** (4 endpoints) - Student enrollments
- **Users** (5 endpoints) - User management
- **Roles** (2 endpoints) - Role configuration
- **Media** (4 endpoints) - File uploads and downloads
- **Categories** (4 endpoints) - Course categories
- **Learner Routes** (5 endpoints) - Student-specific actions

## 🎯 Choosing What to Read

### I want to...

**...explore the API quickly** → Read [SWAGGER_DOCUMENTATION_SUMMARY.md](SWAGGER_DOCUMENTATION_SUMMARY.md) then use the interactive Swagger UI

**...understand specific endpoints** → Look them up in [API_DOCUMENTATION.md](API_DOCUMENTATION.md)

**...find common endpoints fast** → Use [API_QUICK_REFERENCE.md](API_QUICK_REFERENCE.md)

**...integrate with my frontend** → Follow [SWAGGER_SETUP.md](SWAGGER_SETUP.md) and use the Swagger UI for testing

**...generate a client SDK** → Check [SWAGGER_SETUP.md](SWAGGER_SETUP.md) section on SDK generation

**...test with Postman/Insomnia** → Import the JSON spec from `/api/docs.json` or follow instructions in [SWAGGER_SETUP.md](SWAGGER_SETUP.md)

## 🔧 Access Methods

### 1. **Interactive Swagger UI** (Recommended for Testing)
```
http://localhost:8000/api/docs
```
Features:
- Click and explore endpoints
- "Try it out" to test live
- See responses in real-time
- Add authentication tokens
- Filter by endpoint tag

### 2. **OpenAPI JSON Specification**
```
http://localhost:8000/api/docs.json
```
Use for:
- Importing into Postman/Insomnia
- Generating client SDKs
- Validating API contracts
- Publishing to SwaggerHub

### 3. **Markdown Documentation**
Read offline in any text editor:
- Full reference guides
- Examples and tutorials
- Detailed explanations
- Best practices

## 📖 Reading Guide

### For Beginners
```
1. Start: SWAGGER_DOCUMENTATION_SUMMARY.md (10 min)
2. Explore: http://localhost:8000/api/docs (interactive)
3. Learn: Look up endpoints in API_DOCUMENTATION.md
4. Practice: Test with "Try it out" in Swagger UI
5. Integrate: Follow SWAGGER_SETUP.md for your tool
```

### For Experienced Developers
```
1. Quick look: API_QUICK_REFERENCE.md
2. Import spec: http://localhost:8000/api/docs.json
3. Reference: API_DOCUMENTATION.md as needed
4. Integrate: Use your preferred API client
```

### For API Integration
```
1. Read: SWAGGER_SETUP.md
2. Test: Use Swagger UI or Postman
3. Reference: API_DOCUMENTATION.md
4. Build: Implement client in your language
```

## 🌐 Base URLs

- **Development:** `http://localhost:8000/api/v1`
- **Production:** `https://api.securecy.com/api/v1`

## 🔐 Authentication

All endpoints (except public) require Bearer token:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

### Default Test Credentials
```
Email: admin@securecy.com
Password: password
Tenant: securecy
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

## 🎯 Common Tasks

### Test an Endpoint
```
1. Go to http://localhost:8000/api/docs
2. Find the endpoint
3. Click "Try it out"
4. Fill in required fields
5. Click "Execute"
6. See response
```

### Import into Postman
```
1. Click "Import"
2. Select "Link" tab
3. Paste: http://localhost:8000/api/docs.json
4. Import
5. Start testing
```

### Get Lists of Courses
```bash
# Get token first
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@securecy.com","password":"password","tenant_slug":"securecy"}' \
  | jq -r '.data.token')

# List courses
curl http://localhost:8000/api/v1/courses \
  -H "Authorization: Bearer $TOKEN"
```

### Generate JavaScript Client
```bash
npm install @openapitools/openapi-generator-cli -g

openapi-generator-cli generate \
  -i http://localhost:8000/api/docs.json \
  -g javascript \
  -o ./api-client
```

## 📞 Support

### Documentation Issues
Check the appropriate guide:
- **How to use:** [SWAGGER_SETUP.md](SWAGGER_SETUP.md)
- **Endpoint details:** [API_DOCUMENTATION.md](API_DOCUMENTATION.md)
- **Quick answers:** [API_QUICK_REFERENCE.md](API_QUICK_REFERENCE.md)

### Still have questions?
Consult [SWAGGER_SETUP.md](SWAGGER_SETUP.md) troubleshooting section

## 📚 File Structure

```
docs/
├── README.md                           (this file)
├── SWAGGER_DOCUMENTATION_SUMMARY.md    (overview & quick start)
├── API_DOCUMENTATION.md                (complete reference)
├── API_QUICK_REFERENCE.md              (quick lookup)
├── SWAGGER_SETUP.md                    (setup & testing)
├── API_ENDPOINTS.md                    (endpoint listing)
└── (other project documentation)

backend/
├── app/Http/Controllers/
│   └── SwaggerDocsController.php       (serves documentation)
├── resources/views/swagger/
│   └── ui.blade.php                    (Swagger UI template)
├── config/
│   └── l5-swagger.php                  (L5-Swagger config)
├── storage/api-docs/
│   └── swagger.php                     (OpenAPI annotations)
└── routes/web.php                      (documentation routes)
```

## 🎓 Learning Path

### Level 1: Basics
- Read [SWAGGER_DOCUMENTATION_SUMMARY.md](SWAGGER_DOCUMENTATION_SUMMARY.md)
- Visit http://localhost:8000/api/docs
- Try "Try it out" on 3-4 endpoints

### Level 2: Intermediate
- Read [API_QUICK_REFERENCE.md](API_QUICK_REFERENCE.md)
- Import JSON spec into Postman
- Create a simple test collection

### Level 3: Advanced
- Study specific endpoints in [API_DOCUMENTATION.md](API_DOCUMENTATION.md)
- Implement in your frontend
- Generate client SDK
- Handle errors and rate limiting

## ✨ Features

✅ **Complete API Coverage** - 70+ endpoints documented  
✅ **Interactive Testing** - Try endpoints directly in browser  
✅ **Multiple Access Methods** - UI, JSON spec, Markdown  
✅ **Real-world Examples** - Actual request/response samples  
✅ **OpenAPI Standard** - Industry-standard format  
✅ **Tool Integration** - Works with Postman, Insomnia, etc.  
✅ **SDK Generation** - Generate clients automatically  
✅ **Offline Access** - Read all docs without internet  
✅ **Error Documentation** - All error codes explained  
✅ **Rate Limit Info** - Know your limits  

## 🚀 Next Steps

1. **Start the backend:**
   ```bash
   cd backend
   php artisan serve
   ```

2. **Open the Swagger UI:**
   ```
   http://localhost:8000/api/docs
   ```

3. **Test your first request:**
   - Click on `/auth/login`
   - Click "Try it out"
   - Enter credentials
   - Click "Execute"

4. **Read the full documentation:**
   - Start with [SWAGGER_DOCUMENTATION_SUMMARY.md](SWAGGER_DOCUMENTATION_SUMMARY.md)
   - Check [API_DOCUMENTATION.md](API_DOCUMENTATION.md) for details

5. **Integrate with your app:**
   - Use your preferred API client
   - Handle authentication
   - Implement error handling

## 📄 License

The API documentation is part of the Securecy LMS project.

## 👥 Support

For issues or questions about the API:
1. Check the [Troubleshooting section](SWAGGER_SETUP.md#troubleshooting)
2. Review [API_DOCUMENTATION.md](API_DOCUMENTATION.md) for your endpoint
3. Examine response error codes in error handling section

---

**Last Updated:** April 9, 2026  
**API Version:** 1.0.0  
**Documentation Version:** 1.0.0  

**Quick Links:**
- [Swagger UI](http://localhost:8000/api/docs)
- [JSON Spec](http://localhost:8000/api/docs.json)
- [Summary](SWAGGER_DOCUMENTATION_SUMMARY.md)
- [Complete Reference](API_DOCUMENTATION.md)
- [Quick Lookup](API_QUICK_REFERENCE.md)
- [Setup Guide](SWAGGER_SETUP.md)
