<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130108162644_create_TimeCachedTaskOutput extends BaseMigration
{
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "CREATE TABLE TimeCachedTaskOutput (
                id INT AUTO_INCREMENT NOT NULL,
                hash VARCHAR(255) NOT NULL,
                output LONGTEXT NOT NULL,
                errorCount INT NOT NULL,
                warningCount INT NOT NULL,
                maxAge INT NOT NULL,
                lastModified DATETIME NOT NULL,
                INDEX hash_index (hash),
                PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE TimeCachedTaskOutput (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                hash VARCHAR(255) NOT NULL,
                output LONGTEXT NOT NULL,
                errorCount INT NOT NULL,
                warningCount INT NOT NULL,
                maxAge INT NOT NULL,
                lastModified DATETIME NOT NULL)"
        ); 
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {
        $this->addCommonStatement("DROP TABLE TimeCachedTaskOutput");      
        
        parent::down($schema);
    }
}
