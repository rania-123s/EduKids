<?php

namespace App\Tests\Entity;

use App\Entity\Message;
use App\Entity\MessageAttachment;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class MessageTest extends TestCase
{
    public function testAddAndRemoveAttachmentKeepBidirectionalRelationSynced(): void
    {
        $message = new Message();
        $attachment = new MessageAttachment();

        self::assertCount(0, $message->getAttachments());

        $message->addAttachment($attachment);
        self::assertCount(1, $message->getAttachments());
        self::assertSame($message, $attachment->getMessage());

        $message->removeAttachment($attachment);
        self::assertCount(0, $message->getAttachments());
        self::assertNull($attachment->getMessage());
    }

    public function testSenderRoleHelpers(): void
    {
        $message = new Message();
        $admin = (new User())->setRoles(['ROLE_ADMIN']);

        $message->setSender($admin);
        self::assertTrue($message->isFromAdmin());
        self::assertFalse($message->isFromParent());

        $parent = (new User())->setRoles(['ROLE_PARENT']);
        $message->setSender($parent);
        self::assertFalse($message->isFromAdmin());
        self::assertTrue($message->isFromParent());
    }

    public function testIsDeletedDependsOnDeletedAt(): void
    {
        $message = new Message();
        self::assertFalse($message->isDeleted());

        $message->setDeletedAt(new \DateTimeImmutable());
        self::assertTrue($message->isDeleted());
    }
}
