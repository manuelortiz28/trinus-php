<?php

use backendless\Backendless;
use backendless\services\persistence\BackendlessDataQuery;
use Phalcon\DI\InjectionAwareInterface;

class TaxisManager implements InjectionAwareInterface
{
    private $fields = array("objectId", "driver", "plates", "carPhoto", "model", "latitude", "longitude", "status");
    protected $_di;

    public function setDI(Phalcon\DiInterface $dependencyInjector)
    {
        $this->_di = $dependencyInjector;
    }

    public function getDI()
    {
        return $this->_di;
    }

    public function createTaxi($taxi) {
        $pTaxi = new yummy\models\Taxi();
        $pTaxi->setProperty("plates",$taxi->plates);
        $pTaxi->setProperty("carPhoto", $taxi->carPhoto);
        $pTaxi->setProperty("model", $taxi->model);
        $pTaxi->setProperty("latitude", $taxi->latitude);
        $pTaxi->setProperty("longitude", $taxi->longitude);

        try {
            $pTaxi = Backendless::$Persistence->save($pTaxi);
        } catch(Exception $e){
            throw new YummyException("Error Processing Request createTaxi ".$e->getMessage(), 500);
        }

        return $this->_di->get("responseManager")->getAttributes($this->fields, $pTaxi);
    }

    public function getTaxis()
    {
        $results = Backendless::$Persistence->of('Taxi')->find()->getAsClasses();

        $driversManager = $this->_di->get("driversManager");

        $i=0;
        $rows=[];
        foreach($results as $pTaxi) {
            $taxiAttrs = $this->_di->get("responseManager")->getAttributes($this->fields, $pTaxi);

            if (isset($pTaxi->driver)) {
                $taxiAttrs["driver"] = $driversManager->parseToArray($pTaxi->driver);
            }
            $rows[$i++] = $taxiAttrs;
        }

        return $rows;
    }

    public function updateTaxi($pTaxi) {
        //Get Service object
        $taxiQuery = new BackendlessDataQuery();
        $taxiQuery->setDepth(1);

        $condition = "objectId = '" . $pTaxi->idTaxi . "'";

        $taxiQuery->setWhereClause($condition);
        $results = Backendless::$Persistence->of('Taxi')->find($taxiQuery)->getAsClasses();

        if (count($results) == 0) {
            throw new YummyException("Error Processing updateTaxi() " . $condition, 404);
        }

        $taxi = $results[0];
        //Update service status
        $taxi->status = $pTaxi->status;

        try {
            Backendless::$Persistence->save($taxi);
        } catch (Exception $e) {
            throw new YummyException("Error Processing updateTaxi() " . $e->getMessage() . ", " . $condition, 500);
        }
    }

    public function parseToArray($taxi) {
        return $this->_di->get("responseManager")->getAttributes($this->fields, $taxi);
    }
}
?>