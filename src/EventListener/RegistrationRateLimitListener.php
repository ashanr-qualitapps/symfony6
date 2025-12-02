<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[AsEventListener(event: RequestEvent::class, method: 'onRequest', priority: 10)]
class RegistrationRateLimitListener
{
    private RateLimiterFactory $registrationLimiterFactory;
    private KernelInterface $kernel;

    public function __construct(RateLimiterFactory $registrationLimiterFactory, KernelInterface $kernel)
    {
        $this->registrationLimiterFactory = $registrationLimiterFactory;
        $this->kernel = $kernel;
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Apply rate limiting to registration endpoint
        if ($request->getPathInfo() === '/api/register' && $request->isMethod('POST')) {
            // Skip rate limiting in test environment unless explicitly enabled
            $requestData = json_decode($request->getContent(), true) ?: [];
            $enableRateLimiting = $requestData['enable_rate_limiting'] ?? false;

            if ($this->kernel->getEnvironment() === 'test' && !$enableRateLimiting) {
                return;
            }

            $identifier = $requestData['test_id'] ?? $request->getClientIp() ?: 'unknown';
            $limiter = $this->registrationLimiterFactory->create($identifier);

            if (!$limiter->consume(1)->isAccepted()) {
                $event->setResponse(new JsonResponse([
                    'error' => 'Too many registration attempts. Please try again later.',
                    'retry_after' => $limiter->consume(0)->getRetryAfter()->getTimestamp() - time()
                ], Response::HTTP_TOO_MANY_REQUESTS));
            }
        }
    }
}
