<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120809165432_create_TaskOutput extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE TaskOutput (
                    id INT AUTO_INCREMENT NOT NULL,
                    state_id INT NOT NULL,
                    output LONGTEXT DEFAULT NULL,
                    contentType LONGTEXT DEFAULT NULL,
                    errorCount INT NOT NULL,
                    INDEX IDX_C9B3E5C45D83CC1 (state_id),
                    PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB",
                "ALTER TABLE TaskOutput ADD CONSTRAINT FK_C9B3E5C45D83CC1 FOREIGN KEY (state_id) REFERENCES State (id)"
            ],
            'down' => [
                "DROP TABLE TaskOutput"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE TaskOutput (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    state_id INT NOT NULL,
                    output LONGTEXT DEFAULT NULL COLLATE NOCASE,
                    contentType LONGTEXT DEFAULT NULL COLLATE NOCASE,
                    errorCount INT NOT NULL,
                    FOREIGN KEY(state_id) REFERENCES State (id))",
                "CREATE INDEX IDX_C9B3E5C45D83CC1 ON TaskOutput (state_id)"
            ],
            'down' => [
                "DROP TABLE TaskOutput"
            ]
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
