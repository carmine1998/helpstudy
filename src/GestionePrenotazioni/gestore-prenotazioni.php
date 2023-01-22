<?php

require_once 'abstract-gestore-prenotazioni.php';

class GestorePrenotazioni implements AbstractGestorePrenotazioni
{

    /**
     * Restituisce tutte le prenotazioni effettuate, sia per eventi di tipo "gruppo di studio" che per eventi di tipo "ripetizione" di un utente specifico.
     * Le prenotazioni restituite riguarderanno soltanto eventi non ancora trascorsi.
     * @author Marco Brunetti
     * @param string $username
     * @return array|bool
     */
    public function getPrenotazioni(string $username)
    {
        $database = new Database();
        $con = $database->getConnection();

        $username = $con->real_escape_string($username);
        
        $query = "SELECT prenotazioni.id_evento, eventi.luogo, eventi.data, eventi.titolo, utenti.username, utenti.corso, utenti.foto, utenti.nome, utenti.cognome, eventi.materia, eventi.tipo, eventi.id_esame from prenotazioni
            JOIN eventi ON (eventi.id = prenotazioni.id_evento) JOIN utenti ON (utenti.username = eventi.utente) WHERE prenotazioni.id_utente = ? 
            AND DATE(eventi.data) >= CURRENT_DATE()";

        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $username);
        
        if (!$stmt->execute())
            return false;
    
        $stmt->store_result();

        $prenotazioni = array();

        if ($stmt->num_rows > 0) {

            $stmt->bind_result($id_evento, $luogo, $data, $titolo, $username, $corso, $foto, $nome, $cognome, $materia, $tipo, $id_esame);
            
            while ($stmt->fetch()) {
                $temp = array();

                $temp['id_evento'] = $id_evento;
                $temp['luogo'] = $luogo;
                $temp['data'] = $data;
                $temp['titolo'] = $titolo;
                $temp['username'] = $username;
                $temp['corso'] = $corso;
                $temp['foto'] = $foto;
                $temp['nome'] = $nome;
                $temp['cognome'] = $cognome;
                $temp['materia'] = $materia;
                $temp['tipo'] = $tipo;
                $temp['id_esame'] = $id_esame;

                array_push($prenotazioni, $temp);
            }
        }

        return $prenotazioni;
    }

    /**
     * Restituisce i dettagli di un evento di tipo "ripetizione"; è necessario che l'id dell'evento preso in input sia realmente associabile a un
     * evento di tipo "ripetizione"
     * @author Marco Brunetti
     * @param int $id_evento
     * @return object|bool
     */
    public function getDettagliRipetizione(int $id_evento)
    {
        $database = new Database();
        $con = $database->getConnection();

        $id_evento = $con->real_escape_string($id_evento);
        
        $query = "SELECT eventi.data, luogo, titolo, richiesta FROM eventi WHERE id = ? AND tipo = 'ripetizione'";

        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $id_evento);
        
        if (!$stmt->execute())
            return false;

        $stmt->store_result();

        $dettagli_ripetizione = array();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($data, $luogo, $titolo, $richiesta);

            $stmt->fetch();

            $dettagli_ripetizione['data'] = $data;
            $dettagli_ripetizione['luogo'] = $luogo;
            $dettagli_ripetizione['titolo'] = $titolo;
            $dettagli_ripetizione['richiesta'] = $richiesta;
        }
        
        return $dettagli_ripetizione;
    }

    /**
     * Restituisce i dettagli di un evento di tipo "gruppo di studio"; è necessario che l'id dell'evento preso in input sia realmente associabile a un
     * evento di tipo "gruppo di studio"
     * @author Marco Brunetti
     * @param int $id_evento
     * @return object|bool
     */
    public function getDettagliGruppo(int $id_evento)
    {
        $database = new Database();
        $con = $database->getConnection();
        
        $id_evento = $con->real_escape_string($id_evento);

        $query = "SELECT eventi.data, luogo, titolo, richiesta, partecipanti, GROUP_CONCAT(CONCAT_WS(' ', utenti.nome, utenti.cognome) SEPARATOR ', ') AS lista_partecipanti
             FROM eventi JOIN prenotazioni ON (prenotazioni.id_evento = eventi.id) JOIN utenti ON (utenti.username = prenotazioni.id_utente) WHERE eventi.id = ?
              AND tipo = 'gruppo'";
        
        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $id_evento);
            
        if (!$stmt->execute())
            return false;

        $stmt->store_result();

        $dettagli_gruppo = array();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($data, $luogo, $titolo, $richiesta, $partecipanti, $lista_partecipanti);
            $stmt->fetch();

            if ($titolo === NULL)
                return $dettagli_gruppo;

            $dettagli_gruppo['data'] = $data;
            $dettagli_gruppo['luogo'] = $luogo;
            $dettagli_gruppo['titolo'] = $titolo;
            $dettagli_gruppo['richiesta'] = $richiesta;
            $dettagli_gruppo['partecipanti'] = $partecipanti; 
            $dettagli_gruppo['lista_partecipanti'] = $lista_partecipanti;
        }

        return $dettagli_gruppo;
    }

    /**
     * Dati i parametri in input $id_evento e $username permette di annullare una prenotazione ad un evento esistente precedentemente effettuata dall'utente passato in input e identificata tramite
     * il suo id.
     * @author Marco Brunetti
     * @param int $id_evento
     * @param string $username
     * @return bool
     */
    public function annullaPrenotazione(int $id_evento, string $username)
    {
        $database = new Database();
        $con = $database->getConnection();

        $id_evento = $con->real_escape_string($id_evento);
        $username = $con->real_escape_string($username);

        $query = "DELETE prenotazioni.* FROM prenotazioni WHERE id_evento = ? AND id_utente = ?";

        $stmt = $con->prepare($query);

        $stmt->bind_param("is", $id_evento, $username);
        
        if (!$stmt->execute())
            return false;

        if (mysqli_affected_rows($con) > 0)
            return true;

        return false;
    }

     
    /**
     * Dato il parametro in input $username permette di annullare tutte le prenotazioni di un'utente.
     * La funzione deve essere usata solo per fini di testing e non deve essere richiamata in altre funzioni, la funzionalità integrata
     * non è prevista nelle specifiche richieste.
     * @author Marco Brunetti
     * @param string $username
     * @return bool
     */
    public function testAnnullaPrenotazioni(string $username)
    {
        $database = new Database();
        $con = $database->getConnection();

        $username = $con->real_escape_string($username);

        $query = "DELETE prenotazioni.* FROM prenotazioni WHERE id_utente = ?";

        $stmt = $con->prepare($query);

        $stmt->bind_param("s", $username);
        
        if ($stmt->execute() AND mysqli_affected_rows($con) > 0)
            return true;

        return false;
    }
}