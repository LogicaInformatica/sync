CREATE OR REPLACE VIEW v_dati_generali_writeoff AS
select c.IdContratto,
IFNULL(cli.Nominativo,cli.RagioneSociale) AS NomeCliente,
co.TitoloCompagnia AS dealer,
replace(replace(replace(format(IFNULL(c.ImpFinanziato,0),2),'.',';'),',','.'),';',',') as impFinanziato,
DATE_FORMAT(c.DataDecorrenza,'%d/%m/%Y') as dataLiquidazione,
DATE_FORMAT(c.DataDBT,'%d/%m/%Y') as dataPassConDBT,
CodContratto,
fi.TitoloFiliale as zona,
pr.TitoloProdotto as prodotto,
sc.TitoloStatoContratto as stato,
c.numRate as rateTot,
IF(wo.Flag1='Y','SI','NO') as c1,
IF(wo.Flag2='Y','SI','NO') as c2,
IF(wo.Flag3='Y','SI','NO') as c3,
IF(wo.Flag3a='Y','SI','NO') as c3a,
IF(wo.Flag4='Y','SI','NO') as c4,
IF(wo.Flag4a='Y','SI','NO') as c4a,
IF(wo.Flag5='Y','SI','NO') as c5,
IF(wo.Flag5a='Y','SI','NO') as c5a,
IF(wo.Flag5b='Y','SI','NO') as c5b,
IF(wo.Flag5c='Y','SI','NO') as c5c,
IF(wo.Flag6='Y','SI','NO') as c6,
IF(wo.Flag7='Y','SI','NO') as c7,
nota,nota2,nota3a,nota4a, nota5a,nota5b,nota5c,nota6,nota7,
importo3a, importo4a, importo5b,IFNULL(impSpeseLegali,0) AS speseLegali,
#dal 23/1/14, da S.Elena modificato in impDBT vero e proprio (i pagamenti avvenuti nel frattempo saranno imputati manualmente 
##nel form di WO
#IFNULL(wo.impDBT,c.impCapitale+c.ImpAltriAddebiti) AS impDBT, 
IFNULL(wo.impDBT,c.ImpDBT) AS impDBT, 
ifnull(wo.ImpRiscatto,c.ImpRiscatto) AS impRis,
IFNULL(impSval,0) AS impSval,IFNULL(ImpSvalLE,0) AS impSvalLE,IFNULL(percSval,0) AS percSval,IFNULL(percSvalLE,0) AS percSvalLE,
replace(replace(replace(format(wo.importo3a,2),'.',';'),',','.'),';',',') as importo3a_f,
replace(replace(replace(format(wo.importo4a,2),'.',';'),',','.'),';',',') as importo4a_f,
replace(replace(replace(format(wo.importo5b,2),'.',';'),',','.'),';',',') as importo5b_f,
replace(replace(replace(format(ifnull(wo.ImpRiscatto,c.ImpRiscatto),2),'.',';'),',','.'),';',',') as impRis_f,
ifnull(importo3a,0)+ifnull(importo4a,0)+ifnull(importo5b,0) AS impPdr,
replace(replace(replace(format(-ifnull(importo3a,0) -ifnull(importo4a,0) -ifnull(importo5b,0),2),'.',';'),',','.'),';',',') as impPdr_f,
-ifnull(importo3a,0) -ifnull(importo4a,0) -ifnull(importo5b,0) +coalesce(wo.impDBT,c.impDBT,0)
	+coalesce(impIntMora,ImpInteressiMoraAddebitati+c.ImpInteressiMora,0) +ifnull(impSpeseLegali,0) AS impPap,
replace(replace(replace(format(
	-ifnull(importo3a,0) -ifnull(importo4a,0) -ifnull(importo5b,0) +coalesce(wo.impDBT,c.ImpDBT,0)
	+coalesce(impIntMora,ImpInteressiMoraAddebitati+c.ImpInteressiMora,0) +ifnull(impSpeseLegali,0)
	,2),'.',';'),',','.'),';',',') as impPap_f,
#sostituito 11/12/12 impInteressiMoraAddebitati AS intMora
coalesce(impIntMora,ImpInteressiMoraAddebitati+c.ImpInteressiMora,0) as intMora,
replace(replace(replace(format(ifnull(impIntMora,ImpInteressiMoraAddebitati+c.ImpInteressiMora),2),'.',';'),',','.'),';',',') as intMora_f,
replace(replace(replace(format(impSpeseLegali,2),'.',';'),',','.'),';',',') as speseLeg_f,
replace(replace(replace(format(IFNULL(wo.impDBT,c.ImpDBT),2),'.',';'),',','.'),';',',') as impDBT_f,
replace(replace(replace(format(impSval,2),'.',';'),',','.'),';',',') as impSval_f,
replace(replace(replace(format(impSvalLE,2),'.',';'),',','.'),';',',') as impSvalLE_f,
replace(replace(replace(format(percSval,2),'.',';'),',','.'),';',',') as percSval_f,
replace(replace(replace(format(percSvalLE,2),'.',';'),',','.'),';',',') as percSvalLE_f
from contratto c
join cliente cli ON c.IdCliente=cli.IdCliente
LEFT join compagnia co on c.IdDealer=co.IdCompagnia
LEFT join filiale fi on fi.IdFiliale=c.IdFiliale
join prodotto pr on pr.IdProdotto=c.IdProdotto
join statocontratto sc on c.IdStatocontratto=sc.IdStatoContratto
LEFT join writeoff wo on c.IdContratto=wo.IdContratto;