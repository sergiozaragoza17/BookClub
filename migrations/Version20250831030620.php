<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250831030620 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE club_book_post (id INT AUTO_INCREMENT NOT NULL, club_id INT NOT NULL, book_id INT NOT NULL, user_id INT NOT NULL, parent_id INT DEFAULT NULL, content LONGTEXT NOT NULL, created DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_EC1E876361190A32 (club_id), INDEX IDX_EC1E876316A2B381 (book_id), INDEX IDX_EC1E8763A76ED395 (user_id), INDEX IDX_EC1E8763727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE club_book_post ADD CONSTRAINT FK_EC1E876361190A32 FOREIGN KEY (club_id) REFERENCES club (id)');
        $this->addSql('ALTER TABLE club_book_post ADD CONSTRAINT FK_EC1E876316A2B381 FOREIGN KEY (book_id) REFERENCES book (id)');
        $this->addSql('ALTER TABLE club_book_post ADD CONSTRAINT FK_EC1E8763A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE club_book_post ADD CONSTRAINT FK_EC1E8763727ACA70 FOREIGN KEY (parent_id) REFERENCES club_book_post (id)');
        $this->addSql('ALTER TABLE club_book DROP FOREIGN KEY FK_AF2D69D116A2B381');
        $this->addSql('DROP INDEX IDX_AF2D69D116A2B381 ON club_book');
        $this->addSql('ALTER TABLE club_book DROP book_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE club_book_post DROP FOREIGN KEY FK_EC1E876361190A32');
        $this->addSql('ALTER TABLE club_book_post DROP FOREIGN KEY FK_EC1E876316A2B381');
        $this->addSql('ALTER TABLE club_book_post DROP FOREIGN KEY FK_EC1E8763A76ED395');
        $this->addSql('ALTER TABLE club_book_post DROP FOREIGN KEY FK_EC1E8763727ACA70');
        $this->addSql('DROP TABLE club_book_post');
        $this->addSql('ALTER TABLE club_book ADD book_id INT NOT NULL');
        $this->addSql('ALTER TABLE club_book ADD CONSTRAINT FK_AF2D69D116A2B381 FOREIGN KEY (book_id) REFERENCES book (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_AF2D69D116A2B381 ON club_book (book_id)');
    }
}
