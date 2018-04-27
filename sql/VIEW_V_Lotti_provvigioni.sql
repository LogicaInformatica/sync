#
# Lista delle coppie regola provvigione + lotto per le quali esiste qualche assegnazione
#
# nel caso dello stragiudiziale e legale, la chiusura provvigioni e' mensile, 
# quindi visualizza tutti i lotti in corso come se scadessero al prossimo fine mese
#
# Dal 10/12/2012, viene creata anche una riga per ogni lotto STR/LEG vero e proprio (arrotondato a fine mese, perch� LEG prevede
# date fine affido qualsiasi). Tale riga serve per la lista provvigioni STR/LEG organizzata per lotto anzich� per mese
# di chiusura intermedio
#
# Dal 5/5/14, genera righe tipoCalcolo=X che sono uguali al tipo C, ma limitate dalla data max visibilit� STR
# (cioe' sono usate per la lista visibile alle agenzie)
#
# ==> DAL 2018-04-29 include anche i LEGALI
create or replace view v_lotti_provvigioni
AS
-- TIPI N (normale) e C (STR/LEG chiusura mensile)
select distinct 1 as ordine,a.IdAgenzia,r.idregolaprovvigione as IdRegola,
IF (FasciaRecupero LIKE 'DBT%' OR FasciaRecupero LIKE '%REPO%',
	DATE_FORMAT( LEAST(a.DataFin,curdate()) ,"%M %Y"),
	CONCAT('Fino al ',DATE_FORMAT(a.DataFin,'%d/%m/%y'))) AS Lotto,
IF (FasciaRecupero LIKE 'DBT%' OR FasciaRecupero LIKE '%REPO%',
	LAST_DAY(LEAST(a.DataFin,curdate())),a.DataFin) as DataFineAffido,
IF (FasciaRecupero LIKE 'DBT%' OR FasciaRecupero LIKE '%REPO%' ,'C','N') AS TipoCalcolo
,r.CodRegolaProvvigione
FROM regolaprovvigione r
JOIN assegnazione a ON a.idregolaprovvigione=r.idregolaprovvigione
                       OR a.idAgenzia=r.idReparto AND a.dataini<=r.datafin AND a.datafin>=r.dataini
where a.IdAgenzia>0 AND FasciaRecupero!='RINE' /*AND FasciaRecupero!='LEGALE' */

UNION ALL

-- TIPO X - provvigioni STR/LEG con chiusura periodica mensile (visibilita' limitata alle agenzie)
select distinct 2 as ordine,a.IdAgenzia,r.idregolaprovvigione as IdRegola,
	DATE_FORMAT( LEAST(a.DataFin,curdate()),"%M %Y") AS Lotto,
	LAST_DAY(LEAST(a.DataFin,curdate())) as DataFineAffido,
	'X' AS TipoCalcolo,r.CodRegolaProvvigione
FROM regolaprovvigione r
JOIN assegnazione a ON a.idregolaprovvigione=r.idregolaprovvigione
                       OR a.idAgenzia=r.idReparto AND a.dataini<=r.datafin AND a.datafin>=r.dataini
where a.IdAgenzia>0 AND (FasciaRecupero LIKE 'DBT%' OR FasciaRecupero LIKE '%REPO%')

-- TIPO M - provvigioni STR per lotto arrotondato al mese
UNION ALL
select distinct 3 as ordine, a.IdAgenzia,r.idregolaprovvigione as IdRegola,
	CONCAT('Fino al ',DATE_FORMAT(LAST_DAY(a.DataFineAffidoContratto),'%d/%m/%y')) AS Lotto,
	LAST_DAY(a.DataFineAffidoContratto) as DataFineAffido,'M' AS TipoCalcolo,r.CodRegolaProvvigione
FROM regolaprovvigione r
JOIN assegnazione a ON a.idRegolaProvvigione=r.idRegolaProvvigione  AND r.DataFin>=LAST_DAY(a.DataFineAffidoContratto)
WHERE a.IdAgenzia>0 AND (FasciaRecupero LIKE 'DBT%' OR FasciaRecupero LIKE '%REPO%')

-- TIPO R - provvigioni per rinegoziazione
UNION ALL
select distinct 4 as ordine, a.IdAgenzia,r.idregolaprovvigione as IdRegola,
	DATE_FORMAT(LAST_DAY(a.DataFin),'%M %Y') AS Lotto,
	LAST_DAY(a.DataFin) as DataFineAffido,'R' AS TipoCalcolo,r.CodRegolaProvvigione
FROM regolaprovvigione r
JOIN assegnazione a ON a.idregolaprovvigione=r.idregolaprovvigione
			 AND a.dataini<=r.datafin AND a.datafin>=r.dataini
WHERE a.IdAgenzia>0 AND FasciaRecupero='RINE'
order by ordine;