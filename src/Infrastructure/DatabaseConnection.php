<?php

declare(strict_types=1);

namespace App\Infrastructure;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use LLPhant\Embeddings\VectorStores\Doctrine\VectorType;

class DatabaseConnection
{
    private function getRequiredEnv(string $key): string
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            throw new \RuntimeException("Environment variable '{$key}' is not set.");
        }
        return $value;
    }

    public function create(): EntityManager
    {
        Type::addType(VectorType::VECTOR, VectorType::class);

        $isDevMode = true;
        $dbParams = [
            'dbname' => $this->getRequiredEnv('DB_NAME'),
            'user' => $this->getRequiredEnv('DB_USER'),
            'password' => $this->getRequiredEnv('DB_PASSWORD'),
            'host' => $this->getRequiredEnv('DB_HOST'),
            'port' => (int)$this->getRequiredEnv('DB_PORT'),
            'driver' => 'pdo_pgsql',
        ];

        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: [__DIR__ . '/../'],
            isDevMode: $isDevMode,
        );

        return new EntityManager(
            \Doctrine\DBAL\DriverManager::getConnection($dbParams),
            $config
        );
    }
}
