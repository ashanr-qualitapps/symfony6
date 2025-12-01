# Authentication System Documentation

## Overview

This Symfony application implements a secure JWT-like token-based authentication system with the following features:

- User registration with email/password
- Secure password hashing using Symfony's password hasher
- JSON-based login with token generation
- Token-based API authentication
- Session-backed token storage
- Role-based access control (RBAC)

## Architecture

### Components

1. **User Entity** (`src/Entity/User.php`)
   - Implements `UserInterface` and `PasswordAuthenticatedUserInterface`
   - Stores user credentials and roles
   - Uses Doctrine ORM for persistence

2. **Authentication Handlers**
   - `AuthenticationSuccessHandler`: Generates tokens on successful login
   - `AuthenticationFailureHandler`: Returns JSON error responses
   - `ApiTokenAuthenticator`: Validates Bearer tokens for API requests

3. **Controllers**
   - `RegistrationController`: Handles user registration
   - `LoginController`: Manages login/logout endpoints

### Security Configuration

The application uses Symfony's security component with multiple firewalls:

- **dev**: Bypasses security for profiler and debug toolbar
- **login**: Handles JSON login at `/api/login`
- **api**: Protects API endpoints with token authentication
- **main**: Default firewall for other routes

## API Endpoints

### 1. User Registration

**Endpoint:** `POST /api/register`

**Request:**
```json
{
  "email": "user@example.com",
  "password": "SecurePassword123!"
}
```

**Success Response (201 Created):**
```json
{
  "success": true,
  "id": 1
}
```

**Error Responses:**
- `400 Bad Request`: Missing email or password
- `409 Conflict`: Email already exists

**Example:**
```bash
curl -X POST http://localhost:8080/api/register \
  -H 'Content-Type: application/json' \
  -d '{"email":"user@example.com","password":"SecurePassword123!"}'
```

### 2. User Login

**Endpoint:** `POST /api/login`

**Request:**
```json
{
  "email": "user@example.com",
  "password": "SecurePassword123!"
}
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "token": "base64_encoded_token_here",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "roles": ["ROLE_USER"]
  }
}
```

**Error Response (401 Unauthorized):**
```json
{
  "error": "Invalid credentials",
  "message": "Authentication failed"
}
```

**Example:**
```bash
curl -X POST http://localhost:8080/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"user@example.com","password":"SecurePassword123!"}'
```

**Important:** Save both the `token` and the `PHPSESSID` cookie from the response for subsequent requests.

### 3. Accessing Protected Endpoints

All `/api/*` endpoints (except `/api/login` and `/api/register`) require authentication.

**Headers Required:**
- `Authorization: Bearer YOUR_TOKEN`
- `Cookie: PHPSESSID=YOUR_SESSION_ID`

**Example:**
```bash
# First, login and extract token and session
LOGIN_RESPONSE=$(curl -c cookies.txt -X POST http://localhost:8080/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"user@example.com","password":"SecurePassword123!"}')

TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.token')

# Then use the token and cookies for authenticated requests
curl -b cookies.txt \
  -H "Authorization: Bearer $TOKEN" \
  http://localhost:8080/api/countries
```

### 4. User Logout

**Endpoint:** `POST /api/logout`

**Example:**
```bash
curl -b cookies.txt \
  -X POST http://localhost:8080/api/logout \
  -H "Authorization: Bearer $TOKEN"
```

## Security Features

### Password Hashing
- Uses Symfony's auto password hasher (bcrypt/argon2)
- Automatic salt generation
- Cost factor: Production (default), Test (lowest for speed)

### Token Generation
- Base64-encoded random 32-byte tokens
- Stored in session with user identifier
- Validates on each API request

### Access Control
```yaml
access_control:
  - { path: ^/api/login, roles: PUBLIC_ACCESS }
  - { path: ^/api/register, roles: PUBLIC_ACCESS }
  - { path: ^/api, roles: ROLE_USER }
```

### CSRF Protection
- Disabled for stateless API endpoints
- JSON-only authentication

## Testing

Run authentication tests:

```bash
# Run all login tests
docker-compose exec php php bin/phpunit tests/Application/Controller/LoginControllerTest.php

# Run registration tests
docker-compose exec php php bin/phpunit tests/Application/Controller/RegistrationControllerTest.php

# Run all tests
docker-compose exec php php bin/phpunit
```

## Production Considerations

⚠️ **Important for Production:**

1. **Replace Session-Based Tokens with JWT**
   - Current implementation uses session storage
   - For stateless APIs, implement JWT (lcobucci/jwt already installed)
   - Add token expiration and refresh logic

2. **Enable HTTPS**
   - Never transmit credentials over HTTP
   - Update nginx configuration for SSL/TLS

3. **Rate Limiting**
   - Add rate limiting to login endpoint
   - Prevent brute force attacks
   - Consider using `symfony/rate-limiter`

4. **Token Expiration**
   - Implement token TTL (Time To Live)
   - Add refresh token mechanism
   - Automatically invalidate expired tokens

5. **Password Policies**
   - Add password strength validation
   - Implement password history
   - Force password rotation

6. **Multi-Factor Authentication (MFA)**
   - Consider adding 2FA support
   - Use TOTP or SMS verification

7. **Security Headers**
   - Add security headers in nginx
   - Implement CORS properly for API access

8. **Audit Logging**
   - Log all authentication attempts
   - Monitor failed login attempts
   - Track token usage

## Troubleshooting

### 404 Error on Login/Register
Clear the cache:
```bash
docker-compose exec php php bin/console cache:clear --env=prod
docker-compose exec php php bin/console cache:clear --env=dev
```

### Token Authentication Fails
Ensure both headers are set:
```bash
# Include session cookie AND bearer token
curl -b "PHPSESSID=your_session_id" \
  -H "Authorization: Bearer your_token" \
  http://localhost:8080/api/endpoint
```

### Database Connection Errors
Check environment variables:
```bash
docker-compose exec php php bin/console doctrine:database:create
docker-compose exec php php bin/console doctrine:migrations:migrate
```

## Code Examples

### Creating a User Programmatically
```php
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

$user = new User();
$user->setEmail('user@example.com');
$user->setRoles(['ROLE_USER']);
$hashedPassword = $passwordHasher->hashPassword($user, 'plainPassword');
$user->setPassword($hashedPassword);
$user->setCreatedAt(new \DateTime());

$entityManager->persist($user);
$entityManager->flush();
```

### Accessing Current User in Controller
```php
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/profile', methods: ['GET'])]
public function profile(#[CurrentUser] ?User $user): JsonResponse
{
    if (!$user) {
        return new JsonResponse(['error' => 'Not authenticated'], 401);
    }
    
    return new JsonResponse([
        'id' => $user->getId(),
        'email' => $user->getEmail(),
        'roles' => $user->getRoles()
    ]);
}
```

## Additional Resources

- [Symfony Security Documentation](https://symfony.com/doc/current/security.html)
- [Authentication Best Practices](https://symfony.com/doc/current/security/authentication.html)
- [Password Hashing](https://symfony.com/doc/current/security/passwords.html)
