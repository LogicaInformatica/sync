CREATE OR REPLACE VIEW v_ultimo_affidamento
AS
SELECT * from assegnazione a
WHERE CURDATE()<=DataFin AND IdAgenzia IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM assegnazione b where a.idContratto=b.IdContratto AND b.IdAssegnazione>a.IdAssegnazione)