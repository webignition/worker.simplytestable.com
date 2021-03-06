<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20121113145729_add_Task_parameters extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "ALTER TABLE Task ADD parameters LONGTEXT DEFAULT NULL"
            ],
            'down' => [
                "ALTER TABLE Task DROP parameters"
            ]
        ],
        'sqlite' => [
            'up' => [
                "ALTER TABLE Task ADD parameters LONGTEXT DEFAULT NULL"
            ],
            'down' => []
        ]
    ];

    public function up(Schema $schema)
    {
        foreach ($this->statements[$this->connection->getDatabasePlatform()->getName()]['up'] as $statement) {
            $this->addSql($statement);
        }
    }

    public function down(Schema $schema)
    {
        foreach ($this->statements[$this->connection->getDatabasePlatform()->getName()]['down'] as $statement) {
            $this->addSql($statement);
        }
    }
}
