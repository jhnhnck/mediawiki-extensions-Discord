# NovaDiscord

A modernized MediaWiki extension for sending notifications to a Discord webhook from MediaWiki. When certain events occur on your wiki, such as new edits, alerts can be sent as messages to a Discord channel via a webhook.

Live demo: <https://attuproject.org> ([Discord](https://links.attuproject.org/invite)|[Config](/jhnhnck/attu-wiki))

<!-- TODO: new example image
<p align="center">
  <img src="https://i.imgur.com/tCehglJ.png" alt="Example"/>
</p>
-->

## Requirements

- **Webhook URL**: This can be obtained within the "Edit Channel" menu under "Integrations". You can find more [detailed instructions here](https://support.discord.com/hc/en-us/articles/228383668-Intro-to-Webhooks).
- **MediaWiki**: This extension aims to always support the [latest LTS release](https://www.mediawiki.org/wiki/Version_lifecycle).
  - Use the branch that is equal to or below your version. For example, if you are using MediaWiki 1.44, use the `REL1_44` branch.
  - There is no guarantee of support for versions of MediaWiki that are considered end-of-life.
  - The `trunk` branch may contain changes that are only applicable to the cutting-edge alpha version of MediaWiki.

*No extra dependencies are required*

## Installation

1. Clone this repository to your MediaWiki installation's `extensions` folder:

```bash
git clone --depth=1 --branch 'REL1_44' https://github.com/jhnhnck/mediawiki-extensions-NovaDiscord ./extensions/NovaDiscord;
```

1. Configure the extension within your `LocalSettings.php` file:

```php
// Load the extension
wfLoadExtension('NovaDiscord');

// Set the webhook URL(s)
$wgDiscordWebhooks = [
    ['url' => 'https://discord.com/api/webhooks/...'],
];
```

## Configuration

This extension can be configured using the `LocalSettings.php` file in your MediaWiki installation. Only `$wgDiscordWebhooks` is required; all other settings are optional.

### Webhooks

| Variable | Type | Description | Default |
| --- | --- | --- | --- |
| `$wgDiscordWebhooks` **(required)** | array | List of webhook configurations. Each entry must have a `url` key and an optional `hooks` key (see below). | `[]` |
| `$wgDiscordDefaultHooks` | array\|null | Default set of hook names enabled for webhooks that do not specify their own `hooks` key. `null` means all hooks are enabled. | `null` |

Each entry in `$wgDiscordWebhooks` supports:

- `url` *(required)*: The [Discord webhook URL](https://support.discord.com/hc/en-us/articles/228383668-Intro-to-Webhooks).
- `hooks` *(optional)*: Array of hook names this webhook should receive. If absent, falls back to `$wgDiscordDefaultHooks`.

```php
// Send all events to one webhook, and only page edits to another:
$wgDiscordWebhooks = [
    ['url' => 'https://discord.com/api/webhooks/...'],
    ['url' => 'https://discord.com/api/webhooks/...', 'hooks' => ['PageSaveComplete']],
];

// Only send page saves and deletions by default (applies to all webhooks without explicit 'hooks'):
$wgDiscordDefaultHooks = ['PageSaveComplete', 'PageDeleteComplete'];
```

### Filters

| Variable | Type | Description | Default |
| --- | --- | --- | --- |
| `$wgDiscordNoBots` | bool | Do not send notifications triggered by a [bot account](https://www.mediawiki.org/wiki/Manual:Bots) | `true` |
| `$wgDiscordNoMinor` | bool | Do not send notifications for [minor edits](https://www.mediawiki.org/wiki/Help:Minor_edit) | `false` |
| `$wgDiscordNoNull` | bool | Do not send notifications for [null edits](https://www.mediawiki.org/wiki/Manual:Purge#Null_edits) | `true` |
| `$wgDiscordDisabledNS` | int\|array | List of namespace **IDs** to suppress notifications for (see [below](#resources)) | `[]` |
| `$wgDiscordDisabledUsers` | string\|array | List of usernames whose actions should not trigger notifications | `["Redirect fixer"]` |

### Other

| Variable | Type | Description | Default |
| --- | --- | --- | --- |
| `$wgDiscordMaxChars` | int | Maximum characters for user-provided text (summaries, reasons). Longer text is truncated. | `500` |
| `$wgDiscordPrivateExceptionAlerts` | bool | Send a webhook alert when an unhandled PHP exception is logged; rate-limited to one alert per exception fingerprint per 5 minutes (requires APCu); user-facing errors are filtered out | `false` |

## Hooks used

- `PageDeleteComplete` - Page deletions
- `PageMoveComplete` - Page moves
- `PageSaveComplete` - New edits to pages and page creations
- `PageUndeleteComplete` - Page restorations
- `AfterImportPage` - Page was imported
- `ArticleMergeComplete` - Article histories were merged
- `ArticleProtectComplete` - Page protections
- `ArticleRevisionVisibilitySet` - Revision visibility changes
- `BlockIpComplete` - User blocked
- `FileDeleteComplete` - File revision was deleted
- `FileUndeleteComplete` - File revision was restored
- `LocalUserCreated` - User registrations
- `UnblockUserComplete` - User unblocked
- `UploadComplete` - File was uploaded
- `UserGroupsChanged` - User rights changed

### [Renameuser](https://www.mediawiki.org/wiki/Extension:Renameuser)

- `RenameUserComplete` - Rename was completed

### Exception Alerting (opt-in)

- `LogException` - Unhandled PHP exception; requires `$wgDiscordPrivateExceptionAlerts = true`

## Resources

As we use Namespace IDs, the following resources might be helpful:

- [Built in namespaces' IDs](https://www.mediawiki.org/wiki/Manual:Namespace#Built-in_namespaces)
- [Extension default namespaces](https://www.mediawiki.org/wiki/Extension_default_namespaces)

<!--
## Translation

You can submit translations for this extension on [Translatewiki.net](https://translatewiki.net/wiki/Special:Translate/mwgithub-mw-discord).
-->

## Development

### Running Test Cases

Run with `composer test` or the following if you don't have composer installed. (Docker required)

```zsh
docker compose --file .dev/docker-compose.yml up --build \
    --abort-on-container-exit \
    --exit-code-from job-tests \
    --attach job-tests \
    --remove-orphans
```

### Test Coverage

Integration tests are available for these hooks:

- `ArticleProtectComplete` - Page protection webhook notifications
- `ArticleRevisionVisibilitySet` - Revision visibility change webhook notifications
- `BlockIpComplete` - User blocking webhook notifications
- `LocalUserCreated` - User registration webhook notifications
- `PageDeleteComplete` - Page deletion webhook notifications
- `PageMoveComplete` - Page move webhook notifications
- `PageSaveComplete` - Edit and page creation webhook notifications
- `PageUndeleteComplete` - Page restoration webhook notifications
- `UnblockUserComplete` - User unblocking webhook notifications

These hooks are not currently tested due to technical limitations:

- `AfterImportPage` - Requires XML import infrastructure, complex setup
- `ArticleMergeComplete` - Requires MergeHistory functionality, complex setup
- `FileDeleteComplete` - Requires file upload infrastructure
- `FileUndeleteComplete` - Requires file upload infrastructure
- `RenameUserComplete` - Requires Renameuser extension
- `UploadComplete` - Requires file upload infrastructure
- `UserGroupsChanged` - Requires complex setup to trigger with proper performer context

## License

This extension is available under the MIT license. You can [see here](LICENSE) for more information.

This extension was based off the work of jayktaylor's [mw-discord](https://github.com/jayktaylor/mw-discord) project.
