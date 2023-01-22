<?php

require_once 'abstract-gestore-eventi-personali.php';

class GestoreEventiPersonali implements AbstractGestoreEventiPersonali
{
    /**
     * Dati in input il tipo, l'username, il titolo, la data,
     * il luogo, la materia, la descrizione, il numero dei partecipanti e l'idEsame, crea un nuovo evento
     * @author Carmine Salvatore, Daniele Campopiano
     * @param string $tipo
     * @param string $username
     * @param string $titolo
     * @param string $data
     * @param string $luogo
     * @param string $materia
     * @param string $richiesta descrizione dell'evento
     * @param int $partecipanti
     * @param string $idEsame
     * @return bool
     */
    public function postCreaEvento(string $tipo, string $username, string $titolo, string $data, string $luogo, string $materia, string $richiesta,
                                    int $partecipanti, string $idEsame)
    {
        $database = new Database();
        $con = $database->getConnection();

        $tipo = $con->real_escape_string($tipo);
        $username = $con->real_escape_string($username);
        $titolo = $con->real_escape_string($titolo);
        $data = $con->real_escape_string($data);
        $luogo = $con->real_escape_string($luogo);
        $materia = $con->real_escape_string($materia);
        $richiesta = $con->real_escape_string($richiesta);
        $partecipanti = $con->real_escape_string($partecipanti);
        $idEsame = $con->real_escape_string($idEsame);

        $query = "INSERT INTO eventi(tipo, utente, titolo, data, luogo, materia, richiesta, partecipanti, id_esame) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $con->prepare($query);
        $stmt->bind_param("sssssssis", $tipo, $username, $titolo, $data, $luogo, $materia, $richiesta, $partecipanti, $idEsame);

        return $stmt->execute();
    }

    /**
     * Dato l'username dell'utente, restituisce gli eventi personali ancora attivi
     * @author Daniele Campopiano, Carmine Salvatore
     * @param string $username
     * @return array|bool
     */
    public function getEventiPersonali(string $username)
    {
        $database = new Database();
        $con = $database->getConnection();

        $username = $con->real_escape_string($username);

        $query = "SELECT eventi.id, eventi.tipo, eventi.data, eventi.titolo, eventi.richiesta, eventi.materia, eventi.id_esame FROM eventi 
                    WHERE (utente = ? AND DATE(data) > CURRENT_DATE())";

        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $username);

        if (!$stmt->execute()){
            return false;
        }

        $stmt->store_result();

        $numero_eventi_trovati = $stmt->num_rows();

        $eventi = array();

        if ($numero_eventi_trovati > 0) {
            $stmt->bind_result($id, $tipo, $data, $titolo, $richiesta, $materia, $id_esame);

            while ($stmt->fetch()) {
                $temp = array();

                $temp['id'] = $id;
                $temp['tipo'] = $tipo;
                $temp['data'] = $data;
                $temp['titolo'] = $titolo;
                $temp['richiesta'] = $richiesta;
                $temp['materia'] = $materia;
                $temp['id_esame'] = $id_esame;

                array_push($eventi, $temp);
            }
        }
        
        return $eventi;
    }

    /**
     * Dati il titolo, la data, il luogo, la richiesta e l'idEvento,modifica l'evento
     * @author Alessio Olivieri, Mario Rivelli
     * @param string $titolo
     * @param string $data
     * @param string $luogo
     * @param string $richiesta descrizione dell'evento
     * @param int $idEvento
     * @return bool
     */
    public function putModificaEvento(string $titolo, string $data, string $luogo, string $richiesta, int $idEvento)
    {
        $database = new Database();
        $con = $database->getConnection();

        $titolo = $con->real_escape_string($titolo);
        $data = $con->real_escape_string($data);
        $luogo = $con->real_escape_string($luogo);
        $richiesta = $con->real_escape_string($richiesta);
        $idEvento = $con->real_escape_string($idEvento);

        $query = "UPDATE eventi SET titolo=? , eventi.data=?, luogo=?, richiesta=? WHERE eventi.id = ?";

        $stmt = $con->prepare($query);
        $stmt->bind_param("ssssi", $titolo, $data, $luogo, $richiesta, $idEvento);

        return $stmt->execute();
    }

    /**
     * Dato l'id di un evento e l'username di un utente, elimina dal DB l'evento se il creatore dell'evento corrisponde con l'username specificato.
     * @author Maurizio Albani
     * @param int $idEvento
     * @param string $username
     * @return bool
     */
    public function deleteEvento(int $idEvento, string $username)
    {
        $database = new Database();
        $con = $database->getConnection();

        $idEvento = $con->real_escape_string($idEvento);
        $username = $con->real_escape_string($username);

        $query1 = "DELETE FROM prenotazioni WHERE prenotazioni.id_evento = ?";
        $query2 = "DELETE FROM eventi WHERE eventi.id = ? AND eventi.utente = ?";

        $stmt1 = $con->prepare($query1);
        $stmt2 = $con->prepare($query2);
        $stmt1->bind_param("i", $idEvento);
        $stmt2->bind_param("is", $idEvento, $username);

        $driver = new mysqli_driver();
        $driver->report_mode = MYSQLI_REPORT_ALL;
        $con->begin_transaction();

        try {
            $stmt1->execute();
            $stmt2->execute();

            if ($stmt2->affected_rows === 1) {
                $driver->report_mode = MYSQLI_REPORT_OFF;
                $con->commit();

                return true;
            }
        } catch (mysqli_sql_exception $e) { }

        $driver->report_mode = MYSQLI_REPORT_OFF;
        $con->rollback();

        return false;
    }

    /**
     * Dato l'id di un proprio evento, restituisce i dettagli di quell'evento
     * @author Alessio Olivieri
     * @param int $idEvento
     * @return array|bool
     */
    public function getDettagliEventoPersonale(int $idEvento)
    {
        $database = new Database();
        $con = $database->getConnection();

        $idEvento = $con->real_escape_string($idEvento);

        $query = "SELECT utenti.foto, eventi.titolo, eventi.richiesta, eventi.luogo, eventi.materia, eventi.data, eventi.partecipanti 
                    FROM (eventi JOIN utenti ON eventi.utente = utenti.username) WHERE eventi.id = ?";

        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $idEvento);
        
        if (!$stmt->execute())
            return false;
        
        $stmt->store_result();

        $dettaglieventopersonale = array();

        if ($stmt->num_rows() > 0) {
            $stmt->bind_result($foto, $titolo, $richiesta, $luogo, $materia, $data, $partecipanti);
            $stmt->fetch();

            $dettaglieventopersonale['foto'] = $foto;
            $dettaglieventopersonale['titolo'] = $titolo;
            $dettaglieventopersonale['richiesta'] = $richiesta;
            $dettaglieventopersonale['luogo'] = $luogo;
            $dettaglieventopersonale['materia'] = $materia;
            $dettaglieventopersonale['data'] = $data;
            $dettaglieventopersonale['partecipanti'] = $partecipanti;
        }
        
        return $dettaglieventopersonale;
    }

    /**
     * La funzione prende in input l'utente di cui si vogliono eliminare tutte gli eventi creati.
     * La funzione deve essere richiamata solo in fase di testing.
     * @author Marco Brunetti
     * @param string $username
     * @return array|bool
     */
    function testEliminaEventiCreati(string $username)
    {
        $database = new Database();
        $con = $database->getConnection();

        $username = $con->real_escape_string($username);

        $query = "DELETE FROM eventi WHERE eventi.utente = ?";

        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $username);

        if ($stmt->execute() AND mysqli_affected_rows($con) > 0)
            return true;

        return false;
    }
}
