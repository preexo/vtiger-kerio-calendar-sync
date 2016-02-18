<?php

require_once 'msa/caldav/caldavClient.php';
include_once 'modules/MSACustomizations/MSACustomizations.php';

class MSACustomizationsHandler extends VTEventHandler {

    /**
     * Delete event from caldav kerio server in case it was deleted in the vtiger
     * 
     * @see VTEventHandler::handleEvent()
     */
    function handleEvent($eventName, $data) {
        if($eventName == 'vtiger.entity.afterdelete') {
            $uid = MSACustomizations::getCaldavUID($data->entityId, $data->moduleName);
            $msaClient = new MSACalendarConnector("caldav-server-url", "username", "password", "calendar name", 'UTC');
            $msaClient->deleteEventByUID($uid);
        }
    }
}