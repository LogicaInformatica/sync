# VIEW usata nella ricerca avanzata
CREATE OR REPLACE VIEW v_tipo_contratto
AS
SELECT 'LO' AS CodTipoContratto,'Loan' AS TitoloTipoContratto,true as Selected
UNION ALL
SELECT 'LE' AS CodTipoContratto,'Leasing' AS TitoloTipoContratto,true as Selected;