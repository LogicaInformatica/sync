CREATE OR REPLACE VIEW v_nonstarted 
AS
select distinct c.IdContratto from contratto c 
join insoluto i1 on i1.IdContratto=c.IdContratto AND i1.numRata>0 and i1.DataInsoluto < c.DataDecorrenza+INTERVAL 12 MONTH AND i1.ImpInsoluto>0
join insoluto i2 on i2.IdContratto=c.IdContratto AND i2.numRata=i1.NumRata+1 and i2.DataInsoluto < c.DataDecorrenza+INTERVAL 12 MONTH AND i2.ImpInsoluto>0
join insoluto i3 on i3.IdContratto=c.IdContratto AND i3.numRata=i2.NumRata+1 and i3.DataInsoluto < c.DataDecorrenza+INTERVAL 12 MONTH AND i3.ImpInsoluto>0
where (c.ImpInsoluto > 0 or c.IdStatoRecupero in (79,84)) and c.IdStatoContratto !=29
UNION 
select distinct c.IdContratto from contratto c 
where c.DataDBT < c.DataDecorrenza + INTERVAL 12 MONTH AND (c.ImpInsoluto > 0 or c.IdStatoRecupero in (79,84)) and c.IdStatoContratto !=29;
