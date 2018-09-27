<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180927170004AlterThisWorkerState extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ThisWorker DROP FOREIGN KEY FK_8F3607295D83CC1');
        $this->addSql('DROP INDEX IDX_8F3607295D83CC1 ON ThisWorker');
        $this->addSql('ALTER TABLE ThisWorker ADD state VARCHAR(255) DEFAULT NULL, DROP state_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ThisWorker ADD state_id INT NOT NULL, DROP state');
        $this->addSql('ALTER TABLE ThisWorker ADD CONSTRAINT FK_8F3607295D83CC1 FOREIGN KEY (state_id) REFERENCES State (id)');
        $this->addSql('CREATE INDEX IDX_8F3607295D83CC1 ON ThisWorker (state_id)');
    }
}
