<?php
/**
 * NovaDiscord - Extension-specific hooks (Renameuser, etc.)
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord;

use MediaWiki\Context\RequestContext;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Logger\Spi as LoggerSpi;
use MediaWiki\RenameUser\Hook\RenameUserCompleteHook;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\UserFactory;
use MediaWiki\Utils\UrlUtils;

class ExtensionAlert extends DiscordAlert implements RenameUserCompleteHook {
    private UserFactory $userFactory;

    public function __construct(HttpRequestFactory $httpFactory,
                                RevisionLookup $revLookup,
                                TitleFactory $titleFactory,
                                UrlUtils $urlUtils,
                                UserFactory $userFactory,
                                NovaDiscordConfig $novaConfig,
                                LoggerSpi $loggerSpi) {
        $this->userFactory = $userFactory;
        parent::__construct($httpFactory, $revLookup, $titleFactory, $urlUtils, $novaConfig, $loggerSpi);
    }

    /**
     * Called when a user is renamed (Renameuser extension / MW core)
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/RenameUserComplete
     */
    public function onRenameUserComplete(int $uid, string $old, string $new): void {
        $hookName = 'RenameUserComplete';

        $user = $this->userFactory->newFromUserIdentity(RequestContext::getMain()->getUser());

        if (!$this->isEnabled($hookName, null, $user)) {
            return;
        }

        $renamedUserPage = $this->userFactory->newFromName($new)->getUserPage();

        $msg = wfMessage(
            'discord-renameusercomplete',
            $this->formatUserLink($user),
            "*$old*",
            $this->formatMarkdownLink($new, $renamedUserPage->getCanonicalURL())
        )->inContentLanguage()->plain();
        $this->sendAlert($hookName, $msg, time());
    }
}
