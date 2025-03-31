<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250318123707 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE offer CHANGE max_people max_people INT NOT NULL');
        $this->addSql('ALTER TABLE ticket_order ADD quantity INT NOT NULL, CHANGE order_key order_key VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket_order ADD CONSTRAINT FK_DD19F013A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ticket_order ADD CONSTRAINT FK_DD19F01353C674EE FOREIGN KEY (offer_id) REFERENCES offer (id)');
        $this->addSql('CREATE INDEX IDX_DD19F013A76ED395 ON ticket_order (user_id)');
        $this->addSql('CREATE INDEX IDX_DD19F01353C674EE ON ticket_order (offer_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket_order DROP FOREIGN KEY FK_DD19F013A76ED395');
        $this->addSql('ALTER TABLE ticket_order DROP FOREIGN KEY FK_DD19F01353C674EE');
        $this->addSql('DROP INDEX IDX_DD19F013A76ED395 ON ticket_order');
        $this->addSql('DROP INDEX IDX_DD19F01353C674EE ON ticket_order');
        $this->addSql('ALTER TABLE ticket_order DROP quantity, CHANGE order_key order_key VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE offer CHANGE max_people max_people DOUBLE PRECISION NOT NULL');
    }
}
