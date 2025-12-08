<?php
namespace App\Core;

class EnvLoader
{
    /**
     * Load environment variables from .env using phpdotenv if available.
     * Falls back to a minimal parser.
     * @param string $basePath project root (where .env lives)
     */
    public static function load(string $basePath): void
    {
        // Prefer vlucas/phpdotenv when available. Use string class name to
        // avoid static analyzer false-positives when vendor isn't installed yet.
        if (class_exists('Dotenv\\Dotenv')) {
            try {
                $dotenv = call_user_func(['Dotenv\\Dotenv', 'createImmutable'], $basePath);
                if (is_object($dotenv) && method_exists($dotenv, 'safeLoad')) {
                    $dotenv->safeLoad();
                }
                return;
            } catch (\Throwable $e) {
                // fall through to fallback parser
            }
        }

        // Minimal .env parser fallback
        $envFile = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.env';
        if (!file_exists($envFile) || !is_readable($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $trim = trim($line);
            if ($trim === '' || strpos($trim, '#') === 0) {
                continue;
            }
            if (strpos($trim, '=') === false) {
                continue;
            }
            list($key, $value) = explode('=', $trim, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove surrounding quotes if present
            // Remove surrounding quotes if present (compatible with PHP 7.4)
            if ((self::startsWith($value, '"') && self::endsWith($value, '"')) || (self::startsWith($value, "'") && self::endsWith($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            if ($key === '') continue;
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
            }
            if (getenv($key) === false) {
                putenv(sprintf('%s=%s', $key, $value));
            }
        }
    }

    /**
     * PHP 7.4-compatible startsWith
     */
    private static function startsWith(string $haystack, string $needle): bool
    {
        if ($needle === '') return true;
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    /**
     * PHP 7.4-compatible endsWith
     */
    private static function endsWith(string $haystack, string $needle): bool
    {
        if ($needle === '') return true;
        $len = strlen($needle);
        if ($len === 0) return true;
        return substr($haystack, -$len) === $needle;
    }
}


