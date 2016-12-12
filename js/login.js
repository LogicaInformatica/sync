Ext.onReady(function(){
    Ext.QuickTips.init();
 
    // Funzione per il submit   	
    var submitFunction = function(){ 
    	if (login.handling)
    		login.handling = false;
    	else
    	{
    		login.handling=true;
    		login.getForm().submit({ 
    			method:'POST', 
    			waitTitle:'Autenticazione', 
    			waitMsg:'Invio dati...',
				success:function(){
  						Ext.Msg.wait('Accesso in corso...','Autenticazione&nbsp;riuscita');
  						var ps = location.search;
   						window.location = (ps==''?'main.php':ps.slice(3));
                    	},

               	failure:function(form, action){ 
                   		if(action.failureType == 'server'){ 
                   			obj = Ext.util.JSON.decode(action.response.responseText); 
                   			Ext.Msg.alert('Autenticazione&nbsp;fallita', obj.errors.reason); 
                   		}else{ 
                   			Ext.Msg.alert('Attenzione', 'Server irraggiungibile : ' + action.response.responseText); 
                   		}
                   		login.getForm().setValues({loginPassword: ''});
                   	} 	
			}); // fine parametri della .submit
		} // fine else
      }; // fine submitFunction

      
    // Create a variable to hold our EXT Form Panel. 
	// Assign various config options as seen.	 
    var login = new Ext.FormPanel({ 
        labelWidth:100,
		url:'main.php', 
//        url:'server/login.php', 
        frame:true, 
        title:'Login', 
        defaultType:'textfield',
		monitorValid:true,
		handling: false, // creata per tener conto dell'esecuzione dell'Enter con il focus sul bottone
standardSubmit: true,
		defaults: {width: 160},
		// Specific attributes for the text fields for username / password. 
		// The "name" attribute defines the name of variables sent to the server.
        items:[{ 
				id: 'username',
                fieldLabel:'Nome Utente', 
                name:'loginUsername', 
                allowBlank:false
            },{ 
                fieldLabel:'Password', 
                name:'loginPassword', 
                inputType:'password', 
                allowBlank:true	//false 
            }],
            keys: {	
            	 key: Ext.EventObject.ENTER,
            	 fn: submitFunction,  
            	 scope:this
            	},
            buttons:[{ 
        		id:'submitButton',
                text:'Entra',
                formBind: true,	 
                handler: submitFunction
            }],    // fine array buttons
            tools: [helpTool("Accessoallapplicazionelogin")]
    });
 

            
	// This just creates a window to wrap the login form. 
	// The login object is passed to the items collection.       
    var win = new Ext.Window({
    	modal: true,
        layout:'fit',
        width:300,
        height:150,
        closable: false,
        resizable: false,
        plain: true,
        border: false,
		constrain: true,
		defaultButton: 'username',
        items: [login]
	});
	win.show();
	
});