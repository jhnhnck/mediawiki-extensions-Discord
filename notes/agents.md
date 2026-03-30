# agents.md - NovaDiscord

Guidance for AI coding agents working in this repository.

---

## project overview

**NovaDiscord** is a MediaWiki extension (v3.0.0, MIT) that sends real-time webhook notifications to Discord when wiki events occur. Written in PHP 8.4, targeting MediaWiki >= 1.44. Uses MediaWiki's dependency injection and hook interface system.

---

## rules

1. do not edit the rules.
1. do not perform any interactions with Discord without asking.
1. do not create commits without being explicitly asked to.
1. all noqa comments must include a valid reason.
1. check the current time at the start of each conversation. if it is past 12:30 AM ET, suggest a natural stopping point before continuing any task.

---

## architecture

| File | Class | Role |
| :--- | :--- | :--- |
| `includes/DiscordAlert.php` | `DiscordAlert` (abstract) | Base class; `sendAlert()`, `isEnabled()`, `getDiffBlock()`, etc. |
| `includes/NovaDiscordConfig.php` | `NovaDiscordConfig` | Typed config accessor; validates webhook URLs |
| `includes/PageAlert.php` | `PageAlert` | PageSaveComplete, PageDeleteComplete, PageMoveComplete, PageUndeleteComplete |
| `includes/UserAlert.php` | `UserAlert` | LocalUserCreated, BlockIpComplete, UnblockUserComplete, UserGroupsChanged |
| `includes/ArticleAlert.php` | `ArticleAlert` | ArticleRevisionVisibilitySet, ArticleProtectComplete, AfterImportPage, ArticleMergeComplete |
| `includes/FileAlert.php` | `FileAlert` | UploadComplete, FileDeleteComplete, FileUndeleteComplete |
| `includes/ExtensionAlert.php` | `ExtensionAlert` | RenameUserComplete |
| `includes/ErrorAlert.php` | `ErrorAlert` | LogException |
| `ServiceWiring.php` | — | Registers `NovaDiscord.Config` service |

All handler classes extend `DiscordAlert`, implement MW hook interfaces, and receive dependencies via constructor injection (service container).

---

## configuration system

Settings are declared in `extension.json` and read from `LocalSettings.php` via `NovaDiscordConfig` (wired as `NovaDiscord.Config` service). All config values are accessed through `NovaDiscordConfig`; never read `$wg*` globals directly in hook handlers.

| Setting | Type | Default | Purpose |
| :--- | :--- | :--- | :--- |
| `$wgDiscordWebhooks` | array | `[]` | Array of webhook configs; each entry requires `'url'` (must be `https://`) and accepts optional `'hooks'` |
| `$wgDiscordDefaultHooks` | array\|null | `null` | Default hook list for webhooks omitting `'hooks'`; null = all hooks except LogException |
| `$wgDiscordNoBots` | bool | `true` | Skip notifications from bot accounts |
| `$wgDiscordNoMinor` | bool | `false` | Skip minor edit notifications |
| `$wgDiscordNoNull` | bool | `true` | Skip null edit notifications |
| `$wgDiscordMaxChars` | int | `500` | Max chars for user-provided text; truncated with `...` |
| `$wgDiscordDisabledNS` | array | `[]` | Namespace IDs whose page events are silenced |
| `$wgDiscordDisabledUsers` | array | `["Redirect fixer"]` | Usernames whose actions don't trigger alerts |
| `$wgDiscordPrivateExceptionAlerts` | bool | `false` | Enable Discord alerts for unhandled PHP exceptions; opt-in; rate-limited via APCu (5 min TTL) |

---

## coding conventions

1. **constructor injection always** — all new dependencies go into the constructor and the `services` array in `extension.json`. never use `MediaWikiServices::getInstance()` directly inside hook methods.
1. **logger via injected `$this->logger`** — never `wfDebugLog()`. use `$this->logger->debug()` for verbose tracing, `$this->logger->error()` for failures.
1. **webhook URLs must be validated** — `NovaDiscordConfig::getWebhooksForHook()` enforces `https://` and `filter_var`. new URL sources must go through this path.
1. **UTF-8 in JSON payloads** — always use `JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`. sanitize wiki content with `mb_convert_encoding(..., 'UTF-8', 'UTF-8')` before processing diffs.
1. **event timestamps** — use the actual event timestamp (revision, block, file) rather than `time()`. when no event timestamp is available in hook params, `time()` is acceptable.
1. **no globals** — use `MW_INSTALL_PATH` constant instead of `global $IP`.
1. **hook interfaces** — implement the MW hook interface (e.g., `PageSaveCompleteHook`) rather than registering via string. this enforces correct method signatures.
1. **`isEnabled()` first** — always call this at the top of hook methods before any work.
1. **`$wgDiscordNoNull` applies to `isNullEdit()`** — not size comparison. always use `EditResult::isNullEdit()`.
1. **`NS_FILE` guard in PageSave** — file pages are handled by `FileAlert`; skip them in `PageAlert`.

---

## testing

**run tests** (from repo root):

```bash
composer test
# or via docker:
docker compose --file .dev/docker-compose.yml up --build \
    --abort-on-container-exit --exit-code-from job-tests \
    --attach job-tests --remove-orphans
```

**test doubles:** `/tests/doubles/MockRequestFactory.php` — captures HTTP calls to in-memory array. base test: `NovaDiscordIntegrationTestCase`; helper: `getDecodedPayload(int $index)`.

**coverage:** 33 tests, 52 assertions. active: PageSaveComplete, PageDeleteComplete, PageMoveComplete, PageUndeleteComplete, ArticleProtectComplete, ArticleRevisionVisibilitySet, BlockIpComplete, UnblockUserComplete, LocalUserCreated. stub-only (skipped): AfterImportPage, ArticleMergeComplete, FileDeleteComplete, FileUndeleteComplete, UploadComplete, UserGroupsChanged, RenameUserComplete.

---

## patterns & pitfalls

1. **`NovaDiscord.LoggerSpi`** - custom service (wired in `ServiceWiring.php`) that delegates to `LoggerFactory::getInstance()`. injected as `MediaWiki\Logger\Spi`; stored in base class as `protected LoggerInterface $logger`. channel: `nova-discord`. avoids depending on `'LoggerFactory'` being a registered MW service (not guaranteed across all versions).
1. **service registration** - `extension.json` → `HookHandlers[*].services`. `NovaDiscord.Config` is wired in `ServiceWiring.php` using `ServiceOptions`.
1. **`ArticleAlert` and `ExtensionAlert` use `RequestContext::getMain()->getUser()`** - for hooks that don't provide the performing user as a parameter (`ArticleRevisionVisibilitySet`, `AfterImportPage`, `ArticleMergeComplete`, `RenameUserComplete`). no cleaner MW pattern exists for these hooks.
1. **`PageMoveComplete` not `TitleMoveComplete`** - using the wrong name silently disables the hook without errors.
1. **`LogException` is explicit-only** - never included in the null (all hooks) default; must be listed in `'hooks'` AND `$wgDiscordPrivateExceptionAlerts` must be true.

## key base class methods (`DiscordAlert`)

| Method | Visibility | Purpose |
| :--- | :--- | :--- |
| `sendAlert(hookName, msg, timestamp)` | protected | POSTs plain-text JSON to all configured webhook URLs |
| `isEnabled(hookName, namespace, user)` | protected | returns false if namespace/user is disabled, or user is a bot and NoBots is true |
| `formatMarkdownLink(text, url)` | protected static | returns `[text](<url>)` — angle brackets suppress Discord link previews |
| `formatRevisionText(revision)` | protected | returns `[diff](<url>) (±N bytes)` line for a revision |
| `getDiffBlock(revision)` | protected | returns ` ```diff ` code block for revision vs parent; budget-limited to 1500 chars; sanitizes to UTF-8; returns null for non-text content |
| `truncateString(text, length)` | protected static | truncates with `...` using `mb_substr` |
| `formatBytes(bytes, precision)` | protected static | converts byte count to human-readable string (B/KB/MB/GB/TB) |

---

## reference notes

**`notes/style/`**
- [`notes/style/commit_style.md`](notes/style/commit_style.md) - commit message format, types, and tone

**`notes/features/`**
- [`notes/features/novadiscord.md`](notes/features/novadiscord.md) - wiki integration config (LocalSettings, config reference, hook catalog)

**`notes/`**
- [`notes/.meta.md`](notes/.meta.md) - guide to this documentation system
- [`notes/to-do.md`](notes/to-do.md) - open items and known gaps

---

## file & directory layout

```
extension.json               # MW extension manifest; hook registration and service wiring
ServiceWiring.php            # registers NovaDiscord.Config service
includes/
  DiscordAlert.php           # abstract base class
  NovaDiscordConfig.php      # typed config accessor
  PageAlert.php
  UserAlert.php
  ArticleAlert.php
  FileAlert.php
  ExtensionAlert.php
  ErrorAlert.php
tests/
  doubles/
    MockRequestFactory.php   # captures HTTP calls for assertions
  phpunit/
    integration/             # one test class per hook
i18n/                        # message localizations
notes/                       # project notes (not code)
  agents.md                  # this file
  .meta.md                   # documentation system guide
  to-do.md                   # open items
  style/
    commit_style.md          # commit message format, types, and tone
  features/
    novadiscord.md           # wiki integration config (LocalSettings, config reference, hook catalog)
```

---

## personality / style

- lowercase inline comments; no trailing periods
- use semicolons or regular dashes (-); never em-dashes
- do not include any extraneous punctuation
- use american english spelling and grammar
- use spaces for indentation always; avoid formats that require tabs
- prefer brief statements over long explanations

---

## metadata

```yaml
last_updated: 30 March 2026
```
