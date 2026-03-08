<?php
/**
 * NovaDiscord - Config Service
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord;

use MediaWiki\Config\ServiceOptions;

class NovaDiscordConfig {
    public const CONSTRUCTOR_OPTIONS = [
        'DiscordWebhooks',
        'DiscordDefaultHooks',
        'DiscordNoBots',
        'DiscordNoMinor',
        'DiscordNoNull',
        'DiscordMaxChars',
        'DiscordDisabledNS',
        'DiscordDisabledUsers',
        'DiscordPrivateExceptionAlerts',
    ];

    private ServiceOptions $options;

    public function __construct(ServiceOptions $options) {
        $options->assertRequiredOptions(self::CONSTRUCTOR_OPTIONS);
        $this->options = $options;
    }

    /**
     * Returns the URLs of webhooks that have $hookName enabled.
     *
     * Each webhook entry in DiscordWebhooks may have an optional 'hooks' key
     * (array of hook names). If absent, the webhook falls back to DiscordDefaultHooks.
     * A null value (the default) means all hooks are enabled for that webhook.
     */
    // Hooks that are never included in the null (all hooks) default and must be explicitly listed.
    private const EXPLICIT_ONLY_HOOKS = ['LogException'];

    public function getWebhooksForHook(string $hookName): array {
        $defaultHooks = $this->options->get('DiscordDefaultHooks');
        $urls = [];
        foreach ($this->options->get('DiscordWebhooks') as $webhook) {
            $hooks = array_key_exists('hooks', $webhook) ? $webhook['hooks'] : $defaultHooks;
            if ($hooks === null) {
                if (in_array($hookName, self::EXPLICIT_ONLY_HOOKS)) {
                    continue;
                }
            } elseif (!in_array($hookName, $hooks)) {
                continue;
            }
            $urls[] = $webhook['url'];
        }
        return $urls;
    }

    public function isNoBots(): bool {
        return (bool)$this->options->get('DiscordNoBots');
    }

    public function isNoMinor(): bool {
        return (bool)$this->options->get('DiscordNoMinor');
    }

    public function isNoNull(): bool {
        return (bool)$this->options->get('DiscordNoNull');
    }

    public function getMaxChars(): int {
        return (int)$this->options->get('DiscordMaxChars');
    }

    public function getDisabledNS(): array {
        return (array)$this->options->get('DiscordDisabledNS');
    }

    public function getDisabledUsers(): array {
        return (array)$this->options->get('DiscordDisabledUsers');
    }

    public function isPrivateExceptionAlertsEnabled(): bool {
        return (bool)$this->options->get('DiscordPrivateExceptionAlerts');
    }
}
