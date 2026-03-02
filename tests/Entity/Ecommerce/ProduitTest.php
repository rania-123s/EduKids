<?php

namespace App\Tests\Entity\Ecommerce;

use App\Entity\Ecommerce\LigneCommande;
use App\Entity\Ecommerce\Produit;
use App\Entity\Ecommerce\Review;
use PHPUnit\Framework\TestCase;

final class ProduitTest extends TestCase
{
    public function testCollectionsAndRelationSynchronization(): void
    {
        $produit = new Produit();

        self::assertCount(0, $produit->getLigneCommandes());
        self::assertCount(0, $produit->getReviews());

        $ligne = new LigneCommande();
        $review = new Review();

        $produit->addLigneCommande($ligne)->addReview($review);

        self::assertCount(1, $produit->getLigneCommandes());
        self::assertCount(1, $produit->getReviews());
        self::assertSame($produit, $ligne->getProduit());
        self::assertSame($produit, $review->getProduit());

        $produit->removeLigneCommande($ligne)->removeReview($review);

        self::assertCount(0, $produit->getLigneCommandes());
        self::assertCount(0, $produit->getReviews());
        self::assertNull($ligne->getProduit());
        self::assertNull($review->getProduit());
    }
}

