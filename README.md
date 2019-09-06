# sync
Servizi di sincronizzazione tra App mobile e server

# Descrizione
Questo progetto contiene i componenti per i servizi di sincronizzazione tra un App mobile e un database su server, 
cos&igrave; come &egrave; stato realizzato per le applicazioni di tipo "content management" (ad es. IF Experience).
Non viene fornita una interfaccia REST a questi servizi, perch&eacute; le funzioni devono accedere anche al file system
del server applicativo (per le modifiche di tipo javascript e html).

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
AFTER DELETE, che provveda a scrivere su app_sql le istruzioni di DELETE necessarie. Vedi esempio MySql
nella cartella examples/sql di questo progetto.

Il programma lato server effettua due tipi di operazioni:

1. seleziona dalla tabella speciale (app_sql) i comandi SQL da eseguire e li fornisce nella risposta, in ordine di timestamp
(in questa tabella troverà anche le istruzioni DELETE corrispondenti alle cancellazioni fisiche intercettate mediante trigger)
2. seleziona da ogni tabella le righe con data di ultimo aggiornamento maggiore della data fornita e crea una istruzione SQL 
di tipo REPLACE per ciasuna riga. 

## Javascript: acquisizioni modifiche javascript
Si possono creare modifiche in linguaggio javascript (in particolare, definizioni modificate di funzioni js gia' esistenti nell'App)
collocandole in files all'interno della cartella dedicata (definita dalla costante SYNC_PATH). Ogni file pu&ograve; contenere
un qualsiasi numero di istruzioni Javascript.
I files vengono letti in ordine alfabetico ed "eseguiti" con l'istruzione "eval": il che significa che possono contenere qualsiasi
istruzione o definizione valida per javascript. Per essere certi che vengano eseguiti nell'ordine desiderato, conviene usare
una nomemenclatura del tipo 'YYYYMMDD_filexxxx.js', in modo che l'ordinamento alfabetico rispetti l'ordine temporale.

Sul lato App, il contenuto javascript viene conservato in localstorage ed eseguito ad ogni avvio dell'App, in modo che, anche
in assenza di connessione, le modifiche acquisite vengano applicate.

## HTML: acquisizioni modifiche HTML
Le modifiche a frammenti HTML possono essere collocate, con lo stesso nome file usato nell'App (in Framework7, il nome di un file
template HTML) all'interno della cartella dedicata (definita dalla costante SYNC_PATH, la stessa cartella dei file javascript).
Anch'esse, una volta acquisite dall'App, sono conservate in localstorage, per garantirne l'uso anche in assenza di connessione.


