# Upgrade baseline fixture

`refactor-upgrade-baseline.json` records baseline commit `415d288ed13f7b8dee9b96dc98c798e3cee16116`:

- 33 original migration paths and SHA-256 hashes, with LF line endings.
- Serialized legacy jobs for five scheduled report modules and `ProcessParserRun`, whose payload has no `queuedAt` property.
- A YouTube checkpoint with pagination/version/deadline fields and one saved UTF-8 comment; it predates the shared resource fields and source-request column.
- Hashes of the old class sources used to generate these artifacts, retained for audit.

All identities, timestamps, tokens, source contents and queue names are synthetic. No application `.env`, database, external API, Telegram session or worker is read or started during generation.

The upgrade test consumes this committed fixture directly. It does not require Git history or run the generator. It checks immutable migration hashes at runtime; job compatibility is checked by deserialization and execution, allowing compatible changes to current job source files.

Only when deliberately regenerating the fixture, from a checkout with the baseline Git object available, run in a fresh PHP process:

```powershell
php tests/Support/build-refactor-upgrade-fixture.php
```

The script obtains old definitions through `git show`, loads them in temporary files, fixes the clock/timezone, and serializes them before any current classes of the same name load. It removes its temporary files afterward. Review changes to the fixture; generation is not a deployment or CI preparation step.
