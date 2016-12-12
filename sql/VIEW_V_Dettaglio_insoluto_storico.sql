# ATTENZIONE AL NOME SCHEMA
CREATE or replace view db_cnc_storico.v_dettaglio_insoluto
as
select c.IdContratto,c.IdOperatore,c.IdAgenzia,c.idagente,
## Capitale
sum(IF(i.ImpAltriAddebiti IS NOT NULL,i.ImpCapitale-i.ImpPagato,
       case when (i.numrata!=0 and i.impPagato<=i.impCapitale) then LEAST(i.impCapitale-i.impPagato,impDebitoIniziale)
            when (i.numrata=0 or i.impCapitale=0 or i.impcapitale<=i.imppagato and i.impinsoluto>0) THEN 0
            else i.ImpInsoluto
       end
      )
    ) as Capitale, #capitale ancora da pagare
## Interessi di mora (somma il maturato, che è nel contratto, con l'eventuale addebito, preso dalle righe di insoluto (in realtà dalla rata n.0))
case when ifnull(rr.flagInteressimora,rrn.flaginteressimora) = 'Y' then c.impinteressimora+c.impInteressiMoraAddebitati
     else 0
end as InteressiMora,
## Altri Addebiti
sum(IF(i.ImpAltriAddebiti IS NOT NULL,i.ImpAltriAddebiti,
       case when (i.numrata=0 or i.impCapitale=0 or i.impcapitale<=i.imppagato and i.impinsoluto>0) then i.impinsoluto
 			when i.NumRata!=0 and i.ImpCapitale>=i.ImpPagato and i.ImpCapitale-i.ImpPagato<i.ImpInsoluto then i.ImpInsoluto-(i.ImpCapitale-i.ImpPagato)
       		else 0
       end
      )
   ) as AltriAddebiti,
## Spese di recupero (moltiplica la percentuale per il capitale se applicabile)
## dal 10/7/2012 aggiunge anche le spese addebitate esplicitamente
GREATEST(0,SUM(i.ImpSpeseRecupero))+
CASE WHEN rr.IdRegolaRipartizione IS NOT NULL
	   THEN IFNULL(rr.impspeseincasso,
	      		round(rr.percspeseincasso
	      			*sum(IF(i.ImpAltriAddebiti IS NOT NULL,i.ImpCapitaleAffidato,
	      				case when (i.numrata!=0 and i.impPagato<=i.impCapitale) 
	      				then impDebitoIniziale else 0 end))/100,2)
	      		)
     WHEN rrn.IdRegolaRipartizione IS NOT NULL and c.IdAgenzia IS NULL
     THEN IFNULL(rrn.impspeseincasso,
	      		round(rrn.percspeseincasso
	      			*sum(IF(i.ImpAltriAddebiti IS NOT NULL,i.ImpCapitaleAffidato,
	      				case when (i.numrata!=0 and i.impPagato<=i.impCapitale) then
	      					impDebitoIniziale else 0 end))/100,2)
	      		)
     ELSE 0
END as Speseincasso,c.IdClasse,c.datacambiostato,c.idstatorecupero,c.lastupd as lastupd_contratto,
MIN(IF(i.ImpCapitale>0 AND i.ImpInsoluto>5,i.NumRata,NULL)) AS NumRata,
sum(case when i.ImpCapitale>0 AND i.ImpInsoluto>5 then 1 else 0 end) AS NumInsoluti,
sum(i.ImpDebitoIniziale)-sum(i.ImpInsoluto) as ImpPagato,
MIN(IF(i.ImpCapitale>0 AND i.ImpInsoluto>5,i.DataInsoluto,NULL)) as DataRata
from db_cnc_storico.contratto c
left join db_cnc_storico.insoluto i on i.idContratto=c.idContratto
left join regolaprovvigione rp ON rp.IdRegolaProvvigione=c.IdRegolaProvvigione
left join regolaripartizione rr on rr.Idregolaprovvigione=rp.IdRegolaProvvigione AND IFNULL(c.DataFineAffido,CURDATE()) BETWEEN rr.DataIni AND rr.DataFin
left join regolaripartizione rrn on rrn.idclasse=c.idclasse and rrn.idreparto is null AND IFNULL(c.DataFineAffido,CURDATE()) BETWEEN rrn.DataIni AND rrn.DataFin
group by c.idcontratto;