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








