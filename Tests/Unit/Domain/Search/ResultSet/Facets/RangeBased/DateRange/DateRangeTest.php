<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet\Facets\RangeBased\DateRange;

use ApacheSolrForTypo3\Solr\System\Data\DateTime;
use DateInterval;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRange;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRangeFacet;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DateRangeTest extends UnitTestCase
{

    #[Test]
    public function itCanBeSerializedToJSONWhenAllDataIsPresent(): void
    {
        $facetMock = $this->getMockBuilder(DateRangeFacet::class)
            ->disableOriginalConstructor()
            ->getMock();

        $key = uniqid('key_');

        $documentCount = rand(0, 999);
        $url = sprintf('https://www.example.com/%s', $key);

        $startRequested = (new DateTime())->sub(new DateInterval('P1W'));
        $endRequested = (new DateTime())->add(new DateInterval('P1W'));

        $startInResponse = (new DateTime())->sub(new DateInterval('P1D'));
        $endInResponse = (new DateTime())->add(new DateInterval('P1D'));

        $subject = $this->getMockBuilder(DateRange::class)
            ->setConstructorArgs(
                [
                    $facetMock,
                    $startRequested,
                    $endRequested,
                    $startInResponse,
                    $endInResponse,
                    '',
                    $documentCount,
                    [],
                    true
                ]
            )
            ->onlyMethods(['getFacetItemUrl'])
            ->getMock();

        $subject->method('getFacetItemUrl')->willReturn($url);

        $jsonString = json_encode($subject);
        self::assertIsString($jsonString);

        $jsonData = json_decode($jsonString, true);
        self::assertIsArray($jsonData);

        self::assertArrayHasKey('selected', $jsonData);
        self::assertTrue($jsonData['selected']);
        self::assertEquals(true, $jsonData['selected']);

        self::assertArrayHasKey('start', $jsonData);
        self::assertEquals($startRequested->getTimestamp(), $jsonData['start'], 'start time in request not correct');

        self::assertArrayHasKey('end', $jsonData);
        self::assertEquals($endRequested->getTimestamp(), $jsonData['end'], 'end time in request not correct');

        self::assertArrayHasKey('min', $jsonData);
        self::assertEquals($startInResponse->getTimestamp(), $jsonData['min'], 'min time in response not correct');

        self::assertArrayHasKey('max', $jsonData);
        self::assertEquals($endInResponse->getTimestamp(), $jsonData['max'], 'max time in response not correct');

        self::assertArrayHasKey('links', $jsonData);
        self::assertArrayHasKey('self', $jsonData['links']);
        self::assertEquals($url, $jsonData['links']['self']);
    }

    #[Test]
    public function itCanBeSerializedToJSONWhenOptionalDataIsMissing(): void
    {
        $url = sprintf('https://www.example.com/%s', uniqid('key_'));

        $facetMock = $this->getMockBuilder(DateRangeFacet::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject = $this->getMockBuilder(DateRange::class)
            ->setConstructorArgs([$facetMock, null, null, null, null, '', 0, []])
            ->onlyMethods(['getFacetItemUrl'])
            ->getMock();

        $subject->method('getFacetItemUrl')->willReturn($url);

        $jsonString = json_encode($subject);
        self::assertIsString($jsonString);

        $jsonData = json_decode($jsonString, true);
        self::assertIsArray($jsonData);

        self::assertArrayHasKey('selected', $jsonData);
        self::assertEquals(false, $jsonData['selected']);

        self::assertArrayHasKey('start', $jsonData);
        self::assertEquals('', $jsonData['start'], 'start time in request not correct');

        self::assertArrayHasKey('end', $jsonData);
        self::assertEquals('', $jsonData['end'], 'end time in request not correct');

        self::assertArrayHasKey('min', $jsonData);
        self::assertEquals('', $jsonData['min'], 'min time in response not correct');

        self::assertArrayHasKey('max', $jsonData);
        self::assertEquals('', $jsonData['max'], 'max time in response not correct');

        self::assertArrayHasKey('links', $jsonData);
        self::assertArrayHasKey('self', $jsonData['links']);
        self::assertEquals($url, $jsonData['links']['self']);
    }
}

