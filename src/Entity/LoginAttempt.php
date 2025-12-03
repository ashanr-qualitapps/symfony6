<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LoginAttemptRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LoginAttemptRepository::class)]
#[ORM\Table(name: 'login_attempts')]
class LoginAttempt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $identifier;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $attempts = 0;

    #[ORM\Column(type: 'datetime', name: 'last_attempt')]
    private \DateTimeInterface $lastAttempt;

    public function __construct(string $identifier, \DateTimeInterface $lastAttempt, int $attempts = 0)
    {
        $this->identifier = $identifier;
        $this->lastAttempt = $lastAttempt;
        $this->attempts = $attempts;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function setAttempts(int $attempts): self
    {
        $this->attempts = $attempts;
        return $this;
    }

    public function incrementAttempts(int $by = 1): self
    {
        $this->attempts += $by;
        return $this;
    }

    public function getLastAttempt(): \DateTimeInterface
    {
        return $this->lastAttempt;
    }

    public function setLastAttempt(\DateTimeInterface $lastAttempt): self
    {
        $this->lastAttempt = $lastAttempt;
        return $this;
    }
}
