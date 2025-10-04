<?php
/**
 * NovaDiscord - PageDeleteComplete Test Case
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord\Tests\Integration;

/**
 * @group Database
 * @covers MediaWiki\Extension\NovaDiscord\PageAlert::onPageDeleteComplete
 */
class PageDeleteCompleteHookTest extends NovaDiscordIntegrationTestCase {
    public function testPageDeleteSendsWebhook(): void {
        // setup
        $services = $this->getServiceContainer();
        $title = $services->getTitleFactory()->newFromText('NovaTestPageDelete', NS_MAIN);
        $page = $services->getWikiPageFactory()->newFromTitle($title);

        // create page
        $status = $this->editPage($page, 'TBD', 'Created page');
        $this->assertTrue($status->isOK(), 'Page should be created successfully');
        $this->mockHttpFactory->reset();

        // delete page
        $user = $this->getTestUser();
        $this->deletePage($page, 'Deleted page', $user->getAuthority());

        // Build expected payload
        $username = $user->getUser()->getName();
        $userScored = str_replace(' ', '_', $username);
        $expectedPayload = '{"content":"[' . $username . '](<https:\/\/dev.attuproject.org\/wiki\/User:' . $userScored . '>) ([t](<https:\/\/dev.attuproject.org\/wiki\/User_talk:' . $userScored . '>)|[c](<https:\/\/dev.attuproject.org\/wiki\/Special:Contributions\/' . $userScored . '>)) deleted [NovaTestPageDelete](<https:\/\/dev.attuproject.org\/wiki\/NovaTestPageDelete>) `Deleted page` (1 revisions deleted)","allowed_mentions":{"parse":[]}}';

        // verify webhook payload
        $payload = $this->mockHttpFactory->getCaptured()[0];
        $this->assertNotNull($payload, 'Webhook payload should be captured on page edit');
        $this->assertEquals($expectedPayload, $payload['options']['postData'], 'Webhook payload for page edit should match expected output');
    }
}
