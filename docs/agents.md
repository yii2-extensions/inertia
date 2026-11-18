# AI assistance

The package owns [`skills/yii2-inertia-integration/SKILL.md`](../skills/yii2-inertia-integration/SKILL.md). It guides
agents through the Yii 22 integration: bootstrap, views, props, validation flashes, signed CSRF cookies, response
normalization, observers, and migration. Installing the skill does not install PHP dependencies or a frontend.

## Install in an application

### Automatic Composer installation

Complete the
[one-time application setup](https://yii2-framework.github.io/docs/project/ai-agents/#composer-installation) to enable
`llm/skills` and set `auto-sync` to `true` in `skills.json`. Approve `yii2-extensions/inertia` explicitly in
`dependencies.composer.trusted`. The installed package must include its skill bundle; its `extra.skills.source` declares
the directory.

```bash
composer install
composer skills:show
```

With `auto-sync` enabled, later dependency installs and updates synchronize the skill automatically; no per-skill
installer is required. If `auto-sync` is `false`, run `composer skills:update` after dependency installation or updates.

### Optional npx installation

Run the pinned installer from the application's root. For a local package checkout:

```bash
npx skills@1.7.2 add /absolute/path/to/inertia --list
npx skills@1.7.2 add /absolute/path/to/inertia \
  --skill yii2-inertia-integration --agent claude-code codex opencode
```

A Composer installation can be a source if its release includes `skills/`:

```bash
npx skills@1.7.2 add ./vendor/yii2-extensions/inertia --list
```

Installing a dependency under `vendor/` alone does not expose its skill to a client opened at the application root. For
a public source, first list `npx skills@1.7.2 add yii2-extensions/inertia --list`; install from GitHub only once the
listing confirms that the skill is published. Keep project scope; review destinations before installation. A copied
skill is a snapshot to refresh on upgrades.

## Discovery paths and activation

| Client                                                | Project path                                                                                  | Explicit activation                                             |
| ----------------------------------------------------- | --------------------------------------------------------------------------------------------- | --------------------------------------------------------------- |
| [Codex](https://learn.chatgpt.com/docs/build-skills)  | `.agents/skills/yii2-inertia-integration/SKILL.md`                                            | `$yii2-inertia-integration` or the skill selector               |
| [Claude Code](https://code.claude.com/docs/en/skills) | `.claude/skills/yii2-inertia-integration/SKILL.md`                                            | `/yii2-inertia-integration`                                     |
| [OpenCode](https://opencode.ai/docs/skills/)          | `.agents/skills/`, `.claude/skills/`, or `.opencode/skills/`, followed by the named directory | Load `yii2-inertia-integration` through the native `skill` tool |

This checkout links `.agents/skills/` and `.claude/skills/` entries to the canonical directory under `skills/`. OpenCode
supports those shared locations, so no third editable copy is needed. Inspect its available skills for duplicate names
if multiple paths are present. For manual installation, copy or link the complete named directory; keep `SKILL.md` and
its frontmatter intact. The pinned installer uses `.agents/skills/` for Codex/OpenCode and `.claude/skills/` for Claude
Code.

The skill first uses supplied local guides or the installed package revision. It can consult the package's GitHub guides
when those files are absent from a Composer archive. It does not depend on a relative `../../docs` path being present
beside an installed skill. Human documentation and skill distribution remain owned by the package sources.

## Verify each stage

- **Installation:** confirm the named directory, readable `SKILL.md`, and valid frontmatter at the chosen path.
- **Discovery:** open a fresh client session at the application root and inspect the available skills.
- **Activation:** invoke the skill explicitly and record evidence that the client loaded the instructions.
- **Selection:** try a realistic natural prompt without the skill name, plus an unrelated negative prompt.
- **Result:** validate generated configuration and server responses, then check client navigation and CSRF in a browser.

`--list` proves installer discovery only. Reading Markdown manually does not prove native activation or automatic
selection. Record the client version, loaded skill source and results from the consuming application's own checks.

## Example prompts

Start a fresh client session at the application root. The explicit example uses Codex's `$skill-name` notation; use
`/yii2-inertia-integration` in Claude Code or the installed client's native skill mechanism. Do not paste the skill body
and count that as native activation. These are tasks to execute and verify, not claims that an evaluation has passed.

### Explicit invocation

```text
$yii2-inertia-integration
Show a Yii 22 Inertia form response that preserves signed-cookie CSRF validation and validation errors. Include a
valid submission, a rejected CSRF submission and the expected redirect behavior after a write.
Inspect the installed revision first. Do not install dependencies or change files without authorization.
```

### Natural selection

```text
My Inertia partial reload and validation flash work differently from the initial HTML request. How should I inspect
the installed adapter and core contracts without copying a Laravel recipe?
```

### Verify the outcome

Verify HTML and JSON responses, flash shape, CSRF rejection and redirect status with the actual adapter; browser
mounting is a separate check. Record native loading evidence separately from configuration review, executable checks and
browser/runtime results. For a negative selection control, ask an unrelated question in a fresh session and confirm this
package skill is not loaded. Keep evaluation traces private; report unavailable checks rather than treating discovery as
execution.

[Back to documentation](../README.md#documentation)
