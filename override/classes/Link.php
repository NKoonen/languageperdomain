<?php
/**
 * 2007-2020 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

class Link extends LinkCore
{
    /**
     * @param int|null $idShop
     * @param bool|null $ssl
     * @param bool $relativeProtocol
     *
     * @return string
     */
    public function getBaseLink($idShop = null, $ssl = null, $relativeProtocol = false)
    {
        if (null === $ssl) {
            $ssl = (Configuration::get('PS_SSL_ENABLED') && Configuration::get('PS_SSL_ENABLED_EVERYWHERE'));
        }
        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && $idShop !== null) {
            $shop = new Shop($idShop);
        } else {
            $shop = Context::getContext()->shop;
        }
        if ($relativeProtocol) {
            $base = '//'.($ssl && $this->ssl_enable ? $shop->domain_ssl : $shop->domain);
        } else {
            $base = (($ssl && $this->ssl_enable) ? 'https://'.$shop->domain_ssl : 'http://'.$shop->domain);
        }

        $orginalPsLink = $base.$shop->getBaseURI();

        $new_extension = Db::getInstance()->getRow(
            '
            SELECT *
            FROM `'._DB_PREFIX_.'languageperdomain`
            WHERE `lang_id` = '.(int)Context::getContext()->language->id.'
            AND `target_replace` = '.(int)Context::getContext()->shop->id.'
            '
        );

        $controller = Dispatcher::getInstance()->getController();
        if ($new_extension && $controller != 'AdminOrders') {
            $LanguagePerDomainLink = str_replace(parse_url($orginalPsLink)["host"], $new_extension["new_target"], $orginalPsLink);
            return $LanguagePerDomainLink;
        } else {
            return $orginalPsLink;
        }
    }

    /**
     * @param null $idLang
     * @param Context|null $context
     * @param null $idShop
     *
     * @return string
     */
    protected function getLangLink($idLang = null, Context $context = null, $idShop = null)
    {
        return '';
    }

    public function getImageLink($name, $ids, $type = null)
    {
        $notDefault = false;
        $moduleManagerBuilder = ModuleManagerBuilder::getInstance();
        $moduleManager = $moduleManagerBuilder->build();
        static $watermarkLogged = null;
        static $watermarkHash = null;
        static $psLegacyImages = null;
        if ($watermarkLogged === null) {
            $watermarkLogged = Configuration::get('WATERMARK_LOGGED');
            $watermarkHash = Configuration::get('WATERMARK_HASH');
            $psLegacyImages = Configuration::get('PS_LEGACY_IMAGES');
        }

        // Check if module is installed, enabled, customer is logged in and watermark logged option is on
        if (!empty($type) && $watermarkLogged &&
            ($moduleManager->isInstalled('watermark') && $moduleManager->isEnabled('watermark')) &&
            isset(Context::getContext()->customer->id)
        ) {
            $type .= '-' . $watermarkHash;
        }

        // legacy mode or default image
        $theme = ((Shop::isFeatureActive() && file_exists(_PS_PROD_IMG_DIR_ . $ids . ($type ? '-' . $type : '') . '-' . Context::getContext()->shop->theme_name . '.jpg')) ? '-' . Context::getContext()->shop->theme_name : '');
        if (($psLegacyImages
                && (file_exists(_PS_PROD_IMG_DIR_ . $ids . ($type ? '-' . $type : '') . $theme . '.jpg')))
            || ($notDefault = strpos($ids, 'default') !== false)) {
            if ($this->allow == 1 && !$notDefault) {
                $uriPath = __PS_BASE_URI__ . $ids . ($type ? '-' . $type : '') . $theme . '/' . $name . '.jpg';
            } else {
                $uriPath = _THEME_PROD_DIR_ . $ids . ($type ? '-' . $type : '') . $theme . '.jpg';
            }
        } else {
            // if ids if of the form id_product-id_image, we want to extract the id_image part
            $splitIds = explode('-', $ids);
            $idImage = (isset($splitIds[1]) ? $splitIds[1] : $splitIds[0]);
            $theme = ((Shop::isFeatureActive() && file_exists(_PS_PROD_IMG_DIR_ . Image::getImgFolderStatic($idImage) . $idImage . ($type ? '-' . $type : '') . '-' . (int) Context::getContext()->shop->theme_name . '.jpg')) ? '-' . Context::getContext()->shop->theme_name : '');
            if ($this->allow == 1) {
                $uriPath = __PS_BASE_URI__ . $idImage . ($type ? '-' . $type : '') . $theme . '/' . $name . '.jpg';
            } else {
                $uriPath = _THEME_PROD_DIR_ . Image::getImgFolderStatic($idImage) . $idImage . ($type ? '-' . $type : '') . $theme . '.jpg';
            }
        }
        return $this->getBaseLink() . $uriPath;
    }
}
