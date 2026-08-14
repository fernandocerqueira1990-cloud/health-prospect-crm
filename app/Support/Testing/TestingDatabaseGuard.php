<?php

namespace App\Support\Testing;

use LogicException;

class TestingDatabaseGuard
{
    public function ensureSafe(string $environment, string $database): void
    {
        if ($environment !== 'testing') {
            return;
        }

        if ($database === '' || ! str_ends_with($database, '_test')) {
            throw new LogicException(
                'Execução de testes recusada: o banco configurado deve ser dedicado e terminar com "_test".',
            );
        }
    }
}
