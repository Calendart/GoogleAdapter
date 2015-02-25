<?php

namespace CalendArt\Adapter\Google\Event;

use DateTimeZone;

use CalendArt\Adapter\Google\Calendar,
    CalendArt\Adapter\Google\EventParticipation,
    CalendArt\Adapter\Google\User;

class PartialEventTest extends \PHPUnit_Framework_TestCase
{
    public function testExportWithNoChangedProperties()
    {
        $calendar = new Calendar('test@calendart.com', 'Test', new DateTimeZone('UTC'));

        $partialEvent = new PartialEvent($calendar);
        $export = $partialEvent->export();

        $this->assertEmpty($export);
    }

    public function testExportAfterChangedDescription()
    {
        $calendar = new Calendar('test@calendart.com', 'Test', new DateTimeZone('UTC'));

        $partialEvent = new PartialEvent($calendar);
        $partialEvent->setDescription('New description');
        $export = $partialEvent->export();

        $this->assertCount(1, $export);
        $this->assertArrayHasKey('description', $export);
        $this->assertEquals('New description', $export['description']);
    }

    public function testExportAfterChangedAttendees()
    {
        $calendar = new Calendar('test@calendart.com', 'Test', new DateTimeZone('UTC'));
        $user = new User('Test CalendArt', 'test@calendart.com');

        $partialEvent = new PartialEvent($calendar);

        $participation = new EventParticipation($partialEvent, $user);
        $participation->setStatus(EventParticipation::STATUS_ACCEPTED);
        $partialEvent->addParticipation($participation);

        $export = $partialEvent->export();

        $this->assertCount(1, $export);
        $this->assertArrayHasKey('attendees', $export);
        $this->assertArrayHasKey('email', $export['attendees'][0]);
        $this->assertEquals('test@calendart.com', $export['attendees'][0]['email']);
        $this->assertArrayHasKey('responseStatus', $export['attendees'][0]);
        $this->assertEquals(EventParticipation::STATUS_ACCEPTED, $export['attendees'][0]['responseStatus']);
    }
}
