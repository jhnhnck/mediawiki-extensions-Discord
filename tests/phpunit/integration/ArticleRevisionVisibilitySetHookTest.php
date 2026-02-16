<?php
/**
 * NovaDiscord - ArticleRevisionVisibilitySet Test Case
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord\Tests\Integration;

use MediaWiki\Context\RequestContext;


/**
 * @group Database
 * @covers \MediaWiki\Extension\NovaDiscord\ArticleAlert::onArticleRevisionVisibilitySet
 */
class ArticleRevisionVisibilitySetHookTest extends NovaDiscordIntegrationTestCase {
    public function testArticleRevisionVisibilityChangeSendsWebhook(): void {
        $performer = $this->getTestSysop();
        $page = $this->getExistingTestPage('NovaTestRevVisibility');

        $this->editPage($page, 'Content 1', 'First revision', 0, $performer->getAuthority());
        $status2 = $this->editPage($page, 'Content 2', 'Second revision', 0, $performer->getAuthority());
        $revId = $status2->getNewRevision()->getId();

        $this->mockHttpFactory->reset();

        // Set performer as context user (handler reads from RequestContext)
        \RequestContext::getMain()->setUser($performer->getUser());

        // Fire hook directly — MW 1.44 removed getRevisionDeleter() from services
        $this->getServiceContainer()->getHookContainer()->run(
            'ArticleRevisionVisibilitySet',
            [$page->getTitle(), [$revId], [$revId => ['newBits' => 1, 'oldBits' => 0]]]
        );

        $performerUsername = $performer->getUser()->getName();
        $performerUserScored = str_replace(' ', '_', $performerUsername);
        $pageTitle = 'NovaTestRevVisibility';

        $expectedPayload = '{"content":"[' . $performerUsername . '](<https:\/\/novadiscord.local\/wiki\/User:' . $performerUserScored . '>) ([t](<https:\/\/novadiscord.local\/wiki\/User_talk:' . $performerUserScored . '>)|[c](<https:\/\/novadiscord.local\/wiki\/Special:Contributions\/' . $performerUserScored . '>)) changed visibility of 1 revisions on [' . $pageTitle . '](<https:\/\/novadiscord.local\/wiki\/' . $pageTitle . '>)","allowed_mentions":{"parse":[]}}';

        $payload = $this->mockHttpFactory->getCaptured()[0];
        $this->assertNotNull($payload, 'Webhook payload should be captured on revision visibility change');
        $this->assertEquals($expectedPayload, $payload['options']['postData'], 'Webhook payload for revision visibility change should match expected output');
    }
}
