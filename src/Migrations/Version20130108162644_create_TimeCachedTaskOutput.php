<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130108162644_create_TimeCachedTaskOutput extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
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
            ],
            'down' => [
                "DROP TABLE TimeCachedTaskOutput"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE TimeCachedTaskOutput (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    hash VARCHAR(255) NOT NULL,
                    output LONGTEXT NOT NULL,
                    errorCount INT NOT NULL,
                    warningCount INT NOT NULL,
                    maxAge INT NOT NULL,
                    lastModified DATETIME NOT NULL)"
            ],
            'down' => [
                "DROP TABLE TimeCachedTaskOutput"
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
