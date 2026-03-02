<?php

namespace App\Tests\Entity;

use App\Entity\Commande;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class CommandeEntityTest extends TestCase
{
    public function testBasicSettersAndGetters(): void
    {
        $user = (new User())->setEmail('parent@example.com');
        $date = new \DateTime('2026-03-01 12:00:00');

        $commande = new Commande();
        $commande
            ->setUserId($user)
            ->setDate($date)
            ->setMontantTotal(150)
            ->setStatut('paye');

        self::assertSame($user, $commande->getUserId());
        self::assertSame($date, $commande->getDate());
        self::assertSame(150, $commande->getMontantTotal());
        self::assertSame('paye', $commande->getStatut());
    }
}

