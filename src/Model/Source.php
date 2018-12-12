<?php

namespace App\Model;

class Source
{
    const TYPE_CACHED_RESOURCE = 'cached-resource';
    const TYPE_UNAVAILABLE = 'unavailable';

    const FAILURE_TYPE_HTTP = 'http';
    const FAILURE_TYPE_CURL = 'curl';
    const FAILURE_TYPE_UNKNOWN = 'unknown';

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $value;

    public function __construct(string $url, string $type, string $value)
    {
        $this->url = $url;
        $this->type = $type;
        $this->value = $value;
    }

    public static function fromArray(array $data): Source
    {
        $url = $data['url'] ?? '';
        $type = $data['type'] ?? '';
        $value = $data['value'] ?? '';

        return new static($url, $type, $value);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isCachedResource(): bool
    {
        return self::TYPE_CACHED_RESOURCE === $this->type;
    }

    public function isUnavailable(): bool
    {
        return self::TYPE_UNAVAILABLE === $this->type;
    }

    public function getFailureType(): ?string
    {
        if ($this->isCachedResource()) {
            return null;
        }

        $failureComponents = $this->getFailureComponents();

        return $failureComponents['failure_type'];
    }

    public function getFailureCode(): ?int
    {
        if ($this->isCachedResource()) {
            return null;
        }

        $failureComponents = $this->getFailureComponents();

        return $failureComponents['failure_code'];
    }

    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'type' => $this->type,
            'value' => $this->value,
        ];
    }

    private function getFailureComponents(): array
    {
        $failureType = null;
        $failureCode = null;

        $expectedPartCount = 2;

        $valueParts = explode(':', $this->value, $expectedPartCount);

        if (count($valueParts) === $expectedPartCount) {
            $failureType = $valueParts[0];
            $failureCode = (int) $valueParts[1];
        }

        return [
            'failure_type' => $failureType,
            'failure_code' => $failureCode,
        ];
    }
}
