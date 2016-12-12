CREATE OR REPLACE VIEW v_experian_dettaglio
AS
SELECT cl.IdCliente,er.IdExperian,cl.CodCliente,OwnData,DataAnalisi,
    IFNULL(e.Nominativo,IFNULL(cl.RagioneSociale,cl.Nominativo)) AS Nominativo,
    cl.CodiceFiscale,D4CScore,D4CScoreIndex,TipoFinanziamento,MotivoFinanziamento,StatoPagamenti,
    ScadutoNonPagato, MesiDaUltimoProtesto, NumProtesti, ImportoTotaleProtesti,
    MesiDaUltimoDatoPubblico, MesiDaUltimoDataPregiudizievole, NumDatiPregiudizievoli, ImportoTotaleDatiPregiudizievoli,
    NumRichiesteCredito6mesi, ImpRichiesteCredito6mesi, NumRichiesteCredito3mesi, NumRichiesteAccettate6mesi,
    ImpRichiesteAccettate6mesi, NumRichiesteAccettate3mesi, ImpUltimaRichiestaFinanziata, PeggiorStatusSpeciale,
    NumContratti12mesi, NumContrattiAttivi, NumContrattiStatus0, NumContrattiStatus1_6,
    NumContrattiStatus1_3, NumContrattiStatus4_5, NumContrattiStatus6, NumContrattiPeggiorStatus0_2_12mesi,
    NumContrattiPeggiorStatus1_2_12mesi, NumContrattiPeggiorStatus3_5_12mesi, NumContrattiPeggiorStatus6_12mesi, NumContrattiDefault_12mesi,
    PeggiorStatus_1_12mesi, PeggiorStatus_6mesi, PeggiorStatus_7_12mesi, PeggiorStatusCorrente,
    NumContrattiEstinti, NumContrattiEstinti_6mesi, NumContrattiDefault, NumContrattiPerditaCessione,
    NumContrattiPerditaCessione_12mesi,   MesiDaUltimoContrattoStatus0_12mesi, MesiDaUltimoContrattoStatus3__6_12mesi, MesiDaUltimoContrattoDefault,
    TotaleImpScadutoNonPagato, TotaleImpScadutoNonPagato_Status1_2, TotaleImpScadutoNonPagato_Status3_5, TotaleImpScadutoNonPagato_Status6_8,
    TotaleSaldoInEssere, TotaleImpegnoMensile, NumContiRevolvingSaldoMinore75percento, RapportoMaxSaldoLimiteCredito,
    RapportoSaldoAutoSaldoTotale, RapportoMaxScadutoSaldo, NumPrestitiFinalizzati, NumPrestitiPersonali, 
    NumContiRevolving
FROM experianrichiesta er 
JOIN cliente cl ON cl.IdCliente=er.IdCliente
LEFT JOIN experian e ON e.IdExperian=er.IdExperian AND  e.IdCliente=er.IdCliente;


