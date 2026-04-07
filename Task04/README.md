# Task04 - Игра «Калькулятор» на Laravel

В каталоге `Task04` реализована игра из предыдущей лабораторной работы на фреймворке Laravel.

## Возможности

- создание новой игры;
- генерация арифметического выражения;
- отправка ответа игрока;
- просмотр истории всех игр;
- просмотр деталей выбранной игры;
- хранение данных в SQLite-базе `database/database.sqlite`.

## Требования

- PHP 8.3 или выше;
- Composer;
- SQLite.

## Установка

### Linux

Установка автоматизирована через `Makefile`:

```bash
make install
```

Команда выполняет:

- установку PHP-зависимостей;
- создание файла `.env`, если его еще нет;
- создание SQLite-базы в каталоге `database`;
- генерацию ключа приложения;
- запуск миграций.

### Ручная установка

Если `make` недоступен, выполните команды вручную:

```bash
composer install
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate
```

## Запуск

```bash
php artisan serve
```

После запуска приложение доступно по адресу [http://127.0.0.1:8000](http://127.0.0.1:8000).

## API

- `GET /api/games`
- `GET /api/games/{id}`
- `POST /api/games`
- `POST /api/step/{id}`

## Тесты

```bash
php artisan test
```
