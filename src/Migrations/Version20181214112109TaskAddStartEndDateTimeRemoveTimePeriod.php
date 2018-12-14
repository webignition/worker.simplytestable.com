<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181214112109TaskAddStartEndDateTimeRemoveTimePeriod extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Task DROP FOREIGN KEY FK_F24C741BE43FFED1');
        $this->addSql('DROP INDEX UNIQ_F24C741BE43FFED1 ON Task');
        $this->addSql('ALTER TABLE Task ADD startDateTime DATETIME DEFAULT NULL, ADD endDateTime DATETIME DEFAULT NULL, DROP timePeriod_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Task ADD timePeriod_id INT DEFAULT NULL, DROP startDateTime, DROP endDateTime');
        $this->addSql('ALTER TABLE Task ADD CONSTRAINT FK_F24C741BE43FFED1 FOREIGN KEY (timePeriod_id) REFERENCES TimePeriod (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F24C741BE43FFED1 ON Task (timePeriod_id)');
    }
}
