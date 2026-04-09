<?php

namespace App\Http\Controllers;

use App\Http\Requests\Categories\StoreCategoryRequest;
use App\Http\Requests\Categories\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Course::class);

        $categories = CourseCategory::query()
            ->whereNull('parent_id')
            ->with('children')
            ->withCount('courses')
            ->orderBy('sort_order')
            ->get();

        return $this->success(CategoryResource::collection($categories)->resolve());
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', Course::class);

        $data = $request->validated();
        $slug = $data['slug'] ?? Str::slug($data['name']);

        $category = CourseCategory::create([
            'tenant_id' => $this->tenantContext->tenantId(),
            'name' => $data['name'],
            'slug' => $slug,
            'parent_id' => $data['parent_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $category->loadCount('courses');

        return $this->success(
            new CategoryResource($category),
            'Category created successfully.',
            201,
        );
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $this->authorize('create', Course::class);

        $category = CourseCategory::findOrFail($id);

        $data = $request->validated();

        $category->update(array_filter([
            'name' => $data['name'] ?? null,
            'slug' => $data['slug'] ?? null,
            'parent_id' => array_key_exists('parent_id', $data) ? $data['parent_id'] : null,
            'sort_order' => $data['sort_order'] ?? null,
        ], fn ($v) => $v !== null));

        $category->loadCount('courses');

        return $this->success(
            new CategoryResource($category),
            'Category updated successfully.',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->authorize('create', Course::class);

        $category = CourseCategory::findOrFail($id);

        if ($category->courses()->exists()) {
            return $this->error('Cannot delete a category that has courses.', 422);
        }

        $category->delete();

        return $this->success(message: 'Category deleted successfully.');
    }
}
