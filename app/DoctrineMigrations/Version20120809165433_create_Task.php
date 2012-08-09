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
                INDEX IDX_F24C741B5D83CC1 (state_id),
                INDEX IDX_F24C741B7D6EFC3 (tasktype_id),
                INDEX IDX_F24C741BE43FFED1 (timePeriod_id),
                PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB",
            "ALTER TABLE Task ADD CONSTRAINT FK_F24C741B5D83CC1 FOREIGN KEY (state_id) REFERENCES State (id)",
            "ALTER TABLE Task ADD CONSTRAINT FK_F24C741B7D6EFC3 FOREIGN KEY (tasktype_id) REFERENCES TaskType (id)",
            "ALTER TABLE Task ADD CONSTRAINT FK_F24C741BE43FFED1 FOREIGN KEY (timePeriod_id) REFERENCES TimePeriod (id)"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE Task (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                state_id INT NOT NULL,
                tasktype_id INT NOT NULL,
                url LONGTEXT NOT NULL,
                timePeriod_id INT DEFAULT NULL,
                FOREIGN KEY(state_id) REFERENCES State (id),
                FOREIGN KEY(tasktype_id) REFERENCES TaskType (id),
                FOREIGN KEY(timePeriod_id) REFERENCES TimePeriod (id))",
            "CREATE INDEX IDX_F24C741B5D83CC1 ON Task (state_id)",
            "CREATE INDEX IDX_F24C741B7D6EFC3 ON Task (tasktype_id)",
            "CREATE INDEX IDX_F24C741BE43FFED1 ON Task (timePeriod_id)"
        ); 
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {     
        $this->addCommonStatement("DROP TABLE Task");      
        
        parent::down($schema);
    }
}
