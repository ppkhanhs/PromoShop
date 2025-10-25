<?php

namespace App\Auth;

use App\Services\CassandraDataService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class CassandraUserProvider implements UserProvider
{
    public function __construct(protected CassandraDataService $service)
    {
    }

    public function retrieveById($identifier): ?Authenticatable
    {
        $user = $this->service->fetchUserById((string) $identifier);
        if (!$user) {
            return null;
        }

        return new CassandraAuthenticatable($user->toArray());
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        // Không hỗ trợ token "remember me" với Cassandra API
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        // Bỏ qua: không lưu token remember trên Cassandra
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $email = strtolower(trim((string) ($credentials['email'] ?? $credentials['username'] ?? '')));
        if ($email === '') {
            return null;
        }

        $user = $this->service->fetchUserByEmail($email);
        if ($user) {
            return new CassandraAuthenticatable($user->toArray());
        }

        return new CassandraAuthenticatable([
            'email' => $email,
        ]);
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $email = strtolower(trim((string) ($credentials['email'] ?? $credentials['username'] ?? '')));
        $password = (string) ($credentials['password'] ?? '');

        if ($email === '' || $password === '') {
            return false;
        }

        $result = $this->service->authenticate([
            'email' => $email,
            'password' => $password,
        ]);

        if (!$result) {
            return false;
        }

        if (isset($result['token'])) {
            session(['cassandra_token' => $result['token']]);
        }

        if (isset($result['user']) && $user instanceof CassandraAuthenticatable) {
            foreach ((array) $result['user'] as $key => $value) {
                $user->setAttribute((string) $key, $value);
            }
            if (isset($result['user']['user_id'])) {
                $user->setAttribute('user_id', $result['user']['user_id']);
            }
        }

        return true;
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials = [], bool $force = false): bool
    {
        // Mật khẩu do Cassandra quản lý, không thực hiện rehash phía Laravel.
        return false;
    }
}
