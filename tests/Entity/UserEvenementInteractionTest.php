<?php

namespace App\Tests\Entity;

use App\Entity\Evenement;
use App\Entity\User;
use App\Entity\UserEvenementInteraction;
use PHPUnit\Framework\TestCase;

final class UserEvenementInteractionTest extends TestCase
{
    public function testConstructorDefaultsAndSetters(): void
    {
        $interaction = new UserEvenementInteraction();

        self::assertInstanceOf(\DateTimeImmutable::class, $interaction->getCreatedAt());

        $interaction
            ->setUser(new User())
            ->setEvenement(new Evenement())
            ->setTypeInteraction(UserEvenementInteraction::TYPE_FAVORITE);

        self::assertSame(UserEvenementInteraction::TYPE_FAVORITE, $interaction->getTypeInteraction());
        self::assertInstanceOf(User::class, $interaction->getUser());
        self::assertInstanceOf(Evenement::class, $interaction->getEvenement());
    }
}

