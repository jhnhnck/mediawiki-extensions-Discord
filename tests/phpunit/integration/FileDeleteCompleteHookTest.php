<?php
/**
 * NovaDiscord - FileDeleteComplete Test Case
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord\Tests\Integration;

/**
 * @group Database
 * @covers \MediaWiki\Extension\NovaDiscord\FileAlert::onFileDeleteComplete
 */
class FileDeleteCompleteHookTest extends NovaDiscordIntegrationTestCase {
    public function testFileDeleteSendsWebhook(): void {
        $this->markTestSkipped('Stubbed test');
    }
}
