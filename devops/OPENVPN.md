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
docker-compose run --rm openvpn set_passphrase
```

Seguire la procedura inserendo la passphrase più opportuna tutte le volte che viene chiesta
(NOTA: in questa fase si sta decidendo la passphrase che poi sarà usata in futuro per creare le varie 
istanze dei client VPN che dovranno connettersi, quindi prenderne e salvarla sul Servizio Cloud)

Adesso possiamo creare il primo client

```
docker-compose exec openvpn add_client NOMECLIENT
```

con questo comando sarà creato un client verranno chiesti una chiave segrata per lo specifico client e
la passphrase inserita in fase di inizializzazionde del server necessaria affinche si dimostri di avere
i permessi di poter creare utenze in questo server.

Adesso possiamo generare il file `.ovpn` da inviare alla Workstastion o Server remoto che vorra raggiungere Myddleware.
Usare il seguente comando

```
docker-compose exec openvpn get_client NOMECLIENT > NOMECLIENT.ovpn
```

Consegnare il file appena creato e la chiava segreta dell'utente a chi opportuno.
