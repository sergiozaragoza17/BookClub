<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250831020731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE club_post (id INT AUTO_INCREMENT NOT NULL, club_id INT NOT NULL, user_id INT NOT NULL, parent_id INT DEFAULT NULL, content LONGTEXT NOT NULL, created DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_3E42A66D61190A32 (club_id), INDEX IDX_3E42A66DA76ED395 (user_id), INDEX IDX_3E42A66D727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE club_post ADD CONSTRAINT FK_3E42A66D61190A32 FOREIGN KEY (club_id) REFERENCES club (id)');
        $this->addSql('ALTER TABLE club_post ADD CONSTRAINT FK_3E42A66DA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE club_post ADD CONSTRAINT FK_3E42A66D727ACA70 FOREIGN KEY (parent_id) REFERENCES club_post (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE club_post DROP FOREIGN KEY FK_3E42A66D61190A32');
        $this->addSql('ALTER TABLE club_post DROP FOREIGN KEY FK_3E42A66DA76ED395');
        $this->addSql('ALTER TABLE club_post DROP FOREIGN KEY FK_3E42A66D727ACA70');
        $this->addSql('DROP TABLE club_post');
    }
}
