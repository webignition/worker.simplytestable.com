<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180927192850RemoveTaskOutputState extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE TaskOutput DROP FOREIGN KEY FK_C9B3E5C45D83CC1');
        $this->addSql('DROP INDEX IDX_C9B3E5C45D83CC1 ON TaskOutput');
        $this->addSql('ALTER TABLE TaskOutput DROP state_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE TaskOutput ADD state_id INT NOT NULL');
        $this->addSql('ALTER TABLE TaskOutput ADD CONSTRAINT FK_C9B3E5C45D83CC1 FOREIGN KEY (state_id) REFERENCES State (id)');
        $this->addSql('CREATE INDEX IDX_C9B3E5C45D83CC1 ON TaskOutput (state_id)');
    }
}
