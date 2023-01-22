<?php

require_once 'Utilities/Database.php';
require_once 'Utilities/Route.php';

require_once 'GestioneEventi/eventi-routes.php';
require_once 'GestioneEventiPersonali/eventi-personali-routes.php';
require_once 'GestionePrenotazioni/prenotazioni-routes.php';
require_once 'GestioneProfili/profili-routes.php';


EventiRoutes::registerRoutes($app);
EventiPersonaliRoutes::registerRoutes($app);
PrenotazioniRoutes::registerRoutes($app);
ProfiliRoutes::registerRoutes($app);
