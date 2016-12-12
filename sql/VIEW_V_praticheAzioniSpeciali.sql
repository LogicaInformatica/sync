# vista usata per la generazione sia dei tab che dei contenuti della lista azioni da convalidare
create or replace view v_praticheAzioniSpeciali as
select
asp.IdAzioneSpeciale,asp.IdAzione,r.TitoloUfficio,ut.IdReparto,a.TitoloAzione,asp.IdContratto,c.CodContratto,
c.IdAgenzia,c.DataInizioAffido,C.DataFineAffido,ifnull(cl.Nominativo,cl.RagioneSociale) AS NomeCliente,
asp.IdUtente,ut.UserId as NominativoUtente,utn.UserId as NominativoApprovatore,u.Userid as UserSuper,
asp.IdApprovatore,asp.Stato,DATE_FORMAT(asp.DataScadenza,'%Y-%m-%d') AS DataScadenza,
CASE asp.Stato WHEN 'W' THEN 'da Convalidare' WHEN 'A' THEN 'Convalidata' WHEN 'R' THEN 'Respinta' ELSE 'Chiusa' END AS DescStato,
asp.Nota,asp.DataApprovazione,asp.DataEvento,c.ImpSaldoStralcio,c.DataSaldoStralcio,pr.PrimoImporto,
pr.DataPagPrimoImporto,pr.NumeroRate,pr.DecorrenzaRate,pr.ImportoRata,MAX(aasp.IdAllegato) AS IdAllegato,
IFNULL(op.NomeUtente,'(non assegnate)') AS NomeOperatore,
IF(asp.stato IN ('A','R'),asp.IdApprovatore,c.IdOperatore) AS IdOperatore,c.IdOperatore AS IdOperatoreContratto
from azionespeciale as asp
left join azione a on asp.IdAzione = a.IdAzione
left join contratto c on asp.IdContratto = c.IdContratto
left join cliente cl on c.IdCliente = cl.IdCliente
left join utente ut on ut.IdUtente = asp.IdUtente
left join utente utn on utn.IdUtente = asp.IdApprovatore
left join utente op  on op.IdUtente = IF(asp.stato IN ('A','R'),asp.IdApprovatore,c.IdOperatore)
left join reparto r on ut.IdReparto = r.IdReparto
left join storiarecupero sr on sr.IdAzioneSpeciale = asp.IdAzioneSpeciale
left join utente u ON sr.IdSuper=u.IdUtente
left join allegatoazionespeciale aasp on aasp.IdAzioneSpeciale = asp.IdAzioneSpeciale
left join pianorientro pr on pr.IdContratto = asp.IdContratto
group by
IdAzioneSpeciale,IdAzione,TitoloUfficio,IdReparto,TitoloAzione,IdContratto,CodContratto,NomeCliente,IdUtente,NominativoUtente,NominativoApprovatore,
UserSuper,IdApprovatore,Stato,asp.DataScadenza,DescStato,Nota,DataApprovazione,DataEvento;