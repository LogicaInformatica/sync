# sync
Servizi di sincronizzazione tra App mobile e server

# Descrizione
Questo progetto contiene i files di definizione del servizio di sincronizzazione tra un App mobile e un database su server, 
cos&igrave; come &egrave; stato realizzato per le applicazioni di tipo "content management" (ad es. IF Experience).

# Funzionamento
Per ora (Settembre 2019) si gestisce solo una sincornizzazione da server ad App e non viceversa. L'App chiama il server 
(vedi componenti js di esempio nella cartella examples/js) fornendo una "data di ultimo aggiornamento" e una eventuale condizione applicativa di filtro (ad es. se l'App deve acquisire solo dati relativi ad un certo "contesto"). Il server risponde con tre possibili insiemi di informazioni:

1. istruzioni SQL compatibili con SQLite
2. contenuti javascript
3. contenuti HTML

Ciascuna dei tre casi e' spiegato nel seguito.

## SQL: acquisizione da server delle modifiche effettuate sul DB e applicazione delle stesse su DB SQLite dell'App
Il procedimento &egrave; cos&igrave; concepito:

1. l'App possiede un database SQLite, in cui tutte le tabella sincronizzabili con il server hanno nome prefissato in modo 
convenzionale (ad es. sync_) e corrispondono a view o tabelle con lo stesso nome presenti sul DB del server e aventi tipi 
di colonne compatibili (comunque, considerare che in SQLite tutte le colonne possono essere trattate/considerate come fossero di tipo TEXT).

2. Le tabelle possiedono tutte una chiave primaria come prima colonna (ad es. la solita colonna autoincrement) e un campo 
DATETIME o TIMESTAMP ad aggiornamento automatico (per default chiamato LastUpd), in modo che si possieda una data certa di ultimo aggiornamento. Ad es. in MySql
il campo potrebbe essere definito come:

```
    LastUpd TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
```

3. Il DB del server contiene una tabella speciale (per default "app_sql") che contiene istruzioni SQL estemporanee che 
devono essere eseguite sul DB SQLite, anch'esse contraddistinte da un timestamp.

4. Se si vogliono gestire le cancellazioni fisiche di record (cio&egrave; si vogliono replicare su SQLite anche le cancellazioni
che avvengono sulle tabelle corrispondenti del database del server), si deve definire per ciascuna tabella un trigger di tipo
AFTER DELETE, che provveda a scrivere su app_sql le istruzioni di DELETE necessarie. Vedi esempio nella cartella examples/sql di questo
progetto.

Il programma lato server effettua tre tipi di operazioni:

1. seleziona dalla tabella speciale (app_sql) i comandi SQL da eseguire e li fornisce nella risposta, in ordine di timestamp
2. seleziona da ogni tabella le righe con data di ultimo aggiornamento maggiore della data fornita e crea una istruzione SQL 
di tipo REPLACE per ciasuna riga. 
3. per ogni tabella crea una lista completa di tutti gli ID che la tabella contiene...
