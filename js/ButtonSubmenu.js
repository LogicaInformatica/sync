/*
 * ButtonSubmenu.js
 * 
 * Bottone dei sottomenu del navigatore.
 */

// Crea namespace DCS
Ext.namespace('DCS');

DCS.Navigator = Ext.extend(Ext.Panel, {
    initComponent: function() {

	    Ext.apply(this, {
			layout: 'accordion',
			layoutConfig: {hideCollapseTool: true},
			activeItem: 0
		});
	
	    DCS.Navigator.superclass.initComponent.call(this);

		this.addEvents('clickNavElem');
    }
});


DCS.Menu = Ext.extend(Ext.Panel, {
    initComponent: function() {
        Ext.apply(this, {
			layout: 'vbox',
			layoutConfig: {align: 'stretch', flex: 0},
    		headerStyle: 'font-weight: bold',
    		listeners: {
    			'beforeexpand': {
    				fn: this.toggleFirstSubmenu,
    				scope: this
    			}, 
				'added': {
    				fn: function(c1, c2, n) {
						return true;
					},
    				scope: this 
				}
    		}			
   		});
        DCS.Menu.superclass.initComponent.call(this);
    },
    toggleFirstSubmenu : function(p, anim) {
		p.items.get(0).toggle(true);
		return true;
    }
});

DCS.ButtonSubmenu = Ext.extend(Ext.Button, {
	// pannello da attivare nel contenitore 
	panel : null,
	param: null,
    initComponent: function() {
        Ext.apply(this, {
			cls: 'btn-submenu',
			enableToggle: true,
			allowDepress: false,
			toggleGroup: 'Menu',
			panelCmp : null,
			toggleHandler: function(btn, status) {
				if ((this.panelCmp == null || this.panelCmp.alwaysRefresh) && status) {
					if (this.panel instanceof Function) {
						if (this.param)
							btn.panelCmp = this.panel.call(null,this.param); // NB: il primo argomento è l'oggetto di contesto della funzione, che non serve
						else
							btn.panelCmp = this.panel.call();
					}
					else 
						btn.panelCmp = this.panel;
				}
				Ext.getCmp("navigatore").fireEvent('clickNavElem', this.panelCmp, btn, status);

//				this.ownerCt.ownerCt.fireEvent('clickNavElem', this.panelCmp, btn, status);
			}
        });

        DCS.ButtonSubmenu.superclass.initComponent.call(this);
    }
});

// register xtype to allow for lazy initialization
Ext.reg('btnsubmenu', DCS.ButtonSubmenu);

DCS.ButtonSubMain = Ext.extend(Ext.Button, {
	// pannello da attivare nel contenitore 
	panel : null,
	param: null,
	
    initComponent: function() {
        Ext.apply(this, {
			cls: 'btn-submain',
			enableToggle: true,
			allowDepress: false,
			toggleGroup: 'Menu',
			panelCmp : null,

			toggleHandler: function(btn, status) {
				if (this.panelCmp == null && status) {
					if (this.panel instanceof Function) {
						if (this.param)
							btn.panelCmp = this.panel.call(null,this.param); // NB: il primo argomento è l'oggetto di contesto della funzione, che non serve
						else
							btn.panelCmp = this.panel.call();
					}
					else 
						btn.panelCmp = this.panel;
				}

				Ext.getCmp("navigatore").fireEvent('clickNavElem', this.panelCmp, btn, status);
//				this.ownerCt.ownerCt.ownerCt.ownerCt.ownerCt.ownerCt.ownerCt.ownerCt.fireEvent('clickNavElem', this.panelCmp, btn, status);
			}
        });

        DCS.ButtonSubMain.superclass.initComponent.call(this);
    }
});
//register xtype to allow for lazy initialization
Ext.reg('btnsubmain', DCS.ButtonSubMain);