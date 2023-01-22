<?php

use api\routes\Route;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once 'gestore-eventi.php';

class EventiRoutes extends Route
{
    public static function registerRoutes(App $app)
    {
        $app->get('/eventi', self::class . ':getEventi');
        $app->post('/eventiHome', self::class . ':getEventiHome');
        $app->get('/dettagliEvento', self::class . ':getEvento');
        $app->get('/partecipanti', self::class . ':getPartecipanti');
        $app->get('/profiloCreatore', self::class . ':getProfiloCreatore');
        $app->post('/prenota', self::class . ':postPrenotazione');
        $app->get('/recensioniCreatore', self::class . ':getRecensioniCreatore');
    }

    public function getEventiHome(Request $request, Response $response)
    {
        $result = false;
        $message = null;
        $dataKey = "eventiHome";
        $data = null;
        $status = null;

        $username = $_SESSION["username"] ?? false;
        $pds = json_decode($_POST['pds'], true);

        if ($username) {

            $database = new Database();
            $con = $database->getConnection();

            if ($con) {
                $gestoreEventi = new GestoreEventi();

                try {
                    $data = $gestoreEventi->getEventiHome($username, $pds);
                } catch (TypeError $e) {
                    $status = 409;
                    $message = "Il tipo di uno o più dati inviati è errato";
                    $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

                    return $myResponse;
                }

                if (is_array($data)) {
                    $result = true;
                    $status = 200;

                    if (sizeof($data) > 0){
                        $message = "eventi restituite";
                    } else {
                        $message = "nessuna evento restituito";
                    }
                } else {
                    //il messaggio non viene considerato con lo status 204
                    $status = 204;
                }
            } else {
                $message = "database non connesso";
                $status = 503;
            }
        } else {
            $message = "non autorizzato";
            $status = 401;
        }

        $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

        return $myResponse;
    }

    public function getEvento(Request $request, Response $response)
    {
        $result = false;
        $message = null;
        $dataKey = "dettagliEvento";
        $data = null;
        $status = null;

        $id = $_GET['id_evento'];
        $username = $_SESSION["username"] ?? false;

        if ($username) {

            $database = new Database();
            $con = $database->getConnection();

            if ($con) {
                $gestoreEvento = new GestoreEventi();

                try {
                    $data = $gestoreEvento->getEvento($id);
                } catch (TypeError $e) {
                    $status = 409;
                    $message = "Il tipo di uno o più dati inviati è errato";
                    $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

                    return $myResponse;
                }

                if (is_array($data)) {
                    $result = true;
                    $status = 200;

                    if (sizeof($data) > 0){
                        $message = "evento restituito";
                    } else {
                        $message = "nessuna evento trovato";
                    }
                } else {
                    //il messaggio non viene considerato con lo status 204
                    $status = 204;
                }

            } else {
                $message = "database non connesso";
                $status = 503;
            }
        } else {
            $message = "non autorizzato";
            $status = 401;
        }

        $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

        return $myResponse;
    }

    public function getPartecipanti(Request $request, Response $response)
    {
        $result = false;
        $message = null;
        $dataKey = "partecipanti";
        $data = null;
        $status = null;

        $idEvento = $_GET['id_evento'];
        $username = $_SESSION["username"] ?? false;

        if ($username) {
            $database = new Database();
            $con = $database->getConnection();

            if ($con) {
                $gestoreEventi = new GestoreEventi();

                try {
                    $data = $gestoreEventi->getPartecipanti($idEvento);
                } catch (TypeError $e) {
                    $status = 409;
                    $message = "Il tipo di uno o più dati inviati è errato";
                    $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

                    return $myResponse;
                }

                if (is_array($data)) {
                    $result = true;
                    $status = 200;

                    if (sizeof($data) > 0){
                        $message = "partecipanti restituiti";
                    } else {
                        $message = "nessuna partecipante trovato";
                    }
                } else {
                    //il messaggio non viene considerato con lo status 204
                    $status = 204;
                }
            } else {
                $message = "database non connesso";
                $status = 503;
            }
        } else {
            $message = "non autorizzato";
            $status = 401;
        }

        $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

        return $myResponse;
    }

    public function getProfiloCreatore(Request $request, Response $response)
    {
        $result = false;
        $message = null;
        $dataKey = "profiloCreatore";
        $data = null;
        $status = null;
        $username_creatore = $_GET['username'];
        $username = $_SESSION["username"] ?? false;

        if ($username) {
            $database = new Database();
            $con = $database->getConnection();

            if ($con) {
                $gestoreProfiloCreatore = new GestoreEventi();

                try {
                    $data = $gestoreProfiloCreatore->getProfiloCreatore($username_creatore);
                } catch (TypeError $e) {
                    $status = 409;
                    $message = "Il tipo di uno o più dati inviati è errato";
                    $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

                    return $myResponse;
                }

                if (is_array($data)) {
                    $result = true;
                    $status = 200;

                    if (sizeof($data) > 0){
                        $message = "profilo restituito";
                    } else {
                        $message = "nessun profilo trovato";
                    }
                } else {
                    //il messaggio non viene considerato con lo status 204
                    $status = 204;
                }
            } else {
                $message = "database non connesso";
                $status = 503;
            }

        } else {
            $message = "non autorizzato";
            $status = 403;
        }

        $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

        return $myResponse;
    }

    public function postPrenotazione(Request $request, Response $response)
    {
        $result = false;
        $message = null;
        $dataKey = "prenota evento";
        $data = null;
        $status = null;
        $username = $_SESSION["username"] ?? false;
        $requestData = $request->getParsedBody();
        $idEvento = $requestData['id_evento'];

        if ($username) {

            $database = new Database();
            $con = $database->getConnection();

            if ($con) {
                $gestoreEventi = new GestoreEventi();

                try {
                    $data = $gestoreEventi->postPrenotazione($username, $idEvento);
                } catch (TypeError $e) {
                    $status = 409;
                    $message = "Il tipo di uno o più dati inviati è errato";
                    $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

                    return $myResponse;
                }

                if ($data) {
                    $result = true;
                    $message = "Prenotazione effetuata";
                    $status = 200;
                } else {
                    //con ($status == 304) il corpo del messaggio viene ignorato
                    $message = "Prenotazione fallita";
                    $status = 304;
                }
            } else {
                $message = "database non connesso";
                $status = 503;
            }
        } else {
            $message = "non autorizzato";
            $status = 401;
        }

        $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

        return $myResponse;
    }

    public function getRecensioniCreatore(Request $request, Response $response)
    {
        $result = false;
        $message = null;
        $dataKey = "recensioni creatore";
        $data = null;
        $status = null;

        $username = $_SESSION["username"] ?? false;
        $username_creatore = $_GET['username'];

        if ($username) {

            $database = new Database();
            $con = $database->getConnection();

            if ($con) {
                $gestoreEventi = new GestoreEventi();

                try {
                    $data = $gestoreEventi->getRecensioniCreatore($username_creatore);
                } catch (TypeError $e) {
                    $status = 409;
                    $message = "Il tipo di uno o più dati inviati è errato";
                    $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

                    return $myResponse;
                }

                if (is_array($data)) {
                    $result = true;
                    $status = 200;

                    if (sizeof($data) > 0){
                        $message = "recensioni restituite";
                    } else {
                        $message = "nessuna recensione trovata";
                    }
                } else {
                    //il messaggio non viene considerato con lo status 204
                    $status = 204;
                }
            } else {
                $message = "database non connesso";
                $status = 503;
            }
        } else {
            $message = "non autorizzato";
            $status = 401;
        }

        $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

        return $myResponse;

    }

}
