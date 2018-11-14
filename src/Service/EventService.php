<?php
namespace PHPFacile\Event\Db\Service;

use PHPFacile\Event\Json\EventJsonService;
use PHPFacile\Zend\Db\Helper\ZendDbHelper;

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
     * Service to store the location of the event in database
     *
     * @var Adapter
     */
    protected $locationService;

    protected $newEventExtraData = [];

    /**
     * The constructor
     *
     * @param AdapterInterface $adapter         Adapter for the database
     * @param LocationService  $locationService Service to store the location of the event in database
     *
     * @return EventService
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter           = $adapter;
        $this->newEventExtraData = ['status' => 'submitted'];
    }

    public function setLocationService($locationService)
    {
        $this->locationService = $locationService;
    }

    public function getDbRowFromStdClassEventSubmission($eventSubmission)
    {
        $event      = $eventSubmission->event;
        $submitter  = $eventSubmission->submitter;

        // Here we assume that submitter data are stored within the same table
        // as the event. But this should be configurable and an additionnal service call
        // might be required (to get a user id for example)
        // Here type field name should be configuration. Should it be
        // type (for a string) or type_id ?
        $values           = [];
        $values['locale'] = $eventSubmission->locale;
        $values['name']   = $event->name;
        $values['url']    = $event->url;
        $values['type']   = $event->type;
        $values['datetime_start'] = $event->dateTimeStart;
        $values['datetime_end']   = $event->dateTimeEnd;
        //$values['address_full']   = $event->address->name;
        $values['place_name']   = $event->location->place->name;
        if (property_exists($event->location->place, 'postalCode')) {
            $values['postal_code']  = $event->location->place->postalCode;
        }
        $values['country_code'] = $event->location->place->country->code;

        $values['submitter_name']  = $submitter->name;
        $values['submitter_email'] = $submitter->email;

        if (property_exists($event->location->place, 'geocodedLocationId')) {
            $values['place_geocoder_location_id'] = $event->location->place->geocodedLocationId;
        }

        if (property_exists($eventSubmission, 'status')) {
            $values['status'] = $eventSubmission->status;
        }

        return $values;
    }

    /**
     * Saves into database an event submission (i.e. event + submitter data + ...) described as a StdClass
     * REM: Attributes validity checking is not in the scope of this method and must be performed by the calling method
     *
     * @param StdClass $eventSubmission A class with a event field, a user field and others if required
     *
     * @return void
     */
    public function insertStdClassEventSubmission($eventSubmission)
    {
        // TODO Add SQL transaction ?

        $values = self::getDbRowFromStdClassEventSubmission($eventSubmission);
        $values += $this->newEventExtraData;

        $values['submission_datetime_utc'] = ZendDbHelper::getUTCTimestampExpression($this->adapter);

        $sql   = new Sql($this->adapter);
        $query = $sql
            ->insert('events')
            ->values($values);
        $stmt  = $sql->prepareStatementForSqlObject($query);
        $stmt->execute();
    }

    /**
     */
    public function updateStdClassEventSubmission($eventSubmission)
    {
        if ((false === property_exists($eventSubmission, 'id'))
            ||(0 == strlen($eventSubmission->id))) {
            throw new \Exception('Can\'t update. No event submission id provided.');
        }

        $place = $eventSubmission->event->location->place;
        $geocodedLocationId = $this->locationService->getGeocoderLocationIdFromGeocodedPlaceStdClassAfterInsertIfNeeded($place);
        $eventSubmission->event->location->place->geocodedLocationId = $geocodedLocationId;

        $values = self::getDbRowFromStdClassEventSubmission($eventSubmission);

        $sql   = new Sql($this->adapter);
        $query = $sql
            ->update('events')
            ->set($values)
            ->where(['id' => $eventSubmission->id]);
        $stmt  = $sql->prepareStatementForSqlObject($query);
        $stmt->execute();
    }

    public static function row2EventSubmission($row, $eventSubmission)
    {
        if (false === property_exists($eventSubmission, 'event')) {
            $eventSubmission->event = new \StdClass();
        }

        if (false === property_exists($eventSubmission, 'submitter')) {
            $eventSubmission->submitter = new \StdClass();
        }

        if (false === property_exists($eventSubmission->event, 'location')) {
            $eventSubmission->event->location = new \StdClass();
        }

        if (false === property_exists($eventSubmission->event->location, 'place')) {
            $eventSubmission->event->location->place = new \StdClass();
        }

        if (false === property_exists($eventSubmission->event->location->place, 'country')) {
            $eventSubmission->event->location->place->country = new \StdClass();
        }

        if (true === array_key_exists('submission_datetime_utc', $row)) {
            $eventSubmission->submissionDateTimeUTC = $row['submission_datetime_utc'];
        }

        if (true === array_key_exists('id', $row)) {
            $eventSubmission->id = $row['id'];
        }

        if (true === array_key_exists('locale', $row)) {
            $eventSubmission->locale = $row['locale'];
        }

        if (true === array_key_exists('name', $row)) {
            $eventSubmission->event->name = $row['name'];
        }

        if (true === array_key_exists('url', $row)) {
            $eventSubmission->event->url = $row['url'];
        }

        if (true === array_key_exists('type', $row)) {
            $eventSubmission->event->type = $row['type'];
        }

        if (true === array_key_exists('datetime_start', $row)) {
            $eventSubmission->event->dateTimeStart = $row['datetime_start'];
        }

        if (true === array_key_exists('datetime_end', $row)) {
            $eventSubmission->event->dateTimeEnd = $row['datetime_end'];
        }

        if (true === array_key_exists('place_name', $row)) {
            $eventSubmission->event->location->place->name = $row['place_name'];
        }

        if (true === array_key_exists('postal_code', $row)) {
            $eventSubmission->event->location->place->postalCode = $row['postal_code'];
        }

        if (true === array_key_exists('country_code', $row)) {
            $eventSubmission->event->location->place->country->code = $row['country_code'];
        }

        if (true === array_key_exists('submitter_name', $row)) {
            $eventSubmission->submitter->name = $row['submitter_name'];
        }

        if (true === array_key_exists('submitter_email', $row)) {
            $eventSubmission->submitter->email = $row['submitter_email'];
        }

        return $eventSubmission;
    }

    /**
     * Returns a list of event matching a given criteria
     *
     * @param array $where Filter to be applied on table rows
     * @param int   $limit Max number of results
     *
     * @return StdClass[]
     */
    public function getEventSubmissions($where, $limit = 10)
    {
        $sql   = new Sql($this->adapter);
        $query = $sql
            ->select('events')
            ->where($where)
            ->limit($limit);
        $stmt  = $sql->prepareStatementForSqlObject($query);
        $rows = $stmt->execute();

        $eventSubmissions = [];
        foreach ($rows as $row) {
            $eventSubmission = new \StdClass();
            $eventSubmissions[] = self::row2EventSubmission($row, $eventSubmission);
        }

        return $eventSubmissions;
    }

    public function getNextEventSubmissionsToBeValidated($limit = 10)
    {
        // TODO validation checking rule (field name=status? value=submitted?) should be configurable
        $where = ['status' => 'submitted'];
        return $this->getEventSubmissions($where, $limit);
    }

    /**
     *
     * @return StdClass|null
     */
    public function getNextEventSubmissionToBeValidated()
    {
        $events = $this->getNextEventSubmissionsToBeValidated(1);
        if (1 === count($events)) {
            return $events[0];
        }

        return null;
    }
    /*
        Compute UTC datetimes
        But only if timezone is available (which is the case with geonames
        but not with nominatim)
    */
    /*
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
    */

    /**
     * Returns a list of event as an array ready to be exported in JSON format
     * (as expected by the target project) by simple call to json_encode();
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
                'geocoder_locations',
                'events.place_geocoder_location_id=geocoder_locations.id',
                [
                    'geocoded_longitude' => 'geocoded_longitude',
                    'geocoded_latitude'  => 'geocoded_latitude',
                ]
            )
            ->join(
                'places',
                'geocoder_locations.place_id = places.id',
                [
                    'location_place' => 'name',
                    'location_country' => 'country_code'
                ],
                'left'
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
