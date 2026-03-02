<?php

namespace App\Tests\Entity;

use App\Entity\Chat;
use PHPUnit\Framework\TestCase;

final class ChatTest extends TestCase
{
    public function testBasicSettersAndDefaultMessagesCollection(): void
    {
        $chat = new Chat();
        $createdAt = new \DateTime('2026-03-01 10:00:00');
        $lastMessageAt = new \DateTime('2026-03-01 10:30:00');

        self::assertCount(0, $chat->getMessages());

        $chat
            ->setParentId(5)
            ->setDateCreation($createdAt)
            ->setDernierMessage('Bonjour')
            ->setDateDernierMessage($lastMessageAt);

        self::assertSame(5, $chat->getParentId());
        self::assertSame($createdAt, $chat->getDateCreation());
        self::assertSame('Bonjour', $chat->getDernierMessage());
        self::assertSame($lastMessageAt, $chat->getDateDernierMessage());
    }
}

