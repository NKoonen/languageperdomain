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