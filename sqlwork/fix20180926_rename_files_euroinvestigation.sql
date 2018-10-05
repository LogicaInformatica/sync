select UrlAllegato,LastUpd ,CONCAT('attachments/euroInvestigation/',DATE_FORMAT(LastUpd,'%Y%m%d'),'_', substr(urlallegato,31))
from allegato where substr(urlallegato,1,31)= 'attachments/euroInvestigation/L';

update
allegato
set UrlAllegato=CONCAT('attachments/euroInvestigation/',DATE_FORMAT(LastUpd,'%Y%m%d'),'_', substr(urlallegato,31))
 where substr(urlallegato,1,31)= 'attachments/euroInvestigation/L'
