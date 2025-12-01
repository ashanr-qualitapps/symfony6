<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251201000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Baseline migration: initial project baseline (no entities present)';
    }

    public function up(Schema $schema): void
    {
        // intentionally empty baseline migration - no schema changes
    }

    public function down(Schema $schema): void
    {
        // nothing to revert
    }
}
