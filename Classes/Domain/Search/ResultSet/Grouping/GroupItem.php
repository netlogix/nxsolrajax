<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Grouping\GroupItem as SolrGroupItem;
use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use JsonSerializable;

class GroupItem extends SolrGroupItem implements JsonSerializable
{
    private string $groupLabel = '';

    private string $groupUrl = '';

    protected SearchUriBuilder $searchUriBuilder;

    public function setSearchUriBuilder(SearchUriBuilder $searchUriBuilder): void
    {
        $this->searchUriBuilder = $searchUriBuilder;
    }

    public function jsonSerialize(): array
    {
        return [
            'label' => $this->getGroupLabel(),
            'name' => $this->getGroupValue(),
            'totalResults' => $this->getAllResultCount(),
            'start' => $this->getStart(),
            'maxScore' => $this->getMaximumScore(),
            'url' => $this->getGroupUrl(),
            'items' => $this->addSearchUriBuilderToOptions($this->getSearchResults()->getArrayCopy()),
        ];
    }

    protected function getGroupLabel(): string
    {
        return $this->groupLabel !== '' && $this->groupLabel !== '0' ? $this->groupLabel : $this->getGroupValue();
    }

    public function setGroupLabel(string $groupLabel): void
    {
        $this->groupLabel = $groupLabel;
    }

    public function getGroupUrl(): string
    {
        return $this->groupUrl;
    }

    public function setGroupUrl(string $groupUrl): void
    {
        $this->groupUrl = $groupUrl;
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
