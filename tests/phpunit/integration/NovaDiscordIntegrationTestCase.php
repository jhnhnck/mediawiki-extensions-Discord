<?php
/**
 * NovaDiscord - Abstract Integration Test Case
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord\Tests\Integration;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\NovaDiscord\Tests\Doubles\MockRequestFactory;
use MediaWiki\Logger\LoggerFactory;
use MediaWikiIntegrationTestCase;

/**
 * @group Database
 */
abstract class NovaDiscordIntegrationTestCase extends MediaWikiIntegrationTestCase {
    protected $mockHttpFactory;

    protected function setUp(): void {
        $services = $this->getServiceContainer();

        $this->mockHttpFactory = new MockRequestFactory(
            new ServiceOptions(
                MockRequestFactory::CONSTRUCTOR_OPTIONS,
                $services->getMainConfig()
            ),
            LoggerFactory::getInstance('http'),
        );
        $this->setService('HttpRequestFactory', $this->mockHttpFactory);
    }
}
