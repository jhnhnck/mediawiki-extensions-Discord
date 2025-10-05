<?php
/**
 * NovaDiscord - PageSaveComplete Test Case
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord\Tests\Integration;

/**
 * @group Database
 * @covers MediaWiki\Extension\NovaDiscord\PageAlert::onPageSaveComplete
 */
class PageSaveCompleteHookTest extends NovaDiscordIntegrationTestCase {
    public function testPageCreationSendsWebhook(): void {
        // create page
        $user = $this->getTestUser();
        $status = $this->editPage(
            'NovaTestPageCreate',
            'Not to be confused with [[NovaTestPageEdit]].',
            'Created page',
            0,
            $user->getAuthority()
        );
        $this->assertTrue($status->isOK(), 'Page should be created successfully');

        // build expected payload
        $username = $user->getUser()->getName();
        $userScored = str_replace(' ', '_', $username);
        $expectedPayload = '{"content":"[' . $username . '](<https:\/\/dev.attuproject.org\/wiki\/User:' . $userScored . '>) ([t](<https:\/\/dev.attuproject.org\/wiki\/User_talk:' . $userScored . '>)|[c](<https:\/\/dev.attuproject.org\/wiki\/Special:Contributions\/' . $userScored . '>)) created [NovaTestPageCreate](<https:\/\/dev.attuproject.org\/wiki\/NovaTestPageCreate>) ([diff](<https:\/\/dev.attuproject.org\/index.php?title=NovaTestPageCreate&diff=prev&oldid=1>)) (45) `Created page`","allowed_mentions":{"parse":[]}}';

        // verify webhook payload
        $payload = $this->mockHttpFactory->getCaptured()[0];
        $this->assertNotNull($payload, 'Webhook payload should be captured on page creation');
        $this->assertEquals($expectedPayload, $payload['options']['postData'], 'Webhook payload for page creation should match expected output');
    }

    public function testPageEditSendsWebhook(): void {
        // setup
        $status = $this->editPage('NovaTestPageEdit', 'TBD', 'Created page');
        $this->assertTrue($status->isOK(), 'Page should be created successfully');
        $this->mockHttpFactory->reset();

        // perform actual edit
        $user = $this->getTestUser();
        $status = $this->editPage(
            'NovaTestPageEdit',
            'The quick brown fox jumped over the lazy churg.',
            'Added content',
            0,
            $user->getAuthority()
        );
        $this->assertTrue($status->isOK(), 'Page should be edited successfully');

        // build expected payload
        $username = $user->getUser()->getName();
        $userScored = str_replace(' ', '_', $username);
        $expectedPayload = '{"content":"[' . $username . '](<https:\/\/dev.attuproject.org\/wiki\/User:' . $userScored . '>) ([t](<https:\/\/dev.attuproject.org\/wiki\/User_talk:' . $userScored . '>)|[c](<https:\/\/dev.attuproject.org\/wiki\/Special:Contributions\/' . $userScored . '>)) edited [NovaTestPageEdit](<https:\/\/dev.attuproject.org\/wiki\/NovaTestPageEdit>) ([diff](<https:\/\/dev.attuproject.org\/index.php?title=NovaTestPageEdit&diff=prev&oldid=2>)) (+44) `Added content`","allowed_mentions":{"parse":[]}}';

        // verify webhook payload
        $payload = $this->mockHttpFactory->getCaptured()[0];
        $this->assertNotNull($payload, 'Webhook payload should be captured on page edit');
        $this->assertEquals($expectedPayload, $payload['options']['postData'], 'Webhook payload for page edit should match expected output');
    }
}
