<?php

use api\routes\Route;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once 'gestore-eventi-personali.php';

class EventiPersonaliRoutes extends Route
{
    public static function registerRoutes(App $app)
    {
        $app->post('/creaEvento', self::class . ':postCreaEvento');
        $app->get('/eventiPersonali', self::class . ':getEventiPersonali');
        $app->put('/modificaEvento', self::class . ':putModificaEvento');
        $app->delete('/eliminaEvento', self::class . ':deleteEvento');
        $app->get('/dettagliEventoPersonale', self::class . ':getDettagliEventoPersonale');
    }

    public function postCreaEvento(Request $request, Response $response)
    {
        $result = false;
        $message = null;
        $dataKey = "crea evento";
        $dati = null;
        $status = null;
        $requestData = $request->getParsedBody();
        $tipo = $requestData['tipo'];
        $titolo = $requestData['titolo'];
        $data = $requestData['data'];
        $luogo = $requestData['luogo'];
        $materia = $requestData['materia'];
        $richiesta = $requestData['richiesta'];
        $partecipanti = $requestData['partecipanti'];
        $idEsame = $requestData['id_esame'];
        $username = $_SESSION["username"] ?? false;

        if ($username) {

            $database = new Database();
            $con = $database->getConnection();

            if ($con) {

                $gestoreEventiPersonali = new GestoreEventiPersonali();
                    
                try {
                    $dati = $gestoreEventiPersonali->postCreaEvento($tipo, $username, $titolo, $data, $luogo, $materia, $richiesta, $partecipanti, $idEsame);
                } catch (TypeError $e) {
                    $status = 409;
                    $message = "Il tipo di uno o più dati inviati è errato";
                    $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

                    return $myResponse;
                }

                if ($dati) {
                    $result = true;
                    $message = "Evento creato";
                    $status = 200;

                } else {
                    $message = "creazione evento fallita";
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

        $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $dati);

        return $myResponse;
    }

    public function getEventiPersonali(Request $request, Response $response)
    {
        $result = false;
        $message = null;
        $dataKey = "eventi personali";
        $data = null;
        $status = null;
        $username = $_SESSION["username"] ?? false;

        if ($username) {

            $database = new Database();
            $con = $database->getConnection();

            if ($con) {
                $gestoreEventiPersonali = new GestoreEventiPersonali();

                try {
                    $data = $gestoreEventiPersonali->getEventiPersonali($username);
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
                        $message = "eventi personali restituiti";
                    } else {
                        $message = "nessun evento trovato";
                    }
                } else {
                    //il messaggio non viene considerato con lo status 204
                    $status = 204;
                }

                if ($data !== false) {
                    $result = true;
                    $message = "eventi esistenti";
                    $status = 200;

                } else {
                    $message = "eventi non esistenti";
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

    public function putModificaEvento(Request $request, Response $response)
    {
        $result = false;
        $message = null;
        $dataKey = "modifica evento";
        $dati = null;
        $status = null;
        $username = $_SESSION["username"] ?? false;

        $requestData = $request->getParsedBody();
        $titolo = $requestData['titolo'];
        $data = $requestData['data'];
        $luogo = $requestData['luogo'];
        $richiesta = $requestData['descrizione'];
        $idEvento = $requestData['idEvento'];

        if ($username) {

            $database = new Database();
            $con = $database->getConnection();

            if ($con) {

                $gestoreEventiPersonali = new GestoreEventiPersonali();

                try {
                    $dati = $gestoreEventiPersonali->putModificaEvento($titolo, $data, $luogo, $richiesta, $idEvento);
                } catch (TypeError $e) {
                    $status = 409;
                    $message = "Il tipo di uno o più dati inviati è errato";
                    $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

                    return $myResponse;
                }

                if ($dati) {
                    $result = true;
                    $message = "Modifica effettuata";
                    $status = 200;
                } else {
                    $message = "Modifica fallita";
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

        $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $dati);

        return $myResponse;
    }

    public function deleteEvento(Request $request, Response $response)
    {
        $result = false;
        $message = null;
        $dataKey = "Elimina evento";
        $dati = null;
        $status = null;
        $idEvento = $_GET['id_evento'];
        $username = $_SESSION["username"] ?? false;

        if ($username) {

            $database = new Database();
            $con = $database->getConnection();

            if ($con) {
                $gestoreEventiPersonali = new GestoreEventiPersonali();

                try {
                    $dati = $gestoreEventiPersonali->deleteEvento($idEvento, $username);
                } catch (TypeError $e) {
                    $status = 409;
                    $message = "Il tipo di uno o più dati inviati è errato";
                    $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);
    
                    return $myResponse;
                }

                if ($dati) {
                    $result = true;
                    $message = "Evento Eliminato";
                    $status = 200;
                } else {
                    //il messaggio non viene considerato con lo status 304
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

        $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $dati);

        return $myResponse;
    }

    public function getDettagliEventoPersonale(Request $request, Response $response)
    {
        $result = false;
        $message = null;
        $dataKey = "dettagli evento personale";
        $dati = null;
        $status = null;
        $idEvento = $_GET['id_evento'];
        $username = $_SESSION["username"] ?? false;

        if ($username) {
            $requestData = $request->getParsedBody();

            $database = new Database();
            $con = $database->getConnection();

            if ($con) {
                $gestoreEventoPersonali = new GestoreEventiPersonali();

                try {
                    $dati = $gestoreEventoPersonali->getDettagliEventoPersonale($idEvento);
                } catch (TypeError $e) {
                    $status = 409;
                    $message = "Il tipo di uno o più dati inviati è errato";
                    $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);
    
                    return $myResponse;
                }

                if (is_array($dati)) {
                    $result = true;
                    $status = 200;

                    if (sizeof($dati) > 0){
                        $message = "dettagli evento restituiti";
                    } else {
                        $message = "nessun evento trovato";
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

        $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $dati);

        return $myResponse;
    }
}
