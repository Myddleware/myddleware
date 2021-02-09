# Deployment

Il deployment consiste nel installare l'applicazione su un server e renderla facilmente operativa ed utilizzabile
Affinche tutto vada a buon fine il server deve avere installate le seguenti versione dei software in elenco

- Bash, Git e Make
- Docker 18 o superiore 
- Docker Compose 1.17 o superiore

## Iniziamo

Il primo passo consiste nel clonare il progetto sul server dentro la cartella più opportuna, eseguite is sequenti comandi,
attenzione, la cartella 'myhost' è un segnaposto, ti verrà indicata il nome corretto di volta in volta. 

```bash
sudo su
cd /home/(myhost)
git clone https://github.com/opencrmitalia-official/myddleware.git
cd myddleware
```

Con l'ultimo comando ci troviamo nella cartella del nostro software appena scaricato. Procediamo con l'installazione
eseguite il comando,

```bash
make install
```

Adesso bisogna modificare il file `.env` ed inserire i valori per permettere il backup su AWS S3 storage, valorizzare le seguenti chiavi

```dotenv
backup_target=
aws_access_key_id=
aws_secret_access_key=
```

> **AVVISO:** Se non vengono specificate queste chiavi non sarà eseguito il backup

Adesso eseguire il comando per la creazione dei dati nel database

```bash
make setup
```

Adesso l'applicazione sara correttamente installata per accedere usate le seguenti istruzioni

- Visitare la pagina <http://<indirizzo_macchina>:30080> 
- Usate le seguenti credenziali: admin/admin

> **AVVISO**: Potrebbe essere necessario aggiungere per la specifica installazione di myddleware delle regole di /etc/hosts
> ad esempio per raggiungere CRM locali on dentro reti speciali, basterà scrivere le regole dentro il file `hosts` che trovate nella root del prpgetto

Adesso bisogna mettere la applicazione in modalità PRODUCTION

```bash
make prod
```
