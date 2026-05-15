# Module Architecture Standard

All new modules must follow a single layered structure:

- `Domain` -> business rules and contracts
- `Application` -> use cases and orchestration
- `Infrastructure` -> external adapters (HTTP, cache, providers, repositories)
- `UI` -> controllers, requests, transformers/resources

## Directory Blueprint

```text
app/Modules/{ModuleName}/
  Domain/
    Contracts/
    Entities/
    ValueObjects/
    Services/
  Application/
    Contracts/
    DTO/
    UseCases/
  Infrastructure/
    Clients/
    Repositories/
    Providers/
  UI/
    Http/
      Controllers/
      Requests/
    Transformers/
routes/web/{module-slug}.php
```

## Layer Rules

1. `Domain` must not depend on `Infrastructure` or `UI`.
2. `Application` can depend only on `Domain`.
3. `Infrastructure` can depend on `Domain` and `Application` contracts.
4. `UI` can depend on `Application` only, not directly on `Infrastructure`.
5. External APIs and persistence access must be implemented in `Infrastructure`.
6. Validation belongs to `UI/Http/Requests`.
7. Response shape belongs to `UI/Transformers`.

## Naming Rules

1. Module namespace: `App\Modules\{ModuleName}` in StudlyCase.
2. Use case: `Run{ModuleName}UseCase`.
3. Domain repository contract: `{ModuleName}RepositoryInterface`.
4. Infrastructure repository: `{Driver}{ModuleName}Repository` (for example `HttpCompanyIntelRepository`).
5. UI controller: `{ModuleName}Controller`.

## Scaffolding Command

Use:

```bash
php artisan app:make-module CompanyIntel
```

Options:

- `--force` overwrite existing files.

The command generates all required layers and a route stub in `routes/web/{module-slug}.php`.

