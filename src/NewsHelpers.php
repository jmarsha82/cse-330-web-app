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

const CATEGORY_DETAILS = [
    'Politics' => ['label' => 'Politics', 'class' => 'category-politics'],
    'Sports' => ['label' => 'Sports', 'class' => 'category-sports'],
    'Entertainment' => ['label' => 'Arts', 'class' => 'category-entertainment'],
    'World' => ['label' => 'World', 'class' => 'category-world'],
    'Technology' => ['label' => 'Tech', 'class' => 'category-technology'],
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

function category_css_class(?string $category): string
{
    $normalizedCategory = normalize_category($category);

    if ($normalizedCategory === 'All') {
        return 'category-all';
    }

    return CATEGORY_DETAILS[$normalizedCategory]['class'];
}

function category_display_label(?string $category): string
{
    $normalizedCategory = normalize_category($category);

    if ($normalizedCategory === 'All') {
        return 'All';
    }

    return CATEGORY_DETAILS[$normalizedCategory]['label'];
}

function excerpt(?string $value, int $maxLength = 220): string
{
    $normalized = trim((string) preg_replace('/\s+/', ' ', (string) $value));

    if (strlen($normalized) <= $maxLength) {
        return $normalized;
    }

    return rtrim(substr($normalized, 0, $maxLength - 1)) . '...';
}

function reading_minutes(?string $value, int $wordsPerMinute = 220): int
{
    $wordCount = str_word_count((string) $value);

    return max(1, (int) ceil($wordCount / $wordsPerMinute));
}

/**
 * @return list<array<string, int|string|null>>
 */
function demo_stories(): array
{
    return [
        [
            'story_id' => 101,
            'title' => 'Students Map Late-Night Study Spaces Across Campus',
            'category' => 'Technology',
            'uploaded_by_user' => 'campusdesk',
            'date_uploaded' => '2026-06-30 21:14:00-05:00',
            'content' => 'A student-built map now shows open study rooms, outlet density, quiet ratings, and late-night food options near each library. The team says the next release will add crowd reports and accessibility notes submitted by students.',
            'url' => 'https://example.com/study-map',
            'comment_count' => 8,
        ],
        [
            'story_id' => 102,
            'title' => 'Bear Sports Preview: Summer Training Notes',
            'category' => 'Sports',
            'uploaded_by_user' => 'redzone',
            'date_uploaded' => '2026-06-29 17:45:00-05:00',
            'content' => 'Coaches highlighted depth in midfield, a faster transition game, and several first-year athletes who made an early impression during voluntary summer sessions.',
            'url' => null,
            'comment_count' => 4,
        ],
        [
            'story_id' => 103,
            'title' => 'Student Union Debates Transit Funding Proposal',
            'category' => 'Politics',
            'uploaded_by_user' => 'policywatch',
            'date_uploaded' => '2026-06-28 09:30:00-05:00',
            'content' => 'Representatives discussed a proposal to expand evening shuttle frequency and pilot a weekend route connecting off-campus apartments with major study hubs.',
            'url' => null,
            'comment_count' => 12,
        ],
        [
            'story_id' => 104,
            'title' => 'Gallery Walk Brings Local Artists Into Residence Halls',
            'category' => 'Entertainment',
            'uploaded_by_user' => 'artsbeat',
            'date_uploaded' => '2026-06-27 19:20:00-05:00',
            'content' => 'The pop-up gallery series will rotate through four residence halls this fall, pairing student curators with St. Louis artists for small-format exhibitions and workshops.',
            'url' => 'https://example.com/gallery-walk',
            'comment_count' => 6,
        ],
        [
            'story_id' => 105,
            'title' => 'International Center Expands Peer Mentoring Program',
            'category' => 'World',
            'uploaded_by_user' => 'globalnotes',
            'date_uploaded' => '2026-06-26 13:05:00-05:00',
            'content' => 'The expanded program will match incoming international students with trained peer mentors by school, language interest, and academic goals before move-in weekend.',
            'url' => null,
            'comment_count' => 3,
        ],
    ];
}

/**
 * @return list<array<string, int|string>>
 */
function demo_comments(int $storyId): array
{
    $comments = [
        101 => [
            [
                'comment_id' => 1,
                'user' => 'libraryfan',
                'time' => '2026-06-30 22:03:00-05:00',
                'comment_text' => 'The outlet density score is exactly the kind of practical detail I wish every campus tool had.',
            ],
            [
                'comment_id' => 2,
                'user' => 'nightowl',
                'time' => '2026-06-30 22:18:00-05:00',
                'comment_text' => 'Would love to see coffee hours folded into this before finals.',
            ],
        ],
        103 => [
            [
                'comment_id' => 3,
                'user' => 'commuter',
                'time' => '2026-06-28 10:11:00-05:00',
                'comment_text' => 'Weekend routing would make a huge difference for students without cars.',
            ],
        ],
    ];

    return $comments[$storyId] ?? [
        [
            'comment_id' => 99,
            'user' => 'demo_reader',
            'time' => '2026-06-30 12:00:00-05:00',
            'comment_text' => 'Demo comments appear here until the app is connected to MySQL.',
        ],
    ];
}

/**
 * @param list<array<string, int|string|null>> $stories
 * @return list<array<string, int|string|null>>
 */
function filter_stories_by_category(array $stories, string $category): array
{
    if ($category === 'All') {
        return $stories;
    }

    return array_values(array_filter(
        $stories,
        static fn (array $story): bool => ($story['category'] ?? null) === $category
    ));
}

/**
 * @param list<array<string, int|string|null>> $stories
 * @return array<string, int|string|null>|null
 */
function find_story(array $stories, int $storyId): ?array
{
    foreach ($stories as $story) {
        if ((int) $story['story_id'] === $storyId) {
            return $story;
        }
    }

    return null;
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
