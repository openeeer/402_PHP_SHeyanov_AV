# Task03 - SPA на Slim

## Структура
- `Task03/public` - корень сайта и фронтенд SPA
- `Task03/public/index.php` - единственная точка входа PHP (Slim + REST API)
- `Task03/db` - SQLite база данных

## Запуск
1. Перейти в каталог `Task03`.
2. Установить зависимости:
   ```powershell
   composer install
   ```
3. Запустить сервер:
   ```powershell
   php -S localhost:3000 -t public
   ```
4. Открыть `http://localhost:3000/`.

## REST API
- `GET /games`
- `GET /games/{id}`
- `POST /games`
- `POST /step/{id}`

Для встроенного сервера PHP без дополнительного router-скрипта фронтенд использует fallback на префикс `index.php`:
- `GET /index.php/games`
- `GET /index.php/games/{id}`
- `POST /index.php/games`
- `POST /index.php/step/{id}`

