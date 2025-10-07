<?php
/**
 * NovaDiscord - onPageSaveComplete Hook
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord;

use MediaWiki\Hook\PageMoveCompleteHook;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Logging\ManualLogEntry;
use MediaWiki\Page\Hook\PageDeleteCompleteHook;
use MediaWiki\Page\Hook\PageUndeleteCompleteHook;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Page\WikiPage;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentity;
use MediaWiki\Utils\UrlUtils;
use MediaWiki\Linker\LinkTarget;

class PageAlert extends DiscordAlert implements PageSaveCompleteHook, PageDeleteCompleteHook, PageMoveCompleteHook, PageUndeleteCompleteHook {
    private UserFactory $userFactory;
    private WikiPageFactory $pageFactory;

    public function __construct(HttpRequestFactory $httpFactory,
                                RevisionLookup $revLookup,
                                TitleFactory $titleFactory,
                                UrlUtils $urlUtils,
                                UserFactory $userFactory,
                                WikiPageFactory $pageFactory) {
        $this->userFactory = $userFactory;
        $this->pageFactory = $pageFactory;

        parent::__construct($httpFactory, $revLookup, $titleFactory, $urlUtils);
    }

    /**
     * Stub function because hook definition doesn't allow types yet
     * @inheritDoc
     */
    public function onPageSaveComplete($wikiPage, $userIdentity, $summary, $flags, $revision, $editResult) {
        $this->handlePageSave($wikiPage, $userIdentity, (string)$summary, (int)$flags, $revision, $editResult);
    }

    /**
     * Called when a page is created or edited
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageSaveComplete
     */
    private function handlePageSave(WikiPage $wikiPage,
                                        UserIdentity $userIdentity,
                                        string $summary,
                                        int $flags,
                                        RevisionRecord $revision,
                                        EditResult $editResult): void {
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
    public function onPageDeleteComplete(ProperPageIdentity $page,
                                         Authority $deleter,
                                         string $reason,
                                         int $pageID,
                                         RevisionRecord $deletedRev,
                                         ManualLogEntry $logEntry,
                                         int $archivedRevisionCount): void {
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

    /**
     * Stub function because hook definition doesn't allow types yet
     * @inheritDoc
     */
    public function onPageMoveComplete($old, $new, $user, $pageid, $redirid, $reason, $revision) {
        $this->handlePageMove($old, $new, $user, $pageid, $redirid, $reason, $revision);
    }

    /**
     * Called when a page is moved
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageMoveComplete
     */
    private function handlePageMove(LinkTarget $old, LinkTarget $new, UserIdentity $userIdentity, int $pageid, int $redirid, string $reason, RevisionRecord $revision): void {
        $hookName = 'TitleMoveComplete';
        wfDebugLog('nova-discord', 'Completing hook ' . $hookName . ' with on ' . $old);

        $user = $this->userFactory->newFromUserIdentity($userIdentity);

        // check if hook is enabled
        if (!$this->isEnabled($hookName, $old->getNamespace(), $user)) {
            return;
        }

        $oldPage = $this->pageFactory->newFromLinkTarget($old);
        $newPage = $this->pageFactory->newFromLinkTarget($new);

        $msg = wfMessage(
            'discord-titlemove',
            $this->formatUserLink($user),
            $this->formatMarkdownLink($oldPage->getTitle(), $oldPage->getTitle()->getCanonicalURL()),
            $this->formatMarkdownLink($newPage->getTitle(), $newPage->getTitle()->getCanonicalURL()),
            $this->formatMessage($reason),
            $this->formatRevisionText($revision),
        )->inContentLanguage()->plain();

        $this->sendAlert($hookName, $msg, $revision->getTimestamp());
    }

    /**
     * Occurs after the undelete page request has been processed
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageUndeleteComplete
     */
	public function onPageUndeleteComplete(ProperPageIdentity $page,
                                           Authority $restorer,
                                           string $reason,
                                           RevisionRecord $restoredRev,
                                           ManualLogEntry $logEntry,
                                           int $restoredRevisionCount,
                                           bool $created,
                                           array $restoredPageIds): void {
        $hookName = 'PageUndeleteComplete';
        wfDebugLog('nova-discord', 'Completing hook ' . $hookName . ' with on ' . $page);

        $user = $this->userFactory->newFromUserIdentity($restorer->getUser());
        $page = $this->pageFactory->newFromTitle($page);

        // check if hook is enabled
        if (!$this->isEnabled($hookName, $page->getNamespace(), $user)) {
            return;
        }

        // TODO: we can also include $restoredRevisionCount here somewhere
        $msg = wfMessage(
            'discord-articleundelete',
            $this->formatUserLink($user),
            ($created ? '' : wfMessage('discord-undeleterev')->inContentLanguage()->text()),
            $this->formatMarkdownLink($page->getTitle(), $page->getTitle()->getCanonicalURL()),
            $this->formatMessage($reason),
        )->inContentLanguage()->plain();

        $this->sendAlert($hookName, $msg, $restoredRev->getTimestamp());
    }
}
