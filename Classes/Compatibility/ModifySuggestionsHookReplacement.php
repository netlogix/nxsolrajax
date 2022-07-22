<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Compatibility;

use Netlogix\Nxsolrajax\Event\Search\AfterGetSuggestionsEvent;
use Netlogix\Nxsolrajax\SugesstionResultModifier;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ModifySuggestionsHookReplacement
{

    public function __invoke(AfterGetSuggestionsEvent $event): void
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nxsolrajax']['modifySuggestions'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nxsolrajax']['modifySuggestions'] as $key => $classRef) {
                trigger_error(
                    'Using the `modifySuggestions` hook in SearchController is deprecated. Replace "' . $classRef . '" with AfterGetSuggestionsEvent.',
                    E_USER_DEPRECATED
                );

                $hookObject = GeneralUtility::makeInstance($classRef);
                if (!$hookObject instanceof SugesstionResultModifier) {
                    throw new \Exception(
                        sprintf(
                            'modifySuggestions hook expects SuggestionResultModifier, got %s',
                            get_class($hookObject)
                        ), 1533224243
                    );
                }
                $suggestions = $hookObject->modifySuggestions(
                    $event->getQuery(),
                    $event->getSuggestions(),
                    $event->getTypoScriptConfiguration()
                );

                $event->setSuggestions($suggestions ?? []);
            }
        }
    }

}