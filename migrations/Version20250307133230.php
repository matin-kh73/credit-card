<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250307133229 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create all tables with updated schema';
    }

    public function up(Schema $schema): void
    {
        // Drop existing tables if they exist
        $this->addSql('DROP TABLE IF EXISTS credit_card_edits');
        $this->addSql('DROP TABLE IF EXISTS credit_cards');
        $this->addSql('DROP TABLE IF EXISTS banks');

        // Create banks table
        $this->addSql('CREATE TABLE banks (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            code VARCHAR(255) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create credit_cards table
        $this->addSql('CREATE TABLE credit_cards (
            id INT AUTO_INCREMENT NOT NULL,
            bank_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            external_id INT NOT NULL,
            information LONGTEXT NOT NULL,
            image_url VARCHAR(255) DEFAULT NULL,
            website VARCHAR(255) DEFAULT NULL,
            annual_equivalent_rate DOUBLE NOT NULL,
            first_year_fee DOUBLE NOT NULL,
            annual_charges DOUBLE NOT NULL,
            has_reward_program TINYINT(1) NOT NULL,
            has_insurance TINYINT(1) NOT NULL,
            rating DOUBLE NOT NULL,
            card_type VARCHAR(10) NOT NULL COMMENT \'(DC2Type:CardTypeEnum)\',
            is_active TINYINT(1) NOT NULL,
            atm_free_domestic TINYINT(1) NOT NULL,
            provider INT DEFAULT NULL,
            incentive_amount DOUBLE NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_7B8B4C4B9F75D7B0 (external_id),
            INDEX IDX_7B8B4C4B11C8FB41 (bank_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create credit_card_edits table
        $this->addSql('CREATE TABLE credit_card_edits (
            id INT AUTO_INCREMENT NOT NULL,
            credit_card_id INT NOT NULL,
            user_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            description LONGTEXT NOT NULL,
            annual_charges DOUBLE NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_4B3B4C4B11C8FB41 (credit_card_id),
            INDEX IDX_4B3B4C4BA76ED395 (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add foreign key constraints
        $this->addSql('ALTER TABLE credit_cards ADD CONSTRAINT FK_7B8B4C4B11C8FB41 FOREIGN KEY (bank_id) REFERENCES banks (id)');
        $this->addSql('ALTER TABLE credit_card_edits ADD CONSTRAINT FK_4B3B4C4B11C8FB41 FOREIGN KEY (credit_card_id) REFERENCES credit_cards (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE credit_card_edits ADD CONSTRAINT FK_4B3B4C4BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE credit_card_edits');
        $this->addSql('DROP TABLE credit_cards');
        $this->addSql('DROP TABLE banks');
    }
}
