<?php

namespace App\Tests\Entity;

use App\Entity\Evenement;
use App\Entity\Ressource;
use PHPUnit\Framework\TestCase;

final class RessourceTest extends TestCase
{
    public function testSettersToStringAndNullableFields(): void
    {
        $ressource = new Ressource();
        $dateDebut = new \DateTimeImmutable('2026-03-10 09:00:00');
        $dateFin = new \DateTimeImmutable('2026-03-10 10:00:00');

        self::assertSame('', (string) $ressource);

        $ressource
            ->setEvenement(new Evenement())
            ->setTypeRessource('document')
            ->setNom('Planning atelier')
            ->setDescription('Document de preparation')
            ->setDateDebut($dateDebut)
            ->setDateFin($dateFin)
            ->setFichier('planning.pdf')
            ->setQuantite(2);

        self::assertSame('Planning atelier', $ressource->getNom());
        self::assertSame('document', $ressource->getTypeRessource());
        self::assertSame('Document de preparation', $ressource->getDescription());
        self::assertSame($dateDebut, $ressource->getDateDebut());
        self::assertSame($dateFin, $ressource->getDateFin());
        self::assertSame('planning.pdf', $ressource->getFichier());
        self::assertSame(2, $ressource->getQuantite());
        self::assertSame('Planning atelier', (string) $ressource);
    }
}

