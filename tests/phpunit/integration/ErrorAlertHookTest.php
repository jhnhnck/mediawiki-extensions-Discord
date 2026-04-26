<?php
/**
 * NovaDiscord - LogException Test Case
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord\Tests\Integration;

use MediaWiki\Config\HashConfig;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\NovaDiscord\ErrorAlert;
use MediaWiki\Extension\NovaDiscord\NovaDiscordConfig;
use RuntimeException;
use Wikimedia\Assert\PreconditionException;

/**
 * @group Database
 * @covers MediaWiki\Extension\NovaDiscord\ErrorAlert::onLogException
 */
class ErrorAlertHookTest extends NovaDiscordIntegrationTestCase {
    private const WEBHOOK_URL = 'https://novadiscord.local/webhook/exception-test';

    /**
     * Build an ErrorAlert with a fresh NovaDiscordConfig from the given overrides,
     * so each test can dictate config in isolation without touching the global service.
     */
    private function buildAlert(array $overrides): ErrorAlert {
        $defaults = [
            'DiscordWebhooks' => [['url' => self::WEBHOOK_URL, 'hooks' => ['LogException']]],
            'DiscordDefaultHooks' => null,
            'DiscordNoBots' => true,
            'DiscordNoMinor' => false,
            'DiscordNoNull' => true,
            'DiscordMaxChars' => 500,
            'DiscordDisabledNS' => [],
            'DiscordDisabledUsers' => [],
            'DiscordPrivateExceptionAlerts' => true,
            'DiscordExceptionDenyList' => [],
        ];
        $config = new NovaDiscordConfig(
            new ServiceOptions(
                NovaDiscordConfig::CONSTRUCTOR_OPTIONS,
                new HashConfig(array_merge($defaults, $overrides))
            )
        );
        $services = $this->getServiceContainer();
        return new ErrorAlert(
            $this->mockHttpFactory,
            $services->getRevisionLookup(),
            $services->getTitleFactory(),
            $services->getUrlUtils(),
            $config
        );
    }

    public function testEmptyDenyListPostsWebhook(): void {
        $alert = $this->buildAlert(['DiscordExceptionDenyList' => []]);
        $alert->onLogException(new RuntimeException('NovaTest empty deny ' . uniqid()), false);
        $this->assertCount(1, $this->mockHttpFactory->getCaptured(),
            'Empty deny-list should not suppress any exception');
    }

    public function testBareClassEntrySuppressesAllInstances(): void {
        $alert = $this->buildAlert(['DiscordExceptionDenyList' => [RuntimeException::class]]);
        $alert->onLogException(new RuntimeException('NovaTest bare class ' . uniqid()), false);
        $this->assertCount(0, $this->mockHttpFactory->getCaptured(),
            'Bare class entry should suppress matching exception');
    }

    public function testMessageContainsSuppressesOnlyMatchingMessage(): void {
        $denyList = [
            ['class' => RuntimeException::class, 'messageContains' => 'IGNORE-ME'],
        ];

        // matching message should be suppressed
        $alert = $this->buildAlert(['DiscordExceptionDenyList' => $denyList]);
        $alert->onLogException(new RuntimeException('NovaTest IGNORE-ME ' . uniqid()), false);
        $this->assertCount(0, $this->mockHttpFactory->getCaptured(),
            'Matching messageContains should suppress the alert');

        // non-matching message in same class should still post
        $alert->onLogException(new RuntimeException('NovaTest report-me ' . uniqid()), false);
        $this->assertCount(1, $this->mockHttpFactory->getCaptured(),
            'Non-matching message of same class should still post');
    }

    public function testDenyListAppliesEvenWhenPrivateAlertsDisabled(): void {
        // verifies the deny-list filter runs *before* the private-alerts gate, so a
        // future re-enable of DiscordPrivateExceptionAlerts cannot leak suppressed errors
        $alert = $this->buildAlert([
            'DiscordPrivateExceptionAlerts' => false,
            'DiscordExceptionDenyList' => [PreconditionException::class],
        ]);
        $alert->onLogException(
            new PreconditionException('NovaTest precondition ' . uniqid()),
            false
        );
        $this->assertCount(0, $this->mockHttpFactory->getCaptured(),
            'Deny-listed exception must remain suppressed when private alerts toggle on later');
    }
}
