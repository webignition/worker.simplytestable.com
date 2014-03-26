<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120731224355_create_CoreApplication extends BaseMigration
{
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "CREATE TABLE CoreApplication (id INT AUTO_INCREMENT NOT NULL, url VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_F5051811F47645AE (url), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE CoreApplication (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, url VARCHAR(255) NOT NULL COLLATE NOCASE)",
            "CREATE UNIQUE INDEX UNIQ_F5051811F47645AE ON CoreApplication (url)"
        );      
        
        parent::up($schema);      
    }

    public function down(Schema $schema)
    {
        $this->addCommonStatement("DROP TABLE CoreApplication");        
        parent::down($schema);       
    }   
}
