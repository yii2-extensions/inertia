<!-- markdownlint-disable MD041 -->
<p align="center">
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://www.yiiframework.com/image/design/logo/yii3_full_for_dark.svg">
        <source media="(prefers-color-scheme: light)" srcset="https://www.yiiframework.com/image/design/logo/yii3_full_for_light.svg">
        <img src="https://www.yiiframework.com/image/design/logo/yii3_full_for_light.svg" alt="Yii Framework" width="80%">
    </picture>
    <h1 align="center">Inertia for Yii2</h1>
    <br>
</p>
<!-- markdownlint-enable MD041 -->

<p align="center">
    <a href="https://github.com/yii2-extensions/inertia/actions/workflows/build.yml" target="_blank">
        <img src="https://img.shields.io/github/actions/workflow/status/yii2-extensions/inertia/build.yml?style=for-the-badge&label=PHPUnit&logo=github" alt="PHPUnit">
    </a>
    <a href="https://codecov.io/github/yii2-extensions/inertia" target="_blank">
        <img src="https://img.shields.io/codecov/c/github/yii2-extensions/inertia.svg?style=for-the-badge&logo=codecov&logoColor=white&label=Coverage" alt="Coverage">
    </a>
    <a href="https://dashboard.stryker-mutator.io/reports/github.com/yii2-extensions/inertia/main" target="_blank">
        <img src="https://img.shields.io/endpoint?style=for-the-badge&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyii2-extensions%2Finertia%2Fmain" alt="Mutation Testing">
    </a>
    <a href="https://github.com/yii2-extensions/inertia/actions/workflows/static.yml" target="_blank">
        <img src="https://img.shields.io/github/actions/workflow/status/yii2-extensions/inertia/static.yml?style=for-the-badge&label=PHPStan&logo=github" alt="PHPStan">
    </a>
</p>

<p align="center">
    <em>Connect Yii requests, responses, views, sessions, and redirects to the Inertia protocol</em>
</p>

## Architecture

[`php-forge/inertia`](https://github.com/php-forge/inertia) owns the Inertia protocol, page model, prop resolution,
headers, redirects, and result objects. This package connects that core to Yii requests, responses, views, and sessions.
The application supplies its JavaScript client, component resolver, and asset build.

## Installation

Yii 2.0.x is outside this package's declared support.

```bash
composer require yii2-extensions/inertia:^0.6
```

See the [installation guide](https://github.com/yii2-extensions/inertia/blob/main/docs/installation.md) for Yii
development-line resolution, bootstrap registration, CSRF configuration, and frontend setup.

## Quick start

Register the adapter in the existing web application configuration:

```php
'bootstrap' => [\yii\inertia\Bootstrap::class],
```

Return a page from an existing controller action:

```php
return \yii\inertia\Inertia::render('Dashboard', ['stats' => ['visits' => 42]]);
```

A normal visit renders HTML; an Inertia visit returns a JSON page. The default view supplies the page payload but loads
no frontend bundle. Continue with
[configuration](https://github.com/yii2-extensions/inertia/blob/main/docs/configuration.md) and the
[usage examples](https://github.com/yii2-extensions/inertia/blob/main/docs/examples.md) to connect the application's
client.

## Documentation

Practical guides and application recipes, with exact contracts in PHPDoc.

- 📚 [Installation guide](https://github.com/yii2-extensions/inertia/blob/main/docs/installation.md): Composer,
  bootstrap, CSRF, and frontend setup.
- ⚙️ [Configuration and adapter reference](https://github.com/yii2-extensions/inertia/blob/main/docs/configuration.md):
  manager options, props, root views, and responses.
- 💡 [Usage examples](https://github.com/yii2-extensions/inertia/blob/main/docs/examples.md): controllers, prop recipes,
  validation, and redirects.
- 🔄 [Upgrade guide](UPGRADE.md) and
  [migration guide](https://github.com/yii2-extensions/inertia/blob/main/docs/migration.md): application changes for
  existing installations.
- 🧪 [Testing guide](https://github.com/yii2-extensions/inertia/blob/main/docs/testing.md): PHPUnit, static analysis,
  mutation, and documentation checks.

The guides and the [API reference](https://yii2-framework.github.io/docs/extensions/inertia/reference/) generated from
the PHPDoc in `src/` are published at <https://yii2-framework.github.io/docs/extensions/inertia/>.

## AI assistance

AI agents: read the [documentation index](https://yii2-framework.github.io/docs/llms.txt) and fetch the Markdown version
of a page by appending `index.md` to its URL. The
[AI agents guide](https://yii2-framework.github.io/docs/project/ai-agents/) covers skill installation, client setup and
verification.

- 🤖 [`yii2-inertia-integration` skill](https://github.com/yii2-extensions/inertia/blob/main/docs/agents.md): Yii 22
  adapter setup, root views, props, validation flashes, signed CSRF cookies, redirects, observers and 0.1 upgrades.
- 🧭 [`yii2-framework` skill](https://yii2-framework.github.io/docs/guide/skills/yii2-framework/): Yii 22.0 application
  conventions.

The hosted documentation covers Yii 22.0. If it does not match the installed revision or is unavailable, use the
[repository guides](#documentation), the [skill source](skills/yii2-inertia-integration/SKILL.md), the source PHPDoc and
the [changelog](CHANGELOG.md).

## Package information

[![PHP](https://img.shields.io/badge/%3E%3D8.3-777BB4.svg?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/releases/8.3/en.php)
[![Yii 22.0.x](https://img.shields.io/badge/22.0.x-0073AA.svg?style=for-the-badge&logo=yii&logoColor=white)](https://github.com/yiisoft/yii2/tree/22.0)
[![Latest Stable Version](https://img.shields.io/packagist/v/yii2-extensions/inertia.svg?style=for-the-badge&logo=packagist&logoColor=white&label=Stable)](https://packagist.org/packages/yii2-extensions/inertia)
[![Total Downloads](https://img.shields.io/packagist/dt/yii2-extensions/inertia.svg?style=for-the-badge&logo=composer&logoColor=white&label=Downloads)](https://packagist.org/packages/yii2-extensions/inertia)

## Project status

[![PHPStan Level Max](https://img.shields.io/badge/PHPStan-Level%20Max-4F5D95.svg?style=for-the-badge&logo=github&logoColor=white)](https://github.com/yii2-extensions/inertia/actions/workflows/static.yml)
[![Quality](https://img.shields.io/github/actions/workflow/status/yii2-extensions/inertia/quality.yml?style=for-the-badge&label=Quality&logo=github)](https://github.com/yii2-extensions/inertia/actions/workflows/quality.yml)
[![StyleCI](https://img.shields.io/badge/dynamic/xml?url=https%3A%2F%2Fgithub.styleci.io%2Frepos%2F1196150046%2Fshield%3Fbranch%3Dmain&query=%2F%2F*%5Blocal-name%28%29%3D%27text%27%5D%5B2%5D&label=StyleCI&style=for-the-badge&logo=github&logoColor=white&color=4F5D95)](https://github.styleci.io/repos/1196150046?branch=main)

## Security

[![OpenSSF Scorecard](https://api.scorecard.dev/projects/github.com/yii2-extensions/inertia/badge?style=for-the-badge)](https://scorecard.dev/viewer/?uri=github.com/yii2-extensions/inertia)
[![Zizmor](https://img.shields.io/github/actions/workflow/status/yii2-extensions/inertia/security.yml?style=for-the-badge&label=Zizmor&logo=github)](https://github.com/yii2-extensions/inertia/actions/workflows/security.yml)

## Built with AI

[![Built with Claude Code](https://img.shields.io/badge/Built%20with-Claude%20Code-D97757.svg?style=for-the-badge&logo=claude&logoColor=white)](https://claude.com/claude-code)
[![Built with Codex](https://img.shields.io/badge/Built%20with-Codex-10A37F.svg?style=for-the-badge&logo=openaigym&logoColor=white)](https://openai.com/codex)
[![Reviewed by CodeRabbit](https://img.shields.io/badge/Reviewed%20by-CodeRabbit-FF570A.svg?style=for-the-badge&logo=coderabbit&logoColor=white)](https://www.coderabbit.ai)

## Our social networks

[![Follow on X](https://img.shields.io/badge/-Follow%20on%20X-1DA1F2.svg?style=for-the-badge&logo=x&logoColor=white&labelColor=000000)](https://x.com/Terabytesoftw)
[![Follow on Facebook](https://img.shields.io/badge/-Follow%20on%20Facebook-1877F2.svg?style=for-the-badge&logo=facebook&logoColor=white&labelColor=000000)](https://www.facebook.com/wilmer.arambula.9)
[![Join our Subreddit](https://img.shields.io/badge/-Join%20our%20Subreddit-FF4500.svg?style=for-the-badge&logo=reddit&logoColor=white&labelColor=000000)](https://www.reddit.com/r/Yii2/)
[![Join on Telegram](https://img.shields.io/badge/-Join%20on%20Telegram-26A5E4.svg?style=for-the-badge&logo=telegram&logoColor=white&labelColor=000000)](https://t.me/yii_framework_in_english)

## License

[![License](https://img.shields.io/badge/License-BSD--3--Clause-brightgreen.svg?style=for-the-badge&logo=opensourceinitiative&logoColor=white&labelColor=555555)](LICENSE)
