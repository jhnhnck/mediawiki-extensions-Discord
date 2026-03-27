<?php
/**
 * NovaDiscord - LocalUserCreated Test Case
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord\Tests\Integration;

/**
 * @group Database
 * @covers \MediaWiki\Extension\NovaDiscord\UserAlert::onLocalUserCreated
 */
class LocalUserCreatedHookTest extends NovaDiscordIntegrationTestCase {
    public function testLocalUserCreatedSendsWebhook(): void {
        // setup
        $testUser = $this->getTestUser();
        $user = $testUser->getUser();
        $this->assertTrue($user->isRegistered(), 'User should be created successfully');

        // getTestUser() doesn't fire LocalUserCreated; dispatch manually
        $this->mockHttpFactory->reset();
        $this->getServiceContainer()->getHookContainer()->run(
            'LocalUserCreated',
            [$user, false]
        );

        // build expected payload
        $username = $user->getName();
        $userScored = str_replace(' ', '_', $username);
        $expectedPayload = '{"content":"[' . $username . '](<https://novadiscord.local/wiki/User:' . $userScored . '>) ([t](<https://novadiscord.local/wiki/User_talk:' . $userScored . '>)|[c](<https://novadiscord.local/wiki/Special:Contributions/' . $userScored . '>)) registered","allowed_mentions":{"parse":[]}}';

        // verify webhook payload
        $captured = $this->mockHttpFactory->getCaptured();
        $this->assertNotEmpty($captured, 'Webhook payload should be captured on user creation');
        $payload = $captured[0];

        $this->assertNotNull($payload, 'Webhook payload should not be null');
        $this->assertEquals($expectedPayload, $payload['options']['postData'], 'Webhook payload for user creation should match expected output');
    }
}
