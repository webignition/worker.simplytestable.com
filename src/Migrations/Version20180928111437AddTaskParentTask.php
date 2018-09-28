<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180928111437AddTaskParentTask extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Task ADD parent_task_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Task ADD CONSTRAINT FK_F24C741BFFFE75C0 FOREIGN KEY (parent_task_id) REFERENCES Task (id)');
        $this->addSql('CREATE INDEX IDX_F24C741BFFFE75C0 ON Task (parent_task_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Task DROP FOREIGN KEY FK_F24C741BFFFE75C0');
        $this->addSql('DROP INDEX IDX_F24C741BFFFE75C0 ON Task');
        $this->addSql('ALTER TABLE Task DROP parent_task_id');
    }
}
