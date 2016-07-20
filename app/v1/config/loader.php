<?php
use backendless\BackendlessAutoloader;
use backendless\Backendless;

//Mapping backendless
BackendlessAutoloader::addNamespace( 'yummy', __DIR__. DIRECTORY_SEPARATOR . "..");
Backendless::mapTableToClass( "Users", "backendless\model\BackendlessUser" );
Backendless::mapTableToClass( "YummySession", "yummy\models\YummySession" );
Backendless::mapTableToClass( "Taxi", "yummy\models\Taxi" );
Backendless::mapTableToClass( "Driver", "yummy\models\Driver" );
Backendless::mapTableToClass( "TaxiService", "yummy\models\TaxiService" );

//Loading other classes
require $folderApp."/models/ErrorItem.php";

require $folderApp."/models/managers/responseManager.php";
require $folderApp."/models/managers/authenticationManager.php";
require $folderApp."/models/managers/taxisManager.php";
require $folderApp."/models/managers/driversManager.php";
require $folderApp."/models/managers/servicesManager.php";
require $folderApp."/models/managers/pushNotificationsManager.php";

require $folderApp."/models/exceptions/yummyException.php";

$di->setShared("mealsManager", 'MealsManager');
$di->setShared("taxisManager", 'TaxisManager');
$di->setShared("driversManager", 'DriversManager');
$di->setShared("servicesManager", 'ServicesManager');
$di->setShared("pushNotificationsManager", 'PushNotificationsManager');
$di->setShared("responseManager", 'ResponseManager');

// Define the routes here
require $folderApp."/controllers/appController.php";

require $folderApp."/controllers/authenticationController.php";

require $folderApp."/controllers/taxisController.php";
require $folderApp."/controllers/driversController.php";
require $folderApp."/controllers/servicesController.php";

/*
$loader = new \Phalcon\Loader();

// We're a registering a set of directories taken from the configuration file
$loader->registerDirs(
    array(
        "../models/managers",
        "../models/exceptions"
    )
)->register();
*/

?>
