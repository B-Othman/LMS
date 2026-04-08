# Claude Code — Initial Project Prompt

Use this as your first prompt in Claude Code to bootstrap the project.

---

```
Initialize the Enterprise LMS monorepo for Securecy based on CLAUDE.md and design.md in this repo.

Step 1 — Monorepo scaffold:
- Create the folder structure: apps/web, apps/admin, packages/ui, packages/types, packages/config, backend (with app, routes, database, resources, tests subdirs), infra, docs
- Set up a pnpm workspace (pnpm-workspace.yaml) covering apps/* and packages/*
- Initialize apps/web and apps/admin as Next.js 14+ App Router projects with TypeScript
- Initialize packages/ui as a React component library with TypeScript
- Initialize packages/types for shared TS interfaces/types
- Set up shared Tailwind config in packages/config using the design system tokens from design.md (colors, typography, font = Montserrat via Google Fonts)

Step 2 — Design system foundation (packages/ui):
- Create a tailwind preset that defines the full color palette from design.md (primary, neutral, night, success, warning, error with all shades)
- Create base components: Button (primary/secondary/danger/success variants), Card, Badge (status variants), Input, Label, Table, EmptyState
- All components must use the design tokens — no hardcoded colors
- Export everything from packages/ui/index.ts

Step 3 — Backend scaffold:
- Set up a Laravel 11 project in backend/ (PHP modular monolith)
- Configure PostgreSQL connection in .env.example
- Configure Redis for cache/queue/sessions
- Create initial migrations for: users, roles, permissions, user_roles, tenants, tenant_settings
- Set up API routes file at routes/api.php with /api/v1 prefix
- Create the Auth module: LoginController, RegisterController, auth middleware, User model with tenant scoping

Step 4 — Shared types (packages/types):
- Define TypeScript interfaces matching the API contract: User, Role, Course, Module, Lesson, Enrollment, Quiz, Certificate, PaginatedResponse, ApiError

Step 5 — Apps wiring:
- Both apps/web and apps/admin should import from packages/ui and packages/types
- Set up a typed API client wrapper in packages/config or a shared util
- apps/web: create placeholder routes for /dashboard, /courses, /courses/[id], /certificates
- apps/admin: create placeholder routes for /dashboard, /users, /courses, /courses/[id]/edit, /reports

Use the design system strictly — refer to design.md for all color tokens, typography, and component patterns. Follow CLAUDE.md for all architecture decisions.
```
