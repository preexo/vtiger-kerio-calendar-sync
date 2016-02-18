<?php
include_once 'modules/Vtiger/CRMEntity.php';

class MSACustomizations extends Vtiger_CRMEntity {

    /**
     * Invoked when special actions are performed on the module.
     * 
     * @param   String Module name
     * @param   String Event Type
     */
    function vtlib_handler ($moduleName, $eventType) {
        if ($eventType == 'module.postupdate') {
            $adb = PearDatabase::getInstance();
            $EventManager = new VTEventsManager($adb);
            // register the event in case a event entity is being deleted
            $EventManager->registerHandler("vtiger.entity.afterdelete", "modules/MSACustomizations/MSACustomizationHandler.php", "MSACustomizationsHandler", "moduleName in ['Events']");
        }
    }
    
    /**
     * Create a unique event ID
     * 
     * @param unknown $entityId
     * @param unknown $moduleString
     * @return string
     */
    public static function getCaldavUID($entityId, $moduleString){
        return "crmevent-".$entityId."-".md5($moduleString.$entityId);
    }
}