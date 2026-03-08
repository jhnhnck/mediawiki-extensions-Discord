<?php
/**
 * NovaDiscord - User-related hooks
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord;

use MediaWiki\Auth\Hook\LocalUserCreatedHook;
use MediaWiki\Block\DatabaseBlock;
use MediaWiki\Hook\BlockIpCompleteHook;
use MediaWiki\Hook\UnblockUserCompleteHook;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\Hook\UserGroupsChangedHook;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentity;
use MediaWiki\Utils\UrlUtils;

class UserAlert extends DiscordAlert implements LocalUserCreatedHook, BlockIpCompleteHook, UnblockUserCompleteHook, UserGroupsChangedHook {
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
     * Called when a user is created
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/LocalUserCreated
     */
    public function onLocalUserCreated($user, $autocreated) {
        $hookName = 'LocalUserCreated';

        if (!$this->isEnabled($hookName, null, $user)) {
            return;
        }

        $msg = wfMessage('discord-localusercreated', $this->formatUserLink($user))
            ->inContentLanguage()->plain();
        $this->sendAlert($hookName, $msg, time());
    }

    /**
     * Called when a user is blocked
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/BlockIpComplete
     */
    public function onBlockIpComplete($block, $user, $priorBlock) {
        $hookName = 'BlockIpComplete';

        if (!$this->isEnabled($hookName, null, $user)) {
            return;
        }

        $expiry = $block->getExpiry();
        if ($expires = strtotime($expiry)) {
            $expiryMsg = sprintf('%s', date(wfMessage('discord-blocktimeformat')->inContentLanguage()->text(), $expires));
        } else {
            $expiryMsg = $expiry;
        }

        $target = $block->getTargetUserIdentity();
        if ($target !== null) {
            $targetLinks = $this->formatUserLink($this->userFactory->newFromUserIdentity($target));
        } else {
            $targetName = $block->getTargetName();
            $targetLinks = wfMessage('discord-userlinks', $targetName, 'n/a', 'n/a')->inContentLanguage()->text();
        }

        $msg = wfMessage(
            'discord-blockipcomplete',
            $this->formatUserLink($user),
            $targetLinks,
            $this->formatMessage($block->getReasonComment()->text),
            $expiryMsg
        )->inContentLanguage()->plain();
        $this->sendAlert($hookName, $msg, time());
    }

    /**
     * Called when a user is unblocked
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnblockUserComplete
     */
    public function onUnblockUserComplete($block, $user) {
        $hookName = 'UnblockUserComplete';

        if (!$this->isEnabled($hookName, null, $user)) {
            return;
        }

        $target = $block->getTargetUserIdentity();
        if ($target !== null) {
            $targetLinks = $this->formatUserLink($this->userFactory->newFromUserIdentity($target));
        } else {
            $targetName = $block->getTargetName();
            $targetLinks = wfMessage('discord-userlinks', $targetName, 'n/a', 'n/a')->inContentLanguage()->text();
        }

        $msg = wfMessage('discord-unblockusercomplete', $this->formatUserLink($user), $targetLinks)
            ->inContentLanguage()->text();
        $this->sendAlert($hookName, $msg, time());
    }

    /**
     * Called when a user's rights are changed
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserGroupsChanged
     */
    public function onUserGroupsChanged($user, $added, $removed, $performer, $reason, $oldUGMs, $newUGMs) {
        $hookName = 'UserGroupsChanged';

        if ($performer === false) {
            // Rights were changed by autopromotion, do nothing
            return;
        }

        if (!$this->isEnabled($hookName, null, $performer)) {
            return;
        }

        if ($user instanceof UserIdentity && !($user instanceof User)) {
            $user = $this->userFactory->newFromUserIdentity($user);
        }

        $msg = wfMessage(
            'discord-usergroupschanged',
            $this->formatUserLink($performer),
            $this->formatUserLink($user),
            $this->formatMessage($reason ?: ''),
            ((count($added) > 0) ? ('+ ' . implode(', ', $added)) : ''),
            ((count($removed) > 0) ? ('- ' . implode(', ', $removed)) : '')
        )->inContentLanguage()->plain();
        $this->sendAlert($hookName, $msg, time());
    }
}
