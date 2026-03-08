<?php
/**
 * NovaDiscord - Hooks
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord;

use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\User;
use MediaWiki\Utils\UrlUtils;

abstract class DiscordAlert {
    private HttpRequestFactory $httpFactory;
    private RevisionLookup $revLookup;
    private TitleFactory $titleFactory;
    private UrlUtils $urlUtils;
    protected NovaDiscordConfig $novaConfig;

    public function __construct(HttpRequestFactory $httpFactory,
                                RevisionLookup $revLookup,
                                TitleFactory $titleFactory,
                                UrlUtils $urlUtils,
                                NovaDiscordConfig $novaConfig) {
        $this->httpFactory = $httpFactory;
        $this->revLookup = $revLookup;
        $this->titleFactory = $titleFactory;
        $this->urlUtils = $urlUtils;
        $this->novaConfig = $novaConfig;
    }

    // handles sending a webhook to Discord
    protected function sendAlert(string $hookName, string $msg, int $timestamp): void {
        wfDebugLog('nova-discord', 'Triggering discord webhook with ' . $hookName . ' and ' . $msg);

        $urls = $this->novaConfig->getWebhooksForHook($hookName);
        if ($urls === []) {
            return;
        }

        $stripped = preg_replace('/\s+/', ' ', $msg);

        // webhook payload
        $json_data = [
            'content' => "$stripped",
            'allowed_mentions' => [
                'parse' => []
            ]
        ];

        foreach ($urls as $hook) {
            $request = $this->httpFactory->create($hook, [
                'method' => 'POST',
                'postData' => json_encode($json_data),
            ], __METHOD__);

            // we don't care about if this succeeds, so no callback here
            $request->setHeader('Content-Type', 'application/json');
            $request->execute();
        }
    }

    // checks if alert should be sent based on bot/namespace/user filters
    protected function isEnabled(string $hookName, ?int $namespace, User $user): bool {
        // So this shows up in testing and not just when bots are disabled
        if (!$user instanceof User) {
            throw new InvalidArgumentException('$user must be of type User');
        } elseif ($this->novaConfig->isNoBots() && $user->isBot()) {
            return false;
        }

        if ($namespace !== null && in_array($namespace, $this->novaConfig->getDisabledNS())) {
            return false;
        }

        $userName = $user->getName();
        if ($userName && in_array($userName, $this->novaConfig->getDisabledUsers())) {
            return false;
        }

        return true;
    }

    /* --- Util Functions --- */

    protected static function truncateString(string $text, int $length): string {
        if (is_int($length) && $length > 0 && mb_strlen($text) > $length) {
            return mb_substr($text, 0, $length) . '...';
        }

        return $text;
    }

    protected static function formatBytes(int $bytes, int $precision = 2): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Creates a formatted markdown link based on text and given URL.
     * Link previews are always suppressed to keep Discord clean.
     */
    protected static function formatMarkdownLink(string $text, string $url): string {
        return "[" . $text . "](<" . $url . ">)";
    }

    /**
     * Returns a markdown-formatted string that links to the user's page and their talk & contrib pages
     */
    protected function formatUserLink(User $user): string {
        $contribLabel = wfMessage('discord-contribs')->inContentLanguage()->text();
        $contribLink = SpecialPage::getTitleFor('Contributions', $user->getName())->getCanonicalURL();

        // fall back to contributions link if its an anon user
        $userLabel = $this->truncateString($user->getName(), 25);
        $userLink = !$user->isAnon() ? $user->getUserPage()->getCanonicalURL() : $contribLink;

        $talkLabel = wfMessage('discord-talk')->inContentLanguage()->text();
        $talkLink = $user->getTalkPage()->getCanonicalURL();

        return wfMessage(
            'discord-userlinks',
            $this->formatMarkdownLink($userLabel, $userLink),
            $this->formatMarkdownLink($talkLabel, $talkLink),
            $this->formatMarkdownLink($contribLabel, $contribLink),
        )->inContentLanguage()->text();
    }

    protected function formatMessage(string $message): string {
        // TODO: adjust as needed
        $invalidChars = '`@';

        if (mb_strlen($message ?? '') == 0) {
            return '';
        }

        $trimmed = $this->truncateString(addcslashes($message, $invalidChars), $this->novaConfig->getMaxChars());

        return '`' . $trimmed . '`';
    }

    /** create formatted text for a specific revision */
    protected function formatRevisionText(RevisionRecord $revision): string {
        $linkTarget = $revision->getPageAsLinkTarget();
        $title = $this->titleFactory->newFromLinkTarget($linkTarget);

        $diffUrl = $title->getCanonicalURL([
            'diff' => 'prev',
            'oldid' => $revision->getId(),
        ]);

        $diff = $this->formatMarkdownLink(wfMessage('discord-diff')->inContentLanguage()->text(), $diffUrl);
        $minor = $revision->isMinor() ? wfMessage('discord-minor')->inContentLanguage()->text() : '';

        // TODO: not happy with this, can we get this easier?
        $delta = null;
        if ($revision->getParentId()) {
            $parent = $this->revLookup->getPreviousRevision($revision);
            if ($parent) {
                $delta = $revision->getSize() - $parent->getSize();
            }
        }

        $sizeText = wfMessage('discord-size', $delta !== null ? sprintf('%+d', $delta) : (string)$revision->getSize())
            ->inContentLanguage()->text();

        return wfMessage('discord-revisionlinks', $diff, $minor, $sizeText)->inContentLanguage()->text();
    }
}
