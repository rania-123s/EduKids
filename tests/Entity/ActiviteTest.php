<?php

namespace App\Tests\Entity;

use App\Entity\Activite;
use App\Entity\Event;
use PHPUnit\Framework\TestCase;

final class ActiviteTest extends TestCase
{
    public function testBasicSettersAndEventRelation(): void
    {
        $event = new Event();
        $activite = new Activite();

        $activite
            ->setNomActivite('Atelier Robotique')
            ->setLieu('Salle A')
            ->setDescription('Initiation a la robotique pour enfants.')
            ->setEvent($event);

        self::assertSame('Atelier Robotique', $activite->getNomActivite());
        self::assertSame('Salle A', $activite->getLieu());
        self::assertSame('Initiation a la robotique pour enfants.', $activite->getDescription());
        self::assertSame($event, $activite->getEvent());
    }
}

