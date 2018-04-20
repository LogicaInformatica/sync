# Sono nonstarted tutte le pratiche che hanno avuto tre insoluti consecutivi entro i primi 12 mesi e che attualmente sono ancora aperte oppure
# passata in cessione / writeoff
CREATE OR REPLACE VIEW v_nonstarted 
AS
select distinct c.IdContratto from contratto c 
join insoluto i1 on i1.IdContratto=c.IdContratto AND i1.numRata>0 and i1.DataInsoluto < c.DataDecorrenza+INTERVAL 12 MONTH AND i1.ImpDebitoIniziale>0
join insoluto i2 on i2.IdContratto=c.IdContratto AND i2.numRata=i1.NumRata+1 and i2.DataInsoluto < c.DataDecorrenza+INTERVAL 12 MONTH AND i2.ImpDebitoIniziale>0
join insoluto i3 on i3.IdContratto=c.IdContratto AND i3.numRata=i2.NumRata+1 and i3.DataInsoluto < c.DataDecorrenza+INTERVAL 12 MONTH AND i3.ImpDebitoIniziale>0
where (c.ImpInsoluto > 26 or c.IdStatoRecupero in (79,84)) 
;

select * from v_nonstarted;
