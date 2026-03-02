<?php

namespace App\Tests\Entity;

use App\Entity\MessageAttachment;
use PHPUnit\Framework\TestCase;

final class MessageAttachmentTest extends TestCase
{
    public function testSizeAndDurationAreClampedToNonNegativeValues(): void
    {
        $attachment = new MessageAttachment();

        $attachment->setSize(-50);
        self::assertSame(0, $attachment->getSize());

        $attachment->setSize(2048);
        self::assertSame(2048, $attachment->getSize());

        $attachment->setDuration(-12);
        self::assertSame(0, $attachment->getDuration());

        $attachment->setDuration(37);
        self::assertSame(37, $attachment->getDuration());

        $attachment->setDuration(null);
        self::assertNull($attachment->getDuration());
    }

    public function testOnPrePersistSetsCreatedAt(): void
    {
        $attachment = new MessageAttachment();
        self::assertNull($attachment->getCreatedAt());

        $attachment->onPrePersist();
        $createdAt = $attachment->getCreatedAt();

        self::assertInstanceOf(\DateTimeImmutable::class, $createdAt);

        $attachment->onPrePersist();
        self::assertSame($createdAt, $attachment->getCreatedAt());
    }
}
