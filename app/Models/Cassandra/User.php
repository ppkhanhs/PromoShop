<?php

namespace App\Models\Cassandra;

class User extends CassandraModel
{
    public function getAuthIdentifier(): ?string
    {
        return $this->get('user_id');
    }

    public function fullName(): string
    {
        $name = trim((string) $this->get('name', ''));
        if ($name !== '') {
            return $name;
        }

        return trim(sprintf(
            '%s %s',
            (string) $this->get('first_name', ''),
            (string) $this->get('last_name', '')
        ));
    }
}

