<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Traits;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\AbstractFacet;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\AbstractFacetItem;
use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\LinkHelper\ResetLinkHelperInterface;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\LinkHelper\SelfLinkHelperInterface;
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
        $settings = $facet->getConfiguration();

        if (isset($settings['linkHelper']) && is_a($settings['linkHelper'], ResetLinkHelperInterface::class, true)) {
            /** @var ResetLinkHelperInterface $linkHelper */
            $linkHelper = GeneralUtility::makeInstance($settings['linkHelper']);
            if ($linkHelper->canHandleResetLink($facet)) {
                return $linkHelper->renderResetLink($facet);
            }
        }

        $previousRequest = $facet->getResultSet()->getUsedSearchRequest();
        return GeneralUtility::makeInstance(ObjectManager::class)->get(SearchUriBuilder::class)->getRemoveFacetUri(
            $previousRequest,
            $facet->getName()
        );
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
        $settings = $facetItem->getFacet()->getConfiguration();
        if (isset($settings['linkHelper']) && is_a($settings['linkHelper'], SelfLinkHelperInterface::class, true)) {
            /** @var SelfLinkHelperInterface $linkHelper */
            $linkHelper = GeneralUtility::makeInstance($settings['linkHelper']);
            if ($linkHelper->canHandleSelfLink($facetItem)) {
                return $linkHelper->renderSelfLink($facetItem);
            }
        }

        $previousRequest = $facetItem->getFacet()->getResultSet()->getUsedSearchRequest();

        return GeneralUtility::makeInstance(ObjectManager::class)->get(SearchUriBuilder::class)->getSetFacetValueUri(
            $previousRequest,
            $facetItem->getFacet()->getName(),
            $overrideUriValue ?: $facetItem->getUriValue()
        );
    }
}