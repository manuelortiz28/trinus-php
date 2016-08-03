<?php
use Phalcon\Http\Response;

$app->get("/taxis", function () use ($app, $di) {
    $taxisManager = $di->get("taxisManager");
    $responseManager = $di->get("responseManager");
    $acceptHeader = $app->request->getHeader('Accept');

    try {
        if (strpos($acceptHeader, "text/html") !== false) {
            echo $app['views'] ->render('taxis/list');
        } else {
            return $responseManager->getResponse($taxisManager->getTaxis());
        }
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

$app->put("/taxis", function () use ($app, $di) {
    $taxisManager = $di->get("taxisManager");
    $responseManager = $di->get("responseManager");

    try {
        $taxi = $app->request->getJsonRawBody();

        $taxisManager->updateTaxi($taxi);

        return $responseManager->getNotContentResponse();
    } catch(YummyException $e){
        return $responseManager->getErrorResponse($e);
    } catch(Exception $e) {
        return $responseManager->getGenericErrorResponse($e);
    }
});

$app->get("/map", function () use ($di, $app) {
    echo $app['views'] ->render('taxis/map');
});

?>