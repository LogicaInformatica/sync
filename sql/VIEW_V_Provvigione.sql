CREATE OR REPLACE VIEW v_provvigione
AS
SELECT p.*,p.DataFin AS DataFineAffido,
CASE TipoCalcolo 
	WHEN 'C' THEN DATE_FORMAT(LEAST(p.DataFin, LAST_DAY(CURDATE())) ,"%M %Y") # per stragiudiziale a chiusura mensile attribuisce al mese corrente se data fine futura
	WHEN 'X' THEN DATE_FORMAT(LEAST(p.DataFin, LAST_DAY(CURDATE())) ,"%M %Y") # riga come tipo C, limitata alle pratiche visibili all'agenzia
	WHEN 'R' THEN DATE_FORMAT(p.DataFin ,"%M %Y") # rinegoziazione (mensili a fine mese)
	ELSE CONCAT('Fino al ',DATE_FORMAT(p.DataFin,'%d/%m'),IF(YEAR(CURDATE())!=YEAR(p.DataFin),CONCAT('/',YEAR(p.DataFin)),''))
END AS Lotto,
CASE WHEN r.DataFin='9999-12-31' OR rNew.IdRegolaProvvigione IS NULL or p.TipoCalcolo='M' THEN r.CodRegolaProvvigione ELSE CONCAT(r.CodRegolaProvvigione,' old') END AS CodRegolaProvvigione,
           TitoloUfficio AS Agenzia,CASE StatoProvvigione WHEN 0 THEN 'In corso' WHEN 1 THEN 'Completo' ELSE 'Consolidato' END AS Stato,
           CASE WHEN NumAffidati=0 THEN 0 ELSE ROUND(NumIncassati*100.0/NumAffidati,2) END AS IPM,
           CASE WHEN ImpCapitaleAffidato=0 THEN 0 ELSE ROUND(ImpCapitaleIncassato*100.0/ImpCapitaleAffidato,2) END AS IPF,
           CASE WHEN ImpCapitaleAffidato=0 THEN 0 ELSE ROUND(ImpCapitaleRealeIncassato*100.0/ImpCapitaleAffidato,2) END AS IPR,
           DATE_FORMAT(p.LastUpd,'%d/%m %H:%i') AS UltimaElaborazione,
           IFNULL(AbbrFasciaProvvigione,r.AbbrRegolaProvvigione) AS DescrFormula,r.ordine,
           CASE WHEN r.FasciaRecupero LIKE 'DBT%' OR r.FasciaRecupero LIKE '%REPO%' THEN 2 
             WHEN r.FasciaRecupero = 'LEGALE' then 3
             WHEN r.FasciaRecupero = 'RINE' then 4
             ELSE 1 END AS TipoProvvigione
FROM provvigione p
JOIN regolaprovvigione r ON r.IdRegolaProvvigione=p.IdRegolaProvvigione
LEFT JOIN regolaprovvigione rNew ON rNew.CodRegolaProvvigione=r.CodRegolaProvvigione AND rNew.DataFin>r.DataFin AND p.DataFin>=rNew.DataIni
LEFT JOIN fasciaprovvigione f ON f.IdRegolaProvvigione=p.IdRegolaProvvigione AND p.ValoreSoglia=f.ValoreSoglia AND p.DataFin BETWEEN f.DataIni AND f.DataFin
JOIN reparto a ON a.IdReparto=p.IdReparto
ORDER BY r.Ordine,rNew.Ordine