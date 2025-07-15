<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Updates;

use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\AbstractListTypeToCTypeUpdate;

#[UpgradeWizard('nxsolrajaxPluginUpdater')]
final class PluginUpdater extends AbstractListTypeToCTypeUpdate
{
    public function getTitle(): string
    {
        return 'Migrate plugins to content elements.';
    }

    public function getDescription(): string
    {
        return 'Migrate plugins to content elements.';
    }

    protected function getListTypeToCTypeMapping(): array
    {
        return [
            'nxsolrajax_index' => 'nxsolrajax_index',
            'nxsolrajax_results' => 'nxsolrajax_results',
            'nxsolrajax_suggest' => 'nxsolrajax_suggest',
        ];
    }
}
