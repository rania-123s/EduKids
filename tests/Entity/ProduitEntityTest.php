<?php

namespace App\Tests\Entity;

use App\Entity\Produit;
use PHPUnit\Framework\TestCase;

final class ProduitEntityTest extends TestCase
{
    public function testBasicSettersAndGetters(): void
    {
        $produit = new Produit();
        $produit
            ->setNom('Sac scolaire')
            ->setDescription('Sac robuste pour ecole')
            ->setPrix(89)
            ->setType('materiel');

        self::assertSame('Sac scolaire', $produit->getNom());
        self::assertSame('Sac robuste pour ecole', $produit->getDescription());
        self::assertSame(89, $produit->getPrix());
        self::assertSame('materiel', $produit->getType());
    }
}

