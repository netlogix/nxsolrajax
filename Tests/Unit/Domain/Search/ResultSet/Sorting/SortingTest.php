<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet\Sorting;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Sorting\Sorting as SolrSorting;
use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequest;
use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Sorting\Sorting;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SortingTest extends UnitTestCase
{

    #[Test]
    public function itCanBeSerializedToJson(): void
    {
        $name = uniqid('name');
        $field = uniqid('field');
        $direction = SolrSorting::DIRECTION_ASC;
        $label = uniqid('label');

        $searchUriBuilder = $this->getMockBuilder(SearchUriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $searchUriBuilder->expects($this->once())->method('getSetSortingUri');
        $searchResultSet = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchResultSet->method('getUsedSearchRequest')->willReturn(new SearchRequest());

        $subject = new Sorting(
            resultSet: $searchResultSet,
            name: $name,
            field: $field,
            direction: $direction,
            label: $label,
        );
        $subject->setSearchUriBuilder($searchUriBuilder);

        $jsonString = json_encode($subject);
        $this->assertIsString($jsonString);

        $jsonData = json_decode($jsonString, true);
        $this->assertIsArray($jsonData);

        $this->assertArrayHasKey('label', $jsonData);
        $this->assertEquals($label, $jsonData['label']);

//        self::assertArrayHasKey('url', $jsonData);
//        self::assertEquals($resultsPerPage, $jsonData['url']);

        $this->assertArrayHasKey('direction', $jsonData);
        $this->assertEquals($direction, $jsonData['direction']);

        $this->assertArrayHasKey('resetOption', $jsonData);
        $this->assertEquals(false, $jsonData['resetOption']);

        $this->assertArrayHasKey('selected', $jsonData);
        $this->assertEquals(false, $jsonData['selected']);
    }

    #[Test]
    public function generateRemoveSortingUri(): void
    {
        $searchUriBuilder = $this->getMockBuilder(SearchUriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchUriBuilder->expects($this->once())->method('getRemoveSortingUri')->with(
            previousSearchRequest: self::isInstanceOf(SearchRequest::class)
        );
        $searchUriBuilder->expects($this->once())->method('getRemoveSortingUri');

        $searchResultSet = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchResultSet->method('getUsedSearchRequest')->willReturn(new SearchRequest());

        $subject = new Sorting(
            resultSet: $searchResultSet,
            name: uniqid('name'),
            field: uniqid('field'),
            direction: SolrSorting::DIRECTION_ASC,
            label: uniqid('label'),
            selected: false,
            isResetOption: true,
        );
        $subject->setSearchUriBuilder($searchUriBuilder);

        $subject->getUrl();
    }

    #[Test]
    public function generateSoringUriWithOppositeDirectionInSelection(): void
    {
        $searchUriBuilder = $this->getMockBuilder(SearchUriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchUriBuilder->expects($this->once())->method('getSetSortingUri')->with(
            previousSearchRequest: self::isInstanceOf(SearchRequest::class),
            sortingName: self::isType('string'),
            sortingDirection: SolrSorting::DIRECTION_DESC,
        );

        $searchResultSet = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchResultSet->method('getUsedSearchRequest')->willReturn(new SearchRequest());

        $subject = new Sorting(
            resultSet: $searchResultSet,
            name: uniqid('name'),
            field: uniqid('field'),
            direction: SolrSorting::DIRECTION_ASC,
            label: uniqid('label'),
            selected: true,
            isResetOption: false,
        );
        $subject->setSearchUriBuilder($searchUriBuilder);

        $subject->getUrl();
    }
}
