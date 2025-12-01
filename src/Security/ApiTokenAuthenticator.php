<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization');
        
        if (null === $authHeader) {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        // Support both "Bearer token" and just "token"
        $token = str_starts_with($authHeader, 'Bearer ') 
            ? substr($authHeader, 7) 
            : $authHeader;

        if (empty($token)) {
            throw new CustomUserMessageAuthenticationException('Invalid token format');
        }

        // Validate token (simplified - in production, verify JWT or lookup in cache/DB)
        // For this demo, we'll extract user from session or validate against stored tokens
        $userIdentifier = $this->validateToken($token, $request);
        
        if (null === $userIdentifier) {
            throw new CustomUserMessageAuthenticationException('Invalid or expired token');
        }

        return new SelfValidatingPassport(
            new UserBadge($userIdentifier, function ($userIdentifier) {
                return $this->userRepository->findOneByEmail($userIdentifier);
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Allow the request to continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => 'Authentication failed',
            'message' => $exception->getMessage()
        ], Response::HTTP_UNAUTHORIZED);
    }

    private function validateToken(string $token, Request $request): ?string
    {
        // Simplified token validation using session
        // In production, use JWT validation or Redis/database lookup
        $session = $request->hasSession() ? $request->getSession() : null;
        
        if (null === $session) {
            return null;
        }

        // Check all stored tokens in session
        foreach ($session->all() as $key => $value) {
            if (str_starts_with($key, 'api_token_') && $value === $token) {
                return substr($key, 10); // Extract email from key
            }
        }

        return null;
    }
}
