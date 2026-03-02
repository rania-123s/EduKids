<?php

namespace App\Tests\Entity\Ecommerce;

use App\Entity\Ecommerce\LigneCommande;
use PHPUnit\Framework\TestCase;

final class LigneCommandeTest extends TestCase
{
    public function testSousTotalComputation(): void
    {
        $ligne = new LigneCommande();
        self::assertSame(0, $ligne->getSousTotal());

        $ligne->setQuantite(4)->setPrixUnitaire(35);
        self::assertSame(140, $ligne->getSousTotal());
    }
}

