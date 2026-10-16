# AI assistance

The package owns [`skills/yii2-inertia-integration/SKILL.md`](../skills/yii2-inertia-integration/SKILL.md). It guides
agents through the Yii 22 integration: bootstrap, views, props, validation flashes, signed CSRF cookies, response
normalization, observers, and migration. Installing the skill does not install PHP dependencies or a frontend.

## Install in an application

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

Installing a dependency under `vendor/` alone does not expose its skill to a client opened at the application root.
For a public source, first list `npx skills@1.7.2 add yii2-extensions/inertia --list`; install from GitHub only once
the listing confirms that the skill is published. Local changes are not available on the default branch yet.
Keep project scope; review destinations before installation. A copied skill is a snapshot to refresh on upgrades.

## Discovery paths and activation

| Client                                                | Project path                                                                                  | Explicit activation                                             |
| ----------------------------------------------------- | --------------------------------------------------------------------------------------------- | --------------------------------------------------------------- |
| [Codex](https://learn.chatgpt.com/docs/build-skills)  | `.agents/skills/yii2-inertia-integration/SKILL.md`                                            | `$yii2-inertia-integration` or the skill selector               |
| [Claude Code](https://code.claude.com/docs/en/skills) | `.claude/skills/yii2-inertia-integration/SKILL.md`                                            | `/yii2-inertia-integration`                                     |
| [OpenCode](https://opencode.ai/docs/skills/)          | `.agents/skills/`, `.claude/skills/`, or `.opencode/skills/`, followed by the named directory | Load `yii2-inertia-integration` through the native `skill` tool |

This checkout links `.agents/skills/` and `.claude/skills/` entries to the canonical directory under `skills/`.
OpenCode supports those shared locations, so no third editable copy is needed. Inspect its available skills for
duplicate names if multiple paths are present. For manual installation, copy or link the complete named directory;
keep `SKILL.md` and its frontmatter intact. The pinned installer uses `.agents/skills/` for Codex/OpenCode and
`.claude/skills/` for Claude Code.

The skill first uses supplied local guides or the installed package revision. It can consult the package's GitHub
guides when those files are absent from a Composer archive. It does not depend on a relative `../../docs` path being
present beside an installed skill. Human documentation and skill distribution remain owned by the package sources.

## Verify each stage

- **Installation:** confirm the named directory, readable `SKILL.md`, and valid frontmatter at the chosen path.
- **Discovery:** open a fresh client session at the application root and inspect the available skills.
- **Activation:** invoke the skill explicitly and record evidence that the client loaded the instructions.
- **Selection:** try a realistic natural prompt without the skill name, plus an unrelated negative prompt.
- **Result:** validate generated configuration and server responses, then check client navigation and CSRF in a browser.

`--list` proves installer discovery only. Reading Markdown manually does not prove native activation or automatic
selection. Record the client version, loaded skill source and results from the consuming application's own checks.

---

[Migration](migration.md) · [Testing →](testing.md) · [Back to documentation](../README.md#documentation)
