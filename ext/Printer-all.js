/**
 * @class GetIt.GridPrinter
 * @author Ed Spencer (edward@domine.co.uk)
 * Class providing a common way of printing Ext.Components. Ext.ux.Printer.print delegates the printing to a specialised
 * renderer class (each of which subclasses Ext.ux.Printer.BaseRenderer), based on the xtype of the component.
 * Each renderer is registered with an xtype, and is used if the component to print has that xtype.
 * 
 * See the files in the renderers directory to customise or to provide your own renderers.
 * 
 * Usage example:
 * 
 * var grid = new Ext.grid.GridPanel({
 *   colModel: //some column model,
 *   store   : //some store
 * });
 * 
 * Ext.ux.Printer.print(grid);
 * 
 */
Ext.ux.Printer = function() {
  return {
   /**
     * @property renderers
     * @type Object
     * An object in the form {xtype: RendererClass} which is manages the renderers registered by xtype
     */
    renderers: {},
    
    /**
     * Registers a renderer function to handle components of a given xtype
     * @param {String} xtype The component xtype the renderer will handle
     * @param {Function} renderer The renderer to invoke for components of this xtype
     */
    registerRenderer: function(xtype, renderer) {
      this.renderers[xtype] = new (renderer)();
    },
    
    /**
     * Returns the registered renderer for a given xtype
     * @param {String} xtype The component xtype to find a renderer for
     * @return {Object/undefined} The renderer instance for this xtype, or null if not found
     */
    getRenderer: function(xtype) {
      return this.renderers[xtype];
    },
    
    /**
     * Prints the passed grid. Reflects on the grid's column model to build a table, and fills it using the store
     * @param {Ext.Component} component The component to print
     */
    print: function(component,selezione) {
    	selezione = selezione || false;
		this.print_export(component,'HTML',0,selezione);
    },
    exportXLS: function(component,expAll,title) {
		this.print_export(component,'XLS',expAll,'',title);
    },
    print_export: function(component,ftype,expAll,selezione,title) {
      var xtypes = component.getXTypes().split('/');
     
      //iterate backwards over the xtypes of this component, dispatching to the most specific renderer
      for (var i = xtypes.length - 1; i >= 0; i--){
        var xtype    = xtypes[i],        
            renderer = this.getRenderer(xtype);

       if (renderer != undefined) {
	   	  renderer.funType = ftype; 
          renderer.print(component,selezione,expAll,title);
          break;
        }
      }
    }
	
  };
}();

/**
 * Override how getXTypes works so that it doesn't require that every single class has
 * an xtype registered for it.
 */
Ext.override(Ext.Component, {
  getXTypes : function(){
      var tc = this.constructor;
      if(!tc.xtypes){
          var c = [], sc = this;
          while(sc){ //was: while(sc && sc.constructor.xtype) {
            var xtype = sc.constructor.xtype;
            if (xtype != undefined) c.unshift(xtype);
            
            sc = sc.constructor.superclass;
          }
          tc.xtypeChain = c;
          tc.xtypes = c.join('/');
      }
      return tc.xtypes;
  }
});

/**
 * @class Ext.ux.Printer.BaseRenderer
 * @extends Object
 * @author Ed Spencer
 * Abstract base renderer class. Don't use this directly, use a subclass instead
 */
Ext.ux.Printer.BaseRenderer = Ext.extend(Object, {
	funType: 'HTML',
  /**
   * Prints the component
   * @param {Ext.Component} component The component to print
   */
  print: function(component) {
    var name = component && component.getXType
             ? String.format("print_{0}_{1}", component.getXType(), component.id)
             : "print";
             
    var win = window.open('', name);
    
    win.document.write(this.generateHTML(component, null));
    win.document.close();
    
    win.print();
    win.close();
  },
  
  /**
   * Generates the HTML Markup which wraps whatever this.generateBody produces
   * @param {Ext.Component} component The component to generate HTML for
   * @return {String} An HTML fragment to be placed inside the print window
   */
  generateHTML: function(component, storeData) {
    return new Ext.XTemplate(
      '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
      '<html>',
        '<head>',
          '<meta content="text/html; charset=UTF-8" http-equiv="Content-Type" />',
          '<link href="' + this.stylesheetPath + '" rel="stylesheet" type="text/css" media="screen,print" />',
          '<title>' + this.getTitle(component) + '</title>',
        '</head>',
        '<body onload="print(); close();">',
          this.generateBody(component),
        '</body>',
      '</html>'
    ).apply(this.prepareData(component, storeData));
  },
  
  /**
   * Generates the XLS Markup which wraps whatever this.generateBody produces
   * @param {Ext.Component} component The component to generate XLS for
   * @return {String} An HTML fragment to be placed inside the print window
   */
  generateXLS: function(component, storeData) {
  	var oggi = new Date();
	var data = oggi.format('Y-m-d');
	var ora  = oggi.format('H:i');

	var titolo = this.getTitle(component);
    return new Ext.XTemplate(
		'<?xml version="1.0" encoding="utf-8"?>',
		'<?mso-application progid="Excel.Sheet"?>',
		'<ss:Workbook xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:html="http://www.w3.org/TR/REC-html40">',
			'<o:DocumentProperties><o:Title>'+titolo+'</o:Title><o:Created>'+data+'T'+ora+'Z</o:Created></o:DocumentProperties>',
			'<ss:ExcelWorkbook>',
				'<ss:WindowHeight>9240</ss:WindowHeight>',
				'<ss:WindowWidth>50000</ss:WindowWidth>',
				'<ss:ProtectStructure>false</ss:ProtectStructure>',
				'<ss:ProtectWindows>false</ss:ProtectWindows>',
			'</ss:ExcelWorkbook>',
			'<ss:Styles>',
				'<ss:Style ss:ID="Default" ss:Name="Normal">',
					'<ss:Alignment ss:Vertical="Top" ss:WrapText="0" />',
					'<ss:Font ss:FontName="arial" ss:Size="10" />',
					'<ss:Interior />',
					'<ss:NumberFormat />',
					'<ss:Protection />',
					'<ss:Borders />',
				'</ss:Style>',
					'<ss:Style ss:ID="headercell">',
					'<ss:Font ss:Bold="1" ss:Size="10" />',
					'<ss:Interior ss:Pattern="Solid" ss:Color="#C0C0C0" />',
					'<ss:Alignment ss:WrapText="0" ss:Horizontal="Center" />',
				'</ss:Style>',
				'<ss:Style ss:ID="dec">',
					'<ss:NumberFormat ss:Format="[$-410]#,##0.00"/>',
				'</ss:Style>',
				'<ss:Style ss:ID="perc">',
					'<ss:NumberFormat ss:Format="0 %"/>',
				'</ss:Style>',
			'</ss:Styles>',
			'<ss:Worksheet ss:Name="'+titolo+'">',
				'<ss:Names><ss:NamedRange ss:Name="Print_Titles" ss:RefersTo="=\''+titolo+'\'!R1:R1" /></ss:Names>',

          		this.generateBodyXLS(component),
		  
				'<x:WorksheetOptions>',
					'<x:PageSetup>',
						'<x:Layout x:CenterHorizontal="1" x:Orientation="Landscape" />',
						'<x:Footer x:Data="Page &amp;P of &amp;N" x:Margin="0.5" />',
						'<x:PageMargins x:Top="0.5" x:Right="0.5" x:Left="0.5" x:Bottom="0.8" />',
					'</x:PageSetup>',
					'<x:Print>',
						'<x:PrintErrors>Blank</x:PrintErrors>',
						'<x:FitWidth>1</x:FitWidth>',
						'<x:FitHeight>32767</x:FitHeight>',
						'<x:ValidPrinterInfo />',
						'<x:VerticalResolution>600</x:VerticalResolution>',
					'</x:Print>',
					'<x:FitToPage /><x:Selected />', //<x:DoNotDisplayGridlines />',
					'<x:ProtectObjects>False</x:ProtectObjects>',
					'<x:ProtectScenarios>False</x:ProtectScenarios>',
				'</x:WorksheetOptions>',
			'</ss:Worksheet>',
		'</ss:Workbook>',
		{
	        // XTemplate configuration:
	        compiled: false,
	        disableFormats: true,
	        // member functions:
			convData: function(v) {
//				'<ss:Cell><tpl if="values.align==\'right\'"><ss:Data ss:Type="Number">{[this.prepareConv(\'{dataIndex}\')]}</ss:Data></tpl>'
				if (v.match(/^((\d{1,3}\.(\d{3}\.)*\d{3}|\d{1,3}),\d+)$/)) { // numero decimale con separatori italiani
					return '<ss:Cell ss:StyleID="dec"><ss:Data ss:Type="Number">' + v.replace('.', '').replace(',', '.') + '</ss:Data></ss:Cell>'
				} else {
					if (v.match(/\d( )*%/)) { // numero percentuale
						return '<ss:Cell ss:StyleID="perc"><ss:Data ss:Type="Number">' + String(parseFloat(v.replace('%', '').replace(',', '.'))/100.0) + '</ss:Data></ss:Cell>'
					}
					else {
						return '<ss:Cell><ss:Data ss:Type="Number">' + v + '</ss:Data></ss:Cell>'
					}
				}
	        }
	    }
    ).apply(this.prepareData(component, storeData));
  },

  /**
   * Returns the HTML that will be placed into the print window. This should produce HTML to go inside the
   * <body> element only, as <head> is generated in the print function
   * @param {Ext.Component} component The component to render
   * @return {String} The HTML fragment to place inside the print window's <body> element
   */
  generateBody: Ext.emptyFn,

  generateBodyXLS: Ext.emptyFn,
  
  /**
   * Prepares data suitable for use in an XTemplate from the component 
   * @param {Ext.Component} component The component to acquire data from
   * @return {Array} An empty array (override this to prepare your own data)
   */
  prepareData: function(component, storeData) {
    return component;
  },
  
  /**
   * Returns the title to give to the print window
   * @param {Ext.Component} component The component to be printed
   * @return {String} The window title
   */
  getTitle: function(component) {
  	var titolo = typeof component.getTitle == 'function' ? component.getTitle() : component.title;
	if (titolo === undefined || titolo.trim() == "")
		if (component.ownerCt)
			titolo = component.ownerCt.title;
	if (titolo === undefined || titolo.trim() == "")
    	titolo = this.funType=='HTML'?"Stampa":"Foglio";
	return titolo;
  },
  
  /**
   * @property stylesheetPath
   * @type String
   * The path at which the print stylesheet can be found (defaults to 'stylesheets/print.css')
   */
  stylesheetPath: 'css/print.css'
});

/**
 * @class Ext.ux.Printer.ColumnTreeRenderer
 * @extends Ext.ux.Printer.BaseRenderer
 * @author Ed Spencer
 * Helper class to easily print the contents of a column tree
 */
Ext.ux.Printer.ColumnTreeRenderer = Ext.extend(Ext.ux.Printer.BaseRenderer, {

  /**
   * Generates the body HTML for the tree
   * @param {Ext.tree.ColumnTree} tree The tree to print
   */
  generateBody: function(tree) {
    var columns = this.getColumns(tree);
    
    //use the headerTpl and bodyTpl XTemplates to create the main XTemplate below
    var headings = this.headerTpl.apply(columns);
    var body     = this.bodyTpl.apply(columns);
    
    return String.format('<table>{0}<tpl for=".">{1}</tpl></table>', headings, body);
  },
    
  /**
   * Returns the array of columns from a tree
   * @param {Ext.tree.ColumnTree} tree The tree to get columns from
   * @return {Array} The array of tree columns
   */
  getColumns: function(tree) {
    return tree.columns;
  },
  
  /**
   * Descends down the tree from the root, creating an array of data suitable for use in an XTemplate
   * @param {Ext.tree.ColumnTree} tree The column tree
   * @return {Array} Data suitable for use in the body XTemplate
   */
  prepareData: function(tree) {
    var root = tree.root,
        data = [],
        cols = this.getColumns(tree),
        padding = this.indentPadding;
        
    var f = function(node) {
      if (node.hidden === true || node.isHiddenRoot() === true) return;
      
      var row = Ext.apply({depth: node.getDepth() * padding}, node.attributes);
      
      Ext.iterate(row, function(key, value) {
        Ext.each(cols, function(column) {
          if (column.dataIndex == key) {
            row[key] = column.renderer ? column.renderer(value) : value;
          }
        }, this);        
      });
      
      //the property used in the first column is renamed to 'text' in node.attributes, so reassign it here
      row[this.getColumns(tree)[0].dataIndex] = node.attributes.text;
      
      data.push(row);
    };
    
    root.cascade(f, this);
    
    return data;
  },
  
  /**
   * @property indentPadding
   * @type Number
   * Number of pixels to indent node by. This is multiplied by the node depth, so a node with node.getDepth() == 3 will
   * be padded by 45 (or 3x your custom indentPadding)
   */
  indentPadding: 15,
  
  /**
   * @property headerTpl
   * @type Ext.XTemplate
   * The XTemplate used to create the headings row. By default this just uses <th> elements, override to provide your own
   */
  headerTpl:  new Ext.XTemplate(
    '<tr>',
      '<tpl for=".">',
        '<th width="{width}">{header}</th>',
      '</tpl>',
    '</tr>'
  ),
 
  /**
   * @property bodyTpl
   * @type Ext.XTemplate
   * The XTemplate used to create each row. This is used inside the 'print' function to build another XTemplate, to which the data
   * are then applied (see the escaped dataIndex attribute here - this ends up as "{dataIndex}")
   */
  bodyTpl:  new Ext.XTemplate(
    '<tr>',
      '<tpl for=".">',
        '<td style="padding-left: {[xindex == 1 ? "\\{depth\\}" : "0"]}px">\{{dataIndex}\}</td>',
      '</tpl>',
    '</tr>'
  )
});

Ext.ux.Printer.registerRenderer('columntree', Ext.ux.Printer.ColumnTreeRenderer);

/**
 * @class Ext.ux.Printer.GridPanelRenderer
 * @extends Ext.ux.Printer.BaseRenderer
 * @author Ed Spencer
 * Helper class to easily print the contents of a grid. Will open a new window with a table where the first row
 * contains the headings from your column model, and with a row for each item in your grid's store. When formatted
 * with appropriate CSS it should look very similar to a default grid. If renderers are specified in your column
 * model, they will be used in creating the table. Override headerTpl and bodyTpl to change how the markup is generated
 */
Ext.ux.Printer.GridPanelRenderer = Ext.extend(Ext.ux.Printer.BaseRenderer, {
	print: function(grid,selezione,expAll,title) {
		if (this.funType != 'XLS' && (selezione || grid.store.getTotalCount() == grid.store.getCount())) {
			// Una sola pagina: non serve rileggere i dati
			this.printData(grid, grid.store.data);
		} else {
			if (this.funType == 'XLS') {
				// Piu' pagine per export Excel: generazione lato server
				this.exportFromServer(grid,expAll,title);
			} else {
				// Piu' pagine (per stampa): va letto tutto il datastore
				var pstore = new Ext.data.GroupingStore({
					autoDestroy: true,
					proxy: grid.store.proxy,
					baseParams:grid.store.baseParams,
					remoteSort: true,
					sortInfo: grid.store.sortInfo,
					reader: grid.store.reader
			  	});
					
				var par = Ext.apply(grid.store.lastOptions.params,{start:0, limit:0});
				pstore.load({
					params: par,
					callback: function(r,options,success) {
						if (success) {
						    this.printData(grid, pstore.data);
						}
					}, scope:this
				});
			}
		}
	},

	exportFromServer: function(grid,expAll,title) {
		var me = this;
		// Determina se la griglia ha righe selezionate (se non si tratta di un tasto "Esporta tutto")
		if (!expAll && grid.getSelectionModel && grid.getSelectionModel() && grid.getSelectionModel().getSelections) {
			var selectedRows = grid.getSelectionModel().getSelections();
			if (selectedRows.length>0) {
				// PROCEDE SOLO SE I RECORD CONTENGONO IDCONTRATTO
				if (selectedRows[0].get('IdContratto')>'') {
					Ext.Msg.show({
						title:'Conferma esportazione',
						msg: 'Vuoi estrarre le sole righe selezionate oppure l\'intero elenco?',
						buttons: {
							yes: 'Righe selezionate',
							no: 'Intero elenco',
							cancel : 'Esci'
						},
						fn: function(btn, text){
							var keyvalues = [];
							if(btn == 'yes'){ // scelto di esportare solo quelli selezionati
								keyvalues = [];
								for (var i=0; i<selectedRows.length; i++) {
									keyvalues.push(selectedRows[i].get('IdContratto')); 
								}
							}else if(btn == 'no' ){
							}else{
								return;
							}		
							me.continueExport(grid,expAll,title,keyvalues);
						}
					});
				} else {
					me.continueExport(grid,expAll,title,null);
				}
			} else {
				me.continueExport(grid,expAll,title,null);
			}
		} else {
			me.continueExport(grid,expAll,title,null);
		}
	},
	
	continueExport: function(grid,expAll,title,keyvalues) {
		var columns = this.getColumns(grid);
		var data = [];
 		Ext.each(columns, function(column) {
			data.push({
				xtype: column.xtype,
				align: column.align,
				dataIndex: column.dataIndex,
				format: column.format,
				header: (column.header||'').replace(/<br>/g,' '),
				width: column.width
			});
		});

		var escapeForm=new Ext.form.FormPanel({
		    standardSubmit: true,
		    renderTo: Ext.getBody(),
		    hidden: true, floating: true,
		    defaults: {xtype: 'hidden'},
		    items: [
		        {name: 'titolo',		value: title>''?title:this.getTitle(grid)},
		        {name: 'filename',		value: title>''? title:(grid.titlePanel>''?grid.titlePanel:grid.ownerCt.title)},
		        {name: 'url',			value: grid.store.proxy.url},
				{name: 'baseParams',	value: Ext.encode(grid.store.baseParams)},
				{name: 'columns',		value: Ext.encode(data)},
				{name: 'selected',		value: Ext.encode(keyvalues)},
				{name: 'expAll',		value: expAll}
		    ],
		    url: 'server/export.php'
		});
		var frm = escapeForm.getForm();
		//frm.getEl().dom.target='_blank';
		frm.submit();
		//escapeForm.destroy();
	},

	printData: function(grid, storeData) {
		if (this.funType=='XLS') {
			var pagina = this.generateXLS(grid, storeData);

			var escapeForm=new Ext.form.FormPanel({
			    standardSubmit: true,
			    renderTo: Ext.getBody(),
			    hidden: true, floating: true,
			    defaults: {xtype: 'hidden'},
			    items: [
			        {name: 'filename',  value: grid.titlePanel},
			        {name: 'xls',  value: pagina}
			    ],
			    url: 'server/export.php'
			});
			var frm = escapeForm.getForm();
			frm.getEl().dom.target='_blank';
			frm.submit();
			escapeForm.destroy();
		} else {
			var userAgent=navigator.userAgent.toLowerCase();
			//var isFirefox=/firefox/.test(userAgent);
			var isChrome=/chrome/.test(userAgent);
			var pagina = this.generateHTML(grid, storeData);
			if(isChrome){
				var win = window.open('', 'print_grid');
				win.document.write(pagina);
				//win.document.close();
				this.doPrintOnStylesheetLoad.defer(10, this, [win]);
			}else{
		    	var win = window.open('', 'print_grid');
		    	win.document.write(pagina);
			    win.document.close();
			}
	    	
//			win.setTimeout("print();close();",2000);
		}
	},
	doPrintOnStylesheetLoad: function(win) {
		var el = win.document.getElementById('csscheck'),
		    comp = el.currentStyle || getComputedStyle(el, null);
		if (comp.display !== "none") {
			this.doPrintOnStylesheetLoad.defer(10, this, [win]);
			return;
		}
		console.log("end call");
		win.print();
		//win.close();
	},
  /**
   * Generates the body HTML for the grid
   * @param {Ext.grid.GridPanel} grid The grid to print
   */
  generateBody: function(grid) {
    var columns = this.getColumns(grid);
    
    //use the headerTpl and bodyTpl XTemplates to create the main XTemplate below
    var headings = this.headerTpl.apply(columns);
    var body     = this.bodyTpl.apply(columns);
    
    return String.format('<table>{0}<tpl for=".">{1}</tpl></table>', headings, body);
  },

  /**
   * Generates the body XLS for the grid
   * @param {Ext.grid.GridPanel} grid The grid to print
   */
  generateBodyXLS: function(grid) {
    var columns = this.getColumns(grid);
    
    //use the headerTpl and bodyTpl XTemplates to create the main XTemplate below
    var headings = this.headerTplXLS.apply(columns);
    var body     = this.bodyTplXLS.apply(columns);
    
	var nRows = 1 + grid.getStore().getTotalCount();
    return String.format('<ss:Table x:FullRows="1" x:FullColumns="1" ss:ExpandedColumnCount="' +
			columns.length+'" ss:ExpandedRowCount="'+ nRows + 
			'">{0}<tpl for=".">{1}</tpl></ss:Table>', headings, body);
  },

  /**
   * Prepares data from the grid for use in the XTemplate
   * @param {Ext.grid.GridPanel} grid The grid panel
   * @return {Array} Data suitable for use in the XTemplate
   */
  prepareData: function(grid, storeData) {
    //We generate an XTemplate here by using 2 intermediary XTemplates - one to create the header,
    //the other to create the body (see the escaped {} below)
    var columns = this.getColumns(grid);
  
    var data = [];
	storeData.each(function(item) {
		var convertedData = {};
	      
		//apply renderers from column model
		Ext.iterate(item.data, function(key, value) {
			Ext.each(columns, function(column) {
				if (column.dataIndex == key) {
					convertedData[key] = column.renderer ? column.renderer(value, null, item) : value;
					return false;
				}
			}, this);
		});
		
		data.push(convertedData);
	});
    
    return data;
  },
  
  /**
   * Returns the array of columns from a grid
   * @param {Ext.grid.GridPanel} grid The grid to get columns from
   * @return {Array} The array of grid columns
   */
  getColumns: function(grid) {
    var columns = [];
    
  	Ext.each(grid.getColumnModel().config, function(col) {
		if (col.printable!==false && (
				(this.funType=='XLS' && col.exportable!==false) ||
  	  			(this.funType!='XLS' && col.hidden!==true))
			)
		columns.push(col);
  	}, this);
  	
  	return columns;
  },
  
  /**
   * @property headerTpl
   * @type Ext.XTemplate
   * The XTemplate used to create the headings row. By default this just uses <th> elements, override to provide your own
   */
  headerTpl:  new Ext.XTemplate(
    '<tr>',
      '<tpl for=".">',
        '<th>{header}</th>',
      '</tpl>',
    '</tr>'
  ),
  
  headerTplXLS:  new Ext.XTemplate(
	'<tpl for="."><ss:Column ss:AutoFitWidth="1" ss:Width="{width}"/></tpl>',
	'<ss:Row ss:AutoFitHeight="1">',
		'<tpl for=".">',
        	'<ss:Cell ss:StyleID="headercell"><ss:Data ss:Type="String">{header}</ss:Data><ss:NamedCell ss:Name="Print_Titles" /></ss:Cell>',
		'</tpl>',
    '</ss:Row>'
  ),

   /**
    * @property bodyTpl
    * @type Ext.XTemplate
    * The XTemplate used to create each row. This is used inside the 'print' function to build another XTemplate, to which the data
    * are then applied (see the escaped dataIndex attribute here - this ends up as "{dataIndex}")
    */
  bodyTpl:  new Ext.XTemplate(
	'<div id="csscheck"></div>',
    '<tr>',
      '<tpl for=".">',
        '<td>\{{dataIndex}\}</td>',
      '</tpl>',
    '</tr>'
  ),

  bodyTplXLS:  new Ext.XTemplate(
    '<ss:Row>',
      '<tpl for=".">',
        '<tpl if="values.align==\'right\'">{[this.prepareConv([\'{dataIndex}\'].join(""))]}</tpl>',
		'<tpl if="values.align!=\'right\'">',
			'<ss:Cell><ss:Data ss:Type="String"><![CDATA[\{{dataIndex}\}]]></ss:Data></ss:Cell>',
		'</tpl>',
      '</tpl>',
    '</ss:Row>',
		{
        // XTemplate configuration:
        compiled: false,
        disableFormats: true,
        // member functions:
		prepareConv: function(v) {
			return '\{\[this.convData([\'\{'+v+'\}\'].join(""))\]\}';
        }
    }

  )
});

Ext.ux.Printer.registerRenderer('grid', Ext.ux.Printer.GridPanelRenderer);

/**
 * @class Ext.ux.Printer.TreePanelRenderer
 * @extends Ext.ux.Printer.BaseRenderer
 * @author G. Di Falco
 * Usato SOLO per l'Export del treepanel delle note
 */
Ext.ux.Printer.TreePanelRenderer = Ext.extend(Ext.ux.Printer.BaseRenderer, {
	print: function(tree) {
		this.exportFromServer(tree);
	},

	exportFromServer: function(tree) {
		var data = [ // campi della view v_note_per_export
		    {dataIndex:'DataOra',	header:'Data creazione',width:100},
		    {dataIndex:'TipoNota',	header:'Tipo',width:60},
		    {dataIndex:'Mittente',	header:'Mittente',width:80},
		    {dataIndex:'Destinatario',	header:'Destinatario',width:80},
		    {dataIndex:'TestoNota',	header:'Testo nota',width:400}
		    ];

		var escapeForm=new Ext.form.FormPanel({
		    standardSubmit: true,
		    renderTo: Ext.getBody(),
		    hidden: true, floating: true,
		    defaults: {xtype: 'hidden'},
		    items: [
		        {name: 'titolo',		value: 'Lista note'}, // nome del worksheet (non deve essere troppo lungo)
		        {name: 'filename',		value: tree.title},
		        {name: 'url',			value: tree.url},
				{name: 'baseParams',	value: Ext.encode(tree.params)},
				{name: 'columns',		value: Ext.encode(data)}
		    ],
		    url: 'server/export.php'
		});
		var frm = escapeForm.getForm();
		frm.getEl().dom.target='_blank';
		frm.submit();
		escapeForm.destroy();
	}
});

Ext.ux.Printer.registerRenderer('treepanel', Ext.ux.Printer.TreePanelRenderer);