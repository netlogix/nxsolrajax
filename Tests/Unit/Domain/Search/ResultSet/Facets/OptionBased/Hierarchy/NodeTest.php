<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\HierarchyFacet;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\LinkHelper\SelfLinkHelperInterface;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\Node;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class NodeTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function itCanBuildUrlFromLinkHelper()
    {
        $linkHelperMock = $this->getMockBuilder(SelfLinkHelperInterface::class)
            ->onlyMethods(['canHandleSelfLink', 'renderSelfLink'])
            ->getMock();

        $linkHelperMock->method('canHandleSelfLink')->willReturn(true);

        $link = sprintf('https://www.example.com/%s', uniqid());

        $linkHelperMock->method('renderSelfLink')->willReturn($link);

        GeneralUtility::addInstance(get_class($linkHelperMock), $linkHelperMock);

        $facetMock = $this->getMockBuilder(HierarchyFacet::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfiguration'])
            ->getMock();
        $facetMock->method('getConfiguration')->willReturn(['linkHelper' => get_class($linkHelperMock)]);


        $subject = new Node($facetMock, null, uniqid('key_'), '', uniqid('value_'), 0, true);

        $res = $subject->getUrl();

        self::assertEquals($link, $res);
    }


    /**
     * @test
     * @return void
     */
    public function itCanDetermineOwnActiveStateIfSelected()
    {
        $facetMock = $this->getMockBuilder(HierarchyFacet::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject = new Node($facetMock, null, uniqid('key_'), '', uniqid('value_'), 0, true);

        self::assertTrue($subject->isActive());
    }

    /**
     * @test
     * @return void
     */
    public function itCanDetermineOwnActiveStateIfNotSelected()
    {
        $facetMock = $this->getMockBuilder(HierarchyFacet::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject = new Node($facetMock, null, uniqid('key_'), '', uniqid('value_'), 0, false);

        self::assertFalse($subject->isActive());
    }

    /**
     * @test
     * @return void
     */
    public function itCanDetermineOwnActiveStateIfOneChildIsActive()
    {
        $facetMock = $this->getMockBuilder(HierarchyFacet::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject = new Node($facetMock, null, uniqid('key_'), '', uniqid('value_'), 0, false);

        $subject->addChildNode(
            new Node($facetMock, $subject, uniqid('key_'), '', uniqid('value_'), 0, false)
        );
        $subject->addChildNode(
            new Node($facetMock, $subject, uniqid('key_'), '', uniqid('value_'), 0, true)
        );
        $subject->addChildNode(
            new Node($facetMock, $subject, uniqid('key_'), '', uniqid('value_'), 0, false)
        );

        self::assertTrue($subject->isActive());
    }

    /**
     * @test
     * @return void
     */
    public function itCanBeSerializedToJSON()
    {
        $facetMock = $this->getMockBuilder(HierarchyFacet::class)
            ->disableOriginalConstructor()
            ->getMock();

        $key = uniqid('key_');
        $value = uniqid('value_');
        $label = uniqid('lebel_');
        $count = rand(0, 999);
        $selected = $count % 2 == 0;
        $url = sprintf('https://www.example.com/%s', $key);

        $subject = $this->getMockBuilder(Node::class)
            ->setConstructorArgs([$facetMock, null, $key, $label, $value, $count, $selected])
            ->onlyMethods(['getUrl'])
            ->getMock();

        $subject->method('getUrl')->willReturn($url);

        $jsonString = json_encode($subject);
        self::assertIsString($jsonString);

        $jsonData = json_decode($jsonString, true);
        self::assertIsArray($jsonData);

        self::assertArrayHasKey('id', $jsonData);
        self::assertEquals($key, $jsonData['id']);

        self::assertArrayHasKey('label', $jsonData);
        self::assertEquals($label, $jsonData['label']);

        self::assertArrayHasKey('name', $jsonData);
        self::assertEquals($value, $jsonData['name']);

        self::assertArrayHasKey('count', $jsonData);
        self::assertEquals($count, $jsonData['count']);

        self::assertArrayHasKey('selected', $jsonData);
        self::assertEquals($selected, $jsonData['selected']);

        self::assertArrayHasKey('active', $jsonData);
        self::assertEquals($selected, $jsonData['active']);

        self::assertArrayHasKey('options', $jsonData);
        // no child data is set
        self::assertEmpty($jsonData['options']);

        self::assertArrayHasKey('links', $jsonData);
        self::assertArrayHasKey('self', $jsonData['links']);
        self::assertEquals($url, $jsonData['links']['self']);
    }

}
