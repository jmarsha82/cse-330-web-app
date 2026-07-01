<?php

declare(strict_types=1);

namespace WustlNews;

const CATEGORIES = [
    'Politics',
    'Sports',
    'Entertainment',
    'World',
    'Technology',
];

function escape_html(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_valid_account_field(?string $value): bool
{
    return is_string($value) && preg_match('/^[A-Za-z0-9_-]{1,64}$/', $value) === 1;
}

function is_valid_story_text(?string $value, int $maxLength = 10000): bool
{
    if (!is_string($value)) {
        return false;
    }

    $trimmed = trim($value);

    return $trimmed !== ''
        && strlen($trimmed) <= $maxLength
        && preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value) !== 1;
}

function normalize_category(?string $category): string
{
    if (!is_string($category)) {
        return 'All';
    }

    if (strcasecmp($category, 'All') === 0) {
        return 'All';
    }

    foreach (CATEGORIES as $allowedCategory) {
        if (strcasecmp($category, $allowedCategory) === 0) {
            return $allowedCategory;
        }
    }

    return 'All';
}

function is_valid_optional_url(?string $url): bool
{
    if ($url === null || trim($url) === '') {
        return true;
    }

    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function require_valid_csrf_token(?string $sessionToken, ?string $requestToken): void
{
    if (!is_string($sessionToken) || !is_string($requestToken) || !hash_equals($sessionToken, $requestToken)) {
        die('<p class="error">Request forgery detected!</p>');
    }
}
