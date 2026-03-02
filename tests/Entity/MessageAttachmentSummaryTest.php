<?php

namespace App\Tests\Entity;

use App\Entity\MessageAttachmentSummary;
use PHPUnit\Framework\TestCase;

final class MessageAttachmentSummaryTest extends TestCase
{
    public function testTextAndStatusNormalization(): void
    {
        $summary = new MessageAttachmentSummary();

        $summary->setSummaryText("  Resume genere  \n");
        self::assertSame('Resume genere', $summary->getSummaryText());

        $summary->setErrorMessage('  erreur extraction  ');
        self::assertSame('erreur extraction', $summary->getErrorMessage());

        $summary->setErrorMessage(null);
        self::assertNull($summary->getErrorMessage());

        $summary->setStatus('DONE');
        self::assertSame(MessageAttachmentSummary::STATUS_DONE, $summary->getStatus());
        self::assertTrue($summary->isDone());

        $summary->setStatus('unsupported-status');
        self::assertSame(MessageAttachmentSummary::STATUS_PENDING, $summary->getStatus());
        self::assertFalse($summary->isDone());
    }

    public function testLifecycleCallbacksSetAndRefreshTimestamps(): void
    {
        $summary = new MessageAttachmentSummary();

        self::assertNull($summary->getCreatedAt());
        self::assertNull($summary->getUpdatedAt());

        $summary->onPrePersist();
        $createdAt = $summary->getCreatedAt();
        $updatedAt = $summary->getUpdatedAt();

        self::assertInstanceOf(\DateTimeImmutable::class, $createdAt);
        self::assertInstanceOf(\DateTimeImmutable::class, $updatedAt);

        usleep(1000);
        $summary->onPreUpdate();
        $newUpdatedAt = $summary->getUpdatedAt();

        self::assertInstanceOf(\DateTimeImmutable::class, $newUpdatedAt);
        self::assertGreaterThanOrEqual(
            (int) $updatedAt->format('Uu'),
            (int) $newUpdatedAt->format('Uu')
        );
    }
}
