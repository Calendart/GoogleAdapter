<?php

namespace CalendArt\Adapter\Google;

use InvalidArgumentException;

use CalendArt\UriAttachment;

class Attachment implements UriAttachment
{
    private $name;
    private $uri;
    private $icon;
    private $id;
    private $mimeType;
    private $raw;

    public function __construct($name, $uri)
    {
        $this->uri = $uri;
        $this->name = $name;
    }

    public static function hydrate(array $data)
    {
        if (!isset($data['fileId'])) {
            throw new InvalidArgumentException(sprintf('Missing at least one of the mandatory properties "fileId" ; got ["%s"]', implode('", "', array_keys($data))));
        }

        $attachment = new static($data['title'], $data['fileUrl']);
        $attachment->id = $data['fileId'];
        $attachment->icon = $data['iconLink'];
        $attachment->mimeType = $data['mimeType'];
        $attachment->raw = $data;

        return $attachment;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getIcon()
    {
        return $this->icon;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getMimeType()
    {
        return $this->mimeType;
    }

    public function getRaw()
    {
        return $this->raw;
    }

    public function getContents()
    {
        return file_get_contents($this->getUri());
    }
}
