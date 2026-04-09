# Enterprise LMS Platform — CLAUDE.md

## Project Overview
Enterprise Learning Management System for Securecy. Multi-tenant SaaS supporting course delivery, assessments, progress tracking, certificates, and admin controls.

## Quick Start

```bash
# Backend (port 8000)
cd backend && cp .env.example .env   # then configure DB credentials
php artisan migrate && php artisan db:seed
php artisan serve

# Learner app (port 3000)
pnpm dev:web

# Admin app (port 3001)
pnpm dev:admin

# Run backend tests (31 tests, uses PostgreSQL)
cd backend && php artisan test

# Type-check frontend
pnpm type-check
```

**Prerequisites:** PostgreSQL, Redis, PHP 8.2+ (with pdo_pgsql), Node 18+, pnpm 9+

**Default seed accounts:** `admin@securecy.com` / `password` (system admin), `learner@securecy.com` / `password` (learner). Default tenant slug: `securecy`.

## Tech Stack
- **Frontend:** Next.js 14 (App Router) — two apps: `apps/web` (learner), `apps/admin` (admin/instructor)
- **Backend:** Laravel 11 (modular monolith) — REST API under `/api/v1`
- **Database:** PostgreSQL
- **Cache/Queue/Sessions:** Redis (via predis)
- **Auth:** Laravel Sanctum (token-based, 24h expiry)
- **Storage:** S3-compatible object storage for media/files
- **Shared packages:** `packages/ui`, `packages/types`, `packages/config`

## Monorepo Structure
```
apps/web/              — Learner-facing Next.js app
apps/admin/            — Admin/instructor Next.js app
packages/ui/           — Shared React components (design system)
packages/types/        — Shared TypeScript types/contracts
packages/config/       — Shared config (tailwind, tsconfig, postcss, api-client, auth-storage)
backend/
  app/
    Enums/             — UserStatus, TenantStatus, RoleScope
    Exceptions/        — AuthException (typed error codes)
    Http/Controllers/  — Thin controllers (delegate to Services)
    Http/Middleware/    — CheckRole, CheckPermission, ResolveTenantContext, EnsureTenantIsActive
    Http/Requests/     — Form Request validation classes
    Http/Resources/    — API Resource transformers (UserResource, RoleResource, etc.)
    Http/Traits/       — ApiResponse trait (consistent JSON envelope)
    Models/            — Eloquent models + Traits/ (HasRoles, TenantAware)
    Policies/          — UserPolicy, CoursePolicy, EnrollmentPolicy
    Services/          — AuthService, UserService, RoleManagementService
    Support/           — Authorization/PermissionRegistry, Tenancy/ResolvesAuthTenant, TenantContext
  routes/api.php       — All API route definitions
  database/            — Migrations, seeders (PermissionSeeder, RoleSeeder)
  tests/               — Feature tests + Concerns/InteractsWithRbac helper
infra/                 — Docker, nginx, CI configs
docs/                  — Project docs (charter, SDS, SDDS, backlog)
```

## Design System
Read `design.md` for the full token system. Key points:
- **Font:** Montserrat (Google Fonts) — Bold, SemiBold, Medium, Regular, Thin
- **Primary color:** Blue (#3b7ab8 at 500)
- **Brand:** Securecy
- Token format: `{color}-{shade}` (e.g. `primary-500`, `error-50`)
- Typography scale: `text-display`, `text-h1`..`text-h4`, `text-body-lg`/`md`/`sm`, `text-button`, `text-metric`, `text-overline`
- Follow the scale exactly — don't invent sizes

## User Roles & Permissions
Roles have a **scope**: `system` (global, tenant_id=NULL) or `tenant` (per-tenant copy).

| Role | Slug | Scope | Key Permissions |
|------|------|-------|-----------------|
| System Admin | `system_admin` | system | `*` (all) |
| Tenant Admin | `tenant_admin` | tenant | users.*, roles.*, courses.*, enrollments.*, reports.*, certificates.* |
| Content Manager | `content_manager` | tenant | courses.*, modules.manage, lessons.manage, assessments.manage |
| Instructor | `instructor` | tenant | courses.view, enrollments.view, assessments.grade |
| Learner | `learner` | tenant | courses.view, enrollments.view, certificates.view |

Permissions follow `{resource}.{action}` format (e.g. `users.create`, `courses.publish`). Wildcard patterns supported in PermissionRegistry (`courses.*`, `*`).

## Architecture Rules
- Backend owns ALL business rules. Frontend is thin (validation + display only).
- **Thin controllers** — delegate to Services. Controllers use Form Requests for validation and `ApiResponse` trait for responses.
- **Authorization via Policies + middleware**, not scattered in controllers. Use `Gate::authorize()` in controllers, `permission:` and `role:` middleware aliases on routes.
- **Tenant scoping** via `TenantAware` trait (global scope) + `ResolveTenantContext` middleware (sets tenant context from authenticated user or `X-Tenant-ID` header for system admins).
- Background jobs for: email, exports, certificate PDF generation, sync tasks.
- Events/Listeners for side effects (completion → certificate, enrollment → notification).

## API Conventions
- All endpoints under `/api/v1/` (set via `apiPrefix` in bootstrap/app.php)
- RESTful resource routes
- **Success envelope:** `{ data, message? }` or `{ data, message?, meta? }` for paginated
- **Error envelope:** `{ message, errors: [{code, message, field?}] }`
- Machine-readable error codes (e.g. `invalid_credentials`, `account_not_active`, `validation_error`)
- Rate limiting: 5 req/min on `auth/login` and `auth/forgot-password`
- Auth endpoints accept `tenant_id` (int) OR `tenant_slug` (string) for tenant resolution

## Enums
- `UserStatus`: `active`, `inactive`, `suspended` — has `canLogin()` helper
- `TenantStatus`: `active`, `suspended` — has `isActive()` helper
- `RoleScope`: `system`, `tenant`

## Database Conventions
- Use migrations for all schema changes
- All tables include `created_at`, `updated_at`
- Tenant-scoped tables include `tenant_id`
- Users table: unique on `(tenant_id, email)`, soft deletes
- Roles table: unique on `(tenant_id, slug)`, nullable tenant_id for system roles
- Permissions use `code` (not `slug`), format: `{group}.{action}`

## Frontend Conventions
- Use App Router with feature-based folders
- Server Components by default; Client Components only for interactivity
- **API client:** `createApiClient()` from `@securecy/config/api-client` — typed, handles auth token injection, 401/403 callbacks
- **Auth flow:** `@securecy/config/auth-storage` manages token persistence; `@securecy/ui` provides `AuthProvider` and `ProtectedRoute` (checks permissions client-side)
- UI from `packages/ui` — cards, tables, data-table, form controls, badges, empty states, modals, toasts, sidebar, app-shell
- Hide unauthorized nav items in UI but ALWAYS enforce on backend

## Testing Conventions
- Backend uses `RefreshDatabase` trait (tests against real PostgreSQL, not SQLite)
- Use `InteractsWithRbac` trait in tests that need roles/permissions:
  ```php
  use InteractsWithRbac;
  $this->seedRbac();                      // seeds permissions + roles
  $this->assignRole($user, 'learner');    // resolves by slug, respects tenant
  ```
- Authenticate in tests with `Sanctum::actingAs($user)` or real login flow
- phpunit.xml overrides: `CACHE_STORE=array`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync`

## Content Model
- Course → Module → Lesson/Activity → Resource/Assessment
- MVP: native content (video, PDF, text/HTML, quiz)
- Post-MVP: SCORM 1.2 import
- Later: SCORM 2004, xAPI, cmi5

## Key Workflows
1. **Enrollment:** Admin assigns → backend creates enrollment → notification queued → learner sees course
2. **Learning:** Learner opens lesson → progress start → completion updates progress chain
3. **Assessment:** Start quiz → create attempt → store answers → score → pass/fail
4. **Certificate:** Completion + pass rules met → job generates PDF → stored → learner notified
5. **Reporting:** Admin requests export → queued job → file stored → audit logged

## Security
- Hash passwords with bcrypt (Laravel default)
- Sanctum tokens with 24h expiration
- Validate/sanitize file uploads — restrict MIME types and size
- Signed URLs for private file downloads
- Rate limiting on auth and public endpoints
- Audit log all admin actions (role changes, publish, certificate issue, exports)

## Do NOT
- Use microservices — this is a modular monolith
- Put business logic in controllers or frontend
- Skip tenant scoping on multi-tenant queries
- Store media on app servers — use object storage
- Use `any` types in TypeScript — type everything
- Ignore the design system tokens — follow `design.md`
- Use raw `fetch()` in frontend — use the typed API client
- Scatter authorization checks — use Policies and middleware
