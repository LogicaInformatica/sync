CREATE OR REPLACE VIEW v_tipomovimento_decodifica
AS
SELECT IdTipoMovimento,CodTipoMovimento,TitoloTipoMovimento,CodTipoMovimentoLegacy,CategoriaMovimento,
case
when CategoriaMovimento='C' then 'addebito rata (capitale)'
when CategoriaMovimento='X' then 'insoluto RID'
when CategoriaMovimento='S' then 'storno'
when CategoriaMovimento='P' then 'pagamento'
when CategoriaMovimento='I' then 'movimento relatvo agli interessi di mora' 
when CategoriaMovimento='R' then 'movimento relatvo alle spese di recupero'
when CategoriaMovimento='A' then 'annullamento'
when CategoriaMovimento is null then 'Nessuna' end as Categoria
FROM tipomovimento;