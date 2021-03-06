<?php

use backendless\Backendless;
use backendless\services\persistence\BackendlessDataQuery;
use Phalcon\DI\InjectionAwareInterface;

class ServicesManager implements InjectionAwareInterface
{
    private $fields = array("objectId", "status", "user", "vehicle", "targetAddress");
    protected $_di;

    public function setDI(Phalcon\DiInterface $dependencyInjector)
    {
        $this->_di = $dependencyInjector;
    }

    public function getDI()
    {
        return $this->_di;
    }

    public function getTaxiServiceLocation($idService)
    {
        $serviceQuery = new BackendlessDataQuery();
        $serviceQuery->setDepth(1);

        $condition = "objectId = '" . $idService . "'";

        $serviceQuery->setWhereClause($condition);
        $results = Backendless::$Persistence->of('TaxiService')->find($serviceQuery)->getAsClasses();

        if (count($results) == 0) {
            throw new YummyException("Service Not Found", 404);
        }

        error_log("getTaxiServiceLocation(".$results[0]->vehicle->getProperty("latitude").",".$results[0]->vehicle->getProperty("longitude"));

        return $this->_di->get("responseManager")->getAttributes(
            array("latitude", "longitude"),
            $results[0]->vehicle);
    }

    public function createService($userData) {

        //Creates service record
        $pService = new yummy\models\TaxiService();
        $pService->setProperty("status", "CREATED");
        $pService->setProperty("user", $userData->userId);
        $pService->setProperty("startLatitude", $userData->latitude);
        $pService->setProperty("startLongitude", $userData->longitude);
        $pService->setProperty("targetAddress", $userData->address);
        $pService->setProperty("userGCMToken", $userData->userGCMToken);

        try {
            $pService = Backendless::$Persistence->save($pService);
        } catch(Exception $e){
            throw new YummyException("Error Processing Request createService ".$e->getMessage(), 500);
        }

        $response =  $this->_di->get("responseManager")->getAttributes(
                                                            array("objectId", "status", "user"),
                                                            $pService);

        $taxi = $this->getAvailableTaxi($pService->objectId);
        $response["taxi"] = $taxi->objectId;//TODO Remove this field, only for debugging purposes

        $this->sendNewServicePushNotification($pService, $taxi->driver);

        return $response;
    }

    private function getAvailableTaxi($idService) {
        //Use $idService in order to exclude taxis that reject to take this service

        //Get First available taxi
        $taxiQuery = new BackendlessDataQuery();
        $taxiQuery->setDepth(1);

        $condition = "status = 'ACTIVE' and driver IS NOT NULL";

        $taxiQuery->setWhereClause($condition);
        $results = Backendless::$Persistence->of('Taxi')->find($taxiQuery)->getAsClasses();

        if (count($results) == 0) {
            throw new YummyException("No data found", 422);
        }

        return $results[rand(1, count($results)) - 1];
    }

    private function sendNewServicePushNotification($service, $driver) {
        $body = [];
        $body["idService"] = $service->objectId;
        $body["destinationAddress"] = $service->targetAddress;
        $body["userLatitude"] = $service->startLatitude;
        $body["userLongitude"] = $service->startLongitude;

        //Creates info object
        $message = [];
        $message["type"] = "NEW_SERVICE";
        $message["message"] = json_encode($body);

        $this->_di->get("pushNotificationsManager")->send($message, array($driver->getProperty("gcmToken")));
    }

    private function sendChangeServiceStatusPushNotification($service) {

        //Creates info object
        $body = [];
        $body["idService"] = $service->objectId;
        $body["status"] = $service->status;
        $body["vehicle"] = $service->vehicle;
        $body["driver"] = $service->driver;

        //Creates info object
        $message = [];
        $message["type"] = "SERVICE_CHANGE_STATUS";
        $message["message"] = json_encode($body);

        if ($service->status == "ACCEPTED" || $service->status == "ARRIVING"  || $service->status == "CANCELLED_BY_DRIVER") {
            $targetGCMToken = $service->getProperty("userGCMToken");
        } else {
            $targetGCMToken = $service->driver->getProperty("gcmToken");
        }

        $this->_di->get("pushNotificationsManager")->send($message, array($targetGCMToken));
    }

    public function getServices() {
        $results = Backendless::$Persistence->of('TaxiService')->find()->getAsClasses();

        $i=0;
        $rows=[];
        foreach($results as $pService) {
            $serviceAttrs = $this->_di->get("responseManager")->getAttributes($this->fields, $pService);

            $rows[$i++] = $serviceAttrs;
        }

        return $rows;
    }

    public function getServiceById($serviceId) {
        $serviceQuery = new BackendlessDataQuery();
        $serviceQuery->setDepth(1);

        $condition = "objectId = '".$serviceId."'";

        $serviceQuery->setWhereClause($condition);
        $results = Backendless::$Persistence->of('TaxiService')->find($serviceQuery)->getAsClasses();

        if (count($results) == 0) {
            throw new YummyException("Error Processing Request getServiceById where ".$condition, 404);
        }

        $responseManager = $this->_di->get("responseManager");
        $taxisManager = $this->_di->get("taxisManager");
        $driversManager = $this->_di->get("driversManager");

        $service = $results[0];

        $vehicle = null;
        if (isset($service->vehicle)) {
            $vehicle = $taxisManager->parseToArray($service->vehicle);
        }

        $driver = null;
        if (isset($service->driver)) {
            $driver = $driversManager->parseToArray($service->driver);
        }

        $pServiceArray = $responseManager->getAttributes($this->fields, $service);

        $pServiceArray["vehicle"] = $vehicle;
        $pServiceArray["driver"] = $driver;

        return $pServiceArray;
    }

    public function updateService($serviceInfo)
    {
        //Get Service object
        $serviceQuery = new BackendlessDataQuery();
        $serviceQuery->setDepth(1);

        $condition = "objectId = '" . $serviceInfo->idService . "'";

        $serviceQuery->setWhereClause($condition);
        $results = Backendless::$Persistence->of('TaxiService')->find($serviceQuery)->getAsClasses();

        if (count($results) == 0) {
            throw new YummyException("Error Processing Request createService " . $condition, 404);
        }

        $service = $results[0];
        //Update service status
        $service->status = $serviceInfo->status;

        if (isset($serviceInfo->idDriver)) {
            //Get taxi
            $taxiQuery = new BackendlessDataQuery();
            $taxiQuery->setDepth(1);

            $condition = "driver.objectId = '" . $serviceInfo->idDriver . "'";

            $taxiQuery->setWhereClause($condition);
            $taxiResults = Backendless::$Persistence->of('Taxi')->find($taxiQuery)->getAsClasses();

            if (count($taxiResults) == 0) {
                throw new YummyException("Error Processing Request createService " . $condition, 404);
            }

            $taxi = $taxiResults[0];
            $service->vehicle = $taxi;
            $service->driver = $taxi->driver;
        } else {
            $taxi = $results[0]->vehicle;
        }

        $this->sendChangeServiceStatusPushNotification($service);

        try {
            Backendless::$Persistence->save($service);
        } catch (Exception $e) {
            throw new YummyException("Error Processing Request updateService " . $e->getMessage() . ", " . $condition, 500);
        }

        //Update taxi coordinates
        if ($taxi != null) {
            error_log("updateService(".$serviceInfo->latitude.",".$serviceInfo->longitude);
            $taxi->latitude = $serviceInfo->latitude;
            $taxi->longitude = $serviceInfo->longitude;
        }

        try {
            Backendless::$Persistence->save($taxi);
        } catch(Exception $e){
            throw new YummyException("Error Processing Request updateService ".$e->getMessage().", ".$condition, 500);
        }
    }
}
?>