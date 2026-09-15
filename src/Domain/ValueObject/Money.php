<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\ValueObject;

final readonly class Money
{
    private int $cents;

    public function __construct(float|int|string $value)
    {
        $this->cents = match (true) {
            is_int($value) => $value * 100,
            is_float($value) => (int) round($value * 100),
            default => (int) round(((float) str_replace(',', '.', $value)) * 100),
        };
    }

    public static function fromCents(int $cents): self
    {
        return new self((float) $cents / 100);
    }

    public function getValue(): float
    {
        return $this->cents / 100;
    }

    public function getCents(): int
    {
        return $this->cents;
    }

    public function add(self $other): self
    {
        return self::fromCents($this->cents + $other->cents);
    }

    public function subtract(self $other): self
    {
        return self::fromCents($this->cents - $other->cents);
    }

    public function multiply(float $factor): self
    {
        return self::fromCents((int) round($this->cents * $factor));
    }

    public function percentage(float $percent): self
    {
        return $this->multiply($percent / 100);
    }

    public function isPositive(): bool
    {
        return $this->cents > 0;
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function formatted(bool $withSymbol = true): string
    {
        $value = number_format($this->getValue(), 2, ',', '.');
        return $withSymbol ? 'R$ ' . $value : $value;
    }

    public function __toString(): string
    {
        return $this->formatted(false);
    }
}
