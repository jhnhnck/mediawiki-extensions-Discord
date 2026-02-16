<?php
/**
 * NovaDiscord - UploadComplete Test Case
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord\Tests\Integration;

/**
 * @group Database
 * @covers \MediaWiki\Extension\NovaDiscord\FileAlert::onUploadComplete
 */
class UploadCompleteHookTest extends NovaDiscordIntegrationTestCase {
    public function testUploadSendsWebhook(): void {
        $this->markTestSkipped('Stubbed test');
    }
}
