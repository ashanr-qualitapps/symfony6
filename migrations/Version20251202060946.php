<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251202060946 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create sessions table for storing user sessions';
    }

    public function up(Schema $schema): void
    {
        // Create sessions table for Symfony session storage
        $this->addSql('CREATE TABLE sessions (
            sess_id VARCHAR(128) NOT NULL PRIMARY KEY,
            sess_data BLOB NOT NULL,
            sess_lifetime INT UNSIGNED NOT NULL,
            sess_time INT UNSIGNED NOT NULL,
            INDEX sess_lifetime_idx (sess_lifetime),
            INDEX sess_time_idx (sess_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin');
    }

    public function down(Schema $schema): void
    {
        // Drop sessions table
        $this->addSql('DROP TABLE IF EXISTS sessions');
    }
}
