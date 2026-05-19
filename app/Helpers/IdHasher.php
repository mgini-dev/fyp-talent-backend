<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Crypt;

class IdHasher
{
    /**
     * Encrypt an integer ID to a URL-safe string.
     */
    public static function encode($id)
    {
        if (empty($id) || !is_numeric($id)) {
            return $id;
        }

        try {
            $encrypted = Crypt::encryptString((string)$id);
            return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($encrypted));
        } catch (\Exception $e) {
            return $id;
        }
    }

    /**
     * Decrypt a URL-safe string back to the original integer ID.
     */
    public static function decode($encoded)
    {
        if (empty($encoded) || is_numeric($encoded)) {
            return $encoded;
        }

        try {
            $base64 = str_replace(['-', '_'], ['+', '/'], $encoded);
            $rem = strlen($base64) % 4;
            if ($rem) {
                $base64 .= str_repeat('=', 4 - $rem);
            }
            $encrypted = base64_decode($base64);
            return (int) Crypt::decryptString($encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }
}
