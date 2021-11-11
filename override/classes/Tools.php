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

class Tools extends ToolsCore
{
    /**
     * Get the language id according to the url
     *
     * @inheritDoc
     */
    public static function switchLanguage(Context $context = null)
    {
	    /** @var Languageperdomain $languageperdomain */
	    $languageperdomain = Module::getInstanceByName('languageperdomain');
		if ( ! $languageperdomain ) {
			parent::switchLanguage();
			return;
		}

        if (null === $context) {
            $context = Context::getContext();
        }
        if (!isset($context->cookie)) {
            return;
        }
        if (($iso = Tools::getValue('isolang')) &&
            Validate::isLanguageIsoCode($iso) &&
            ($id_lang = (int)Language::getIdByIso($iso))
        ) {
            $_GET['id_lang'] = $id_lang;
        }

        $newLanguageId = 0;
        $curUrl = urlencode( urldecode( Tools::getHttpHost() ) );

        $allExtensions = $languageperdomain->getDomains();
        foreach ($allExtensions as $extension) {
            if ( $curUrl === $extension['new_target'] ) {
                $newLanguageId = (int)$extension['lang_id'];
            }
        }
        if (Validate::isUnsignedId($newLanguageId) && $newLanguageId !== 0 && $context->cookie->id_lang !== $newLanguageId)
        {
            $context->cookie->id_lang = $newLanguageId;
            $language = new Language($newLanguageId);
            if (Validate::isLoadedObject($language) && $language->active && $language->isAssociatedToShop()) {
                $context->language = $language;
            }
        }

        Tools::setCookieLanguage($context->cookie);
    }

	/**
	 * Replace media server URL with translation domain if no media servers are set.
	 * @since 1.1.0
	 * @inheritDoc
	 */
	public static function getMediaServer(string $filename): string
	{
		if ( ! Tools::hasMediaServer() ) {
			$languageperdomain = Module::getInstanceByName('languageperdomain');
			if ( $languageperdomain ) {
				return $languageperdomain->getLangDomain();
			}
		}
		return parent::getMediaServer( $filename );
	}
}
