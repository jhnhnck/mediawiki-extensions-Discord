# NovaDiscord

MediaWiki extension (v3.0.0, MIT) that sends real-time webhook notifications to Discord when wiki events occur. Requires MW >= 1.44. Source: `devel/NovaDiscord/`. Bind-mounted read-only into containers in dev. For extension development details, see [`agents.md`](notes/agents.md).

---

## LocalSettings.php (attu-wiki repo)

```php
wfLoadExtension('NovaDiscord');
$wgDiscordNoBots = false; // bots do post (overrides default true)

if (!$attuDevMode) {
    $wgDiscordWebhooks = [['url' => $_ENV['ATTU_WIKI_WEBHOOK']]];
    $wgDiscordDisabledUsers = ['Redirect fixer', '127.0.0.1'];
} else {
    $wgDiscordWebhooks = [['url' => $_ENV['ATTU_WIKI_WEBHOOK_ALT']]]; // dev fires alternate webhook
}
```

Env vars: `ATTU_WIKI_WEBHOOK` (production), `ATTU_WIKI_WEBHOOK_ALT` (dev mode).

---

## Configuration Reference

| Setting | Type | Default | Purpose |
| :--- | :--- | :--- | :--- |
| `$wgDiscordWebhooks` | array | `[]` | Array of webhook configs; each entry requires `'url'` (string, must be `https://`) and accepts optional `'hooks'` (array of hook names, or null for all) |
| `$wgDiscordDefaultHooks` | array\|null | `null` | Default hook list for webhooks that omit `'hooks'`; null = all hooks except LogException |
| `$wgDiscordNoBots` | bool | `true` | Skip notifications from bot accounts |
| `$wgDiscordNoMinor` | bool | `false` | Skip minor edit notifications |
| `$wgDiscordNoNull` | bool | `true` | Skip null edit notifications |
| `$wgDiscordMaxChars` | int | `500` | Max chars for user-provided text (summaries, reasons); truncated with `...` |
| `$wgDiscordDisabledNS` | array | `[]` | Namespace IDs whose page events are silenced |
| `$wgDiscordDisabledUsers` | array | `["Redirect fixer"]` | Usernames whose actions don't trigger alerts |
| `$wgDiscordPrivateExceptionAlerts` | bool | `false` | Enable Discord alerts for unhandled PHP exceptions (LogException hook); opt-in; rate-limited per exception type via APCu (5 min TTL) |

**Webhook URL validation:** URLs must begin with `https://` and pass `filter_var(FILTER_VALIDATE_URL)` — invalid entries are silently skipped in `NovaDiscordConfig::getWebhooksForHook()`.

**Per-hook routing:** each webhook entry can specify `'hooks'` to receive only certain events:

```php
$wgDiscordWebhooks = [
    ['url' => 'https://...', 'hooks' => ['PageSaveComplete', 'PageDeleteComplete']],
    ['url' => 'https://...'], // receives all default hooks
];
```

**LogException is explicit-only** — never included in the null (all hooks) default; must be listed explicitly in a webhook's `'hooks'` array AND `$wgDiscordPrivateExceptionAlerts` must be true.

---

## Hook Catalog

| Hook | Handler | Event |
| :--- | :--- | :--- |
| `PageSaveComplete` | `PageAlert` | Page edited or created |
| `PageDeleteComplete` | `PageAlert` | Page deleted |
| `PageMoveComplete` | `PageAlert` | Page moved/renamed |
| `PageUndeleteComplete` | `PageAlert` | Page restored from deletion |
| `ArticleRevisionVisibilitySet` | `ArticleAlert` | Revision visibility changed |
| `ArticleProtectComplete` | `ArticleAlert` | Page protection changed |
| `AfterImportPage` | `ArticleAlert` | Page imported via Special:Import |
| `ArticleMergeComplete` | `ArticleAlert` | Article revision histories merged |
| `LocalUserCreated` | `UserAlert` | User account registered |
| `BlockIpComplete` | `UserAlert` | User or IP blocked |
| `UnblockUserComplete` | `UserAlert` | User unblocked |
| `UserGroupsChanged` | `UserAlert` | User rights/groups changed |
| `UploadComplete` | `FileAlert` | File uploaded (new or new version) |
| `FileDeleteComplete` | `FileAlert` | File version deleted |
| `FileUndeleteComplete` | `FileAlert` | File version restored |
| `RenameUserComplete` | `ExtensionAlert` | User renamed (requires Renameuser extension) |
| `LogException` | `ErrorAlert` | Unhandled PHP exception (filtered; rate-limited; opt-in) |

---

## metadata

```yaml
last_updated: 30 March 2026
```
