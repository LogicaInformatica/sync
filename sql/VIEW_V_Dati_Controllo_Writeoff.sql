#
# OBSOLETA
#


CREATE OR REPLACE VIEW v_dati_controllo_writeoff AS
select c.IdContratto,
IF(EXISTS (select 1 from storiarecupero sr where IdAzione=400 and sr.IdContratto=c.IdContratto),'SI','NO') AS c1, # esiste la Relazione intervento

CASE WHEN EXISTS (select 1 from storiarecupero sr where IdAzione=99 and sr.IdContratto=c.IdContratto) # esiste l'azione Fallimento
     THEN 'SI' ELSE 'NO' END AS c2,

(SELECT notaEvento from storiarecupero sr where sr.IdContratto=c.IdContratto and idAzione=99 LIMIT 1)
 as note2, # nota azione fallimento

CASE WHEN IdProdotto IN (162,165) THEN 'SI' # piano di rientro come contratto
     WHEN EXISTS (SELECT 1 FROM pianorientro pr WHERE pr.IdContratto=c.IdContratto) THEN 'SI' # piano di rientro CNC
     ELSE 'NO' END AS c3, #  non so se è una rinegoziazione

CASE WHEN IdProdotto IN (162,165) # piano di rientro come contratto
     	THEN ifnull(c.ImpFinanziato,0) - ifnull(c.ImpCapitale,0)
     ELSE (SELECT SUM(ImpPagato) FROM ratapiano rp JOIN pianorientro pr ON rp.IdPianoRientro=pr.IdPianoRientro WHERE pr.IdContratto=c.IdContratto)
END as importo3a, # quanto ha versato del piano di rientro

CASE WHEN IdProdotto IN (162,165) AND c.ImpCapitale < 26 THEN 'SI'
     WHEN EXISTS (SELECT 1 FROM pianorientro pr WHERE pr.IdContratto=c.IdContratto)
     	AND (SELECT SUM(Importo)-SUM(ImpPagato) FROM ratapiano rp JOIN pianorientro pr ON rp.IdPianoRientro=pr.IdPianoRientro WHERE pr.IdContratto=c.IdContratto)<26 THEN 'SI'
     ELSE 'NO' END as c3a, # se ha rispettato il piano

CASE WHEN ImpSaldoStralcio>0 THEN 'SI' ELSE NULL END AS c4,

CASE WHEN ImpSaldoStralcio>0 AND EXISTS
			(SELECT 1 FROM movimento mo JOIN tipopartita tp ON mo.IdTipoPartita=tp.IdTipoPartita and CategoriaPartita='C'
			 WHERE Importo<0 AND mo.DataRegistrazione > c.DataDBT AND mo.IdContratto=c.IdContratto AND numRata=0) # incasso capitale su rata zero dopo dbt
	      THEN 'SI'
	 WHEN ImpSaldoStralcio>0 THEN 'NO' #saldo e stralcio senza incasso
	 ELSE '  ' END AS c4a, # se ha fatto il pagamento

CASE WHEN ImpSaldoStralcio>0
		THEN (SELECT SUM(importo) FROM movimento mo JOIN tipopartita tp ON mo.IdTipoPartita=tp.IdTipoPartita and CategoriaPartita='C'
			 WHERE mo.DataRegistrazione > c.DataDBT AND mo.IdContratto=c.IdContratto AND numRata=0) # incasso capitale su rata zero dopo dbt
	 ELSE NULL END AS importo4a, # importo pagato su saldo e stralcio

(select notaEvento from storiarecupero sr where IdAzione in (344,345,347,374,375,377) and sr.IdContratto=c.IdContratto LIMIT 1)
as note4a, # nota autorizz. saldo e stralcio

CASE WHEN EXISTS (select 1 from storiarecupero sr where IdAzione=327 and sr.IdContratto=c.IdContratto) #  vendita ripossessato
       OR c.IdAttributo IN (SELECT IdAttributo FROM attributo WHERE CodAttributo='LE RE') # attributo ripossesso leasing
       OR c.IdCategoria IN (SELECT IdCategoria FROM categoria WHERE CodCategoria='RE') # categoria ripossesso
     THEN 'SI'
     ELSE 'NO' END as c5, # se è un ripossesso

CASE WHEN EXISTS (select 1 from storiarecupero sr where IdAzione=312 and sr.IdContratto=c.IdContratto) # perdita di possesso
     	THEN 'SI'
     ELSE 'NO' END as c5a, #se è perdita di possesso

(select notaEvento from storiarecupero sr where IdAzione=312 and sr.IdContratto=c.IdContratto LIMIT 1)
as note5a # nota perdita di possesso

from contratto c
join statocontratto sc on c.IdStatocontratto=sc.IdStatoContratto
LEFT join writeoff wo ON wo.IdContratto=c.IdContratto;
