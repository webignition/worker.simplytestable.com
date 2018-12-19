<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181219100830CachedResourceAddRequestHash extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX hash_url_unique ON CachedResource');
        $this->addSql('ALTER TABLE CachedResource DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE CachedResource DROP id, CHANGE urlhash requestHash VARCHAR(32) NOT NULL');
        $this->addSql('ALTER TABLE CachedResource ADD PRIMARY KEY (requestHash)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE CachedResource DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE CachedResource ADD id CHAR(36) NOT NULL COLLATE utf8mb4_unicode_ci COMMENT \'(DC2Type:guid)\', CHANGE requesthash urlHash VARCHAR(32) NOT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('CREATE UNIQUE INDEX hash_url_unique ON CachedResource (urlHash)');
        $this->addSql('ALTER TABLE CachedResource ADD PRIMARY KEY (id)');
    }
}
