
DROP DATABASE IF EXISTS MSSQL;

CREATE DATABASE MSSQL COLLATE Latin1_General_CS_AS;

USE MSSQL;

--
-- Anagrafica semplice
--
CREATE TABLE A_Persone (
    PersonID INT,
    LastName VARCHAR(255),
    FirstName VARCHAR(255),
    Email VARCHAR(255),
    City VARCHAR(255),
    IsDeleted BIT
);

INSERT INTO A_Persone (PersonID, LastName, FirstName, Email, City) VALUES (2682, 'Rossi', 'Mario', 'mario@rossi.it', 'Milano')

--
-- Gestione Prodotti/Listini
--
CREATE TABLE B_Prodotti (
    ProdottoID INT,
    Descrizione VARCHAR(255),
    Prezzo MONEY
)

INSERT INTO B_Prodotti (ProdottoID, Descrizione, Prezzo) VALUES (1, 'Zucchero', 10.5)

CREATE TABLE B_Listini (
    ListinoID INT,
    Nome VARCHAR(255),
)

INSERT INTO B_Listini (ListinoID, Nome) VALUES (1, 'Nuovi Clienti'), (2, 'Clienti Fedeli')

CREATE TABLE B_ListiniProdotti (
    ListinoID INT,
    ProdottoID INT,
    Prezzo MONEY
)

INSERT INTO B_ListiniProdotti (ListinoID, ProdottoID, Prezzo) VALUES (1, 1, 10), (2, 1, 9)
