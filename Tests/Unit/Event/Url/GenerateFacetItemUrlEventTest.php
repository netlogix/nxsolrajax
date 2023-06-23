<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Event\Url;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\AbstractFacetItem;
use Netlogix\Nxsolrajax\Event\Url\GenerateFacetItemUrlEvent;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class GenerateFacetItemUrlEventTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function itExposesFacetItem(): void
    {
        $facetMock = $this->getMockBuilder(AbstractFacetItem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject = new GenerateFacetItemUrlEvent($facetMock, '');

        self::assertEquals($facetMock, $subject->facetItem);
    }

    #[Test]
    public function itExposesOverrideUriValue(): void
    {
        $overrideUriValue = uniqid('overrideUriValue_');

        $facetMock = $this->getMockBuilder(AbstractFacetItem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject = new GenerateFacetItemUrlEvent($facetMock, '', $overrideUriValue);

        self::assertEquals($overrideUriValue, $subject->overrideUriValue);
    }


    #[Test]
    public function itExposesUrl(): void
    {
        $url = uniqid('https://www.example.com/');

        $facetMock = $this->getMockBuilder(AbstractFacetItem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject = new GenerateFacetItemUrlEvent($facetMock, $url);

        self::assertEquals($url, $subject->getUrl());
    }

    #[Test]
    public function itAllowsManipulationOfUrl(): void
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
