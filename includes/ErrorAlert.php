<?php
/**
 * NovaDiscord - Exception Alerting
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord;

use MediaWiki\Hook\LogExceptionHook;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Title\TitleFactory;
use MediaWiki\Utils\UrlUtils;

class ErrorAlert extends DiscordAlert implements LogExceptionHook {
    public function __construct(HttpRequestFactory $httpFactory,
                                RevisionLookup $revLookup,
                                TitleFactory $titleFactory,
                                UrlUtils $urlUtils) {
        parent::__construct($httpFactory, $revLookup, $titleFactory, $urlUtils);
    }

    /**
     * Called when an exception is logged
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/LogException
     */
    public function onLogException(\Throwable $e, bool $suppressed): void {
        wfDebugLog('nova-discord', 'Completing hook LogException with ' . get_class($e));

        // skip errors suppressed via @ operator or error_reporting()
        if ($suppressed) {
            return;
        }

        // skip user-navigation errors; these are expected behavior, not bugs
        foreach ([\ErrorPageError::class, \HttpError::class] as $class) {
            if ($e instanceof $class) {
                return;
            }
        }

        global $wgDiscordPrivateExceptionAlerts, $wgDiscordWebhookURL, $wgDiscordDisabledHooks, $wgDiscordMaxChars;

        // opt-in only; disabled by default
        if (!$wgDiscordPrivateExceptionAlerts) {
            return;
        }

        if (!is_string($wgDiscordWebhookURL) && !is_array($wgDiscordWebhookURL)) {
            return;
        }

        // respect disabled hooks config (no User object available, so isEnabled() can't be used)
        if (is_array($wgDiscordDisabledHooks) &&
            in_array('logexception', array_map('strtolower', $wgDiscordDisabledHooks))) {
            return;
        }

        // rate-limit per exception fingerprint via apcu; skip if apcu not available (e.g. cli)
        $fingerprint = 'nova_discord_exc_' . md5(get_class($e) . $e->getMessage());
        if (function_exists('apcu_enabled') && apcu_enabled()) {
            if (apcu_exists($fingerprint)) {
                return;
            }
            apcu_store($fingerprint, 1, 300);
        }

        global $IP;
        $file = str_replace("{$IP}/", '', $e->getFile()) . ':' . $e->getLine();
        $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $context = $uri ? "{$method} {$uri}" : $method;

        $msg = '[' . get_class($e) . '] ' . $this->truncateString(
            $e->getMessage() . ' - ' . $file . ' (' . $context . ')',
            $wgDiscordMaxChars ?? 500
        );

        $this->sendAlert('LogException', $msg, time());
    }
}
