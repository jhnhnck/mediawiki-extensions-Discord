<?php
/**
 * NovaDiscord - ArticleMergeComplete Test Case
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord\Tests\Integration;

/**
 * @group Database
 * @covers \MediaWiki\Extension\NovaDiscord\ArticleAlert::onArticleMergeComplete
 */
class ArticleMergeCompleteHookTest extends NovaDiscordIntegrationTestCase {
    public function testArticleMergeSendsWebhook(): void {
        $this->markTestSkipped('Stubbed test');
    }
}
