CREATE OR REPLACE VIEW v_messaggi_differiti AS
select cn.CodContratto AS CodContratto,
       ifnull(cl.Nominativo,cl.RagioneSociale) AS NomeCliente,
       a.TitoloAllegato AS TitoloAllegato,
       a.UrlAllegato AS UrlAllegato,
       m.IdModello as IdModello,
       m.IdContratto as IdContratto,
       (case m.Stato
       when 'E' then 'Elaborato'
       when 'C' then 'Creato'
       when 'N' then 'Errore'
       when 'S' then 'Sospeso' end) AS Stato,
       (case m.Tipo
       when 'E' then 'Email'
       when 'S' then 'Sms'
       when 'L' then 'Lettera' end) AS Tipo,
       m.DataCreazione AS DataCreazione,
       m.DataEmissione AS DataEmissione,
       m.TestoEsito AS TestoEsito,
       m.TestoMessaggio AS TestoMessaggio,
       m.IdMessaggioDifferito AS IdMessaggioDifferito
       from messaggiodifferito m  left join allegato a on m.IdAllegato = a.IdAllegato
                                  left join contratto cn on m.IdContratto = cn.IdContratto
                                  left join cliente cl on cl.Idcliente = cn.IdCliente;