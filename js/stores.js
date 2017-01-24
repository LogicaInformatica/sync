Ext.namespace('DCS.Store');

DCS.Store.dsUtente = new Ext.data.Store({
	proxy: new Ext.data.HttpProxy({
		url: 'server/AjaxRequest.php',
		method: 'POST'
	}),   
	baseParams:{	//this parameter is passed for any HTTP request
		task: 'read', 
		sql: "SELECT u.IdUtente, u.NomeUtente "
			+" FROM utente u, reparto r, tiporeparto tr"
			+" WHERE u.IdReparto=r.IdReparto"
			+" AND r.IdTipoReparto=tr.IdTipoReparto"
			+" AND tr.CodTipoReparto='UFF'"
	},
	/*2. specify the reader*/
	reader:  new Ext.data.JsonReader(
		{
			root: 'results',//name of the property that is container for an Array of row objects
			id: 'IdUtente'//the property within each row object that provides an ID for the record (optional)
		},[
			{name: 'IdUtente'},
			{name: 'NomeUtente'}
		]),
	autoLoad: true,
	sortInfo:{field: 'NomeUtente', direction: "ASC"}
});        

DCS.Store.dsAgenzia = new Ext.data.Store({
	proxy: new Ext.data.HttpProxy({
		url: 'server/AjaxRequest.php',
		method: 'POST'
	}),   
	baseParams:{	//this parameter is passed for any HTTP request
		task: 'read', 
		sql: "SELECT r.IdReparto, CONCAT(r.CodUfficio,' - ',r.TitoloUfficio) AS TitoloUfficio"
			+" FROM reparto r LEFT JOIN compagnia c on c.IdCompagnia = r.IdCompagnia"
			+" WHERE c.IdTipoCompagnia = 2"
	},
	/*2. specify the reader*/
	reader:  new Ext.data.JsonReader(
		{
			root: 'results',//name of the property that is container for an Array of row objects
			id: 'IdReparto'//the property within each row object that provides an ID for the record (optional)
		},
		[
			{name: 'IdReparto'},
			{name: 'TitoloUfficio'}
		]
        ),
	autoLoad: true,
	sortInfo:{field: 'TitoloUfficio', direction: "ASC"}
});//end dsAgenzia        

DCS.Store.dsReparto = new Ext.data.Store({
	proxy: new Ext.data.HttpProxy({
		url: 'server/AjaxRequest.php',
		method: 'POST'
	}),   
	baseParams:{	//this parameter is passed for any HTTP request
		task: 'read', 
		sql: "SELECT r.IdReparto, CONCAT(r.CodUfficio,' - ',r.TitoloUfficio) AS TitoloUfficio"
			+" FROM reparto r LEFT JOIN compagnia c on c.IdCompagnia = r.IdCompagnia"
			+" WHERE c.IdTipoCompagnia = 1"
	},
	/*2. specify the reader*/
	reader:  new Ext.data.JsonReader(
		{
			root: 'results',//name of the property that is container for an Array of row objects
			id: 'IdReparto'//the property within each row object that provides an ID for the record (optional)
		},
		[
			{name: 'IdReparto'},
			{name: 'TitoloUfficio'}
		]
        ),
	autoLoad: true,
	sortInfo:{field: 'TitoloUfficio', direction: "ASC"}
});//end dsReparto      

DCS.Store.dsTipoRecapito = new Ext.data.Store({
	proxy: new Ext.data.HttpProxy({
		url: 'server/AjaxRequest.php',
		method: 'POST'
	}),   
	baseParams:{	//this parameter is passed for any HTTP request
		task: 'read',
		sql: "SELECT IdTipoRecapito, TitoloTipoRecapito"
			+" FROM tiporecapito where NOW() BETWEEN DataIni AND DataFin ORDER BY Ordine"
	},
	/*2. specify the reader*/
	reader:  new Ext.data.JsonReader(
		{
			root: 'results',//name of the property that is container for an Array of row objects
			id: 'IdTipoRecapito'//the property within each row object that provides an ID for the record (optional)
		},
		[
			{name: 'IdTipoRecapito'},
			{name: 'TitoloTipoRecapito'}
		]
        ),
	autoLoad: true
});//end dsTipoRecapito      
DCS.Store.dsTipoRecapito.load();

DCS.Store.dsProvince = new Ext.data.ArrayStore({
	autoDestroy: false,
	url: 'server/province.php',
	idIndex: 0,
	fields: ['sigla'],
	autoload: true
});
DCS.Store.dsProvince.load();

/* Mesi per i grafici (singleton) */
DCS.Store.ChartMesi = function() {
   var _instance = null;
   return {
      getInstance : function() {
         if (_instance === null) {
			var firstday = new Date();
			firstday.setDate(1);
			firstday = firstday.add(Date.MONTH,5); // fino al sesto mese succcessivo 
			var data = firstday.format("[[n, 'M-y', 'F Y', Y]");
			for (i=1; i<=18; i++) {
				firstday = firstday.add(Date.MONTH,-1);
				if (firstday.format("Ymm")<'201105') // prima del maggio 2011 non ci sono dati
					break;
				data += firstday.format(",[n, 'M-y', 'F Y', Y]");
			}
			data = Ext.decode(data+']');
			for (i=0; i<data.length; i++) {
				var y = data[i][3];
				data[i][3] = (data[i][0]>LAST_FY_MONTH)?y+1:y;
				data[i][3] = 'Fiscal Year '+data[i][3];		// fiscal year
				data[i][0] = y*100+data[i][0];		// yyyymm
			}
            _instance = new Ext.data.ArrayStore({
	        	fields: ['num','abbr','mese', 'FY'],
		        data: data
		    });
         }
         return _instance;
      }
   };
}();

/* Anni fiscali per i grafici (singleton) */
DCS.Store.ChartAnni = new Ext.data.ArrayStore({
	autoDestroy: false,
	url: 'server/AjaxRequest.php',
	baseParams:{	//this parameter is passed for any HTTP request
		task: 'getFiscalYears'
	},
	fields: ['num'],
	autoload: true
});
DCS.Store.ChartAnni.load();

//-----------stores filtri--------------------
DCS.Store.dsAbbrStatoRecupero = new Ext.data.Store({
	proxy: new Ext.data.HttpProxy({
		url: 'server/AjaxRequest.php',
		method: 'POST'
	}),   
	baseParams:{	//this parameter is passed for any HTTP request
		task: 'read',
		sql: "select IdStatoRecupero as id,AbbrStatoRecupero as text from statorecupero where AbbrStatoRecupero is not null order by AbbrStatoRecupero asc;"
	},
	/*2. specify the reader*/
	reader:  new Ext.data.JsonReader(
		{
			root: 'results',//name of the property that is container for an Array of row objects
			id: 'id'//the property within each row object that provides an ID for the record (optional)
		},
		[{name: 'id', type: 'int'},
		{name: 'text'}]
        ),
	autoLoad: true
});//end dsTipoRecapito      
DCS.Store.dsAbbrStatoRecupero.load();

DCS.Store.dsAbbrClasse = new Ext.data.Store({
	proxy: new Ext.data.HttpProxy({
		url: 'server/AjaxRequest.php',
		method: 'POST'
	}),   
	baseParams:{	//this parameter is passed for any HTTP request
		task: 'read',
		sql: "select 0 as id,'STR' as text"+
				" union all"+
				" select 1,'LEG'"+
				" union all"+
				" select 2,'DBT'"+
				" union all"+
				" select IdClasse+2,AbbrClasse from classificazione;"
	},
	/*2. specify the reader*/
	reader:  new Ext.data.JsonReader(
		{
			root: 'results',//name of the property that is container for an Array of row objects
			id: 'id'//the property within each row object that provides an ID for the record (optional)
		},
		[{name: 'id', type: 'int'},
		{name: 'text'}]
        ),
	autoLoad: true
});//end dsTipoRecapito      
DCS.Store.dsAbbrClasse.load();

DCS.Store.dsAgenzieAFF = new Ext.data.Store({
	proxy: new Ext.data.HttpProxy({
		url: 'server/AjaxRequest.php',
		method: 'POST'
	}),   
	baseParams:{	//this parameter is passed for any HTTP request
		task: 'read',
		sql: "select idregolaprovvigione as id,CONCAT(r.TitoloUfficio,' (',c.CodRegolaProvvigione,')') AS text"+
				" from regolaprovvigione c left join reparto r on(r.Idreparto=c.Idreparto) group by text;"
	},
	/*2. specify the reader*/
	reader:  new Ext.data.JsonReader(
		{
			root: 'results',//name of the property that is container for an Array of row objects
			id: 'id'//the property within each row object that provides an ID for the record (optional)
		},
		[{name: 'id', type: 'int'},
		{name: 'text'}]
        ),
	autoLoad: true
});//end dsTipoRecapito      
DCS.Store.dsAgenzieAFF.load();