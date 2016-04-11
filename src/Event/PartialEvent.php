<?php

namespace CalendArt\Adapter\Google\Event;

use DateTime;

use CalendArt\Adapter\Google\Calendar;
use CalendArt\Adapter\Google\PartialInterface;
use CalendArt\EventParticipation as BaseEventParticipation;

/**
 * Represents a PATCH'd event
 *
 * this is used to make a patch, and dump only changed properties
 * of a BasicEvent
 *
 * @author Rémy Gazelot <r.gazelot@gmail.com>
 */
class PartialEvent extends BasicEvent implements PartialInterface
{
    /** @var array */
    private $changedProperties = [];

    public function __construct(Calendar $calendar, $id = null)
    {
        parent::__construct($calendar);

        if (null !== $id) {
            $this->id = $id;
        }
    }

    /** {@inheritDoc} */
    public function setVisibility($visibility)
    {
        $this->changedProperties['visibility'] = true;

        parent::setVisibility($visibility);
    }

    /** {@inheritDoc} */
    public function setStackable($stackable)
    {
        $this->changedProperties['stackable'] = true;

        parent::setStackable($stackable);
    }

    /** {@inheritDoc} */
    public function setStatus($status)
    {
        $this->changedProperties['status'] = true;

        parent::setStatus($status);
    }

    /** {@inheritDoc} */
    public function setName($name)
    {
        $this->changedProperties['summary'] = true;

        parent::setName($name);
    }

    /** {@inheritDoc} */
    public function setDescription($description)
    {
        $this->changedProperties['description'] = true;

        parent::setDescription($description);
    }

    /** {@inheritDoc} */
    public function setStart(DateTime $start)
    {
        $this->changedProperties['start'] = true;

        parent::setStart($start);
    }

    /** {@inheritDoc} */
    public function setEnd(DateTime $end)
    {
        $this->changedProperties['end'] = true;

        parent::setEnd($end);
    }

    /** {@inheritDoc} */
    public function addParticipation(BaseEventParticipation $participation)
    {
        $this->changedProperties['attendees'] = true;

        parent::addParticipation($participation);
    }

    /** {@inheritDoc} */
    public function export()
    {
        $parentExport = parent::export();
        $export = [];

        foreach ($parentExport as $property => $value) {
            if (!isset($this->changedProperties[$property]) || true !== $this->changedProperties[$property]) {
                continue;
            }

            $export[$property] = $value;
        }

        return $export;
    }
}
