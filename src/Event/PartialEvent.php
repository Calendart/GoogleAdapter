<?php

namespace CalendArt\Adapter\Google\Event;

use DateTime;

use CalendArt\EventParticipation as BaseEventParticipation,

    CalendArt\Adapter\Google\PartialInterface;

class PartialEvent extends BasicEvent implements PartialInterface
{
    /** @var array $proxyProperties */
    private $changedProperties = [];

    public function setVisibility($visibility)
    {
        $this->changedProperties['visibility'] = true;

        parent::setVisibility($visibility);
    }

    public function setStackable($stackable)
    {
        $this->changedProperties['stackable'] = true;

        parent::setStackable($stackable);
    }


    public function setStatus($status)
    {
        $this->changedProperties['status'] = true;

        parent::setStatus($status);
    }

    public function setName($name)
    {
        $this->changedProperties['name'] = true;

        parent::setName($name);
    }

    public function setDescription($description)
    {
        $this->changedProperties['description'] = true;

        parent::setDescription($description);
    }

    public function setStart(DateTime $start)
    {
        $this->changedProperties['start'] = true;

        parent::setStart($start);
    }

    public function setEnd(DateTime $end)
    {
        $this->changedProperties['end'] = true;

        parent::setEnd($end);
    }

    public function addParticipation(BaseEventParticipation $participation)
    {
        $this->changedProperties['attendees'] = true;

        parent::addParticipation($participation);
    }

    public function export()
    {
        $parentExport = parent::export();
        $export = [];

        foreach($parentExport as $property => $value) {
            if (!isset($this->changedProperties[$property]) || true !== $this->changedProperties[$property]) {
                continue;
            }

            $export[$property] = $value;
        }

        return $export;
    }
}
