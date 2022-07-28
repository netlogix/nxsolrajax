<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet;

use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SearchResult;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class SearchResultTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function itCanBeSerializedToJSON()
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

    /**
     * @test
     * @return void
     */
    public function itReturnsHighlightedContentForContent()
    {
        $data = ['highlightedContent' => uniqid('highlightedContent_')];

        $subject = new SearchResult($data, [], []);

        self::assertEquals($data['highlightedContent'], $subject->getContent());
    }

    /**
     * @test
     * @return void
     */
    public function itReturnsAbstractForContent()
    {
        $data = ['abstract' => uniqid('abstract_')];

        $subject = new SearchResult($data, [], []);

        self::assertEquals($data['abstract'], $subject->getContent());
    }

    /**
     * @test
     * @return void
     */
    public function itPrefersHighlightedContentForContent()
    {
        $data = [
            'highlightedContent' => uniqid('highlightedContent_'),
            'abstract' => uniqid('abstract_')
        ];

        $subject = new SearchResult($data, [], []);

        self::assertEquals($data['highlightedContent'], $subject->getContent());
    }
}
