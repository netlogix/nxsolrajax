<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Search;

use Netlogix\Nxsolrajax\Query\Modifier\DefaultFacetSelection;
use Netlogix\Nxsolrajax\Search\DefaultFacetSelectionComponent;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class DefaultFacetSelectionComponentTest extends UnitTestCase
{

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['modifySearchQuery']['defaultFacetSelection']);
    }

    /**
     * @test
     * @return void
     */
    public function itDoesNotAddHookIfFacetingIsNotEnabled()
    {
        self::assertEmpty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['modifySearchQuery']['defaultFacetSelection']);

        $subject = new DefaultFacetSelectionComponent();

        $subject->initializeSearchComponent();

        self::assertEmpty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['modifySearchQuery']['defaultFacetSelection']);
    }

    /**
     * @test
     * @return void
     */
    public function itAddsHookIfFacetingIsEnabled()
    {
        self::assertEmpty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['modifySearchQuery']['defaultFacetSelection']);

        $subject = new DefaultFacetSelectionComponent();

        $searchConfiguration = [
            'faceting' => 1,
            'faceting.' => []
        ];
        $subject->setSearchConfiguration($searchConfiguration);

        $subject->initializeSearchComponent();

        self::assertIsString(
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['modifySearchQuery']['defaultFacetSelection']
        );
        self::assertEquals(
            DefaultFacetSelection::class,
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['modifySearchQuery']['defaultFacetSelection']
        );
    }

}

