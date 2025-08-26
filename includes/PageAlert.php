<?php
/**
 * NovaDiscord - onPageSaveComplete Hook
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord;

use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Title\TitleFactory;
use MediaWiki\Utils\UrlUtils;

use MediaWiki\Storage\Hook\PageSaveCompleteHook;
use MediaWiki\Page\WikiPage;
use MediaWiki\User\UserIdentity;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\User\UserFactory;

use MediaWiki\Page\Hook\PageDeleteCompleteHook;
use MediaWiki\Logging\ManualLogEntry;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Permissions\Authority;

class PageAlert extends DiscordAlert implements PageSaveCompleteHook, PageDeleteCompleteHook {
    private UserFactory $userFactory;
    private WikiPageFactory $pageFactory;

    public function __construct(HttpRequestFactory $httpFactory, RevisionLookup $revLookup,
                                TitleFactory $titleFactory, UrlUtils $urlUtils, UserFactory $userFactory, WikiPageFactory $pageFactory) {
        $this->userFactory = $userFactory;
        $this->pageFactory = $pageFactory;

        parent::__construct($httpFactory, $revLookup, $titleFactory, $urlUtils);
    }


    /**
     * Stub function because hook definition doesn't allow types yet
     * @inheritDoc
     */
    public function onPageSaveComplete($wikiPage, $userIdentity, $summary, $flags, $revision, $editResult) {
        $this->handleSaveComplete($wikiPage, $userIdentity, (string)$summary, (int)$flags, $revision, $editResult);
        }

    /**
     * Called when a page is created or edited
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageSaveComplete
     */
    private function handleSaveComplete(WikiPage $wikiPage, UserIdentity $userIdentity, string $summary, int $flags, RevisionRecord $revision, EditResult $editResult): void {
        $hookName = 'PageSaveComplete';
        wfDebugLog('nova-discord', 'Completing hook ' . $hookName . ' with on ' . $wikiPage);

        $user = $this->userFactory->newFromUserIdentity($userIdentity);
        global $wgDiscordNoMinor, $wgDiscordNoNull;

        // check if hook is enabled
        if (!$this->isEnabled($hookName, $wikiPage->getTitle()->getNamespace(), $user)) {
            return;
        }

        // filter out minor/null edits if configured
        if (($wgDiscordNoMinor && $revision->isMinor()) || ($wgDiscordNoNull && $editResult->isNullEdit())) {
            return;
        }

        // new files are handled by onUploadComplete instead
        if ($wikiPage->getTitle()->inNamespace(NS_FILE)) {
            return;
        }

        $msg = wfMessage(
            ($editResult->isNew() ? 'discord-create' : 'discord-edit'),
            $this->formatUserLink($user),
            $this->formatMarkdownLink($wikiPage->getTitle(), $wikiPage->getTitle()->getCanonicalURL()),
            $this->formatRevisionText($revision),
            $this->formatMessage($summary),
        )->inContentLanguage()->plain();

        $this->sendAlert($hookName, $msg, $revision->getTimestamp());
    }

    /**
     * Called when a page is deleted
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageDeleteComplete
     */
    public function onPageDeleteComplete(ProperPageIdentity $page, Authority $deleter, string $reason, int $pageID, RevisionRecord $deletedRev, ManualLogEntry $logEntry, int $archivedRevisionCount): void {
        $hookName = 'PageDeleteComplete';
        wfDebugLog('nova-discord', 'Completing hook ' . $hookName . ' with on ' . $page);

        // TODO: possible to remove this Title call?
        $user = $this->userFactory->newFromUserIdentity($deleter->getUser());
        $page = $this->pageFactory->newFromTitle($page);

        if (!$this->isEnabled($hookName, $page->getNamespace(), $user)) {
            return;
        }

        $msg = wfMessage(
            'discord-articledelete',
            $this->formatUserLink($user),
            $this->formatMarkdownLink($page->getTitle(), $page->getTitle()->getCanonicalURL()),
            $this->formatMessage($reason),
            $archivedRevisionCount
        )->inContentLanguage()->plain();

        $this->sendAlert($hookName, $msg, $deletedRev->getTimestamp());
    }
}
