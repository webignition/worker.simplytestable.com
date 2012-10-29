<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20121029123528_create_WebResourceTaskOutput extends BaseMigration
{
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "CREATE TABLE WebResourceTaskOutput (
                id INT AUTO_INCREMENT NOT NULL,
                hash VARCHAR(255) NOT NULL,
                output LONGTEXT NOT NULL,
                errorCount INT NOT NULL,
                INDEX hash_index (hash),
                PRIMARY KEY(id))
             DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE WebResourceTaskOutput (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                hash VARCHAR(255) NOT NULL COLLATE NOCASE,
                output LONGTEXT NOT NULL COLLATE NOCASE,
                errorCount INT NOT NULL)",
            "CREATE INDEX hash_index ON WebResourceTaskOutput (hash)"
        ); 
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {
        $this->addCommonStatement("DROP TABLE WebResourceTaskOutput");      
        
        parent::down($schema);
    } 
}
