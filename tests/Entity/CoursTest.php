<?php

namespace App\Tests\Entity;

use App\Entity\Cours;
use App\Entity\Lecon;
use App\Entity\Quiz;
use App\Entity\UserCoursProgress;
use PHPUnit\Framework\TestCase;

final class CoursTest extends TestCase
{
    public function testDefaultStateAndCounterClamping(): void
    {
        $cours = new Cours();

        self::assertCount(0, $cours->getLecons());
        self::assertCount(0, $cours->getQuizzes());
        self::assertCount(0, $cours->getProgress());
        self::assertSame(0, $cours->getLikes());
        self::assertSame(0, $cours->getDislikes());

        $cours->setLikes(-5)->setDislikes(-10);
        self::assertSame(0, $cours->getLikes());
        self::assertSame(0, $cours->getDislikes());

        $cours->setLikes(12)->setDislikes(3);
        self::assertSame(12, $cours->getLikes());
        self::assertSame(3, $cours->getDislikes());
    }

    public function testAddAndRemoveLeconKeepsRelationInSync(): void
    {
        $cours = new Cours();
        $lecon = new Lecon();

        $cours->addLecon($lecon);

        self::assertCount(1, $cours->getLecons());
        self::assertSame($cours, $lecon->getCours());

        $cours->addLecon($lecon);
        self::assertCount(1, $cours->getLecons());

        $cours->removeLecon($lecon);
        self::assertCount(0, $cours->getLecons());
        self::assertNull($lecon->getCours());
    }

    public function testAddAndRemoveQuizKeepsRelationInSync(): void
    {
        $cours = new Cours();
        $quiz = new Quiz();

        $cours->addQuiz($quiz);

        self::assertCount(1, $cours->getQuizzes());
        self::assertSame($cours, $quiz->getCours());

        $cours->removeQuiz($quiz);
        self::assertCount(0, $cours->getQuizzes());
        self::assertNull($quiz->getCours());
    }

    public function testAddAndRemoveProgressKeepsRelationInSync(): void
    {
        $cours = new Cours();
        $progress = new UserCoursProgress();

        $cours->addProgress($progress);

        self::assertCount(1, $cours->getProgress());
        self::assertSame($cours, $progress->getCours());

        $cours->removeProgress($progress);
        self::assertCount(0, $cours->getProgress());
        self::assertNull($progress->getCours());
    }
}
