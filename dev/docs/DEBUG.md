# Debug

Questo documento ha lo scopo di supportare il consulenze/programmatore nel debuggare o individuare problemi di sincronizzazione
di una istanza Myddleware. Il documento cerca di dare una strategia di ricerca dei problemi e non è un elenco completo 
di tutti i problemi che si possono riscontrare, la storia e le patch specifiche per i problemi rilevati saranno in una FAQ di riferimento.

## Cominciamo il debug

Per cominciare la sessione di debug bisogna posizionarsi con un terminale BASH/SH nel server che ospita l'applicazione myddleware,
dopo di che eseguite il seguente comando

```bash
make debug
```

Da questo momento in poi saranno disponibili i seguenti strumenti

## Accedere a Myddleware

Visitare l'indirizzo seguente <http://IP_MACCHINA:30080> oppure <http://localhost:30080> nel caso si stia debuggando localmente
