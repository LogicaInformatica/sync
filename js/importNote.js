/*
 * File che costruisce il form di dettaglio delle modalità di 
 * importazione dei file
 */
// Crea namespace DCS
Ext.namespace('DCS');

//window di dettaglio
var wnd;

var recordIMP = new Ext.data.Record.create([{
        name: 'IdModulo1',
        type: 'int'
    },
    {
        name: 'TipoAttivazione1',
        type: 'string'
    }
]);

DCS.ImportNote = function() {
    return {
        create: function() {

            /** Campi che stanno sulla prima riga **/
            var labelFileName = { // in prima colonna: visualizza il nome del file selezionato
                id: 'newFile1',
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
                id: 'docPath1',
                name: 'docPath1',
                buttonText: 'Scegli file',
                buttonOnly: true,
                listeners: {
                    'fileselected': function() {
                        var valueTitolo = Ext.getCmp('docPath1').getValue();
                        // Ri-trasforma i caratteri URLEncoded in caratteri normali
                        valueTitolo = unescape(String(valueTitolo).replace("/\+/g", " "));
                        // Toglie il path
                        if (valueTitolo.lastIndexOf("\\") > 0)
                            valueTitolo = valueTitolo.substring(1 + valueTitolo.lastIndexOf("\\"));
                        if (valueTitolo.lastIndexOf("/") > 0)
                            valueTitolo = valueTitolo.substring(1 + valueTitolo.lastIndexOf("/"));
                        Ext.getCmp('newFile1').setValue(valueTitolo);
                    }
                }
            };

            var labelHelp1 = { // in terza colonna: testo esplicativo
                xtype: 'displayfield',
                fieldLabel: '',
                labelWidth: 1,
                value: 'Selezionare un file Excel in cui ogni riga (a parte la prima di testata) contiene i seguenti campi:' +
                '<br>&nbsp; 1. Codice della pratica' +
                '<br>&nbsp; 2. Data della registrazione' +
                '<br>&nbsp; 3. Nome dell\'operatore (opzionale)' +
                '<br>&nbsp; 4. Descrizione dell\'evento' +
                '<br>&nbsp; 5. Nota (opzionale)'
            };

            /** Campi che stanno sulla seconda riga **/

            var radioImport = {
                xtype: 'radiogroup',
                columns: 1,
                vertical: true,
                fieldLabel: 'Tipo di caricamento',
                labelWidth: 100,
                items: [{
                    checked: true,
                    style: 'margin-left: 4px',
                    xtype: 'radio',
                    boxLabel: '<span style="color:darkblue">Rimpiazza le note gi&agrave; caricate</span>',
                    name: 'radioTipoImport',
                    id: 'TipoImportR',
                    inputValue: 'R',
                    style: {
                        marginLeft: '5px'
                    },
                    width: 200
                }, {
                    checked: false,
                    style: 'margin-left: 4px',
                    xtype: 'radio',
                    boxLabel: '<span style="color:darkblue">Aggiorna le note gi&agrave; caricate</span>',
                    name: 'radioTipoImport',
                    id: 'TipoImportA',
                    inputValue: 'A',
                    style: {
                        marginLeft: '5px'
                    },
                    width: 200
                }]
            };
            var nofield = {
                xtype: 'displayfield',
                height: 24
            };
            var labelHelp2 = { // in terza colonna: testo esplicativo
                xtype: 'displayfield',
                fieldLabel: '',
                labelWidth: 1,
                value: 'Se scegli la seconda opzione, le note aventi stesso numero di pratica, data ed evento vengono aggiornate anzich&eacute; inserite come nuove'
            };


            /** campo nascosto necessario al programma php (viene messo sulla terza riga)**/
            var hidden1 = {
                xtype: 'textfield',
                name: 'IdModulo1',
                id: 'IdModulo1',
                value: 1,
                hidden: true
            };

            var column1 = {
                xtype: 'panel',
                layout: 'form',
                colWidth: 0.35,
                items: [labelFileName, nofield, radioImport, hidden1]
            };

            var column2 = {
                xtype: 'panel',
                layout: 'form',
                colWidth: 0.20,
                items: [btnUpload, nofield, nofield, nofield]
            };
            var column3 = {
                xtype: 'panel',
                layout: 'form',
                labelWidth: 20,
                colWidth: 0.45,
                items: [labelHelp1, nofield, labelHelp2, nofield]
            };
            //form su cui montare gli elementi
            var formImportNote = new Ext.form.FormPanel({
                title: 'Importazione file di note (da caricare nello "Storico recupero")',
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
                        idLotto: 0,
                        handler: function(btn) {
                            var processName = Math.random();
                            if (!(Ext.getCmp('docPath1').getValue() > '')) {
                                Ext.Msg.alert('Errore', 'Selezionare un file');
                                return;
                            }
                            DCS.showMask('Invio file al server...');
                            formImportNote.getForm().submit({
                                url: 'server/processControl.php',
                                method: 'POST',
                                params: {
                                    task: 'importFile'
                                },
                                success: function(frm, action) {
                                    DCS.hideMask();
                                    showgridDettaglioProcesso(
                                        btn.idLotto,
                                        processName,
                                        action.result.data // array dei file paths
                                    );

                                },
                                failure: function(frm, action) {
                                    Ext.MessageBox.alert('Errore', action.result.error);
                                }
                            }); //fine submit formImport
                        },
                        scope: this
                    }
                    /*{text: 'Annulla',handler: function () {finestraImport.close();} 
                    }*/
                ] //fine array buttons
            }); //fine formImportNote
            Ext.apply(this, {
                items: [formImportNote]
            });

            return formImportNote;
        }
    }

}();

/*
 * Visualizzazione dettaglio del processo di import (funzione chiamata dal pulsante Esegui)
 */

function showgridDettaglioProcesso(IdLotto, processName, files) {
    var TipoImport = Ext.getCmp('TipoImportR').checked ? 'R' : 'A';

    var grid = new DCS.dettaglioProcesso(IdLotto, TipoImport, processName, files);

    var wnd = new Ext.Window({
        width: 1000,
        height: 600,
        layout: 'fit',
        stateful: false,
        plain: true,
        bodyStyle: 'padding:5px;',
        modal: true,
        title: 'Controllo import delle note',
        constrain: true,
        items: [grid]
    }); //fine window


    wnd.show();
    wnd.on({
        'beforeclose': function() {
            if (DCS.intervalDettaglioProcesso) {
                clearInterval(DCS.intervalDettaglioProcesso);
                DCS.intervalDettaglioProcesso = null;
                // Lancia il comando di interruzione del processo
                Ext.Ajax.request({
                    url: 'server/funzioniWizard.php',
                    params: {
                        task: 'interrompiProcesso',
                        processName: processName
                    },
                    method: 'POST',
                    success: function(result, request) {},
                    failure: function(result, request) {}
                });
            }
        }
    });
} //fine funzione showgridDettaglioProcesso