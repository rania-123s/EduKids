<?php

namespace App\Tests\Entity;

use App\Entity\Notification;
use PHPUnit\Framework\TestCase;

final class NotificationTest extends TestCase
{
    public function testTypeTextAndConversationIdNormalization(): void
    {
        $notification = new Notification();

        $notification->setType('   ');
        self::assertSame(Notification::TYPE_MESSAGE, $notification->getType());

        $notification->setType('custom');
        self::assertSame('custom', $notification->getType());

        $notification->setText('  Nouveau message  ');
        self::assertSame('Nouveau message', $notification->getText());

        $notification->setConversationId(-10);
        self::assertSame(0, $notification->getConversationId());

        $notification->setConversationId(42);
        self::assertSame(42, $notification->getConversationId());

        $notification->setConversationId(null);
        self::assertNull($notification->getConversationId());
    }

    public function testOnPrePersistInitializesCreatedAtOnce(): void
    {
        $notification = new Notification();
        self::assertNull($notification->getCreatedAt());

        $notification->onPrePersist();
        $createdAt = $notification->getCreatedAt();
        self::assertInstanceOf(\DateTimeImmutable::class, $createdAt);

        $notification->onPrePersist();
        self::assertSame($createdAt, $notification->getCreatedAt());
    }

    public function testReadFlagCanBeToggled(): void
    {
        $notification = new Notification();

        self::assertFalse($notification->isRead());
        $notification->setIsRead(true);
        self::assertTrue($notification->isRead());
    }
}
