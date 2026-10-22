# Sala
#
#   make dev        local stack (with compose.override.yaml)
#   make install    first deploy on a new server
#   make deploy     every deploy after that
#   make doctor     check the server is configured the way you think
#
# Server targets use compose.yaml alone, like CI's staging deploy, so the dev
# override (source mounts, MinIO, Mailpit, a published MySQL port) never
# reaches a server. Every target is safe to re-run. Destructive ones ask first.

SHELL := /bin/bash
DC    := docker compose -f compose.yaml$(shell grep -qE '^PROXY_NETWORK=.+' .env 2>/dev/null && echo ' -f compose.proxy.yaml')
DEV   := docker compose
ART   := $(DC) exec -T app php artisan

# APP_IMAGE set (ghcr.io/...) means CI built the images: pull them. Unset
# means a host without a registry: build here.
define get_images
	@if grep -qE '^APP_IMAGE=.+' .env; then \
		echo "==> Pulling images..."; $(DC) pull; \
	else \
		echo "==> Building images (APP_IMAGE not set)..."; $(DC) build; \
	fi
endef

.DEFAULT_GOAL := help
.PHONY: help dev setup test lint check install deploy migrate doctor backup restore \
        logs shell tinker queue-status down up reset

help:
	@echo "Sala"
	@echo
	@echo "  Local development"
	@echo "    dev            Start the local stack (Vite, MinIO, Mailpit included)"
	@echo "    setup          First run: .env, start, composer setup (demo data)"
	@echo "    check          Lint (Pint, Larastan, ESLint) and test"
	@echo
	@echo "  Server"
	@echo "    install        First deploy: images, key, migrate, roles"
	@echo "    deploy         Back up, pull, migrate, sync roles, restart workers"
	@echo "    doctor         Check the deployment invariants — run after every deploy"
	@echo "    backup         Dump the database and storage to backups/"
	@echo "    restore        Restore a database dump (make restore f=backups/db-....sql.gz)"
	@echo "    logs           Follow logs   (make logs s=worker)"
	@echo "    shell          Shell in the app container"
	@echo "    queue-status   Failed jobs and Horizon state"
	@echo "    down / up      Maintenance mode on / off"
	@echo
	@echo "  Destructive"
	@echo "    reset          Destroy all data and start fresh"

# ----------------------------------------------------------------- dev ---

dev:
	$(DEV) up -d --build

setup:
	@[ -f .env ] || { cp .env.example .env; echo "==> Created .env from .env.example."; }
	$(DEV) up -d --build
	$(DEV) exec app composer setup
	@echo "==> Ready at http://localhost:8080 — sign in as admin@sala.test / password"

test:
	$(DEV) exec app php artisan test

lint:
	$(DEV) exec app composer lint
	$(DEV) exec vite npm run lint

check: lint test

# --------------------------------------------------------------- setup ---

install:
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		echo "==> Created .env from .env.example. Its values are for local development:"; \
		echo "    set APP_ENV, APP_DEBUG=false, APP_URL, DB_PASSWORD, DB_ROOT_PASSWORD,"; \
		echo "    MEDIA_*, MAIL_* and APP_IMAGE / WEB_IMAGE, then run 'make install' again."; \
		exit 1; \
	fi
	@command -v docker >/dev/null 2>&1 || { \
		echo "!!! docker is not installed."; \
		echo "      curl -fsSL https://get.docker.com | sudo sh"; \
		echo "      sudo usermod -aG docker \$$USER   # then log out and back in"; \
		exit 1; \
	}
	@if grep -qE '^APP_ENV=local' .env; then \
		echo "!!! APP_ENV=local in .env. Set production or staging first."; exit 1; \
	fi
	@if grep -qE '^DB_PASSWORD=secret$$' .env; then \
		echo "!!! DB_PASSWORD is still the example value 'secret'."; exit 1; \
	fi
	@# The image has no .env to write to, so key:generate cannot run inside it.
	@if grep -qE '^APP_KEY=\s*$$' .env; then \
		key="base64:$$(openssl rand -base64 32)"; \
		sed -i "s|^APP_KEY=.*|APP_KEY=$$key|" .env; \
		echo "==> Generated APP_KEY."; \
	fi
	$(call get_images)
	$(DC) up -d --wait --remove-orphans
	$(ART) migrate --force
	$(ART) sala:sync-roles
	@# The seeder creates demo accounts whose password is "password".
	@if grep -qE '^APP_ENV=production' .env; then \
		echo "==> Production: no demo data. Create the platform admin with 'make tinker'."; \
	else \
		$(ART) db:seed --force; \
	fi
	@echo
	@echo "==> Installed at $$(grep ^APP_URL .env | cut -d= -f2)"
	@echo "      make doctor   to check it is configured the way you think"

# -------------------------------------------------------------- deploy ---

deploy: backup
	@echo "==> Deploying..."
	git pull --ff-only
	$(call get_images)
	$(DC) up -d --wait --remove-orphans
	@# Down after `up`: the maintenance flag lives in the container, so setting
	@# it on the old one does not survive the recreate. The secret lets whoever
	@# is deploying keep browsing to check the result.
	$(ART) down --render="errors::503" --secret="deploy-check"
	$(ART) migrate --force
	$(ART) sala:sync-roles
	$(ART) up
	@# Horizon holds the old code in memory; supervisord restarts it on exit.
	$(ART) horizon:terminate
	@echo "==> Deployed."
	@$(MAKE) --no-print-directory doctor

migrate:
	$(ART) migrate --force

# -------------------------------------------------------------- doctor ---
#
# One shell invocation held together by line continuations: a `#` comment
# inside it ends the command, so notes inside use `:` instead.

doctor:
	@source .env 2>/dev/null; \
	fail=0; \
	chk() { if [ "$$2" = "ok" ]; then printf "  \033[32m✓\033[0m %s\n" "$$1"; \
	        else printf "  \033[31m✗\033[0m %s — %s\n" "$$1" "$$2"; fail=1; fi; }; \
	echo "==> Checking the deployment (APP_ENV=$$APP_ENV)"; \
	\
	case "$$APP_ENV" in production|staging) chk "APP_ENV is production or staging" ok ;; \
	  *) chk "APP_ENV is production or staging" "it is '$$APP_ENV' — config and routes are not cached" ;; esac; \
	\
	if [ "$$($(ART) tinker --execute='echo config("app.debug") ? "on" : "off";' 2>/dev/null | tr -d '\r\n')" = "off" ]; \
	  then chk "APP_DEBUG is off" ok; else chk "APP_DEBUG is off" "it is ON — stack traces are public"; fi; \
	\
	if [ -n "$$APP_KEY" ]; then chk "APP_KEY is set" ok; else chk "APP_KEY is set" "empty — every request fails"; fi; \
	\
	if $(DC) ps --status running --services 2>/dev/null | grep -qx worker; \
	  then chk "Worker is running" ok; \
	  else chk "Worker is running" "no queued job (mail, PDFs, imports) and no scheduled task will run"; fi; \
	\
	if $(ART) horizon:status 2>/dev/null | grep -qi running; \
	  then chk "Horizon is running" ok; \
	  else chk "Horizon is running" "the worker is up but Horizon is not — see: make logs s=worker"; fi; \
	\
	if [ -n "$$($(DC) port mysql 3306 2>/dev/null)" ]; \
	  then chk "MySQL is not published" "a host port maps to it — is compose.override.yaml on this server?"; \
	  else chk "MySQL is not published" ok; fi; \
	\
	migrations=$$($(ART) migrate:status 2>/dev/null | tr -d '\r'); \
	if echo "$$migrations" | grep -q 'Pending'; \
	  then chk "Migrations are applied" "$$(echo "$$migrations" | grep -c 'Pending') pending — run: make migrate"; \
	elif echo "$$migrations" | grep -q 'Ran'; \
	  then chk "Migrations are applied" ok; \
	  else chk "Migrations are applied" "migrate:status returned nothing — is the database reachable?"; fi; \
	\
	if $(DC) exec -T nginx wget -q -O /dev/null http://127.0.0.1/up 2>/dev/null; \
	  then chk "nginx reaches PHP" ok; \
	  else chk "nginx reaches PHP" "/up inside the nginx container failed — see: make logs s=app"; fi; \
	\
	code=$$(curl -s -o /dev/null -m 10 -w '%{http_code}' "$$APP_URL/up" 2>/dev/null); \
	case "$$code" in \
	  200) chk "$$APP_URL answers" ok ;; \
	  000) chk "$$APP_URL answers" "no answer — DNS record, or the proxy host for it?" ;; \
	  *) chk "$$APP_URL answers" "/up returned $$code — is the proxy forwarding to http://sala-web:80?" ;; \
	esac; \
	\
	echo; \
	if [ $$fail -eq 0 ]; then echo "  All checks passed."; \
	  else echo "  Some checks failed. Each line above says what it costs."; exit 1; fi

# ---------------------------------------------------------------- data ---
#
# Media on S3/R2 is backed up by the provider sync, not here; storage/app is
# only the local disk (MEDIA_DRIVER=local) plus anything else the app writes.

backup:
	@mkdir -p backups
	@ts=$$(date +%Y%m%d-%H%M%S); \
	set -o pipefail; \
	$(DC) exec -T mysql sh -c 'exec mysqldump -uroot -p"$$MYSQL_ROOT_PASSWORD" --single-transaction --routines "$$MYSQL_DATABASE"' \
		| gzip > backups/db-$$ts.sql.gz \
		&& $(DC) exec -T app tar czf - -C storage/app . > backups/storage-$$ts.tgz \
		&& echo "==> Backed up to backups/db-$$ts.sql.gz and backups/storage-$$ts.tgz" \
		|| { echo "!!! Backup failed."; rm -f backups/db-$$ts.sql.gz backups/storage-$$ts.tgz; exit 1; }

restore:
	@[ -n "$(f)" ] && [ -f "$(f)" ] || { echo "Usage: make restore f=backups/db-YYYYMMDD-HHMMSS.sql.gz"; ls -1t backups/db-*.sql.gz 2>/dev/null | head -5; exit 1; }
	@echo "!!! This replaces the database with $(f)."
	@read -p "    Type the database name to confirm: " ans; \
	if [ "$$ans" != "$$(grep ^DB_DATABASE .env | cut -d= -f2)" ]; then echo "==> Aborted."; exit 1; fi; \
	set -o pipefail; \
	gunzip -c "$(f)" | $(DC) exec -T mysql sh -c 'exec mysql -uroot -p"$$MYSQL_ROOT_PASSWORD" "$$MYSQL_DATABASE"' \
		&& echo "==> Restored. Run 'make migrate' if the dump is older than the code."

# ---------------------------------------------------------- day to day ---

logs:
	$(DC) logs -f --tail=200 $(s)

shell:
	$(DC) exec app sh

tinker:
	$(DC) exec app php artisan tinker

queue-status:
	@echo "== failed jobs =="
	@$(ART) queue:failed || true
	@echo
	@echo "== horizon =="
	@$(ART) horizon:status || true

down:
	$(ART) down --render="errors::503"

up:
	$(ART) up

# --------------------------------------------------------- destructive ---

reset:
	@echo "!!! This destroys the database, Redis and stored files (all volumes)."
	@read -p "    Type the database name to confirm: " ans; \
	if [ "$$ans" = "$$(grep ^DB_DATABASE .env | cut -d= -f2)" ]; then \
		set -e; \
		$(DC) down -v; \
		$(DC) up -d --wait; \
		$(ART) migrate --force; \
		$(ART) sala:sync-roles; \
		if ! grep -qE '^APP_ENV=production' .env; then $(ART) db:seed --force; fi; \
		echo "==> Reset."; \
	else \
		echo "==> Aborted."; \
	fi
