<?php

namespace App\Tests\Entity;

use App\Entity\Evenement;
use App\Entity\Reservation;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class ReservationTest extends TestCase
{
    public function testConstructorAndTotalPlacesCalculation(): void
    {
        $reservation = new Reservation();

        self::assertInstanceOf(\DateTimeInterface::class, $reservation->getDateReservation());
        self::assertSame(0, $reservation->getNbPlacesTotal());

        $reservation
            ->setUser(new User())
            ->setEvenement(new Evenement())
            ->setNom('Doe')
            ->setPrenom('Jane')
            ->setEmail('jane@example.com')
            ->setTelephone('123456')
            ->setNbAdultes(2)
            ->setNbEnfants(3);

        self::assertSame(5, $reservation->getNbPlacesTotal());
        self::assertSame('Doe', $reservation->getNom());
        self::assertSame('Jane', $reservation->getPrenom());
        self::assertSame('jane@example.com', $reservation->getEmail());
        self::assertSame('123456', $reservation->getTelephone());
    }
}

