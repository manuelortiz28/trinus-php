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

    public function getTaxis($name, $user)
    {
        $mealQuery = new BackendlessDataQuery();
        $mealQuery->setDepth(1);

        $condition = "user.objectId = '" . $user->getObjectId() . "'";

        if ($name) {
            $condition = $condition . " and name LIKE '" . $name . "%'";
        }

        $mealQuery->setWhereClause($condition);
        $results = Backendless::$Persistence->of('Meal')->find($mealQuery)->getAsClasses();

        $i = 0;
        $rows = [];
        foreach ($results as $pMeal) {
            $pMeal->setProperty("fileName", "/public/images/" . $pMeal->getFileName() . ".jpg");
            $mealAttrs = $this->_di->get("responseManager")->getAttributes($this->fields, $pMeal);

            $rows[$i++] = $mealAttrs;
        }

        return $rows;
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