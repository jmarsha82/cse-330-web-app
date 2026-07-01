<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function WustlNews\escape_html;
use function WustlNews\is_valid_account_field;
use function WustlNews\is_valid_optional_url;
use function WustlNews\is_valid_story_text;
use function WustlNews\normalize_category;

final class NewsHelpersTest extends TestCase
{
    public function testEscapeHtmlEscapesQuotesAndTags(): void
    {
        self::assertSame('&lt;script&gt;alert(&#039;x&#039;)&lt;/script&gt;', escape_html("<script>alert('x')</script>"));
    }

    #[DataProvider('accountFieldProvider')]
    public function testAccountFieldValidation(?string $value, bool $expected): void
    {
        self::assertSame($expected, is_valid_account_field($value));
    }

    /**
     * @return array<string, array{0: ?string, 1: bool}>
     */
    public static function accountFieldProvider(): array
    {
        return [
            'letters numbers underscores and hyphens' => ['washu_user-330', true],
            'empty' => ['', false],
            'space' => ['washu user', false],
            'html' => ['<admin>', false],
            'too long' => [str_repeat('a', 65), false],
            'null' => [null, false],
        ];
    }

    public function testStoryTextAllowsNormalSentencesAndRejectsEmptyText(): void
    {
        self::assertTrue(is_valid_story_text('Campus launches a new project today.'));
        self::assertTrue(is_valid_story_text("Line one\nLine two"));
        self::assertFalse(is_valid_story_text('   '));
        self::assertFalse(is_valid_story_text("Bad\x00Text"));
    }

    public function testStoryTextHonorsLengthLimit(): void
    {
        self::assertTrue(is_valid_story_text('short', 5));
        self::assertFalse(is_valid_story_text('too long', 5));
    }

    #[DataProvider('categoryProvider')]
    public function testNormalizeCategory(?string $category, string $expected): void
    {
        self::assertSame($expected, normalize_category($category));
    }

    /**
     * @return array<string, array{0: ?string, 1: string}>
     */
    public static function categoryProvider(): array
    {
        return [
            'canonical category' => ['Technology', 'Technology'],
            'lowercase category' => ['sports', 'Sports'],
            'all filter' => ['All', 'All'],
            'unknown category falls back to all' => ['Weather', 'All'],
            'missing category falls back to all' => [null, 'All'],
        ];
    }

    public function testOptionalUrlValidation(): void
    {
        self::assertTrue(is_valid_optional_url(null));
        self::assertTrue(is_valid_optional_url(''));
        self::assertTrue(is_valid_optional_url('https://example.com/story'));
        self::assertFalse(is_valid_optional_url('not a url'));
    }
}
