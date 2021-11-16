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
	 * @param string $link
	 * @param int $idLang
	 * @return string
	 */
	public static function translateDomain( $link, $idLang )
	{
		/** @var Languageperdomain $languageperdomain */
		static $languageperdomain = null;
		if ( ! $languageperdomain ) {
			$languageperdomain = Module::getInstanceByName('languageperdomain');
		}
		if ( $languageperdomain ) {
			return $languageperdomain->replaceDomain( $link, $idLang );
		}
		return $link;
	}

	/**
	 * @inheritDoc
     */
    public function getBaseLink($idShop = null, $ssl = null, $relativeProtocol = false)
    {
	    $link = parent::getBaseLink( $idShop, $ssl, $relativeProtocol );
		return self::translateDomain( $link, null );
    }

    /**
     * @inheritDoc
     */
    protected function getLangLink($idLang = null, Context $context = null, $idShop = null)
    {
        return '';
    }

	/**
	 * @inheritDoc
	 */
	public function getCategoryLink( $category, $alias = null, $idLang = null, $selectedFilters = null, $idShop = null, $relativeProtocol = false ) {
		$link = parent::getCategoryLink( $category, $alias, $idLang, $selectedFilters, $idShop, $relativeProtocol );
		return ( $idLang ) ? self::translateDomain( $link, $idLang ) : $link;
	}

	/**
	 * @inheritDoc
	 */
	public function getCMSLink( $cms, $alias = null, $ssl = null, $idLang = null, $idShop = null, $relativeProtocol = false ) {
		$link = parent::getCMSLink( $cms, $alias, $ssl, $idLang, $idShop, $relativeProtocol );
		return ( $idLang ) ? self::translateDomain( $link, $idLang ) : $link;
	}

	/**
	 * @inheritDoc
	 */
	public function getCMSCategoryLink( $cmsCategory, $alias = null, $idLang = null, $idShop = null, $relativeProtocol = false ) {
		$link = parent::getCMSCategoryLink( $cmsCategory, $alias, $idLang, $idShop, $relativeProtocol );
		return ( $idLang ) ? self::translateDomain( $link, $idLang ) : $link;
	}

	/**
	 * @inheritDoc
	 */
	public function getPageLink( $controller, $ssl = null, $idLang = null, $request = null, $requestUrlEncode = false, $idShop = null, $relativeProtocol = false ) {
		$link = parent::getPageLink( $controller, $ssl, $idLang, $request, $requestUrlEncode, $idShop, $relativeProtocol );
		return ( $idLang ) ? self::translateDomain( $link, $idLang ) : $link;
	}

	/**
	 * @inheritDoc
	 */
	public function getManufacturerLink( $manufacturer, $alias = null, $idLang = null, $idShop = null, $relativeProtocol = false ) {
		$link = parent::getManufacturerLink( $manufacturer, $alias, $idLang, $idShop, $relativeProtocol );
		return ( $idLang ) ? self::translateDomain( $link, $idLang ) : $link;
	}

	/**
	 * @inheritDoc
	 */
	public function getSupplierLink( $supplier, $alias = null, $idLang = null, $idShop = null, $relativeProtocol = false ) {
		$link = parent::getSupplierLink( $supplier, $alias, $idLang, $idShop, $relativeProtocol );
		return ( $idLang ) ? self::translateDomain( $link, $idLang ) : $link;
	}

	/**
	 * @inheritDoc
	 */
	public function getModuleLink( $module, $controller = 'default', array $params = [], $ssl = null, $idLang = null, $idShop = null, $relativeProtocol = false ) {
		$link = parent::getModuleLink( $module, $controller, $params, $ssl, $idLang, $idShop, $relativeProtocol );
		return ( $idLang ) ? self::translateDomain( $link, $idLang ) : $link;
	}
}
