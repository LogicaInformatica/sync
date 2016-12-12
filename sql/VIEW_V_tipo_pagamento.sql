#usata nella ricerca avanzata
CREATE OR REPLACE VIEW v_tipo_pagamento
AS
SELECT IdTipoPagamento,TitoloTipoPagamento,true as Selected
FROM tipopagamento;
