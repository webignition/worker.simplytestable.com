<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180927213707DropTaskType extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Task DROP FOREIGN KEY FK_F24C741B7D6EFC3');
        $this->addSql('DROP TABLE TaskType');
        $this->addSql('DROP INDEX IDX_F24C741B7D6EFC3 ON Task');
        $this->addSql('ALTER TABLE Task ADD tasktype VARCHAR(255) NOT NULL, DROP tasktype_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE TaskType (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, description LONGTEXT NOT NULL COLLATE utf8_unicode_ci, selectable TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_F7737B3C5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE Task ADD tasktype_id INT NOT NULL, DROP tasktype');
        $this->addSql('ALTER TABLE Task ADD CONSTRAINT FK_F24C741B7D6EFC3 FOREIGN KEY (tasktype_id) REFERENCES TaskType (id)');
        $this->addSql('CREATE INDEX IDX_F24C741B7D6EFC3 ON Task (tasktype_id)');
    }
}
