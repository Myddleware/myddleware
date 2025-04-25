<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230501000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create two_factor_auth table for two-factor authentication';
    }

    public function up(Schema $schema): void
    {
        // Create two_factor_auth table
        $this->addSql('CREATE TABLE two_factor_auth (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            enabled TINYINT(1) NOT NULL,
            verification_code VARCHAR(10) DEFAULT NULL,
            code_expires_at DATETIME DEFAULT NULL,
            phone_number VARCHAR(255) DEFAULT NULL,
            preferred_method VARCHAR(10) DEFAULT NULL,
            failed_attempts INT DEFAULT NULL,
            blocked_until DATETIME DEFAULT NULL,
            remember_device TINYINT(1) NOT NULL,
            remember_token VARCHAR(255) DEFAULT NULL,
            UNIQUE INDEX UNIQ_F8A1A73CA76ED395 (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Add foreign key constraint
        $this->addSql('ALTER TABLE two_factor_auth ADD CONSTRAINT FK_F8A1A73CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // Drop the table
        $this->addSql('DROP TABLE two_factor_auth');
    }
} 