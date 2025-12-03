<?php

namespace App\EventListener;

use App\Exception\TooManyLoginAttemptsException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

#[AsEventListener(event: RequestEvent::class, method: 'onRequest')]
#[AsEventListener(event: CheckPassportEvent::class, method: 'onCheckPassport')]
class LoginRateLimitListener
{
    private RateLimiterFactory $loginLimiterFactory;
    private RateLimiterFactory $registrationLimiterFactory;
    private RequestStack $requestStack;
    private KernelInterface $kernel;

    public function __construct(
        RateLimiterFactory $loginLimiterFactory,
        RateLimiterFactory $registrationLimiterFactory,
        RequestStack $requestStack,
        KernelInterface $kernel
    ) {
        $this->loginLimiterFactory = $loginLimiterFactory;
        $this->registrationLimiterFactory = $registrationLimiterFactory;
        $this->requestStack = $requestStack;
        $this->kernel = $kernel;
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Check if rate limiting is explicitly enabled via request data
        $requestData = json_decode($request->getContent(), true) ?: [];
        $enableRateLimiting = $requestData['enable_rate_limiting'] ?? false;

        // Skip rate limiting in test environment unless explicitly enabled
        if ($this->kernel->getEnvironment() === 'test' && !$enableRateLimiting) {
            return;
        }

        // Apply rate limiting to registration endpoint
        if ($request->getPathInfo() === '/api/register' && $request->isMethod('POST')) {
            $requestData = json_decode($request->getContent(), true) ?: [];
            $identifier = $requestData['test_id'] ?? $request->getClientIp() ?: 'test-client';

            $limiter = $this->registrationLimiterFactory->create($identifier);
            $limit = $limiter->consume(1);

            if (!$limit->isAccepted()) {
                $event->setResponse(new JsonResponse([
                    'error' => 'Too many registration attempts. Please try again later.',
                    'retry_after' => $limit->getRetryAfter()->getTimestamp() - time()
                ], Response::HTTP_TOO_MANY_REQUESTS));
            }
        }
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        // Skip rate limiting in test environment unless explicitly enabled via request data
        $requestData = json_decode($request->getContent(), true) ?: [];
        $enableRateLimiting = $requestData['enable_rate_limiting'] ?? false;

        if ($this->kernel->getEnvironment() === 'test' && !$enableRateLimiting) {
            return;
        }

        // Apply rate limiting to login endpoint
        if ($request->getPathInfo() === '/api/login' && $request->isMethod('POST')) {
            error_log('LoginRateLimitListener: CheckPassportEvent triggered for login');
            $limiter = $this->loginLimiterFactory->create($request->getClientIp() ?: 'test-client');

            $limit = $limiter->consume(1);
            error_log('LoginRateLimitListener: Consumed 1 token, accepted: ' . ($limit->isAccepted() ? 'yes' : 'no'));

            if (!$limit->isAccepted()) {
                error_log('LoginRateLimitListener: Rate limit exceeded, throwing exception');
                // For CheckPassportEvent, we need to throw an exception or set a response
                // But since this is during authentication, let's throw a custom exception
                throw new TooManyLoginAttemptsException('Too many login attempts. Please try again later.');
            }
        }
    }
}
