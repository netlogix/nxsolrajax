<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Event\Url;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\AbstractFacetItem;
use Netlogix\Nxsolrajax\Event\Url\GenerateFacetItemUrlEvent;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class GenerateFacetItemUrlEventTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function itExposesFacetItem()
    {
        $facetMock = $this->getMockBuilder(AbstractFacetItem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject = new GenerateFacetItemUrlEvent($facetMock, '');

        self::assertEquals($facetMock, $subject->getFacetItem());
    }

    /**
     * @test
     * @return void
     */
    public function itExposesOverrideUriValue()
    {
        $overrideUriValue = uniqid('overrideUriValue_');

        $facetMock = $this->getMockBuilder(AbstractFacetItem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject = new GenerateFacetItemUrlEvent($facetMock, '', $overrideUriValue);

        self::assertEquals($overrideUriValue, $subject->getOverrideUriValue());
    }


    /**
     * @test
     * @return void
     */
    public function itExposesUrl()
    {
        $url = uniqid('https://www.example.com/');

        $facetMock = $this->getMockBuilder(AbstractFacetItem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject = new GenerateFacetItemUrlEvent($facetMock, $url);

        self::assertEquals($url, $subject->getUrl());
    }

    /**
     * @test
     * @return void
     */
    public function itAllowsManipulationOfUrl()
    {
        $url1 = uniqid('https://www.example.com/');
        $url2 = uniqid('https://www.example.com/');

        $facetMock = $this->getMockBuilder(AbstractFacetItem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject = new GenerateFacetItemUrlEvent($facetMock, $url1);

        $subject->setUrl($url2);

        self::assertEquals($url2, $subject->getUrl());
    }
}