<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180928104346DropTimeCachedTaskOutput extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE TimeCachedTaskOutput');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE TimeCachedTaskOutput (id INT AUTO_INCREMENT NOT NULL, hash VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, output LONGTEXT NOT NULL COLLATE utf8_unicode_ci, errorCount INT NOT NULL, warningCount INT NOT NULL, maxAge INT NOT NULL, lastModified DATETIME NOT NULL, INDEX hash_index (hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
    }
}
