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

        $searchUriBuilder->expects(self::once())->method('getSetSortingUri');
        $searchResultSet = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchResultSet->method('getUsedSearchRequest')->willReturn(new SearchRequest());

        GeneralUtility::addInstance(SearchUriBuilder::class, $searchUriBuilder);

        $subject = new Sorting(
            resultSet: $searchResultSet,
            name: $name,
            field: $field,
            direction: $direction,
            label: $label,
        );

        $jsonString = json_encode($subject);
        self::assertIsString($jsonString);

        $jsonData = json_decode($jsonString, true);
        self::assertIsArray($jsonData);

        self::assertArrayHasKey('label', $jsonData);
        self::assertEquals($label, $jsonData['label']);

//        self::assertArrayHasKey('url', $jsonData);
//        self::assertEquals($resultsPerPage, $jsonData['url']);

        self::assertArrayHasKey('direction', $jsonData);
        self::assertEquals($direction, $jsonData['direction']);

        self::assertArrayHasKey('resetOption', $jsonData);
        self::assertEquals(false, $jsonData['resetOption']);

        self::assertArrayHasKey('selected', $jsonData);
        self::assertEquals(false, $jsonData['selected']);
    }

    #[Test]
    public function generateRemoveSortingUri(): void
    {
        $searchUriBuilder = $this->getMockBuilder(SearchUriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchUriBuilder->expects(self::once())->method('getRemoveSortingUri')->with(
            previousSearchRequest: self::isInstanceOf(SearchRequest::class)
        );
        $searchUriBuilder->expects(self::once())->method('getRemoveSortingUri');

        $searchResultSet = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchResultSet->method('getUsedSearchRequest')->willReturn(new SearchRequest());

        GeneralUtility::addInstance(SearchUriBuilder::class, $searchUriBuilder);

        $subject = new Sorting(
            resultSet: $searchResultSet,
            name: uniqid('name'),
            field: uniqid('field'),
            direction: SolrSorting::DIRECTION_ASC,
            label: uniqid('label'),
            selected: false,
            isResetOption: true,
        );

        $subject->getUrl();
    }

    #[Test]
    public function generateSoringUriWithOppositeDirectionInSelection(): void
    {
        $searchUriBuilder = $this->getMockBuilder(SearchUriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchUriBuilder->expects(self::once())->method('getSetSortingUri')->with(
            previousSearchRequest: self::isInstanceOf(SearchRequest::class),
            sortingName: self::isType('string'),
            sortingDirection: SolrSorting::DIRECTION_DESC,
        );

        $searchResultSet = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchResultSet->method('getUsedSearchRequest')->willReturn(new SearchRequest());

        GeneralUtility::addInstance(SearchUriBuilder::class, $searchUriBuilder);

        $subject = new Sorting(
            resultSet: $searchResultSet,
            name: uniqid('name'),
            field: uniqid('field'),
            direction: SolrSorting::DIRECTION_ASC,
            label: uniqid('label'),
            selected: true,
            isResetOption: false,
        );

        $subject->getUrl();
    }
}
