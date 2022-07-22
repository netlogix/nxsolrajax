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
use TYPO3\CMS\Extbase\Object\ObjectManager;

trait FacetUrlTrait
{

    /**
     * Generates a reset URL for a given facet
     *
     * This has support for configurable linkHelpers.
     *
     * @param AbstractFacet $facet
     * @return string
     */
    protected function getFacetResetUrl(AbstractFacet $facet): string
    {
        $url = '';
        $event = new GenerateFacetResetUrlEvent($facet, $url);

        /** @var GenerateFacetResetUrlEvent $event */
        $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch($event);

        $url = $event->getUrl();

        if ($url == '') {
            $previousRequest = $facet->getResultSet()->getUsedSearchRequest();
            $url = GeneralUtility::makeInstance(ObjectManager::class)->get(SearchUriBuilder::class)->getRemoveFacetUri(
                $previousRequest,
                $facet->getName()
            );
        }

        return $url;
    }

    /**
     * Generates the URL for a given facet item.
     *
     * This has support for configurable linkHelpers.
     *
     * @param AbstractFacetItem $facetItem
     * @param string $overrideUriValue Do not use the FacetItem's current value but force this one instead
     * @return string
     */
    protected function getFacetItemUrl(AbstractFacetItem $facetItem, string $overrideUriValue = ''): string
    {
        $url = '';
        $event = new GenerateFacetItemUrlEvent($facetItem, $url, $overrideUriValue);

        /** @var GenerateFacetItemUrlEvent $event */
        $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch($event);

        $url = $event->getUrl();

        if ($url == '') {
            $previousRequest = $facetItem->getFacet()->getResultSet()->getUsedSearchRequest();

            $url = GeneralUtility::makeInstance(ObjectManager::class)->get(SearchUriBuilder::class)->getSetFacetValueUri(
                $previousRequest,
                $facetItem->getFacet()->getName(),
                $overrideUriValue ?: $facetItem->getUriValue()
            );
        }

        return $url;
    }
}