<?php

use Sabre\VObject;
require_once('vendor/autoload.php');

// Part of AWL, paths might need adjustment
require_once('/usr/share/awl/inc/XMLDocument.php'); 
require_once('/usr/share/awl/inc/CalendarInfo.php');
require_once('/usr/share/awl/inc/CalDAVClient.php');

/**
 * This class uses the Sabre VObject and the CalDAVClient of the AWL library to establish a connection to the Kerio calendar
 * and provide simple interfaces for adding, updating and deleting calendar events. 
 * 
 * @author Tim
 */
class MSACalendarConnector {
    private $host = "";
    private $caldavUser = "";
    private $caldavPassword = "";
    private $calUrl = "";
    private $cal;
    private $timezone = '';
    
    public function __construct($host, $caldavUser, $caldavPassword, $calendarName = null, $timezone = 'Asia/Hong_Kong'){
        $this->init($host, $caldavUser, $caldavPassword, $timezone);
        $this->setWorkingCalendar($calendarName);
    }
    
    /**
     * Initializes the connection by creating a new CalDAVClient with all the connection parameters
     * 
     * @param string $host
     * @param string $caldavUser
     * @param string $caldavPassword
     * @param string $timezone
     */
    protected function init($host, $caldavUser, $caldavPassword, $timezone){
        if(strlen($host) > 0){
            $this->host = $host;
        }
        if(strlen($caldavUser) > 0){
            $this->caldavUser = $caldavUser;
        }
        if(strlen($caldavPassword) > 0){
            $this->caldavPassword = $caldavPassword;
        }
        if(strlen($timezone) > 0){
            $this->timezone = $timezone;
        }
        $this->cal = new CalDAVClient( $this->getUrl(), $this->caldavUser, $this->caldavPassword, "Calendar" );
    }
    
    /**
     * Set the CalDAVClient on the calendar that we want to synchronize to. By name of the calendar.
     * 
     * @param string $calendarName
     */
    protected function setWorkingCalendar($calendarName){
        if(!is_null($calendarName) && strlen($calendarName)>0){
            foreach($this->getCalendarArray() as $calendar){
                if ($calendar->displayname == $calendarName){
                    $this->cal->SetCalendar($this->host.$calendar->url);
                    $this->calUrl = $calendar->url;
                }
            }
        }
    }
    
    /**
     * Return all available calendars of this user
     */
    public function getCalendarArray(){
        return $this->cal->FindCalendars();
    }
    
    /**
     * Return the caldav principal path
     */
    public function getPrincipal(){
        return $this->cal->FindPrincipal(); 
    }
    
    /**
     * Return the caldav calendar home path
     */
    public function getCalendarHome(){
        return $this->cal->FindCalendarHome();
    }
    
    /**
     * Return all events in that calendar as an array
     */
    public function getAllEventsArray(){
        return $this->cal->GetEvents();
    }
    
    /**
     * Return an event by the UID of the event
     * 
     * @param string $uid
     * @return unknown|NULL
     */
    public function getFirstEventByUID($uid){
        $eventArray = $this->cal->GetEntryByUid($uid);
        if(isset($eventArray[0])){
            return $eventArray[0];
        }
        return null;
    }
    
    /**
     * Build the URL, makes sure always trailing slash at the end
     * 
     * @param string $event
     * @return string
     */
    private function getUrl($event = null){
        $eventExt = '';
        if(!is_null($event) && isset($event['href']) && strlen($event['href'])>0){
            $eventExt = $event['href'];
        }
        $base = rtrim($this->host.$this->calUrl, '/') . '/';
        return $base.$eventExt;
    }
    
    /**
     * Update Event by UID
     * 
     * @param unknown $uid
     * @param unknown $vCalendarObject
     * @return boolean
     */
    public function updateEventByUID($uid, $vCalendarObject){
        $event = $this->getFirstEventByUID($uid);
        if(is_null($event)){
            return false;
        }
        $this->putEvent($this->getUrl($event), $vCalendarObject);
    }
    
    /**
     * Takes a Sabre VObject\Component\VCalendar and sends it to the host
     * 
     * @param VObject\Component\VCalendar $vCalendarObject
     */
    public function createEvent($vCalendarObject){
        $this->putEvent($this->host.$this->calUrl, $vCalendarObject);
    }
    
    /**
     * Send the update request
     * 
     * @param unknown $url
     * @param VObject\Component\VCalendar $vCalendarObject
     */
    protected function putEvent($url, $vCalendarObject){
        $this->cal->DoPUTRequest($url, $vCalendarObject->serialize());
    }
    
    /**
     * Set the timezone of following event objects
     * 
     * @param unknown $timezone
     */
    public function setTimeZone($timezone){
        $this->timezone = $timezone;
    }
    
    /**
     * Create a new Sabre VObject\Component\VCalendar object
     * 
     * @param unknown $uid
     * @param unknown $summary
     * @param unknown $startTimeDate
     * @param unknown $endTimeDate
     * @return \Sabre\VObject\Component\VCalendar
     */
    public function createVCalendarEventObject($uid, $summary, $startTimeDate, $endTimeDate){
        $vcalendar = new VObject\Component\VCalendar();
        $t1 = new \DateTime($startTimeDate, new \DateTimeZone($this->timezone));
        $t1->setTimeZone(new \DateTimeZone($this->timezone));
        $t2 = new \DateTime($endTimeDate, new \DateTimeZone($this->timezone));
        $t2->setTimezone(new \DateTimeZone($this->timezone));
        $dt = $vcalendar->create('DTSTART');
        $dt->setValue($t1);
        $de = $vcalendar->create("DTEND");
        $de->setValue($t2);
        
        $vcalendar->add('VEVENT', [
                'SUMMARY' => $summary,
                'DTSTART' => $dt->getValue(),
                'DTEND' => $de->getValue(),
                'UID' => $uid,
                ]);
        return $vcalendar;
    }
    
    /**
     * Remove an event by UID, double checks if href is valid
     * 
     * @param unknown $uid
     * @return boolean
     */
    public function deleteEventByUID($uid){
        $ret = false;
        $event = $this->getFirstEventByUID($uid);
        if(isset($event['href']) && strlen($event['href'])>1){
            $url = $this->getUrl($event);
            $ret = $this->deleteEvent($url);
        }
        return $ret;
    }
    
    /**
     * Send DELETE request to host
     * 
     * @param unknown $url
     */
    protected function deleteEvent($url){
        if(isset($url) && strlen($url)>0){
            $test = ".eml";
            if(substr_compare($url, $test, strlen($url)-strlen($test), strlen($test)) === 0){
                $this->cal->DoDELETERequest($url);
            } 
        }
    }
}
