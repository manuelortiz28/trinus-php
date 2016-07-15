<?php

$app->get("/taxis", function () use ($app, $di) {
    $taxisManager = $di->get("taxisManager");
    $responseManager = $di->get("responseManager");

    try {
        return $responseManager->getResponse(
            array('taxis' => $taxisManager->getTaxis())
        );
    } catch(YummyException $e){
        return $responseManager->getErrorResponse($e);
    } catch(Exception $e) {
        return $responseManager->getGenericErrorResponse($e);
    }
});

$app->post("/taxis", function () use ($di, $app) {
    $taxisManager = $di->get("taxisManager");
    $responseManager = $di->get("responseManager");

    try {
        $taxi = $app->request->getJsonRawBody();//plates, carPhoto, model, latitude, longitude

        return $responseManager->getCreatedResponse($taxisManager->createTaxi($taxi));
    }catch(YummyException $e){
        return $responseManager->getErrorResponse($e);
    } catch(Exception $e) {
        return $responseManager->getGenericErrorResponse($e);
    }
});

?>