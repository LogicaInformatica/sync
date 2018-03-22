/*
 * File che costruisce il form di dettaglio delle modalità di 
 * importazione dei file
 */
// Crea namespace DCS
Ext.namespace('DCS');

var recordIMP = new Ext.data.Record.create([{
        name: 'IdModulo1',
        type: 'int'
    },
    {
        name: 'TipoAttivazione1',
        type: 'string'
    }
]);

DCS.AllegatiMassivi = function() {
    return {
        create: function() {

            /** Campi che stanno sulla prima riga **/
            var labelFileName = { // in prima colonna: visualizza il nome del file selezionato
                id: 'newFileAllegatiMassivi',
                xtype: 'displayfield',
                fieldLabel: 'File da caricare',
                style: {
                    color: 'darkblue',
                    fontWeight: 'bold'
                },
                height: 86 // per forzare l'altezza della riga in modo che si allinei bene la successiva
            };
            var btnUpload = { // in seconda colonna: pulsante per upload, senza campo di input
                xtype: 'fileuploadfield',
                //fieldLabel: 'File di input',
                id: 'docPathAllegatiMassivi',
                name: 'docPathAllegatiMassivi',
                buttonText: 'Scegli file',
                buttonOnly: true,
                listeners: {
                    'fileselected': function() {
                        var valueTitolo = Ext.getCmp('docPathAllegatiMassivi').getValue();
                        // Ri-trasforma i caratteri URLEncoded in caratteri normali
                        valueTitolo = unescape(String(valueTitolo).replace("/\+/g", " "));
                        // Toglie il path
                        if (valueTitolo.lastIndexOf("\\") > 0)
                            valueTitolo = valueTitolo.substring(1 + valueTitolo.lastIndexOf("\\"));
                        if (valueTitolo.lastIndexOf("/") > 0)
                            valueTitolo = valueTitolo.substring(1 + valueTitolo.lastIndexOf("/"));
                        Ext.getCmp('newFileAllegatiMassivi').setValue(valueTitolo);
                    }
                }
            };

            var labelHelp1 = { // in terza colonna: testo esplicativo
                xtype: 'displayfield',
                fieldLabel: '',
                labelWidth: 1,
                value: 'Selezionare il file zip con i file da caricare'
            };
            
            var column1 = {
                xtype: 'panel',
                layout: 'form',
                colWidth: 0.35,
                items: [labelFileName]
            };

            var column2 = {
                xtype: 'panel',
                layout: 'form',
                colWidth: 0.20,
                items: [btnUpload]
            };
            
            var column3 = {
                xtype: 'panel',
                layout: 'form',
                labelWidth: 20,
                colWidth: 0.45,
                items: [labelHelp1]
            };

            //form su cui montare gli elementi
            var formAllegatiMassivi = new Ext.form.FormPanel({
                title: 'Allegati massivi',
                frame: true,
                header: true,
                fileUpload: true,
                bodyStyle: 'padding:5px 5px 0',
                border: false,
                anchor: '95%',
                //height: 360,
                labelWidth: 110,
                trackResetOnLoad: true,
                /*idProdotto: this.idProdotto,
                idLotto: this.idLotto,*/
                reader: new Ext.data.JsonReader({
                    root: 'results',
                    fields: recordIMP
                }),
                items: [{
                    xtype: 'container',
                    layout: 'column',
                    items: [column1, column2, column3]
                }],
                //buttons
                buttons: [{
                        text: 'Esegui',
                        handler: function(btn) {
                            var processName = Math.random();
                            if (!(Ext.getCmp('docPathAllegatiMassivi').getValue() > '')) {
                                Ext.Msg.alert('Errore', 'Selezionare un file');
                                return;
                            }
                            DCS.showMask('Invio file al server...');
                            formAllegatiMassivi.getForm().submit({
                                url: 'server/allegatiMassivi.php',
                                method: 'POST',
                                params: {
                                    task: 'importFile'
                                },
                                success: function(frm, action) {
                                	DCS.hideMask();
                                	if(action.result.success){
										Ext.MessageBox.alert('Esito', action.result.data);
							        } else {
										Ext.MessageBox.alert('Errore', action.result.error);
									  }
                                },
                                failure: function(frm, action) {
                                	DCS.hideMask();
                                    Ext.MessageBox.alert('Errore', action.result.error);
                                }
                            }); //fine submit formImport
                        },
                        scope: this
                    }
                    /*{text: 'Annulla',handler: function () {finestraImport.close();} 
                    }*/
                ] //fine array buttons
            }); //fine formImportVisureACI
            Ext.apply(this, {
                items: [formAllegatiMassivi]
            });

            return formAllegatiMassivi;
    }
  };
}();