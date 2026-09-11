<?php

declare(strict_types=1);

namespace App\Support\MadelineProto\Authentication;

use App\Support\MadelineProto\MadelineProtoClientFactory;
use danog\MadelineProto\API;
use danog\MadelineProto\RPCErrorException;
use SensitiveParameter;
use Throwable;

final readonly class MadelineSessionAuthenticator implements SessionAuthenticator
{
    public function __construct(private MadelineProtoClientFactory $factory, private AuthenticationRuntime $runtime) {}

    public function submit(string $name, LoginStage $stage, #[SensitiveParameter] string $value): LoginResult
    {
        return $this->runtime->execute(fn (): LoginResult => $this->authenticate($name, $stage, $value));
    }

    private function authenticate(string $name, LoginStage $stage, #[SensitiveParameter] string $value): LoginResult
    {
        try {
            $client = $this->factory->makeForAuthentication($name);
            $actual = $this->stage($client->getAuthorization());
            if ($actual === LoginStage::Ready) {
                return new LoginResult($actual, 'already_ready');
            }
            if ($stage !== LoginStage::Phone && $actual !== $stage) {
                return new LoginResult($actual, 'state_changed');
            }

            match ($stage) {
                LoginStage::Phone => $client->phoneLogin($value),
                LoginStage::Code => $client->completePhoneLogin($value),
                LoginStage::Password => $client->complete2faLogin($value),
                default => throw new SessionConnectionException('state_changed'),
            };

            $actual = $this->stage($client->getAuthorization());

            return new LoginResult($actual, $actual === LoginStage::Unsupported ? 'signup_unsupported' : null);
        } catch (Throwable $exception) {
            return $this->failure($exception, $stage);
        }
    }

    public function inspect(string $name): LoginResult
    {
        return $this->runtime->execute(function () use ($name): LoginResult {
            try {
                return new LoginResult($this->stage($this->factory->makeForAuthentication($name)->getAuthorization()));
            } catch (Throwable $exception) {
                return $this->failure($exception);
            }
        });
    }

    private function stage(int $authorization): LoginStage
    {
        return match ($authorization) {
            API::LOGGED_IN => LoginStage::Ready,
            API::WAITING_CODE => LoginStage::Code,
            API::WAITING_PASSWORD => LoginStage::Password,
            API::WAITING_SIGNUP => LoginStage::Unsupported,
            default => LoginStage::Phone,
        };
    }

    private function failure(#[SensitiveParameter] Throwable $exception, ?LoginStage $stage = null): LoginResult
    {
        if ($exception instanceof RPCErrorException) {
            if (preg_match('/^FLOOD_(?:TEST_PHONE_)?WAIT_(\d+)$/', $exception->rpc, $matches)) {
                return new LoginResult($stage === LoginStage::Code ? LoginStage::Phone : null, 'flood_wait', (int) $matches[1]);
            }

            $reason = match ($exception->rpc) {
                'PHONE_NUMBER_INVALID' => 'phone_invalid',
                'PHONE_NUMBER_BANNED' => 'phone_banned',
                'PHONE_CODE_INVALID', 'PHONE_CODE_EMPTY' => 'code_invalid',
                'PHONE_CODE_EXPIRED', 'PHONE_CODE_HASH_EMPTY' => 'code_expired',
                'PASSWORD_HASH_INVALID' => 'password_invalid',
                'API_ID_INVALID', 'API_ID_PUBLISHED_FLOOD' => 'configuration',
                default => 'unavailable',
            };

            // completePhoneLogin resets the SDK state before attempting sign-in.
            return new LoginResult($stage === LoginStage::Code ? LoginStage::Phone : null, $reason);
        }

        return new LoginResult(null, 'unavailable');
    }
}
