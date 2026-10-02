# ==============================================================================
#  Mystery Passenger v2 — perintah pengembangan
#  Jalankan `make` atau `make help` untuk melihat semua target.
# ==============================================================================

SHELL := /bin/bash
.DEFAULT_GOAL := help

DC      := docker compose
PHP     := $(DC) exec -T php
PHP_TTY := $(DC) exec php
ART     := $(PHP) php artisan
COMPOSER:= $(PHP) composer
NPM     := $(PHP) npm

DB_NAME := $(shell grep -E '^DB_DATABASE=' .env 2>/dev/null | cut -d= -f2)
DB_USER := $(shell grep -E '^DB_USERNAME=' .env 2>/dev/null | cut -d= -f2)
DB_PASS := $(shell grep -E '^DB_PASSWORD=' .env 2>/dev/null | cut -d= -f2)
DB_NAME := $(or $(DB_NAME),v2mysterypassenger)
DB_USER := $(or $(DB_USER),mp)
DB_PASS := $(or $(DB_PASS),secret)

STAMP   := $(shell date +%Y%m%d-%H%M%S)

.PHONY: help
help: ## Tampilkan daftar perintah
	@echo ""
	@echo "  Mystery Passenger v2 — Makefile"
	@echo ""
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
	  | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[1;36m%-20s\033[0m %s\n", $$1, $$2}'
	@echo ""

# ── Siklus hidup ──────────────────────────────────────────────────────────────

.PHONY: install
install: ## Setup awal: build, up, composer, .env, key, migrate, seed, build aset
	@test -f .env || cp .env.example .env
	$(DC) build --build-arg UID=$$(id -u) --build-arg GID=$$(id -g)
	$(DC) up -d
	@$(MAKE) --no-print-directory wait-db
	$(COMPOSER) install
	$(ART) key:generate --force
	$(ART) storage:link
	$(ART) migrate --force
	$(ART) db:seed --force
	$(NPM) install
	$(NPM) run build
	$(ART) filament:assets
	@$(MAKE) --no-print-directory perms
	@echo ""
	@echo "  Selesai. Buka http://localhost:$$(grep -E '^APP_PORT=' .env | cut -d= -f2 || echo 8080)/admin"
	@echo "  Buat user admin dengan: make user"
	@echo ""

# ── Aset ──────────────────────────────────────────────────────────────────────
# Pastikan aset frontend (Vite) & aset Filament tersedia. Tanpa ini, halaman
# tampil tanpa CSS/style (public/build & public/css/filament tidak ada pada
# checkout bersih karena keduanya di-gitignore).
# Prasyarat: container php sudah berjalan.
.PHONY: assets
assets: ## Bangun aset Vite + publikasikan aset Filament bila belum ada
	@if [ ! -f public/build/manifest.json ] || [ ! -f public/css/filament/filament/app.css ]; then \
		echo ">> Aset belum lengkap, membangun..."; \
		$(NPM) install --no-audit --no-fund; \
		$(NPM) run build; \
		$(ART) filament:assets; \
		echo ">> Selesai membangun aset."; \
	else \
		echo ">> Aset sudah ada (public/build + public/css/filament)."; \
	fi

.PHONY: up
up: ## Nyalakan semua service lalu pastikan aset terbangun
	$(DC) up -d
	@$(MAKE) --no-print-directory assets
	@$(MAKE) --no-print-directory ps

.PHONY: up-build
up-build: ## Build ulang image lalu nyalakan
	$(DC) up -d --build
	@$(MAKE) --no-print-directory assets
	@$(MAKE) --no-print-directory ps

.PHONY: dev
dev: ## Nyalakan semua service + Vite dev server (HMR)
	$(DC) --profile dev up -d
	@echo "Vite: http://localhost:$$(grep -E '^VITE_PORT=' .env | cut -d= -f2 || echo 5173)"

.PHONY: down
down: ## Matikan semua service
	$(DC) --profile dev down

.PHONY: down-v
down-v: ## Matikan service DAN hapus volume (DATA DATABASE HILANG)
	@read -p "Hapus volume termasuk data MySQL? Ketik 'ya' untuk lanjut: " c; \
	 if [ "$$c" = "ya" ]; then $(DC) --profile dev down -v; else echo "Dibatalkan."; fi

.PHONY: restart
restart: ## Restart semua service
	$(DC) restart

.PHONY: restart-php
restart-php: ## Restart hanya php, queue, dan scheduler
	$(DC) restart php queue scheduler

.PHONY: restart-nginx
restart-nginx: ## Restart hanya nginx
	$(DC) restart nginx

.PHONY: stop
stop: ## Hentikan container tanpa menghapusnya
	$(DC) stop

.PHONY: ps
ps: ## Status container
	$(DC) ps

# ── Shell & log ───────────────────────────────────────────────────────────────

.PHONY: assets-check
assets-check: ## Peringatkan bila aset frontend/Filament belum ada
	@if [ ! -f public/build/manifest.json ]; then \
		echo ""; \
		echo "  PERINGATAN: public/build/manifest.json tidak ada → halaman akan tampil"; \
		echo "  TANPA CSS. Jalankan: make assets   (atau make up)"; \
		echo ""; \
	fi
	@if [ ! -f public/css/filament/filament/app.css ]; then \
		echo ""; \
		echo "  PERINGATAN: aset Filament belum dipublikasikan → panel admin TANPA CSS."; \
		echo "  Jalankan: make assets"; \
		echo ""; \
	fi

.PHONY: shell
shell: assets-check ## Masuk shell container php (user www-data)
	$(PHP_TTY) bash

.PHONY: shell-root
shell-root: ## Masuk shell container php sebagai root
	$(DC) exec -u root php bash

.PHONY: shell-nginx
shell-nginx: ## Masuk shell container nginx
	$(DC) exec nginx sh

.PHONY: logs
logs: ## Ikuti log semua service
	$(DC) logs -f --tail=100

.PHONY: logs-php
logs-php: ## Ikuti log php-fpm
	$(DC) logs -f --tail=100 php

.PHONY: logs-nginx
logs-nginx: ## Ikuti log nginx
	$(DC) logs -f --tail=100 nginx

.PHONY: logs-queue
logs-queue: ## Ikuti log queue worker
	$(DC) logs -f --tail=100 queue

.PHONY: logs-app
logs-app: ## Ikuti laravel.log lewat Pail
	$(ART) pail --timeout=0

# ── Database ──────────────────────────────────────────────────────────────────

.PHONY: db
db: ## Buka shell MySQL interaktif
	$(DC) exec mysql mysql -u"$(DB_USER)" -p"$(DB_PASS)" "$(DB_NAME)"

.PHONY: db-root
db-root: ## Buka shell MySQL sebagai root
	$(DC) exec mysql mysql -uroot -p"$$(grep -E '^DB_ROOT_PASSWORD=' .env | cut -d= -f2)"

.PHONY: wait-db
wait-db: ## Tunggu sampai MySQL siap menerima koneksi
	@echo -n "Menunggu MySQL"
	@for i in $$(seq 1 60); do \
	   if $(DC) exec -T mysql mysqladmin ping -h127.0.0.1 --silent >/dev/null 2>&1; then echo " siap."; exit 0; fi; \
	   echo -n "."; sleep 1; \
	 done; echo " timeout."; exit 1

.PHONY: migrate
migrate: ## Jalankan migration yang belum dijalankan
	$(ART) migrate

.PHONY: migrate-status
migrate-status: ## Lihat status migration
	$(ART) migrate:status

.PHONY: rollback
rollback: ## Rollback batch migration terakhir
	$(ART) migrate:rollback

.PHONY: fresh
fresh: ## DROP semua tabel, migrate ulang, lalu seed (DATA HILANG)
	@read -p "Hapus semua tabel di $(DB_NAME) lalu migrate+seed? Ketik 'ya': " c; \
	 if [ "$$c" = "ya" ]; then $(ART) migrate:fresh --seed; else echo "Dibatalkan."; fi

.PHONY: seed
seed: ## Jalankan seeder
	$(ART) db:seed

.PHONY: dump
dump: ## Dump database ke storage/backups/
	$(PHP) mkdir -p storage/backups
	$(DC) exec -T mysql mysqldump -u"$(DB_USER)" -p"$(DB_PASS)" \
	  --single-transaction --routines --triggers "$(DB_NAME)" \
	  > storage/backups/$(DB_NAME)-$(STAMP).sql
	@echo "Tersimpan: storage/backups/$(DB_NAME)-$(STAMP).sql"

.PHONY: restore
restore: ## Restore database dari file: make restore FILE=storage/backups/x.sql
	@test -n "$(FILE)" || { echo "Gunakan: make restore FILE=storage/backups/namafile.sql"; exit 1; }
	@test -f "$(FILE)" || { echo "File tidak ditemukan: $(FILE)"; exit 1; }
	@read -p "Timpa database $(DB_NAME) dengan $(FILE)? Ketik 'ya': " c; \
	 if [ "$$c" = "ya" ]; then \
	   $(DC) exec -T mysql mysql -u"$(DB_USER)" -p"$(DB_PASS)" "$(DB_NAME)" < "$(FILE)" && echo "Selesai."; \
	 else echo "Dibatalkan."; fi

.PHONY: tinker
tinker: ## Buka REPL Tinker
	$(PHP_TTY) php artisan tinker

# ── Artisan, Composer, npm ────────────────────────────────────────────────────

.PHONY: artisan
artisan: ## Jalankan artisan: make artisan CMD="make:model Foo -m"
	@test -n "$(CMD)" || { echo 'Gunakan: make artisan CMD="route:list"'; exit 1; }
	$(PHP_TTY) php artisan $(CMD)

.PHONY: composer
composer: ## Jalankan composer: make composer CMD="require vendor/paket"
	@test -n "$(CMD)" || { echo 'Gunakan: make composer CMD="install"'; exit 1; }
	$(PHP_TTY) composer $(CMD)

.PHONY: npm
npm: ## Jalankan npm: make npm CMD="install paket"
	@test -n "$(CMD)" || { echo 'Gunakan: make npm CMD="install"'; exit 1; }
	$(PHP_TTY) npm $(CMD)

.PHONY: build
build: ## Build aset frontend untuk produksi
	$(NPM) run build

.PHONY: watch
watch: ## Jalankan Vite dev server di foreground (Ctrl+C untuk berhenti)
	$(PHP_TTY) npm run dev -- --host 0.0.0.0

# ── Filament ──────────────────────────────────────────────────────────────────

.PHONY: user
user: ## Buat user panel Filament secara interaktif
	$(PHP_TTY) php artisan make:filament-user

.PHONY: filament-upgrade
filament-upgrade: ## Jalankan ulang upgrade/optimize aset Filament
	$(ART) filament:optimize-clear
	$(ART) filament:assets
	$(ART) filament:optimize

# ── Antrean & jadwal ──────────────────────────────────────────────────────────

.PHONY: queue
queue: ## Jalankan queue worker di foreground (untuk debugging)
	$(PHP_TTY) php artisan queue:work --tries=3 --timeout=900 -vv

.PHONY: queue-restart
queue-restart: ## Minta queue worker memuat ulang kode
	$(ART) queue:restart

.PHONY: queue-failed
queue-failed: ## Daftar job yang gagal
	$(ART) queue:failed

.PHONY: queue-retry
queue-retry: ## Ulangi semua job yang gagal
	$(ART) queue:retry all

.PHONY: schedule
schedule: ## Jalankan scheduler sekali (debug)
	$(ART) schedule:run

# ── Kualitas kode ─────────────────────────────────────────────────────────────

# PENTING: bila bootstrap/cache/config.php ada, .env.testing DIABAIKAN dan
# test akan berjalan pada DB utama (menghapus datanya). Selalu bersihkan
# config cache sebelum menjalankan test.
.PHONY: test-prepare
test-prepare: ## Bersihkan cache config & hasil uji agar suite memakai .env.testing
	$(ART) config:clear >/dev/null 2>&1 || true
	$(PHP) rm -f bootstrap/cache/config.php bootstrap/cache/routes-v7.php
	$(PHP) rm -rf .phpunit.cache

.PHONY: test
test: test-prepare ## Jalankan seluruh test suite
	$(PHP) php artisan test

.PHONY: test-unit
test-unit: test-prepare ## Jalankan hanya test unit
	$(PHP) php artisan test --testsuite=Unit

.PHONY: test-feature
test-feature: test-prepare ## Jalankan hanya test feature
	$(PHP) php artisan test --testsuite=Feature

.PHONY: test-filter
test-filter: test-prepare ## Jalankan test tertentu: make test-filter NAME=ScoreCalculator
	@test -n "$(NAME)" || { echo 'Gunakan: make test-filter NAME=NamaTest'; exit 1; }
	$(PHP) php artisan test --filter=$(NAME)

.PHONY: coverage
coverage: test-prepare ## Jalankan test dengan laporan coverage
	$(PHP) php artisan test --coverage --min=70

.PHONY: pint
pint: ## Format kode dengan Laravel Pint
	$(PHP) ./vendor/bin/pint

.PHONY: pint-test
pint-test: ## Cek format tanpa mengubah file
	$(PHP) ./vendor/bin/pint --test

.PHONY: lint
lint: pint-test ## Alias pemeriksaan gaya kode

.PHONY: check
check: pint-test test ## Gerbang pre-commit: format + test

# ── Pemeliharaan ──────────────────────────────────────────────────────────────

.PHONY: clear
clear: ## Bersihkan cache config, view, application, route, lalu optimize ulang
	$(ART) config:clear
	$(ART) view:clear
	$(ART) cache:clear
	$(ART) route:clear
	$(ART) optimize:clear
	$(ART) filament:optimize-clear
	$(ART) optimize
	$(ART) filament:optimize

.PHONY: optimize
optimize: ## Cache config/route/view/Filament untuk produksi
	$(ART) optimize
	$(ART) filament:optimize

.PHONY: perms
perms: ## Perbaiki kepemilikan storage & bootstrap/cache
	$(DC) exec -u root php chown -R www-data:www-data storage bootstrap/cache
	$(DC) exec -u root php find storage -type d -exec chmod 775 {} \;

.PHONY: storage-link
storage-link: ## Buat symlink public/storage
	$(ART) storage:link

.PHONY: routes
routes: ## Tampilkan daftar route
	$(ART) route:list --except-vendor

.PHONY: about
about: ## Informasi lingkungan Laravel
	$(ART) about

.PHONY: doctor
doctor: ## Periksa kesehatan toolchain & container
	@echo "── Docker ───────────────────────────────"
	@docker --version 2>/dev/null || echo "  docker TIDAK ditemukan"
	@docker compose version 2>/dev/null || echo "  docker compose TIDAK ditemukan"
	@echo "── Container ────────────────────────────"
	@$(DC) ps
	@echo "── Versi di dalam container ─────────────"
	@$(PHP) php -v | head -1   || true
	@$(PHP) composer -V        || true
	@$(PHP) node -v            || true
	@$(PHP) npm -v             || true
	@$(DC) exec -T mysql mysql --version || true
	@echo "── Aplikasi ─────────────────────────────"
	@test -f .env && echo "  .env ada" || echo "  .env TIDAK ADA — jalankan make install"
	@$(ART) --version || true

.PHONY: prune
prune: ## Hapus image/container/volume Docker yang tidak terpakai (global)
	@read -p "Jalankan 'docker system prune -f'? Ini memengaruhi SELURUH Docker di mesin ini. Ketik 'ya': " c; \
	 if [ "$$c" = "ya" ]; then docker system prune -f; else echo "Dibatalkan."; fi
