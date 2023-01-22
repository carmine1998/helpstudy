<?php

use api\routes\Route;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once 'gestore-profili.php';

class ProfiliRoutes extends Route
{
    public static function registerRoutes(App $app)
    {
        $app->get('/profilo', self::class . ':getProfilo');
        $app->post('/login', self::class . ':login');
        $app->get('/logout', self::class . ':logout');
        $app->put('/modificaProfilo', self::class . ':modificaProfilo');
        $app->post('/sceltaCarriera', self::class . ':sceltaCarriera');
        $app->get('/recensioni', self::class . ':getRecensioni');
        $app->post('/nuova_recensione', self::class . ':nuovaRecensione');
        $app->get('/storico_eventi', self::class . ':getStoricoEventi');
        $app->get('/dettagli_nuova_recensione', self::class . ':getDettagliNuovaRecensione');
    }

    public function getProfilo(Request $request, Response $response)
    {
        $result = false;
        $message = null;
        $dataKey = "profilo";
        $data = null;
        $status = null;

        $database = new Database();
        $con = $database->getConnection();

        if ($con) {

            $username = $_SESSION["username"];

            if ($username) {

                $gestoreProfili = new GestoreProfili();
                
                try {
                    $data = $gestoreProfili->getProfilo($username);
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
                        $message = "nessun profilo restituito";
                    }
                } else {
                    //il messaggio non viene considerato con lo status 204
                    $status = 204;
                }
            } else {
                $message = "non autorizzato";
                $status = 401;
            }

        } else {
            $message = "database non connesso";
            $status = 503;
        }

        $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

        return $myResponse;
    }

    public function login(Request $request, Response $response)
    {
        $result = false;
        $message = null;
        $dataKey = "login";
        $data = null;
        $status = null;

        $requestData = $request->getParsedBody();
        $username = $requestData['username'];
        $password = $requestData['password'];

        if ($username || $password) {
            if ($username) {
                if ($password) {

                    $database = new Database();
                    $con = $database->getConnection();

                    if ($con) {
                        $gestoreProfili = new GestoreProfili();
                        
                        try {
                            $data = $gestoreProfili->loginEsse3($username, $password);
                        } catch (TypeError $e) {
                            $status = 409;
                            $message = "Il tipo di uno o più dati inviati è errato";
                            $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);
        
                            return $myResponse;
                        }

                        if ($data) {

                            session_unset();
                            session_destroy();
                            session_start();

                            $_SESSION["username"] = $username;
                            $_SESSION["mat_id"] = $data['mat_id'];
                            $_SESSION["id_session"] = session_id();
                            $_SESSION["primologin"] = $data['nuovo_utente'];

                            $data['id_session'] = session_id();

                            $result = true;
                            $message = "autenticazione riuscita";
                            $status = 200;
                        } else {
                            $message = "autenticazione fallita";
                            $status = 401;
                        }

                    } else {
                        $message = "database non connesso";
                        $status = 503;
                    }
                } else {
                    $message = "password non inserita";
                    $status = 403;
                }
            } else {
                $message = "username non inserita";
                $status = 403;
            }
        } else {
            $message = "username e password non inseriti";
            $status = 403;
        }

        $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

        return $myResponse;
    }

    public function logout(Request $request, Response $response)
    {
        $result = false;
        $message = null;
        $dataKey = "logout";
        $data = null;
        $status = null;

        session_unset();
        session_destroy();

        if (!$_SESSION) {
            $result = true;
            $message = "Logout";
            $status = 200;
        } else {
            $message = "Impossibile fare il logout";
            $status = 401;
        }

        $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

        return $myResponse;
    }

    /**
     * @author Francesca Zero
     */
    public function modificaProfilo(Request $request, Response $response)
    {

        $result = false;
        $message = null;
        $dataKey = "modificaProfilo";
        $data = null;
        $status = null;

        $requestData = $request->getParsedBody();
        $descrizione = $requestData['descrizione'] ?? null;
        $foto = $requestData['foto'];

        $database = new Database();
        $con = $database->getConnection();

        if ($con) {

            $username = $_SESSION["username"] ?? false;

            if ($username) {
                $gestoreProfili = new GestoreProfili();
                
                try {
                    $data = $gestoreProfili->modificaProfilo($foto, $descrizione, $username);
                } catch (TypeError $e) {
                    $status = 409;
                    $message = "Il tipo di uno o più dati inviati è errato";
                    $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

                    return $myResponse;
                }

                if ($data) {
                    $result = true;
                    $message = "Modifica eseguita correttamente";
                    $status = 200;
                } else {
                    $message = "Siamo spiacenti si è verificato un errore";
                    $status = 403;
                }

            } else {
                $message = "Non autorizzato";
                $status = 401;
            }
        } else {
            $message = "database non connesso";
            $status = 503;
        }

        $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

        return $myResponse;
    }

    /**
     * @author Milena Maisto
     */
    public function sceltaCarriera(Request $request, Response $response)
    {
        $result = false;
        $message = null;
        $dataKey = "sceltaCarriera";
        $data = null;
        $status = null;

        $requestData = $request->getParsedBody();
        $esame = $requestData['esame'];
        $azione = $requestData['azione'];
        $azione = filter_var($azione, FILTER_VALIDATE_BOOLEAN);

        $username = $_SESSION["username"] ?? false;

        if ($username) {

            $database = new Database();
            $con = $database->getConnection();

            if ($con) {

                $gestoreProfili = new GestoreProfili();

                try {
                    $data = $gestoreProfili->sceltaCarriera($esame, $azione, $username);
                } catch (TypeError $e) {
                    $status = 409;
                    $message = "Il tipo di uno o più dati inviati è errato";
                    $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

                    return $myResponse;
                }

                if ($data) {
                    $result = true;
                    $status = 200;
                    if ($azione === true ) {
                        $message = "Esame scelto inserito";
                    } else {
                        $message = "Esame scelto rimosso";
                    }
                } else {
                    $status = 403;
                    if ($azione === true) {
                        $message = "Impossibile inserire esame";
                    } else {
                        $message = "Impossibile rimuovere esame";
                    }
                }
            } else {
                $message = "Database non connesso";
                $status = 503;
            }
        } else {
            $message = "Non autorizzato";
            $status = 401;
        }

        $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

        return $myResponse;
    }

    /**
     * @author Eros Di Meo
     */
    public function getRecensioni(Request $request, Response $response)
    {

        $result = false;
        $message = null;
        $dataKey = "recensioni";
        $data = null;
        $status = null;

        $username = $_SESSION["username"] ?? false;

        if ($username) {

            $database = new Database();
            $con = $database->getConnection();

            if ($con) {

                $gestoreProfili = new GestoreProfili();

                try {
                    $data = $gestoreProfili->getRecensioni($username);
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
                $message = "Database non connesso";
                $status = 503;
            }

        } else {
            $message = "Non Autorizzato";
            $status = 401;
        }

        $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

        return $myResponse;
    }

    /**
     * @authors Federica Ciccaglione, Marco Brunetti, Maurizio Albani
     */
    public function getStoricoEventi(Request $request, Response $response)
    {
        $result = false;
        $message = null;
        $dataKey = "storico_eventi";
        $data = null;
        $status = null;

        $username = $_SESSION["username"] ?? false;

        if ($username) {

            $database = new Database();
            $con = $database->getConnection();

            if ($con) {

                $gestoreProfili = new GestoreProfili();
                
                try {
                    $data = $gestoreProfili->getStoricoEventi($username);
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
                        $message = "eventi restituiti";
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

        $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

        return $myResponse;
    }

    /**
     * @author Marco Brunetti
     */
    public function nuovaRecensione(Request $request, Response $response)
    {
        $result = false;
        $message = null;
        $dataKey = "nuova_recensione";
        $data = null;
        $status = null;

        $requestData = $request->getParsedBody();
        $id_evento = $requestData['id_evento'];
        $feedback = $requestData['feedback'];
        $valutazione = $requestData['valutazione'];
        $username = $_SESSION["username"] ?? false;

        if ($username) {

            $database = new Database();
            $con = $database->getConnection();

            if ($con) {

                $gestoreProfili = new GestoreProfili();
                
                try {
                    $data = $gestoreProfili->nuovaRecensione($id_evento, $feedback, $username, $valutazione);
                } catch (TypeError $e) {
                    $status = 409;
                    $message = "Il tipo di uno o più dati inviati è errato";
                    $myResponse = self::getResponse($response, $status, $result, $message, $dataKey, $data);

                    return $myResponse;
                }


                if ($data) {
                    $result = true;
                    $message = "recensione aggiunta";
                    $status = 200;
                } else {
                    $message = "recensione non aggiunta";
                    $status = 403;
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

    public function getDettagliNuovaRecensione(Request $request, Response $response)
    {
        $result = false;
        $message = null;
        $dataKey = "dettagli-nuova-recensione";
        $data = null;
        $status = null;

        $id = $_GET['id_evento'];
        $username = $_SESSION["username"] ?? false;

        if ($username) {

            $database = new Database();
            $con = $database->getConnection();

            if ($con) {

                $gestoreProfili = new GestoreProfili();
                
                try {
                    $data = $gestoreProfili->getDettagliNuovaRecensione($id);
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
                        $message = "dettagli restituiti";
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
