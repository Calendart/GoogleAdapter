<?php
/**
 * This file is part of the CalendArt package
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 *
 * @copyright Wisembly
 * @license   http://www.opensource.org/licenses/MIT-License MIT License
 */

namespace CalendArt\Adapter\Google;

use InvalidArgumentException;

use GuzzleHttp\Client as Guzzle;

use Doctrine\Common\Collections\ArrayCollection;

use CalendArt\Adapter\EventApiInterface,
    CalendArt\AbstractEvent as CalendArtAbstractEvent,

    CalendArt\Adapter\Google\Event\BasicEvent,
    CalendArt\Adapter\Google\Exception\ApiErrorException,
    CalendArt\Adapter\Google\Exception\CriterionNotFoundException,

    CalendArt\Adapter\Google\AbstractCriterion,
    CalendArt\Adapter\Google\Criterion\Field,
    CalendArt\Adapter\Google\Criterion\Collection;

/**
 * Google Adapter for the Calendars
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 */
class EventApi implements EventApiInterface
{
    /** @var Guzzle Guzzle Http Client to use */
    private $guzzle;

    /** @var Calendar */
    private $calendar;

    /** @var Field[] */
    private $fields;

    /** @var GoogleAdapter */
    private $adapter;

    public function __construct(Guzzle $client, GoogleAdapter $adapter, Calendar $calendar)
    {
        $this->guzzle   = $client;
        $this->adapter  = $adapter;
        $this->calendar = $calendar;

        $this->fields = [new Field('id'),
                         new Field('end'),
                         new Field('etag'),
                         new Field('start'),
                         new Field('status'),
                         new Field('created'),
                         new Field('updated'),
                         new Field('summary'),
                         new Field('location'),
                         new Field('organizer'),
                         new Field('description'),
                         new Field('creator', [new Field('email'),
                                               new Field('displayName')]),

                         new Field('attendees', [new Field('email'),
                                                 new Field('resource'),
                                                 new Field('organizer'),
                                                 new Field('displayName'),
                                                 new Field('responseStatus')])];
    }

    /** {@inheritDoc} */
    public function getList(AbstractCriterion $criterion = null)
    {
        $nextPageToken = null;
        $query         = new Collection([]);
        $list          = new ArrayCollection;
        $calendars     = new ArrayCollection;

        $calendars[$this->calendar->getId()] = $this->calendar;

        $fields = [new Field('nextSyncToken'),
                   new Field('nextPageToken'),
                   new Field('items', $this->fields)];

        $query->addCriterion(new Collection([new Field(null, $fields)], 'fields'));

        if (null !== $criterion) {
            $query = $query->merge($criterion);
        }

        try {
            $showDeleted = (bool) $query->getCriterion('showDeleted');
        } catch (CriterionNotFoundException $e) {
            $showDeleted = false;
        }

        $query = $query->build();

        do {
            $current = $query;

            if (null !== $nextPageToken) {
                $current['pageToken'] = $nextPageToken;
            }

            $response = $this->guzzle->get(sprintf('calendars/%s/events', $this->calendar->getId()), ['query' => $current]);

            if (200 > $response->getStatusCode() || 300 <= $response->getStatusCode()) {
                throw new ApiErrorException($response);
            }

            $result = $response->json();

            foreach ($result['items'] as $item) {
                // ignore the short cancelled recurring events
                if (!$showDeleted && isset($item['status']) && AbstractEvent::STATUS_CANCELLED === $item['status']) {
                    continue;
                }

                $calendar = $this->calendar;

                // match the _real_ calendar for this event
                if (isset($item['organizer']) && (!isset($item['organizer']['self']) || false === $item['organizer']['self'])) {
                    try {
                        $data = $item['organizer'];

                        // the email is usually an identifier for the calendars
                        if (!isset($data['id'])) {
                            if (!isset($data['email'])) {
                                throw new InvalidArgumentException;
                            }

                            $data['id'] = $data['email'];
                        }

                        if (!isset($calendars[$data['id']])) {
                            $data += ['summary' => !isset($data['displayName'])
                                        ? !isset($data['email'])
                                            ? null
                                            : $data['email']
                                        : $data['displayName'],

                                      'timeZone' => null];

                            $calendars[$data['id']] = Calendar::hydrate($data);
                        }

                        $calendar = $calendars[$data['id']];
                    } catch (InvalidArgumentException $e) {
                        $calendar = $this->calendar;
                    }
                }

                $list[$item['id']] = BasicEvent::hydrate($calendar, $item);

                if ($calendar !== $this->calendar) {
                    $this->calendar->getEvents()->add($list[$item['id']]);
                }
            }

            $nextPageToken = isset($result['nextPageToken']) ? $result['nextPageToken'] : null;
        } while (null !== $nextPageToken);

        $this->calendar->setSyncToken($result['nextSyncToken']);

        return $list;
    }

    /** @return Calendar */
    public function getCalendar()
    {
        return $this->calendar;
    }

    /** {@inheritDoc} */
    public function get($identifier, AbstractCriterion $criterion = null)
    {
        $query = new Collection($this->fields, 'fields');

        if (null !== $criterion) {
            $query = $query->merge($criterion);
        }

        $response = $this->guzzle->get(sprintf('calendars/%s/events/%s', $this->calendar->getId(), $identifier), ['query' => $query->build()]);

        if (200 > $response->getStatusCode() || 300 <= $response->getStatusCode()) {
            throw new ApiErrorException($response);
        }

        return BasicEvent::hydrate($this->calendar, $response->json());
    }

    /** {@inheritDoc} */
    public function persist(CalendArtAbstractEvent $event)
    {
        if (!$event instanceof AbstractEvent) {
            throw new InvalidArgumentException('Wrong event provided, expected a google event');
        }

        $url = sprintf('calendars/%s/events/%s', $event->getCalendar()->getId(), $event->getId());

        $options = [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($event->export())
        ];

        $response = $this->guzzle->patch($url, $options);

        if (200 > $response->getStatusCode() || 300 <= $response->getStatusCode()) {
            throw new ApiErrorException($response);
        }
    }

    /** @return GoogleAdapter */
    public function getAdapter()
    {
        return $this->adapter;
    }
}

