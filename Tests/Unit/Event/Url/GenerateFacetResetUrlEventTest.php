<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Event\Url;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\AbstractFacet;
use Netlogix\Nxsolrajax\Event\Url\GenerateFacetResetUrlEvent;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class GenerateFacetResetUrlEventTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function itExposesFacet()
    {
        $facetMock = $this->getMockBuilder(AbstractFacet::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject = new GenerateFacetResetUrlEvent($facetMock, '');

        self::assertEquals($facetMock, $subject->getFacet());
    }

    /**
     * @test
     * @return void
     */
    public function itExposesUrl()
    {
        $url = uniqid('https://www.example.com/');

        $facetMock = $this->getMockBuilder(AbstractFacet::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject = new GenerateFacetResetUrlEvent($facetMock, $url);

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

        $facetMock = $this->getMockBuilder(AbstractFacet::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject = new GenerateFacetResetUrlEvent($facetMock, $url1);

        $subject->setUrl($url2);

        self::assertEquals($url2, $subject->getUrl());
    }
}