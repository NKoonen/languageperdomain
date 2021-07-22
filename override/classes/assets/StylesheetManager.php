<?php
class StylesheetManager extends StylesheetManagerCore
{
    private $valid_media = [
        'all',
        'braille',
        'embossed',
        'handheld',
        'print',
        'projection',
        'screen',
        'speech',
        'tty',
        'tv',
    ];

    protected function add($id, $fullPath, $media, $priority, $inline, $server)
    {
        $priority = is_int($priority) ? $priority : self::DEFAULT_PRIORITY;
        $media = $this->getSanitizedMedia($media);
        if ('remote' === $server) {
            $uri = $fullPath;
            $type = 'external';
        } else {
            $uri = Context::getContext()->link->getBaseLink() . $this->getUriFromPath($fullPath);
            //$uri = $this->getFQDN() . $this->getUriFromPath($fullPath);
            $type = ($inline) ? 'inline' : 'external';
        }

        $this->list[$type][$id] = [
            'id' => $id,
            'type' => $type,
            'path' => $fullPath,
            'uri' => $uri,
            'media' => $media,
            'priority' => $priority,
            'server' => $server,
        ];
    }

    private function getSanitizedMedia($media)
    {
        return in_array($media, $this->valid_media, true) ? $media : self::DEFAULT_MEDIA;
    }

}