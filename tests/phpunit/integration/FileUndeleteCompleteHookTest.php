<?php
/**
 * NovaDiscord - FileUndeleteComplete Test Case
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord\Tests\Integration;

/**
 * @group Database
 * @covers \MediaWiki\Extension\NovaDiscord\FileAlert::onFileUndeleteComplete
 */
class FileUndeleteCompleteHookTest extends NovaDiscordIntegrationTestCase {
    public function testFileUndeleteSendsWebhook(): void {
        $this->markTestSkipped('Stubbed test');
    }
}
