CREATE OR REPLACE VIEW v_azioni_pratiche_dipendenti
AS
select IdFunzione,CodFunzione from funzione where idFunzione in(22,23,27,29,158)