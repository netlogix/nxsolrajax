<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Controller;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use ApacheSolrForTypo3\Solr\Domain\Search\Suggest\SuggestService;
use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use ApacheSolrForTypo3\Solr\System\Solr\SolrUnavailableException;
use Netlogix\Nxsolrajax\Controller\SearchController;
use Netlogix\Nxsolrajax\SugesstionResultModifier;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class SearchControllerTest extends UnitTestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        unset($_SERVER['HTTP_ACCEPT'], $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nxsolrajax']['modifySuggestions']);

        GeneralUtility::purgeInstances();
    }

    /**
     * @test
     * @return void
     */
    public function indexActionAddsContentTypeForJSONRequests()
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $mockResponse = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setHeader'])
            ->getMock();
        $mockResponse->expects(self::once())->method('setHeader')->with(
            'Content-Type',
            'application/json; charset=utf-8',
            true
        );

        $tsfeMock = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['applyHttpHeadersToResponse'])
            ->getMock();
        $tsfeMock->expects(self::once())->method('applyHttpHeadersToResponse')->willReturn(
            new \TYPO3\CMS\Core\Http\Response()
        );

        $subject = $this->getMockBuilder(SearchController::class)
            ->onlyMethods(['getSearchResultSet', 'getTypoScriptFrontendController'])
            ->getMock();
        $subject->method('getSearchResultSet')->willReturn(
            new SearchResultSet()
        );
        $subject->method('getTypoScriptFrontendController')->willReturn($tsfeMock);

        $reflection = new \ReflectionClass(SearchController::class);
        $reflectionProperty = $reflection->getProperty('response');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($subject, $mockResponse);

        $res = $subject->indexAction();
        self::assertIsString($res);

        $json = json_decode($res, true);
        self::assertEquals(JSON_ERROR_NONE, json_last_error());
        self::assertIsArray($json);
    }

    /**
     * @test
     * @return void
     */
    public function indexActionWillHandleSolrUnavailable()
    {
        // use an exception to verify that the method was called and then break out of the stack to prevent further actions
        $time = time();
        $this->expectExceptionCode($time);

        $subject = $this->getMockBuilder(SearchController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSearchResultSet', 'handleSolrUnavailable'])
            ->getMock();

        $message = uniqid('message_');
        $number = time();

        $subject->method('getSearchResultSet')->willThrowException(new SolrUnavailableException($message, $number));
        $subject->expects(self::once())->method('handleSolrUnavailable')->willThrowException(
            new \Exception(uniqid(), $time)
        );

        $subject->indexAction();
    }

    /**
     * @test
     * @return void
     */
    public function indexActionAddsDataToView()
    {
        $viewMock = $this->getMockBuilder(TemplateView::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['assign'])
            ->getMock();
        $viewMock->expects(self::exactly(2))->method('assign')->withConsecutive(['resultSet'], ['resultSetJson']);

        $tsfeMock = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['applyHttpHeadersToResponse'])
            ->getMock();
        $tsfeMock->expects(self::once())->method('applyHttpHeadersToResponse')->willReturn(
            new \TYPO3\CMS\Core\Http\Response()
        );

        $subject = $this->getMockBuilder(SearchController::class)
            ->onlyMethods(['getSearchResultSet', 'getTypoScriptFrontendController'])
            ->getMock();
        $subject->method('getSearchResultSet')->willReturn(
            new SearchResultSet()
        );
        $subject->method('getTypoScriptFrontendController')->willReturn(
            $tsfeMock
        );

        $this->inject($subject, 'view', $viewMock);

        $subject->indexAction();
    }

    /**
     * @test
     * @return void
     */
    public function resultsActionWillApplyHeadersToResponse()
    {
        $subject = $this->getMockBuilder(SearchController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSearchResultSet', 'applyHttpHeadersToResponse'])
            ->getMock();
        $subject->method('getSearchResultSet')->willReturn(new SearchResultSet());
        $subject->expects(self::once())->method('applyHttpHeadersToResponse');

        $subject->resultsAction();
    }

    /**
     * @test
     * @return void
     */
    public function resultsActionWillReturnResultsAsJSON()
    {
        $subject = $this->getMockBuilder(SearchController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSearchResultSet', 'applyHttpHeadersToResponse'])
            ->getMock();
        $subject->method('getSearchResultSet')->willReturn(new SearchResultSet());
        $subject->method('applyHttpHeadersToResponse')->willReturn(null);

        $res = $subject->resultsAction();
        self::assertIsString($res);

        $json = json_decode($res, true);
        self::assertEquals(JSON_ERROR_NONE, json_last_error());
        self::assertIsArray($json);
    }


    /**
     * @test
     * @return void
     */
    public function resultActionWillHandleSolrUnavailable()
    {
        // use an exception to verify that the method was called and then break out of the stack to prevent further actions
        $time = time();
        $this->expectExceptionCode($time);

        $subject = $this->getMockBuilder(SearchController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSearchResultSet', 'handleSolrUnavailable'])
            ->getMock();

        $message = uniqid('message_');
        $number = time();

        $subject->method('getSearchResultSet')->willThrowException(new SolrUnavailableException($message, $number));
        $subject->expects(self::once())->method('handleSolrUnavailable')->willThrowException(
            new \Exception(uniqid(), $time)
        );

        $subject->indexAction();
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
     * @return void
     */
    public function suggestActionWillHandleSolrUnavailable()
    {
        $this->expectException(StopActionException::class);
        $this->expectExceptionCode(1476045801);
        $this->expectExceptionMessage('forward');

        $subject = new SearchController();

        $request = new Request();
        $request->setArgument('q', uniqid('query_'));
        $this->inject($subject, 'request', $request);

        $mockSuggestService = $this->getMockBuilder(SuggestService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSuggestions'])
            ->getMock();
        $mockSuggestService->method('getSuggestions')->willThrowException(new SolrUnavailableException());
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
