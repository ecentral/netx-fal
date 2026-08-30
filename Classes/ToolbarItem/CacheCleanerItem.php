<?php

declare(strict_types=1);

/*
 * This file is part of the "netx_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\NetXFal\ToolbarItem;

use AllowDynamicProperties;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Backend\Event\ModifyClearCacheActionsEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Toolbar\RequestAwareToolbarItemInterface;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Render cache clearing toolbar item.
 * Adds a dropdown if there are more than one item to clear (usually for admins to render the flush all caches).
 * The dropdown items can be manipulated using ModifyClearCacheActionsEvent.
 */
#[AllowDynamicProperties]
class CacheCleanerItem implements ToolbarItemInterface, RequestAwareToolbarItemInterface
{
    /** @var list<array{id: string, title: string, description: string, href: string, iconIdentifier: string}> */
    protected array $cacheActions = [];
    /** @var list<string> */
    protected array $optionValues = [];
    private ServerRequestInterface $request;
    private ?ViewFactoryInterface $viewFactory = null;

    public function __construct(
        UriBuilder $uriBuilder,
        EventDispatcherInterface $eventDispatcher
    ) {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $clearCacheUri = (string)$uriBuilder->buildUriFromRoute('ajax_netx_cache');

        $cacheActions[] = [
            'id' => 'netx_cache_action',
            'title' => 'LLL:EXT:netx_fal/Resources/Private/Language/locallang.xlf:be_clear_cache_title',
            'description' => 'LLL:EXT:netx_fal/Resources/Private/Language/locallang.xlf:be_clear_cache_description',
            'href' => $clearCacheUri,
            'iconIdentifier' => 'actions-synchronize',
        ];
        $this->optionValues[] = 'netx';

        if ((new Typo3Version())->getMajorVersion() > 12) {
            $this->viewFactory = GeneralUtility::makeInstance(ViewFactoryInterface::class);
        }
        $event = new ModifyClearCacheActionsEvent($cacheActions, $this->optionValues);
        $event = $eventDispatcher->dispatch($event);
        $this->cacheActions = $event->getCacheActions();
        $this->optionValues = $event->getCacheActionIdentifiers();
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    /**
     * Checks whether the user has access to this toolbar item.
     */
    public function checkAccess(): bool
    {
        $backendUser = $this->getBackendUser();
        if ($backendUser->isAdmin()) {
            return true;
        }
        foreach ($this->optionValues as $value) {
            if ($backendUser->getTSConfig()['options.']['clearCache.'][$value] ?? false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Render clear cache icon, based on the option if there is more than one icon or just one.
     */
    public function getItem(): string
    {
        if ((new Typo3Version())->getMajorVersion() < 13) {
            /** @var StandaloneView $view */
            $view = GeneralUtility::makeInstance(StandaloneView::class);
            $view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:netx_fal/Resources/Private/Templates/')]);
            $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:netx_fal/Resources/Private/Partials/')]);
            $view->setLayoutRootPaths([GeneralUtility::getFileAbsFileName('EXT:netx_fal/Resources/Private/Layouts/')]);
            $view->setTemplate('ToolbarItems/ClearCumulusCacheToolbarItemSingle.html');
        } else {
            $this->viewFactory ??= GeneralUtility::makeInstance(ViewFactoryInterface::class);
            $viewFactoryData = new ViewFactoryData(
                templateRootPaths: ['EXT:netx_fal/Resources/Private/Templates/'],
                partialRootPaths: ['EXT:netx_fal/Resources/Private/Partials/'],
                layoutRootPaths: ['EXT:netx_fal/Resources/Private/Layouts/'],
                request: $this->request,
            );
            $view = $this->viewFactory->create($viewFactoryData);
        }

        $cacheAction = end($this->cacheActions);
        $view->assignMultiple([
            'link'  => $cacheAction['href'],
            'title' => $cacheAction['title'],
            'iconIdentifier'  => $cacheAction['iconIdentifier'],
        ]);

        return $view->render('ToolbarItems/ClearCumulusCacheToolbarItemSingle.html');
    }

    /**
     * Render drop-down.
     */
    public function getDropDown(): string
    {
        return '';
    }

    /**
     * No additional attributes needed.
     *
     * @return array<string, string>
     */
    public function getAdditionalAttributes(): array
    {
        return [];
    }

    /**
     * This item has a drop-down, if there is more than one cache action available for the current Backend user.
     */
    public function hasDropDown(): bool
    {
        return count($this->cacheActions) > 1;
    }

    /**
     * Position relative to others
     */
    public function getIndex(): int
    {
        return 25;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
