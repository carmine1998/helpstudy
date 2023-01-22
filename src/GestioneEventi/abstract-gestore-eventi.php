<?php

interface AbstractGestoreEventi
{
    public function getEventiHome(string $username, array $pds);
    public function getEvento(int $idEvento);
    public function getPartecipanti(int $idEvento);
    public function getProfiloCreatore(string $username);
    public function postPrenotazione(string $username, int $idEvento);
    public function getRecensioniCreatore(string $username);
}