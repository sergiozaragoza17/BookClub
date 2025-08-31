<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250829031343 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE club_book (id INT AUTO_INCREMENT NOT NULL, club_id INT NOT NULL, book_id INT NOT NULL, added_by_id INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_AF2D69D161190A32 (club_id), INDEX IDX_AF2D69D116A2B381 (book_id), INDEX IDX_AF2D69D155B127A4 (added_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE club_book ADD CONSTRAINT FK_AF2D69D161190A32 FOREIGN KEY (club_id) REFERENCES club (id)');
        $this->addSql('ALTER TABLE club_book ADD CONSTRAINT FK_AF2D69D116A2B381 FOREIGN KEY (book_id) REFERENCES book (id)');
        $this->addSql('ALTER TABLE club_book ADD CONSTRAINT FK_AF2D69D155B127A4 FOREIGN KEY (added_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE book ADD genre VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE club ADD genre VARCHAR(100) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE club_book DROP FOREIGN KEY FK_AF2D69D161190A32');
        $this->addSql('ALTER TABLE club_book DROP FOREIGN KEY FK_AF2D69D116A2B381');
        $this->addSql('ALTER TABLE club_book DROP FOREIGN KEY FK_AF2D69D155B127A4');
        $this->addSql('DROP TABLE club_book');
        $this->addSql('ALTER TABLE book DROP genre');
        $this->addSql('ALTER TABLE club DROP genre');
    }
}
