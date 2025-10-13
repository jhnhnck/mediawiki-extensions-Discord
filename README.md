# NovaDiscord

A modernized MediaWiki extension rewrite (wip) for sending notifications to a Discord webhook from MediaWiki. When certain events occur on your wiki, such as new edits, alerts can be sent as messages to a Discord channel via a webhook.

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

// Set the webhook URL(s) (string or array)
$wgDiscordWebhookURL = ['https://discord.com/api/webhooks/...'];
```

## Configuration

This extension can be configured using the `LocalSettings.php` file in your MediaWiki installation. Only `$wgDiscordWebhookURL` is required; all other setting are optional and used to customize the behavior.

| Variable | Type | Description | Default |
| --- | --- | --- | --- |
| `$wgDiscordWebhookURL` **(required)** | string\|array | [Webhook URL](https://support.discord.com/hc/en-us/articles/228383668-Intro-to-Webhooks) for Discord channel; multiple URLs can be provided as an array of strings | *none* |
| `$wgDiscordNoBots` | bool | Do not send notifications that are triggered by a [bot account](https://www.mediawiki.org/wiki/Manual:Bots) | `true` |
| `$wgDiscordNoMinor` | bool | Do not send notifications that are for [minor edits](https://www.mediawiki.org/wiki/Help:Minor_edit) | `false` |
| `$wgDiscordNoNull` | bool | Do not send notifications for [null edits](https://www.mediawiki.org/wiki/Manual:Purge#Null_edits) | `true` |
| `$wgDiscordSuppressPreviews` | bool | Force previews for links in Discord messages to be suppressed | `true` |
| `$wgDiscordMaxChars` | int | Maximum amount of characters for user-generated text (e.g summaries, reasons). Set to `null` to disable truncation | `500` |
| `$wgDiscordMaxCharsUsernames` | int | Maximum amount of characters for usernames. Set to `null` to disable truncation | `25` |
| `$wgDiscordDisabledHooks` | string\|array | List of hooks to disable sending webhooks for (see [below](#hooks-used)) | `[]` |
| `$wgDiscordDisabledNS` | int\|array | List of namespace **IDs** to disable sending webhooks for. (see [below](#resources)) | `[]` |
| `$wgDiscordDisabledUsers` | string\|array | List of users whose performed actions shouldn't send webhooks | `[]` |
| `$wgDiscordPrependTimestamp` | bool | Prepend a timestamp (in UTC) to all sent messages. The format can be changed by editing the MediaWiki message `discord-timestampformat` | `false` |
| `$wgDiscordUseEmojis` | bool | Prepend emojis to different types of messages to help distinguish them | `false` |
| `$wgDiscordEmojis` | string associative array | Map of hook names and their associated emojis to prepend to messages if `$wgDiscordUseEmojis` is enabled | See [extension.json](/extension.json#L30) |

## Compatibility

For now, compatibility with the original extension has been kept for the most part, any differences will be listed below.

- `$wgDiscordMaxChars`: Default changed to `500` characters as a temporary bug fix for avoiding issues with the total webhook max length
- `discord-timestampformat`: Format string changed to use Discord's built-in timestamp support
- `PageUndeleteComplete` is now used instead of the deprecated `ArticleUndelete` hook; may need to change config for `$wgDiscordEmojis` or `$wgDiscordDisabledHooks`

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

### [Approved Revs](https://www.mediawiki.org/wiki/Extension:Approved_Revs)

- `ApprovedRevsRevisionApproved` - Revision was approved
- `ApprovedRevsRevisionUnapproved` - Revision was unapproved
- `ApprovedRevsFileRevisionApproved` - File revision was approved
- `ApprovedRevsFileRevisionUnapproved` - File revision was unapproved

### [Renameuser](https://www.mediawiki.org/wiki/Extension:Renameuser)

- `RenameUserComplete` - Rename was completed

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

Requires `docker`; can also be ran with `composer test`

```zsh
docker compose --file .dev/docker-compose.yml up --build \
    --abort-on-container-exit \
    --exit-code-from job-tests \
    --attach job-tests \
    --remove-orphans
```

## License

This extension is available under the MIT license. You can [see here](LICENSE) for more information.

This extension was based off the work of jayktaylor's [mw-discord](https://github.com/jayktaylor/mw-discord) project.
