<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle\Controller;

use Contao\ContentModel;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\System;
use Derhaeuptling\RegiondoBundle\BookingManager;
use Symfony\Component\HttpFoundation\Request;

abstract class AjaxController implements FrameworkAwareInterface
{
    /**
     * @var BookingManager
     */
    protected $bookingManager;

    /**
     * @var ContaoFrameworkInterface
     */
    protected $framework;

    /**
     * Sets the framework service.
     *
     * @param ContaoFrameworkInterface|null $framework
     */
    public function setFramework(ContaoFrameworkInterface $framework = null): void
    {
        $this->framework = $framework;
    }

    /**
     * Set the booking manager.
     *
     * @param BookingManager $bookingManager
     */
    public function setBookingManager(BookingManager $bookingManager): void
    {
        $this->bookingManager = $bookingManager;
    }

    /**
     * Initialize the AJAX environment.
     *
     * @param Request $request
     *
     * @throws \RuntimeException
     */
    protected function initAjaxEnvironment(Request $request): void
    {
        \define('BE_USER_LOGGED_IN', false);
        \define('FE_USER_LOGGED_IN', false);

        $this->framework->initialize();

        /** @var PageModel $pageAdapter */
        $pageAdapter = $this->framework->getAdapter(PageModel::class);
        $pageId = $request->attributes->getInt('page');

        if (null === ($pageModel = $pageAdapter->findByPk($pageId))) {
            throw new \RuntimeException(\sprintf('Page ID %s not found', $pageId));
        }

        $pageModel->loadDetails();
        $pageModel->noSearch = true;

        $GLOBALS['objPage'] = $pageModel;
        $GLOBALS['TL_LANGUAGE'] = $pageModel->language;

        /** @var System $systemAdapter */
        $systemAdapter = $this->framework->getAdapter(System::class);
        $systemAdapter->loadLanguageFile('default');
    }

    /**
     * Get the content element.
     *
     * @param int $contentId
     *
     * @return ContentModel
     */
    protected function getContentElement(int $contentId): ContentModel
    {
        /** @var ContentModel $contentAdapter */
        $contentAdapter = $this->framework->getAdapter(ContentModel::class);

        if (!$contentId || null === ($model = $contentAdapter->findByPk($contentId))) {
            throw new \InvalidArgumentException(\sprintf('The content element ID %s does not exist', $contentId));
        }

        return $model;
    }

    /**
     * Get the frontend module.
     *
     * @param int $moduleId
     *
     * @return ModuleModel
     */
    protected function getFrontendModule(int $moduleId): ModuleModel
    {
        /** @var ModuleModel $moduleAdapter */
        $moduleAdapter = $this->framework->getAdapter(ModuleModel::class);

        if (!$moduleId || null === ($model = $moduleAdapter->findByPk($moduleId))) {
            throw new \InvalidArgumentException(\sprintf('The frontend module ID %s does not exist', $moduleId));
        }

        return $model;
    }
}
