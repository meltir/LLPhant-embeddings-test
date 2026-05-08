<?php

declare(strict_types=1);

namespace App\Infrastructure;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use LLPhant\Embeddings\VectorStores\Doctrine\VectorType;

class DatabaseConnection
{
    public function create(): EntityManager
    {
        Type::addType(VectorType::VECTOR, VectorType::class);

        $isDevMode = true;
        $dbParams = [
            'dbname' => getenv('DB_NAME') ?: 'sherlock',
            'user' => getenv('DB_USER') ?: 'postgres',
            'password' => getenv('DB_PASSWORD') ?: 'password',
            'host' => getenv('DB_HOST') ?: 'db',
            'port' => (int)(getenv('DB_PORT') ?: 5432),
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
