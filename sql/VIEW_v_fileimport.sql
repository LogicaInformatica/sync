CREATE OR REPLACE VIEW v_fileimport AS select IdImportLog AS IdImportLog,
IdCompagnia AS IdCompagnia,ImportTime AS ImportTime,
FileType AS FileType,FileId AS FileId,
(case ImportResult when 'U' then 'Ok' when 'K' then 'Fallito' end)  AS ImportResult,
 (case Status when 'P' then 'Processato' when 'R' then 'In corso' when 'C' then 'Confermato' when 'N' then 'Non processato' end) AS Status,
 lastupd AS lastupd,Message AS Message 
 from importlog
 order by lastupd desc;