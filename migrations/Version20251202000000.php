<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251202000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create api_tokens table for storing API tokens';
    }

    public function up(Schema $schema): void
    {
        // Create api_tokens table
        $tokensTable = $schema->createTable('api_tokens');
        $tokensTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $tokensTable->setPrimaryKey(['id']);
        $tokensTable->addColumn('token', 'string', ['length' => 255]);
        $tokensTable->addUniqueIndex(['token'], 'idx_api_tokens_token');
        $tokensTable->addColumn('user_id', 'integer', ['notnull' => true]);
        $tokensTable->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_api_tokens_user_id');
        $tokensTable->addColumn('expires_at', 'datetime', []);
        $tokensTable->addColumn('created_at', 'datetime', []);
    }

    public function down(Schema $schema): void
    {
        // Drop api_tokens table
        $schema->dropTable('api_tokens');
    }
}