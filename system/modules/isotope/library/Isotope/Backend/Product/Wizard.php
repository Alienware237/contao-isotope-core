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

use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;

class Wizard
{
    public function onProductTypeWizard(DataContainer $dc)
    {
        if ($dc->value < 1) {
            return '';
        }

        // Use Environment::get('scriptName') to get the correct backend entry point (e.g., /contao)
        $strBackendEntry = \Contao\Environment::get('scriptName');

        return sprintf(
            ' <a href="%s" title="%s" style="padding-left:3px" onclick="Backend.openModalIframe({\'width\':768,\'title\':\'%s\',\'url\':this.href});return false">%s</a>',
            sprintf(
                '%s?do=iso_setup&amp;mod=producttypes&amp;table=tl_iso_producttype&amp;act=edit&amp;id=%s&amp;popup=1&amp;nb=1&amp;rt=%s',
                $strBackendEntry,
                $dc->value,
                $this->getCsrfToken()
            ),
            sprintf(StringUtil::specialchars($GLOBALS['TL_LANG']['tl_iso_producttype']['edit'][1]), $dc->value),
            StringUtil::specialchars(str_replace("'", "\\'", sprintf($GLOBALS['TL_LANG']['tl_iso_producttype']['edit'][1], $dc->value))),
            Image::getHtml('alias.svg', $GLOBALS['TL_LANG']['tl_iso_producttype']['edit'][0])
        );
    }

    /**
     * Get the current CSRF token
     * @return string
     */
    protected function getCsrfToken()
    {
        $container = System::getContainer();
        return $container->get('contao.csrf.token_manager')
            ->getToken($container->getParameter('contao.csrf_token_name'))
            ->getValue();
    }
}
