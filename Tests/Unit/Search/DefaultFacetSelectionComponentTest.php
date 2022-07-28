<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Search;

use Netlogix\Nxsolrajax\Query\Modifier\DefaultFacetSelection;
use Netlogix\Nxsolrajax\Search\DefaultFacetSelectionComponent;
use Nimut\TestingFramework\TestCase\UnitTestCase;

use function PHPUnit\Framework\assertFalse;

class DefaultFacetSelectionComponentTest extends UnitTestCase
{

    /**
     * @test
     * @return void
     */
    public function itDoesNotAddHookIfFacetingIsNotEnabled()
    {
        self::assertFalse(isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['modifySearchQuery']['defaultFacetSelection']));

        $subject = new DefaultFacetSelectionComponent();

        $subject->initializeSearchComponent();

        self::assertFalse(isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['modifySearchQuery']['defaultFacetSelection']));
    }

    /**
     * @test
     * @return void
     */
    public function itAddsHookIfFacetingIsEnabled()
    {
        self::assertFalse(isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['modifySearchQuery']['defaultFacetSelection']));

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

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['modifySearchQuery']['defaultFacetSelection']);
    }

}

