<?php
/**
 * 2020-now Inform-All & Keraweb
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
 * @author    Inform-All & Keraweb
 * @copyright 2020-now Inform-All & Keraweb
 */

if (!defined('_PS_VERSION_')) {
	exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Languageperdomain extends Module implements WidgetInterface
{
	protected $output = '';
	private $templateFile;

	/**
	 * @inheritDoc
	 */
	public function __construct()
	{
		$this->name = 'languageperdomain';
		$this->tab = 'administration';
		$this->version = '1.2.0';
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
	}

	/**
	 * @inheritDoc
	 */
	public function install()
	{
		include(dirname(__FILE__).'/sql/install.php');

		return parent::install() &&
			$this->registerHook( 'header' ) &&
			$this->registerHook( 'displayTop' ) &&
			$this->registerHook( 'actionFrontControllerSetVariables' ) &&
			$this->registerHook( 'actionHtaccessCreate' );
	}

	/**
	 * @inheritDoc
	 */
	public function uninstall()
	{
		include(dirname(__FILE__).'/sql/uninstall.php');

		return parent::uninstall();
	}

	/**
	 * @since 1.1.0
	 * @param Context $context
	 * @return bool
	 */
	public function isAdmin( $context = null ) {
		if ( ! $context instanceof Context ) {
			$context = $this->context;
		}

		if ( $context->controller instanceof Controller ) {
			if ( $context->controller instanceof AdminController ) {
				return true;
			}
			$controller_type = $context->controller->controller_type;
			if ( $controller_type && true === stripos( $controller_type, 'admin' ) ) {
				return true;
			}
		}

		return defined( '_PS_ADMIN_DIR_' );
	}

	/**
	 * @inheritDoc
	 */
	public function renderWidget($hookName = null, array $configuration = [])
	{
		$languages = Language::getLanguages(TRUE, $this->context->shop->id);

		if (1 < count($languages)) {
			$this->smarty->assign($this->getWidgetVariables($hookName, $configuration));

			return $this->fetch($this->templateFile);
		}

		return FALSE;
	}

	/**
	 * @inheritDoc
	 */
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

	/**
	 * @return string
	 */
	private function getNameSimple($name)
	{
		return preg_replace('/\s\(.*\)$/', '', $name);
	}

	/**
	 * @since 1.1.0
	 * @return array
	 */
	public function getDomains() {
		return Db::getInstance()->executeS(
			'
            SELECT *
            FROM `'._DB_PREFIX_.'languageperdomain`
            '
		);
	}

	/**
	 * @since 1.1.0
	 * @return string|array
	 */
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

	/**
	 * @since 1.1.0
	 * @param string $url
	 * @param int $idLang
	 * @param int $idShop
	 * @param bool $force
	 * @return string
	 */
	public function replaceDomain( $url, $idLang = null, $idShop = null, $force = false )
	{
		// Only run in front-end context.
		if ( ! $force && $this->isAdmin() ) {
			return $url;
		}

		if ( false === strpos( $url, '//' ) ) {
			// No protocol.
			$url = explode( '/', $url );
			$domain = $url[0];
			$url = implode( '/', $url );
		} else {
			$parts = parse_url( $url );
			if ( empty( $parts['host'] ) ) {
				return $url;
			}
			$domain = $parts['host'];
		}

		return str_replace( $domain, $this->getLangDomain( false, $idLang, $idShop ), $url );
	}

	/**
	 * @since 1.1.0
	 * @param array $params
	 */
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

	/**
	 * @inheritDoc
	 */
	public function getContent()
	{
		$output = null;
		if (Tools::isSubmit('submit'.$this->name)) {

			$shopId = $this->context->shop->id;
			$languages = Language::getLanguages(TRUE, $shopId);
			if (count($languages) <= 0) {
				$output .= $this->displayError($this->l('No active languages'));
			} else {
				foreach ($languages as $lang) {
					$updatedTarget = Tools::getValue('languageperdomainID'.$lang["id_lang"]);
					if (urlencode(urldecode($updatedTarget)) === $updatedTarget && $updatedTarget != null) {
						$this->updateDomain( $updatedTarget, $lang["id_lang"], $shopId );
					} else {
						$output .= $this->displayError(
							$this->l('Not a valid URL for '.$this->getNameSimple($lang['name']))
						);
					}
				}
				$output .= $this->displayConfirmation($this->l('Settings updated'));
			}
		}

		return $output.$this->displayForm();
	}

	/**
	 * @throws PrestaShopDatabaseException
	 *
	 * @param string $updatedTarget
	 * @param int $langId
	 * @param int $shopId
	 *
	 * @return bool
	 */
	public function updateDomain( $updatedTarget, $langId, $shopId )
	{
		$domain = $this->getLangDomain( false, $langId );
		$updatedTarget = pSQL($updatedTarget);
		$langId = (int) $langId;
		$shopId = (int) $shopId;

		if ( $domain ) {
			// Update PS shop URL's.
			Db::getInstance()->update(
				'shop_url',
				array(
					'domain'     => $updatedTarget,
					'domain_ssl' => $updatedTarget,
				),
				'domain = "'.pSQL($domain).'" AND id_shop = '. $shopId.''
			);
			// Update lang-per-domain table.
			Db::getInstance()->update(
				'languageperdomain',
				array(
					'new_target' => $updatedTarget,
				),
				'lang_id = '.$langId.' AND target_replace = '.$shopId.''
			);
		} else {
			// Create domain in PS shop URL's.
			Db::getInstance()->insert(
				'shop_url',
				array(
					'domain'     => $updatedTarget,
					'domain_ssl' => $updatedTarget,
					'id_shop'    => $shopId,
					'main'       => 1,
					'active'     => 1,
				)
			);
			// Create domain in lang-per-domain table.
			Db::getInstance()->insert(
				'languageperdomain',
				array(
					'lang_id'        => $langId,
					'new_target'     => $updatedTarget,
					'target_replace' => $shopId,
				)
			);
		}
	}

	/**
	 * @return string
	 */
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

	/**
	 * @since 1.1.0
	 * Make sure translation domains are accepted for media URL's.
	 */
	public function hookActionHtaccessCreate() {
		if (Shop::isFeatureActive() || Tools::hasMediaServer()) {
			return;
		}

		$path = _PS_ROOT_DIR_ . '/.htaccess';

		$content = file_get_contents($path);

		$domains = $this->getDomains();
		$domain_cond = '';
		foreach ( $domains as $domain ) {
			$domain_cond .= 'RewriteCond %{HTTP_HOST} ^' . $domain['new_target'] . '$ [OR]' . PHP_EOL;
		}

		$find = 'RewriteCond %{HTTP_HOST} ^';
		$replace = $domain_cond . $find;

		$content = str_replace( $find, $replace, $content );

		file_put_contents( $path, $content );
	}
}
