<?php

namespace App\Tests\Entity\Ecommerce;

use App\Entity\Ecommerce\CategoryProduit;
use PHPUnit\Framework\TestCase;

final class CategoryProduitTest extends TestCase
{
    public function testDefaultCollectionAndStringRepresentation(): void
    {
        $category = new CategoryProduit();

        self::assertCount(0, $category->getProduits());
        self::assertSame('', (string) $category);

        $category
            ->setNom('Papeterie')
            ->setDescription('Articles scolaires');

        self::assertSame('Papeterie', $category->getNom());
        self::assertSame('Articles scolaires', $category->getDescription());
        self::assertSame('Papeterie', (string) $category);
    }
}

