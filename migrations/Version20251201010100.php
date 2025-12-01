<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251201010100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users table with typical fields (id, email, roles, password, created_at, updated_at)';
    }

    public function up(Schema $schema): void
    {
        // Create users table
        $usersTable = $schema->createTable('users');
        $usersTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $usersTable->setPrimaryKey(['id']);
        $usersTable->addColumn('email', 'string', ['length' => 180]);
        $usersTable->addUniqueIndex(['email'], 'idx_users_email');
        // Roles column as json (DB platform needs to support json column type; will be adapted by Doctrine DBAL)
        $usersTable->addColumn('roles', 'json', ['notnull' => true]);
        $usersTable->addColumn('password', 'string', ['length' => 255]);
        $usersTable->addColumn('created_at', 'datetime', []);
        $usersTable->addColumn('updated_at', 'datetime', ['notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        // Drop users table
        $schema->dropTable('users');
    }
}
