<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet\Facets\RangeBased\DateRange;

use ApacheSolrForTypo3\Solr\System\Data\DateTime;
use DateInterval;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRange;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRangeFacet;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DateRangeTest extends UnitTestCase
{
    #[Test]
    public function itCanBeSerializedToJSONWhenAllDataIsPresent(): void
    {
        $facetMock = $this->createStub(DateRangeFacet::class);

        $key = uniqid('key_');

        $documentCount = random_int(0, 999);
        $url = sprintf('https://www.example.com/%s', $key);

        $startRequested = (new DateTime())->sub(new DateInterval('P1W'));
        $endRequested = (new DateTime())->add(new DateInterval('P1W'));

        $startInResponse = (new DateTime())->sub(new DateInterval('P1D'));
        $endInResponse = (new DateTime())->add(new DateInterval('P1D'));

        $subject = $this->getMockBuilder(DateRange::class)
            ->setConstructorArgs([
                $facetMock,
                $startRequested,
                $endRequested,
                $startInResponse,
                $endInResponse,
                '',
                $documentCount,
                [],
                true,
            ])
            ->onlyMethods(['getFacetItemUrl'])
            ->getMock();

        $subject->method('getFacetItemUrl')->willReturn($url);

        $jsonString = json_encode($subject);
        $this->assertIsString($jsonString);

        $jsonData = json_decode($jsonString, true);
        $this->assertIsArray($jsonData);

        $this->assertArrayHasKey('selected', $jsonData);
        $this->assertTrue($jsonData['selected']);
        $this->assertEquals(true, $jsonData['selected']);

        $this->assertArrayHasKey('start', $jsonData);
        $this->assertEquals(
            $startRequested->getTimestamp(),
            $jsonData['start'],
            'start time in request not correct',
        );

        $this->assertArrayHasKey('end', $jsonData);
        $this->assertEquals($endRequested->getTimestamp(), $jsonData['end'], 'end time in request not correct');

        $this->assertArrayHasKey('min', $jsonData);
        $this->assertEquals(
            $startInResponse->getTimestamp(),
            $jsonData['min'],
            'min time in response not correct',
        );

        $this->assertArrayHasKey('max', $jsonData);
        $this->assertEquals($endInResponse->getTimestamp(), $jsonData['max'], 'max time in response not correct');

        $this->assertArrayHasKey('links', $jsonData);
        $this->assertArrayHasKey('self', $jsonData['links']);
        $this->assertEquals($url, $jsonData['links']['self']);
    }

    #[Test]
    public function itCanBeSerializedToJSONWhenOptionalDataIsMissing(): void
    {
        $url = sprintf('https://www.example.com/%s', uniqid('key_'));

        $facetMock = $this->createStub(DateRangeFacet::class);

        $subject = $this->getMockBuilder(DateRange::class)
            ->setConstructorArgs([$facetMock, null, null, null, null, '', 0, []])
            ->onlyMethods(['getFacetItemUrl'])
            ->getMock();

        $subject->method('getFacetItemUrl')->willReturn($url);

        $jsonString = json_encode($subject);
        $this->assertIsString($jsonString);

        $jsonData = json_decode($jsonString, true);
        $this->assertIsArray($jsonData);

        $this->assertArrayHasKey('selected', $jsonData);
        $this->assertEquals(false, $jsonData['selected']);

        $this->assertArrayHasKey('start', $jsonData);
        $this->assertEquals('', $jsonData['start'], 'start time in request not correct');

        $this->assertArrayHasKey('end', $jsonData);
        $this->assertEquals('', $jsonData['end'], 'end time in request not correct');

        $this->assertArrayHasKey('min', $jsonData);
        $this->assertEquals('', $jsonData['min'], 'min time in response not correct');

        $this->assertArrayHasKey('max', $jsonData);
        $this->assertEquals('', $jsonData['max'], 'max time in response not correct');

        $this->assertArrayHasKey('links', $jsonData);
        $this->assertArrayHasKey('self', $jsonData['links']);
        $this->assertEquals($url, $jsonData['links']['self']);
    }
}
