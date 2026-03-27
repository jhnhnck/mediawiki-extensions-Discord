<?php
/**
 * NovaDiscord - LoggerSpi wrapper
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord;

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Logger\Spi as LoggerSpi;
use Psr\Log\LoggerInterface;

/**
 * LoggerSpi implementation that delegates to the MW LoggerFactory static API.
 * Used as a custom service so handlers can receive LoggerSpi via DI without
 * depending on 'LoggerFactory' being a registered service (it isn't in all MW versions).
 */
class NovaDiscordLoggerSpi implements LoggerSpi {
    public function getLogger( $channel ): LoggerInterface {
        return LoggerFactory::getInstance( $channel );
    }
}
