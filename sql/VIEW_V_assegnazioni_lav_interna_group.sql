create or replace view v_assegnazioni_lav_interna_group
as
select  IdContratto,DataInizioAffido,DataFineAffido,SUM(Debito) AS Debito,SUM(Pagato) AS Pagato
FROM v_assegnazioni_lav_interna
GROUP BY IdContratto,DataInizioAffido,DataFineAffido;