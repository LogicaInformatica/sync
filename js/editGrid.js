function EditGrid(table) {

	// Variabili private
	var _table = table;
	var server_script = 'server/edit_decodifica.php';
	// --------- Hide delete button -------------------------------------
	var _hdnBtnDelete;
		if (_table.hdnBtnDelete !="" || _table.hdnBtnDelete !=undefined )
			_hdnBtnDelete = _table.hdnBtnDelete
		else
			_hdnBtnDelete = false
	// --------- Hide add button -------------------------------------
	var _hdnBtnAdd;
		if (_table.hdnBtnAdd !="" || _table.hdnBtnAdd !=undefined )
			_hdnBtnAdd = _table.hdnBtnAdd
		else
			_hdnBtnAdd = false


	//------------------------- Reader -------------------------
	var _reader = new Ext.data.JsonReader ({
			root: 'results',
			totalProperty: 'total',
			idProperty: _table.pk
		},
		_table.record 
	);

	//------------------------- Store -------------------------
//	var _store = new Ext.data.GroupingStore ({	//if grouping
	var _store = new Ext.data.Store({			//if not grouping
		proxy: new Ext.data.HttpProxy({
		url: server_script,			//url to data object (server side script)
		method: 'POST'
		}),
		baseParams:{
			task: "readTable", 
			table: (_table.view==undefined?_table.name:_table.view)
		},
		autoDestroy: true,
		reader: _reader,
// groupField:'...',
		remoteSort: true
	});


	var _filters;		//definition of filter plugin
	var _grid;			//the grid component (object)

	//------------------------------------------
	// Costruisce la griglia e carica lo store
	//------------------------------------------
	buildGrid();			//Build the Grid
	loadStore();			//Load the Store

	//private method
	function buildGrid() {
		/**
		* Handler for Adding a Record
		*/
		function addRecord() {
			var r = _table.newRecord();
			_grid.stopEditing();//stops any active editing

			//very similar to _store.add, with _store.insert we can specify
			//the insertion point
			_store.insert(0, r);		//1st arg is index, 2nd arg is Ext.data.Record[] records

			//start editing the specified rowIndex, colIndex
			//make sure you pick an editable location
			//otherwise it won't initiate the editor
			_grid.startEditing(0, 2);
		}; // end addRecord 

		/**
		* Function for Deleting record(s)
		* @param {Object} btn
		*/ 
		function deleteRecord(btn) {
			if(btn=='yes') {
				/* block of code if we just want to remove 1 row
				var selectedRow = grid.getSelectionModel().getSelected();	/returns record object for the most recently selected
																			//row that is in data store for grid
				if(selectedRow){
					_store.remove(selectedRow);
				} //end of block to remove 1 row
				*/
				
				//returns record objects for selected rows (all info for row)
				var selectedRows = _grid.selModel.selections.items;
				
				//returns array of selected rows ids only
				var selectedKeys = _grid.selModel.selections.keys; 

				//note we already did an if(selectedKeys) to get here

				//encode array into json
				var encoded_keys = Ext.encode(selectedKeys);

				//submit to server
				Ext.Ajax.request( //alternative to Ext.form.FormPanel? or Ext.BasicForm.submit
					{	//specify options (note success/failure below that receives these same options)
						waitMsg: 'Salvataggio in corso...',
						//url where to send request (url to server side script)
						url: server_script,
						
						//params will be available via $_POST or $_REQUEST:
						params: { 
							task: "delete",		//pass task to do to the server script
							table: _table.name,
							id: encoded_keys,		//the unique id(s)
							key: _table.pk			//pass to server same 'id' that the reader used
						},

						/**
						 * You can also specify a callback (instead of or in
						 * addition to success/failure) for custom handling.
						 * If you have success/failure defined, those will
						 * fire before 'callback'.  This callback will fire
						 * regardless of success or failure.*/
						callback: function (options, success, response) {
							if (success) { //success will be true if the request succeeded
//								Ext.MessageBox.alert('OK',response.responseText);//you won't see this alert if the next one pops up fast
								var json = Ext.util.JSON.decode(response.responseText);

								//need to move this to an after event because
								//it will fire before the grid is re-rendered
								//(while the deleted row(s) are still there
								var msg = json.del_count + '';
								msg += (json.del_count==1)?' riga cancellata.':' righe cancellate.';
								Ext.MessageBox.alert('OK',msg);

								//You could update an element on your page with
								//the result from the server
								//(e.g.<div id='total'></div>)
								//var total = Ext.get('total');
								//total.update(json.sum);
							} else {
								Ext.MessageBox.alert('Prego, provare di nuovo. ',response.responseText);
							}
						},

						//the function to be called upon failure of the request (server script, 404, or 403 errors)
						failure:function(response,options){
//							Ext.MessageBox.alert('Attenzione','Oops...');
							//_store.rejectChanges();//undo any changes
						},                                      
						success:function(response,options){
							//Ext.MessageBox.alert('Success','Yeah...');
							//commit changes and remove the red triangle which
							//indicates a 'dirty' field
							_store.reload();
						}                                      
					} //end Ajax request config
				);// end Ajax request initialization
			};//end if click 'yes' on button
		}; // end deleteRecord 


		/**
		* Handler for Deleting record(s)
		*/ 
		function handleDelete() {
			//returns array of selected rows ids only
			var selectedKeys = _grid.selModel.selections.keys;
			if(selectedKeys.length > 0) {
				Ext.MessageBox.confirm('Messaggio','Confermate la cancellazione delle righe selezionate?', deleteRecord);
			} else {
				Ext.MessageBox.alert('Messaggio','Selezionare almeno una riga da cancellare');
			}//end if/else block
		}; // end handleDelete 


		/**
		 * Handler to control grid editing
		 * @param {Object} oGrid_Event
		 */
		function handleEdit(editEvent) {
			//start the process to update the db with cell contents
			console.log("editing");
			updateDB(editEvent);
		}

		/**
		* Function for updating database
		* @param {Object} oGrid_Event
		*/
		function updateDB(oGrid_Event) {
 			var insOp = (oGrid_Event.record.data[_table.pk] == 0);
			if (insOp) {
				var campi = new Array();
				var valori = new Array();
				oGrid_Event.record.fields.eachKey(
					function(k,i) {
						if (k != _table.pk) {
							var v = oGrid_Event.record.data[k];
							if (v != undefined) {
								campi.push("`"+k+"`");
								if (v=='') {
									valori.push(null);
								} else {
									if (v instanceof Date)
										v = v.format('Y-m-d H:i:s');
//									else 
// SCA									if (v instanceof String)
//											v = '"'+ v.replace(/\"/g,'\\"') +'"';
									valori.push(v);
								}
							}
						}
					}
				);
				campi = campi.join();
				valori = valori.join("|");
			} else {
				var campi = oGrid_Event.field;
				var valori = (oGrid_Event.value instanceof Date)?oGrid_Event.value.format('Y-m-d H:i:s'):oGrid_Event.value;
			}
			//submit to server
			Ext.Ajax.request ( //alternative to Ext.form.FormPanel? or Ext.BasicForm
				{   //Specify options (note success/failure below that
					//receives these same options)
					waitMsg: 'Salvataggio in corso...',
					//url where to send request (url to server side script)
					url: server_script, 

					//If specify params default is 'POST' instead of 'GET'
					//method: 'POST', 
					
					//params will be available server side via $_POST or $_REQUEST:
					params: { 
						task: "update", //pass task to do to the server script
						table: _table.name,
						key: _table.pk,//pass to server same 'id' that the reader used

						//For existing records this is the unique id (we need
						//this one to relate to the db). We'll check this
						//server side to see if it is a new record                    
						keyValue: oGrid_Event.record.data[_table.pk],

						//For new records Ext creates a number here unrelated
						//to the database
						//-bogusID: oGrid_Event.record.id,

						fields: campi,	//the column names
						values: valori	//the updated values

						//The original value (oGrid_Event.orginalValue does
						//not work for some reason) this might(?) be a way
						//to 'undo' changes other than by cookie? When the
						//response comes back from the server can we make an
						//undo array?                         
//						, originalValue: oGrid_Event.record.modified
						
					},//end params

					//the function to be called upon failure of the request
					//(404 error etc, ***NOT*** success=false)
					failure:function(response,options){
//						Ext.MessageBox.alert('Warning','Oops...');
						//_store.rejectChanges();//undo any changes
					},//end failure block      

					//The function to be called upon success of the request                                
					success:function(response,options){
//						Ext.MessageBox.alert('Success','Yeah...');
						var responseData = Ext.util.JSON.decode(response.responseText);//passed back from server
						if (responseData.success) {
							//if this is a new record need special handling
							if (insOp) {
								//Extract the ID provided by the server
								var newID = responseData.newID;
								//oGrid_Event.record.id = newID;
								
								//Reset the indicator since update succeeded
								oGrid_Event.record.set('newRecord','no');
								
								//Assign the id to the record
								oGrid_Event.record.set(_table.pk,newID);
								oGrid_Event.record.id = newID;								//Note the set() calls do not trigger everything
								//since you may need to update multiple fields for
								//example. So you still need to call commitChanges()
								//to start the event flow to fire things like
								//refreshRow()
								
								//commit changes (removes the red triangle which
								//indicates a 'dirty' field)
								_store.commitChanges();

								//var whatIsTheID = oGrid_Event.record.modified;

								//not a new record so just commit changes	
							} else {
								//commit changes (removes the red triangle
								//which indicates a 'dirty' field)
								_store.commitChanges();
							}
							_grid.selModel.clearSelections();
							_grid.getView().refresh();
						}
					}//end success block
				}//end request config
			); //end request  
		}; //end updateDB 


	///////////////////////////////////////////////////////////////////////////

		// colonna combo di selezione
		var check_sm = _table.colModel.getColumnById('checker');

		_grid = new Ext.grid.EditorGridPanel({
			hideMode: 'offsets',
			store: _store,
		    autoHeight: false, //true,
			flex: 1,
//			frame: true,
			autoExpandColumn: _table.expandCol,
			loadMask: true,//use true to mask the grid while loading (default = false)
			//Enable a Selection Model.  The Selection Model defines the selection behavior,
			//(single vs. multiple select, row or cell selection, etc.)
			selModel: check_sm===undefined?new Ext.grid.RowSelectionModel({singleSelect:false}):check_sm,
			view: new Ext.grid.GridView({
//			view: new Ext.grid.GroupingView({
				autoFill: true,
				forceFit: false,
				markDirty: true //false
			}),
			tbar: [{
				text: 'Aggiungi',
				tooltip: 'Aggiunge una nuova riga',
				iconCls:'grid-add', 
				handler: addRecord,
				disabled: _hdnBtnAdd
			}, '-', { //add a separator
				ref: '../removeBtn',
				text: 'Elimina',
				tooltip: 'Elimina le righe selezionate',
				iconCls:'grid-remove', 
				handler: handleDelete,
				disabled: _hdnBtnDelete 
			}, '-', { //add a separator
				ref: '../editBtn',
				text: 'Modifica',
				tooltip: 'Modifica la riga selezionata',
				iconCls:'grid-edit', 
				hidden: true,
				disabled: true 
			}],
			// paging bar on the bottom
			bbar: new Ext.PagingToolbar({
				pageSize: PAGESIZE,
				store: _store,
				displayInfo: true,
				displayMsg: 'Righe {0} - {1} di {2}',
				emptyMsg: "Nessun risultato"
			}),
			colModel: _table.colModel
		});
		_grid.colModel.columns

		/**
		* Add an event/listener to handle any updates to grid
		*/ 
		_grid.addListener('afteredit', handleEdit);//give event name, handler (can use 'on' shorthand for addListener) 

		_grid.getSelectionModel().on('selectionchange', function(sm) {
			if(_hdnBtnDelete != true)
				_grid.removeBtn.setDisabled(sm.getCount() < 1);
			
			_grid.editBtn.setDisabled(sm.getCount() != 1);		// eventualmente aggiunto esternamente
		});

	}

	function loadStore() {
		_store.load({
			params: { //this is only parameters for the FIRST page load, use baseParams above for ALL pages.
				table: (_table.view==undefined?_table.name:_table.view),
				start: 0, //pass start/limit parameters for paging
				limit: PAGESIZE
			}
		}); 
	}
 
 
	//public method
	this.getGrid = function(){
		return _grid;
	};
	
	this.refresh = function(){
		_grid.getBottomToolbar().doRefresh();
	};
}
