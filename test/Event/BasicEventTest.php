<?php

namespace CalendArt\Adapter\Google\test\Event;

use Prophecy\Argument;

use CalendArt\Adapter\Google\Event\BasicEvent;

class BasicEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getWrongEndData
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage When the "end" property is missing, the "endTimeUnspecified" property must be specified or must be worth true
     */
    public function testHydrateWithoutEnd(array $endData)
    {
        $data = array_merge([
            'id' => '1',
            'etag' => '1',
            'status' => 'confirmed',
            'creator' => ['name' => 'John Doe'],
            'created' => '2015-01-01',
            'start' => ['dateTime' => '2015-01-01T10:00:00'],
        ], $endData);

        $calendar = $this->prophesize('CalendArt\Adapter\Google\Calendar');

        BasicEvent::hydrate($calendar->reveal(), $data);
    }

    public function getWrongEndData()
    {
        return [
            [[]],
            [['endTimeUnspecified' => false]],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The start and the end dates should be an array
     */
    public function testHydrateWithWrongEndFormat()
    {
        $data = [
            'id' => '1',
            'etag' => '1',
            'status' => 'confirmed',
            'creator' => ['name' => 'John Doe'],
            'created' => '2015-01-01',
            'start' => ['dateTime' => '2015-01-01T10:00:00'],
            'end' => 'foo',
        ];

        $calendar = $this->prophesize('CalendArt\Adapter\Google\Calendar');

        BasicEvent::hydrate($calendar->reveal(), $data);
    }

    public function testHydrateWithoutEndButEndTimeUnspecified()
    {
        $data = [
            'id' => '1',
            'etag' => '1',
            'status' => 'confirmed',
            'creator' => ['name' => 'John Doe'],
            'created' => '2015-01-01',
            'start' => ['dateTime' => '2015-01-01T10:00:00'],
            'endTimeUnspecified' => true,
        ];

        $collection = $this->prophesize('Doctrine\Common\Collections\Collection');
        $collection->add(Argument::any())->shouldBeCalled();

        $calendar = $this->prophesize('CalendArt\Adapter\Google\Calendar');
        $calendar->getEvents()->willReturn($collection->reveal());

        $event = BasicEvent::hydrate($calendar->reveal(), $data);

        $this->assertNull($event->getEnd());
    }

    public function testHydrateWithEndAndEndTimeUnspecified()
    {
        $data = [
            'id' => '1',
            'etag' => '1',
            'status' => 'confirmed',
            'creator' => ['name' => 'John Doe'],
            'created' => '2015-01-01',
            'start' => ['dateTime' => '2015-01-01T10:00:00'],
            'end' => ['dateTime' => '2015-01-01T11:00:00'],
            'endTimeUnspecified' => true,
        ];

        $collection = $this->prophesize('Doctrine\Common\Collections\Collection');
        $collection->add(Argument::any())->shouldBeCalled();

        $calendar = $this->prophesize('CalendArt\Adapter\Google\Calendar');
        $calendar->getEvents()->willReturn($collection->reveal());

        $event = BasicEvent::hydrate($calendar->reveal(), $data);

        $this->assertEquals('2015-01-01 11:00:00', $event->getEnd()->format('Y-m-d H:i:s'));
    }
}
