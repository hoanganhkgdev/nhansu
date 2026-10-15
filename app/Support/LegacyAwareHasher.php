<?php

namespace App\Support;

use Illuminate\Hashing\BcryptHasher;

/**
 * Bcrypt hasher that also accepts passwords imported from NukeViet ({SSHA512} + base64(sha512(pass.salt).salt)).
 * Legacy hashes report needsRehash() = true, so Laravel upgrades them to bcrypt on the next successful login.
 */
class LegacyAwareHasher extends BcryptHasher
{
    private const PREFIX = '{SSHA512}';

    public function check($value, $hashedValue, array $options = []): bool
    {
        if ($this->isLegacy($hashedValue)) {
            $decoded = base64_decode(substr($hashedValue, strlen(self::PREFIX)), true);
            if ($decoded === false || strlen($decoded) < 64) {
                return false;
            }

            $salt = substr($decoded, 64);

            return hash_equals(substr($decoded, 0, 64), hash('sha512', $value.$salt, true));
        }

        return parent::check($value, $hashedValue, $options);
    }

    public function needsRehash($hashedValue, array $options = []): bool
    {
        return $this->isLegacy($hashedValue) || parent::needsRehash($hashedValue, $options);
    }

    private function isLegacy(?string $hashedValue): bool
    {
        return $hashedValue !== null && str_starts_with($hashedValue, self::PREFIX);
    }
}
