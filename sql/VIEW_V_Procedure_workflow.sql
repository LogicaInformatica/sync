CREATE OR REPLACE ALGORITHM=MERGE VIEW v_procedure_workflow
AS
Select p.*, (select count(*) from azioneprocedura ap where ap.IdProcedura=p.IdProcedura) as numAzioni,
            (select count(distinct sap.IdStatoRecuperoSuccessivo) 
                from azioneprocedura apr 
                left join statoazione sap on(apr.IdAzione=sap.IdAzione) 
                left join statorecupero sr on(sap.IdStatoRecuperoSuccessivo=sr.IdStatoRecupero)
                where apr.IdProcedura=p.IdProcedura
                and sap.IdStatoRecuperoSuccessivo is not null
                and sr.CodStatoRecupero like 'WRK%') as numStati,
            case when p.DataFin>=date(now()) then 'Y' else 'N' end as Attiva
from procedura p