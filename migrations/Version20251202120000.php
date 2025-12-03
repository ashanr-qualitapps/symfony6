<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates the login_attempts table for rate limiting
 */
final class Version20251202120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates login_attempts table for rate limiting failed login attempts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT NOT NULL,
            identifier VARCHAR(255) NOT NULL,
            attempts INT NOT NULL DEFAULT 0,
            last_attempt DATETIME NOT NULL,
            PRIMARY KEY(id),
            UNIQUE INDEX UNIQ_login_attempts_identifier (identifier)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS login_attempts');
    }
}
