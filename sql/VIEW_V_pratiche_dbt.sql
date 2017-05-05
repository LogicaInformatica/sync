#
# vista usata per l'export excel speciale nel workflow DBT
#
CREATE OR REPLACE VIEW v_pratiche_dbt
AS
select c.IdContratto,CodContratto AS numPratica,c.IdCliente,cl.CodCliente,
ImpCapitale+ImpDebitoResiduo AS ImpInsoluto,IFNULL(Nominativo,RagioneSociale) AS Cliente,TitoloCompagnia AS Dealer,c.IdStatoRecupero,
concat(p.CodProdotto,' ',TitoloProdotto) AS Prodotto,TitoloRegione AS Regione,AbbrStatoRecupero as Stato,
ap.TitoloAgenzia AS AgenziaProx,DataVendita,DataCambioStato AS DataStato,sto.NotaEvento AS Nota,Garanzie
FROM contratto c
JOIN cliente cl ON c.IdCliente=cl.IdCliente
join prodotto p on c.IdProdotto = p.IdProdotto
join statorecupero sr ON sr.IdStatoRecupero=c.IdStatoRecupero
LEFT JOIN compagnia co ON c.IdDealer=co.IdCompagnia
LEFT JOIN v_indirizzo_principale ind ON ind.IdCliente=c.IdCliente
LEFT JOIN assegnazione a on a.idContratto=c.idContratto AND a.DataFin>=CURDATE()
LEFT JOIN regolaprovvigione rp ON c.CodRegolaProvvigione=rp.CodRegolaProvvigione AND rp.DataFin>CURDATE()
LEFT JOIN v_agenzia_provv_plus ap ON ap.IdRegolaProvvigione = CASE WHEN c.IdAgenzia IS NULL THEN rp.IdRegolaProvvigione ELSE a.IdAffidoForzato END
LEFT JOIN storiarecupero sto ON sto.IdContratto=c.IdContratto AND sto.IdAzione IN (129,130,131,132,134,136,138)
LEFT JOIN storiarecupero x ON x.IdContratto=c.IdContratto AND x.IdAzione IN (129,130,131,132,134,136,138) AND x.IdStoriaRecupero>sto.IdStoriaRecupero
WHERE x.IdStoriaRecupero IS NULL AND c.IdStatoRecupero BETWEEN 14 AND 17
;