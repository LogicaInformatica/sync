<?php

/****************************** COSTANTI **************************************/
define("KPAGEOK", "main.php");
define("KPAGEKO", "exit.page.php");

//Debug
//0 - Debug non attivo
//1 - Debug Attito, senza supporto di rete
//2 - Debug attivo, con rete (Consigliato)
define("KDEBUG", 0);

//URL relativo del Bridge Fin-Portal
define('URL_REMOTE_ANA', '/php/r_bridge_gtw.php');

define("ROW_SEP", "|R|");
define("FIELD_SEP", "|F|");
define("SECTION_SEP","||||");
define("SEP","||");

//Costanti che indicano la correttezza della comunizazione
define("KCorrectRet", 101);
define("KCorrectMsgRet", 'tutto ok');

//Array degli IURL permessi per contattare Fin-Portal
$GATrustURL= array( 'www.fin-portal.svil' ,
                    'www.fin-portal.labit',
                    'www.fin-portal.test' ,
                    'www.fin-portal.prod' ,
                    'portallabit.tfsi.it',
                    'portaltest.tfsi.it' ,
                    'portal.tfsi.it'     ,
                    "portal".get_cfg_var('EB_DNS_SUFFIX').".".get_cfg_var('EB_DNS_DOMAIN')
                  );

/******************************* FUNZIONI *************************************/

//Restituisce l' UserAgent usato per contattare Fin-Portl
function geu_getUserAgent(){
    $WUserAgent = 'overall';

    return $WUserAgent;
}
//------------------------------------------------------------------------------
//Restituisce Url Assoluta di Fin-Portal
//function geu_getRemoreAnaUrl(){
//    $WAnaCentral= $_SESSION['SUserData']['SUrlPortal'] . URL_REMOTE_ANA;
//
//    $WAnaCentral= ( ($WAnaCentral) ? $WAnaCentral : 'https://portal.tfsi.it/php/r_bridge_gtw.php') ;
//    return $WAnaCentral;
//}
function geu_getRemoreAnaUrl(){
    if(defined("GATEWAY_ENVIRONMENT") && (GATEWAY_ENVIRONMENT==True)){
        $WAnaCentral= URL_REMOTE_ANA_FOR_GATEWAY;
    }
    else{
        if(!empty($_SESSION['SUserData']['SUrlPortal']))
            $WAnaCentral= $_SESSION['SUserData']['SUrlPortal'] . URL_REMOTE_ANA;
        }

    $WAnaCentral= ( ($WAnaCentral) ? $WAnaCentral : get_cfg_var('EB_WEB_PROTOCOL').'://portal'.get_cfg_var('EB_DNS_SUFFIX').".".get_cfg_var('EB_DNS_DOMAIN').'/php/r_bridge_gtw.php') ;
    return $WAnaCentral;
}

//------------------------------------------------------------------------------
//Restituisce il path dove saranno situati i LOG
function geu_getPathLog(){
    return "log";
}
//------------------------------------------------------------------------------
//Controlla se un URL, appartiene alla lista dei 'Sicuri'
function geu_isTrustURL($IUrl){
    $WbaseUrl= $IUrl;

    //Se è presente la / finale, la elimina
    if (substr($WbaseUrl, -1)=='/') {
        $WbaseUrl = substr($WbaseUrl, 0, strlen($WbaseUrl)-1);
    }

    //Toglie eventuali specificatori di protocolli, http[s]://
    $AUrl= explode('://', $WbaseUrl);
    $WbaseUrl= ( isset($AUrl[1])? $AUrl[1] : $AUrl[0]);

    $WbaseUrl= strtolower($WbaseUrl);
    global $GATrustURL;

    //Controlla che sia contenuto nell'array dei siti Trust
    $WFind= array_search($WbaseUrl, $GATrustURL);

    if($WFind!==FALSE)
        return 1;
    else
        return 0;
}
//------------------------------------------------------------------------------
//Scrive un stringa in un file, con possibilta di accodamento
function geu_stingTofile($IFullNameFile, $IStr, $Ifile_append = 0){

    //Controlla prima che esista la directory
    $WPath= dirname($IFullNameFile);
    if(!is_dir($WPath)){
       return false;
    }

    //In caso non si sia in PHP 5
    if(!function_exists('file_put_contents')) {
        define('FILE_APPEND', 1);
        function file_put_contents($filename, $data, $file_append = 0) {
            $fp = fopen($filename, (!$file_append ? 'w+' : 'a+'));
            if(!$fp) {
                  trigger_error('file_put_contents cannot write in file.', E_USER_ERROR);
                  return 0;
            }
            fwrite($fp, $data);
            fclose($fp);
            return 1 ;
        }
    }
    if($Ifile_append)
        $Ifile_append=FILE_APPEND;
    return file_put_contents($IFullNameFile , $IStr, $Ifile_append);

}
//------------------------------------------------------------------------------
function geu_appendToLog($INomeFile, $ITxt){
    return geu_stingTofile($INomeFile, "\n\n*LOG ".date("d/m/Y - H:i:s") ."\n\n" .$ITxt, 1);
}
/*------------------------------------------------------------------------------
    geu_trasport
------------------------------------------------------------------------------*/
function geu_trasport($IProperties){

  //------------------Paramatri-------------------------------------------------
  //Indica se fare il debug: 0 Spento, 1: Debug ma senza connessione fisica, 2 Debug con connessione fisicica
  $WDebug       = KDEBUG;
  //URL
  $WUrl         = empty($IProperties["Url"])? '' :  $IProperties["Url"];
  //Indica se fare uso di un Proxy http
  $WProxy       = empty($IProperties["Proxy"])?  false :  $IProperties["Proxy"];
  //Indica a curl di forza https, anche se url è http
  $ForceHttps   = empty($IProperties["ForceHttps"]) ? '' : $IProperties["ForceHttps"];
  //Specifica lo UserAgent per la connessione
  $WUserAgent   = empty($IProperties["UserAgent"])? '' : $IProperties["UserAgent"];
  //Specifica il Referer per la connessione
  $WReferer     = empty($IProperties["Referer"])  ? '' : $IProperties["Referer"];
  //Timeout nella connessione http
  $WTimeOut     = empty($IProperties["Timeout"])  ? 40 : (int) $IProperties["Timeout"];
  //Indica di fare un redir se viene incontrato
  $WLocation    = empty($IProperties["Location"]) ? 1  : $IProperties["Location"];
  //Numero massimo di redirs che curl effettuera
  $WMaxRedirs   = empty($IProperties["MaxRedirs"])? 5  : (int) $IProperties["MaxRedirs"];
  //----------------------------------------------------------------------------

  //Controlla se c'è il proxy
  /*if($WProxy){
    //Se è stata specificata una porta distinta dal proxy
    if(!empty($WProxy["Port"]))
        $WProxyFull= $WProxy["Url"] .":". $WProxy["Port"];
    else
        $WProxyFull= $WProxy["Url"];
  }*/

  //Controlla se è HTTPS dall' URL
  $WIsHttps = true;//(strtolower(substr($WUrl ,0 ,5))=='https') || $ForceHttps ;


  //Prepara i Valori POST
  define('FCId', 'GVP=F-P1');
  $WData= FCId;

  $WTmpData= $IProperties["Data"] ;
  $WCount= count($WTmpData);
  for ($i = 0; $i < $WCount; $i++) {
    $WData.= '&' . $WTmpData[$i]["Name"] ."=". urlencode($WTmpData[$i]["Value"]);
  }

  if ($WDebug>0){
    if(function_exists("geu_getPathLog"))
        $WPathLog= geu_getPathLog() ."/";
    else
        $WPathLog= "../";
    $WNameLog= date("Ymd") . "_Curl.log";
    $WDataLog= "Url: ".  $WUrl . "\n". "Data: ". $WData;
    $WDataLog.= "\nCURL:";
    $WDataLog.= "\n\$ch = curl_init()";
    $WDataLog.= "\ncurl_setopt(\$ch, CURLOPT_URL, '{$WUrl}' );";
    $WDataLog.= "\ncurl_setopt(\$ch, CURLOPT_HEADER, 0);";
    $WDataLog.= "\ncurl_setopt(\$ch, CURLOPT_POST, 1)";
    $WDataLog.= "\ncurl_setopt(\$ch, CURLOPT_RETURNTRANSFER,1);";
    $WDataLog.= "\ncurl_setopt(\$ch, CURLOPT_POSTFIELDS, '{$WData}');";
    $WDataLog.= "\ncurl_setopt(\$ch, CURLOPT_USERAGENT, '{$WUserAgent}');";
    $WDataLog.= "\ncurl_setopt(\$ch, CURLOPT_CONNECTTIMEOUT, '{$WTimeOut}');";

    //Se è stato chiesto di inviare il Referer
    if($WReferer==1)
        $WDataLog.= "\ncurl_setopt(\$ch, CURLOPT_REFERER, '{$_SERVER['HTTP_HOST']}');";

    //Se è attivo il proxy
    if((!empty($WProxyFull))&&($WProxyFull==1)){
     $WDataLog.= "\ncurl_setopt(\$ch, CURLOPT_TIMEOUT, '{$WProxyTimeOut}' );";
     $WDataLog.= "\ncurl_setopt(\$ch, CURLOPT_PROXY, '{$WProxyFull}');";
    }
    if ((!empty($WIsHttps)) &&  ($WIsHttps==1)){
       $WDataLog.= "\ncurl_setopt(\$ch, CURLOPT_SSL_VERIFYPEER, 0);";
       $WDataLog.= "\ncurl_setopt(\$ch, CURLOPT_SSL_VERIFYHOST, 0);";
    }

    if($WLocation){
     $WDataLog.= "\ncurl_setopt(\$ch, CURLOPT_FOLLOWLOCATION, {$WLocation} );";
     $WDataLog.= "\ncurl_setopt(\$ch, CURLOPT_MAXREDIRS, {$WMaxRedirs});";
    }
    $WDataLog.= "\n\$result = curl_exec (\$ch);";
    $WDataLog.= "\n\$intReturnCode = curl_getinfo(\$ch , CURLINFO_HTTP_CODE);";
    $WDataLog.= "\n\$EffectiveUrl = curl_getinfo(\$ch , CURLINFO_EFFECTIVE_URL);";

    $WDataLog.= "\ncurl_close (\$ch);\n\n";

    if($WDebug==1){
        $WResult=  geu_appendToLog($WPathLog .$WNameLog, $WDataLog );
        return array( 'Result'      => "",
                      'ReturnCode'  => 200,
                      'EffectiveUrl'=> $WUrl
                    );
    }
   }
//------------FINE  DEBUG

   $ch = curl_init();
   curl_setopt($ch, CURLOPT_URL, $WUrl );
   curl_setopt($ch, CURLOPT_HEADER, 0);
   curl_setopt($ch, CURLOPT_POST, 1);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
   curl_setopt($ch, CURLOPT_POSTFIELDS, $WData);
   curl_setopt($ch, CURLOPT_USERAGENT, $WUserAgent);
   curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $WTimeOut);
   if($WReferer==1)
       curl_setopt($ch, CURLOPT_REFERER, $_SERVER["HTTP_HOST"]);
   //Se è attivo il proxy
    if((!empty($WProxyFull))&&($WProxyFull==1)){
    curl_setopt($ch, CURLOPT_TIMEOUT, $WProxyTimeOut );
    curl_setopt($ch, CURLOPT_PROXY, $WProxyFull);
   }
   if ((!empty($WIsHttps)) &&  ($WIsHttps==1)){
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
       curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
   }
   if($WLocation){
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $WLocation);
        curl_setopt($ch, CURLOPT_MAXREDIRS, $WMaxRedirs);
   }
   $result = curl_exec ($ch);
   $WLastErrorId= curl_errno($ch);

   //Errore di comunicazione
   if($WLastErrorId){
    $WDataLog.= "\n\nErrore durante la comunicazione:";
    $WDataLog.= "\nErrore ID: {$WLastErrorId}";
    $WDataLog.= "\nErrore Descrizione: ". curl_error($ch) . "\n\n";

   }

   $intReturnCode = curl_getinfo($ch , CURLINFO_HTTP_CODE);
   $EffectiveUrl  = curl_getinfo($ch , CURLINFO_EFFECTIVE_URL);


   curl_close ($ch);



   if($WDebug==2){
        $WDataLog.= "Result: $result";
        $WResult=  geu_appendToLog($WPathLog .$WNameLog, $WDataLog );
   }

   return array( 'Result'      => $result,
                 'ReturnCode'  => (int) $intReturnCode,
                 'EffectiveUrl'=> $EffectiveUrl
               );
}
//------------------------------------------------------------------------------
function geu_communicator($IProperties){
	if(stristr( $IProperties['Url'], 'portal') !== FALSE) {
		if (strtolower(substr($IProperties['Url'] , 0, 5)) =='https' ) {
			$IProperties['Url'] = 'http' . substr($IProperties['Url'], 5);
		}
	}
	
    //Contatatta il gateway remoto
    $WResult= geu_trasport($IProperties);
    //Controlla che URL sia quello desiderato e non sia stato cambiato
    if ($WResult['EffectiveUrl']!=$IProperties['Url']){
        return array( "LowRetCode"  => 800,
                      "LowMsgRet"  => 'Effective ulr not valid',
                      "RetCode"     => '',
                      "MsgReturn"   => $IProperties['Url'],
                      "Msg"         => ''
                    );
    }

    //Controlla che il Retuen Code sia 2XX
    if($WResult['ReturnCode']!=200){
        //Errore avvenuto durante la comunicazione
        return array( "LowRetCode"  => $WResult['ReturnCode'],
                      "LowMsgRet"  => 'Errore di comunicazione',
                      "RetCode"     => '',
                      "MsgReturn"   => $WResult['EffectiveUrl'],
                      "Msg"         => ''
                    );

    }

    if((empty($IProperties['OldStyle'])) || ($IProperties['OldStyle']!=1)){
      //Tutto Ok con la comunicazione a basso livello
      $Wbfr       = unserialize($WResult['Result']);
      $WGwRetCode =  $Wbfr['RetCode'];

      $WGmRetMsg  = $Wbfr['MsgRet'];

      //Message Return
      $WData      = $Wbfr['Data'];
    }else{
        //Tutto Ok con la comunicazione a basso livello
        $Wbfr       = explode(ROW_SEP , $WResult['Result']);
        if($Wbfr[0]){
            $Wbfr2  = explode(FIELD_SEP , $Wbfr[0]);

            $WGwRetCode =  trim($Wbfr2[0])+'';

            $WGmRetMsg  =  $Wbfr2[1];

            unset($Wbfr[0]);
            //Message Return
            $WData      =  implode(ROW_SEP,  $Wbfr);

        }

    }
    return array( "LowRetCode"  => (int) $WResult['ReturnCode'],
                  "LowMsgRet"   => '',
                  "RetCode"     => $WGwRetCode,
                  "MsgReturn"   => $WGmRetMsg,
                  "Msg"         => $WData
                );

}
//------------------------------------------------------------------------------
function gen_initSessionRemote($IUpdTimeSession=1, $IOtherParms= false){
    //***************************************************************************
    //Controlla che la sessione PHP sia ancora valida
    //Errore: 11
    if  (!((int) $_SESSION["SUserData"]["Sute_biideute"])>0  )
        return array(
                  'LowRetCode'    => 200,
                  'RetCode'       => 11,
                  'MsgReturn'     => 'Session non valida',
                  'Esito'         => 0
                 );
    //***************************************************************************
    //Controlla che sessione esista!
    //Errore: 13
    if (!$_SESSION["SUserData"]["Sute_biideute"])
        return array(
                  'LowRetCode'    => 200,
                  'RetCode'       => 13,
                  'MsgReturn'     => 'Sessione non valida',
                  'Esito'         => 0
                 );

    //Continua i controlli in Remoto
    return gen_checkRemoreSession($IUpdTimeSession, $IOtherParms);
}
//------------------------------------------------------------------------------
//Controlla se una sessione sia valida, contattando una ANA gentrale
function gen_checkRemoreSession($IUpdTimeSession=1, $IOtherParms= false){

    $WAnaCentral= geu_getRemoreAnaUrl();
    $WUserAgent = geu_getUserAgent();

    $WCorrectId  = KCorrectRet;
    $WCorrectMsg = KCorrectMsgRet;

    //Indica se aggiornare il timer della sessione
    if($IUpdTimeSession)
        $WFun= 1;
    else
        $WFun= 2;

    //Ora ricontatta ANA gentrale, per controllare che sia tutto o.k.
    $IDebug=0;
    $Properties= array(
                       "Url"        => $WAnaCentral ,
                       "Debug"      => $IDebug,
                       "UserAgent"  => $WUserAgent,
                       "Data"       => array(
                                            array("Name"  => "FFun",
                                                  "Value" => $WFun),
                                            array("Name"  => "FData",
                                                  "Value" => $_SESSION["SUserData"]["Sute_vcidesescor"] )
                                            )
                    );
    if($IOtherParms){
        $Properties['Data']   = array_merge($Properties['Data'], $IOtherParms);
        $Properties['Referer']= 1;
    }

    $WEsito= geu_communicator($Properties);
    $WResult = array();
    $WResult = $WEsito;
    $WResult['Esito'] = 0;
    //Controlla che sia andato tutto bene, e la risposta sia positiva
    if(($WEsito["LowRetCode"]==200) &&($WEsito["RetCode"]==$WCorrectId) && ($WEsito["MsgReturn"]==$WCorrectMsg)){
        $WResult['Esito']=1;
    }

    return $WResult;
}
//------------------------------------------------------------------------------
//Restituisce il ServerName, nella forma http[s]://SERVER[:PORT]
//Non restituisce la / finale
function geu_getServerName(){
    $zzztemp = explode('/', $_SERVER['SERVER_PROTOCOL']);
    $protocol = strtolower(array_shift($zzztemp));
    if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') {
        $protocol = "https";
    }

    $WHost= (empty($_SERVER['HTTP_X_FORWARDED_HOST'])) ? $_SERVER['HTTP_HOST']: $_SERVER['HTTP_X_FORWARDED_HOST'];
    $baseUrl = $protocol . '://' . $WHost;
    if (substr($baseUrl, -1)=='/') {
        $baseUrl = substr($baseUrl, 0, strlen($baseUrl)-1);
    }

    if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT']!='' && $_SERVER['SERVER_PORT']!='80' && $_SERVER['SERVER_PORT']!='443') {
        $baseUrl .= ':'.$_SERVER['SERVER_PORT'];
    }
    return $baseUrl;
}

//User e Password necessari per ottenere accesso ANA attraverso il gateway di Desires
//In fin-portal deve esistere un utenza gateway con questi dati, altrimenti la funzione
// non negato accesso al DB
define('KGT_USER', 'cso_gtw_prod');
define('KGT_PASS', 'cso_gtw_prod09');
//URL assoluto di fin-portal, da usare per le funzioni gateway(cio柳enza sessione PHP)
define('URL_REMOTE_ANA_FOR_GATEWAY', 'http://portal.tfsi.it/php/r_bridge_gtw.php');

//Contatta l'ANA centrale e resistuisce AC_CODE dei clienti
function getAC_CODEForGateway($IUser='', $IPass=''){
    define('GATEWAY_ENVIRONMENT', True);
    //Se non sono stati passati i dati di login, usa quelli di default
    if(empty($IUser))
        $IUser=KGT_USER;
    if(empty($IPass))
        $IPass=KGT_PASS;

    $WAnaCentral= geu_getRemoreAnaUrl();

    $WUserAgent = geu_getUserAgent();

    $WCorrectId  = KCorrectRet;
    $WCorrectMsg = KCorrectMsgRet;

    $WFun= 201;
    //Ora ricontatta ANA gentrale,
    $Properties= array(
                       "Url"        => $WAnaCentral ,
                       "Debug"      => KDEBUG,
                       "UserAgent"  => $WUserAgent,
                       "Data"       => array(
                                            array("Name"  => "FFun",
                                                  "Value" => $WFun),
                                            array("Name"  => "FUser",
                                                  "Value" => $IUser ),
                                            array("Name"  => "FPass",
                                                  "Value" => $IPass)
                                            )
                    );
	//print_r($Properties);
	//exit;
    if($IOtherParms){
        $Properties['Data']= array_merge($Properties['Data'], $IOtherParms);
    }
	
    $WEsito= geu_communicator($Properties);
    //print_r($WEsito);
    //exit;
    $WResult = array();
    $WResult = $WEsito;
    $WResult['Esito'] = 0;
   
    //Controlla che sia andato tutto bene, e la risposta sia positiva
    if(($WEsito["LowRetCode"]==200) &&($WEsito["RetCode"]==$WCorrectId) && ($WEsito["MsgReturn"]==$WCorrectMsg)){
         $WResult= $WResult['Msg'];
    }else
        $WResult= false;

    return $WResult;
}
?>
