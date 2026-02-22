<?php

namespace App\Controller;

use App\Service\LibreTranslateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DetectLangController extends AbstractController
{
    private const MAX_TEXT_LENGTH = 5000;

    public function __construct(
        private readonly LibreTranslateService $libreTranslateService
    ) {
    }

    #[Route('/api/detect-lang', name: 'api_detect_lang', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function detect(Request $request): JsonResponse
    {
        $this->assertCsrf($request);

        try {
            $payload = $request->toArray();
        } catch (\JsonException) {
            return $this->json(['error' => 'Invalid JSON payload.'], Response::HTTP_BAD_REQUEST);
        }

        $text = trim((string) ($payload['text'] ?? ''));
        if ($text === '') {
            return $this->json(['error' => 'Text cannot be empty.'], Response::HTTP_BAD_REQUEST);
        }

        if (mb_strlen($text) > self::MAX_TEXT_LENGTH) {
            return $this->json([
                'error' => sprintf('Text exceeds max length (%d characters).', self::MAX_TEXT_LENGTH),
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $lang = $this->libreTranslateService->detectLanguage($text);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'error' => $this->formatServiceErrorMessage($exception),
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $this->json(['lang' => $lang]);
    }

    private function formatServiceErrorMessage(\RuntimeException $exception): string
    {
        $message = trim($exception->getMessage());
        if ($message === '') {
            return 'Language detection service unavailable.';
        }

        return mb_substr($message, 0, 240);
    }

    private function assertCsrf(Request $request): void
    {
        $token = $request->headers->get('X-CSRF-TOKEN') ?? '';
        if (!$this->isCsrfTokenValid('chat_action', $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }
}
