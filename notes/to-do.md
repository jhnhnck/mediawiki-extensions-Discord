# NovaDiscord to-do list

_see the [meta](#meta) section at the end of this file for format reference._

---

## tasks

### code

- ⭕ `low priority` `medium effort` investigate removing `Title` factory call in `PageDeleteComplete` handler if MW API allows (`includes/PageAlert.php:117`)
- ⭕ `low priority` `low effort` include `$restoredRevisionCount` in page undelete message (`includes/PageAlert.php:203`)
- ⭕ `low priority` `medium effort` simplify delta retrieval in `getDiffBlock` (`includes/DiscordAlert.php:188`)
- ⭕ `low priority` `low effort` finalize invalid chars list in `formatMessage` (`includes/DiscordAlert.php:162`)

### tests

- ⭕ `medium priority` `medium effort` write proper UserGroupsChanged integration test; requires complex setup to trigger hook with valid performer (`tests/phpunit/integration/UserGroupsChangedHookTest.php:15`)
- ⭕ `low priority` `low effort` add test for restoring specific revisions in PageUndeleteComplete (`tests/phpunit/integration/PageUndeleteCompleteHookTest.php:12`)
- ⭕ `low priority` `high effort` implement stub-only tests: AfterImportPage, ArticleMergeComplete, FileDeleteComplete, FileUndeleteComplete, UploadComplete, RenameUserComplete

### docs

- ⭕ `low priority` `low effort` add new example image to README (`README.md:7`)

### meta

- `high priority` `low effort` assign any to-dos without an effort or category; update priorities; move completed and sort all

---

## completed

---

## meta

### format

open item: `- ⭕ \`priority\` \`effort\` description`

completed item: `- 🔴 \`26 March 2026\` description`

priority levels (highest to lowest): `high priority`, `medium priority`, `low priority`, `future idea`

effort levels: `no effort`, `low effort`, `medium effort`, `high effort`, `very high effort`

items without a checkbox are recurring; they repeat each maintenance cycle rather than being tracked as one-time work. these live in the `## meta` section.

when an item is completed, move it to the `# completed` section under the appropriate category, strip the priority/effort tags, and add a date stamp. sort completed entries chronologically within each category (oldest first). remove completed entries that are no longer relevant and not referenced by any open to-do. increment `total_completed` in the metadata each time an item is marked done.

when adding a new item, sort it into the appropriate section by topic, or add a new section if none fits. assign priority and effort tags. if the scope, priority, or effort is unclear, ask clarifying questions before adding. split larger projects into multiple entries.

### sections

- **to-do** - active items grouped by area; sorted within each section by priority (high first)
- **completed** - done items kept for reference; sorted chronologically; pruned when no longer relevant
- **meta** - this section; describes the doc format and holds recurring maintenance tasks

### metadata

```yaml
last_updated: 30 March 2026
total_completed: 0
```
