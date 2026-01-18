<?php

namespace App\Entity;

use App\Repository\OtpRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OtpRepository::class)]
class Otp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 15)]
    private string $phone;

    #[ORM\Column(length: 6)]
    private string $code;

    #[ORM\Column]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column]
    private bool $isUsed = false;

    public function __construct(string $phone, string $code)
    {
        $this->phone = $phone;
        $this->code = $code;
        $this->expiresAt = new \DateTimeImmutable('+5 minutes');
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function markUsed(): void
    {
        $this->isUsed = true;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
