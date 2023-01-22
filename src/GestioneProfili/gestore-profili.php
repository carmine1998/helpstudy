<?php

require_once 'abstract-gestore-profili.php';

class GestoreProfili implements AbstractGestoreProfili
{

    /**
     * Dato un username, restituisce le informazioni del profilo se l'utente esiste altrimenti restituisce un array vuoto;
     * restituisce false se la query non viene eseguita
     * @author Angela Venditti, Daniela Rossi
     * @param string $username
     * @return array|bool
     */
    public function getProfilo(string $username)
    {
        $database = new Database();
        $con = $database->getConnection();

        $username = $con->real_escape_string($username);

        $query = "SELECT utenti.*, ROUND(AVG(recensioni.valutazione)) AS media_recensioni FROM recensioni JOIN eventi ON (eventi.id = recensioni.id_evento) 
                    RIGHT JOIN utenti ON (recensioni.id_recensito = utenti.username) WHERE utenti.username = ?";

        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $username);
        
        if (!$stmt->execute())
            return false;
       
        $stmt->store_result();

        $profilo = array();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($username, $nome, $cognome, $descrizione, $foto, $corso, $mat_id, $media_recensioni);

            $stmt->fetch();

            if ($username === NULL)
                return $profilo;

            $profilo['utente'] = $username;
            $profilo['nome'] = $nome;
            $profilo['cognome'] = $cognome;
            $profilo['descrizione'] = $descrizione;
            $profilo['foto'] = $foto;
            $profilo['corso'] = $corso;
            $profilo['mat_id'] = $mat_id;
            $profilo['media_recensioni'] = $media_recensioni;
        }

        return $profilo;
    }

    /**
     * Dato un username e una password invia una richiesta al servizio di autenticazione Esse3 il quale restituisce "autenticazione riuscita" se le
     * credenziali sono corrette, altrimenti "autenticazione fallita".
     * dopodichè se si tratta di un nuovo studente per il sistema, quest'ultimo verrà aggiunto nel database, infine restituisce il valore di mat_id.
     * @author Angela Venditti, Daniela Rossi
     * @param string $username
     * @param string $password
     * @return array|bool
     */
    public function loginEsse3(string $username, string $password)
    {
        $jsonCredentials = file_get_contents('php://input');
        $credentials = json_decode($jsonCredentials);
        $ch = curl_init("https://app.unimol.it/app_2_2/api/getUser.php");

        $arrayCredential = array( //json da inviare a esse3
            'username' => $username,
            'password' => $password,

        );

        $jsonDataEncoded = json_encode($arrayCredential);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // imposta tipo di ritorno json

        $result = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($result); //risposta da esse3 'decodificata'
        //var_dump($response);

        if ($response->codice !== 0 && $response->codice !== 2) {
            return false;
        }
        //print_r($response);

        if ($response->codice == 2) {
            $matricolaMag = $response->carriere[0]->matricola;
            $jsonCredentials = file_get_contents('php://input');
            $credentials = json_decode($jsonCredentials);
            $ch = curl_init("https://app.unimol.it/app_2_2/api/getUser.php");

            $arrayCredential = array(
                'username' => $username,
                'password' => $password,
                'matricola' => $matricolaMag,
            );

            $jsonDataEncoded = json_encode($arrayCredential);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // imposta tipo di ritorno json

            $result = curl_exec($ch);
            curl_close($ch);

            $response = json_decode($result); //risposta da esse3 'decodificata'
        }

        $infoUtente = array();

        if ($response->codice == 0) {
            $infoUtente['response'] = $response->codice;
            $infoUtente['email'] = $response->result->email;
            $matricola = $infoUtente['matricola'] = $response->result->matricola;
            $nome = $infoUtente['nome'] = $response->result->nome;
            $cognome = $infoUtente['cognome'] = $response->result->cognome;
            $mat_id = $infoUtente['mat_id'] = $response->result->mat_id;
            $cds_nome = $infoUtente['cds_nome'] = $response->result->nome_cds;
            $foto = $infoUtente['img'] = $response->result->img;
            $pds = $response->result->pds;

            $libretto = array();

            for ($i = 0; $i < count($pds); $i++) {
                if (is_null($pds[$i]->STATO)) {
                    continue;
                }

                $tmp = array();
                $tmp['CODICE'] = $pds[$i]->CODICE;
                $tmp['DESCRIZIONE'] = $pds[$i]->DESCRIZIONE;
                $tmp['STATO'] = $pds[$i]->STATO;
                array_push($libretto, $tmp);
            }

        }

        $gestoreProfili = new GestoreProfili();
        $isAdded = $gestoreProfili->nuovoStudente($username, $nome, $cognome, $mat_id, $cds_nome, $foto);

        $info_utente = array();
        $info_utente['mat_id'] = $mat_id;
        $info_utente['libretto'] = $libretto;
        $info_utente['nuovo_utente'] = $isAdded;

        return $info_utente;
    }

    /**
     * Dato un username, nome, cognome, mat_id, corso e foto inserisce un nuovo studente
     * @author Angela Venditti, Daniela Rossi
     * @param string $username
     * @param string $nome
     * @param string $cognome
     * @param int $mat_id
     * @param string $corso
     * @param string $foto
     * @return bool
     */
    public function nuovoStudente(string $username, string $nome, string $cognome, int $matid, string $corso, string $foto)
    {
        $database = new Database();
        $con = $database->getConnection();

        $username = $con->real_escape_string($username);
        $nome = $con->real_escape_string($nome);
        $cognome = $con->real_escape_string($cognome);
        $matid = $con->real_escape_string($matid);
        $corso = $con->real_escape_string($corso);
        $foto = $con->real_escape_string($foto);

        $query = "INSERT INTO utenti (username, nome, cognome, matid, corso, foto) VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $con->prepare($query);

        $stmt->bind_param("sssiss", $username, $nome, $cognome, $matid, $corso, $foto);
        
        $isAdded = $stmt->execute();

        return $isAdded;
    }

    /**
     * Dai i parametri $foto, $descrizione e $username è possibile cambiare nella tabella utenti il valore dei campi foto e descrizione dell'utente
     * passato alla funzione. La funzione esegue inoltre un controllo sull'effettiva esecuzione della query, permettendo di capire se sia stata eseguita
     * o meno.
     * @author Francesca Zero
     * @param string $foto
     * @param string $descrizione
     * @param string $username
     * @return bool
     */
    public function modificaProfilo(string $foto, ?string $descrizione, string $username)
    {

        $database = new Database();
        $con = $database->getConnection();

        $foto = $con->real_escape_string($foto);
        $descrizione = isset($descrizione) ? $con->real_escape_string($descrizione) : null;
        $username = $con->real_escape_string($username);

        $query = "UPDATE utenti SET foto = ?, descrizione = ?  WHERE username = ?";

        $stmt = $con->prepare($query);
        $stmt->bind_param("sss", $foto, $descrizione, $username);

        return $stmt->execute();
    }

    /**
     * La funzione prende in input i parametri $esame, $toogle e $username.
     * Lo scopo della funzione è aggiungere o rimuovere l'esame scelto dalla tabella esami_scelti, avendo come parametro anche $username permettendo
     * di aggioungere o rimuovere la tupla nella relazione a seconda dei casi riferita solo all'utente scelto.
     * A seconda del valore del parametro $toogle la funzione decide se provare a inserire un nuovo record o provare a cancellare lo stesso.
     * @author Milena Maisto, Marco Brunetti
     * @param string $esame
     * @param bool $azione
     * @param string $username
     * @return bool
     */
    public function sceltaCarriera(string $esame, bool $azione, string $username)
    {
        $database = new Database();
        $con = $database->getConnection();

        $username = $con->real_escape_string($username);
        $esame = $con->real_escape_string($esame);

        if ($azione) {
            $query = "INSERT INTO esami_scelti (id_utente, id_esame) VALUES (?, ?) ";

            $stmt = $con->prepare($query);
            $stmt->bind_param("ss", $username, $esame);

            if (!$stmt->execute()){
                return false;
            }
        } else {
            $query = "DELETE FROM esami_scelti WHERE id_utente = ? AND id_esame = ?";

            $stmt = $con->prepare($query);
            $stmt->bind_param("ss", $username, $esame);

            $isDeleted = $stmt->execute();

            if ($isDeleted AND mysqli_affected_rows($con) < 1)
                return false;
        }

        return true;
    }

    /**
     * La funzione prende in input il parametro $username, permettendo così la visualizzazione delle recensioni fatte
     * e ricevute dall'utente identificato dal parametro della funzione.
     * @author Eros Di Meo
     * @param string $username
     * @return array|bool
     */
    public function getRecensioni(string $username)
    {
        $database = new Database();
        $con = $database->getConnection();

        $username = $con->real_escape_string($username);

        $query = "SELECT eventi.titolo, eventi.materia, eventi.data, recensioni.feedback, recensioni.valutazione, utenti.nome, utenti.cognome, utenti.foto, utenti.corso 
                    FROM eventi JOIN prenotazioni ON (prenotazioni.id_evento = eventi.id)
                    JOIN recensioni ON (recensioni.id_evento = prenotazioni.id_evento)
                    JOIN utenti ON (utenti.username = eventi.utente) WHERE recensioni.id_recensito = ?";

        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $username);
        
        if (!$stmt->execute())
            return false;
        
        $stmt->store_result();

        $recensioni_totali = $stmt->num_rows();
        $recensioni = array();

        if ($recensioni_totali > 0) {
            $stmt->bind_result($titolo, $materia, $data, $feedback, $valutazione, $nome, $cognome, $foto, $corso);

            $recensioni = array();

            while ($stmt->fetch()) {
                $temp = array();

                $temp['titolo'] = $titolo;
                $temp['materia'] = $materia;
                $temp['data'] = $data;
                $temp['feedback'] = $feedback;
                $temp['valutazione'] = $valutazione;
                $temp['nome'] = $nome;
                $temp['cognome'] = $cognome;
                $temp['foto'] = $foto;
                $temp['corso'] = $corso;

                array_push($recensioni, $temp);
            }
        }
        
        return $recensioni;
    }

    /**
     * Visualizza tutti gli eventi in cui si è riscontrata la partecipazione di un utente specifico e la cui data di partecipazione stabilita sia già trascorsa
     * @author Federica Ciccaglione, Marco Brunetti, Maurizio Albani
     * @param string $username
     * @return array|bool
     */
    public function getStoricoEventi(string $username)
    {
        $database = new Database();
        $con = $database->getConnection();

        $username = $con->real_escape_string($username);

        $query = "SELECT eventi.id, eventi.tipo, eventi.titolo, utente.creatore, eventi.richiesta, eventi.utente, eventi.materia, eventi.data,
                  GROUP_CONCAT(CONCAT_WS(' ', utenti.nome, utenti.cognome) SEPARATOR ', ') AS lista_partecipanti FROM
                  (SELECT CONCAT_WS(' ', nome, cognome) as creatore FROM utenti WHERE username = ?) as utente, eventi
                  JOIN prenotazioni ON (prenotazioni.id_evento = eventi.id) JOIN utenti ON (utenti.username = prenotazioni.id_utente)
                  WHERE (eventi.utente = ? AND DATE(eventi.data) < CURRENT_DATE()) GROUP BY eventi.id
                    UNION
                  SELECT eventi.id, eventi.tipo, eventi.titolo, selezione.creatore, eventi.richiesta, eventi.utente, eventi.materia, eventi.data, GROUP_CONCAT(CONCAT_WS(' ', utenti.nome, utenti.cognome) SEPARATOR ', ') AS lista_partecipanti FROM
                  ((SELECT eventi.id as evento, CONCAT_WS(' ', utenti.nome, utenti.cognome) as creatore FROM prenotazioni JOIN eventi ON (eventi.id = prenotazioni.id_evento)
                  JOIN utenti ON (utenti.username = eventi.utente) WHERE prenotazioni.id_utente = ?) AS selezione ) JOIN prenotazioni ON (prenotazioni.id_evento = selezione.evento)
                  JOIN eventi ON (eventi.id = selezione.evento) JOIN utenti ON (utenti.username = prenotazioni.id_utente) WHERE (DATE(eventi.data) < CURRENT_DATE()) GROUP BY eventi.id";

        $stmt = $con->prepare($query);
        $stmt->bind_param("sss", $username, $username, $username);
        
        if (!$stmt->execute())
            return false;
        
        $stmt->store_result();

        $numero_eventi = $stmt->num_rows;
        $storico = array();

        if ($numero_eventi > 0) {
            $stmt->bind_result($id, $tipo, $titolo, $creatore, $richiesta, $utente, $materia, $data, $lista_partecipanti);

            while($stmt->fetch()){
              $temp = array();
              
              $temp['id'] = $id;
              $temp['tipo'] = $tipo;
              $temp['titolo'] = $titolo;
              $temp['creatore'] = $creatore;
              $temp['richiesta'] = $richiesta;
              $temp['utente'] = $utente;
              $temp['materia'] = $materia;
              $temp['data'] = $data;
              $temp['lista_partecipanti'] = $lista_partecipanti;
              
              array_push($storico, $temp);
            }
        }

        return $storico;
    }

    /**
     * La funzione prende in input i parametri $id_evento, $feedback, $username, $feedback al fine di aggiungere una recensione composta da un feebdack, su un evento
     * trascorso e da parte di un utente che vi ha partecipato.
     * @author Marco Brunetti
     * @param int $id_evento
     * @param string $feedback
     * @param string $username
     * @param int $valutazione
     * @return bool
     */
    public function nuovaRecensione(int $id_evento, string $feedback, string $username, int $valutazione)
    {
        $database = new Database();
        $con = $database->getConnection();

        $id_evento = $con->real_escape_string($id_evento);
        $feedback = $con->real_escape_string($feedback);
        $username = $con->real_escape_string($username);
        $valutazione = $con->real_escape_string($valutazione);

        $query = "INSERT INTO recensioni (id_recensito, id_evento, feedback, valutazione) VALUES (
                    (SELECT prenotazioni.id_utente FROM eventi JOIN prenotazioni ON (eventi.id = prenotazioni.id_evento)
                    WHERE prenotazioni.id_evento = ? AND eventi.utente = ?), ?, ?, ?)";

        $stmt = $con->prepare($query);

        $stmt->bind_param("isisi", $id_evento, $username, $id_evento, $feedback, $valutazione);

        return $stmt->execute();
    }

    /**
     * La funzione prende in input il parametro $id_evento al fine di restituire le informazioni
     * dell'evento per il quale si sta inserendo una recensione
     * @author Maurizio Albani
     * @param int $id_evento
     * @return array|bool
     */
    public function getDettagliNuovaRecensione(int $id_evento)
    {
        $database = new Database();
        $con = $database->getConnection();

        $id_evento = $con->real_escape_string($id_evento);

        $query = "SELECT utenti.foto, CONCAT_WS(' ', utenti.nome, utenti.cognome) as recensito, utenti.corso, eventi.titolo, eventi.luogo, eventi.data
                    FROM prenotazioni JOIN eventi ON (prenotazioni.id_evento = eventi.id) 
                    JOIN utenti ON (prenotazioni.id_utente = utenti.username) WHERE eventi.id = ?";

        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $id_evento);
           
        if (!$stmt->execute()) {
            return false;
        }

        $stmt->store_result();

        $storico = array();

        if ($stmt->num_rows() > 0) {
            $stmt->bind_result($foto, $recensito, $corso, $titolo, $luogo, $data);

            $stmt->fetch();
            
            $storico['foto'] = $foto;
            $storico['recensito'] = $recensito;
            $storico['corso'] = $corso;
            $storico['titolo'] = $titolo;
            $storico['luogo'] = $luogo;
            $storico['data'] = $data;
        }

        return $storico;
    }

    /**
     * Elimina uno studente nella tabella utenti attraverso l'identificativo $username.
     * La funzione deve essere chiamata solo dai test, per i quali si è resa necessaria la creazione di un nuovo utente fittizio
     * @author Marco Brunetti
     * @param string $username
     * @return bool
     */
    public function testEliminaStudente(string $username)
    {
        $database = new Database();
        $con = $database->getConnection();

        $username = $con->real_escape_string($username);

        $query = "DELETE FROM utenti WHERE username = ?";

        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $username);

        if ($stmt->execute() AND mysqli_affected_rows($con) === 1){
            return true;
        }

        return false;
    }

    /**
     * La funzione prende in input i parametri $username.
     * Lo scopo della funzione è rimuovere tutte le prenotazioni dell'utente passato attraverso il parametro $username.
     * La funzione deve essere chiamata solo dai test, per i quali si è resa necessaria la creazione di un nuovo utente fittizio.
     * @author Marco Brunetti
     * @param string $username
     * @return boolean
     */
    public function testEliminaServizioRipetizione(string $username)
    {
        $database = new Database();
        $con = $database->getConnection();

        $username = $con->real_escape_string($username);

        $query = "DELETE FROM esami_scelti WHERE id_utente = ?";

        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $username);

        if ($stmt->execute() AND mysqli_affected_rows($con) < 1)
            return false;

        return true;
    }

    /**
     * La funzione prende in input i parametri $username.
     * Lo scopo della funzione è rimuovere tutte le recensioni dell'utente passato attraverso il parametro $username.
     * La funzione deve essere chiamata solo dai test, per i quali si è resa necessaria la creazione di un nuovo utente fittizio.
     * @author Maurizio Albani
     * @param string $username
     * @return boolean
     */
    public function testEliminaRecensioni(string $username)
    {
        $database = new Database();
        $con = $database->getConnection();

        $username = $con->real_escape_string($username);

        $query = "DELETE FROM recensioni WHERE id_recensito = ?";

        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $username);

        if ($stmt->execute() AND mysqli_affected_rows($con) < 1)
            return false;

        return true;
    }

}
