# Abilitare l'utilizzo di OpenVPN per accedere a myddleware

Prima bisogna valorizzare le seguenti variabili nel file `.env`

```
external_address=...
openvpn_port=...
```

- `external_address` deve riportare l'IP pubblico della macchia server in cui e installato myddleware
- `openvpn_port` in generale mettere sempre il valore 1194, se questa porta nel server non è usabile, allora chiede supporto avanzato.

Adesso, seguire i seguenti comandi 

```
docker-compose up -d
```

Fare l'inizializzazione del Server OpenVPN con i seguenti comandi

```
docker-compose run --rm vpn set_passphrase
```

Seguire la procedura inserendo la passphrase più opportuna tutte le volte che viene chiesta
(NOTA: in questa fase si sta decidendo la passphrase che poi sarà usata in futuro per creare le varie 
istanze dei client VPN che dovranno connettersi, quindi prenderne e salvarla sul Servizio Cloud)

Adesso possiamo creare il primo client

```
docker-compose exec vpn add_client NOMECLIENT
```

con questo comando sarà creato un client verranno chiesti una chiave segrata per lo specifico client e
la passphrase inserita in fase di inizializzazionde del server necessaria affinche si dimostri di avere
i permessi di poter creare utenze in questo server.

Adesso possiamo generare il file `.ovpn` da inviare alla Workstastion o Server remoto che vorra raggiungere Myddleware.
Usare il seguente comando

```
docker-compose exec vpn get_client NOMECLIENT > NOMECLIENT.ovpn
```

Consegnare il file appena creato e la chiava segreta dell'utente a chi opportuno.

## Casi particolari

Potrebbe essere necessario fore in modo che Myddleware possa raggiungere applicazioni server che si trovano
in client che si sono connessi alla VPN per fare in modo bisogna scrivere una regola dentro la variabile `vpn_client_forward`
Inserire qui l'ip virtuale assegnato al client seguito da ':' e la porta del servizio, esempio

```
vpn_client_forward=192.168.255.6:22
```

dopo che vinene cambiata questa variabile bisogna lanciare il comando

```
docker-compose up -d
```

per rendere effettiva la modifica

questa riga permettere a myddleware di accedere al servizio PDF presente sul server
usando come HOST la parola `vpn` e come porta la `22`

NOTA: Non usare gli IP virtuali all'interno di Myddleware

## Problemi di DNS nel client


