create or replace view v_prossima_scadenza (IdContratto,DataScadenzaAzione)
as
SELECT IdContratto,MIN(DataScadenza) AS DataScadenzaAzione FROM Nota WHERE DataScadenza>=CURDATE()
GROUP BY IdContratto