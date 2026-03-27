<?php
/**
 * NovaDiscord - Service Wiring
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\NovaDiscord\NovaDiscordConfig;
use MediaWiki\Extension\NovaDiscord\NovaDiscordLoggerSpi;
use MediaWiki\Logger\Spi as LoggerSpi;
use MediaWiki\MediaWikiServices;

return [
    'NovaDiscord.Config' => static function (MediaWikiServices $services): NovaDiscordConfig {
        return new NovaDiscordConfig(
            new ServiceOptions(
                NovaDiscordConfig::CONSTRUCTOR_OPTIONS,
                $services->getMainConfig()
            )
        );
    },
    // LoggerFactory is a static facade and not always a registered service in all MW versions;
    // wrap it here so handlers can receive LoggerSpi via DI without depending on the service name
    'NovaDiscord.LoggerSpi' => static function (MediaWikiServices $services): LoggerSpi {
        return new NovaDiscordLoggerSpi();
    },
];
