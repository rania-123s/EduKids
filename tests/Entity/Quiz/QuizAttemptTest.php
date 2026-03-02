<?php

namespace App\Tests\Entity\Quiz;

use App\Entity\Quiz\QuizAttempt;
use PHPUnit\Framework\TestCase;

final class QuizAttemptTest extends TestCase
{
    public function testPercentageCalculationAndAnswers(): void
    {
        $attempt = new QuizAttempt();

        self::assertSame(0.0, $attempt->getPercentage());

        $attempt
            ->setScore(7)
            ->setTotalQuestions(9)
            ->setAnswers(['1' => 'A', '2' => 'B']);

        self::assertSame(77.8, $attempt->getPercentage());
        self::assertSame(['1' => 'A', '2' => 'B'], $attempt->getAnswers());
    }
}

