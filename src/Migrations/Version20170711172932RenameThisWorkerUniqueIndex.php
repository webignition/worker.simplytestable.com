<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170711172932RenameThisWorkerUniqueIndex extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('DROP INDEX uniq_8f360729f47645ae ON ThisWorker');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8F360729E551C011 ON ThisWorker (hostname)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('DROP INDEX uniq_8f360729e551c011 ON ThisWorker');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8F360729F47645AE ON ThisWorker (hostname)');
    }
}
