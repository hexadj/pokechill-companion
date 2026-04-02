<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Error\ApiRequestValidationException;
use App\Error\InvalidJsonBodyException;
use App\Error\ProblemJsonResponseFactory;
use App\Recommendation\Exception\InvalidRecommendationQueryException;
use App\Recommendation\Exception\OpponentPokemonNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Maps known exceptions to application/problem+json responses.
 */
final class ProblemJsonExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if ($throwable instanceof InvalidJsonBodyException) {
            $event->setResponse(ProblemJsonResponseFactory::create(
                400,
                'Bad Request',
                $throwable->getMessage(),
                ProblemJsonResponseFactory::TYPE_BAD_REQUEST,
            ));

            return;
        }

        if ($throwable instanceof ApiRequestValidationException) {
            $event->setResponse(ProblemJsonResponseFactory::create(
                422,
                'Validation failed',
                $throwable->getMessage(),
                ProblemJsonResponseFactory::TYPE_VALIDATION,
                $throwable->getErrors(),
            ));

            return;
        }

        if ($throwable instanceof InvalidRecommendationQueryException) {
            $event->setResponse(ProblemJsonResponseFactory::create(
                422,
                'Validation failed',
                $throwable->getMessage(),
                ProblemJsonResponseFactory::TYPE_VALIDATION,
                ['query' => [$throwable->getMessage()]],
            ));

            return;
        }

        if ($throwable instanceof OpponentPokemonNotFoundException) {
            $keys = $throwable->getSourceKeys();
            $detail = sprintf('Unknown source keys: %s.', implode(', ', $keys));
            $event->setResponse(ProblemJsonResponseFactory::create(
                422,
                'Validation failed',
                'One or more opponentSourceKeys are invalid.',
                ProblemJsonResponseFactory::TYPE_VALIDATION,
                ['opponentSourceKeys' => [$detail]],
            ));

            return;
        }

        if (str_starts_with($event->getRequest()->getPathInfo(), '/api/v1')) {
            $event->setResponse(ProblemJsonResponseFactory::create(
                500,
                'Internal Server Error',
                'An unexpected error occurred.',
                ProblemJsonResponseFactory::TYPE_SERVER_ERROR,
            ));
        }
    }
}
