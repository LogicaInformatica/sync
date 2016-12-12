#
# Subview usata nella view sottostante
#
create or replace view v_check_08_subview
as
SELECT IdProvvigione,SUM(ImpCapitaleAffidato) AS CapitaleAffidato,SUM(ImpTotaleAffidato) AS TotaleAffidato,
             SUM(ImpPagato) AS Pagato,COUNT(*) AS NumAffidati
      FROM v_dettaglio_provvigioni v
      where datafineAffido>=CURDATE()-INTERVAL 4 DAY
      GROUP BY IdProvvigione;

#
# Controllo congruenza tra sintesi e dettaglio provvigioni
#
create or replace view v_check_08
as
select p.datafin as DataFineAffido,p.idprovvigione,codRegolaProvvigione,p.NumAffidati AS NumAffidatiSintesi,x.NumAffidati AS NumAffidatiDettaglio,
	   IF(p.NumAffidati!=x.NumAffidati,p.NumAffidati-x.NumAffidati,NULL) AS DiffAffidati,
       p.ImpCapitaleAffidato AS CapitaleAffidatoSintesi,x.CapitaleAffidato AS CapitaleAffidatoDettaglio,
       IF(p.ImpCapitaleAffidato!=x.CapitaleAffidato,p.ImpCapitaleAffidato-x.CapitaleAffidato,NULL) AS DiffCapitale,
       p.ImpCapitaleAffidato+p.ImpAltroAffidato AS TotaleAffidatoSintesi,x.TotaleAffidato AS TotaleAffidatoDettaglio,
       IF(p.ImpCapitaleAffidato+p.ImpAltroAffidato!=x.TotaleAffidato,p.ImpCapitaleAffidato+p.ImpAltroAffidato-x.TotaleAffidato,NULL) AS DiffTotale,
       p.ImpCapitaleRealeIncassato AS PagatoSintesi,x.Pagato AS PagatoDettaglio,
       IF(p.ImpCapitaleRealeIncassato!=x.Pagato,p.ImpCapitaleRealeIncassato-x.Pagato,NULL) AS DiffPagato
from provvigione p
JOIN regolaprovvigione rp ON p.IdRegolaProvvigione=rp.IdRegolaProvvigione
join v_check_08_subview x ON p.IdProvvigione=x.IdProvvigione
where p.datafin>=CURDATE()-INTERVAL 4 DAY
and (p.NumAffidati!=x.NumAffidati OR p.ImpCapitaleAffidato!=x.CapitaleAffidato OR p.ImpCapitaleAffidato+p.ImpAltroAffidato!=x.TotaleAffidato
     OR p.ImpCapitaleRealeIncassato!=x.Pagato)
order by 1,2
;

select * from v_check_08
