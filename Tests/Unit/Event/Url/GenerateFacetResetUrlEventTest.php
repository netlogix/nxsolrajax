<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Event\Url;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\AbstractFacet;
use Netlogix\Nxsolrajax\Event\Url\GenerateFacetResetUrlEvent;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class GenerateFacetResetUrlEventTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function itExposesFacet(): void
    {
        $facetMock = $this->createStub(AbstractFacet::class);

        $subject = new GenerateFacetResetUrlEvent($facetMock, '');

        $this->assertEquals($facetMock, $subject->facet);
    }

    #[Test]
    public function itExposesUrl(): void
    {
        $url = uniqid('https://www.example.com/');

        $facetMock = $this->createStub(AbstractFacet::class);

        $subject = new GenerateFacetResetUrlEvent($facetMock, $url);

        $this->assertSame($url, $subject->getUrl());
    }

    #[Test]
    public function itAllowsManipulationOfUrl(): void
    {
        $url1 = uniqid('https://www.example.com/');
        $url2 = uniqid('https://www.example.com/');

        $facetMock = $this->createStub(AbstractFacet::class);

        $subject = new GenerateFacetResetUrlEvent($facetMock, $url1);

        $subject->setUrl($url2);

        $this->assertSame($url2, $subject->getUrl());
    }
}
