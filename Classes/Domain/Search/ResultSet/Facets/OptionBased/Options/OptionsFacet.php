<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Options;

use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\LinkHelper\ResetLinkHelperInterface;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\LinkHelper\SelfLinkHelperInterface;

class OptionsFacet extends \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Options\OptionsFacet implements \JsonSerializable
{
    /**
     * @return string
     */
    public function getResetUrl()
    {
        $settings = $this->getConfiguration();
        if (isset($settings['linkHelper']) && is_a($settings['linkHelper'], ResetLinkHelperInterface::class, true)) {
            /** @var ResetLinkHelperInterface $linkHelper */
            $linkHelper = $this->objectManager->get($settings['linkHelper']);
            if ($linkHelper->canHandleResetLink($this)) {
                return $linkHelper->renderResetLink($this);
            }
        }

        $searchUriBuilder = $this->objectManager->get(SearchUriBuilder::class);
        $previousRequest = $this->getResultSet()->getUsedSearchRequest();
        return $searchUriBuilder->getRemoveFacetUri($previousRequest, $this->getName());
    }

    /**
     * @return array
     */
    function jsonSerialize()
    {
        return [
            'name' => $this->getName(),
            'type' => self::TYPE_OPTIONS,
            'label' => $this->getLabel(),
            'used' => $this->getIsUsed(),
            'options' => array_values($this->getOptions()->getArrayCopy()),
            'links' => [
                'reset' => $this->getResetUrl(),
            ]
        ];
    }

}
