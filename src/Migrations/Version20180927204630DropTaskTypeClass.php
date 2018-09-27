<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180927204630DropTaskTypeClass extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE TaskType DROP FOREIGN KEY FK_F7737B3CAEA19A54');
        $this->addSql('DROP TABLE TaskTypeClass');
        $this->addSql('DROP INDEX IDX_F7737B3CAEA19A54 ON TaskType');
        $this->addSql('ALTER TABLE TaskType DROP tasktypeclass_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE TaskTypeClass (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, description LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci, UNIQUE INDEX UNIQ_F92FE5F25E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE TaskType ADD tasktypeclass_id INT NOT NULL');
        $this->addSql('ALTER TABLE TaskType ADD CONSTRAINT FK_F7737B3CAEA19A54 FOREIGN KEY (tasktypeclass_id) REFERENCES TaskTypeClass (id)');
        $this->addSql('CREATE INDEX IDX_F7737B3CAEA19A54 ON TaskType (tasktypeclass_id)');
    }
}
