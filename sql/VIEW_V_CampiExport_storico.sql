# versione per Storico: attenzione al nome schema
CREATE OR REPLACE VIEW db_cnc_storico.v_campi_export
AS
select c.DescrBene as Modello,co.TitoloCompagnia AS Dealer, titoloFiliale as Filiale,c.DataContratto as DataLiquidazione,
c.ImpValoreBene as ValoreBene,c.ImpFinanziato as Finanziato,c.ImpAnticipo as Anticipo,c.ImpErogato as Erogato,
c.ImpRata as Rata, c.ImpRataFinale as RataFinale, c.ImpRiscatto as Riscatto, c.ImpInteressi as Interessi,
c.ImpSpeseIncasso as SpeseIncasso, c.ImpBollo as Bollo, c.PercTasso as Tasso, c.PercTaeg as Taeg, c.PercTassoReale as TassoReale,
c.NumRate as NumeroRate, c.ImpInteressiDilazione as InteressiDilazione,c.NumMesiDilazione as MesiDilazione,
case when c.IdStatoContratto=12 then 'In stato DBT'
     when c.IdClasse=17         then 'Classificato DBT'
     when c.IdStatoRecupero in(14,15,16,17) then sr.TitoloStatoRecupero
     else ''
end as StatoInDBT,c.IdContratto,c.ImpInsoluto as ImpRateInsoluto,(c.NumRate-c.NumInsoluti) as NumRatePagate
from db_cnc_storico.contratto c
left join statorecupero sr on(sr.IdStatoRecupero=c.IdStatoRecupero)
left join compagnia co on co.IdCompagnia=c.IdDealer
left join filiale f on c.idfiliale=f.idfiliale;