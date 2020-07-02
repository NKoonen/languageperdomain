{*
* 2007-2015 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2020 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div id="_desktop_language_selector" class="language-selector">
    <div class="language-selector-wrapper">
        <a href="#" class="language selected"><img src="{$urls.img_lang_url}{$language.id}.jpg"/></a>
        {foreach from=$allExtensions item=newExtension}
            {if $language.id != $newExtension["lang_id"]}
                {foreach from=$languages item=lang}
                    {if $lang["id_lang"] == $newExtension["lang_id"]}
                        {assign var="tempLink" value=$link->getLanguageLink($newExtension["lang_id"])}
                        {assign var="finalLink" value=$tempLink|replace:$toReplace:$newExtension["new_target"]}
                        <a class="language" href="{$finalLink}"><img src="{$urls.img_lang_url}{$lang.id_lang}.jpg"/></a>
                    {/if}
                {/foreach}
            {/if}
        {/foreach}
    </div>
</div>