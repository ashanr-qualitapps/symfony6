<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251204000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure login_attempts table exists (safe migration to re-create the table if missing)';
    }

    public function up(Schema $schema): void
    {
        // Create the login_attempts table if it doesn't exist. We intentionally do not DROP it on down()
        // to avoid accidental data loss during rollbacks.
        $this->addSql("CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT NOT NULL,
            identifier VARCHAR(255) NOT NULL,
            attempts INT NOT NULL DEFAULT 0,
            last_attempt DATETIME NOT NULL,
            PRIMARY KEY(id),
            UNIQUE INDEX UNIQ_login_attempts_identifier (identifier)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        // This migration is intentionally non-destructive on revert to keep data safe.
    }
}
