<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120731224355_create_CoreApplication extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE CoreApplication (id INT AUTO_INCREMENT NOT NULL, url VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_F5051811F47645AE (url), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB"
            ],
            'down' => [
                "DROP TABLE CoreApplication"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE CoreApplication (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, url VARCHAR(255) NOT NULL COLLATE NOCASE)",
                "CREATE UNIQUE INDEX UNIQ_F5051811F47645AE ON CoreApplication (url)"
            ],
            'down' => [
                "DROP TABLE CoreApplication"
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
