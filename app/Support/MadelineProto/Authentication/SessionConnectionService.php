<?php

declare(strict_types=1);

namespace App\Support\MadelineProto\Authentication;

use App\Models\AdminAuditLog;
use App\Models\TelegramSessionConnection;
use App\Support\MadelineProto\MadelineProtoConfig;
use Illuminate\Support\Facades\DB;
use SensitiveParameter;

final readonly class SessionConnectionService
{
    public function __construct(
        private SessionFiles $files,
        private SessionAuthenticator $authenticator,
        private MadelineProtoConfig $config,
    ) {}

    public function create(string $name, int $actorId): TelegramSessionConnection
    {
        if (! preg_match('/\A[a-z][a-z0-9_-]*\z/D', $name)
            || strlen($name) > (int) config('madelineproto.admin_auth.max_name_length')) {
            throw new SessionConnectionException('name_invalid');
        }

        $this->ensureConfigured();

        return $this->files->catalogLocked(function () use ($name, $actorId): TelegramSessionConnection {
            return $this->files->locked($name, function () use ($name, $actorId): TelegramSessionConnection {
                if (TelegramSessionConnection::query()->where('name', $name)->exists()) {
                    throw new SessionConnectionException('exists');
                }
                if (TelegramSessionConnection::query()->count() >= (int) config('madelineproto.admin_auth.max_sessions')) {
                    throw new SessionConnectionException('limit');
                }
                $this->files->reserve($name);

                return DB::transaction(function () use ($name, $actorId): TelegramSessionConnection {
                    $connection = TelegramSessionConnection::query()->create([
                        'name' => $name, 'created_by' => $actorId, 'stage' => LoginStage::Phone,
                        'expires_at' => now()->addMinutes((int) config('madelineproto.admin_auth.ttl_minutes')),
                    ]);
                    $this->audit($connection, $actorId, 'created', null);

                    return $connection;
                });
            });
        });
    }

    public function submit(int $id, int $actorId, LoginStage $stage, #[SensitiveParameter] string $value): TelegramSessionConnection
    {
        return $this->operate($id, $actorId, function (TelegramSessionConnection $connection) use ($stage, $value): LoginResult {
            if ($connection->stage === LoginStage::Ready) {
                throw new SessionConnectionException('already_ready');
            }
            if ($stage !== LoginStage::Phone && $connection->expired()) {
                throw new SessionConnectionException('expired');
            }
            if ($stage !== LoginStage::Phone && $connection->stage !== $stage) {
                throw new SessionConnectionException('state_changed');
            }
            if (! $this->files->pending($connection->name)) {
                throw new SessionConnectionException('storage');
            }
            if ($stage === LoginStage::Phone) {
                $connection->expires_at = now()->addMinutes((int) config('madelineproto.admin_auth.ttl_minutes'));
            }

            return $this->authenticator->submit($connection->name, $stage, $value);
        });
    }

    public function inspect(int $id, int $actorId): TelegramSessionConnection
    {
        return $this->operate($id, $actorId, function (TelegramSessionConnection $connection): LoginResult {
            if ($connection->expired()) {
                throw new SessionConnectionException('expired');
            }
            if (! $this->files->exists($connection->name)) {
                throw new SessionConnectionException('storage');
            }

            return $this->authenticator->inspect($connection->name);
        });
    }

    private function operate(int $id, int $actorId, \Closure $callback): TelegramSessionConnection
    {
        $connection = $this->owned($id, $actorId);
        $this->ensureConfigured();

        return $this->files->locked($connection->name, function () use ($id, $actorId, $callback): TelegramSessionConnection {
            $connection = $this->owned($id, $actorId);
            if ($connection->retry_at?->isFuture()) {
                throw new SessionConnectionException('flood_wait');
            }
            $before = $connection->stage->value;
            $result = $callback($connection);

            if ($result->stage !== null && $result->stage !== LoginStage::Ready) {
                $this->files->quarantine($connection->name);
            }

            DB::transaction(function () use ($connection, $result, $actorId, $before): void {
                $connection->stage = $result->stage ?? $connection->stage;
                $connection->last_error = $result->error;
                $connection->retry_at = $result->retryAfter > 0 ? now()->addSeconds($result->retryAfter) : null;
                $connection->save();
                $this->audit($connection, $actorId, $result->error === null ? 'advanced' : 'failed', $before);
            });

            if ($connection->stage === LoginStage::Ready && $result->error === null) {
                $this->files->publish($connection->name);
            }

            return $connection;
        });
    }

    private function owned(int $id, int $actorId): TelegramSessionConnection
    {
        $connection = TelegramSessionConnection::query()->find($id);
        if ($connection === null || $connection->created_by !== $actorId) {
            throw new SessionConnectionException('not_owned');
        }

        return $connection;
    }

    private function ensureConfigured(): void
    {
        try {
            if ($this->config->apiId() > 0 && $this->config->apiHash() !== '') {
                return;
            }
        } catch (\Throwable) {
        }

        throw new SessionConnectionException('configuration');
    }

    private function audit(TelegramSessionConnection $connection, int $actorId, string $action, ?string $before): void
    {
        AdminAuditLog::query()->create([
            'actor_admin_id' => $actorId, 'actor_admin_name' => null,
            'target_type' => 'telegram_session', 'target_id' => $connection->id,
            'action' => 'telegram_session.'.$action,
            'changes' => ['stage' => ['old' => $before, 'new' => $connection->stage->value]],
            'meta' => ['source' => 'moonshine', 'error' => $connection->last_error],
            'created_at' => now(),
        ]);
    }
}
