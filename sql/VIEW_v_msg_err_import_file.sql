CREATE OR REPLACE  VIEW v_msg_err_import_file 
AS select m.IdImportLog AS IdLog,m.Message AS Messaggio,m.RecordKey AS Campo 
from importmessage m order by m.IdImportLog,m.RecordKey;