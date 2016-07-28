<?php

use backendless\Backendless;
use backendless\services\persistence\BackendlessDataQuery;
use Phalcon\DI\InjectionAwareInterface;

class DriversManager implements InjectionAwareInterface
{
    private $fields = array("objectId", "name", "driverPhoto");
    protected $_di;

    public function setDI(Phalcon\DiInterface $dependencyInjector)
    {
        $this->_di = $dependencyInjector;
    }

    public function getDI()
    {
        return $this->_di;
    }

    public function createTaxiDriver($driver) {
        $pDriver = new yummy\models\Driver();
        $pDriver->setProperty("name", $driver->name);
        $pDriver->setProperty("driverPhoto", $driver->driverPhoto);

        try {
            $pDriver = Backendless::$Persistence->save($pDriver);
        } catch(Exception $e){
            throw new YummyException("Error Processing Request createTaxiDriver ".$e->getMessage(), 500);
        }

        return $this->_di->get("responseManager")->getAttributes($this->fields, $pDriver);
    }

    public function updateDriverLocation($driverInfo) {
        //Get Taxi with driver related
        $taxiQuery = new BackendlessDataQuery();
        $taxiQuery->setDepth(1);

        $condition = "driver.objectId = '".$driverInfo->idDriver."'";

        $taxiQuery->setWhereClause($condition);
        $results = Backendless::$Persistence->of('Taxi')->find($taxiQuery)->getAsClasses();

        if (count($results) == 0) {
            throw new YummyException("No data found".", ".$condition, 422);
        }

        $taxi = $results[0];
        $taxi->latitude = $driverInfo->latitude;
        $taxi->longitude = $driverInfo->longitude;
        $taxi->driver->gcmToken = $driverInfo->tokenGCM;

        error_log("updateDriverLocation(".$driverInfo->latitude.",".$driverInfo->longitude);

        try {
            Backendless::$Persistence->save($taxi);
        } catch(Exception $e){
            throw new YummyException("Error Processing Request updateDriverLocation ".$e->getMessage(), 500);
        }
    }

    public function parseToArray($driver) {
        return $this->_di->get("responseManager")->getAttributes($this->fields, $driver);
    }
}
?>