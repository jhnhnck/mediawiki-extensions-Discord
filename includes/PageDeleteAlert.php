<?php
/**
 * NovaDiscord - onPageDeleteComplete Hook
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord;

use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Logging\ManualLogEntry;
use MediaWiki\Page\Hook\PageDeleteCompleteHook;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\UserFactory;
use MediaWiki\Utils\UrlUtils;

class PageDeleteAlert extends DiscordAlert implements PageDeleteCompleteHook {
    private UserFactory $userFactory;
    private WikiPageFactory $pageFactory;

    public function __construct(HttpRequestFactory $httpFactory, RevisionLookup $revLookup, TitleFactory $titleFactory,
                                UrlUtils $urlUtils, UserFactory $userFactory, WikiPageFactory $pageFactory) {
        $this->userFactory = $userFactory;
        $this->pageFactory = $pageFactory;

        parent::__construct($httpFactory, $revLookup, $titleFactory, $urlUtils);
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
        DiscordUtils::handleDiscord($hookName, $msg);
        return;
    }
}
