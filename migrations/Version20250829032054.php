<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250829032054 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE genre (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_835033F85E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("INSERT INTO genre (name) VALUES ('Science Fiction'), ('Fantasy'), ('Mystery'), ('Romance'), ('Horror'), ('Non-Fiction');");
        $this->addSql('ALTER TABLE book ADD genre_id INT NOT NULL, DROP genre');
        $this->addSql("UPDATE book SET genre_id = (SELECT id FROM genre WHERE name = 'Science Fiction')");
        $this->addSql('ALTER TABLE book ADD CONSTRAINT FK_CBE5A3314296D31F FOREIGN KEY (genre_id) REFERENCES genre (id)');
        $this->addSql('CREATE INDEX IDX_CBE5A3314296D31F ON book (genre_id)');
        $this->addSql('ALTER TABLE club ADD genre_id INT NOT NULL, DROP genre');
        $this->addSql('ALTER TABLE club ADD CONSTRAINT FK_B8EE38724296D31F FOREIGN KEY (genre_id) REFERENCES genre (id)');
        $this->addSql('CREATE INDEX IDX_B8EE38724296D31F ON club (genre_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book DROP FOREIGN KEY FK_CBE5A3314296D31F');
        $this->addSql('ALTER TABLE club DROP FOREIGN KEY FK_B8EE38724296D31F');
        $this->addSql('DROP TABLE genre');
        $this->addSql('DROP INDEX IDX_B8EE38724296D31F ON club');
        $this->addSql('ALTER TABLE club ADD genre VARCHAR(100) NOT NULL, DROP genre_id');
        $this->addSql('DROP INDEX IDX_CBE5A3314296D31F ON book');
        $this->addSql('ALTER TABLE book ADD genre VARCHAR(100) NOT NULL, DROP genre_id');
    }
}
