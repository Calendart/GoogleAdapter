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

use Http\Client\HttpClient;
use Http\Client\Common\PluginClient;
use Http\Client\Common\Plugin\BaseUriPlugin;
use Http\Client\Common\Plugin\RedirectPlugin;
use Http\Client\Common\Plugin\HeaderDefaultsPlugin;
use Http\Client\Common\Plugin\ContentLengthPlugin;
use Http\Client\Common\Plugin\AuthenticationPlugin;

use Http\Message\UriFactory;
use Http\Message\MessageFactory;
use Http\Message\Authentication\Bearer;

use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Discovery\UriFactoryDiscovery;

use CalendArt\Adapter\AdapterInterface;

use CalendArt\Adapter\Google\Criterion\Field;
use CalendArt\Adapter\Google\Criterion\Collection;
use CalendArt\Adapter\Google\Exception\BackendException;

use CalendArt\AbstractCalendar;

/**
 * Google Adapter - He knows how to dialog with google's calendars !
 *
 * This requires to have an OAuth2 token established with the following scopes :
 * - email
 * - https://www.googleapis.com/auth/calendar
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 */
class GoogleAdapter implements AdapterInterface
{
    use ResponseHandler;

    /** @var HttpClient */
    private $client;

    /** @var MessageFactory */
    private $messageFactory;

    /** @var CalendarApi CalendarApi to use */
    private $calendarApi;

    /** @var EventApi[] */
    private $eventApis;

    /** @var User Current user, associated with the given token */
    private $user;

    /** @param string $token access token delivered by google's oauth system */
    public function __construct(
        $token,
        HttpClient $client = null,
        MessageFactory $messageFactory = null,
        UriFactory $uriFactory = null
    ) {
        $uriFactory = $uriFactory ?: UriFactoryDiscovery::find();

        $this->client = new PluginClient(
            $client ?: HttpClientDiscovery::find(),
            [
                new AuthenticationPlugin(new Bearer($token)),
                new BaseUriPlugin($uriFactory->createUri(
                    $uriFactory->createUri('https://www.googleapis.com')
                )),
                new RedirectPlugin,
                new ContentLengthPlugin,
                new HeaderDefaultsPlugin([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
            ]
        );

        $this->messageFactory = $messageFactory ?: MessageFactoryDiscovery::find();
    }

    /** {@inheritDoc} */
    public function getCalendarApi()
    {
        if (null === $this->calendarApi) {
            $this->calendarApi = new CalendarApi($this);
        }

        return $this->calendarApi;
    }

    /** {@inheritDoc} */
    public function getEventApi(AbstractCalendar $calendar = null)
    {
        if (!$calendar instanceof Calendar) {
            throw new InvalidArgumentException('Wrong calendar provided, expected a google calendar');
        }

        if (!isset($this->eventApis[$calendar->getId()])) {
            $this->eventApis[$calendar->getId()] = new EventApi($this, $calendar);
        }

        return $this->eventApis[$calendar->getId()];
    }

    /**
     * Get the current user ; fetches its information if it was not fetched yet
     *
     * @return User
     */
    public function getUser()
    {
        if (null == $this->user) {
            $fields = [new Field('id'),
                       new Field('name'),
                       new Field('emails')];

            $criterion = new Collection([new Collection($fields, 'fields')]);
            $result = $this->get('/plus/v1/people/me', ['query' => $criterion->build()]);

            $emails = [];

            foreach ($result['emails'] as $email) {
                if ('account' !== $email['type']) {
                    continue;
                }

                $emails[] = $email['value'];
            }

            $name = sprintf('%s %s', $result['name']['givenName'], $result['name']['familyName']);

            $this->user = new User($name, $emails, $result['id']);
        }

        return $this->user;
    }

    /**
     * Sets a Google User
     *
     * @return $this
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    public function sendRequest($method, $uri, array $headers = [], $body = null)
    {
        // deal with query string parameters
        if (isset($headers['query'])) {
            $uri = sprintf('%s?%s', $uri, implode('&', array_map(function ($k, $v) {
                $v = is_array($v) ? implode(',', $v) : $v;
                return sprintf('%s=%s', $k, $v);
            }, array_keys($headers['query']), array_values($headers['query']))));
            unset($headers['query']);
        }

        $response = $this->client->sendRequest(
            $this->messageFactory->createRequest($method, $uri, $headers, $body)
        );
        $this->handleResponse($response);
        $result = json_decode($response->getBody(), true);

        if (null === $result) {
            throw new Exception\BackendException($response);
        }

        return $result;
    }
}
