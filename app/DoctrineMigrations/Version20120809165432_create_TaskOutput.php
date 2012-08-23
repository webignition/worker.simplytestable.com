<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120809165432_create_TaskOutput extends BaseMigration
{
    public function up(Schema $schema)
    {        
        $this->statements['mysql'] = array(
            "CREATE TABLE TaskOutput (id INT AUTO_INCREMENT NOT NULL, state_id INT NOT NULL, output LONGTEXT DEFAULT NULL, contentType LONGTEXT DEFAULT NULL, INDEX IDX_C9B3E5C45D83CC1 (state_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB",
            "ALTER TABLE TaskOutput ADD CONSTRAINT FK_C9B3E5C45D83CC1 FOREIGN KEY (state_id) REFERENCES State (id)"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE TaskOutput (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                state_id INT NOT NULL,
                output LONGTEXT DEFAULT NULL COLLATE NOCASE,
                contentType LONGTEXT DEFAULT NULL COLLATE NOCASE,
                FOREIGN KEY(state_id) REFERENCES State (id))",
            "CREATE INDEX IDX_C9B3E5C45D83CC1 ON TaskOutput (state_id)"
        ); 
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {     
        $this->addCommonStatement("DROP TABLE TaskOutput");      
        
        parent::down($schema);
    }
}
