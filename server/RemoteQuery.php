<?/*====================================================================================================================='  Remote Query Communication Page'  php 5 version - Requirements: libxml extension enabled (as usual)''  Copyright 2010 Logica Informatica srl'                 viale della Tecnica, 205'                 00144 Roma RM'=====================================================================================================================' I M P O R T A N T : this version only supports MySql databases. The configuration.xml enry needed is' 					  similar to the following:''                     <database name="myDBLogicalName" schema="myDbPhysicalname" type="MySQL" updatable="true"'                                connectionstring="Password=mypassword;User ID=myuserid;Data Source=myserver"/>'' or, in case when user must login each time:''                     <database name="myDBLogicalName" schema="myDbPhysicalname" type="MySQL" updatable="true"'                                asklogin="true"'                                connectionstring="Data Source=myserver"/>''=====================================================================================================================' Change log:'' 2010, Aug 11st   created'=====================================================================================================================' This page receives requests from the mobile device and returns data caught from databases' that are allowed for the given device and are accessible from the server where this page ' is run. A configuration file is used to define the database names and connection strings' in addition to user profiles.'====================================================================================================================='  F U N C T I O N S    (the function code is passed through the F argument in the query string''                       COMMON NOTE 1: if an error occurs, the output has always the form:'                                      !mmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmm'                                    i.e. an exclamation point followed by the error message''                       COMMON NOTE 2: all elements of a result are coded base64, with fields'                                      separated by commas and lines separated by semicolon.''                       COMMON NOTE 3: from version 2.0 on, all responses are prefixed with a base64 encoded'                                      string containing the version number (e.g. "V=2.0")''---------------------------------------------------------------------------------------------------------------------'  DBLIST               Returns a list of logical names of all databases allowed for the given'                       user, with a type associated with each of them. '            Arguments: UDID=   mobile device identifier. '            Output:    a list of database types, names, asklogin flag, updatable flag (see common note 2)'                       Types can be: Oracle - SQLServer - MySQL - MSAccess - SQLite '                       The two flags are returned as Y/N                   '---------------------------------------------------------------------------------------------------------------------'  CONNECT             	Try to connect to a given Database, for verifying the provided userid and password'                       (and the proper user profile)'            Arguments: UDID=   mobile device identifier. '                       DB  =   DB name '                       L   =   userid and password (mandatory if the db is marked with asklogin=true)'                       Userid and password are received (mixed and scrambled) in the U parameter'            Output:    'Y' if connection succeeded'                       standard error message, otherwise (i.e. msg preceded by !)'---------------------------------------------------------------------------------------------------------------------'  OBJLIST             	Returns a list of logical objects for the given database'            Arguments: UDID=   mobile device identifier. '                       DB  =   DB name '                       L   =   userid and password (mandatory if the db is marked with asklogin=true)'                       Userid and password are received (mixed and scrambled) in the U parameter'            Output:    a list of object types, object owner/schema and object names. '                       (see common note 2); type can be T=table, V=view (at now).'---------------------------------------------------------------------------------------------------------------------'  GETKEY             	Returns info for the record unique identification on a given table or view'            Arguments: UDID=   mobile device identifier. '                       DB  =   DB name '                       L   =   userid and password (scrambled, mandatory if the db is marked with asklogin=true)'						OBJ =   table or view (full qualified name)'            Output:    three strings (base64 encoded) separated by comma'						1) an SQL expression to add to the SELECT column list, for having a unique '                          row identifier.'                       2) an SQL expression to use in the WHERE clause of an UPDATE/DELETE, for'                          selecting the record having a given unique identification; the expression will'                          contain as many parameter markers ("?") as the fields in the first list '                       3) a list of as many character as the number of fields in the first and second list'                          where each character indicates the corresponding field generic type (s,n,d or b, for'                          string, number, date or other)'            Examples:  (note that fields are base64 encoded, here shown as if they were not encoded)'                       Oracle:  ROWIDTOCHAR(ROWID),ROWID=CHARTOROWID(?),s'                       SQLite:  ROWID,ROWID=?,n'                       Others:  pk1,pk2,pk3,pk1=? AND pk2=? AND pk3=?;...'                                (being pk1,pk2 and pk3 the names of three fields composing the table primary key)     '---------------------------------------------------------------------------------------------------------------------'  QUERY             	Returns a list of row resulting from a given SQL query'            Arguments: UDID=   mobile device identifier. '                       DB  =   DB name '                       CMD =   SELECT statement'                       LIMIT=  max number of returned rows'                       OFFSET= offset of the first row returned (starting from 0)'                       L   =   userid and password (scrambled, mandatory if the db is marked with asklogin=true)'                       MAXL=   maximum value length to be returned (used for avoiding that very long'                               text fields are fully returned; the default value is 500)'                       PARAMS= list of values corresponding to the placeholders (question marks) in '                               a parameterized query. Each value is built by concatenation of a data type (s/n/d)'                               and a value. Values are separated by "tab" (ASCII 9)'            Output:    lines separated by semicolons:'                       1. the first line contains the total number of records (null if unknown,'                          "U" followed by a number if the command was not a select: in such a case the number is the nr. of'                          affected records)'                       2. the second line contains a sequence of column names, types and lengths (all separated'						   by commas and base64 encoded, as in common note 2).'                          The data type is expressed as:  "s" (string), "n" (number), "d" (date), "t" (time),'                          "b" (not editable, e.g. blob)'                       3. Lines from 3rd on contains the table/query data, consisting of rows separated by semicolon'                          each row consists of columns, base64 encoded and separated by commas: each colomn has a starting character'                          flag (n=null; t=truncatedM; blank=normal) followed by the column value.'---------------------------------------------------------------------------------------------------------------------'  EXEC             	Execute a command (UPDATE,DELETE,INSERT with version 2.0)'            Arguments: UDID=   mobile device identifier. '                       DB  =   DB name '                       CMD =   SQL statement'                       L   =   userid and password (scrambled, mandatory if the db is marked with asklogin=true)'                       PARAMS= list of values corresponding to the placeholders (question marks) in '                               a parameterized command. Each value is built by concatenation of a data type (s/n/d)'                               a null indicatore (n or blank) and a value. Values are separated by "tab" (ASCII 9)'            Output:    "U" followed by the number of affected records (comma separated and base64 encoded as usual)'            NOTE:      When the iPhone user enter an Update/Insert/Delete command manually in the query panel,'                       the QUERY function is called, not the EXEC function'=====================================================================================================================*/$serverVersion  = base64_encode("V=2.0").";"; //Prefix for all responses$dom 		= NULL;$updatable 	= FALSE;$conn       = FALSE;$catalogquery = "";$dbtype = "";$schema = "";$verb = "";$maxlen = 500;$timezone = "";set_time_limit(1200); // aumenta il tempo max di cpu// ATTENTION: enable the following flag only if the current php page is allowed to write onto the//            same folder where it stays. Look at the last function in this file to change the output directory, if needed$traceOn = FALSE;  // WRITE TRACE TO FILE //-------------------------------------------------------------------------------------//  Get parameters and authenticate //-------------------------------------------------------------------------------------TraceToFile("Incoming request: ".print_r($_GET,true));$func = $_GET["F"].$_GET["f"];$version = $_GET["V"].$_GET["v"];if ($version == "")	$version = "1.0";$udid = $_GET["UDID"].$_GET["udid"];if ($udid == "")	die("!Request format error: no UDID specified");//-------------------------------------------------------------------------------------//  Read the user profile //-------------------------------------------------------------------------------------$dbList = getUserDBlist($udid);  // check the user id and retrieve the authorized dbListif ($dbList==""){	TraceToFile("User not authorized, the current UDID is not listed among the authorized users in the configuration.xml file");	die("!User not authorized, the current UDID is not listed among the authorized users in the configuration.xml file");}else	TraceToFile("Database list = $dbList");if ($dbList=="*")  // user authorized to all db, retrieve the real list{	$dbs = $dom->getElementsByTagName("database");	$dbList = "";	foreach($dbs as $db)		$dbList .= ",".$db->getAttribute("name");	$dbList = substr($dbList,1);}$dbList = split(",",$dbList); // transform the list into an array//-------------------------------------------------------------------------------------//  Call the selected function //-------------------------------------------------------------------------------------switch (strtoupper($func)){	case "DBLIST":		doDBList();		break;	case "CONNECT":		if (connectTo($_GET["DB"]))			if ($version == "1.0")				die("Y"); // version 1.0 used to return an unencoded "Y" only			else				die($serverVersion); // version after 1.0 returns the encoded version prefix		break;	case "OBJLIST":		if (connectTo($_GET["DB"]))			doObjList();		break; 	case "GETKEY":		if (connectTo($_GET["DB"]))			if ($_GET["OBJ"] == "")			{				TraceToFile("Missing table/view name");				die("!Missing table/view name");			}			else				doGetKey($_GET["OBJ"]);		break;	case "QUERY": 		if (!connectTo($_GET["DB"]))			die();		$cmd = trim($_GET["CMD"]);		if (isCommandSupported($cmd))		{			$limit = $_GET["LIMIT"];			if ($limit=="")				$limit = 100; // defaul max num of rows			else if (!is_numeric($limit))			{				TraceToFile("The LIMIT parameter must be numeric");				die("!The LIMIT parameter must be numeric");			}		}		else		{			$limit = (int)$limit;			if ($limit==0)				$limit = 100;		}		$offset = $_GET["OFFSET"];		if ($offset=="")			$offset = 0;		else if (!is_numeric($offset))		{			TraceToFile("The OFFSET parameter must be numeric");			die("!The OFFSET parameter must be numeric");		}		else			$offset = (int)$offset;		$maxlen = $_GET["MAXL"];		if ($maxlen=="")			$maxlen = 500; // 500 bytes is the default maximum length		else if (!is_numeric($maxlen))		{			TraceToFile("The MAXL parameter must be numeric");			die("!The MAXL parameter must be numeric");		}		else			$maxlen = (int)$maxlen;				doCommand($cmd,$limit,$offset,$maxlen,$_GET["PARAMS"]);	    break;	case "EXEC": 		if (connectTo($_GET["DB"]))		{			$cmd = trim($_GET["CMD"]);			if (isCommandSupported($cmd))				doCommand($cmd,"","","",$_GET["PARAMS"]);	    }	    break;	default:		TraceToFile("Unknown function code ($func)");		die("!Unknown function code ($func)");}//=====================================================================================//  doDBList //  Return the answer to a DBLIST request, composing a list with the authorized DB only,//  each one preceded by its dbms type//=====================================================================================function doDBList(){	global $dom,$serverVersion,$dbList;		echo $serverVersion;  // output begins wth the version number	$dbs = $dom->getElementsByTagName("database");	foreach ($dbs as $db)	{		$name = $db->getAttribute("name");		if (in_array($name,$dbList))		{			$typ = $db->getAttribute("type");			$ask = $db->getAttribute("asklogin");			if ($ask>"")				if ($ask) 					$ask = "Y";				else					$ask = "N";			else				$ask = "N";			$upd = $db->getAttribute("updatable");			if ($upd>"")				if ($upd) 					$upd = "Y";				else					$upd = "N";			else				$upd = "N";			echo base64_encode($typ).",".base64_encode($name).",".base64_encode($ask).",".base64_encode($upd).";";		}	}	die();}//=====================================================================================//  connectTo //  Find a db definition in the configuration file and open a connection to it//=====================================================================================function connectTo($dbname){	global $dbList,$dom,$updatable,$conn,$dbtype,$schema,$catalogquery,$timezone;		// Check whether the db is listed among the db's the user is authorized to	if (!in_array($dbname,$dbList))	{		TraceToFile("Database '$dbname' not defined or user not allowed to it");		die("!Database '$dbname' not defined or user not allowed to it");	}	
	$dbs = $dom->getElementsByTagName("database");
	foreach ($dbs as $db)
	{
		$name = $db->getAttribute("name");
		if ($name==$dbname)
		{
			$cs = $db->getAttribute("connectionstring");
			$dbtype = str_replace(" ","",strtoupper($db->getAttribute("type"))); 
			
			if (strtoupper($dbtype)!="MYSQL")			// TEMPORARY
			{
				TraceToFile("Unsupported DB type");
				die("!Unsupported DB type");
			}
			$schema = $db->getAttribute("schema");
			$catalogquery = $db->getAttribute("query");
			$ask  = $db->getAttribute("asklogin");
			$timezone = $db->getAttribute("timezone"); 
			if ($ask>"")
			{
				if ($ask) 
				{
					if ($_GET["L"]=="")
					{
						TraceToFile("User name and password not specified for connecting to database '$dbname'");
						die("!User name and password not specified for connecting to database '$dbname$'");
					}
					// content is hidden by using a double base64 encoding
					try
					{
						$login = base64_decode($_GET["L"]);
						$login = base64_decode($login);
						$couple  = split("/",$login."/");
						$user  = $couple[0];
						$password = $couple[1];
					}	
					catch (Exception $ex)
					{
						$user = "";
						$password = "";
					}
					if ($user=="" || $password=="")
					{
						TraceToFile("Invalid user ID and/or password");
						die("!Invalid user ID and/or password");
					}
					// if user and password are embedded in the connection string, substitute them
					$cs = str_replace("%user",$user,$cs);
					$cs = str_replace("%password",$password,$cs);
				}
			}
		
			$upd = $db->getAttribute("updatable");
			if ($upd>"")
				$updatable = ($upd!=FALSE);  // remember for following operations
			else
				$updatable = FALSE;			 // remember for following operations

			// CONNECTION: only for MySql
			try 
			{
				// Extract the Password, User ID and Data Source value from the connection string
				$server = "localhost";
				$parts = split(";",$cs);
				foreach($parts as $part) // read all items in the connection strng
				{
					$couple = split("=",$part); // separate key and value
					if (count($couple)==2)
					{
						if (strtoupper(trim($couple[0]))=="PASSWORD")
							$password = trim($couple[1]); 
						else if (strtoupper(trim($couple[0]))=="USER ID")
							$user = trim($couple[1]); 
						else if (strtoupper(trim($couple[0]))=="DATA SOURCE")
							$server = trim($couple[1]);
					} 
				}
				
				$conn = openMySqlDb($server,$schema,$user,$password);
				// imposta il timezone
				if ($timezone>"")
				{
					if (!$res=mysql_query("SET time_zone='$timezone'",$conn))
					{
						TraceToFile(mysql_error($conn));
						die ("!".mysql_error($conn));
					}
					TraceToFile("timezone set to: $timezone");
				}	
				return TRUE;
			}
			catch (Exception $ex)
			{	
				TraceToFile($ex->getMessage());
				die("!".$ex->getMessage());
			}
		}
	
	}
	TraceToFile("Database '$dbname' not defined");
	die("!Database '$dbname' not defined");
}

//-----------------------------------------------------------------------
// openMySqlDb
// Connect to MySql
//-----------------------------------------------------------------------
function openMySqlDb($server,$dbRealName,$user,$password)
{
	$conn = mysql_connect($server, $user, $password);
	if (!$conn)
		die("!".mysql_error());
	if (!mysql_select_db($dbRealName, $conn))
		die("!".mysql_error($conn));
	return $conn;
}

//=====================================================================================
//  doObjList 
//  Return a list of objects for a given DB
//=====================================================================================
function doObjList()
{	
	global $conn,$catalogquery,$version,$serverVersion,$dbtype,$schema;	
	//------------------------------------------------------------------------------------------------
    // If a custom query is provided, get object types and names by using that		
	//------------------------------------------------------------------------------------------------
	if ($catalogquery == "")
		$catalogquery = "SELECT CASE WHEN TABLE_TYPE LIKE '%TABLE' THEN 'T' ELSE 'V' END,TABLE_SCHEMA,TABLE_NAME FROM INFORMATION_SCHEMA.TABLES"
		               ." WHERE TABLE_SCHEMA ='$schema' ORDER BY 1,2";

	if ($version != "1.0") // if the client is not old version (1.0) put the prefix out
		echo $serverVersion;

	if (!$res=mysql_query($catalogquery,$conn))
	{
		TraceToFile(mysql_error($conn));
		die ("!".mysql_error($conn));
	}
	while ($row = mysql_fetch_row($res))
	{
		echo base64_encode($row[0]).",".base64_encode($row[1]).",".base64_encode($row[2]).";";
	}
	mysql_free_result($res);
}

//=====================================================================================
//  doGetKey 
//  Return the strings for the clauses on primary key (see GETKEY function explanation
//  in the header)
//=====================================================================================
function doGetKey($objFullName)
{
	global $conn,$version,$serverVersion;
	//----------------------------------------------------------------
	//	case "MYSQL"
	//----------------------------------------------------------------
	$objFullName = split("\.",$objFullName);
	if (count($objFullName)<2)
	{
		TraceToFile("Missing database qualifier in the object name");
		die ("!Missing database qualifier in the object name");
	}
	$owner = $objFullName[0];
	$tname = str_replace("''","'",$objFullName[1]);
	
	// Use the standard INFORMATION_SCHEMA for retrieving the primary key columns
	$sql = "SELECT U.COLUMN_NAME,CO.DATA_TYPE FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS C,INFORMATION_SCHEMA.KEY_COLUMN_USAGE U,INFORMATION_SCHEMA.COLUMNS CO" 		
					 ." WHERE U.TABLE_SCHEMA='$owner' AND U.TABLE_NAME='$tname' AND CONSTRAINT_TYPE = 'PRIMARY KEY'"  
					 ." AND C.CONSTRAINT_NAME=U.CONSTRAINT_NAME AND C.CONSTRAINT_SCHEMA=U.CONSTRAINT_SCHEMA"  
					 ." AND CO.TABLE_NAME=U.TABLE_NAME AND CO.TABLE_SCHEMA=U.TABLE_SCHEMA AND CO.COLUMN_NAME=U.COLUMN_NAME"  
					 ." AND C.TABLE_NAME=U.TABLE_NAME AND C.TABLE_SCHEMA=U.TABLE_SCHEMA";
	if (!$res=mysql_query($sql,$conn))
	{
		TraceToFile(mysql_error($conn));
		die ("!".mysql_error($conn));
	}

	// IF NO PRIMARY KEY IS FOUND TRY TO FIND A UNIQUE KEY
	if (mysql_num_rows($res)==0)
	{
		mysql_free_result($res);
		$sql = "SELECT U.COLUMN_NAME,CO.DATA_TYPE FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS C,INFORMATION_SCHEMA.KEY_COLUMN_USAGE U,INFORMATION_SCHEMA.COLUMNS CO"	
					 ." WHERE U.TABLE_SCHEMA='$owner' AND U.TABLE_NAME='$tname' AND CONSTRAINT_TYPE = 'UNIQUE'" 
					 ." AND C.CONSTRAINT_NAME=U.CONSTRAINT_NAME AND C.CONSTRAINT_SCHEMA=U.CONSTRAINT_SCHEMA" 
					 ." AND CO.TABLE_NAME=U.TABLE_NAME AND CO.TABLE_SCHEMA=U.TABLE_SCHEMA AND CO.COLUMN_NAME=U.COLUMN_NAME"  
					 ." AND C.TABLE_NAME=U.TABLE_NAME AND C.TABLE_SCHEMA=U.TABLE_SCHEMA";
		if (!$res=mysql_query($sql,$conn))
		{
			TraceToFile(mysql_error($conn));
			die ("!".mysql_error($conn));
		}
	}

	$str1 = "";
	$str2 = "";
	$str3 = "";
	while ($row = mysql_fetch_row($res))
	{
		$str1 .= ",".$row[0];
		$str2 .= " AND ".$row[0]."=?";
		$str3 .=  getColumnType($row[1]);
	}
	mysql_free_result($res);

	if ($version != "1.0") // if the client is not old version (1.0) put the prefix out
		echo $serverVersion;
	if ($str1>"") $str1 = substr($str1,1);
	if ($str2>"") $str2 = substr($str2,4);
	
	echo base64_encode($str1).",".base64_encode($str2).",".base64_encode($str3);
	TraceToFile("Keys:$str1,$str2,$str3");
}

//=====================================================================================
//  getColumnType 
//  Obtain a generic type for a given MySql, PostgreSql or SQLServer data type
//  Returns: same as getGenericType
//=====================================================================================
function getColumnType($fldtype)
{
	global $version;
	if (stripos($fldtype,"char")!==FALSE || stripos($fldtype,"text")!==FALSE || stripos($fldtype,"string")!==FALSE)
		return "s";
	else if (stripos($fldtype,"date")!==FALSE || stripos($fldtype,"datetime")!==FALSE || stripos($fldtype,"timestamp")!==FALSE)
		return "d";
	else if (stripos($fldtype,"time")!==FALSE)
		if ($version != "1.0")
			return "t";
		else
			return "d"; // version 1 clients do not handle time fields
	else if (stripos($fldtype,"int")!==FALSE || stripos($fldtype,"float")!==FALSE || stripos($fldtype,"number")!==FALSE
	     || stripos($fldtype,"real")!==FALSE || stripos($fldtype,"oid")!==FALSE   || stripos($fldtype,"bit")!==FALSE
	     || stripos($fldtype,"numeric")!==FALSE || stripos($fldtype,"decimal")!==FALSE || stripos($fldtype,"money")!==FALSE)
		return "n";
	else if (stripos($fldtype,"lob")!==FALSE || stripos($fldtype,"long")!==FALSE)
		return "b";
	else	// unknown/unsupported type
	{
		TraceToFile("Unsupported column type $fldtype");
		return "x";
	}
}

//=====================================================================================
//  doCommand 
//  Execute a SQL command
//=====================================================================================
function doCommand($cmd,$limit,$offset,$maxlen,$params)
{
	global $conn,$verb,$limit,$offset,$maxlen,$version,$serverVersion;
	if ($verb=="SELECT")
	{
		$cmd = prepareCommand($cmd,$params);
		
		//----------------------------------------------------------------------------
		// Execute the full statement (with no limit/offset) to know the total
		// number of rows
		//----------------------------------------------------------------------------
		$cmdNoLimit = $cmd;
		if (($pos = stripos($cmd," ORDER BY "))!==FALSE)
			$cmdNoLimit = substr($cmdNoLimit,0,$pos); // strip the ORDER BY CLAUSE
		if (!$res=mysql_query($cmdNoLimit,$conn))
		{
			TraceToFile(mysql_error($conn));
			die ("!".mysql_error($conn));
		}
		$numRows = 	mysql_num_rows($res);		
		mysql_free_result($res);

		//----------------------------------------------------------------------------
		// If a limit and offset are speciefied, add the appropriate clause
		//----------------------------------------------------------------------------
		if ($limit>"")
			$cmd .= " LIMIT $limit";
		if ($offset>"")
			$cmd .= " OFFSET $offset";
		//----------------------------------------------------------------------------
		// Execute the query
		//----------------------------------------------------------------------------
		if (!$res=mysql_query($cmd,$conn))
		{
			TraceToFile(mysql_error($conn));
			die ("!".mysql_error($conn));
		}
		
		if ($version!="1.0") // if the client is not old version (1.0) put the prefix out
			echo $serverVersion;
			
		// Write the first line containing the total number of rows 
		echo base64_encode($numRows).";";

		// Write the header containing field names, types and lengths
		for ($i=0; $i< mysql_num_fields($res); $i++)
		{
			echo base64_encode(mysql_field_name($res,$i)).",".base64_encode(getColumnType(mysql_field_type($res,$i))).",".base64_encode(mysql_field_len($res,$i));
			if ($i<mysql_num_fields($res)-1)
				echo ",";
		}
		echo ";";

		// Read all rows
		while ($row = mysql_fetch_row($res))
		{
			for ($i=0; $i< mysql_num_fields($res); $i++)
			{
				$fldtype  = getColumnType(mysql_field_type($res,$i));
				$fldvalue = $row[$i];
				$fldlen   = strlen($fldvalue);
				TraceToFile("col. $i = $fldvalue ($fldtype)");
				if ($version != "1.0")  // if the client is not old version (1.0) put the prefix out and manage the null/truncate indicator
				{
					if (is_null($fldvalue))
						echo base64_encode("n"); // response contains the null indicator alone
					else
					{
						switch($fldtype)
						{
							case "s":  // string: truncate at maximum length
								if ($fldlen>$maxlen)
									echo base64_encode("t".substr($fldvalue,0,$maxlen)); // truncation indicator plus part of the field value 
								else
									echo base64_encode(" ".$fldvalue);  // empty indicator and full value 
								break;
							case "b": // binary (blob): return a string the describes the field length
								echo base64_encode(" (".$fldlen." bytes)");
								break;
							case "d":  // date: put it in YYYY-MM-DD form
								echo base64_encode(" ".strftime("%Y-%m-%d",strtotime($fldvalue)));
								break;
							case "t": // time data: return only the time part
								echo base64_encode(" ".strftime("%H:%M",strtotime($fldvalue)));
								break;
							default:  // all other types (n,d,t): return the value preceded by an empty indicator
								echo base64_encode(" ".$fldvalue);   
						}
					}
				}
				else // if the client is old version (1.0) no null indicator and slightly different handling
				{
					if (is_null($fldvalue))
						echo base64_encode("(null)"); // response contains the null indicator alone
					else
					{
						switch($fldtype)
						{
							case "s": // string: truncate at maximum length
								if ($fldlen>$maxlen)
									echo base64_encode(substr($fldvalue,0,$maxlen));  
								else
									echo base64_encode($fldvalue);  
								break;
							case "b": // binary: return an indication of length
								echo base64_encode(" (".$fldlen." bytes)");
								break;
							case "x":  // null
								echo base64_encode("(null)"); // response contains the null indicator alone
								break;
							default: // all other types (n,d): return the value
								echo base64_encode($fldvalue);   
						}
					}
				}
				if ($i<mysql_num_fields($res)-1)
					echo ",";
			}				
			echo ";";
		}
		mysql_free_result($res);
	}
	
	else
	{ 
		//=================================================================================
		//   non-SELECT commands
		//=================================================================================
		$cmd = prepareCommand($cmd,$params);
		
		//----------------------------------------------------------------------------
		// Execute the command
		//----------------------------------------------------------------------------
		if (!mysql_query($cmd,$conn))
		{
			TraceToFile(mysql_error($conn));
			die ("!".mysql_error($conn));
		}
		
		if ($version!="1.0") // if the client is not old version (1.0) put the prefix out
			echo $serverVersion;

		$numrecs = mysql_affected_rows($conn);
		echo base64_encode("U").",".base64_encode($numrecs);
		TraceToFile("SQL command ($cmd) affected records: $numrecs");
	}
}

//=====================================================================================
//  prepareCommand 
//  Prepare the parameters for a Command
//  (unlike the ADO and Java version, question marks are replaced instead of creating
//  a parameterized query, just to avoid requiring the mysqli extension)
//=====================================================================================
function prepareCommand($cmd,$params)
{
	if ($params=="")
		return $cmd;
	
	$params = split("\t",$params);	// array of type+value
	$numSost = 0;
	foreach ($params as $param)
	{
		$gentype = substr($param,0,1); // generic data type
		$value   = substr($param,1);   // value as a string
		$pos = strpos($cmd,"?");       // position of first ? not substituted
		if ($pos===FALSE)
		{
			TraceToFile("Number of ? placeholders not matching the number of parameters");
			die("!Number of ? placeholders not matching the number of parameters");
		}
		
		switch ($gentype)
		{
		case "s": // string value
			if ($value=="")
				$cmd = substr($cmd,0,$pos)."NULL".substr($cmd,$pos+1);
			else
				$cmd = substr($cmd,0,$pos)."'".str_replace("'","''",$value)."'".substr($cmd,$pos+1);
			break;
		case "d": // date 
			if ($value=="")
				$cmd = substr($cmd,0,$pos)."NULL".substr($cmd,$pos+1);
			else 
			{
				$datecomp = date_parse_from_format("Y-m-d",$value);
				if (!checkdate($datecomp["month"],$datecomp["day"],$datecomp["year"]))
				{
					TraceToFile("Invalid date literal $value"); 
					die("!Invalid date literal $value"); 
				}
				else
					$cmd = substr($cmd,0,$pos)."'$value'".substr($cmd,$pos+1);
			}
			break;
		case "t": // time
			if ($value=="")
				$cmd = substr($cmd,0,$pos)."NULL".substr($cmd,$pos+1);
			else 
			{
				$value = str_replace(".",":",$value); // sopporta sia con separatore . che con :
				$datecomp = date_parse_from_format("H:i",$value);
				if (!($datecomp["hour"]<25 && $datecomp["minute"]<61))
				{
					TraceToFile("Invalid time literal $value"); 
					die("!Invalid time literal $value"); 
				}
				else
					$cmd = substr($cmd,0,$pos)."'".$value."'".substr($cmd,$pos+1);
			}
			break;
		case "n"; // number
			if ($value=="")
				$cmd = substr($cmd,0,$pos)."NULL".substr($cmd,$pos+1);
			else if (!is_numeric($value))
			{
				TraceToFile("Invalid numeric value $value");
				die("!Invalid numeric value: $value");
			}
			else
				$cmd = substr($cmd,0,$pos).$value.substr($cmd,$pos+1);
		}
	}
	TraceToFile("Modified MySql command: $cmd");
	return $cmd;
}



//=====================================================================================
//  isCommandSupported 
//  Return if the given SQL command is supported on the currently connected DB
//=====================================================================================
function isCommandSupported($sql)
{
	global $updatable,$version,$verb;
	$verb = split(" ",strtoupper(trim($sql)));
	$verb = $verb[0]; 
	if ($verb == "SELECT") 
		return true;
	else if ($verb=="UPDATE" || $verb=="DELETE" || $verb=="INSERT")
	{
		if (!$updatable || $version == "1.0")  // not updatable DB or the client version is too old
		{
			TraceToFile("SQL command ($sql) not permitted on this database");
			die("!SQL command ($sql) not permitted on this database");
		}
		else
			return true;
	}
	else
	{
		TraceToFile("SQL command ($sql) not supported");
		die("!SQL command ($sql) not supported");
	}
	return TRUE;
}

//=====================================================================================//  getUserDBList //  Check the user id and retrieve the authorized dbList//=====================================================================================function getUserDBList($udid){	global $dom,$udid;		$dom = getConfiguration();  // read the configuration XML document	if (!$dom)		die("!configuration.xml parsing failed");			// Find the corresponding "User" node in the configuration 	$users = $dom->getElementsByTagName("user");	foreach($users as $user)	{		$id = $user->getAttribute("udid");		if ($udid==$id || $id=="*") 			return $user->getAttribute("dblist"); 	}	return "";}
//=====================================================================================//  getConfiguration //  Get the configuration.xml document//=====================================================================================function getConfiguration(){	//--------------------------------------------------------------------------------	//  Load the configuration XML file	//--------------------------------------------------------------------------------	$xml = new DOMDocument();	if (!isset($xml))		die(!"DOMDocument init failed");//	xml.validateOnParse = false	$filename = $_SERVER["DOCUMENT_ROOT"]."/server/configuration.xml";	try	{		$xml->load($filename);		if (!$xml)		{			TraceToFile("Missing or empty $filename file"); 				die("!Missing or empty $filename file");		}		// Legge nodo principale		$nodelist = $xml->getElementsByTagName("configuration"); 		if (!$nodelist)		{			TraceToFile("Invalid $filename file: missing 'configuration' node"); 				die("!Invalid $filename file: missing 'configuration' node");		}		return $nodelist->item(0); 	}	catch (Exception $ex)	{		TraceToFile("Load of the configuration.xml file failed: ".$ex->getMessage()); 			die("!Load of the configuration.xml file failed: ".$ex->getMessage());	}}//---------------------------------------------------------------// TraceToFile// Write a trace to file//---------------------------------------------------------------function TraceToFile($text){	global $traceOn;	try	{		if ($traceOn)		{			$filename = $_SERVER["DOCUMENT_ROOT"]."/logfiles/RemoteQueryTrace_".strftime("%F",time());			file_put_contents($filename,strftime("%H.%M.%S",time())." ".$text."\n",FILE_APPEND); 		}	}	catch (Exception $ex)	{		die("!TraceToFile: ".$ex->getMessage());	}}?>