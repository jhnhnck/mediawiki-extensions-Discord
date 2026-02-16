<?php
/**
 * NovaDiscord - RenameUserComplete Test Case
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord\Tests\Integration;

/**
 * @group Database
 * @covers \MediaWiki\Extension\NovaDiscord\ExtensionAlert::onRenameUserComplete
 */
class RenameUserCompleteHookTest extends NovaDiscordIntegrationTestCase {
    public function testRenameUserSendsWebhook(): void {
        $this->markTestSkipped('Stubbed test');
    }
}
