<?php
/**
 * NovaDiscord - PageMoveComplete Test Case
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord\Tests\Integration;

/**
 * @group Database
 * @covers MediaWiki\Extension\NovaDiscord\PageAlert::onPageMoveComplete
 */
class PageMoveCompleteHookTest extends NovaDiscordIntegrationTestCase {
    public function testPageMoveSendsWebhook(): void {
        // setup
        $services = $this->getServiceContainer();
        $titleFactory = $services->getTitleFactory();
        $movePageFactory = $services->getMovePageFactory();
        $wikiPageFactory = $services->getWikiPageFactory();

        $titleBefore = $titleFactory->newFromText('NovaTestPageMoveBefore', NS_MAIN);
        $titleAfter = $titleFactory->newFromText('NovaTestPageMoveAfter', NS_MAIN);
        $page = $wikiPageFactory->newFromTitle($titleBefore);

        // create page
        $status = $this->editPage($page, 'TBD', 'Created page');
        $this->assertTrue($status->isOK(), 'Page should be created successfully');
        $this->mockHttpFactory->reset();

        // move page
        $user = $this->getTestSysop();
        $pageMove = $movePageFactory->newMovePage($titleBefore, $titleAfter);
        $status = $pageMove->moveIfAllowed($user->getAuthority(), 'Moved page because reasons', false);
        $this->assertTrue($status->isOK(), 'Page should be moved successfully');

        // build expected payload
        $username = $user->getUser()->getName();
        $userScored = str_replace(' ', '_', $username);
        $expectedPayload = '{"content":"[' . $username . '](<https:\/\/novadiscord.local\/wiki\/User:' . $userScored . '>) ([t](<https:\/\/novadiscord.local\/wiki\/User_talk:' . $userScored . '>)|[c](<https:\/\/novadiscord.local\/wiki\/Special:Contributions\/' . $userScored . '>)) moved [NovaTestPageMoveBefore](<https:\/\/novadiscord.local\/wiki\/NovaTestPageMoveBefore>) to [NovaTestPageMoveAfter](<https:\/\/novadiscord.local\/wiki\/NovaTestPageMoveAfter>) `Moved page because reasons` ([diff](<https:\/\/novadiscord.local\/index.php?title=NovaTestPageMoveAfter&diff=prev&oldid=2>)) (m) (+0)","allowed_mentions":{"parse":[]}}';

        // verify webhook payload
        $payload = $this->mockHttpFactory->getCaptured()[0];
        $this->assertNotNull($payload, 'Webhook payload should be captured on page move');
        $this->assertEquals($expectedPayload, $payload['options']['postData'], 'Webhook payload for page move should match expected output');
    }
}
