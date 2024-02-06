<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Controller;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequest;
use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequestBuilder;
use ApacheSolrForTypo3\Solr\Domain\Search\Suggest\SuggestService;
use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use ApacheSolrForTypo3\Solr\System\Solr\SolrUnavailableException;
use Netlogix\Nxsolrajax\Controller\SearchController;
use Netlogix\Nxsolrajax\Event\Search\AfterGetSuggestionsEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SearchControllerTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    public function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['interceptors'] = [];
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'] = [];
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['interceptors']);

        GeneralUtility::purgeInstances();
    }

    #[Test]
    public function indexActionAddsContentTypeForJSONRequests(): void
    {
        $searchController = $this->getMockBuilder(SearchController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSearchResultSet'])
            ->getMock();
        $searchController->method('getSearchResultSet')->willReturn(
            new SearchResultSet()
        );

        $this->inject($searchController, 'responseFactory', new ResponseFactory());
        $this->inject($searchController, 'streamFactory', new StreamFactory());
        $this->inject(
            $searchController,
            'request',
            $this->createRequest()
                ->withHeader('Accept', 'application/json')
        );
        $response = $searchController->indexAction();

        self::assertEquals('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
    }

    #[Test]
    public function indexActionWillHandleSolrUnavailable(): void
    {
        $typoScriptConfiguration = $this->getMockBuilder(TypoScriptConfiguration::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLoggingExceptions'])
            ->getMock();
        $typoScriptConfiguration->method('getLoggingExceptions')->willReturn(false);

        $searchController = $this->getMockBuilder(SearchController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSearchResultSet'])
            ->getMock();

        $searchController->method('getSearchResultSet')
            ->willThrowException(new SolrUnavailableException('Solr Server not available', 1505989391));

        $this->inject($searchController, 'typoScriptConfiguration', $typoScriptConfiguration);

        $response = $searchController->indexAction();
        self::assertInstanceOf(ForwardResponse::class, $response);
        self::assertEquals('solrNotAvailable', $response->getActionName());
    }

    #[Test]
    public function indexActionAddsDataToView(): void
    {
        $viewMock = $this->getMockBuilder(TemplateView::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['assign'])
            ->getMock();

        $viewMock->expects(self::exactly(1))
            ->method('assign')
            ->with('resultSet', []);

        $searchController = $this->getMockBuilder(SearchController::class)
            ->onlyMethods(['getSearchResultSet', 'htmlResponse'])
            ->getMock();

        $searchController->method('getSearchResultSet')->willReturn(new SearchResultSet());
        $searchController->method('htmlResponse')->willReturn(new Response());

        $this->inject($searchController, 'view', $viewMock);
        $this->inject($searchController, 'responseFactory', new ResponseFactory());
        $this->inject($searchController, 'streamFactory', new StreamFactory());
        $this->inject($searchController, 'request', $this->createRequest());

        $searchController->indexAction();
    }

    #[Test]
    public function resultsActionWillReturnResultsAsJson(): void
    {
        $subject = $this->getMockBuilder(SearchController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSearchResultSet'])
            ->getMock();
        $subject->method('getSearchResultSet')->willReturn(new SearchResultSet());

        $this->inject($subject, 'responseFactory', new ResponseFactory());
        $this->inject($subject, 'streamFactory', new StreamFactory());

        $response = $subject->resultsAction();
        self::assertEquals('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
    }

    #[Test]
    public function resultActionWillHandleSolrUnavailable(): void
    {
        $typoScriptConfiguration = $this->getMockBuilder(TypoScriptConfiguration::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLoggingExceptions'])
            ->getMock();
        $typoScriptConfiguration->method('getLoggingExceptions')->willReturn(false);

        $searchController = $this->getMockBuilder(SearchController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSearchResultSet'])
            ->getMock();

        $searchController->method('getSearchResultSet')
            ->willThrowException(new SolrUnavailableException('Solr Server not available', 1505989391));

        $this->inject($searchController, 'typoScriptConfiguration', $typoScriptConfiguration);

        $response = $searchController->resultsAction();
        self::assertInstanceOf(ForwardResponse::class, $response);
        self::assertEquals('solrNotAvailable', $response->getActionName());
    }

    #[Test]
    public function suggestActionsWillReturnResultsAsJson(): void
    {
        $queryString = uniqid('query_');
        $request = $this->createRequest()
            ->withArgument('q', $queryString);

        $typoScriptFrontendController =$this->typoscritpFrontendControllerMock();
        $typoscriptConfiguration = new TypoScriptConfiguration([], (int) $typoScriptFrontendController->getRequestedId());

        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $searchRequestBuilder = $this->getMockBuilder(SearchRequestBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['buildForSuggest'])
            ->getMock();
        $searchRequestBuilder->method('buildForSuggest')->willReturn($searchRequest);

        $suggestService = $this->getMockBuilder(SuggestService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSuggestions'])
            ->getMock();

        $suggestions = ['asdasdasdasd a sdasdsa'];
        $suggestService->method('getSuggestions')->willReturn($suggestions);

        GeneralUtility::addInstance(SuggestService::class, $suggestService);
        GeneralUtility::addInstance(SearchRequestBuilder::class, $searchRequestBuilder);
        $this->registerEvent(AfterGetSuggestionsEvent::class, $queryString, $suggestions, $typoscriptConfiguration);

        $searchController = $this->getMockBuilder(SearchController::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->inject($searchController, 'request', $request);
        $this->inject($searchController, 'typoScriptFrontendController', $typoScriptFrontendController);
        $this->inject($searchController, 'typoScriptConfiguration', $typoscriptConfiguration);
        $this->inject($searchController, 'responseFactory', new ResponseFactory());
        $this->inject($searchController, 'streamFactory', new StreamFactory());

        $result = $searchController->suggestAction();

        self::assertEquals('application/json; charset=utf-8', $result->getHeaderLine('Content-Type'));
    }

    #[Test]
    public function suggestActionWillHandleSolrUnavailable(): void
    {
        $typoScriptConfiguration = $this->getMockBuilder(TypoScriptConfiguration::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLoggingExceptions'])
            ->getMock();
        $typoScriptConfiguration->method('getLoggingExceptions')->willReturn(false);

        $searchController = $this->getMockBuilder(SearchController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSuggestRequest'])
            ->getMock();

        $searchController->method('getSuggestRequest')
            ->willThrowException(new SolrUnavailableException('Solr Server not available', 1505989391));

        $this->inject(
            $searchController,
            'request',
            $this->createRequest()
                ->withArgument('q', uniqid('query_'))
        );
        $this->inject($searchController, 'typoScriptConfiguration', $typoScriptConfiguration);

        $response = $searchController->suggestAction();
        self::assertInstanceOf(ForwardResponse::class, $response);
        self::assertEquals('solrNotAvailable', $response->getActionName());
    }

    #[Test]
    public function solrNotAvailableActionReturnsJsonResponse(): void
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

    #[Test]
    public function solrNotAvailableActionReturnsErrorsInResponse(): void
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

    #[Test]
    public function itStartsErrorHandlingIfSolrIsUnavailable(): void
    {
        $expectedResponse = new Response();

        $subject = $this->getMockBuilder(SearchController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSearchResultSet', 'handleSolrUnavailable'])
            ->getMock();

        $subject->method('getSearchResultSet')->willThrowException(new SolrUnavailableException());
        $subject->expects(self::once())->method('handleSolrUnavailable')->willReturn($expectedResponse);

        $res = $subject->resultsAction();

        self::assertSame($expectedResponse, $res);
    }

    protected function inject($target, $name, $dependency)
    {
        if (! is_object($target)) {
            throw new \InvalidArgumentException('Wrong type for argument $target, must be object.', 1476107338);
        }

        $objectReflection = new \ReflectionObject($target);
        $methodNamePart = strtoupper($name[0]) . substr($name, 1);
        if ($objectReflection->hasMethod('set' . $methodNamePart)) {
            $methodName = 'set' . $methodNamePart;
            $target->$methodName($dependency);
        } elseif ($objectReflection->hasMethod('inject' . $methodNamePart)) {
            $methodName = 'inject' . $methodNamePart;
            $target->$methodName($dependency);
        } elseif ($objectReflection->hasProperty($name)) {
            $property = $objectReflection->getProperty($name);
            $property->setAccessible(true);
            $property->setValue($target, $dependency);
        } else {
            throw new \RuntimeException(
                'Could not inject ' . $name . ' into object of type ' . get_class($target),
                1476107339
            );
        }
    }

    protected function createRequest(): Request
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        return (new Request($serverRequest))
            ->withControllerExtensionName('SearchController')
            ->withControllerName('Search')
            ->withControllerActionName('index');
    }

    protected function typoscritpFrontendControllerMock(): TypoScriptFrontendController
    {

        $siteLanguage = $this->getMockBuilder(SiteLanguage::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLanguageId'])
            ->getMock();
        $siteLanguage->method('getLanguageId')->willReturn(0);

        $mock = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRequestedId', 'getLanguage'])
            ->getMock();
        $mock->method('getRequestedId')->willReturn(1);
        $mock->method('getLanguage')->willReturn($siteLanguage);
        return $mock;
    }

    protected function registerEvent(string $className, ...$args): EventDispatcherInterface&MockObject
    {
        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $eventDispatcher->expects(self::once())->method('dispatch')->with(
            self::isInstanceOf($className)
        )->willReturn(new $className(...$args));

        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);

        return $eventDispatcher;
    }
}
