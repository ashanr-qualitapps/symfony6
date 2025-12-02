<?php

namespace App\Security;

use App\Repository\ApiTokenRepository;
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
    private ApiTokenRepository $apiTokenRepository;

    public function __construct(UserRepository $userRepository, ApiTokenRepository $apiTokenRepository)
    {
        $this->userRepository = $userRepository;
        $this->apiTokenRepository = $apiTokenRepository;
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

        // Validate token against database
        $userIdentifier = $this->validateToken($token);
        
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

    private function validateToken(string $token): ?string
    {
        $apiToken = $this->apiTokenRepository->findValidToken($token);
        
        return $apiToken ? $apiToken->getUser()->getUserIdentifier() : null;
    }
}
