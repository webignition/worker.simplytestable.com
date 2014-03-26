<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120802182016_create_ThisWorker extends BaseMigration
{
    public function up(Schema $schema)
    {        
        $this->statements['mysql'] = array(
            "CREATE TABLE ThisWorker (
                id INT AUTO_INCREMENT NOT NULL,
                state_id INT NOT NULL,
                hostname VARCHAR(255) NOT NULL,
                activationToken VARCHAR(255) DEFAULT NULL,
                UNIQUE INDEX UNIQ_8F360729F47645AE (hostname),
                INDEX IDX_8F3607295D83CC1 (state_id),
                PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB",
            "ALTER TABLE ThisWorker ADD CONSTRAINT FK_8F3607295D83CC1 FOREIGN KEY (state_id) REFERENCES State (id)"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE ThisWorker (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                state_id INT NOT NULL,
                hostname VARCHAR(255) NOT NULL COLLATE NOCASE,
                activationToken VARCHAR(255) NOT NULL COLLATE NOCASE,
                FOREIGN KEY(state_id) REFERENCES State (id))",
            "CREATE UNIQUE INDEX UNIQ_8F360729F47645AE ON ThisWorker (hostname)",
            "CREATE INDEX IDX_8F3607295D83CC1 ON ThisWorker (state_id)"             
        );
        
        parent::up($schema);
    }

    public function down(Schema $schema)
    {
        $this->addCommonStatement("DROP TABLE ThisWorker");    
        
        parent::down($schema);
    }
}
