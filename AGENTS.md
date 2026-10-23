# Inertia repository guide

This repository is `yii2-extensions/inertia`, the Yii 22 adapter for `php-forge/inertia`. Read `composer.json`, Git
status/diff, and the relevant guide before editing. Runtime requirements are PHP >=8.3, Yii `^22.0@dev`, and core
`^0.5`. Do not widen support to Yii 2.0.x or copy newer core signatures without implementing and verifying support.

Application integration guidance belongs to `skills/yii2-inertia-integration/SKILL.md`. Client discovery paths link to
that source; do not maintain client-specific instruction copies. Human guides live in `docs/`, and exact contracts live
in PHPDoc. Keep public text in English and avoid duplicate PHPDoc usage blocks when a dedicated example exists.

The protocol, prop wrappers, and result objects belong to the core. This adapter owns the Yii request/response, view,
session, bootstrap, and CSRF boundary. The consuming application owns its JavaScript client and asset build. Report
unrelated runtime issues rather than expanding a documentation task into a behavior change.

Use `TestWith` for simple datasets and `DataProviderExternal` with separate data classes for reusable datasets; do not
define provider methods inside test classes.

Use `docs/testing.md` for PHPUnit, examples, static analysis, dependencies, and formatting commands. Run Prettier,
EditorConfig, and Markdownlint together on edited text; numbered-list continuations must satisfy the two-space Markdown
indentation. Add visible changes at the end of the current unreleased section in `CHANGELOG.md`. Keep migration
instructions tied to actual released changes.

The documentation site consumes the package sources. Coordinate its catalog/navigation separately; do not edit shared
site outputs or configuration as part of a package-only task. Skill discovery, native activation, and functional
application checks are separate evidence; do not claim one from another.
