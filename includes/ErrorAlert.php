<?php
/**
 * NovaDiscord - Exception Alerting
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord;

use MediaWiki\Exception\ErrorPageError;
use MediaWiki\Exception\HttpError;
use MediaWiki\Hook\LogExceptionHook;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Logger\Spi as LoggerSpi;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Title\TitleFactory;
use MediaWiki\Utils\UrlUtils;
use Psr\Log\NullLogger;

class ErrorAlert extends DiscordAlert implements LogExceptionHook {
    public function __construct(HttpRequestFactory $httpFactory,
                                RevisionLookup $revLookup,
                                TitleFactory $titleFactory,
                                UrlUtils $urlUtils,
                                NovaDiscordConfig $novaConfig) {
        // Use a NullLogger rather than injecting LoggerFactory: this hook fires during
        // exception handling, potentially before services are fully initialized or when
        // the original exception is itself a service-container failure. Requesting
        // LoggerFactory here would cause a cascading fatal error in those cases.
        $nullSpi = new class implements LoggerSpi {
            public function getLogger( $channel ) {
                return new NullLogger();
            }
        };
        parent::__construct($httpFactory, $revLookup, $titleFactory, $urlUtils, $novaConfig, $nullSpi);
    }

    /**
     * Called when an exception is logged
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/LogException
     */
    public function onLogException($e, $suppressed): void {
        $this->logger->debug('Completing hook LogException with ' . get_class($e));

        // skip errors suppressed via @ operator or error_reporting()
        if ($suppressed) {
            return;
        }

        // skip user-navigation errors; these are expected behavior, not bugs
        foreach ([ErrorPageError::class, HttpError::class] as $class) {
            if ($e instanceof $class) {
                return;
            }
        }

        // opt-in only; disabled by default
        if (!$this->novaConfig->isPrivateExceptionAlertsEnabled()) {
            return;
        }

        // no webhooks enabled for this hook = nothing to do
        if ($this->novaConfig->getWebhooksForHook('LogException') === []) {
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

        $file = str_replace(MW_INSTALL_PATH . '/', '', $e->getFile()) . ':' . $e->getLine();
        $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $context = $uri ? "{$method} {$uri}" : $method;

        $msg = '[' . get_class($e) . '] ' . $this->truncateString(
            $e->getMessage() . ' - ' . $file . ' (' . $context . ')',
            $this->novaConfig->getMaxChars()
        );

        $this->sendAlert('LogException', $msg, time());
    }
}
