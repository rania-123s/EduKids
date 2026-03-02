<?php

namespace App\Tests\Entity;

use App\Entity\Evenement;
use App\Entity\Programme;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class ProgrammeTest extends TestCase
{
    public function testDureePauseAndStringRepresentation(): void
    {
        $programme = new Programme();

        self::assertNull($programme->getDureePause());
        self::assertSame('Programme #', (string) $programme);

        $evenement = (new Evenement())->setTitre('Forum Parents');
        $programme
            ->setEvenement($evenement)
            ->setPauseDebut(new \DateTimeImmutable('12:00'))
            ->setPauseFin(new \DateTimeImmutable('12:45'));

        self::assertSame(45, $programme->getDureePause());
        self::assertSame('Programme de Forum Parents', (string) $programme);
    }

    public function testValidatePauseIntervalAddsViolationWhenEventHasNoHours(): void
    {
        $programme = new Programme();
        $programme
            ->setEvenement(new Evenement())
            ->setPauseDebut(new \DateTimeImmutable('10:00'))
            ->setPauseFin(new \DateTimeImmutable('10:30'));

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects(self::once())
            ->method('atPath')
            ->with('evenement')
            ->willReturnSelf();
        $builder->expects(self::once())
            ->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::once())
            ->method('buildViolation')
            ->with(self::stringContains('heures de'))
            ->willReturn($builder);

        $programme->validatePauseInterval($context);
    }

    public function testValidatePauseIntervalAddsViolationsWhenPauseIsOutsideEventRange(): void
    {
        $evenement = (new Evenement())
            ->setHeureDebut(new \DateTimeImmutable('09:00'))
            ->setHeureFin(new \DateTimeImmutable('17:00'));

        $programme = (new Programme())
            ->setEvenement($evenement)
            ->setPauseDebut(new \DateTimeImmutable('08:30'))
            ->setPauseFin(new \DateTimeImmutable('17:30'));

        $startBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $startBuilder->expects(self::exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $startBuilder->expects(self::once())
            ->method('atPath')
            ->with('pauseDebut')
            ->willReturnSelf();
        $startBuilder->expects(self::once())
            ->method('addViolation');

        $endBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $endBuilder->expects(self::exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $endBuilder->expects(self::once())
            ->method('atPath')
            ->with('pauseFin')
            ->willReturnSelf();
        $endBuilder->expects(self::once())
            ->method('addViolation');

        $messages = [];
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::exactly(2))
            ->method('buildViolation')
            ->willReturnCallback(function (string $message) use (&$messages, $startBuilder, $endBuilder) {
                $messages[] = $message;
                return count($messages) === 1 ? $startBuilder : $endBuilder;
            });

        $programme->validatePauseInterval($context);

        self::assertCount(2, $messages);
        self::assertStringContainsString('pause_debut', $messages[0]);
        self::assertStringContainsString('pause_fin', $messages[1]);
    }

    public function testValidatePauseIntervalDoesNotAddViolationWhenRangeIsValid(): void
    {
        $evenement = (new Evenement())
            ->setHeureDebut(new \DateTimeImmutable('09:00'))
            ->setHeureFin(new \DateTimeImmutable('17:00'));

        $programme = (new Programme())
            ->setEvenement($evenement)
            ->setPauseDebut(new \DateTimeImmutable('12:00'))
            ->setPauseFin(new \DateTimeImmutable('12:30'));

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::never())->method('buildViolation');

        $programme->validatePauseInterval($context);
    }
}
