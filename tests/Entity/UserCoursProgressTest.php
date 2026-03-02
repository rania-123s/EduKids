<?php

namespace App\Tests\Entity;

use App\Entity\Cours;
use App\Entity\User;
use App\Entity\UserCoursProgress;
use PHPUnit\Framework\TestCase;

final class UserCoursProgressTest extends TestCase
{
    public function testProgressIsClampedBetweenZeroAndHundred(): void
    {
        $progress = new UserCoursProgress();
        $progress->setUser(new User())->setCours(new Cours());

        $progress->setProgress(-10);
        self::assertSame(0, $progress->getProgress());

        $progress->setProgress(45);
        self::assertSame(45, $progress->getProgress());

        $progress->setProgress(250);
        self::assertSame(100, $progress->getProgress());
    }
}

