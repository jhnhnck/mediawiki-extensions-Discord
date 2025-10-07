<?php
/**
 * NovaDiscord - ArticleUndelete Test Case
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord\Tests\Integration;

/**
 * @group Database
 * @covers MediaWiki\Extension\NovaDiscord\PageAlert::onPageUndeleteComplete
 * TODO: This should also test for restoring specific revisions as well, I believe
 */
class PageUndeleteCompleteHookTest extends NovaDiscordIntegrationTestCase {
    public function testPageUndeletionSendsWebhook(): void {
        // setup
        $services = $this->getServiceContainer();
        $title = $services->getTitleFactory()->newFromText('NovaTestPageUndelete', NS_MAIN);
        $page = $services->getWikiPageFactory()->newFromTitle($title);
        $undeletePageFactory = $services->getUndeletePageFactory();

        // create page
        $status = $this->editPage($page, 'TBD', 'Created page');
        $this->assertTrue($status->isOK(), 'Page should be created successfully');

        // delete page
        $user = $this->getTestSysop();
        $this->deletePage($page, 'Deleted page', $user->getAuthority());
        $this->assertFalse($page->exists(), 'Page should be deleted successfully');
        $this->mockHttpFactory->reset();

        // undelete page
        $pageUndeleter = $undeletePageFactory->newUndeletePage($page, $user->getAuthority());
        $status = $pageUndeleter->undeleteIfAllowed('Undeleted page for test case');
        $this->assertTrue($status->isOK(), 'Page should be undeleted successfully');

        // build expected payload
        $username = $user->getUser()->getName();
        $userScored = str_replace(' ', '_', $username);
        $expectedPayload = '{"content":"[' . $username . '](<https:\/\/dev.attuproject.org\/wiki\/User:' . $userScored . '>) ([t](<https:\/\/dev.attuproject.org\/wiki\/User_talk:' . $userScored . '>)|[c](<https:\/\/dev.attuproject.org\/wiki\/Special:Contributions\/' . $userScored . '>)) undeleted [NovaTestPageUndelete](<https:\/\/dev.attuproject.org\/wiki\/NovaTestPageUndelete>) `Undeleted page for test case`","allowed_mentions":{"parse":[]}}';

        // verify webhook payload
        $payload = $this->mockHttpFactory->getCaptured()[0];
        $this->assertNotNull($payload, 'Webhook payload should be captured on page undelete');
        $this->assertEquals($expectedPayload, $payload['options']['postData'], 'Webhook payload for page undelete should match expected output');
    }
}
