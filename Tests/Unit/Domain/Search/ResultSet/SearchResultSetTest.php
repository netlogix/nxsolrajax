<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet;

use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SearchResultSet;
use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class SearchResultSetTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function itCanForceAddingFacetData()
    {
        /** @var SearchResultSet|MockObject|AccessibleMockObjectInterface $subject */
        $subject = $this->getAccessibleMock(SearchResultSet::class, ['getPage'], [], '', false);
        $subject->method('getPage')->willReturn(0);

        self::assertFalse($subject->_call('shouldAddFacetData'));

        $subject->forceAddFacetData(true);

        self::assertTrue($subject->_call('shouldAddFacetData'));
    }

    /**
     * @test
     * @return void
     */
    public function itWillAddFacetDataIfHasResults()
    {
        /** @var SearchResultSet|MockObject|AccessibleMockObjectInterface $subject */
        $subject = $this->getAccessibleMock(SearchResultSet::class, ['getPage'], [], '', false);

        self::assertFalse($subject->_call('shouldAddFacetData'));

        $subject->method('getPage')->willReturn(1);

        self::assertTrue($subject->_call('shouldAddFacetData'));
    }
}