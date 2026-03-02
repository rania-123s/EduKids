<?php

namespace App\Tests\Entity\Ecommerce;

use App\Entity\Ecommerce\Review;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class ReviewTest extends TestCase
{
    public function testConstructorDefaultsAndSetters(): void
    {
        $review = new Review();

        self::assertInstanceOf(\DateTimeImmutable::class, $review->getCreatedAt());
        self::assertSame(Review::STATUS_PENDING, $review->getStatus());

        $review
            ->setUser(new User())
            ->setRating(5)
            ->setComment('Excellent produit')
            ->setStatus(Review::STATUS_APPROVED);

        self::assertSame(5, $review->getRating());
        self::assertSame('Excellent produit', $review->getComment());
        self::assertSame(Review::STATUS_APPROVED, $review->getStatus());
    }
}

