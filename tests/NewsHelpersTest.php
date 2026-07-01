<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function WustlNews\escape_html;
use function WustlNews\category_css_class;
use function WustlNews\category_display_label;
use function WustlNews\demo_comments;
use function WustlNews\demo_stories;
use function WustlNews\excerpt;
use function WustlNews\filter_stories_by_category;
use function WustlNews\find_story;
use function WustlNews\is_valid_account_field;
use function WustlNews\is_valid_optional_url;
use function WustlNews\is_valid_story_text;
use function WustlNews\normalize_category;
use function WustlNews\reading_minutes;

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

    public function testCategoryPresentationHelpers(): void
    {
        self::assertSame('category-technology', category_css_class('techNOLogy'));
        self::assertSame('category-all', category_css_class('Weather'));
        self::assertSame('Arts', category_display_label('Entertainment'));
        self::assertSame('All', category_display_label(null));
    }

    public function testExcerptNormalizesWhitespaceAndTruncatesLongText(): void
    {
        self::assertSame('Campus launches today.', excerpt(" Campus\nlaunches   today. "));
        self::assertSame('Campus transit...', excerpt('Campus transit proposal advances', 18));
    }

    public function testReadingMinutesHasOneMinuteMinimum(): void
    {
        self::assertSame(1, reading_minutes('Short update.'));
        self::assertSame(2, reading_minutes(str_repeat('word ', 221)));
    }

    public function testDemoStoryCollectionSupportsFilteringAndLookup(): void
    {
        $stories = demo_stories();
        $technologyStories = filter_stories_by_category($stories, 'Technology');

        self::assertCount(5, $stories);
        self::assertCount(1, $technologyStories);
        self::assertSame('Technology', $technologyStories[0]['category']);
        $foundStory = find_story($stories, 103);
        self::assertNotNull($foundStory);
        self::assertSame('Student Union Debates Transit Funding Proposal', $foundStory['title']);
        self::assertNull(find_story($stories, 999));
    }

    public function testDemoCommentsReturnStorySpecificAndFallbackRows(): void
    {
        self::assertSame('libraryfan', demo_comments(101)[0]['user']);
        self::assertSame('demo_reader', demo_comments(999)[0]['user']);
    }
}
