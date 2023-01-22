<?php

use api\routes\Route;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once 'gestore-prenotazioni.php';

class PrenotazioniRoutes extends Route
{
    public static function registerRoutes(App $app)
    {
        $app->get('/prenotazioni', self::class . ':getPrenotazioni');
        $app->get('/dettagli_ripetizione', self::class . ':getDettagliRipetizione');
        $app->get('/dettagli_gruppo', self::class . ':getDettagliGruppo');
        $app->delete('/annulla_prenotazione', self::class . ':annullaPrenotazione');
    }

    /**
     * @author Marco Brunetti
     */
    public function getPrenotazioni(Request $request, Response $response)
    {
        $result = false;
        $message = null;
        $dataKey = "prenotazioni";
        $data = null;
        $status = null;
        $username = $_SESSION["username"] ?? false;

        if ($username){

            $database = new Database();
            $con = $database->getConnection();

            if ($con) {
                
                $gestorePrenotazioni = new GestorePrenotazioni();

                try {
                    $data = $gestorePrenotazioni->getPrenotazioni($username);
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
                        $message = "prenotazioni restituite";
                    } else {
                        $message = "nessuna prenotazione trovata";
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

    /**
     * @author Marco Brunetti
     */
    public function getDettagliRipetizione(Request $request, Response $response)
    {
        $result = false;
        $message = null;
        $dataKey = "dettagli_ripetizione";
        $data = null;
        $status = null;

        $database = new Database();
        $con = $database->getConnection();
        $idEvento = $_GET['id_evento'];

        if ($con){

            $gestorePrenotazioni = new GestorePrenotazioni();

            try {
                $data = $gestorePrenotazioni->getDettagliRipetizione($idEvento);
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
                    $message = "dettagli ripetizione restituiti";
                } else {
                    $message = "ripetizione inesistente";
                }
            } else {
                //il messaggio non viene considerato con lo status 204
                $status = 204;
            }
        }else {
            $message = "database non connesso";
            $status = 503;
        }

        $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

        return $myResponse;
    }

    /**
     * @author Marco Brunetti
     */
    public function getDettagliGruppo(Request $request, Response $response)
    {
        $result = false;
        $message = null;
        $dataKey = "dettagli_gruppo";
        $data = null;
        $status = null;

        $database = new Database();
        $con = $database->getConnection();

        $idEvento = $_GET['id_evento'];

        if ($con){

            $gestorePrenotazioni = new GestorePrenotazioni();
            
            try {
                $data = $gestorePrenotazioni->getDettagliGruppo($idEvento);
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
                    $message = "dettagli evento restituiti";
                } else {
                    $message = "nessun evento trovato";
                }
            } else {
                //il messaggio non viene considerato con lo status 204
                $status = 204;
            }
        }else {
            $message = "database non connesso";
            $status = 503;
        }

        $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

        return $myResponse;
    }

    /**
     * @author Marco Brunetti
     */
    public function annullaPrenotazione(Request $request, Response $response)
    {
        $result = false;
        $message = null;
        $dataKey = "annulla_prenotazione";
        $data = null;
        $status = null;

        $idEvento = $_GET['id_evento'];
        $username = $_SESSION["username"] ?? false;

        if ($username){

            $database = new Database();
            $con = $database->getConnection();

            if ($con) {
                
                $gestorePrenotazioni = new GestorePrenotazioni();

                try {
                    $data = $gestorePrenotazioni->annullaPrenotazione($idEvento, $username);
                } catch (TypeError $e) {
                    $status = 409;
                    $message = "Il tipo di uno o più dati inviati è errato";
                    $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);
    
                    return $myResponse;
                }

                if ($data) {
                    $result = true;
                    $message = "prenotazione cancellata";
                    $status = 200;
                } else {
                    $message = "impossibile cancellare la prenotazione o prenotazione inesistente";
                    $status = 403;
                }
            } else {
                $message = "database non connesso";
                $status = 503;
            }
        }else {
            $message = "non autorizzato";
            $status = 401;
        }

        $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

        return $myResponse;
    }

}