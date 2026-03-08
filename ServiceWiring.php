<?php
/**
 * NovaDiscord - Service Wiring
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\NovaDiscord\NovaDiscordConfig;
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
];
