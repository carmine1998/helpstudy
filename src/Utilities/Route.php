<?php

namespace api\routes;

use Slim\App;
use Slim\Http\Response;

abstract class Route
{

    public static abstract function registerRoutes(App $app);

    public function getResponse($response, $status, $result, $message, $dataKey, $data)
    {
        $jsonData = array(
            'result' => $result,
            'message' => $message,
            $dataKey => $data
        );

        $response = $response->withJson($jsonData);
        $response = $response->withStatus($status);

        return $response;
    }

}