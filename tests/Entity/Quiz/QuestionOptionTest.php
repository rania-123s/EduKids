<?php

namespace App\Tests\Entity\Quiz;

use App\Entity\Quiz\QuestionOption;
use PHPUnit\Framework\TestCase;

final class QuestionOptionTest extends TestCase
{
    public function testBasicSettersAndFlags(): void
    {
        $option = new QuestionOption();
        self::assertFalse($option->isCorrect());
        self::assertSame(0, $option->getOrdre());

        $option
            ->setTexte('Paris')
            ->setOrdre(2)
            ->setCorrect(true);

        self::assertSame('Paris', $option->getTexte());
        self::assertSame(2, $option->getOrdre());
        self::assertTrue($option->isCorrect());
    }
}

