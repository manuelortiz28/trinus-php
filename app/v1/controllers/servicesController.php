<?php

$app->get("/services", function () use ($app, $di) {
    $acceptHeader = $app->request->getHeader('Accept');
    $servicesManager = $di->get("servicesManager");
    $responseManager = $di->get("responseManager");

    try {
        if (strpos($acceptHeader, "text/html") !== false) {
            echo $app['views'] ->render('services/list');
        } else {

            if ($app->request->get("serviceId") != null) {
                $serviceId = $app->request->get("serviceId");

                return $responseManager->getResponse(
                    $servicesManager->getServiceById($serviceId)
                );
            } else {
                return $responseManager->getResponse(
                    $servicesManager->getServices()
                );
            }
        }
    } catch (YummyException $e) {
        return $responseManager->getErrorResponse($e);
    } catch (Exception $e) {
        return $responseManager->getGenericErrorResponse($e);
    }
});

$app->get("/services/location", function () use ($app, $di) {
    $servicesManager = $di->get("servicesManager");
    $responseManager = $di->get("responseManager");

    $idService = $app->request->get("idService");

    try {
        return $responseManager->getResponse($servicesManager->getTaxiServiceLocation($idService));
    } catch(YummyException $e){
        return $responseManager->getErrorResponse($e);
    } catch(Exception $e) {
        return $responseManager->getGenericErrorResponse($e);
    }
});

$app->post("/services", function () use ($di, $app) {
    $servicesManager = $di->get("servicesManager");
    $responseManager = $di->get("responseManager");

    try {
        $userData = $app->request->getJsonRawBody();

        return $responseManager->getResponse($servicesManager->createService($userData));
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