<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250506212326 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE account (id CHAR(36) NOT NULL, code VARCHAR(16) NOT NULL, name VARCHAR(100) NOT NULL, description CLOB DEFAULT NULL, is_active BOOLEAN NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7D3656A477153098 ON account (code)');
        $this->addSql('CREATE TABLE event (id CHAR(36) NOT NULL, residence VARCHAR(36) NOT NULL, event_type VARCHAR(50) NOT NULL, payload CLOB DEFAULT NULL, occurred_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE expense (id CHAR(36) NOT NULL, amount INTEGER NOT NULL, description VARCHAR(255) DEFAULT NULL, due_date DATETIME NOT NULL, paid_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, account_id CHAR(36) DEFAULT NULL, type_id INTEGER DEFAULT NULL, PRIMARY KEY(id), CONSTRAINT FK_2D3A8DA69B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_2D3A8DA6C54C8C93 FOREIGN KEY (type_id) REFERENCES expense_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_2D3A8DA69B6B5FBA ON expense (account_id)');
        $this->addSql('CREATE INDEX IDX_2D3A8DA6C54C8C93 ON expense (type_id)');
        $this->addSql('CREATE TABLE expense_type (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(6) NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL)');
        $this->addSql('CREATE TABLE income (id CHAR(36) NOT NULL, amount INTEGER NOT NULL, description VARCHAR(255) DEFAULT NULL, due_date DATETIME NOT NULL, paid_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, type_id INTEGER DEFAULT NULL, residence_id CHAR(36) NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_3FA862D0C54C8C93 FOREIGN KEY (type_id) REFERENCES income_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_3FA862D08B225FBD FOREIGN KEY (residence_id) REFERENCES resident (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_3FA862D0C54C8C93 ON income (type_id)');
        $this->addSql('CREATE INDEX IDX_3FA862D08B225FBD ON income (residence_id)');
        $this->addSql('CREATE TABLE income_type (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(6) NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL)');
        $this->addSql('CREATE TABLE resident (id CHAR(36) NOT NULL, unit VARCHAR(10) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE slip (id CHAR(36) NOT NULL, amount INTEGER NOT NULL, status VARCHAR(25) DEFAULT NULL, created_at DATETIME NOT NULL, residence_id CHAR(36) DEFAULT NULL, PRIMARY KEY(id), CONSTRAINT FK_FD3943F88B225FBD FOREIGN KEY (residence_id) REFERENCES resident (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_FD3943F88B225FBD ON slip (residence_id)');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, full_name VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, roles CLOB NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON user (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
        $this->addSql('DROP TABLE symfony_demo_user');
        $this->addSql('CREATE TEMPORARY TABLE __temp__symfony_demo_comment AS SELECT id, post_id, author_id, content, published_at FROM symfony_demo_comment');
        $this->addSql('DROP TABLE symfony_demo_comment');
        $this->addSql('CREATE TABLE symfony_demo_comment (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, post_id INTEGER NOT NULL, author_id INTEGER NOT NULL, content CLOB NOT NULL, published_at DATETIME NOT NULL, CONSTRAINT FK_53AD8F834B89032C FOREIGN KEY (post_id) REFERENCES symfony_demo_post (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_53AD8F83F675F31B FOREIGN KEY (author_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO symfony_demo_comment (id, post_id, author_id, content, published_at) SELECT id, post_id, author_id, content, published_at FROM __temp__symfony_demo_comment');
        $this->addSql('DROP TABLE __temp__symfony_demo_comment');
        $this->addSql('CREATE INDEX IDX_53AD8F83F675F31B ON symfony_demo_comment (author_id)');
        $this->addSql('CREATE INDEX IDX_53AD8F834B89032C ON symfony_demo_comment (post_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__symfony_demo_post AS SELECT id, author_id, title, slug, summary, content, published_at FROM symfony_demo_post');
        $this->addSql('DROP TABLE symfony_demo_post');
        $this->addSql('CREATE TABLE symfony_demo_post (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, author_id INTEGER NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, summary VARCHAR(255) NOT NULL, content CLOB NOT NULL, published_at DATETIME NOT NULL, CONSTRAINT FK_58A92E65F675F31B FOREIGN KEY (author_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO symfony_demo_post (id, author_id, title, slug, summary, content, published_at) SELECT id, author_id, title, slug, summary, content, published_at FROM __temp__symfony_demo_post');
        $this->addSql('DROP TABLE __temp__symfony_demo_post');
        $this->addSql('CREATE INDEX IDX_58A92E65F675F31B ON symfony_demo_post (author_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE symfony_demo_user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, full_name VARCHAR(255) NOT NULL COLLATE "BINARY", username VARCHAR(255) NOT NULL COLLATE "BINARY", email VARCHAR(255) NOT NULL COLLATE "BINARY", password VARCHAR(255) NOT NULL COLLATE "BINARY", roles CLOB NOT NULL COLLATE "BINARY" --(DC2Type:json)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8FB094A1E7927C74 ON symfony_demo_user (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8FB094A1F85E0677 ON symfony_demo_user (username)');
        $this->addSql('DROP TABLE account');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE expense');
        $this->addSql('DROP TABLE expense_type');
        $this->addSql('DROP TABLE income');
        $this->addSql('DROP TABLE income_type');
        $this->addSql('DROP TABLE resident');
        $this->addSql('DROP TABLE slip');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TEMPORARY TABLE __temp__symfony_demo_comment AS SELECT id, content, published_at, post_id, author_id FROM symfony_demo_comment');
        $this->addSql('DROP TABLE symfony_demo_comment');
        $this->addSql('CREATE TABLE symfony_demo_comment (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, content CLOB NOT NULL, published_at DATETIME NOT NULL, post_id INTEGER NOT NULL, author_id INTEGER NOT NULL, CONSTRAINT FK_53AD8F834B89032C FOREIGN KEY (post_id) REFERENCES symfony_demo_post (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_53AD8F83F675F31B FOREIGN KEY (author_id) REFERENCES symfony_demo_user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO symfony_demo_comment (id, content, published_at, post_id, author_id) SELECT id, content, published_at, post_id, author_id FROM __temp__symfony_demo_comment');
        $this->addSql('DROP TABLE __temp__symfony_demo_comment');
        $this->addSql('CREATE INDEX IDX_53AD8F834B89032C ON symfony_demo_comment (post_id)');
        $this->addSql('CREATE INDEX IDX_53AD8F83F675F31B ON symfony_demo_comment (author_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__symfony_demo_post AS SELECT id, title, slug, summary, content, published_at, author_id FROM symfony_demo_post');
        $this->addSql('DROP TABLE symfony_demo_post');
        $this->addSql('CREATE TABLE symfony_demo_post (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, summary VARCHAR(255) NOT NULL, content CLOB NOT NULL, published_at DATETIME NOT NULL, author_id INTEGER NOT NULL, CONSTRAINT FK_58A92E65F675F31B FOREIGN KEY (author_id) REFERENCES symfony_demo_user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO symfony_demo_post (id, title, slug, summary, content, published_at, author_id) SELECT id, title, slug, summary, content, published_at, author_id FROM __temp__symfony_demo_post');
        $this->addSql('DROP TABLE __temp__symfony_demo_post');
        $this->addSql('CREATE INDEX IDX_58A92E65F675F31B ON symfony_demo_post (author_id)');
    }
}
