<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet;

use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SearchResult;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SearchResultTest extends UnitTestCase
{

    #[Test]
    public function itCanBeSerializedToJSON(): void
    {
        $data = [
            'id' => (string)rand(1, 9999999),
            'type' => uniqid('type_'),
            'title' => uniqid('title_'),
            'content' => uniqid('content_'),
            'abstract' => uniqid('abstract_'),
            'image' => uniqid('https://www.example.com/') . '.jpg',
            'url' => uniqid('https://www.example.com/')

        ];

        $subject = new SearchResult($data, [], []);

        $jsonString = json_encode($subject);
        self::assertIsString($jsonString);

        $jsonData = json_decode($jsonString, true);
        self::assertIsArray($jsonData);

        self::assertArrayHasKey('id', $jsonData);
        self::assertEquals($data['id'], $jsonData['id']);

        self::assertArrayHasKey('type', $jsonData);
        self::assertEquals($data['type'], $jsonData['type']);

        self::assertArrayHasKey('title', $jsonData);
        self::assertEquals($data['title'], $jsonData['title']);

        self::assertArrayHasKey('image', $jsonData);
        self::assertEquals($data['image'], $jsonData['image']);

        self::assertArrayHasKey('url', $jsonData);
        self::assertEquals($data['url'], $jsonData['url']);
    }

    #[Test]
    public function itReturnsHighlightedContentForContent(): void
    {
        $data = ['highlightedContent' => uniqid('highlightedContent_')];

        $subject = new SearchResult($data, [], []);

        self::assertEquals($data['highlightedContent'], $subject->getContent());
    }

    #[Test]
    public function itReturnsAbstractForContent(): void
    {
        $data = ['abstract' => uniqid('abstract_')];

        $subject = new SearchResult($data, [], []);

        self::assertEquals($data['abstract'], $subject->getContent());
    }

    #[Test]
    public function itPrefersHighlightedContentForContent(): void
    {
        $data = [
            'highlightedContent' => uniqid('highlightedContent_'),
            'abstract' => uniqid('abstract_')
        ];

        $subject = new SearchResult($data, [], []);

        self::assertEquals($data['highlightedContent'], $subject->getContent());
    }
}
