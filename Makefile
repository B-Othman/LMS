# ─────────────────────────────────────────────────────────────────────────────
# Securecy LMS — Makefile
# All Docker commands use the dev compose file.
# ─────────────────────────────────────────────────────────────────────────────

COMPOSE := docker compose -f infra/docker/docker-compose.yml
PHP     := $(COMPOSE) exec api php

.DEFAULT_GOAL := help

.PHONY: help up down restart logs \
        migrate seed fresh \
        test-backend test-frontend test \
        lint-backend lint-frontend lint \
        shell shell-db build

# ── Help ──────────────────────────────────────────────────────────────────────
help:
	@echo ""
	@echo "  Securecy LMS — available targets"
	@echo ""
	@echo "  Docker"
	@echo "    make up               Start all services (detached)"
	@echo "    make down             Stop and remove containers"
	@echo "    make restart          down + up"
	@echo "    make logs             Tail all service logs"
	@echo "    make build            Rebuild all Docker images"
	@echo ""
	@echo "  Database"
	@echo "    make migrate          Run pending migrations"
	@echo "    make seed             Run database seeders"
	@echo "    make fresh            Drop all tables, migrate, and seed  ⚠ DEV ONLY"
	@echo ""
	@echo "  Tests"
	@echo "    make test-backend     Run PHP test suite"
	@echo "    make test-frontend    Run JS/TS test suite"
	@echo "    make test             Run all tests"
	@echo ""
	@echo "  Lint"
	@echo "    make lint-backend     Run Laravel Pint"
	@echo "    make lint-frontend    Run ESLint + tsc"
	@echo "    make lint             Run all linters"
	@echo ""
	@echo "  Utilities"
	@echo "    make shell            Open a shell in the api container"
	@echo "    make shell-db         Open psql in the postgres container"
	@echo ""

# ── Docker lifecycle ──────────────────────────────────────────────────────────
up:
	$(COMPOSE) up -d
	@echo "  api    → http://localhost:8000"
	@echo "  web    → http://localhost:3000"
	@echo "  admin  → http://localhost:3001"
	@echo "  MinIO  → http://localhost:9001  (console)"
	@echo "  Mail   → http://localhost:8025  (Mailpit)"

down:
	$(COMPOSE) down

restart: down up

logs:
	$(COMPOSE) logs -f

build:
	$(COMPOSE) build --parallel

# ── Database ──────────────────────────────────────────────────────────────────
migrate:
	$(PHP) artisan migrate --force

seed:
	$(PHP) artisan db:seed

fresh:
	@echo "⚠  This will DESTROY all data. Continue? [y/N] " && read ans && [ $${ans:-N} = y ]
	$(PHP) artisan migrate:fresh --seed

# ── Tests ─────────────────────────────────────────────────────────────────────
test-backend:
	$(COMPOSE) exec api php artisan test

test-frontend:
	pnpm test --if-present

test: test-backend test-frontend

# ── Linters ───────────────────────────────────────────────────────────────────
lint-backend:
	$(COMPOSE) exec api ./vendor/bin/pint

lint-frontend:
	pnpm lint && pnpm type-check

lint: lint-backend lint-frontend

# ── Utilities ─────────────────────────────────────────────────────────────────
shell:
	$(COMPOSE) exec api /bin/sh

shell-db:
	$(COMPOSE) exec postgres psql -U securecy -d securecy_lms
