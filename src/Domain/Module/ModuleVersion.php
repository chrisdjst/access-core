<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\Module;

use ModularizeRbac\Core\Exceptions\InvalidInput;

/**
 * Semver 2.0.0 version identifier for a module release.
 * Parses MAJOR.MINOR.PATCH[-PRERELEASE] and implements §11 precedence rules.
 */
final readonly class ModuleVersion
{
    private const PATTERN = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-([0-9A-Za-z.-]+))?$/';

    public function __construct(
        public int $major,
        public int $minor,
        public int $patch,
        public ?string $prerelease,
    ) {
    }

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if (preg_match(self::PATTERN, $trimmed, $m) !== 1) {
            throw InvalidInput::of(
                'module_version',
                "Version must be a valid semver string (e.g. \"1.2.3\" or \"1.2.3-beta.1\"). Got: {$value}"
            );
        }

        return new self(
            major: (int) $m[1],
            minor: (int) $m[2],
            patch: (int) $m[3],
            prerelease: isset($m[4]) && $m[4] !== '' ? $m[4] : null,
        );
    }

    public function isPrerelease(): bool
    {
        return $this->prerelease !== null;
    }

    /**
     * SemVer 2.0.0 §11 precedence comparison.
     * Returns -1, 0, or 1 (compatible with usort).
     */
    public function compare(self $other): int
    {
        foreach (['major', 'minor', 'patch'] as $part) {
            $diff = $this->$part <=> $other->$part;
            if ($diff !== 0) {
                return $diff;
            }
        }

        // §11.3: when MAJOR.MINOR.PATCH are equal, pre-release < release
        if ($this->prerelease === null && $other->prerelease === null) {
            return 0;
        }
        if ($this->prerelease === null) {
            return 1;
        }
        if ($other->prerelease === null) {
            return -1;
        }

        return $this->comparePrerelease($this->prerelease, $other->prerelease);
    }

    public function equals(self $other): bool
    {
        return $this->compare($other) === 0;
    }

    public function __toString(): string
    {
        $base = "{$this->major}.{$this->minor}.{$this->patch}";

        return $this->prerelease !== null ? "{$base}-{$this->prerelease}" : $base;
    }

    /**
     * §11.4: compare pre-release identifier lists split on dot.
     * Numeric identifiers: compared as integers.
     * Alphanumeric identifiers: compared lexically (ASCII).
     * Shorter list has lower precedence when all preceding match.
     */
    private function comparePrerelease(string $a, string $b): int
    {
        $partsA = explode('.', $a);
        $partsB = explode('.', $b);
        $len = max(count($partsA), count($partsB));

        for ($i = 0; $i < $len; $i++) {
            if (! isset($partsA[$i])) {
                return -1;
            }
            if (! isset($partsB[$i])) {
                return 1;
            }

            $ia = $partsA[$i];
            $ib = $partsB[$i];
            $aIsNum = ctype_digit($ia);
            $bIsNum = ctype_digit($ib);

            if ($aIsNum && $bIsNum) {
                $diff = (int) $ia <=> (int) $ib;
            } elseif ($aIsNum) {
                // numeric < alphanumeric
                $diff = -1;
            } elseif ($bIsNum) {
                $diff = 1;
            } else {
                $diff = strcmp($ia, $ib);
            }

            if ($diff !== 0) {
                return $diff < 0 ? -1 : 1;
            }
        }

        return 0;
    }
}
