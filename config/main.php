<?php

GW::s('DB/INIT_SQLS',"SET SESSION sql_mode = '';"); //mysql 5.7 

GW::s('WSS/MAX_CLIENTS', 100);
GW::s('WSS/MAX_CONNECTIONS_PER_IP', 100);
GW::s('WSS/MAX_REQUESTS_PER_MINUTE', 2000);


GW::s('REPOS_DIR', dirname(__DIR__).'/repository/');

include __DIR__.'/project.php';


GW::s('WSS/IRCURI', (GW::s('WSS/SSL_OPT/ENABLED') ? 'wss':'ws').'://'.GW::s('WSS/HOSTNAME').':'.GW::s('WSS/PORT').'/irc');

