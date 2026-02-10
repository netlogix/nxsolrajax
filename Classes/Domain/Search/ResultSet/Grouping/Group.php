<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Grouping\Group as SolrGroup;
use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use JsonSerializable;

class Group extends SolrGroup implements JsonSerializable
{
    protected SearchUriBuilder $searchUriBuilder;

    public function setSearchUriBuilder(SearchUriBuilder $searchUriBuilder): void
    {
        $this->searchUriBuilder = $searchUriBuilder;
    }

    public function jsonSerialize(): array
    {
        return [
            'groupName' => $this->getGroupName(),
            'resultsPerPage' => $this->getResultsPerPage(),
            'groupItems' => $this->addSearchUriBuilderToOptions($this->getGroupItems()->getArrayCopy()),
        ];
    }

    protected function addSearchUriBuilderToOptions(array $options): array
    {
        foreach ($options as $option) {
            if (method_exists($option, 'setSearchUriBuilder')) {
                $option->setSearchUriBuilder($this->searchUriBuilder);
            }
        }

        return $options;
    }
}
