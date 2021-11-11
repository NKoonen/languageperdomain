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

if (!defined('_PS_VERSION_')) {
	exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Languageperdomain extends Module implements WidgetInterface
{
	protected $output = '';
	private $templateFile;

	public function __construct()
	{
		$this->name = 'languageperdomain';
		$this->tab = 'administration';
		$this->version = '1.0.7';
		$this->author = 'Inform-All';
		$this->bootstrap = TRUE;
		$this->need_instance = 0;
		$this->module_key = '72bbd86520e7e08465532b8c1153d0bb';
		$this->displayName = $this->l('Language per domain');
		$this->description = $this->l('Use a domain for every language, without multistore.');
		$this->templateFile = 'module:languageperdomain/views/templates/hook/languageperdomain_select.tpl';
		$this->confirmUninstall = $this->l('Are you sure about disabling Language per domain?');
		$this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
		parent::__construct();

		$this->context->smarty->assign( 'languageperdomain_base_url', array( $this, 'getBaseLink' ) );
	}

	public function install()
	{
		include(dirname(__FILE__).'/sql/install.php');

		return parent::install() &&
			$this->registerHook( 'header' ) &&
			$this->registerHook( 'displayTop' ) &&
			$this->registerHook( 'actionFrontControllerSetVariables' );
	}

	public function uninstall()
	{
		include(dirname(__FILE__).'/sql/uninstall.php');

		return parent::uninstall();
	}

	public function renderWidget($hookName = null, array $configuration = [])
	{
		$languages = Language::getLanguages(TRUE, $this->context->shop->id);

		if (1 < count($languages)) {
			$this->smarty->assign($this->getWidgetVariables($hookName, $configuration));

			return $this->fetch($this->templateFile);
		}

		return FALSE;
	}

	public function getWidgetVariables($hookName = null, array $configuration = [])
	{
		$languages = Language::getLanguages(TRUE, $this->context->shop->id);

		foreach ($languages as &$lang) {
			$lang['name_simple'] = $this->getNameSimple($lang['name']);
		}
		$allExtensions = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'languageperdomain`');

		$toReplace = "";
		foreach ($allExtensions as $ext) {
			if ($ext["lang_id"] == $this->context->language->id) {
				$toReplace = $ext["new_target"];
			}
		}

		return array(
			'allExtensions' => $allExtensions,
			'toReplace' => $toReplace,
			'languages' => $languages,
			'current_language' => array(
				'id_lang' => $this->context->language->id,
				'name' => $this->context->language->name,
				'name_simple' => $this->getNameSimple($this->context->language->name),
			),
		);
	}

	private function getNameSimple($name)
	{
		return preg_replace('/\s\(.*\)$/', '', $name);
	}

	public function getLangDomain( $full = false, $idLang = null, $idShop = null )
	{
		if ( ! $idLang ) {
			$idLang = $this->context->language->id;
		}
		if ( ! $idShop ) {
			$idShop = $this->context->shop->id;
		}

		$result = Db::getInstance()->getRow(
			'
            SELECT *
            FROM `'._DB_PREFIX_.'languageperdomain`
            WHERE `lang_id` = '.(int)$idLang.'
            AND `target_replace` = '.(int)$idShop.'
            '
		);

		if ( $full ) {
			return $result;
		}
		return $result['new_target'];
	}

	public function getBaseLink()
	{
		return $this->context->link->getBaseLink();
	}

	public function replaceDomain( $url )
	{
		// Only run in front-end context.
		if ( false === strpos( Context::getContext()->controller->controller_type, 'front' ) ) {
			return $url;
		}

		$parsed = parse_url( $url );
		if ( empty( $parsed['host'] ) ) {
			return $url;
		}

		return str_replace( $parsed['host'], $this->getLangDomain(), $url );
	}

	public function hookActionFrontControllerSetVariables( $params )
	{
		if ( empty( $params['templateVars'] )) {
			return;
		}
		$vars = $params['templateVars'];

		if ( empty( $vars['urls'] ) ) {
			return;
		}

		$urls = $vars['urls'];

		$replace = array(
			'base_url',
			'current_url',
			'shop_domain_url',
		);

		foreach ( $urls as $key => $url ) {
			if ( in_array( $key, $replace, true ) ) {
				$urls[ $key ] = $this->replaceDomain( $url );
			}
		}

		$params['templateVars']['urls'] = $urls;
	}

	public function getContent()
	{
		$output = null;
		if (Tools::isSubmit('submit'.$this->name)) {
			$languages = Language::getLanguages(TRUE, $this->context->shop->id);
			if (count($languages) <= 0) {
				$output .= $this->displayError($this->l('No active languages'));
			}
			foreach ($languages as $lang) {
				$updatedTarget = Tools::getValue('languageperdomainID'.$lang["id_lang"]);
				if (urlencode(urldecode($updatedTarget)) === $updatedTarget && $updatedTarget != null) {
					$this->updatePSURL($updatedTarget, $lang["id_lang"]);
					$this->getNewTargetOfLang($lang["id_lang"]) ? $this->updateDomain(
						$updatedTarget,
						$lang["id_lang"]
					) : $this->createDomain($updatedTarget, $lang["id_lang"]);
				} else {
					$output .= $this->displayError(
						$this->l('Not a valid URL for '.$this->getNameSimple($lang['name']))
					);
				}
			}
			$output .= $this->displayConfirmation($this->l('Settings updated'));
		}

		return $output.$this->displayForm();
	}

	public function updatePSURL($updatedTarget, $langId)
	{
		$oldDomain = $this->getNewTargetOfLang($langId);
		$result = $oldDomain ? Db::getInstance()->update(
			'shop_url',
			array(
				'domain' => pSQL($updatedTarget),
				'domain_ssl' => pSQL($updatedTarget),
			),
			'domain = "'.pSQL($oldDomain).'" AND id_shop = '.(int)Context::getContext()->shop->id.''
		) :
			Db::getInstance()->insert(
				'shop_url',
				array(
					'domain' => pSQL($updatedTarget),
					'domain_ssl' => pSQL($updatedTarget),
					'id_shop' => (int)Context::getContext()->shop->id,
					'main' => (int)1,
					'active' => (int)1,
				)
			);
	}

	public function getNewTargetOfLang($langId)
	{
		return Db::getInstance()->getValue(
			'SELECT `new_target` FROM `'._DB_PREFIX_.'languageperdomain` WHERE `lang_id` = '.(int)$langId.' AND `target_replace` = '.(int)Context::getContext(
			)->shop->id.''
		);
	}

	public function updateDomain($updatedTarget, $langId)
	{

		$result = Db::getInstance()->update(
			'languageperdomain',
			array(
				'new_target' => pSQL($updatedTarget),
			),
			'lang_id = '.(int)$langId.' AND target_replace = '.(int)Context::getContext()->shop->id.''
		);
	}

	public function createDomain($updatedTarget, $langId)
	{
		$createNew = Db::getInstance()->insert(
			'languageperdomain',
			array(
				'lang_id' => (int)$langId,
				'new_target' => pSQL($updatedTarget),
				'target_replace' => (int)Context::getContext()->shop->id,
			)
		);
	}

	public function displayForm()
	{

		// Get default language
		$defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

		$languages = Language::getLanguages(TRUE, $this->context->shop->id);

		$domainInputArray = [];

		foreach ($languages as $lang) {
			array_push(
				$domainInputArray,
				[
					'type' => 'text',
					'label' => $this->l($lang["name"]),
					'name' => 'languageperdomainID'.$lang["id_lang"],
					'size' => 20,
					'required' => TRUE,
					'value' => "emptyForNow",
				]
			);
		}


		$fieldsForm[0]['form'] = [
			'legend' => [
				'title' => $this->l('Settings'),
			],
			'input' => $domainInputArray,
			'submit' => [
				'title' => $this->l('Save'),
				'class' => 'btn btn-default pull-right',
			],
		];


		$helper = new HelperForm();

		// Module, token and currentIndex
		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

		// Language
		$helper->default_form_language = $defaultLang;
		$helper->allow_employee_form_lang = $defaultLang;

		// Title and toolbar
		$helper->title = $this->displayName;
		$helper->show_toolbar = TRUE;        // false -> remove toolbar
		$helper->toolbar_scroll = TRUE;      // yes - > Toolbar is always visible on the top of the screen.
		$helper->submit_action = 'submit'.$this->name;
		$helper->toolbar_btn = [
			'save' => [
				'desc' => $this->l('Save'),
				'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
					'&token='.Tools::getAdminTokenLite('AdminModules'),
			],
			'back' => [
				'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
				'desc' => $this->l('Back to list'),
			],
		];


		foreach ($languages as $lang) {
			$sql = Db::getInstance()->getValue(
				'SELECT `new_target` FROM `'._DB_PREFIX_.'languageperdomain` WHERE `lang_id` = '.(int)$lang["id_lang"].' AND `target_replace` = '.(int)Context::getContext(
				)->shop->id.''
			);
			$helper->fields_value['languageperdomainID'.$lang["id_lang"]] = $sql;
		}


		return $helper->generateForm($fieldsForm);
	}
}
