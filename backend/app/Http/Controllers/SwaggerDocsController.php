<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SwaggerDocsController extends Controller
{
    /**
     * Serve Swagger UI HTML
     */
    public function ui()
    {
        return response()->view('swagger.ui');
    }

    /**
     * Serve OpenAPI specification (JSON)
     */
    public function json()
    {
        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Securecy LMS API',
                'version' => '1.0.0',
                'description' => 'Enterprise Learning Management System API Documentation',
                'contact' => [
                    'email' => 'support@securecy.com',
                ],
            ],
            'servers' => [
                [
                    'url' => 'http://localhost:8000/api/v1',
                    'description' => 'Development Server',
                ],
                [
                    'url' => 'https://api.securecy.com/api/v1',
                    'description' => 'Production Server',
                ],
            ],
            'components' => [
                'securitySchemes' => [
                    'sanctum' => [
                        'type' => 'apiKey',
                        'name' => 'Authorization',
                        'in' => 'header',
                        'scheme' => 'Bearer',
                    ],
                ],
                'schemas' => $this->getSchemas(),
            ],
            'paths' => $this->getPaths(),
            'tags' => $this->getTags(),
        ];

        return response()->json($spec);
    }

    protected function getSchemas()
    {
        return [
            'User' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'tenant_id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'status' => ['type' => 'string', 'enum' => ['active', 'inactive', 'suspended']],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Course' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'tenant_id' => ['type' => 'integer'],
                    'title' => ['type' => 'string'],
                    'description' => ['type' => 'string'],
                    'status' => ['type' => 'string', 'enum' => ['draft', 'published', 'archived']],
                    'visibility' => ['type' => 'string', 'enum' => ['private', 'public']],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Quiz' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'title' => ['type' => 'string'],
                    'status' => ['type' => 'string', 'enum' => ['draft', 'published']],
                    'passing_score' => ['type' => 'integer'],
                    'duration_minutes' => ['type' => 'integer'],
                ],
            ],
            'Certificate' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'course_id' => ['type' => 'integer'],
                    'user_id' => ['type' => 'integer'],
                    'status' => ['type' => 'string', 'enum' => ['issued', 'revoked']],
                    'verification_code' => ['type' => 'string'],
                    'issued_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Enrollment' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'course_id' => ['type' => 'integer'],
                    'user_id' => ['type' => 'integer'],
                    'status' => ['type' => 'string', 'enum' => ['active', 'completed', 'dropped']],
                    'progress_percentage' => ['type' => 'integer'],
                ],
            ],
            'ApiResponse' => [
                'type' => 'object',
                'properties' => [
                    'data' => ['type' => 'object'],
                    'message' => ['type' => 'string'],
                ],
            ],
            'ApiError' => [
                'type' => 'object',
                'properties' => [
                    'message' => ['type' => 'string'],
                    'errors' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'code' => ['type' => 'string'],
                                'message' => ['type' => 'string'],
                                'field' => ['type' => 'string', 'nullable' => true],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function getPaths()
    {
        return [
            '/auth/login' => [
                'post' => [
                    'tags' => ['Authentication'],
                    'summary' => 'Login user',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['email', 'password'],
                                    'properties' => [
                                        'email' => ['type' => 'string', 'format' => 'email'],
                                        'password' => ['type' => 'string'],
                                        'tenant_id' => ['type' => 'integer', 'nullable' => true],
                                        'tenant_slug' => ['type' => 'string', 'nullable' => true],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Login successful',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'token' => ['type' => 'string'],
                                                    'user' => ['$ref' => '#/components/schemas/User'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '401' => ['description' => 'Invalid credentials'],
                      
                    ],
                ],
            ],
            '/courses' => [
                'get' => [
                    'tags' => ['Courses'],
                    'summary' => 'List courses',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'page',
                            'in' => 'query',
                            'schema' => ['type' => 'integer'],
                        ],
                        [
                            'name' => 'search',
                            'in' => 'query',
                            'schema' => ['type' => 'string'],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'List of courses',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => [
                                                'type' => 'array',
                                                'items' => ['$ref' => '#/components/schemas/Course'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'tags' => ['Courses'],
                    'summary' => 'Create course',
                    'security' => [['sanctum' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['title', 'description'],
                                    'properties' => [
                                        'title' => ['type' => 'string'],
                                        'description' => ['type' => 'string'],
                                        'category_id' => ['type' => 'integer'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => ['description' => 'Course created'],
                    ],
                ],
            ],
        ];
    }

    protected function getTags()
    {
        return [
            ['name' => 'Authentication', 'description' => 'Auth endpoints (login, register, logout)'],
            ['name' => 'Courses', 'description' => 'Course management'],
            ['name' => 'Modules', 'description' => 'Module management'],
            ['name' => 'Lessons', 'description' => 'Lesson management'],
            ['name' => 'Quizzes', 'description' => 'Quiz management and attempts'],
            ['name' => 'Certificates', 'description' => 'Certificate management'],
            ['name' => 'Enrollments', 'description' => 'User enrollments'],
            ['name' => 'Users', 'description' => 'User management'],
            ['name' => 'Media', 'description' => 'Media files (upload, download, delete)'],
            ['name' => 'Learner', 'description' => 'Learner-specific endpoints'],
        ];
    }
}
