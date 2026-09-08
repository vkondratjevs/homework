<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260908174814 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $sql = <<<'SQL'
        CREATE TABLE applications
        (
            id            UUID                        NOT NULL,
            personal_code VARCHAR(12)                 NOT NULL,
            amount        NUMERIC(10, 2)              NOT NULL,
            term          SMALLINT                    NOT NULL,
            currency      VARCHAR(3)                  NOT NULL,
            status        SMALLINT                    NOT NULL,
            created_at    TIMESTAMP(0) WITH TIME ZONE NOT NULL,
            updated_at    TIMESTAMP(0) WITH TIME ZONE NOT NULL,
            PRIMARY KEY (id)
        )
        SQL;
        $this->addSql($sql);
        $this->addSql('CREATE INDEX idx_applications_status ON applications (status)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE applications');
    }
}
