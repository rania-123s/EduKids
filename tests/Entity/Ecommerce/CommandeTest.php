<?php

namespace App\Tests\Entity\Ecommerce;

use App\Entity\Ecommerce\Commande;
use App\Entity\Ecommerce\LigneCommande;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class CommandeTest extends TestCase
{
    public function testConstructorDefaultsAndLigneRelation(): void
    {
        $commande = new Commande();

        self::assertInstanceOf(\DateTimeImmutable::class, $commande->getDate());
        self::assertCount(0, $commande->getLigneCommandes());

        $commande->setUser(new User())->setStatut(Commande::STATUT_EN_ATTENTE);
        self::assertSame(Commande::STATUT_EN_ATTENTE, $commande->getStatut());

        $ligne = (new LigneCommande())->setQuantite(2)->setPrixUnitaire(120);
        $commande->addLigneCommande($ligne);

        self::assertCount(1, $commande->getLigneCommandes());
        self::assertSame($commande, $ligne->getCommande());

        $commande->removeLigneCommande($ligne);
        self::assertCount(0, $commande->getLigneCommandes());
        self::assertNull($ligne->getCommande());
    }

    public function testRecalculerMontantTotalUsesOrderLines(): void
    {
        $commande = new Commande();

        $l1 = (new LigneCommande())->setQuantite(2)->setPrixUnitaire(100);
        $l2 = (new LigneCommande())->setQuantite(3)->setPrixUnitaire(50);
        $commande->addLigneCommande($l1)->addLigneCommande($l2);

        $commande->recalculerMontantTotal();
        self::assertSame(350, $commande->getMontantTotal());
    }
}

