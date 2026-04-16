<?php

/*
 * Isotope eCommerce for Contao Open Source CMS
 *
 * Copyright (C) 2009 - 2019 terminal42 gmbh & Isotope eCommerce Workgroup
 *
 * @link       https://isotopeecommerce.org
 * @license    https://opensource.org/licenses/lgpl-3.0.html
 */

namespace Isotope\Backend\Product;

use Contao\Automator;
use Contao\Backend;
use Contao\DataContainer;
use Contao\System;

class XmlSitemap extends Backend
{

    /**
     * Schedule an XML sitemap update
     * @param DataContainer
     */
    public function scheduleUpdate($dc)
    {
        // Return if there is no ID
        if (!$dc->id) {
            return;
        }

        // Store the ID in the session
        $session   = \Contao\System::getContainer()->get('request_stack')->getSession()->get('iso_product_updater');
        $session[] = $dc->id;
        \Contao\System::getContainer()->get('request_stack')->getSession()->set('iso_product_updater', array_unique($session));
    }

    /**
     * Check for modified products and update the XML files if necessary
     */
    public function generate()
    {
        $session = System::getContainer()->get('request_stack')->getSession();
        $updaterIds = $session->get('iso_product_updater');

        if (!\is_array($session) || empty($session)) {
            return;
        }

        $objAutomator = new Automator();
        $objAutomator->generateSitemap();

        $session->set('iso_product_updater', null);
    }
}
