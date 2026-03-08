<?php
/**
 * NovaDiscord - Article and revision hooks
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord;

use MediaWiki\Context\RequestContext;
use MediaWiki\Hook\AfterImportPageHook;
use MediaWiki\Hook\ArticleMergeCompleteHook;
use MediaWiki\Hook\ArticleRevisionVisibilitySetHook;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Page\Hook\ArticleProtectCompleteHook;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\UserFactory;
use MediaWiki\Utils\UrlUtils;

class ArticleAlert extends DiscordAlert implements ArticleRevisionVisibilitySetHook, ArticleProtectCompleteHook, AfterImportPageHook, ArticleMergeCompleteHook {
    private UserFactory $userFactory;

    public function __construct(HttpRequestFactory $httpFactory,
                                RevisionLookup $revLookup,
                                TitleFactory $titleFactory,
                                UrlUtils $urlUtils,
                                UserFactory $userFactory,
                                NovaDiscordConfig $novaConfig) {
        $this->userFactory = $userFactory;
        parent::__construct($httpFactory, $revLookup, $titleFactory, $urlUtils, $novaConfig);
    }

    /**
     * Called after committing revision visibility changes to the database
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleRevisionVisibilitySet
     */
    public function onArticleRevisionVisibilitySet($title, $ids, $visibilityChangeMap) {
        $hookName = 'ArticleRevisionVisibilitySet';

        $user = $this->userFactory->newFromUserIdentity(RequestContext::getMain()->getUser());

        if (!$this->isEnabled($hookName, $title->getNamespace(), $user)) {
            return;
        }

        $msg = wfMessage(
            'discord-revvisibility',
            $this->formatUserLink($user),
            count($visibilityChangeMap),
            $this->formatMarkdownLink($title, $title->getCanonicalURL())
        )->inContentLanguage()->plain();
        $this->sendAlert($hookName, $msg, time());
    }

    /**
     * Called when a page is protected (or unprotected)
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleProtectComplete
     */
    public function onArticleProtectComplete($wikiPage, $user, $protect, $reason) {
        $hookName = 'ArticleProtectComplete';

        if (!$this->isEnabled($hookName, $wikiPage->getTitle()->getNamespace(), $user)) {
            return;
        }

        $msg = wfMessage(
            'discord-articleprotect',
            $this->formatUserLink($user),
            $this->formatMarkdownLink($wikiPage->getTitle(), $wikiPage->getTitle()->getCanonicalURL()),
            $this->formatMessage($reason),
            implode(", ", $protect)
        )->inContentLanguage()->plain();
        $this->sendAlert($hookName, $msg, time());
    }

    /**
     * Called when a page is imported
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/AfterImportPage
     */
    public function onAfterImportPage($title, $foreignTitle, $revCount, $sRevCount, $pageInfo) {
        $hookName = 'AfterImportPage';

        $user = $this->userFactory->newFromUserIdentity(RequestContext::getMain()->getUser());

        if (!$this->isEnabled($hookName, $title->getNamespace(), $user)) {
            return;
        }

        $msg = wfMessage(
            'discord-afterimportpage',
            $this->formatUserLink($user),
            $this->formatMarkdownLink($title, $title->getCanonicalURL()),
            $revCount,
            $sRevCount
        )->inContentLanguage()->plain();
        $this->sendAlert($hookName, $msg, time());
    }

    /**
     * Called when article histories are merged
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleMergeComplete
     */
    public function onArticleMergeComplete($targetTitle, $destTitle) {
        $hookName = 'ArticleMergeComplete';

        $user = $this->userFactory->newFromUserIdentity(RequestContext::getMain()->getUser());

        if (!$this->isEnabled($hookName, $destTitle->getNamespace(), $user)) {
            return;
        }

        $msg = wfMessage(
            'discord-articlemergecomplete',
            $this->formatUserLink($user),
            $this->formatMarkdownLink($targetTitle, $targetTitle->getCanonicalURL()),
            $this->formatMarkdownLink($destTitle, $destTitle->getCanonicalURL())
        )->inContentLanguage()->plain();
        $this->sendAlert($hookName, $msg, time());
    }
}
