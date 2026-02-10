<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || die();

ExtensionUtility::registerPlugin(
    'nxsolrajax',
    'index',
    'LLL:EXT:nxsolrajax/Resources/Private/Language/locallang_db.xml:plugins.index.title',
);
