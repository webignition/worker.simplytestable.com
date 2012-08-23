<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120809165433_create_Task extends BaseMigration
{    
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "CREATE TABLE Task (
                id INT AUTO_INCREMENT NOT NULL,
                state_id INT NOT NULL,
                tasktype_id INT NOT NULL,
                url LONGTEXT NOT NULL,
                timePeriod_id INT DEFAULT NULL,
                output_id INT DEFAULT NULL,
                INDEX IDX_F24C741B5D83CC1 (state_id),
                INDEX IDX_F24C741B7D6EFC3 (tasktype_id),
                UNIQUE INDEX UNIQ_F24C741BE43FFED1 (timePeriod_id),
                UNIQUE INDEX UNIQ_F24C741BDE097880 (output_id),
                PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB",
            "ALTER TABLE Task ADD CONSTRAINT FK_F24C741B5D83CC1 FOREIGN KEY (state_id) REFERENCES State (id)",
            "ALTER TABLE Task ADD CONSTRAINT FK_F24C741B7D6EFC3 FOREIGN KEY (tasktype_id) REFERENCES TaskType (id)",
            "ALTER TABLE Task ADD CONSTRAINT FK_F24C741BE43FFED1 FOREIGN KEY (timePeriod_id) REFERENCES TimePeriod (id)",
            "ALTER TABLE Task ADD CONSTRAINT FK_F24C741BDE097880 FOREIGN KEY (output_id) REFERENCES TaskOutput (id)"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE Task (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                state_id INT NOT NULL,
                tasktype_id INT NOT NULL,
                url LONGTEXT NOT NULL COLLATE NOCASE,
                timePeriod_id INT DEFAULT NULL,
                output_id INT DEFAULT NULL,
                FOREIGN KEY(state_id) REFERENCES State (id),
                FOREIGN KEY(tasktype_id) REFERENCES TaskType (id),
                FOREIGN KEY(timePeriod_id) REFERENCES TimePeriod (id),
                FOREIGN KEY(output_id) REFERENCES TaskOutput (id))",
            "CREATE INDEX IDX_F24C741B5D83CC1 ON Task (state_id)",
            "CREATE INDEX IDX_F24C741B7D6EFC3 ON Task (tasktype_id)",
            "CREATE UNIQUE INDEX UNIQ_F24C741BE43FFED1 ON Task (timePeriod_id)",
            "CREATE UNIQUE INDEX UNIQ_F24C741BDE097880 ON Task (output_id)"
        ); 
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {
        $this->addCommonStatement("DROP TABLE Task");      
        
        parent::down($schema);
    }
}
