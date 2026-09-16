<?php

namespace Tests\Unit;

use App\MediaProviders\AgoraMediaProvider;
use App\MediaProviders\MediaProviderManager;
use App\Models\Call;
use App\Models\User;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MediaProviderManagerTest extends TestCase
{
    private function managerConfig(): array
    {
        return [
            'default' => 'agora',
            'providers' => [
                'agora' => [
                    'app_id' => 'example-app-id',
                    'app_certificate' => 'example-certificate',
                    'token_expires_after' => 300,
                ],
            ],
        ];
    }

    public function test_resolves_the_configured_default_driver(): void
    {
        $manager = new MediaProviderManager($this->managerConfig());

        $driver = $manager->driver();

        $this->assertInstanceOf(AgoraMediaProvider::class, $driver);
        $this->assertSame('agora', $driver->name());
        $this->assertSame('example-app-id', $driver->appId());
    }

    public function test_resolves_a_named_driver(): void
    {
        $manager = new MediaProviderManager($this->managerConfig());

        $driver = $manager->driver('agora');

        $this->assertInstanceOf(AgoraMediaProvider::class, $driver);
    }

    public function test_throws_for_a_provider_without_configuration(): void
    {
        $manager = new MediaProviderManager($this->managerConfig());

        $this->expectException(InvalidArgumentException::class);

        $manager->driver('daily');
    }

    public function test_throws_for_a_known_but_unimplemented_provider(): void
    {
        $manager = new MediaProviderManager([
            'default' => 'daily',
            'providers' => [
                'daily' => [
                    'app_id' => 'a1b2c3d4e5f60718293a4b5c6d7e8f90',
                    'app_certificate' => '9f8e7d6c5b4a39281706f5e4d3c2b1a0',
                    'token_expires_after' => 300,
                ],
            ],
        ]);

        $this->expectException(RuntimeException::class);

        $manager->driver('daily');
    }

    public function test_agora_driver_builds_a_join_payload(): void
    {
        $driver = new AgoraMediaProvider('a1b2c3d4e5f60718293a4b5c6d7e8f90', '9f8e7d6c5b4a39281706f5e4d3c2b1a0', 300);

        $call = new Call;
        $call->channel_name = 'call-test-channel';

        $user = new User;
        $user->id = 42;

        $payload = $driver->joinPayload($user, $call);

        $this->assertSame('agora', $payload->provider);
        $this->assertSame('a1b2c3d4e5f60718293a4b5c6d7e8f90', $payload->appId);
        $this->assertSame('call-test-channel', $payload->channel);
        $this->assertSame(42, $payload->uid);
        $this->assertIsString($payload->token);
        $this->assertNotSame('', $payload->token);
    }
}
