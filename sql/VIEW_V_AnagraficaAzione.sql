CREATE OR REPLACE VIEW v_anagrafica_azione
AS
SELECT a.IdAzione,a.IdFunzione,p.IdProcedura,a.CodAzione,a.CodAzioneLegacy,a.TitoloAzione,f.TitoloFunzione,
p.TitoloProcedura,a.TipoFormAzione,a.FlagMultipla,
case 
when a.FlagSpeciale='Y' then a.FlagSpeciale
else 'N' end as FlagSpeciale,
a.FlagAllegato,
case 
when a.FlagAllegato='Y' then 'Obbligatorio'
when a.FlagAllegato='N' then 'Non obbligatorio'
when a.FlagAllegato is null then 'Assente'
else a.FlagAllegato
end as FlagAllegatoDesc,
a.FormWidth,a.FormHeight,a.GiorniEvasione,
a.LastUpd,a.LastUser
FROM 
azione a 
left join funzione f on(a.IdFunzione=f.IdFunzione) 
left join (azioneprocedura ap 
            left join procedura p on(ap.IdProcedura=p.IdProcedura)) 
        on(a.IdAzione=ap.IdAzione);