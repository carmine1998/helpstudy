
# HelpStudy-Backend

Il backend dell'applicazione HelpStudy che gestisce l'interazione tra il frontend ed il database.


## Tecnologie utilizzate

**-** PHP

**-** MySql



## Pre-requisiti

Bisogna aver installato xampp, PHP, Docker e PHPcomposer.


## Installazione
Spostarsi in xampp\htdocs\ ed effettuare il clone della repo.
Spostarsi all'interno della repo e lanciare il comando 
```bash
  composer install
```

## Esecuzione
Aprire xampp e avviare Apache e MySql

## PHPMyAdmin con Docker
Si può gestire il database con PHPMyAdmin utilizzando Docker
Per poter avviare Docker è sufficiente lanciare il comando
```bash
  docker compose up --build -d 
```

## Attenzione
Nel caso in cui il server MySql fosse già in esecuzione, è necessario interromperlo per poter utilizzare xampp