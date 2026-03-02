<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testGetRolesAlwaysContainsRoleUserOnlyOnce(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN', 'ROLE_USER', 'ROLE_ADMIN']);

        $roles = $user->getRoles();

        self::assertContains('ROLE_ADMIN', $roles);
        self::assertContains('ROLE_USER', $roles);
        self::assertSame(1, count(array_keys($roles, 'ROLE_USER', true)));
        self::assertSame(1, count(array_keys($roles, 'ROLE_ADMIN', true)));
    }

    public function testUserIdentifierUsesEmail(): void
    {
        $user = new User();
        $user->setEmail('student@example.com');

        self::assertSame('student@example.com', $user->getUserIdentifier());
    }

    public function testActiveFlagDefaultsToTrueAndCanBeUpdated(): void
    {
        $user = new User();
        self::assertTrue($user->isActive());

        $user->setIsActive(false);
        self::assertFalse($user->isActive());
    }
}
