'*------------------------------------------------------------------------
'*    Validate the configuration.xml file against its schema 
'*    and make some more checks
'*
'*    Version 1.0 - July 2009
'*------------------------------------------------------------------------
xmlfile = "configuration.xml"   'configuration file

'on error resume next
set xml = CreateObject("MSXML2.DOMDOCUMENT.6.0")
if xml is nothing then
	set xml = CreateObject("MSXML2.DOMDOCUMENT.4.0")
end if
if xml is nothing then
   errorExit "The needed MSXML2 library is not installed"
end if
'-------------------------------------------------------------------------------------------
'  XML parse validation
'-------------------------------------------------------------------------------------------
xml.validateOnParse = true
xml.resolveExternals = true
xml.async = false
call xml.load(xmlFile)
if xml.parseError.errorCode then
   errorExit xml.parseError.reason
end if

'-------------------------------------------------------------------------------------------
'  Load all DB names for check
'-------------------------------------------------------------------------------------------
set dbDict = CreateObject("Scripting.Dictionary")
set dbs = xml.getElementsByTagName("database")
for each db in dbs
	dbName = db.getAttribute("name")
	if dbDict.exists(lcase(dbName)) then
	    errorExit "database name '" & dbName & "' is duplicated"
	else
		dbDict.add lcase(dbName),dbName
	end if
	'if askusername=true, the user id parameter should not be included in the connection string
	cs   = db.getAttribute("connectionstring")
	ask = db.getAttribute("asklogin")
	if ask>"" then
		if CBool(ask) and (instr(cs,"%user")=0 or instr(cs,"%password")=0) then
		    errorExit "with asklogin=true, the connection string for database '" & dbName & "' must contain the '%user' and '%password' placeholders"
		end if
	end if
next

'-------------------------------------------------------------------------------------------
'  Load all user names for check
'-------------------------------------------------------------------------------------------
set udidDict = CreateObject("Scripting.Dictionary")
set users = xml.getElementsByTagName("user")
for each user in users
	userName = user.getAttribute("name")
	udid     = user.getAttribute("udid")
	if udidDict.Exists(udid) then
	    errorExit "UDID '" & userName & "' is duplicated"
	else
		udidDict.add udid,did
	end if
	'---------------------------------------------------------
	' Check the user's DB list
	'---------------------------------------------------------
	dbList = user.getAttribute("dblist")
	if dbList = "*" then    'dblist='*' means all db allowed
	else
		dbList = split(dbList,",")
		for each dbName in dbList
			if not dbDict.Exists(lcase(dbName)) then
				errorExit "The '" & dbName & "' database for user " & userName & " is not defined among the database elements"
			end if
		next
	end if
next

'-------------------------------------------------------------------------------------------
'  Normal completion
'-------------------------------------------------------------------------------------------
set userDict = nothing
set dbDict   = nothing
Wscript.Echo "Validation OK"
Wscript.quit

'-------------------------------------------------------------------------------------------
'  Exit with an error messaeg and return code = 8
'-------------------------------------------------------------------------------------------
Function errorExit(msg)
    Wscript.echo "Invalid configuration file: " & msg
    Wscript.quit 8
End Function
