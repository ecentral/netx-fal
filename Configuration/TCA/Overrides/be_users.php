<?php

declare(strict_types=1);

/*
 * This file is part of the "netx_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

defined('TYPO3') || die('Access denied.');

$netxFilePermissions = [
    'netx_file_permission_read' => 'NetX Files: Read',
    'netx_file_permission_copy' => 'NetX Files: Copy',
];

$GLOBALS['TCA']['be_users']['columns']['file_permissions']['config']['items'][] = [
    'label' => 'NetX Files',
    'value' => '--div--',
];

foreach ($netxFilePermissions as $permissionKey => $label) {
    $GLOBALS['TCA']['be_users']['columns']['file_permissions']['config']['columns'][$permissionKey] = [
        'label' => $label,
        'config' => [
            'type' => 'check',
            'default' => 0,
        ],
    ];
    $GLOBALS['TCA']['be_users']['columns']['file_permissions']['config']['columnsOverrides'][$permissionKey] = [
        'label' => $label,
    ];
    $GLOBALS['TCA']['be_users']['columns']['file_permissions']['config']['items'][] = [
        'label' => $label,
        'value' => $permissionKey,
        'icon' => 'mimetypes-other-other',
    ];
}

$netxFolderPermissions = [
    'netx_folder_permission_read' => 'NetX Folder: Read',
    'netx_folder_permission_copy' => 'NetX Folder: Copy',
];

$GLOBALS['TCA']['be_users']['columns']['file_permissions']['config']['items'][] = [
    'label' => 'NetX Folder',
    'value' => '--div--',
];

foreach ($netxFolderPermissions as $permissionKey => $label) {
    $GLOBALS['TCA']['be_users']['columns']['file_permissions']['config']['columns'][$permissionKey] = [
        'label' => $label,
        'config' => [
            'type' => 'check',
            'default' => 0,
        ],
    ];
    $GLOBALS['TCA']['be_users']['columns']['file_permissions']['config']['columnsOverrides'][$permissionKey] = [
        'label' => $label,
    ];
    $GLOBALS['TCA']['be_users']['columns']['file_permissions']['config']['items'][] = [
        'label' => $label,
        'value' => $permissionKey,
        'icon' => 'apps-filetree-folder-default',
    ];
}

$count = count($GLOBALS['TCA']['be_users']['columns']['file_permissions']['config']['items']);
$GLOBALS['TCA']['be_users']['columns']['file_permissions']['config']['maxitems'] = $count;
$GLOBALS['TCA']['be_users']['columns']['file_permissions']['config']['size'] = $count;
