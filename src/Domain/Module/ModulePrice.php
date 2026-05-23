<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\Module;

use DateTimeImmutable;
use ModularizeRbac\Core\Domain\Shared\Clock;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;

/**
 * Optional monetary price attached to a module — the host platform
 * uses this to gate module activation per tenant. Multiple historical
 * prices may exist per module; at most one is `isActive` at a time.
 *
 * Currency is stored as a 3-letter ISO 4217 code (uppercased) and
 * value as a string to preserve precision — the application boundary
 * decides whether to format as cents or decimal at I/O time.
 */
final class ModulePrice
{
    private const CURRENCY_PATTERN = '/^[A-Z]{3}$/';

    public function __construct(
        public readonly Uuid $id,
        public readonly Uuid $moduleId,
        private string $value,
        private string $currency,
        private bool $isActive,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        Uuid $id,
        Uuid $moduleId,
        string $value,
        string $currency,
        bool $isActive,
        Clock $clock,
    ): self {
        self::assertValidValue($value);
        $normalizedCurrency = self::normalizeCurrency($currency);
        $now = $clock->now();

        return new self(
            id: $id,
            moduleId: $moduleId,
            value: $value,
            currency: $normalizedCurrency,
            isActive: $isActive,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function value(): string
    {
        return $this->value;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function changeAmount(string $value, string $currency, Clock $clock): void
    {
        self::assertValidValue($value);
        $normalizedCurrency = self::normalizeCurrency($currency);
        if ($this->value === $value && $this->currency === $normalizedCurrency) {
            return;
        }
        $this->value = $value;
        $this->currency = $normalizedCurrency;
        $this->updatedAt = $clock->now();
    }

    public function deactivate(Clock $clock): void
    {
        if (! $this->isActive) {
            return;
        }
        $this->isActive = false;
        $this->updatedAt = $clock->now();
    }

    private static function assertValidValue(string $value): void
    {
        if (! preg_match('/^-?\d+(\.\d+)?$/', $value)) {
            throw InvalidInput::of('module_price.value', "Price must be a decimal string. Got: {$value}");
        }
    }

    private static function normalizeCurrency(string $currency): string
    {
        $upper = strtoupper(trim($currency));
        if (preg_match(self::CURRENCY_PATTERN, $upper) !== 1) {
            throw InvalidInput::of(
                'module_price.currency',
                "Currency must be a 3-letter ISO 4217 code. Got: {$currency}"
            );
        }

        return $upper;
    }
}
