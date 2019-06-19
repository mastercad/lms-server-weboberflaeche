<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190530171550 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Insert initial Values';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('INSERT INTO `media_types` (`id`, `name`) VALUES (NULL, \'File\'), (NULL, \'Playlist\'), (NULL, \'Folder\')');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('DELETE `media_types` FROM `media_types` WHERE name = \'File\' OR name = \'Playlist\' OR name = \'Folder\'');
    }
}
