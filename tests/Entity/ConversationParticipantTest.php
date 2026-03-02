<?php

namespace App\Tests\Entity;

use App\Entity\ConversationParticipant;
use PHPUnit\Framework\TestCase;

final class ConversationParticipantTest extends TestCase
{
    public function testRoleNormalizationAndAdminFlag(): void
    {
        $participant = new ConversationParticipant();

        self::assertSame(ConversationParticipant::ROLE_MEMBER, $participant->getRole());
        self::assertFalse($participant->isAdmin());

        $participant->setRole(ConversationParticipant::ROLE_ADMIN);
        self::assertSame(ConversationParticipant::ROLE_ADMIN, $participant->getRole());
        self::assertTrue($participant->isAdmin());

        $participant->setRole('unknown-role');
        self::assertSame(ConversationParticipant::ROLE_MEMBER, $participant->getRole());
        self::assertFalse($participant->isAdmin());
    }

    public function testOnPrePersistSetsJoinedAtOnlyOnce(): void
    {
        $participant = new ConversationParticipant();
        self::assertNull($participant->getJoinedAt());

        $participant->onPrePersist();
        $joinedAt = $participant->getJoinedAt();

        self::assertInstanceOf(\DateTimeImmutable::class, $joinedAt);

        $participant->onPrePersist();
        self::assertSame($joinedAt, $participant->getJoinedAt());
    }
}
