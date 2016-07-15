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

    public function createService($user) {

        //Get First available taxi
        $taxiQuery = new BackendlessDataQuery();
        $taxiQuery->setDepth(1);

        $condition = "status = 'available'";

        $taxiQuery->setWhereClause($condition);
        $results = Backendless::$Persistence->of('Taxi')->find($taxiQuery)->getAsClasses();

        if (count($results) == 0) {
            throw new YummyException("No data found", 422);
        }

        $pService = new yummy\models\TaxiService();
        $pService->setProperty("status", "assigned");
        $pService->setProperty("user", $user->userId);
        $pService->setProperty("vehicle", $results[0]);
        $pService->setProperty("driver", $results[0]->driver);

        try {
            $pService = Backendless::$Persistence->save($pService);
        } catch(Exception $e){
            throw new YummyException("Error Processing Request createService ".$e->getMessage(), 500);
        }

        $responseManager = $this->_di->get("responseManager");
        $taxisManager = $this->_di->get("taxisManager");
        $driversManager = $this->_di->get("driversManager");

        $vehicle = $taxisManager->parseToArray($results[0]);
        $driver = $driversManager->parseToArray($results[0]->driver);

        $pServiceArray = $responseManager->getAttributes($this->fields, $pService);
        $pServiceArray["vehicle"] = $vehicle;
        $pServiceArray["driver"] = $driver;

        return $pServiceArray;
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

        $vehicle = $taxisManager->parseToArray($results[0]->vehicle);
        $driver = $driversManager->parseToArray($results[0]->driver);

        $pServiceArray = $responseManager->getAttributes($this->fields, $results[0]);
        $pServiceArray["vehicle"] = $vehicle;
        $pServiceArray["driver"] = $driver;

        return $pServiceArray;
    }

    public function updateService($serviceInfo) {

        //Get Service object
        $serviceQuery = new BackendlessDataQuery();
        $serviceQuery->setDepth(1);

        $condition = "objectId = '".$serviceInfo->idService."'";

        $serviceQuery->setWhereClause($condition);
        $results = Backendless::$Persistence->of('TaxiService')->find($serviceQuery)->getAsClasses();

        if (count($results) == 0) {
            throw new YummyException("Error Processing Request createService ".$condition, 404);
        }

        //Update service status
        $service = $results[0];
        $service->status = $serviceInfo->status;

        try {
            Backendless::$Persistence->save($service);
        } catch(Exception $e){
            throw new YummyException("Error Processing Request updateService ".$e->getMessage().", ".$condition, 500);
        }

        //Update taxi coordinates
        $taxi = $results[0]->vehicle;
        $taxi->latitude = $serviceInfo->latitude;
        $taxi->longitude = $serviceInfo->longitude;

        try {
            Backendless::$Persistence->save($taxi);
        } catch(Exception $e){
            throw new YummyException("Error Processing Request updateService ".$e->getMessage().", ".$condition, 500);
        }
    }
}
?>