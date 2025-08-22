<?php
/**
 * NovaDiscord - Utilities
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\User\UserIdentity;

class DiscordUtils {
    /**
     * Checks if criteria is met for this action to be cancelled
     */
    public static function isDisabled($hook, $ns, $user) {
        global $wgDiscordDisabledHooks, $wgDiscordDisabledNS, $wgDiscordDisabledUsers;

        if (is_array($wgDiscordDisabledHooks)) {
            if (in_array(strtolower($hook), array_map('strtolower', $wgDiscordDisabledHooks))) {
                // Hook is disabled, return true
                return true;
            }
        } else {
            wfDebugLog('nova-discord', 'The value of $wgDiscordDisabledHooks is not valid and therefore all hooks are enabled.');
        }
        if (is_array($wgDiscordDisabledNS)) {
            if ($ns !== null) {
                $ns = (int)$ns;
                if (in_array($ns, $wgDiscordDisabledNS)) {
                    // Namespace is disabled, return true
                    return true;
                }
            }
        } else {
            wfDebugLog('nova-discord', 'The value of $wgDiscordDisabledNS is not valid and therefore all namespaces are enabled.');
        }
        if (is_array($wgDiscordDisabledUsers)) {
            if ($user !== null) {
                if ($user instanceof UserIdentity) {
                    $user = MediaWikiServices::getInstance()->getUserFactory()->newFromUserIdentity($user);
                }

                if ($user instanceof User) {
                    if (in_array($user->getName(), $wgDiscordDisabledUsers)) {
                        // User shouldn't trigger a message, return true
                        return true;
                    }
                }
            }
        } else {
            wfDebugLog('nova-discord', 'The value of $wgDiscordDisabledUsers is not valid and therefore all users can trigger messages.');
        }

        return false;
    }

    /**
     * Handles sending a webhook to Discord using cURL
     */
    public static function handleDiscord($hookName, $msg) {
        wfDebugLog('nova-discord', 'Attempting to handle ' . $hookName . ': ' . $msg);

        global $wgDiscordWebhookURL, $wgDiscordEmojis, $wgDiscordUseEmojis, $wgDiscordPrependTimestamp;

        if (!$wgDiscordWebhookURL) {
            // There's nothing in here, so we won't do anything
            return false;
        }

        $urls = [];

        if (is_array($wgDiscordWebhookURL)) {
            $urls = array_merge($urls, $wgDiscordWebhookURL);
        } elseif (is_string($wgDiscordWebhookURL)) {
            $urls[] = $wgDiscordWebhookURL;
        } else {
            wfDebugLog('nova-discord', 'The value of $wgDiscordWebhookURL is not valid and therefore no webhooks could be sent.');
            return false;
        }

        // Strip whitespace to just one space
        $stripped = preg_replace('/\s+/', ' ', $msg);

        if ($wgDiscordPrependTimestamp) {
            // Add timestamp
            $dateString = gmdate(wfMessage('discord-timestampformat')->inContentLanguage()->text());
            $stripped = $dateString . ' ' . $stripped;
        }

        if ($wgDiscordUseEmojis) {
            // Add emoji
            $emoji = $wgDiscordEmojis[$hookName];
            $stripped = $emoji . ' ' . $stripped;
        }

        $json_data = [
            'content' => "$stripped",
            'allowed_mentions' => [
                'parse' => []
            ]
        ];

        $factory = MediaWikiServices::getInstance()->getHttpRequestFactory();

        foreach ($urls as &$hook) {
            $request = $factory->create($hook, [
                'method' => 'POST',
                'postData' => json_encode($json_data),
            ], __METHOD__);

            $request->setHeader('Content-Type', 'application/json');

            // we don't care about if this succeeds, so no callback here
            $request->execute();
        }

        return true;
    }

    /**
     * Creates links for a specific MediaWiki User object
     */
    public static function createUserLinks($user) {
        global $wgDiscordMaxCharsUsernames;

        if ($user instanceof UserIdentity) {
            // If we were passed a UserIdentity object, get the relevant user.
            $user = MediaWikiServices::getInstance()->getUserFactory()->newFromUserIdentity($user);
        }

        if ($user instanceof User) {
            $isAnon = $user->isAnon();
            $contribs = Title::newFromText("Special:Contributions/" . $user);
            $user_abbr = strval($user);

            if ($wgDiscordMaxCharsUsernames) {
                if (strlen($user_abbr) > $wgDiscordMaxCharsUsernames) {
                    $user_abbr = substr($user_abbr, 0, $wgDiscordMaxCharsUsernames);
                    $user_abbr = $user_abbr . '...';
                }
            }

            $userPage = self::createMarkdownLink($user_abbr, ($isAnon ? $contribs : $user->getUserPage())->getFullURL('', false, PROTO_CANONICAL));
            $userTalk = self::createMarkdownLink(wfMessage('discord-talk')->inContentLanguage()->text(), $user->getTalkPage()->getFullURL('', false, PROTO_CANONICAL));
            $userContribs = self::createMarkdownLink(wfMessage('discord-contribs')->inContentLanguage()->text(), $contribs->getFullURL('', false, PROTO_CANONICAL));
            $text = wfMessage('discord-userlinks', $userPage, $userTalk, $userContribs)->inContentLanguage()->text();
        } else {
            // If we were given a string, handle this differently.
            $text = wfMessage('discord-userlinks', $user, 'n/a', 'n/a')->inContentLanguage()->text();
        }
        return $text;
    }

    /**
     * Creates a formatted markdown link based on text and given URL
     */
    public static function createMarkdownLink($text, $url) {
        global $wgDiscordSuppressPreviews;

        return "[" . $text . "]" . '(' . ($wgDiscordSuppressPreviews ? '<' : '') . self::encodeURL($url) . ($wgDiscordSuppressPreviews ? '>' : '') . ')';
    }

    /**
     * Creates formatted text for a specific Revision object
     */
    public static function createRevisionText($revision) {
        $linkTarget = $revision->getPageAsLinkTarget();
        $title = Title::newFromLinkTarget($linkTarget);

        if (!$title) {
            return '';
        }

        $diff = self::createMarkdownLink(wfMessage('discord-diff')->inContentLanguage()->text(), $title->getFullURL(['diff' => 'prev', 'oldid' => $revision->getId()], false, PROTO_CANONICAL));

        $minor = '';
        $size = '';

        if ($revision->isMinor()) {
            $minor .= wfMessage('discord-minor')->inContentLanguage()->text();
        }

        $parentId = $revision->getParentId();

        if ($parentId) {
            $parent = MediaWikiServices::getInstance()->getRevisionLookup()->getRevisionById($parentId);

            if ($parent) {
                $size .= wfMessage('discord-size', sprintf("%+d", $revision->getSize() - $parent->getSize()))->inContentLanguage()->text();
            }
        }
        if ($size == '') {
            $size .= wfMessage('discord-size', sprintf("%d", $revision->getSize()))->inContentLanguage()->text();
        }

		$text = wfMessage('discord-revisionlinks', $diff, $minor, $size)->inContentLanguage()->text();
		return $text;
	}

    // escape characters for markdown urls
    public static function encodeURL($url) {
        return addcslashes($url, ' ()');
    }

    /**
     * Formats bytes to a string representing B, KB, MB, GB, TB
     */
    public static function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Truncate text to maximum allowed characters
     */
    public static function truncateText($text) {
        global $wgDiscordMaxChars;
        if ($wgDiscordMaxChars) {
            if (strlen($text) > $wgDiscordMaxChars) {
                $text = substr($text, 0, $wgDiscordMaxChars);
                $text = $text . '...';
            }
        }
        return $text;
    }

    // sanitize text input (may need improvement)
    public static function sanitizeText(string $text): string {
        return addcslashes($text, '`@');
    }
}
