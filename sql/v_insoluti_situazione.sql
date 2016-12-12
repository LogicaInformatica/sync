CREATE OR REPLACE VIEW v_insoluti_situazione 
AS SELECT 
	s.*,
	concat(p.CodProdotto,' ',TitoloProdotto) AS prodotto,co.CodContratto AS numPratica,
	ifnull(c.Nominativo,c.RagioneSociale) AS cliente,
	sc.CodStatoRecupero AS stato,sc.AbbrStatoRecupero,cl.CodClasse AS classif,
	CASE WHEN sc.CodStatoRecupero IN ('STR1','STR2') THEN 'STR'
		WHEN sc.CodStatoRecupero = 'LEG' THEN 'LEG'
		WHEN cl.IdClasse=19 THEN cl.AbbrClasse
		ELSE cl.AbbrClasse 
	END AS AbbrClasse,
	CASE WHEN s.CodRegolaProvvigione>'' THEN CONCAT(r.TitoloUfficio,' (',s.CodRegolaProvvigione,')') ELSE r.TitoloUfficio END AS agenzia,
	IFNULL(cat.TitoloCategoria,'Nessuna') AS Categoria,IFNULL(CodiceFiscale,PartitaIVA) AS CodiceFiscale,
	s.ImpCapitale+s.ImpDebitoResiduo AS CapitaleResiduo, ((s.PercSvalutazione/100)*s.ImpInsoluto) as Svalutazione,
	FasciaRecupero
from situazione s
join contratto co on co.IdContratto=s.IdContratto
join prodotto p on co.IdProdotto = p.IdProdotto
join cliente c on c.IdCliente = co.IdCliente
left join statorecupero sc on sc.IdStatoRecupero = s.IdStatoRecupero
left join classificazione cl on cl.IdClasse = s.IdClasse
left join reparto r on r.IdReparto = s.IdAgenzia
left join categoria cat on s.IdCategoria=cat.IdCategoria
left join regolaprovvigione rp on  rp.IdRegolaProvvigione = s.IdRegolaProvvigione
left join tipodettaglio td ON p.IdTipoDettaglio = td.IdTipoDettaglio
