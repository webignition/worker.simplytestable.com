<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180927125529RemoveStateNextState extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE State DROP FOREIGN KEY FK_6252FDFF4A689548');
        $this->addSql('DROP INDEX UNIQ_6252FDFF4A689548 ON State');
        $this->addSql('ALTER TABLE State DROP nextState_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE State ADD nextState_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE State ADD CONSTRAINT FK_6252FDFF4A689548 FOREIGN KEY (nextState_id) REFERENCES State (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6252FDFF4A689548 ON State (nextState_id)');
    }
}
