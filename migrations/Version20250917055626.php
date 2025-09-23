<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250917055626 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD fav_book_id INT DEFAULT NULL, ADD currently_reading_id INT DEFAULT NULL, DROP fav_book, DROP currently_reading');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64969B2B513 FOREIGN KEY (fav_book_id) REFERENCES book (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649DD8A7669 FOREIGN KEY (currently_reading_id) REFERENCES book (id)');
        $this->addSql('CREATE INDEX IDX_8D93D64969B2B513 ON user (fav_book_id)');
        $this->addSql('CREATE INDEX IDX_8D93D649DD8A7669 ON user (currently_reading_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE club_book DROP FOREIGN KEY FK_AF2D69D116A2B381');
        $this->addSql('DROP INDEX IDX_AF2D69D116A2B381 ON club_book');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D64969B2B513');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649DD8A7669');
        $this->addSql('DROP INDEX IDX_8D93D64969B2B513 ON `user`');
        $this->addSql('DROP INDEX IDX_8D93D649DD8A7669 ON `user`');
        $this->addSql('ALTER TABLE `user` ADD fav_book VARCHAR(255) DEFAULT NULL, ADD currently_reading VARCHAR(255) DEFAULT NULL, DROP fav_book_id, DROP currently_reading_id');
    }
}
