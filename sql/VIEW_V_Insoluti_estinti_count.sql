CREATE OR REPLACE ALGORITHM=MERGE VIEW v_insoluti_estinti_count
AS
select co.*
from contratto co
where co.impinsoluto>26 AND co.idstatocontratto in (2, 3, 5, 14, 17, 22, 24)
AND (LEFT(CodContratto,2)='LO' OR co.IdAttributo IN (63,68,71,80,82,84,88)) ## vedi mail Federica Cerrato del 27/9/13 
 AND idcontrattoderivato is null AND DataChiusura<=CURDATE()
and co.IdClasse!=19 #esclude quelli messi in EXIT
;
