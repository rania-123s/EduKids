<?php

namespace App\Tests\Entity;

use App\Entity\Activite;
use App\Entity\Event;
use PHPUnit\Framework\TestCase;

final class EventTest extends TestCase
{
    public function testBasicSettersAndActiviteRelation(): void
    {
        $event = new Event();
        $dateHeure = new \DateTime('2026-04-02 14:00:00');
        $activite = new Activite();

        $event
            ->setTitre('Forum Sciences')
            ->setDescription('Presentation des projets eleves')
            ->setDateHeure($dateHeure)
            ->addActivite($activite);

        self::assertSame('Forum Sciences', $event->getTitre());
        self::assertSame('Presentation des projets eleves', $event->getDescription());
        self::assertSame($dateHeure, $event->getDateHeure());
        self::assertCount(1, $event->getActivites());
        self::assertSame($event, $activite->getEvent());

        $event->removeActivite($activite);
        self::assertCount(0, $event->getActivites());
        self::assertNull($activite->getEvent());
    }
}

