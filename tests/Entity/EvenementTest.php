<?php

namespace App\Tests\Entity;

use App\Entity\Evenement;
use App\Entity\Programme;
use App\Entity\Reservation;
use PHPUnit\Framework\TestCase;

final class EvenementTest extends TestCase
{
    public function testDureeAndDureeFormateeVariants(): void
    {
        $evenement = new Evenement();

        self::assertNull($evenement->getDuree());
        self::assertSame('-', $evenement->getDureeFormatee());

        $evenement
            ->setHeureDebut(new \DateTimeImmutable('09:00'))
            ->setHeureFin(new \DateTimeImmutable('11:30'));

        self::assertSame(150, $evenement->getDuree());
        self::assertSame('2h30', $evenement->getDureeFormatee());

        $evenement->setHeureFin(new \DateTimeImmutable('11:00'));
        self::assertSame('2h', $evenement->getDureeFormatee());

        $evenement
            ->setHeureDebut(new \DateTimeImmutable('09:15'))
            ->setHeureFin(new \DateTimeImmutable('09:45'));

        self::assertSame('30min', $evenement->getDureeFormatee());
    }

    public function testTypeLabelAndStringRepresentation(): void
    {
        $evenement = new Evenement();

        $evenement->setTypeEvenement('science');
        self::assertSame('Science', $evenement->getTypeEvenementLabel());

        $evenement->setTypeEvenement('custom');
        self::assertSame('custom', $evenement->getTypeEvenementLabel());

        $evenement->setTypeEvenement(null);
        self::assertSame('', $evenement->getTypeEvenementLabel());

        self::assertSame('', (string) $evenement);
        $evenement->setTitre('Forum Jeunesse');
        self::assertSame('Forum Jeunesse', (string) $evenement);
    }

    public function testCountersDoNotGoBelowZero(): void
    {
        $evenement = new Evenement();

        self::assertSame(0, $evenement->getLikesCount());
        self::assertSame(0, $evenement->getDislikesCount());
        self::assertSame(0, $evenement->getFavoritesCount());

        $evenement->decrementLikes()->decrementDislikes()->decrementFavorites();
        self::assertSame(0, $evenement->getLikesCount());
        self::assertSame(0, $evenement->getDislikesCount());
        self::assertSame(0, $evenement->getFavoritesCount());

        $evenement
            ->incrementLikes()
            ->incrementDislikes()
            ->incrementFavorites()
            ->decrementLikes()
            ->decrementDislikes()
            ->decrementFavorites();

        self::assertSame(0, $evenement->getLikesCount());
        self::assertSame(0, $evenement->getDislikesCount());
        self::assertSame(0, $evenement->getFavoritesCount());
    }

    public function testProgrammeRelationIsBidirectional(): void
    {
        $evenement = new Evenement();
        $programme = new Programme();

        self::assertFalse($evenement->hasProgramme());
        self::assertNull($programme->getEvenement());

        $evenement->setProgramme($programme);

        self::assertTrue($evenement->hasProgramme());
        self::assertSame($programme, $evenement->getProgramme());
        self::assertSame($evenement, $programme->getEvenement());

        $evenement->setProgramme(null);

        self::assertFalse($evenement->hasProgramme());
        self::assertNull($evenement->getProgramme());
        self::assertNull($programme->getEvenement());
    }

    public function testReservationsAndPlacesCalculations(): void
    {
        $evenement = new Evenement();

        self::assertCount(0, $evenement->getReservations());
        self::assertSame(0, $evenement->getNbPlacesReservees());
        self::assertTrue($evenement->hasPlacesDisponibles());
        self::assertNull($evenement->getNbPlacesRestantes());

        $r1 = (new Reservation())->setNbAdultes(2)->setNbEnfants(1);
        $r2 = (new Reservation())->setNbAdultes(1)->setNbEnfants(0);

        $evenement->addReservation($r1)->addReservation($r2);

        self::assertCount(2, $evenement->getReservations());
        self::assertSame($evenement, $r1->getEvenement());
        self::assertSame($evenement, $r2->getEvenement());
        self::assertSame(4, $evenement->getNbPlacesReservees());

        $evenement->setNbPlacesDisponibles(5);
        self::assertTrue($evenement->hasPlacesDisponibles());
        self::assertSame(1, $evenement->getNbPlacesRestantes());

        $r3 = (new Reservation())->setNbAdultes(2)->setNbEnfants(0);
        $evenement->addReservation($r3);

        self::assertSame(6, $evenement->getNbPlacesReservees());
        self::assertFalse($evenement->hasPlacesDisponibles());
        self::assertSame(0, $evenement->getNbPlacesRestantes());

        $evenement->removeReservation($r3);
        self::assertSame(4, $evenement->getNbPlacesReservees());
        self::assertNull($r3->getEvenement());
    }
}
