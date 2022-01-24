<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220124013049 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE cache_api_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE character_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE film_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE cache_api (id INT NOT NULL, path VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE character (id INT NOT NULL, name VARCHAR(255) NOT NULL, gender VARCHAR(255) NOT NULL, specie VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE character_film (character_id INT NOT NULL, film_id INT NOT NULL, PRIMARY KEY(character_id, film_id))');
        $this->addSql('CREATE INDEX IDX_95B8AABA1136BE75 ON character_film (character_id)');
        $this->addSql('CREATE INDEX IDX_95B8AABA567F5183 ON character_film (film_id)');
        $this->addSql('CREATE TABLE film (id INT NOT NULL, director VARCHAR(255) NOT NULL, release_date DATE NOT NULL, title VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE character_film ADD CONSTRAINT FK_95B8AABA1136BE75 FOREIGN KEY (character_id) REFERENCES character (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE character_film ADD CONSTRAINT FK_95B8AABA567F5183 FOREIGN KEY (film_id) REFERENCES film (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE character_film DROP CONSTRAINT FK_95B8AABA1136BE75');
        $this->addSql('ALTER TABLE character_film DROP CONSTRAINT FK_95B8AABA567F5183');
        $this->addSql('DROP SEQUENCE cache_api_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE character_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE film_id_seq CASCADE');
        $this->addSql('DROP TABLE cache_api');
        $this->addSql('DROP TABLE character');
        $this->addSql('DROP TABLE character_film');
        $this->addSql('DROP TABLE film');
    }
}
