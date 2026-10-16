# Testing

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

## PHP checks

```bash
composer tests
composer static
composer check-dependencies
vendor/bin/ecs check --no-progress-bar
```

`composer tests -- --filter ManagerTest` forwards arguments to PHPUnit. Use the non-fixing ECS command above for
verification; `composer ecs` applies fixes. `composer rector` also modifies source. Neither is needed to reformat
Markdown-only changes. Lint application-owned PHP snippets explicitly when adapting the guide recipes.

Mutation testing for runtime changes:

```bash
composer mutation
composer mutation-static
```

Retain the configured 100% mutation thresholds. Run mutation tests when relevant to runtime work; an unchanged
runtime does not need a fresh mutation run merely because documentation or skill instructions changed.

## Documentation quality

The pinned reusable quality workflow defines the tools/configuration used by CI. Run its formatting checks together
from the package root, including new guides and hidden skill discovery paths:

```bash
npx --yes prettier@3.8.4 --config .prettierrc.json --ignore-path .prettierignore \
  --check '**/*.md' '**/*.{yml,yaml}'
npx --yes markdownlint-cli2@0.22.1 --config .github/linters/.markdown-lint.yml \
  '**/*.md' '#vendor' '#runtime'
editorconfig-checker -config .github/linters/.editorconfig-checker.json
```

CI pins EditorConfig Checker 3.7.0. Match that version when reproducing CI; a newer local version is useful but
record the difference. Check edited JSON/PHP files with EditorConfig too. Prettier uses two spaces for Markdown;
numbered items with multiline continuations can produce three-space indentation and fail EditorConfig. Prefer
unnested bullets or separate paragraphs for long steps, then rerun all three checks after formatting.

CI also checks spelling and YAML. Validate relative link targets/anchors and symlink targets. When links refer to
unpublished work, distinguish the local source from published availability. Do not claim a site build or public
skill download until the coordinating documentation build has produced and checked them.

---

[AI assistance](agents.md) · [Back to documentation](../README.md#documentation)
