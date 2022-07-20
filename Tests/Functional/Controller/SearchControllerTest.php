<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Functional\Controller;

use ApacheSolrForTypo3\Solr\Domain\Search\Suggest\SuggestService;
use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use Netlogix\Nxsolrajax\Controller\SearchController;
use Netlogix\Nxsolrajax\SugesstionResultModifier;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class SearchControllerTest extends FunctionalTestCase
{

    protected $testExtensionsToLoad = ['typo3conf/ext/solr', 'typo3conf/ext/nxsolrajax'];

    /**
     * @test
     * @return void
     */
    public function suggestActionsThrowsExceptionIfHookDoesNotImplementInterface()
    {
        $this->expectExceptionCode(1533224243);

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nxsolrajax']['modifySuggestions'][] = \stdClass::class;

        $subject = new SearchController();

        $request = new Request();
        $request->setArgument('q', uniqid('query_'));
        $this->inject($subject, 'request', $request);

        $mockSuggestService = $this->getMockBuilder(SuggestService::class)
            ->disableOriginalConstructor()
            ->getMock();
        GeneralUtility::addInstance(SuggestService::class, $mockSuggestService);

        $mockTSFE = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $mockTSFE->id = (string)rand(1, 9999);
        $this->inject($subject, 'typoScriptFrontendController', $mockTSFE);

        $typoscriptConfiguration = new TypoScriptConfiguration([], (int)$mockTSFE->id);
        $this->inject($subject, 'typoScriptConfiguration', $typoscriptConfiguration);

        $subject->suggestAction();
    }

    /**
     * @test
     * @return void
     */
    public function suggestActionsCallsHookWithSuggestResults()
    {
        $query = uniqid('query_');

        $mockTSFE = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $mockTSFE->id = (string)rand(1, 9999);
        $typoscriptConfiguration = new TypoScriptConfiguration([], (int)$mockTSFE->id);

        $hookMock = $this->getMockBuilder(SugesstionResultModifier::class)
            ->onlyMethods(['modifySuggestions'])
            ->getMock();
        $hookMock
            ->expects(self::once())
            ->method('modifySuggestions')
            ->with($query, [], $typoscriptConfiguration);
        GeneralUtility::addInstance(get_class($hookMock), $hookMock);

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nxsolrajax']['modifySuggestions'][] = get_class($hookMock);

        $subject = new SearchController();

        $request = new Request();
        $request->setArgument('q', $query);
        $this->inject($subject, 'request', $request);

        $mockSuggestService = $this->getMockBuilder(SuggestService::class)
            ->disableOriginalConstructor()
            ->getMock();
        GeneralUtility::addInstance(SuggestService::class, $mockSuggestService);


        $this->inject($subject, 'typoScriptFrontendController', $mockTSFE);


        $this->inject($subject, 'typoScriptConfiguration', $typoscriptConfiguration);

        $this->inject($subject, 'response', new Response());

        $subject->suggestAction();
    }

    /**
     * @test
     * @dataProvider suggestServiceDataProvider
     * @return void
     */
    public function suggestActionsReturnsResultsInJSON(array $suggestResult, array $expectedResult)
    {
        $subject = $this->getMockBuilder(SearchController::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $request = new Request();
        $request->setArgument('q', uniqid('query_'));
        $this->inject($subject, 'request', $request);

        $response = $this->getMockBuilder(Response::class)
            ->onlyMethods(['setHeader'])
            ->getMock();
        $response->expects(self::once())->method('setHeader')->with(
            'Content-Type',
            'application/json; charset=utf-8',
            true
        );
        $this->inject($subject, 'response', $response);

        $mockTSFE = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $mockTSFE->id = (string)rand(1, 9999);
        $this->inject($subject, 'typoScriptFrontendController', $mockTSFE);

        $typoscriptConfiguration = new TypoScriptConfiguration([], (int)$mockTSFE->id);
        $this->inject($subject, 'typoScriptConfiguration', $typoscriptConfiguration);


        $mockSuggestService = $this->getMockBuilder(SuggestService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSuggestions'])
            ->getMock();

        $mockSuggestService->method('getSuggestions')->willReturn($suggestResult);

        GeneralUtility::addInstance(SuggestService::class, $mockSuggestService);

        $res = $subject->suggestAction();

        self::assertIsString($res);

        $json = json_decode($res, true);
        self::assertEquals(JSON_ERROR_NONE, json_last_error());
        self::assertIsArray($json);

        self::assertEquals($expectedResult, $json);
    }

    public function suggestServiceDataProvider(): array
    {
        $query = uniqid('query_');
        $suggestResult = [
            'suggestions' => [
                $query . ' ' . uniqid('suggestion_') => rand(1, 999)
            ],
            'suggestion' => $query
        ];

        return [
            'single suggest result' => [
                $suggestResult,
                [
                    [
                        'count' => current($suggestResult['suggestions']),
                        'name' => current(array_keys($suggestResult['suggestions']))
                    ]
                ]
            ]
        ];
    }
}