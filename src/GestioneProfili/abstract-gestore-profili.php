<?php

interface AbstractGestoreProfili
{
    public function getProfilo(string $username);
    public function loginEsse3(string $username, string $password);
    public function nuovoStudente(string $username, string $nome, string $cognome, int $matid, string $corso, string $foto);
    public function modificaProfilo(string $foto, string $descrizione, string $username);
    public function sceltaCarriera(string $esame, bool $azione, string $username);
    public function getRecensioni(string $username);
    public function nuovaRecensione(int $id_evento, string $feedback, string $username, int $valutazione);
    public function getStoricoEventi(string $username);
    public function getDettagliNuovaRecensione(int $id_evento);
}