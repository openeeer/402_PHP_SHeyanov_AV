APP_ENV_FILE=.env
DB_FILE=database/database.sqlite

.PHONY: install serve test

install:
	@if [ ! -f $(APP_ENV_FILE) ]; then cp .env.example $(APP_ENV_FILE); fi
	@mkdir -p database
	@touch $(DB_FILE)
	@composer install
	@if ! grep -q '^APP_KEY=base64:' $(APP_ENV_FILE); then php artisan key:generate --force; fi
	@php artisan migrate --force

serve:
	php artisan serve

test:
	php artisan test
