<?php

use backendless\Backendless;
use backendless\services\persistence\BackendlessDataQuery;
use Phalcon\DI\InjectionAwareInterface;

class ServicesManager implements InjectionAwareInterface
{
    private $fields = array("objectId", "status", "user", "vehicle");
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
        $pService->setProperty("status", "created");
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

        $this->sendNewServicePushNotification($pService);

        return $response;
    }

    private function sendNewServicePushNotification($service) {

        //Get First available taxi
        $taxiQuery = new BackendlessDataQuery();
        $taxiQuery->setDepth(1);

        $condition = "status = 'available'";

        $taxiQuery->setWhereClause($condition);
        $results = Backendless::$Persistence->of('Taxi')->find($taxiQuery)->getAsClasses();

        if (count($results) == 0) {
            throw new YummyException("No data found", 422);
        }

        $driver = $results[0]->driver;

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

        if ($service->status == "ACCEPTED" || $service->status == "ARRIVING"  || $service->status == "CANCELEDBYDRIVER") {
            $targetGCMToken = $service->getProperty("userGCMToken");
        } else {
            $targetGCMToken = $service->driver->getProperty("gcmToken");
        }

        $this->_di->get("pushNotificationsManager")->send($message, array($targetGCMToken));
    }

    public function getService($serviceId) {
        $serviceQuery = new BackendlessDataQuery();
        $serviceQuery->setDepth(1);

        $condition = "objectId = '".$serviceId."'";

        $serviceQuery->setWhereClause($condition);
        $results = Backendless::$Persistence->of('TaxiService')->find($serviceQuery)->getAsClasses();

        if (count($results) == 0) {
            throw new YummyException("Error Processing Request createService ".$condition, 404);
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

        //Is it assigning a driver?
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

            $this->sendChangeServiceStatusPushNotification($service);
        } else {
            $taxi = $results[0]->vehicle;
        }

        try {
            Backendless::$Persistence->save($service);
        } catch (Exception $e) {
            throw new YummyException("Error Processing Request updateService " . $e->getMessage() . ", " . $condition, 500);
        }

        //Update taxi coordinates
        if ($taxi != null) {
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