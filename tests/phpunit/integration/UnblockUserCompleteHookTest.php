<?php
/**
 * NovaDiscord - UnblockUserComplete Test Case
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord\Tests\Integration;

/**
 * @group Database
 * @covers \MediaWiki\Extension\NovaDiscord\UserAlert::onUnblockUserComplete
 */
class UnblockUserCompleteHookTest extends NovaDiscordIntegrationTestCase {
    public function testUnblockUserSendsWebhook(): void {
        $targetUser = $this->getTestUser()->getUser();
        $blockingUser = $this->getTestSysop()->getUser();
        $unblockingUser = $this->getTestSysop()->getUser();

        $blockUserFactory = $this->getServiceContainer()->getBlockUserFactory();
        $status = $blockUserFactory->newBlockUser(
            $targetUser,
            $blockingUser,
            'infinity',
            'Test block reason'
        )->placeBlock();
        $this->assertTrue($status->isOK(), 'User should be blocked successfully');

        $this->mockHttpFactory->reset();

        $unblockUserFactory = $this->getServiceContainer()->getUnblockUserFactory();
        $unblockStatus = $unblockUserFactory->newUnblockUser(
            $targetUser,
            $unblockingUser,
            'Test unblock reason'
        )->unblock();
        $this->assertTrue($unblockStatus->isOK(), 'User should be unblocked successfully');

        $unblockingUsername = $unblockingUser->getName();
        $unblockingUserScored = str_replace(' ', '_', $unblockingUsername);
        $targetUsername = $targetUser->getName();
        $targetUserScored = str_replace(' ', '_', $targetUsername);

        $expectedPayload = '{"content":"[' . $unblockingUsername . '](<https:\/\/novadiscord.local\/wiki\/User:' . $unblockingUserScored . '>) ([t](<https:\/\/novadiscord.local\/wiki\/User_talk:' . $unblockingUserScored . '>)|[c](<https:\/\/novadiscord.local\/wiki\/Special:Contributions\/' . $unblockingUserScored . '>)) unblocked [' . $targetUsername . '](<https:\/\/novadiscord.local\/wiki\/User:' . $targetUserScored . '>) ([t](<https:\/\/novadiscord.local\/wiki\/User_talk:' . $targetUserScored . '>)|[c](<https:\/\/novadiscord.local\/wiki\/Special:Contributions\/' . $targetUserScored . '>))","allowed_mentions":{"parse":[]}}';

        $payload = $this->mockHttpFactory->getCaptured()[0];
        $this->assertNotNull($payload, 'Webhook payload should be captured on user unblock');
        $this->assertEquals($expectedPayload, $payload['options']['postData'], 'Webhook payload for user unblock should match expected output');
    }
}
