<?php
/**
 * NovaDiscord - ArticleProtectComplete Test Case
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord\Tests\Integration;

/**
 * @group Database
 * @covers \MediaWiki\Extension\NovaDiscord\ArticleAlert::onArticleProtectComplete
 */
class ArticleProtectCompleteHookTest extends NovaDiscordIntegrationTestCase {
    public function testArticleProtectSendsWebhook(): void {
        $performer = $this->getTestSysop()->getUser();
        $page = $this->getExistingTestPage('NovaTestProtect');
        $this->mockHttpFactory->reset();

        $cascade = false;
        $status = $page->doUpdateRestrictions(
            ['edit' => 'sysop', 'move' => 'sysop'],
            ['edit' => 'infinity', 'move' => 'infinity'],
            $cascade,
            'Test protection reason',
            $performer
        );
        $this->assertTrue($status->isOK(), 'Page should be protected successfully');

        $performerUsername = $performer->getName();
        $performerUserScored = str_replace(' ', '_', $performerUsername);
        $pageTitle = 'NovaTestProtect';

        $expectedPayload = '{"content":"[' . $performerUsername . '](<https://novadiscord.local/wiki/User:' . $performerUserScored . '>) ([t](<https://novadiscord.local/wiki/User_talk:' . $performerUserScored . '>)|[c](<https://novadiscord.local/wiki/Special:Contributions/' . $performerUserScored . '>)) changed protection of [' . $pageTitle . '](<https://novadiscord.local/wiki/' . $pageTitle . '>) `Test protection reason` (sysop, sysop)","allowed_mentions":{"parse":[]}}';

        $payload = $this->mockHttpFactory->getCaptured()[0];
        $this->assertNotNull($payload, 'Webhook payload should be captured on article protect');
        $this->assertEquals($expectedPayload, $payload['options']['postData'], 'Webhook payload for article protect should match expected output');
    }
}
