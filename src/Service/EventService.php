<?php
namespace PHPFacile\Event\Db\Service;

use PHPFacile\Event\Json\EventJsonService;

use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Sql;

class EventService
{
    /**
     * Adapter for the database
     *
     * @var Adapter
     */
    protected $adapter;

    /**
     * The constructor
     *
     * @param AdapterInterface $adapter Adapter for the database
     *
     * @return LocationService
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Saves into database an event described as a StdClass
     * REM: Attributes validity checking is not in the scope of this method and must be performed by the calling method
     *
     * @param StdClass $event A class with name, dateTimeStart, dateTimeEnd, addressFull, etc. fields
     *
     * @return void
     */
    public function insertStdClassEvent($event)
    {
        // TODO Add SQL transaction ?
        $placeId = $this->locationService->getIdOfStdClassLocationAfterInsertIfNeeded($event->place);

        $values         = [];
        $values['name'] = $event->name;
        $values['datetime_start'] = $event->dateTimeStart;
        $values['datetime_end']   = $event->dateTimeEnd;
        $values['address_full']   = $event->address->name;
        $values['place_id']       = $placeId;

        /*
            Compute UTC datetimes
            But only if timezone is available (which is the case with geonames
            but not with nominatim)
        */

        if (strlen($event->place->geocoding->timezone) > 0) {
            $timeZone = new \DateTimeZone($event->place->geocoding->timezone);

            $dateTime = new \DateTime($event->dateTimeStart, $timeZone);
            $dateTime->setTimeZone(new \DateTimeZone('UTC'));
            $values['datetime_start_utc'] = $dateTime->format('Y-m-d H:i:s');

            $dateTime = new \DateTime($event->dateTimeEnd, $timeZone);
            $dateTime->setTimeZone(new \DateTimeZone('UTC'));
            $values['datetime_end_utc'] = $dateTime->format('Y-m-d H:i:s');
        } else {
            $values['datetime_start_utc'] = null;
            $values['datetime_end_utc']   = null;
        }

        $sql   = new Sql($this->adapter);
        $query = $sql
            ->insert('events')
            ->values($values);
        $stmt  = $sql->prepareStatementForSqlObject($query);
        $stmt->execute();
    }

    /**
     * Returns a list of event as an array ready to be exported in JSON format
     * (as expected by the CPLC event map) by simple call to json_encode();
     *
     * @param mixed            $filter           Filter to be applied to the event list (to be defined)
     * @param EventJsonService $eventJsonService A service to transform database row into a JSON ready array
     *
     * @return array
     */
    public function getEventsAsArrayReadyForJSON($filter, $eventJsonService = null)
    {
        if (null === $eventJsonService) {
            $eventJsonService = new EventJsonService();
        }

        // TODO Take into account filter (to be defined)
        // so as (maybe) to return only future events within a given area (ex: France)
        $sql   = new Sql($this->adapter);
        $query = $sql
            ->select('events')
            ->columns(
                [
                    'event_id'       => 'id',
                    'name'           => 'name',
                    'datetime_start' => 'datetime_start',
                ]
            )
            ->join(
                'locations',
                'events.place_id=locations.id',
                [
                    'geocoded_longitude' => 'geocoded_longitude',
                    'geocoded_latitude'  => 'geocoded_latitude',
                    'location_place'     => 'place',
                    'location_country'   => 'country',
                ]
            );
        $stmt  = $sql->prepareStatementForSqlObject($query);
        $rows  = $stmt->execute();

        $arrayForJSON = [];
        foreach ($rows as $row) {
            $arrayForJSON[] = $eventJsonService->getDbRowAsRowReadyForJSON($row);
        }

        return $arrayForJSON;
    }
}
