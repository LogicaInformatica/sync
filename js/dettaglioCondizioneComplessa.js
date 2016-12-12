// Crea namespace DCS
Ext.namespace('DCS');

//window di dettaglio
// l'elemento n.5 deve essere la combo di stato recupero.
var winCC;


DCS.DettaglioAzCComx = Ext.extend(Ext.TabPanel, {

	IdMainW:'',
	IdLauncher:'',
	isbutton:false,
	arrConf:'',
	listStore: '',
	rowIndex: '',
	idProcedura:'',
	titoloAz:'',
	whoIs:'',
	
	initComponent: function() {
		
		var IdMain = this.getId();
		var underWin = this.IdMainW;
		var launcher = this.IdLauncher;
		var arrConf = this.arrConf;
		var ls = this.listStore;
		var ri = this.rowIndex;
		var idP = this.idProcedura;
		var tAz = this.titoloAz;
		var WhoIsCaller = this.whoIs;
		var IdSolo = '';
		var arrStringa=[];

		var ckStoreStati = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			id:'ckstStatCmx',
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				sql: "SELECT IdStatoRecupero,TitoloStatoRecupero FROM statorecupero order by TitoloStatoRecupero asc" 
			},
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdStatoRecupero'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'IdStatoRecupero'},
				{name: 'TitoloStatoRecupero'}]
			),
			autoLoad: true
		});
			
		//CheckGroup e array di configurazione
		var checkboxconfigsST = []; //array of about to be checkboxes.   
		var CheckStateGroup = new Ext.form.CheckboxGroup({
		    id:'CPGroupSt',
		    xtype: 'checkboxgroup',
		    fieldLabel: 'Single Column',
		    itemCls: 'x-check-group-alt',
		    columns: 1,
		    items: [checkboxconfigsST],
		    listeners:{
				change:function(CheckStateGroup,arr){
					//segna le aggiunte di check
					flag = false;
					arr = CheckStateGroup.getValue();
					for(k=0;k<arr.length;k++)
	            	{
						for(j=0;j<checkboxconfigsST.length;j++)
		            	{
							if(arr[k].id==checkboxconfigsST[j].id)
							{
								checkboxconfigsST[j].checked = arr[k].checked;
								break;
							}
		            	}
	            	}
					//segna le detrazioni di check
					for(j=0;j<checkboxconfigsST.length;j++)
	            	{
						flag = false;
						if(checkboxconfigsST[j].checked){
							for(k=0;k<arr.length;k++)
			            	{
								if(arr[k].id==checkboxconfigsST[j].id)
								{
									flag=true;
									break;
								}
							}
							if(!flag){checkboxconfigsST[j].checked = false;}
						}
	            	}
					
					Ext.getCmp('btnSaveCC').setDisabled(false);
					var arrChekd=[];
					for(var h=0;h<checkboxconfigsST.length;h++)
	            	{
						if(checkboxconfigsST[h].checked==true)
						{
							arrChekd.push(checkboxconfigsST[h].id);
						}
	            	}
					arrStringa=arrChekd;
					IdSolo=arrChekd.join();
					Ext.getCmp(IdMain).addString(IdSolo,Ext.getCmp('ckNot').getValue());
				}
			}
		});
		
		var chiudi = new Ext.Button({
			text: 'Chiudi',
			handler: function(b,event) {
				winCC.close();
				switch(WhoIsCaller)
				{
					case 'isAzWrkF':showAzioneDetail(arrConf[1],ls,ri,idP,tAz,arrConf);
						break;
					case 'isAzRule':showAzDetail(arrConf[9],tAz,ls,ri,arrConf);
						break;
				}
			},
			scope: this
		});
		//Bottone di salvataggio
		var save = new Ext.Button({
			text: 'Salva',
			id:'btnSaveCC',
			disabled: true,
			handler: function(b,event) 
			{
				if (formCCond.getForm().isValid())
				{
					if(arrStringa.length==1 && Ext.getCmp('ckNot').getValue()!=true)
					{
						//seleziona l'indice giusto e passalo nell'array di conf
						arrConf[5]=IdSolo;
					}else if(arrStringa.length==0){
						arrConf[5]=-1;
					}else{
						//passa il valore nell'array di conf
						arrConf[5]=Ext.getCmp('cond').getValue();
					}
					winCC.close();
					switch(WhoIsCaller)
					{
						case 'isAzWrkF':showAzioneDetail(arrConf[1],ls,ri,idP,tAz,arrConf);
							break;
						case 'isAzRule':showAzDetail(arrConf[9],tAz,ls,ri,arrConf);
							break;
					}
				}
			},
			scope: this
		});
		
		//Form su cui montare gli elementi
		var formCCond = new Ext.form.FormPanel({
			title:'Dettaglio',		//il titolo è usato per testare il tab
			frame: true,
			id:'formCmplx',
			bodyStyle: 'padding:5px 5px 0',
			border: false,
			trackResetOnLoad: true,
			items: [{
				xtype:'container',columnWidth:.98, 
				items:[{//oggetto primo
						xtype:'fieldset', title:'Condizione', border: true,
						items:[{
								xtype:'panel', layout:'form', columnWidth:.98,/*labelWidth:0,*/ defaultType:'textfield',
								defaults: {anchor:'99%', readOnly:true},
								items: [{fieldLabel:'',	id:'cond', name:'condizione', style:'text-align:left',hideLabel: true}]
						}]
				}]
			},{
				xtype:'container', layout:'column', columnWidth:.99,
				items:[{
						xtype:'fieldset', id:'fsomx', title:'Lista stati',autoScroll:true, border:true, layout:'column',columnWidth:.65,height:280,bodyStyle: 'padding-left:5px;',
						items:[]
				},{
						xtype:'fieldset', title:'', border: false, columnWidth:.35,
						items:[{
								xtype:'fieldset', title:'', border:false, layout:'column',height:170,bodyStyle: 'padding-left:5px;',
								items:[]
						},{
								xtype:'container', 
								items:[{
									xtype:'fieldset', title:'', border: false, bodyStyle: 'padding-left:0px;',layout:'anchor',//columnWidth:.43,
									items:[{
										style: 'padding-left:0px; anchor:"0%";',
		           						xtype: 'checkbox',
										boxLabel: 'Non in',
										id: 'ckNot',
										//id:'NotIn',
										name:'NotIn',
										hiddenName: 'NotIn',
										hidden: false,
										checked: false,
										handler: function() {
											Ext.getCmp('btnSaveCC').setDisabled(false);
											var arrChekd=[];
											for(j=0;j<checkboxconfigsST.length;j++)
							            	{
												if(checkboxconfigsST[j].checked==true)
												{
													arrChekd.push(checkboxconfigsST[j].id);
												}
							            	}
											arrStringa=arrChekd;
											IdSolo=arrChekd.join();
											Ext.getCmp(IdMain).addString(IdSolo,Ext.getCmp('ckNot').getValue());
						        		}
									}]
								},{
									
									xtype:'fieldset', title:'', border: false, bodyStyle: 'padding-left:0px;',//columnWidth:.43,
									items:[{
										xtype:'container', layout:'column', 
										items:[{
											xtype:'container',columnWidth:.30, 
											items:[{
							                	xtype:'button',
						                    	tooltip:"Accoda alla condizione gli stati specificati",
												text:"Aggiungi",
												name: 'btnAddCondC', 
											    id: 'btnAddCondC',
											    hidden:true,
											    disabled:false,
											    anchor: '30%',
											    handler: function(b,event) {
													Ext.getCmp('btnSaveCC').setDisabled(false);
													var arrChekd=[];
													for(j=0;j<checkboxconfigsST.length;j++)
									            	{
														if(checkboxconfigsST[j].checked==true)
														{
															arrChekd.push(checkboxconfigsST[j].id);
														}
									            	}
													arrStringa=arrChekd;
													IdSolo=arrChekd.join();
													Ext.getCmp(IdMain).addString(IdSolo,Ext.getCmp('ckNot').getValue());
									        	},
												scope: this
											}]
										},{
											xtype:'container',columnWidth:.30, 
											items:[{
							                	xtype:'button',
						                    	tooltip:"Pulisci la condizione",
												text:"Cancella",
												name: 'btnClearCondC', 
											    id: 'btnClearCondC',
											    disabled:false,
											    anchor: '30%',
											    handler: function(b,event) {
													//debug
													if(Ext.getCmp('cond').getValue()!='')
													{
														arrStringa='';
														for(var u=0;u<checkboxconfigsST.length;u++)
										            	{
															checkboxconfigsST[u].checked=false;
										            	}
														CheckStateGroup.setValue(checkboxconfigsST);
														Ext.getCmp('btnSaveCC').setDisabled(false);
														Ext.getCmp('cond').setValue('');
													}
									        	},
												scope: this
											}]
										}]
									}]
									
								}]
	                    }]
				}]
			}],
			buttons:[chiudi,save]
		});
		
		Ext.apply(this, {
			activeTab:0,
			items: [formCCond]
        });
		
		DCS.DettaglioAzCComx.superclass.initComponent.call(this);
		
		ckStoreStati.load({
			callback: function(r,options,success){
				  
				range = ckStoreStati.getRange();
				for (i=0; i<range.length; i++)
				{
					var rec = range[i];
					checkboxconfigsST.push({ 
				        id:rec.data.IdStatoRecupero,
				        boxLabel:rec.data.TitoloStatoRecupero,
				        checked: false
				      });
				}
				var flagInRange=false;
				for(var j=0;j<checkboxconfigsST.length;j++)
            	{
					if(arrConf[5]==checkboxconfigsST[j].id)
					{
						checkboxconfigsST[j].checked = true;
						flagInRange=true;
						Ext.getCmp('cond').setValue("IdStatoRecupero IN ("+checkboxconfigsST[j].id+")");
						break;
					}
            	}
				if(!flagInRange && arrConf[5]!=null && arrConf[5]!=-1)
				{
					Ext.getCmp('cond').setValue(arrConf[5]);
					var first=arrConf[5].indexOf('(');
					var last=arrConf[5].indexOf(')');
					var stringa = arrConf[5].slice(first+1,last);
					var ArrayS = stringa.split(',');

					var h=0;
					for(var k=0;k<checkboxconfigsST.length;k++)
	            	{
						if(ArrayS[h]==checkboxconfigsST[k].id)
						{
							checkboxconfigsST[k].checked = true;
							h++;
							k=checkboxconfigsST.length;
						}
						if(h<ArrayS.length && k==checkboxconfigsST.length)
						{
							k=0;
						}
	            	}

				}
				Ext.getCmp('fsomx').add(CheckStateGroup);
				Ext.getCmp('fsomx').doLayout(true,false);
			},
			scope: this
		});
	},
	//--------------------------------------------------------
    // Elaborazione stringa
	// stringa: la lista degli Id da condizionare
	// inOrNot: la modalità in cui va inserita  tale lista
    //--------------------------------------------------------
	addString: function(stringa,inOrNot)
	{
			var word='';
			switch(inOrNot)
			{
				case true: word="IdStatoRecupero NOT IN("+stringa+")"; 
					break;
				default:word="IdStatoRecupero IN("+stringa+")";
			}
			Ext.getCmp('cond').setValue(word);
	}
});

// register xtype
Ext.reg('DCS_dettaglioAzCComplx', DCS.DettaglioAzCComx);

//--------------------------------------------------------
//Visualizza dettaglio dell'editor di condizione complessa
//--------------------------------------------------------
function showCCmxDetail(IdMainW,IdLauncher,isbutton,arrConf,store,rowIndex,pass,titoloaz,whoIs) {
	
	var myMask = new Ext.LoadMask(Ext.getBody(), {
		msg: "Preparazione editor..."
	});
	myMask.show();
	titolo='Componi una condizione complessa sullo stato dell\'azione di "'+titoloaz+'"';

	arrConf=arrConf||'';
	isbutton=isbutton||false;
	
	winCC = new Ext.Window({
		width: 600,
		height: 470,
		minWidth: 600,
		minHeight: 470,
		layout: 'fit',
		id:'dettaglioCC',
		stateful:false,
		plain: true,
		bodyStyle: 'padding:5px;',
		modal: true,
		title: titolo,
		tools: [helpTool("DettaglioCondCompl")],
		constrain: true,
		items: [{
			xtype: 'DCS_dettaglioAzCComplx',
			IdMainW:IdMainW,
			IdLauncher:IdLauncher,
			isbutton:isbutton,
			arrConf:arrConf,
			listStore: store,
			rowIndex: rowIndex,
			idProcedura:pass,
			titoloAz:titoloaz,
			whoIs:whoIs
			}]
	});

	//Ext.apply(win.getComponent(0),{winList:win});
	winCC.on({
		'close' : function () {
			//oldWind = '';
		}
	});
	winCC.show();
	myMask.hide();
	
}; // fine funzione 