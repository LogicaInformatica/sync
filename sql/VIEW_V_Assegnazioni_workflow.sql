CREATE OR REPLACE ALGORITHM=MERGE VIEW v_assegnazioni_workflow
AS
SELECT re.*,
(SELECT count(*) FROM regolaprovvigione rp where rp.IdReparto=re.IdReparto) as NumTipAff,
(SELECT count(*) FROM regolaassegnazione rass where rass.tipoassegnazione=2 and rass.IdReparto=re.IdReparto) as NumRegAff,
(SELECT count(*) FROM regolaassegnazione rasse where rasse.tipoassegnazione=3 and rasse.IdReparto=re.IdReparto) as NumRegAffOpe
FROM reparto re where re.IdTipoReparto>1