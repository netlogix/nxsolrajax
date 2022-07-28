<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Controller;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSetService;
use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequest;
use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequestBuilder;
use ApacheSolrForTypo3\Solr\Domain\Search\Suggest\SuggestService;
use ApacheSolrForTypo3\Solr\Mvc\Controller\SolrControllerContext;
use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use ApacheSolrForTypo3\Solr\System\Solr\SolrUnavailableException;
use Exception;
use Netlogix\Nxsolrajax\Controller\SearchController;
use Nimut\TestingFramework\Rendering\RenderingContextFixture;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\TemplateParser;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;
use TYPO3Fluid\Fluid\View\TemplatePaths;

class SearchControllerTest extends UnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['interceptors'] = [];

        $GLOBALS['TSFE'] = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'] = [];
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unset($_SERVER['HTTP_ACCEPT'], $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nxsolrajax']['modifySuggestions'], $GLOBALS['TSFE']);
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['interceptors']);

        GeneralUtility::purgeInstances();
    }

    /**
     * @test
     * @return void
     */
    public function indexActionAddsContentTypeForJSONRequests()
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $tsfeMock = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();


        $subject = $this->getMockBuilder(SearchController::class)
            ->onlyMethods(['getSearchResultSet', 'getTypoScriptFrontendController'])
            ->getMock();
        $subject->method('getSearchResultSet')->willReturn(
            new SearchResultSet()
        );
        $subject->method('getTypoScriptFrontendController')->willReturn($tsfeMock);

        $this->inject($subject, 'responseFactory', new ResponseFactory());
        $this->inject($subject, 'streamFactory', new StreamFactory());

        $res = $subject->indexAction();

        self::assertEquals('application/json; charset=utf-8', $res->getHeaderLine('Content-Type'));
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
            new Exception(uniqid(), $time)
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

        $renderingContext = new RenderingContextFixture();
        $renderingContext->injectViewHelperVariableContainer(new ViewHelperVariableContainer());
        $renderingContext->setTemplateCompiler(new TemplateCompiler());
        $renderingContext->setTemplateParser(new TemplateParser());
        $renderingContext->setTemplatePaths(new TemplatePaths());

        $viewMock->setRenderingContext($renderingContext);

        $subject = $this->getMockBuilder(SearchController::class)
            ->onlyMethods(['getSearchResultSet', 'htmlResponse'])
            ->getMock();
        $subject->method('getSearchResultSet')->willReturn(
            new SearchResultSet()
        );
        $subject->method('htmlResponse')->willReturn(
            new \TYPO3\CMS\Core\Http\Response()
        );


        $this->inject($subject, 'view', $viewMock);
        $this->inject($subject, 'responseFactory', new ResponseFactory());
        $this->inject($subject, 'streamFactory', new StreamFactory());

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

        $this->inject($subject, 'responseFactory', new ResponseFactory());
        $this->inject($subject, 'streamFactory', new StreamFactory());

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
            ->onlyMethods(['getSearchResultSet'])
            ->getMock();
        $subject->method('getSearchResultSet')->willReturn(new SearchResultSet());

        $this->inject($subject, 'responseFactory', new ResponseFactory());
        $this->inject($subject, 'streamFactory', new StreamFactory());

        $res = $subject->resultsAction();

        self::assertEquals('application/json; charset=utf-8', $res->getHeaderLine('Content-Type'));
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
            new Exception(uniqid(), $time)
        );

        $subject->indexAction();
    }


    /**
     * @test
     * @return void
     */
    public function suggestActionWillHandleSolrUnavailable()
    {
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

        $res = $subject->suggestAction();

        self::assertInstanceOf(ForwardResponse::class, $res);
    }


    /**
     * @test
     * @return void
     */
    public function isSetsSearchResultsInControllerContext()
    {
        $subject = $this->getAccessibleMock(SearchController::class, ['dummy']);

        $controllerContext = $this->getMockBuilder(SolrControllerContext::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setSearchResultSet'])
            ->getMock();
        $controllerContext->expects(self::once())->method('setSearchResultSet');
        $this->inject($subject, 'controllerContext', $controllerContext);

        $request = new Request();
        $this->inject($subject, 'request', $request);

        $mockTSFE = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRequestedId'])
            ->getMock();
        $mockTSFE->method('getRequestedId')->willReturn(rand(1, 9999));
        $this->inject($subject, 'typoScriptFrontendController', $mockTSFE);

        $searchRequest = new SearchRequest();

        $searchRequestBuilderMock = $this->getMockBuilder(SearchRequestBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['buildForSearch'])
            ->getMock();
        $searchRequestBuilderMock->method('buildForSearch')->willReturn($searchRequest);
        $this->inject($subject, 'searchRequestBuilder', $searchRequestBuilderMock);

        $searchResultSet = new SearchResultSet();

        $searchServiceMock = $this->getMockBuilder(SearchResultSetService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['search'])
            ->getMock();
        $searchServiceMock->expects(self::once())->method('search')->with($searchRequest)->willReturn($searchResultSet);
        $this->inject($subject, 'searchService', $searchServiceMock);

        $res = $subject->_call('getSearchResultSet');

        self::assertSame($searchResultSet, $res);
    }

    /**
     * @test
     * @return void
     */
    public function itCanGetTSFE()
    {
        $subject = $this->getAccessibleMock(SearchController::class, ['dummy']);

        $GLOBALS['TSFE'] = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->getMock();

        $res = $subject->_call('getTypoScriptFrontendController');

        self::assertSame($GLOBALS['TSFE'], $res);
    }

    /**
     * @test
     * @return void
     */
    public function solrNotAvailableActionReturnsJsonResponse()
    {
        $subject = new SearchController();

        $subject->injectResponseFactory(new ResponseFactory());
        $subject->injectStreamFactory(new StreamFactory());

        $res = $subject->solrNotAvailableAction();

        self::assertEquals('application/json; charset=utf-8', $res->getHeaderLine('Content-Type'));

        $res->getBody()->rewind();

        json_decode($res->getBody()->getContents());
        self::assertEquals(JSON_ERROR_NONE, json_last_error(), 'failed decoding content to JSON');
    }

    /**
     * @test
     * @return void
     */
    public function solrNotAvailableActionReturnsErrorsInResponse()
    {
        $subject = new SearchController();

        $subject->injectResponseFactory(new ResponseFactory());
        $subject->injectStreamFactory(new StreamFactory());

        $res = $subject->solrNotAvailableAction();

        $res->getBody()->rewind();

        $resData = json_decode($res->getBody()->getContents(), true);

        self::assertCount(2, $resData);

        self::assertArrayHasKey('status', $resData);
        self::assertEquals(503, $resData['status']);

        self::assertArrayHasKey('message', $resData);
        self::assertEmpty($resData['message']);
    }
}
