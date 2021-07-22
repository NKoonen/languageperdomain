<?php
class JavascriptManager extends JavascriptManagerCore
{
    protected function add($id, $fullPath, $position, $priority, $inline, $attribute, $server)
    {
        $priority = is_int($priority) ? $priority : self::DEFAULT_PRIORITY;
        $position = $this->getSanitizedPosition($position);
        $attribute = $this->getSanitizedAttribute($attribute);

        if ('remote' === $server) {
            $uri = $fullPath;
            $type = 'external';
        } else {
            $uri = Context::getContext()->link->getBaseLink() . $this->getUriFromPath($fullPath);
            //$uri = $this->getFQDN() . $this->getUriFromPath($fullPath);
            $type = ($inline) ? 'inline' : 'external';
        }

        $this->list[$position][$type][$id] = [
            'id' => $id,
            'type' => $type,
            'path' => $fullPath,
            'uri' => $uri,
            'priority' => $priority,
            'attribute' => $attribute,
            'server' => $server,
        ];
    }

    private function getSanitizedPosition($position)
    {
        return in_array($position, $this->valid_position, true) ? $position : self::DEFAULT_JS_POSITION;
    }

    private function getSanitizedAttribute($attribute)
    {
        return in_array($attribute, $this->valid_attribute, true) ? $attribute : '';
    }

}