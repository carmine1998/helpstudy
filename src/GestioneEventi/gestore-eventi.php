<?php

require_once 'abstract-gestore-eventi.php';

class GestoreEventi implements AbstractGestoreEventi
{
    /**
     * Dato l'username dell'utente ed il suo percorso di studi,
     * restituisce gli eventi ai quali l'utente può prenotarsi
     * ovvero le richieste di ripetizioni legate ad esami già superati
     * e i gruppi di studio legati agli esami ancora non superati
     * @author Maurizio Albani
     * @param string $username
     * @param array $pds percorso di studi dell'utente
     * @return array|bool
     */
    public function getEventiHome(string $username, array $pds)
    {
        $database = new Database();
        $con = $database->getConnection();

        $username = $con->real_escape_string($username);

        $esami_non_dati = "";

        if (!is_array($pds)) {
            return false;
        }

        for ($i = 0; $i < count($pds); $i++) {
            if ($pds[$i]['STATO'] === "F") {
                $codice_esame = $con->real_escape_string($pds[$i]["CODICE"]);
                if ($esami_non_dati === "") {
                    $esami_non_dati = "'" . $codice_esame . "'";
                } else {
                    $esami_non_dati .= ", '" . $codice_esame . "'";
                }
            }
        }

        if ($esami_non_dati !== "") {

            //questa query restituisce tutte le ripetizioni e i gruppi ai quali l'utente può prenotarsi e ai aquali ancora non si è prenotato
            $query = "SELECT DISTINCT eventi_home.id, eventi_home.tipo, eventi_home.data, eventi_home.titolo, eventi_home.richiesta, eventi_home.materia, eventi_home.id_esame
             FROM (
             SELECT eventi.id, eventi.tipo, eventi.data, eventi.titolo, eventi.richiesta, eventi.materia, eventi.id_esame FROM esami_scelti JOIN eventi ON (eventi.id_esame = esami_scelti.id_esame) WHERE ((eventi.utente != ?) AND (esami_scelti.id_utente = ?) AND (eventi.tipo = 'ripetizione') AND (DATE(eventi.data) > CURRENT_DATE()))
             UNION
             SELECT eventi.id, eventi.tipo, eventi.data, eventi.titolo, eventi.richiesta, eventi.materia, eventi.id_esame FROM eventi WHERE ((eventi.utente != ?) AND (DATE(eventi.data) > CURRENT_DATE()) AND (eventi.tipo = 'gruppo') AND (eventi.id_esame IN ($esami_non_dati)))
             ) AS eventi_home
             LEFT JOIN (
             SELECT eventi.id, eventi.tipo, eventi.data, eventi.titolo, eventi.richiesta, eventi.materia, eventi.id_esame FROM prenotazioni JOIN eventi ON(eventi.id = prenotazioni.id_evento) WHERE prenotazioni.id_utente = ?
             ) AS mie_prenotazioni
             ON eventi_home.id = mie_prenotazioni.id
             WHERE mie_prenotazioni.id IS NULL";

            $stmt = $con->prepare($query);
            $stmt->bind_param("ssss", $username, $username, $username, $username);

        } else {
            //questa query restituisce tutte le ripetizioni alle quali l'utente può prenotarsi e alle aquali ancora non si è prenotato
            $query = "SELECT DISTINCT eventi_home.id, eventi_home.tipo, eventi_home.data, eventi_home.titolo, eventi_home.richiesta, eventi_home.materia, eventi_home.id_esame
            FROM (
            SELECT eventi.id, eventi.tipo, eventi.data, eventi.titolo, eventi.richiesta, eventi.materia, eventi.id_esame FROM esami_scelti JOIN eventi ON (eventi.id_esame = esami_scelti.id_esame) WHERE ((eventi.utente != ?) AND (esami_scelti.id_utente = ?) AND (eventi.tipo = 'ripetizione') AND (DATE(eventi.data) > CURRENT_DATE()))
            ) AS eventi_home
            LEFT JOIN (
            SELECT eventi.id, eventi.tipo, eventi.data, eventi.titolo, eventi.richiesta, eventi.materia, eventi.id_esame FROM prenotazioni JOIN eventi ON(eventi.id = prenotazioni.id_evento) WHERE prenotazioni.id_utente = ?
            ) AS mie_prenotazioni
            ON eventi_home.id = mie_prenotazioni.id
            WHERE mie_prenotazioni.id IS NULL";

            $stmt = $con->prepare($query);
            $stmt->bind_param("sss", $username, $username, $username);
        }

        if (!$stmt->execute()) {
            return false;
        }
        $stmt->store_result();

        $eventiHome = array();

        if ($stmt->num_rows() >= 0) {
            $stmt->bind_result($id, $tipo, $data, $titolo, $richiesta, $materia, $id_esame);

            while ($stmt->fetch()) {
                $temp = array();
                $temp['id'] = $id;
                $temp['tipo'] = $tipo;
                $temp['titolo'] = $titolo;
                $temp['data'] = $data;
                $temp['materia'] = $materia;
                $temp['richiesta'] = $richiesta;
                $temp['id_esame'] = $id_esame;

                array_push($eventiHome, $temp);
            }
        }

        return $eventiHome;
    }

    /**
     * Dato l'id di un evento, restituisce i dettagli ad esso associati
     * @author Carmine Salvatore, Daniele Campopiano
     * @param int $idEvento
     * @return array|bool
     */
    public function getEvento(int $idEvento)
    {
        $database = new Database();
        $con = $database->getConnection();

        $idEvento = $con->real_escape_string($idEvento);

        $query = "SELECT eventi.*, utenti.nome, utenti.cognome, utenti.foto FROM eventi JOIN utenti ON (eventi.utente = utenti.username) WHERE eventi.id = ? ";

        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $idEvento);

        if (!$stmt->execute())
            return false;
        
        $stmt->store_result();

        $evento = array();

        if ($stmt->num_rows() > 0) {
            $stmt->bind_result($id, $tipo, $utente, $titolo, $data, $luogo, $materia, $richiesta, $partecipanti, $id_esame, $nome, $cognome, $foto);

            $stmt->fetch();

            $evento['id'] = $id;
            $evento['tipo'] = $tipo;
            $evento['utente'] = $utente;
            $evento['titolo'] = $titolo;
            $evento['data'] = $data;
            $evento['luogo'] = $luogo;
            $evento['materia'] = $materia;
            $evento['richiesta'] = $richiesta;
            $evento['partecipanti'] = $partecipanti;
            $evento['id_esame'] = $id_esame;
            $evento['nome'] = $nome;
            $evento['cognome'] = $cognome;
            $evento['foto'] = $foto;
        }

        return $evento;
    }

    /**
     * Dato l'id di un evento, restituisce nome e cognome dei partecipanti all'evento
     * @author Maurizio Albani
     * @param int $idEvento
     * @return array|bool
     */
    public function getPartecipanti(int $idEvento)
    {
        $database = new Database();
        $con = $database->getConnection();

        $idEvento = $con->real_escape_string($idEvento);

        $query = "SELECT CONCAT_WS(' ', utenti.nome, utenti.cognome) AS partecipante FROM (utenti JOIN prenotazioni ON utenti.username=prenotazioni.id_utente)
                    WHERE prenotazioni.id_evento=?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $idEvento);

        if (!$stmt->execute()) {
            return false;
        }

        $stmt->store_result();

        $partecipanti = array();

        if ($stmt->num_rows() > 0) {
            $stmt->bind_result($partecipante);

            $partecipanti = array();

            while ($stmt->fetch()) {
                $temp = array();
                $temp['partecipante'] = $partecipante;

                array_push($partecipanti, $temp);
            }
            
        }

        return $partecipanti;
    }

    /**
     * Dato l'username, restituisce i dettagli del profilo ad esso associato
     * @author Mario Rivelli, Alessio Olivieri
     * @param string $username
     * @return array|bool
     */
    public function getProfiloCreatore(string $username)
    {
        $database = new Database();
        $con = $database->getConnection();

        $username = $con->real_escape_string($username);

        $query = "SELECT utenti.*, ROUND(AVG(recensioni.valutazione)) media_recensioni FROM recensioni JOIN eventi ON (eventi.id = recensioni.id_evento) 
                    RIGHT JOIN utenti ON (recensioni.id_recensito = utenti.username) WHERE utenti.username = ?";

        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $username);
        
        if (!$stmt->execute())
            return false;
        
        $stmt->store_result();

        $profiloCreatore = array();

        if ($stmt->num_rows() > 0) {
            $stmt->bind_result($username, $nome, $cognome, $descrizione, $foto, $corso, $matid, $mediaRecensioni);

            $stmt->fetch();
            
            $profiloCreatore['username'] = $username;
            $profiloCreatore['nome'] = $nome;
            $profiloCreatore['cognome'] = $cognome;
            $profiloCreatore['descrizione'] = $descrizione;
            $profiloCreatore['foto'] = $foto;
            $profiloCreatore['corso'] = $corso;
            $profiloCreatore['matid'] = $matid;
            $profiloCreatore['media_recensioni'] = $mediaRecensioni;
        }

        return $profiloCreatore;
    }

    /**
     * Dati l'username e l'idEvento, crea una prenotazione all'evento
     * @author Maurizio Albani, Alessio Olivieri, Carmine Salvatore, Mario Rivelli, Daniele Campopiano
     * @param string $username
     * @param int $idEvento
     * @return array|bool
     */
    public function postPrenotazione(string $username, int $idEvento)
    {
        $database = new Database();
        $con = $database->getConnection();

        $query = "INSERT INTO prenotazioni (id_utente, id_evento) VALUES (?, ?)";

        $stmt = $con->prepare($query);
        $stmt->bind_param("si", $username, $idEvento);

        return $stmt->execute();
    }

    /**
     * Dato un username, restituisce le recensioni ad esso associato
     * @author Daniele Campopiano, Carmine Salvatore, Alessio Olivieri, Mario Rivelli
     * @param string $username
     * @return array|bool
     */
    public function getRecensioniCreatore(string $username)
    {
        $database = new Database();
        $con = $database->getConnection();

        $username = $con->real_escape_string($username);

        $query = "SELECT CONCAT_WS(' ',utenti.nome, utenti.cognome) as recensore, utenti.foto as foto_recensore, recensioni.feedback AS recensione,
                    recensioni.valutazione as stelle FROM recensioni JOIN eventi ON (recensioni.id_evento = eventi.id)
                    JOIN utenti ON (eventi.utente = utenti.username) WHERE (recensioni.id_recensito = ?)";

        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $username);

        if (!$stmt->execute()) {
            return false;
        }

        $stmt->store_result();

        $eventi = array();

        if ($stmt->num_rows() > 0) {
            $stmt->bind_result($recensore, $foto_recensore, $recensione, $stelle);

            while ($stmt->fetch()) {
                $temp = array();
                $temp['recensore'] = $recensore;
                $temp['foto_recensore'] = $foto_recensore;
                $temp['recensione'] = $recensione;
                $temp['stelle'] = $stelle;

                array_push($eventi, $temp);

            }
        }

        return $eventi;
    }
}
