<?php


interface AbstractGestoreEventiPersonali
{
    public function postCreaEvento(string $tipo, string $username, string $titolo, string $data, string $luogo, string $materia, string $richiesta,
                                    int $partecipanti, string $idEsame);
    public function getEventiPersonali(string $username);
    public function putModificaEvento(string $titolo, string $data, string $luogo, string $richiesta, int $idEvento);
    public function deleteEvento(int $idEvento, string $username);
    public function getDettagliEventoPersonale(int $idEvento);
}