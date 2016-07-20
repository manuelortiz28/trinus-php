<?php

use backendless\Backendless;
use backendless\services\persistence\BackendlessDataQuery;
use Phalcon\DI\InjectionAwareInterface;

class TaxisManager implements InjectionAwareInterface
{
    private $fields = array("objectId", "plates", "carPhoto", "model", "latitude", "longitude");
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

    public function parseToArray($taxi) {
        return $this->_di->get("responseManager")->getAttributes($this->fields, $taxi);
    }
}
?>