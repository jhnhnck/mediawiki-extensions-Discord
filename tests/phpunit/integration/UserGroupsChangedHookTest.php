<?php
/**
 * NovaDiscord - UserGroupsChanged Test Case
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord\Tests\Integration;

/**
 * @group Database
 * @covers \MediaWiki\Extension\NovaDiscord\UserAlert::onUserGroupsChanged
 */
class UserGroupsChangedHookTest extends NovaDiscordIntegrationTestCase {
    public function testUserGroupsChangedSendsWebhook(): void {
        // TODO: This test requires complex setup to properly trigger the UserGroupsChanged hook
        // with a valid performer. UserGroupManager->addUserToGroup() doesn't provide a way to
        // specify the performing user, causing the hook to receive performer=false (autopromotion)
        // which is filtered out by the hook implementation. This needs investigation into
        // MediaWiki's SpecialUserRights or a different approach to trigger the hook.
        $this->markTestSkipped('Requires complex setup to trigger hook with proper performer');
    }
}
