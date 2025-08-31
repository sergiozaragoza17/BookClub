<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250831032239 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE club_book ADD book_id INT NOT NULL');
        $this->addSql('ALTER TABLE club_book ADD CONSTRAINT FK_AF2D69D116A2B381 FOREIGN KEY (book_id) REFERENCES book (id)');
        $this->addSql('CREATE INDEX IDX_AF2D69D116A2B381 ON club_book (book_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE club_book DROP FOREIGN KEY FK_AF2D69D116A2B381');
        $this->addSql('DROP INDEX IDX_AF2D69D116A2B381 ON club_book');
        $this->addSql('ALTER TABLE club_book DROP book_id');
    }
}
