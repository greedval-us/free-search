# Module Architecture Standard

## Goals
- Keep domain logic isolated from framework and transport.
- Make modules easy to test, replace, and evolve independently.
- Keep config, contracts, and infrastructure explicit.

## Recommended Structure
For each module under `app/Modules/<ModuleName>`:

1. `Application/`
- Use-cases and orchestration services.
- Public interfaces for app-layer use.

2. `Domain/`
- Pure business rules, entities/value objects, domain services.
- No Laravel or HTTP dependencies.

3. `Infrastructure/`
- External clients, gateways, parsers, persistence adapters.
- Implements contracts from `Application`/`Domain`.

4. `DTO/`
- Request/response data contracts crossing boundaries.

5. `Providers/`
- Module service provider and bindings registration.

## Layer Rules
1. Controllers must be thin: validate input and call application services.
2. `FormRequest` handles validation/normalization only.
3. Application services orchestrate; heavy calculations should be delegated to focused collaborators.
4. Infrastructure must not leak into controllers directly.
5. Shared helpers go to `app/Support/*` only when truly cross-module.

## Dependency Direction
1. `Http` -> `Application` -> `Domain`
2. `Infrastructure` depends on contracts, then bound in providers.
3. `Domain` never depends on Laravel facades.

## Configuration Rules
1. Keep feature config in dedicated section files (for example `config/osint/*.php`).
2. Map raw config arrays into typed config objects at module boundaries.
3. Avoid reading `env()` outside config files.

## Testing Baseline
1. Unit tests for domain services and calculators.
2. Feature tests for main HTTP flows and auth/security rules.
3. Contract tests for infrastructure adapters (API clients/parsers).

## Naming
1. Interfaces end with `Interface`.
2. Config objects end with `Config`.
3. Orchestration services use `...Service` or `...ApplicationService`.

