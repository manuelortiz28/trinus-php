<?php

$app->get("/services", function () use ($app, $di) {
    $servicesManager = $di->get("servicesManager");
    $responseManager = $di->get("responseManager");

    try {
        $serviceId = $app->request->get("serviceId");

        return $responseManager->getResponse(
            $servicesManager->getService($serviceId)
        );
    } catch(YummyException $e){
        return $responseManager->getErrorResponse($e);
    } catch(Exception $e) {
        return $responseManager->getGenericErrorResponse($e);
    }
});

$app->post("/services", function () use ($app, $di) {
    $servicesManager = $di->get("servicesManager");
    $responseManager = $di->get("responseManager");

    try {
        $user = $app->request->getJsonRawBody();

        return $responseManager->getResponse(
            array('services' => $servicesManager->createService($user))
        );
    } catch(YummyException $e){
        return $responseManager->getErrorResponse($e);
    } catch(Exception $e) {
        return $responseManager->getGenericErrorResponse($e);
    }
});

$app->put("/services", function () use ($app, $di) {
    $servicesManager = $di->get("servicesManager");
    $responseManager = $di->get("responseManager");

    try {
        $service = $app->request->getJsonRawBody();

        $servicesManager->updateService($service);

        return $responseManager->getNotContentResponse();
    } catch(YummyException $e){
        return $responseManager->getErrorResponse($e);
    } catch(Exception $e) {
        return $responseManager->getGenericErrorResponse($e);
    }
});

?>