<?php

namespace App\MediaProviders;

use App\Contracts\MediaProvider;
use InvalidArgumentException;
use RuntimeException;

/**
 * Resolves the configured media provider strategy ahead of a call accept.
 *
 * Drivers are memoized per name, so repeated steps within a request build the
 * underlying token once per provider.
 */
final class MediaProviderManager
{
    /** @var array<string, MediaProvider> */
    private array $resolved = [];

    /**
     * @param  array{default?: string, providers?: array<string, array<string, mixed>>}  $config
     */
    public function __construct(private readonly array $config)
    {
        //
    }

    public function defaultName(): string
    {
        return $this->config['default'] ?? 'agora';
    }

    public function driver(?string $name = null): MediaProvider
    {
        $name ??= $this->defaultName();

        return $this->resolved[$name] ??= $this->build($name);
    }

    /**
     * @return array<string, string> Available provider names.
     */
    public function availableProviders(): array
    {
        $providers = $this->config['providers'] ?? [];

        return array_combine(array_keys($providers), array_keys($providers)) ?: [];
    }

    private function build(string $name): MediaProvider
    {
        $options = $this->config['providers'][$name] ?? null;

        if ($options === null) {
            throw new InvalidArgumentException(
                "Media provider [$name] is not configured under \"calls.media.providers\".",
            );
        }

        return match ($name) {
            'agora' => new AgoraMediaProvider(
                appId: (string) ($options['app_id'] ?? ''),
                appCertificate: (string) ($options['app_certificate'] ?? ''),
                tokenExpiresAfter: (int) ($options['token_expires_after'] ?? 3600),
            ),
            default => throw new RuntimeException("Unsupported media provider [$name]."),
        };
    }
}
