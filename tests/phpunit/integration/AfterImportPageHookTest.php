<?php
/**
 * NovaDiscord - AfterImportPage Test Case
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord\Tests\Integration;

/**
 * @group Database
 * @covers \MediaWiki\Extension\NovaDiscord\ArticleAlert::onAfterImportPage
 */
class AfterImportPageHookTest extends NovaDiscordIntegrationTestCase {
    public function testPageImportSendsWebhook(): void {
        $this->markTestSkipped('Stubbed test');
    }
}
