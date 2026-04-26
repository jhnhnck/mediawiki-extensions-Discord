<?php
/**
 * NovaDiscord - HttpRequestFactory testing double
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord\Tests\Doubles;

use MediaWiki\Http\HttpRequestFactory;

class MockRequestFactory extends HttpRequestFactory {
    private array $captured = [];

    // public function __construct() {}

    public function create($url, array $options = [], $caller = __METHOD__): MockRequest {
        return new MockRequest($url, $options, $caller, $this->captured);
    }

    public function getCaptured(): array {
        return $this->captured;
    }

    public function reset(): void {
        $this->captured = [];
    }
}

/**
 * Minimal request object
 */
class MockRequest {
    private string $url;
    private array $options;
    private string $caller;
    private array $headers = [];
    private array $sink;

    /**
     * @param array<int,mixed> $options
     * @param array<int,mixed> $sink
     */
    public function __construct(string $url, array $options, string $caller, array &$sink) {
        $this->url = $url;
        $this->options = $options;
        $this->caller = $caller;
        $this->sink = &$sink;
    }

    public function setHeader(string $name, string $value): void {
        $this->headers[$name] = $value;
    }

    /**
     * Execute the fake request; append a record to the sink.
     * Returns an object that quacks like a MediaWiki StatusValue.
     */
    public function execute() {
        $this->sink[] = [
            'url' => $this->url,
            'method' => $this->options['method'] ?? 'GET',
            'options' => $this->options,
            'headers' => $this->headers,
            'caller' => $this->caller,
            'ts' => microtime(true),
        ];
        return new class {
            public function isOK(): bool { return true; }
        };
    }

    public function getStatus(): int {
        return 200;
    }

    public function getContent(): string {
        return '';
    }

    public function getHeaders(): array {
        return $this->headers;
    }
}
