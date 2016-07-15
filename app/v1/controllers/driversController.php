<?php

$app->post("/drivers", function () use ($di, $app) {
    $driversManager = $di->get("driversManager");
    $responseManager = $di->get("responseManager");

    try {
        $driver = $app->request->getJsonRawBody();//name, photo

        return $responseManager->getCreatedResponse($driversManager->createTaxiDriver($driver));
    }catch(YummyException $e){
        return $responseManager->getErrorResponse($e);
    } catch(Exception $e) {
        return $responseManager->getGenericErrorResponse($e);
    }
});

$app->post("/drivers/location", function () use ($di, $app) {
    $driversManager = $di->get("driversManager");
    $responseManager = $di->get("responseManager");

    try {
        $driver = $app->request->getJsonRawBody();//idDriver, latitude, longitude

        $driversManager->updateDriverLocation($driver);

        return $responseManager->getNotContentResponse();
    }catch(YummyException $e){
        return $responseManager->getErrorResponse($e);
    } catch(Exception $e) {
        return $responseManager->getGenericErrorResponse($e);
    }
});
?>