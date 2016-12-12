CREATE OR REPLACE VIEW v_procedure_azioni_statoazione
AS
select a.IdAzione as IdAzione,a.TitoloAzione as TitoloAzione,sa.IdStatoAzione as IdStatoAzione,
sa.Condizione as Condizione,
sr.AbbrStatoRecupero as AbbrStatoRecupero,
sa.IdClasseSuccessiva as IdClasseSuccessiva,cl.AbbrClasse as ClassSucc,
sa.IdStatoRecuperoSuccessivo as IdStatoRecuperoSuccessivo, srs.AbbrStatoRecupero as StatRecSucc,
pr.IdProcedura as IdProcedura,sa.DataIni as DataIni,sa.DataFin as DataFin
from ((((azione a 
left join Statoazione sa on(a.IdAzione=sa.IdAzione))
left join StatoRecupero sr on(sr.IdStatoRecupero=sa.IdStatoRecupero))
left join StatoRecupero srs on(srs.IdStatoRecupero=sa.IdStatoRecuperoSuccessivo))
left join classificazione cl on(cl.IdClasse=sa.IdClasseSuccessiva))
left join AzioneProcedura pr on(pr.IdAzione=a.IdAzione)
