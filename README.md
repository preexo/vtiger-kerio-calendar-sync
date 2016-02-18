# vtiger-kerio-calendar-sync
A little class to synchronize vtiger events towards a kerio calendar.
We have a Kerio Mailserver which comes with a caldav server. We wanted to have our vtiger events in the kerio server, so I wrote a small adapter which synchronizes the vtiger events into the kerio calendar. Create/Update/Delete. We now have all our vtiger events in our Kerio calendar plus the original Kerio calendar events from before. 

I put it all here in case someone else is interested.

It's not self explanatory, it's not plug and play and it needs to be highly adjusted and well tested before usage in production. But I put it up there, because I tried many different libraries, frameworks and whatsoever solutions and that one was the only one which worked without any problems... Have fun, let me know if anyone needs help with it.

# Dependencies

## awl
On debian install `libawl-php` to have access to these classes:
* XMLDocument
* CalendarInfo
* CalDAVClient

## Composer
* Install composer
* Install composer dependencies

## MSACustomizations 
MSACustomizations is a vtiger module, here is only the necessary part committed to the repository

## Example Usage

As a workflow function on every update of an event
```
require_once 'caldav/caldavClient.php';
require_once 'modules/MSACustomizations/MSACustomizations.php';
$entity_id = vtws_getIdComponents($entity->getId());
$entity_id = $entity_id[1];
$eventRecord = Vtiger_Record_Model::getInstanceById($entity_id, "Events");
$eventData = $eventRecord->getData();
$userModel = Vtiger_Record_Model::getInstanceById($eventData['assigned_user_id'], "Users");
$userData = $userModel->getData();
$summary = $userModel->get('user_name').": {$eventData['subject']}";
$dateTimeStart = $eventData['date_start']." ".$eventData['time_start'];
$dateTimeEnd = $eventData['due_date']." ".$eventData['time_end'];
$dtStart = new \DateTime($dateTimeStart, new \DateTimeZone($userData['time_zone']));
$dtEnd = new \DateTime($dateTimeEnd, new \DateTimeZone($userData['time_zone']));
$uid = MSACustomizations::getCaldavUID($entity_id, $eventData['record_module']);
$msaClient = new MSACalendarConnector("caldavserverhostaddress", "username", "password", "Calendar Name", 'UTC');
$vCalendarObject = $msaClient->createVCalendarEventObject($uid, $summary, $dtStart->format("Y-m-d H:i:s"), $dtEnd->format("Y-m-d H:i:s"));
$existingEvent = $msaClient->getFirstEventByUID($uid);
if(isset($existingEvent['href'])){
	$msaClient->updateEventByUID($uid, $vCalendarObject);
} else {
	$msaClient->createEvent($vCalendarObject);
}
```