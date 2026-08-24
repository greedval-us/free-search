# Development guide

## Рабочий цикл

Backend использует Laravel service container и module Service Providers из `bootstrap/providers.php`. Frontend — Inertia pages и Vue components из `resources/js`.

```bash
composer run dev
composer run lint:check
npm run quality:check
composer run test
npm run test:unit
```

`composer run test` включает Pint check и PHPUnit. `npm run quality:check` включает Prettier, ESLint, vue-tsc и нестрогую i18n-проверку. Vitest запускается отдельно и не входит в текущий GitHub Actions workflow.

## Добавление сценария

1. Определите модуль-владельца и существующий стиль внутри него.
2. Оставьте Controller транспортным: Request → DTO/normalization → Application Service → response.
3. Разместите external I/O за существующим Gateway/Provider contract или узким adapter.
4. Зарегистрируйте bindings в module Provider и `bootstrap/providers.php`, если Provider новый.
5. Добавьте named route и Feature Access policy, если capability тарифицируется.
6. Добавьте locale messages и выполните `npm run i18n:check`.
7. Покройте use case Unit или Feature test.

Не навязывайте всем модулям одинаковые папки: фактические границы описаны в [modules architecture](architecture/modules.md).

## Миграции, Queue и Vite

```bash
php artisan migrate
php artisan queue:work
npm run dev
```

Parser jobs при включённом queue mode не продвигаются HTTP polling-запросом. Для отладки синхронного пошагового режима можно использовать `PARSER_RUN_QUEUE_ENABLED=false`, понимая, что это другой runtime path.

## Formatting и generated frontend routes

- PHP: `composer run lint` / `composer run lint:check`.
- Vue/TS: `npm run format`, `npm run lint`, `npm run types:check`.
- `resources/js/routes` и Wayfinder integration связаны с named Laravel routes; не документируйте их как стабильный внешний API.

## CI

`.github/workflows/ci.yml` использует PHP 8.3, Node 22 и три job: `quality`, `tests`, `build`. Подробнее: [Testing](testing.md).
