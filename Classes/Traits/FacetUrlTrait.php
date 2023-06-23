<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Traits;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\AbstractFacet;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\AbstractFacetItem;
use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use Netlogix\Nxsolrajax\Event\Url\GenerateFacetItemUrlEvent;
use Netlogix\Nxsolrajax\Event\Url\GenerateFacetResetUrlEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait FacetUrlTrait
{

    /**
     * Generates a reset URL for a given facet
     *
     * This has support for configurable linkHelpers.
     */
    protected function getFacetResetUrl(AbstractFacet $facet): string
    {
        $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(
            new GenerateFacetResetUrlEvent($facet, '')
        );

        $url = $event->getUrl();
        if ($url !== '') {
            return $url;
        }

        $previousRequest = $facet->getResultSet()->getUsedSearchRequest();
        return GeneralUtility::makeInstance(SearchUriBuilder::class)->getRemoveFacetUri(
            $previousRequest,
            $facet->getName()
        );
    }

    /**
     * Generates the URL for a given facet item.
     *
     * This has support for configurable linkHelpers.
     */
    protected function getFacetItemUrl(AbstractFacetItem $facetItem, string $overrideUriValue = ''): string
    {
        $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(
            new GenerateFacetItemUrlEvent($facetItem, '', $overrideUriValue)
        );

        $url = $event->getUrl();
        if ($url !== '') {
            return $url;
        }

        $previousRequest = $facetItem->getFacet()->getResultSet()->getUsedSearchRequest();
        return GeneralUtility::makeInstance(SearchUriBuilder::class)->getSetFacetValueUri(
            $previousRequest,
            $facetItem->getFacet()->getName(),
            $overrideUriValue ?: $facetItem->getUriValue()
        );
    }
}
