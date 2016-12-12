CREATE OR REPLACE VIEW v_attorisucontratto AS
select c.IdContratto,
       c.IdOperatore,
       c.IdAgente,
       c.IdAgenzia,
       u.IdReparto
from contratto c left join utente u on c.IdOperatore=u.IdUtente;
