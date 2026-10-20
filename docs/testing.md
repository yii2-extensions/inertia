# Testing

This package provides a consistent set of [Composer](https://getcomposer.org/) scripts for local validation.

Tool references:

- [Actionlint](https://github.com/rhysd/actionlint) for GitHub Actions workflow linting.
- [Codespell](https://github.com/codespell-project/codespell) for spelling checks.
- [Composer Require Checker](https://github.com/maglnet/ComposerRequireChecker) for dependency definition checks.
- [Easy Coding Standard (ECS)](https://github.com/easy-coding-standard/easy-coding-standard) for coding standards.
- [EditorConfig Checker](https://github.com/editorconfig-checker/editorconfig-checker) for EditorConfig compliance.
- [Gitleaks](https://github.com/gitleaks/gitleaks) for secret scanning.
- [Infection](https://infection.github.io/) for mutation testing.
- [Markdownlint-cli2](https://github.com/DavidAnson/markdownlint-cli2) for Markdown linting.
- [PHPStan](https://phpstan.org/) for static analysis.
- [PHPUnit](https://phpunit.de/) for unit tests.
- [Prettier](https://prettier.io/) for Markdown and YAML formatting checks.
- [Rector](https://github.com/rectorphp/rector) for automated refactoring.
- [Yamllint](https://github.com/adrienverge/yamllint) for YAML linting.
- [Zizmor](https://github.com/zizmorcore/zizmor) for GitHub Actions security audits.

## Automated refactoring (Rector)

Run Rector to apply automated code refactoring. This command modifies files.

```bash
composer rector
```

Preview the refactoring without modifying files.

```bash
composer rector-check
```

Neither Rector nor ECS is needed to reformat Markdown-only changes.

## Coding standards (ECS)

Check coding standards without modifying files.

```bash
composer ecs-check
```

Apply coding standard fixes. Review the changes before keeping them.

```bash
composer ecs
```

## Dependency definition check

Verify that runtime dependencies are correctly declared in `composer.json`.

```bash
composer check-dependencies
```

## Mutation testing (Infection)

Run mutation testing.

```bash
composer mutation
```

Run mutation testing with PHPStan static analysis enabled.

```bash
composer mutation-static
```

Thresholds, thread count and memory options live in `infection.json5`. CI enforces the same thresholds; retain
the configured 100% mutation thresholds. Run mutation tests when relevant to runtime work; an unchanged runtime
does not need a fresh mutation run merely because documentation or skill instructions changed.

## Repository quality checks

Make the CLI tools used by the shared quality workflow available on `PATH`:
actionlint with its ShellCheck helper, codespell, EditorConfig Checker, markdownlint-cli2, Prettier and
yamllint. Run all checks:

```bash
composer quality
```

Each tool also has its own script:

```bash
composer actionlint
composer codespell
composer editorconfig-checker
composer markdownlint
composer prettier
composer yamllint
```

The linter configuration lives in `.github/linters`, and the Prettier configuration in `.prettierrc.json` and
`.prettierignore`. CI uses the same files.

Run Prettier, EditorConfig Checker and markdownlint together on edited text, including new guides and hidden skill
discovery paths. Prettier uses two spaces for Markdown; numbered items with multiline continuations can produce
three-space indentation and fail EditorConfig. Prefer unnested bullets or separate paragraphs for long steps, then
rerun all three checks after formatting.

Validate relative link targets/anchors and symlink targets. When links refer to unpublished work, distinguish the
local source from published availability. Do not claim a site build or public skill download until the
coordinating documentation build has produced and checked them.

## Static analysis (PHPStan)

Run static analysis.

```bash
composer static
```

## Unit tests (PHPUnit)

Run the full test suite.

```bash
composer tests
```

## Workflow security checks

Make zizmor and Gitleaks available on `PATH`. Audit the current workflow files
with the same pedantic persona used by the shared security workflow:

```bash
composer zizmor
```

Scan Git history for secrets with the repository's Gitleaks configuration:

```bash
composer gitleaks
```

Run both checks. Findings make the command fail; inspect them rather than
disabling the checks.

```bash
composer security
```

Zizmor needs GitHub API access for its online audits. If the tool runs offline,
report that limit separately. To request an offline audit explicitly:

```bash
composer zizmor -- --offline
```

Report hosted CI execution and its PHP/OS matrix separately from these local
commands. Keep generated reports in ignored `internal/`.

## Passing extra arguments

Composer scripts support forwarding additional arguments using `--`.

Run a single PHPUnit test class.

```bash
composer tests -- --filter ManagerTest
```

Run PHPUnit with code coverage report generation.

```bash
composer tests -- --coverage-html internal/coverage
```

Run PHPStan with a different memory limit.

```bash
composer static -- --memory-limit=512M
```

## Prepare a checkout

Use PHP 8.3 or later and the dependencies in the repository's `composer.json`:

```bash
composer install
composer validate --strict
composer check-platform-reqs
```

CI tests PHP 8.3, 8.4, and 8.5 with `gd` and `intl`. The PHP suite uses PHPUnit 12.5 and the installed Yii 22/core
revisions. Keep an application lockfile when verifying an integration; this library's lockfile is ignored.

## Application checks

Review the [configuration](configuration.md) and [application recipes](examples.md), then run the consuming
application's own checks.

`ManagerTest` covers validation/flash lifetime across requests, asset conflicts, page encoding, and response
headers; `RequestTest` covers signed CSRF handling, and `BootstrapTest` covers write redirects.

For application acceptance, additionally mount the actual client, navigate between two pages, submit a valid and an
invalid form, inspect the cookie/header exchange, resolve deferred UI, and retry with an outdated asset version.
Record server and browser evidence separately.

Lint application-owned PHP snippets explicitly when adapting the guide recipes.

---

[AI assistance](agents.md) · [Back to documentation](../README.md#documentation)
