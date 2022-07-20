<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Compatibility;

use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\LinkHelper\ResetLinkHelperInterface;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\LinkHelper\SelfLinkHelperInterface;
use Netlogix\Nxsolrajax\Event\Url\GenerateFacetItemUrlEvent;
use Netlogix\Nxsolrajax\Event\Url\GenerateFacetResetUrlEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LinkHelperReplacement
{

    public function generateFacetResetUrl(GenerateFacetResetUrlEvent $event): void
    {
        $facet = $event->getFacet();

        $settings = $facet->getConfiguration();

        if (isset($settings['linkHelper']) && is_a($settings['linkHelper'], ResetLinkHelperInterface::class, true)) {
            trigger_error(
                'Using LinkHelpers to generate reset urls is deprecated. Use GenerateFacetResetUrlEvent instead.',
                E_USER_DEPRECATED
            );

            /** @var ResetLinkHelperInterface $linkHelper */
            $linkHelper = GeneralUtility::makeInstance($settings['linkHelper']);
            if ($linkHelper->canHandleResetLink($facet)) {
                $url = $linkHelper->renderResetLink($facet);
                $event->setUrl($url);
            }
        }
    }

    public function generateFacetItemUrl(GenerateFacetItemUrlEvent $event): void
    {
        $facetItem = $event->getFacetItem();

        $settings = $facetItem->getFacet()->getConfiguration();
        if (isset($settings['linkHelper']) && is_a($settings['linkHelper'], SelfLinkHelperInterface::class, true)) {
            trigger_error(
                'Using LinkHelpers to generate facet urls is deprecated. Use GenerateFacetItemUrlEvent instead.',
                E_USER_DEPRECATED
            );

            /** @var SelfLinkHelperInterface $linkHelper */
            $linkHelper = GeneralUtility::makeInstance($settings['linkHelper']);
            if ($linkHelper->canHandleSelfLink($facetItem)) {
                $url = $linkHelper->renderSelfLink($facetItem);
                $event->setUrl($url);
            }
        }
    }

}
