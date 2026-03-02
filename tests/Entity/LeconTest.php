<?php

namespace App\Tests\Entity;

use App\Entity\Cours;
use App\Entity\Lecon;
use PHPUnit\Framework\TestCase;

final class LeconTest extends TestCase
{
    public function testBasicGettersAndSetters(): void
    {
        $cours = new Cours();
        $lecon = new Lecon();

        $lecon
            ->setCours($cours)
            ->setTitre('Introduction Symfony')
            ->setOrdre(2)
            ->setMediaType('pdf_video')
            ->setMediaUrl('/uploads/lecons/intro.pdf')
            ->setVideoUrl('https://cdn.example.com/video.mp4')
            ->setYoutubeUrl('https://youtube.com/watch?v=abc')
            ->setImage('intro.jpg');

        self::assertSame($cours, $lecon->getCours());
        self::assertSame('Introduction Symfony', $lecon->getTitre());
        self::assertSame(2, $lecon->getOrdre());
        self::assertSame('pdf_video', $lecon->getMediaType());
        self::assertSame('/uploads/lecons/intro.pdf', $lecon->getMediaUrl());
        self::assertSame('https://cdn.example.com/video.mp4', $lecon->getVideoUrl());
        self::assertSame('https://youtube.com/watch?v=abc', $lecon->getYoutubeUrl());
        self::assertSame('intro.jpg', $lecon->getImage());
    }

    public function testNullableMediaFieldsCanBeCleared(): void
    {
        $lecon = new Lecon();

        $lecon
            ->setVideoUrl(null)
            ->setYoutubeUrl(null)
            ->setImage(null)
            ->setCours(null);

        self::assertNull($lecon->getVideoUrl());
        self::assertNull($lecon->getYoutubeUrl());
        self::assertNull($lecon->getImage());
        self::assertNull($lecon->getCours());
    }
}
