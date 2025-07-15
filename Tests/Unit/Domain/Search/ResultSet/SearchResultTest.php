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
            'id' => (string)random_int(1, 9999999),
            'type' => uniqid('type_'),
            'title' => uniqid('title_'),
            'content' => uniqid('content_'),
            'abstract' => uniqid('abstract_'),
            'image' => uniqid('https://www.example.com/') . '.jpg',
            'url' => uniqid('https://www.example.com/')

        ];

        $subject = new SearchResult($data, [], []);

        $jsonString = json_encode($subject);
        $this->assertIsString($jsonString);

        $jsonData = json_decode($jsonString, true);
        $this->assertIsArray($jsonData);

        $this->assertArrayHasKey('id', $jsonData);
        $this->assertEquals($data['id'], $jsonData['id']);

        $this->assertArrayHasKey('type', $jsonData);
        $this->assertEquals($data['type'], $jsonData['type']);

        $this->assertArrayHasKey('title', $jsonData);
        $this->assertEquals($data['title'], $jsonData['title']);

        $this->assertArrayHasKey('image', $jsonData);
        $this->assertEquals($data['image'], $jsonData['image']);

        $this->assertArrayHasKey('url', $jsonData);
        $this->assertEquals($data['url'], $jsonData['url']);
    }

    #[Test]
    public function itReturnsHighlightedContentForContent(): void
    {
        $data = ['highlightedContent' => uniqid('highlightedContent_')];

        $subject = new SearchResult($data, [], []);

        $this->assertSame($data['highlightedContent'], $subject->getContent());
    }

    #[Test]
    public function itReturnsAbstractForContent(): void
    {
        $data = ['abstract' => uniqid('abstract_')];

        $subject = new SearchResult($data, [], []);

        $this->assertSame($data['abstract'], $subject->getContent());
    }

    #[Test]
    public function itPrefersHighlightedContentForContent(): void
    {
        $data = [
            'highlightedContent' => uniqid('highlightedContent_'),
            'abstract' => uniqid('abstract_')
        ];

        $subject = new SearchResult($data, [], []);

        $this->assertSame($data['highlightedContent'], $subject->getContent());
    }
}
