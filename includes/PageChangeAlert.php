<?php
/**
 * NovaDiscord - Hooks
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord;

use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Page\WikiPage;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\Title\TitleFactory;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentity;
use MediaWiki\Utils\UrlUtils;

class PageChangeAlert extends DiscordAlert implements PageSaveCompleteHook {
    private UserFactory $userFactory;

    public function __construct(HttpRequestFactory $httpFactory, RevisionLookup $revLookup,
                                TitleFactory $titleFactory, UrlUtils $urlUtils, UserFactory $userFactory) {
        $this->userFactory = $userFactory;

        parent::__construct($httpFactory, $revLookup, $titleFactory, $urlUtils);
    }

    /** @inheritDoc */
    public function onPageSaveComplete($wikiPage, $userIdentity, $summary, $flags, $revision, $editResult) {
        $this->handleSaveComplete($wikiPage, $userIdentity, (string)$summary, (int)$flags, $revision, $editResult);
    }

    /**
     * Called when a page is created or edited
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageSaveComplete
     */
    private function handleSaveComplete(WikiPage $wikiPage, UserIdentity $userIdentity, string $summary, int $flags, RevisionRecord $revision, EditResult $editResult): void {
        wfDebugLog('nova-discord', 'hook onPageSaveComplete with on ' . $wikiPage);
        $hookName = 'PageSaveComplete';

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

        $this->sendAlert($hookName, $msg);
    }
}
