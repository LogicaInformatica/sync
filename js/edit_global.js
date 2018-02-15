Ext.namespace('DCS');
Ext.namespace('DCS.render');
// Ext.namespace('DCS','DCS.render'); questo formato in GoogleChrome d� poi DCS undefined

DCS.Table = function(){
	var name;
	var view;
	var pk;
	var expandCol;

	var record;
	var colModel;

	var newRecord;
};

//-------------------------------------------------------------
// DCS.render
//-------------------------------------------------------------
// create reusable combobox renderer
DCS.render.combo = function(combo){
    return function(value){
        var record = combo.findRecord(combo.valueField, value);
        return record ? record.get(combo.displayField) : combo.valueNotFoundText;
    };
};

// Renderer per la data in giorno/mese/anno
DCS.render.date = function (value){
	return value ? value.dateFormat('d/m/Y') : '';
};

//Renderer per scrivere una data in formato Nomemese Anno
DCS.render.meseAnno = function (value){
	var mesi = ["Gennaio","Febbraio","Marzo","Aprile","Maggio","Giugno","Luglio","Agosto","Settembre","Ottobre","Novembre","Dicembre"];
	if (value) {
		return mesi[value.getMonth()]+' '+value.getFullYear();
	} else {
		return '';
	}
};


//Renderer per un float che pu� essere nullo e rappresenta una percentuale
DCS.render.floatV = function (value){
	return value>0 ?  (Ext.util.Format.number(value,'000,00/i')+' %') : '';
};

//Renderer per un int che pu� essere nullo
DCS.render.intV = function (value){
	return value!=0 ?  value : '';
};

// Renderer per uno sfondo con una striscia colorata da verde a rosso 
// per una data relativa ad oggi sulla base di 30 giorni
DCS.render.dataSem = function (value, meta, rec){
	var inizio = rec.get('DataInizioAffido');
	if (value && inizio) {
		var oggi = new Date();
		oggi.setUTCHours(0,0,0,0);
		var periodo   = Math.round((value - inizio) / 86400000);	// periodo di affido in giorni
		var trascorso = Math.round((oggi - inizio) / 86400000);		// giorni trascorsi dall'inizio
		tooltip = trascorso + '&#176; su '+periodo; 

		var indice = Math.round(trascorso*60/periodo);
		if (indice < 1) {
			indice = 1;
		}
		else 
			if (indice > 60) {
				indice = 60;
			}
		return '<div style="background:url(\'images/urgenza.gif\') no-repeat left; width:' + indice + 'px;" title="'+tooltip+'">&nbsp;</div>';
	} else
		return '';
};

// Renderer per un segno di spunta nelle liste
DCS.render.spunta = function(value, me, rec, r){
	//value deve essere Y/N oppure P (grigio)
	return '<img src="images/'+value+'_spunta.gif" />';
};

DCS.render.word_wrap = function(value, cell) {
	var str = "<span style='white-space:normal'>" + value + "</span>";
	return str;
};

// Renderer per la data di prossima azione (o per una qualsiasi data/ora di scadenza futura)
DCS.render.prossimaData = function(value, metaData, record, rowIndex, colIndex, store) {
	if (!value)
		return '';
	var oggi = new Date();
	var ora  = value.dateFormat('H:i');
	if (value.dateFormat('d/m/Y')==oggi.dateFormat('d/m/Y')) // oggi
		if (ora=='00:00') // nessuna ora specificata
			return '<b>Oggi</b>';
		else
			return 'Ore <b>'+ora+'</b>';
	else // non � oggi
		if (ora=='00:00') // nessuna ora specificata
			return value.dateFormat('d/m');
		else
			return value.dateFormat('d/m H:i');
};

//-------------------------------------------------------------
//Renderer da usare per le celle con editor: combobox (deve essere usata con renderer: DCS.comboRenderer(combo)
DCS.comboRenderer = function(combo){
    return function(value){
        var record = combo.findRecord(combo.valueField, value);
        return record ? record.get(combo.displayField) : combo.valueNotFoundText;
    };
};

// Renderer per marcare le pratiche arricchite dalla visura ACI
DCS.render.flagVisuraAci = function (value, meta, rec){
	var flagVisuraAci = rec.get('FlagVisuraAci');
	return (flagVisuraAci=='Y') ? value+' (visura)': value;
};

// Converter per valuta in italiano
function numdec_it(v, record){
   	return Ext.util.Format.number(v, '0.000,00/i');
}

// Converter per valore boleano da db ('N','n' o null --> false, altrimenti true)
function bool_db(v, record){
   	return (v==null || v=='N' || v=='n')?false:true;
}

// Converter per data in formato italiano
function date_it(v, record){
   	return Ext.util.Format.date(v, 'd/m/Y');
}

// Restituisce il contenuto del componente dato come float eliminando la formattazione italiana
// Att.ne se il campo � un numberfield definito con il giusto decimalSeparator la getvalue restituisce un vero numero
function getFloatValue(name) {
	var fld = Ext.getCmp(name);
	var v   = fld.getValue();
	var raw = fld.getRawValue();
	if (fld.xtype=='numberfield') {
		if (raw=='')
			return 0;
		else
			return v;
	}
	// non � un number field
	if (!raw>'')
		return 0;
	else
		return parseFloat(raw.replace('.','').replace(',','.'));
}
		
// Imposta il contenuto del componente dato con un valore float con formattazione italiana
// (se il campo � un numberfield, mette il valore numerico cos� com'�, visto che lo formatta il sistema)
function setFloatValue(name,value) {
	var fld = Ext.getCmp(name);
	if (fld.xtype=='numberfield')
		fld.setValue(value);
	else
		fld.setValue(Ext.util.Format.number(value, '0.000,00/i'));
}

var dataEditor =  new Ext.form.DateField({ //DateField editor
	allowBlank: false, 

	//defaults to 'm/d/y', if there is a renderer 
	//specified it will render whatever this form
	//returns according to the renderer
	format: 'd/m/Y', 

	//specify a minimum value such that anything prior to
	//this date is greyed out/unclicklable.  The validator
	//prevents typing a new date violating criteria 
	minValue: '01/01/70'
	/*,
	disabledDays: [0, 3, 6],
	disabledDaysText: 'Closed on this day' */
});

   // Definisce funzioni summary aggiuntive per il calcolo della percentuale totale
   // nelle griglie di sintesi
   Ext.ux.grid.GroupSummary.Calculations['percentTotale'] = function(v, record, field, data)
   {
   	if (!data['ImpInsolutoTotal'])   // elemento di comodo in cui accumula l'imp totale debito
   	{
   		data['ImpInsolutoTotal'] = 0;
   		data['ImpPagatoTotal'] = 0;
   	}
	data['ImpInsolutoTotal'] += record.data['ImpInsoluto'];
	data['ImpPagatoTotal'] += record.data['ImpPagato'];
	return  (data['ImpPagatoTotal']*100)/data['ImpInsolutoTotal'];
   };

   Ext.ux.grid.GroupSummary.Calculations['percentCapitale'] = function(v, record, field, data)
   {
   	if (!data['ImpCapitaleTotal'])   // elemento di comodo in cui accumula l'imp totale debito
   		data['ImpCapitaleTotal'] = 0;
	data['ImpCapitaleTotal'] += record.data['ImpCapitale'];
	return  (data['ImpPagatoTotal']*100)/data['ImpCapitaleTotal'];
   };

   Ext.ux.grid.GroupSummary.Calculations['percentIPM'] = function(v, record, field, data)
   {
   	if (!data['NumAffidatiTotal'])   // elemento di comodo in cui accumula il numero totale pratiche
   	{
   		data['NumAffidatiTotal'] = 0;
   		data['NumIncassatiTotal'] = 0;
   	}
	data['NumIncassatiTotal'] += record.data['NumIncassati'];
	data['NumAffidatiTotal'] += record.data['NumAffidati'];
	return  (data['NumIncassatiTotal']*100.)/data['NumAffidatiTotal'];
   };

   Ext.ux.grid.GroupSummary.Calculations['percentIPR'] = function(v, record, field, data)
   {
   	if (!data['ImpCapitaleAffidatoTotal'])   // elemento di comodo in cui accumula il numero totale pratiche
   	{
   		data['ImpCapitaleAffidatoTotal'] = 0;
   		data['ImpCapitaleIncassatoTotal'] = 0;
   	}
	data['ImpCapitaleIncassatoTotal'] += record.data['ImpCapitaleIncassato'];
	data['ImpCapitaleAffidatoTotal'] += record.data['ImpCapitaleAffidato'];
	return  (data['ImpCapitaleIncassatoTotal']*100.)/data['ImpCapitaleAffidatoTotal'];
   };

   Ext.ux.grid.GroupSummary.Calculations['percentIPF'] = function(v, record, field, data)
   {
   	if (!data['ImpCapitaleRealeIncassatoTotal'])   // elemento di comodo in cui accumula il numero totale pratiche
   	{
   		data['ImpCapitaleRealeIncassatoTotal'] = 0;
   	}
	data['ImpCapitaleRealeIncassatoTotal'] += record.data['ImpCapitaleRealeIncassato'];
	return  (data['ImpCapitaleRealeIncassatoTotal']*100.)/data['ImpCapitaleAffidatoTotal'];
   };


