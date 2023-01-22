<?php


interface AbstractGestorePrenotazioni
{
    public function getPrenotazioni(string $username);
    public function getDettagliRipetizione(int $id_evento);
    public function getDettagliGruppo(int $id_evento);
    public function annullaPrenotazione(int $id_evento, string $username);
}