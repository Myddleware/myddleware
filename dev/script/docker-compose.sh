#!/usr/bin/env bash

##
# Questo file sarà chiamato da SystemD per riavviare tutte
# le istanze Myddleware presneti su questo server,
# se si aggiungono istanze ricordardi di cambiare il nome istanza presente nella riga 'echo'.
# Inoltre bisogna cambiare il percorso indicato dal comando 'cd' segnando il path della istanza che si vuole riavviare
##

## == DUPLICARE QUESTO BLOCCO DI CODICE PER OGNI ISTANZA PRESENTE SUL SERVER ==
echo "RIAVVIO MYDDLEWARE: ...nome istanza..."
cd /home/ubuntu/myddleware
docker-compose -f docker-compose.yml up -d --force-recreate
## == (fine) ==
