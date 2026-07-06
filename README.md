# System Dashboard & Monitoring

A **local-only** Laravel + Inertia + React tool that scans your other local projects,
surfaces gaps via heuristic rules, and generates ready-to-paste prompts you can feed
into Claude Code to act on those gaps.

It runs on your machine via `php artisan serve` — single user, no auth, no external or
AI API calls. Prompt generation is local template assembly, not an LLM call.

## What it does

1. **Discovers** projects under configured root folders (`config/dashboard.php`),
   resolving nested wrapper directories (e.g. `Foo/Foo`) to the real project root.
2. **Scans** each project: git activity (commits, last commit, uncommitted files),
   stack detection (Laravel / Livewire / Inertia+React / React Native / Vue / Go /
   Python / Docker), file markers (README, tests, CI, `.env.example`), and TODO/FIXME
   density.
3. **Flags gaps** via a heuristic rule engine (10 starter rules — zero commits, stale
   repo, missing README/tests/CI, uncommitted changes, high TODO density, etc.).
4. **Generates prompts** — bundles a project's context (structure, README, recent git
   log, detected gaps) into text you paste into Claude Code to fix or build things.

## Stack

- Laravel 12, Inertia.js, React, Tailwind (Breeze scaffold, auth stripped)
- **Database: SQLite** (see note below), Symfony Finder + Process for filesystem/git

> **Note on the database:** the plan targeted MySQL, but the local MySQL data directory
> was found corrupted (and conflicting with a parallel MariaDB install) at build time,
> so the app was wired to SQLite to keep it fully local and zero-config. To switch to
> MySQL later: repair MySQL, set `DB_*` in `.env`, create the database, and re-run
> `php artisan migrate:fresh`.

## Setup

```bash
composer install
npm install && npm run build
touch database/database.sqlite   # if not present
php artisan migrate
php artisan projects:scan --discover   # discover + first scan
php artisan serve
```

Then open the served URL (default `http://127.0.0.1:8000`).

## Usage

- **`php artisan projects:scan --discover`** — discover new projects and scan all
  included ones. Drop `--discover` to only rescan known projects. Pass a project id or
  path to scan just one.
- In the UI: **Scan All** / **Discover New** buttons on the Dashboard; **Rescan** and
  include/exclude/archive per project; **Generate Prompt** (whole-project or per-finding);
  **Prompts** page for copy-paste history; **Settings** shows the current config
  (edit `config/dashboard.php` to change scan roots, thresholds, or toggle rules).

## Architecture

| Layer | Location |
|-------|----------|
| Config (scan roots, thresholds, rules) | `config/dashboard.php` |
| Discovery + wrapper-dir resolution | `app/Services/ProjectDiscovery/` |
| Scanning + metric gatherers | `app/Services/ProjectScanner/` |
| Heuristic rule engine + 10 rules | `app/Services/RuleEngine/` |
| Prompt generation (Blade templates) | `app/Services/PromptGeneration/`, `resources/views/prompts/` |
| Data model | `projects`, `scan_snapshots`, `findings`, `generated_prompts` |
| Frontend | `resources/js/Pages/{Dashboard,Projects,Prompts,Settings}` |

## Adding a rule

Implement `App\Services\RuleEngine\Contracts\GapRule` in
`app/Services/RuleEngine/Rules/`, then register it in `config/dashboard.php` under
`rules` and `rules_enabled`.
