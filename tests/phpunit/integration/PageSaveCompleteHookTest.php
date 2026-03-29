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
        $expectedPayload = '{"content":"[' . $username . '](<https://novadiscord.local/wiki/User:' . $userScored . '>) ([t](<https://novadiscord.local/wiki/User_talk:' . $userScored . '>)|[c](<https://novadiscord.local/wiki/Special:Contributions/' . $userScored . '>)) created [NovaTestPageCreate](<https://novadiscord.local/wiki/NovaTestPageCreate>) ([diff](<https://novadiscord.local/index.php?title=NovaTestPageCreate&diff=prev&oldid=1>)) (45) `Created page`\n```diff\n- \n+ Not to be confused with [[NovaTestPageEdit]].\n```","allowed_mentions":{"parse":[]}}';

        // verify webhook payload
        $payload = $this->mockHttpFactory->getCaptured()[0];
        $this->assertNotNull($payload, 'Webhook payload should be captured on page creation');
        $this->assertEquals($expectedPayload, $payload['options']['postData'], 'Webhook payload for page creation should match expected output');
    }

    public function testMinorEditOmitsDiffBlock(): void {
        // setup
        $status = $this->editPage('NovaTestPageMinor', 'Initial content.', 'Created page');
        $this->assertTrue($status->isOK(), 'Page should be created successfully');
        $this->mockHttpFactory->reset();

        // perform minor edit
        $user = $this->getTestUser();
        $status = $this->editPage(
            'NovaTestPageMinor',
            'Initial content. (minor fix)',
            'Fixed typo',
            EDIT_MINOR,
            $user->getAuthority()
        );
        $this->assertTrue($status->isOK(), 'Page should be edited successfully');

        // build expected payload — no diff block
        $username = $user->getUser()->getName();
        $userScored = str_replace(' ', '_', $username);
        $expectedPayload = '{"content":"[' . $username . '](<https://novadiscord.local/wiki/User:' . $userScored . '>) ([t](<https://novadiscord.local/wiki/User_talk:' . $userScored . '>)|[c](<https://novadiscord.local/wiki/Special:Contributions/' . $userScored . '>)) edited [NovaTestPageMinor](<https://novadiscord.local/wiki/NovaTestPageMinor>) ([diff](<https://novadiscord.local/index.php?title=NovaTestPageMinor&diff=prev&oldid=2>)) m (+14) `Fixed typo`","allowed_mentions":{"parse":[]}}';

        // verify webhook payload
        $payload = $this->mockHttpFactory->getCaptured()[0];
        $this->assertNotNull($payload, 'Webhook payload should be captured on minor edit');
        $this->assertEquals($expectedPayload, $payload['options']['postData'], 'Minor edit webhook payload should not include diff block');
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
        $expectedPayload = '{"content":"[' . $username . '](<https://novadiscord.local/wiki/User:' . $userScored . '>) ([t](<https://novadiscord.local/wiki/User_talk:' . $userScored . '>)|[c](<https://novadiscord.local/wiki/Special:Contributions/' . $userScored . '>)) edited [NovaTestPageEdit](<https://novadiscord.local/wiki/NovaTestPageEdit>) ([diff](<https://novadiscord.local/index.php?title=NovaTestPageEdit&diff=prev&oldid=2>)) (+44) `Added content`\n```diff\n- TBD\n+ The quick brown fox jumped over the lazy churg.\n```","allowed_mentions":{"parse":[]}}';

        // verify webhook payload
        $payload = $this->mockHttpFactory->getCaptured()[0];
        $this->assertNotNull($payload, 'Webhook payload should be captured on page edit');
        $this->assertEquals($expectedPayload, $payload['options']['postData'], 'Webhook payload for page edit should match expected output');
    }
}
