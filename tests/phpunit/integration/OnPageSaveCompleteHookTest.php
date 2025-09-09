<?php
/**
 * NovaDiscord - OnPageSaveComplete Test Case
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord\Tests\Integration;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\NovaDiscord\Tests\Doubles\MockRequestFactory;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;

/**
 * @group Database
 * @covers MediaWiki\Extension\NovaDiscord\PageAlert::onPageSaveComplete
 */
class OnPageSaveCompleteHookTest extends MediaWikiIntegrationTestCase {
    private $mockHttpFactory;

    protected function setUp(): void {
        $this->mockHttpFactory = new MockRequestFactory(
            new ServiceOptions(
                MockRequestFactory::CONSTRUCTOR_OPTIONS,
                MediaWikiServices::getInstance()->getMainConfig()
            ),
            LoggerFactory::getInstance( 'http' ),
        );
        $this->setService('HttpRequestFactory', $this->mockHttpFactory);
    }

    public function testPageCreationSendsWebhook(): void {
        // create page
        $user = $this->getTestUser();
        $status = $this->editPage(
            'NovaTestPageA',
            'Not to be confused with [[NovaTestPageB]].',
            'Created page',
            0,
            $user->getAuthority()
        );
        $this->assertTrue($status->isOK(), 'Page should be created successfully');

        // Build expected payload
        $username = $user->getUser()->getName();
        $userScored = str_replace(' ', '_', $username);
        $expectedPayload = '{"content":"[' . $username . '](<https:\/\/dev.attuproject.org\/wiki\/User:' . $userScored . '>) ([t](<https:\/\/dev.attuproject.org\/wiki\/User_talk:' . $userScored . '>)|[c](<https:\/\/dev.attuproject.org\/wiki\/Special:Contributions\/' . $userScored . '>)) created [NovaTestPageA](<https:\/\/dev.attuproject.org\/wiki\/NovaTestPageA>) ([diff](<https:\/\/dev.attuproject.org\/index.php?title=NovaTestPageA&diff=prev&oldid=1>)) (42) `Created page`","allowed_mentions":{"parse":[]}}';

        // verify webhook payload
        $payload = $this->mockHttpFactory->getCaptured()[0];
        $this->assertNotNull($payload, 'Webhook payload should be captured on page creation');
        $this->assertEquals($expectedPayload, $payload['options']['postData'], 'Webhook payload for page creation should match expected output');
    }

    public function testPageEditSendsWebhook(): void {
        // setup
        $status = $this->editPage('NovaTestPageB', 'TBD', 'Created page');
        $this->mockHttpFactory->reset();

        // perform actual edit
        $user = $this->getTestUser();
        $status = $this->editPage(
            'NovaTestPageB',
            'The quick brown fox jumped over the lazy churg.',
            'Added content',
            0,
            $user->getAuthority()
        );
        $this->assertTrue($status->isOK(), 'Page should be edited successfully');

        // Build expected payload
        $username = $user->getUser()->getName();
        $userScored = str_replace(' ', '_', $username);
        $expectedPayload = '{"content":"[' . $username . '](<https:\/\/dev.attuproject.org\/wiki\/User:' . $userScored . '>) ([t](<https:\/\/dev.attuproject.org\/wiki\/User_talk:' . $userScored . '>)|[c](<https:\/\/dev.attuproject.org\/wiki\/Special:Contributions\/' . $userScored . '>)) edited [NovaTestPageB](<https:\/\/dev.attuproject.org\/wiki\/NovaTestPageB>) ([diff](<https:\/\/dev.attuproject.org\/index.php?title=NovaTestPageB&diff=prev&oldid=2>)) (+44) `Added content`","allowed_mentions":{"parse":[]}}';

        // verify webhook payload
        $payload = $this->mockHttpFactory->getCaptured()[0];
        $this->assertNotNull($payload, 'Webhook payload should be captured on page edit');
        $this->assertEquals($expectedPayload, $payload['options']['postData'], 'Webhook payload for page edit should match expected output');
    }
}
