# Myddleware with Docker

> This is work in progress documentation. (Last update: 2022-02-11)

## Requirements

To work with Docker on this project you need to have the following:

- GNU Make (to process Makefile)
- Docker (at least version 20)
- Docker Compose (at least version 1.27)

## Use Docker as a stand-alone production environment

Run the following commands

```shell
git clone https://github.com/myddleware/myddleware.git
cd myddleware
make install
make setup
make prod
```

Visit the following page than login with username `admin` and password `secret`

- <http://localhost:30080>

If you run Myddleware in a remote machine use instead of `localhost` use the public IP address.
