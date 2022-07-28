<?php

/** @noinspection PhpFullyQualifiedNameUsageInspection */
defined('TYPO3_MODE') or die();

call_user_func(function () {

    $pluginLabel = 'LLL:EXT:nxsolrajax/Resources/Private/Language/locallang_db.xml:plugins.%s.title';

    foreach (['index'] as $pluginName) {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'nxsolrajax',
            $pluginName,
            sprintf($pluginLabel, $pluginName)
        );
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['nxsolrajax_' . $pluginName] = 'layout,select_key,pages,recursive';
    }
});
