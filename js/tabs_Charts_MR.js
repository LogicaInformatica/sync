// Sintesi delle pratiche viste da operatore interno
Ext.namespace('DCS.ChartsMr');

DCS.ChartsMr.Maxirate = Ext.extend(Ext.Panel, {
	titlePanel: '',
	task: '',
	itipo: 1,
	dsMese: null,
	dsAnni: null,
	comboTipo: null,
	comboMese: null,
	comboAnni: null,
	comboData: null,
	gc: null,
	gc2: null,
	gcStory :null,
		
	initComponent : function() {
	
		this.dsMese = DCS.Store.ChartMesi.getInstance();
		
		this.dsAnni = DCS.Store.ChartAnni;//.getInstance();
		
		var dsTipoDataMaxirata = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				sql: "SELECT 0 as IdCategoriaMaxirata, '&nbsp;' as CategoriaMaxirata UNION SELECT IdCategoriaMaxirata,CategoriaMaxirata FROM categoriamaxirata" 
			},
			/*2. specify the reader*/
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdCategoriaMaxirata'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'IdCategoriaMaxirata'},
				{name: 'CategoriaMaxirata'}]
			),
			autoLoad: true
		});//end dsTipo
		
		this.comboTipo = new Ext.form.ComboBox({
		    hidden: true,
		    triggerAction: 'all',
		    mode: 'local',
			lazyInit: false, 
			forceSelection: false,
			autoSelect: false,
		    store: new Ext.data.ArrayStore({
	        	fields: ['tipo'],
	        	data: [['Mensile'],['Storico']]
	    	}),
		    valueField: 'tipo',
		    displayField: 'tipo',
			width: 100,
			listeners: {
				select: function(combo, record, index) {
					//aaaa
					//this.comboMese.setVisible(index==0);
					Ext.getCmp(this.task+'_pnl').setVisible(index==0);
					if (!Ext.getCmp(this.task+'_pnl').hidden) {
						if (this.comboMese.getValue() == '') {
							var oggi = new Date();
							this.selectMonth(oggi.dateFormat('Ym'));
						} else {
							//this.selectMonth(this.comboMese.getValue());
						}
					}

					//this.comboAnni.setVisible(index==1);
					Ext.getCmp(this.task+'_pnlStory').setVisible(index==1);
					if (!Ext.getCmp(this.task+'_pnlStory').hidden) {
						if (this.comboAnni.getValue() == '') {
							var oggi = new Date();
							var fy = oggi.dateFormat('Y');
							if (oggi.dateFormat('m')>LAST_FY_MONTH) fy++;
							this.selectYear(fy);
						} else {
							this.selectYear(this.comboAnni.getValue());
						}
					}

					///this.comboData.setVisible(index==1);
					if (!this.comboData.hidden) {
//						this.selectYear(this.comboData.getRawValue());
					}

					this.doLayout();
				},
				scope: this
			}
		});


		this.comboData = new Ext.form.ComboBox({
		    triggerAction: 'all',
		    lazyInit: false, 
			forceSelection: true,
			autoSelect: true,
		    store: dsTipoDataMaxirata,
		    allowBlank: true,
		    valueField: 'IdCategoriaMaxirata',
		    displayField: 'CategoriaMaxirata',
			width: 150,
			listeners: {
				select: function(combo, record, index) {
					if (combo.getRawValue() === "" || combo.getRawValue() === "&nbsp;") {
			            combo.setValue(null);
			        }
			        if (this.comboTipo.getValue()=='Storico' || this.comboTipo.getValue()==='') {
					  this.selectYear(this.comboAnni.getValue());
					} else {
						this.selectMonth(this.comboMese.getValue());
					}  
				},
				scope: this
			}
		});

		this.comboMese = new Ext.form.ComboBox({
		    triggerAction: 'all',
		    mode: 'local',
			lazyInit: false, 
			forceSelection: true,
			autoSelect: true,
		    store: this.dsMese,
		    valueField: 'num',
		    displayField: 'abbr',
			width: 80,
			allowBlank: true,
			listeners: {
				afterrender: {
                   fn: function (combo) {
                   	  var store = combo.getStore();
                      var data = [];
                      data.push(new Ext.data.Record({
                          num:0,abbr:"&nbsp;",mese:"", FY:""
                      }));
                      store.insert(0, data);
                   }
                },
				select: function(combo, record, index) {
					if (combo.getRawValue() === "" || combo.getRawValue() === "&nbsp;") {
			            combo.setValue(null);
			            this.comboTipo.setValue('Storico');	
					    this.comboTipo.fireEvent('select',this.comboTipo,null,1);	
			       } else {
				       	if (this.comboTipo.getValue()!=='Mensile') {
						  this.comboTipo.setValue('Mensile');	
						  this.comboTipo.fireEvent('select',this.comboTipo,null,0);	
						}
						var dataType = this.comboData.getRawValue();
						if (dataType!=='') {
						  //Ext.getCmp(this.task+'_title').update('<h1>'+dataType+' - '+ record.data.FY + ' - ' + record.data.mese  + 
						  //		'<br><font size=2>'+ this.titlePanel+'</font></h1>');
						  Ext.getCmp(this.task+'_title').update('<h1>'+dataType+' - Anno '+ this.comboAnni.getValue() + ' - ' + record.data.mese  + 
								'<br><font size=2>'+ this.titlePanel+'</font></h1>');			
						} else {
							//Ext.getCmp(this.task+'_title').update('<h1>' + record.data.FY + ' - ' + record.data.mese  + 
							//	'<br><font size=2>'+ this.titlePanel+'</font></h1>');
							Ext.getCmp(this.task+'_title').update('<h1> Anno '+ this.comboAnni.getValue() + ' - ' + record.data.mese  + 
								'<br><font size=2>'+ this.titlePanel+'</font></h1>');	
						}
						
						Chart.plugins.register({
							afterDraw: function(chart) {
							  	if (chart.data.datasets.length === 0) {
							    	// No data is present
							      var ctx = chart.chart.ctx;
							      var width = chart.chart.width;
							      var height = chart.chart.height;
							      chart.clear();
							      
							      ctx.save();
							      ctx.textAlign = 'center';
							      ctx.textBaseline = 'middle';
							      ctx.font = '12px "Helvetica Nueue"';
							      ctx.fontColor = 'black';
							      ctx.fillText('No data to display', width / 2, height / 2);
							      ctx.restore();
							    }
						    }
						});
								
						Chart.Tooltip.positioners.cursor = function(chartElements, coordinates) {
						  return coordinates;
						};
						
						var canvas = document.getElementById(this.id+'_canvas');
						var ctx = canvas.getContext("2d");
						
	                    if (this.gc!=null) {
	                      this.gc.destroy();	
	                    }
	                    this.gc = new Chart(ctx, {
				            type: 'bar',
							data: {
								labels: [],
					            datasets: []
					        },
				            options: {
				                responsive: true,
				                showTooltips : false,
				                showInlineValues : true,
					            centeredInllineValues : true,
					            tooltipCaretSize : 0,
								hover: {
								    animationDuration : 0
								},
								layout: {
						            padding: {
						                left: 15,
						                right: 0,
						                top: 40,
						                bottom: 0
						            }
						        },
								barValueDisplay: {
							        color: 'rgba(0, 0, 0, 1)'
							    },
				                legend: {
				                    position: 'bottom',
				                },
				                tooltips: {
				                	mode: 'single',
				                	position: 'cursor',
				                	backgroundColor: 'rgba(0, 0, 0, 1)',
				                	titleFontSize: 0,
				                	intersect: false,
				                	callbacks: {
							            label: function(tooltipItem, data) {
							            	return data.datasets[tooltipItem.datasetIndex].label+', '+tooltipItem.yLabel;
							            }
							        }
							    },
							    scales: {
							    	xAxes: [{
							            barPercentage: 0.5
							        }],
						            yAxes: [{
						            	ticks: {
						                	padding: 5,
						                	beginAtZero: true,
						                	callback: function(value) {
									            if (Math.floor(value) === value) {
							                        return value;
							                    }
									        }
						                },
						                scaleLabel: {
									        display: true,
									        labelString: 'NUMERO  PRATICHE',
									        fontSize: '20',
									        fontFamily: 'Helvetica Nueue',
									        fontStyle: 'bold'
									    }
						            }]
						        },
						        animation: {
							        duration: 2000,
							        onComplete: function (animation) {
							        	var chartInstance = this.chart,
							                ctx = chartInstance.ctx;
							            var configOptions = this.chart.config.options;
							            ctx.font = '12px "Helvetica Nueue"';
							            ctx.textAlign = 'center';
							            ctx.textBaseline = 'bottom';
							
							            this.data.datasets.forEach(function (dataset, i) {
							                var meta = chartInstance.controller.getDatasetMeta(i);
							                meta.data.forEach(function (bar, index) {
							                    var data = dataset.data[index];
							                    ctx.fillStyle = configOptions.barValueDisplay.color;
							                    ctx.fillText(data, bar._model.x, bar._model.y);
							                });
							            });
							        }
							    }
				            }
				        });
				        Ext.Ajax.request({
					        url: 'server/charts/maxirate.php',
					        method: 'GET',
					        params: {type: 'stack', mese: record.data.num, task: this.task, data: dataType},
					        success: function(obj) {
					        	strDataSet = new Array();
					        	var jsonData = Ext.util.JSON.decode(obj.responseText);
					        	//var arrLabel = jsonData.categorie; 
	                            var arrRes = jsonData.results;
	                            //var arrTarget = jsonData.target;
	                            var colori = ['#99bbe8','#88ff88','#aa88ff','#3588aa','#489999','#66aa88','#02b955','#55ca00','#a2ca00','#ff4400','#ffca00','#cc0088','#aa2266','#bbaa99'];
	                            if(arrRes.length>0) {
	                            	for (i = 0; i < arrRes.length; i++) {
						        		categoriaMaxirata = arrRes[i].CategoriaMaxirata;
						        		var ind = strDataSet.indexOf(categoriaMaxirata);
										if (ind<0) {
											var newDataset = {
										        label: arrRes[i].CategoriaMaxirata,
										        fill: false,
										        backgroundColor: colori[this.gc.data.datasets.length],
										        borderColor: '#FF6384',
										        borderWidth: 1,
										        data: [],
										    };
										    this.gc.data.datasets.push(newDataset);
											strDataSet.push(categoriaMaxirata);
											ind = strDataSet.indexOf(categoriaMaxirata);
										}
										this.gc.data.datasets[ind].data.push(arrRes[i].NumCategoriaMaxirata);	
									}
						        }
					        	this.gc.update();
							},	scope: this});  	
	
						var canvas2 = document.getElementById(this.id+'_canvas2');
	                    var ctx2 = canvas2.getContext("2d");
	                    if (this.gc2!=null) {
	                      this.gc2.destroy();	
	                    }
			            this.gc2 = new Chart(ctx2, {
				            type: 'bar',
				            data: {
								labels: [],
					            datasets: []
					        },
					        plugins: [],
				            options: {
				                responsive: true,
				                hover: {
								    animationDuration : 0
								},
								layout: {
						            padding: {
						                left: 0,
						                right: 20,
						                top: 40,
						                bottom: 0
						            }
						        },
								barValueDisplay: {
							        color: 'rgba(0, 0, 0, 1)'
							    },
							    legend: {
				                    position: 'bottom',
				                },
				                tooltips: {
				                	mode: 'single',
				                	position: 'cursor',
				                	backgroundColor: 'rgba(0, 0, 0, 1)',
				                	titleFontSize: 0,
				                	intersect: false,
								    callbacks: {
								    	label: function(tooltipItem, data) {
							                return data.datasets[tooltipItem.datasetIndex].label+', \u20ac '+Ext.util.Format.number(tooltipItem.yLabel, '0.0,00/i');
							            }
							        }
							    },
							    scales: {
							    	xAxes: [{
							            barPercentage: 0.5
							        }],
						            yAxes: [{
						                gridLines: {
			                                display: true
			                            },
			                            ticks: {
						                    padding: 30,
						                    min: 0,
						                    maxTicksLimit: 5,
							                beginAtZero: true,
							                callback: function (value) {
					                            return '\u20ac '+Ext.util.Format.number(value, '0.0/i');
					                        }
						                },
						                scaleLabel: {
									        display: true,
									        labelString: 'TOTALE  INSOLUTO',
									        fontSize: '20',
									        fontFamily: 'Helvetica Nueue',
									        fontStyle: 'bold'
									    }
						            }]
						        },
			                    horizontalLine: [],
						        animation: {
							        duration: 2000,
							        onComplete: function (animation) {
							        	var chartInstance = this.chart,
							                ctx = chartInstance.ctx;
							            var configOptions = this.chart.config.options;    
							            ctx.font = '12px "Helvetica Nueue"';
							            ctx.textAlign = 'center';
							            ctx.textBaseline = 'bottom';
							            						
							            this.data.datasets.forEach(function (dataset, i) {
							                var meta = chartInstance.controller.getDatasetMeta(i);
							                meta.data.forEach(function (bar, index) {
							                    var data = dataset.data[index];
							                    data = '\u20ac '+Ext.util.Format.number(data, '0.0,00/i');                            
							                    ctx.fillStyle = configOptions.barValueDisplay.color;
							                    ctx.fillText(data, bar._model.x, bar._model.y);
							                });
							            });
							        }
							    }
				            }
				        });
				        Ext.Ajax.request({
					        url: 'server/charts/maxirate.php',
					        method: 'GET',
					        params: {type: 'stack', mese: record.data.num, task: this.task, data: dataType},
					        success: function(obj) {
					        	strDataSet = new Array();
					        	var jsonData = Ext.util.JSON.decode(obj.responseText);
					        	//var arrLabel = jsonData.categorie; 
	                            var arrRes = jsonData.results;
	                            //var arrTarget = jsonData.target;
	                            var colori = ['#99bbe8','#88ff88','#aa88ff','#3588aa','#489999','#66aa88','#02b955','#55ca00','#a2ca00','#ff4400','#ffca00','#cc0088','#aa2266','#bbaa99'];
	                            if(arrRes.length>0) {
	                            	for (i = 0; i < arrRes.length; i++) {
						        		categoriaMaxirata = arrRes[i].CategoriaMaxirata;
						        		var ind = strDataSet.indexOf(categoriaMaxirata);
										if (ind<0) {
											var newDataset = {
										        label: arrRes[i].CategoriaMaxirata,
										        fill: false,
										        backgroundColor: colori[this.gc2.data.datasets.length],
										        borderColor: '#FF6384',
										        borderWidth: 1,
										        data: [],
										    };
										    this.gc2.data.datasets.push(newDataset);
											strDataSet.push(categoriaMaxirata);
											ind = strDataSet.indexOf(categoriaMaxirata);
										}
										this.gc2.data.datasets[ind].data.push(arrRes[i].TotaleImportoInsoluto);	
									}
						        }
					        	this.gc2.update();
					        },	scope: this});
					 }	
				},
				scope: this
			}
		});

		this.comboAnni = new Ext.form.ComboBox({
		    triggerAction: 'all',
		    mode: 'local',
			lazyInit: false, 
			forceSelection: true,
			autoSelect: true,
		    store: this.dsAnni,
		    valueField: 'num',
		    displayField: 'num',
		    width: 60,
			listeners: {
				select: function(combo, record, index) {
					if (this.comboTipo.getValue()!=='Storico') {
					  this.comboTipo.setValue('Storico');	
					  this.comboTipo.fireEvent('select',this.comboTipo,null,1);	
					}
					var dataType = this.comboData.getRawValue();
					if (dataType!=='') {
					  //Ext.getCmp(this.task+'_title').update('<h1>'+dataType+' - Fiscal Year '+record.data.num +'</h1>');
					  Ext.getCmp(this.task+'_title').update('<h1>'+dataType+' - Anno '+record.data.num +'</h1>');	
					} else {
						//Ext.getCmp(this.task+'_title').update('<h1> Fiscal Year '+record.data.num +'</h1>');
						Ext.getCmp(this.task+'_title').update('<h1> Anno '+record.data.num +'</h1>');
					}
										
					var idPanel = this.id;
					
					Chart.plugins.register({
						afterDraw: function(chart) {
						  	if (chart.data.datasets.length === 0) {
						    	// No data is present
						      var ctx = chart.chart.ctx;
						      var width = chart.chart.width;
						      var height = chart.chart.height;
						      chart.clear();
						      
						      ctx.save();
						      ctx.textAlign = 'center';
						      ctx.textBaseline = 'middle';
						      ctx.font = '12px "Helvetica Nueue"';
						      ctx.fontColor = 'black';
						      ctx.fillText('No data to display', width / 2, height / 2);
						      ctx.restore();
						    }
					    }
					});
					
					Chart.defaults.line.spanGaps = true;
					
					var canvasStory = document.getElementById(this.id+'_canvasStory');
					var ctxStory = canvasStory.getContext("2d");
					if (this.gcStory!=null) {
                      this.gcStory.destroy();	
                    }
					this.gcStory = new Chart(ctxStory, {
			            type: 'line',
			            plugins: [],
			            data: {
				            labels: ["Gen", "Feb", "Mar", "Apr", "Mag", "Giu","Lug", "Ago", "Set", "Ott", "Nov", "Dic"],
				            datasets: []
				        },
			            options: {
			                responsive: true,
			                hover: {
							    animationDuration : 0
							},
							layout: {
					            padding: {
					                left: 15,
					                right: 0,
					                top: 15,
					                bottom: 0
					            }
					        },
					        legend: {
			                    position: 'bottom'
			                },
			                tooltips: {
			                	mode: 'point',
			                	backgroundColor: 'rgba(0, 0, 0, 1)',
				                titleFontSize: 0,
			                	callbacks: {
							    	label: function(tooltipItem, data) {
						                return data.datasets[tooltipItem.datasetIndex].label+', '+tooltipItem.yLabel;
						            }
						        }
							},
						    scales: {
						    	yAxes: [{
					            	gridLines: {
		                                display: true
		                            },
		                            ticks: {
		                            	beginAtZero: true,
					                	callback: function(value) {
								            if (Math.floor(value) === value) {
						                        return value;
						                    }
								        }
					                },
					                scaleLabel: {
								        display: true,
								        labelString: 'NUMERO  PRATICHE',
									    fontSize: '20',
									    fontFamily: 'Helvetica Nueue',
									    fontStyle: 'bold'
								    }
					            }]
					        },
					        animation: {
						        duration: 2000
						    }
			            }
			        });
			        Ext.Ajax.request({
				        url: 'server/charts/maxirateStory.php',
				        method: 'GET',
				        params: {type: 'stack', id:this.id, anno: record.data.num, task: this.task, data: dataType},
				        success: function(obj) {
							strDataSet = new Array();
                            var jsonData = Ext.util.JSON.decode(obj.responseText);
                            //var arrLabel = jsonData.categorie; 
                            var arrRes = jsonData.results;
                            //var arrTarget = jsonData.target;
                            var colori = ['#99bbe8','#aa88ff','#3588aa','#489999','#66aa88','#02b955','#55ca00','#a2ca00','#ff4400','#ffca00','#cc0088','#aa2266','#bbaa99'];
		                    var tension = [0.1,0.9,0.1,0.9,0.1,0.9,0.1,0.9,0.1,0.9,0.1,0.9,0.1];
                            if(arrRes.length>0) {
                            	for (i = 0; i < arrRes.length; i++) {
					        		categoriaMaxirata = arrRes[i].CategoriaMaxirata;
					        		var ind = strDataSet.indexOf(categoriaMaxirata);
									if (ind<0) {
										var newDataset = {
									        label: arrRes[i].CategoriaMaxirata,
									        fill: false,
									        backgroundColor: colori[this.gcStory.data.datasets.length],
									        borderColor: colori[this.gcStory.data.datasets.length],
									        lineTension: tension[this.gcStory.data.datasets.length],
									        borderWidth: 3,
									        data: new Array(12),
									    };
									    this.gcStory.data.datasets.push(newDataset);
										strDataSet.push(categoriaMaxirata);
										ind = strDataSet.indexOf(categoriaMaxirata);
									}
									//inserisco i valori dell'agenzia al mese corrispondente
								    data = arrRes[i].Mese;
								    anno = data.substr(0,4);
								    mese = data.substr(4,data.length);
								    //indexLabel =this.gcStory.data.labels.indexOf(mese); 
								    this.gcStory.data.datasets[ind].data[parseInt(mese)-1] = arrRes[i].NumCategoriaMaxirata;	
								}
					        }
				        	this.gcStory.update();
				        },	scope: this});
				        
				        var canvas2Story = document.getElementById(this.id+'_canvas2Story');
						var ctx2Story = canvas2Story.getContext("2d");
						if (this.gc2Story!=null) {
		                  this.gc2Story.destroy();	
		                }
						this.gc2Story = new Chart(ctx2Story, {
				            type: 'line',
				            plugins: [],
				            data: {
					            labels: ["Gen", "Feb", "Mar", "Apr", "Mag", "Giu","Lug", "Ago", "Set", "Ott", "Nov", "Dic"],
					            datasets: []
					        },
				            options: {
				                responsive: true,
			                    hover: {
								    animationDuration : 0
								},
								layout: {
						            padding: {
						                left: 0,
						                right: 20,
						                top: 15,
						                bottom: 0
						            }
						        },
						        legend: {
				                    position: 'bottom'
				                },
				                tooltips: {
			                	    mode: 'point',
			                	    backgroundColor: 'rgba(0, 0, 0, 1)',
				                	titleFontSize: 0,
				                	callbacks: {
								    	label: function(tooltipItem, data) {
							                return data.datasets[tooltipItem.datasetIndex].label+', \u20ac '+Ext.util.Format.number(tooltipItem.yLabel, '0.0,00/i');
							            }
							        }
								},
						        scales: {
							    	yAxes: [{
						            	gridLines: {
			                                display: true
			                            },
			                            ticks: {
			                            	beginAtZero: true,
							                callback: function(label, index, labels) {
						                        return '\u20ac '+Ext.util.Format.number(label, '0.0/i');
						                    }
						                },
						                scaleLabel: {
									        display: true,
									        labelString: 'TOTALE  INSOLUTO',
									        fontSize: '20',
									        fontFamily: 'Helvetica Nueue',
									        fontStyle: 'bold'
									    }
						            }]
						        },
			                    animation: {
							        duration: 2000
							    }
				            }
				        });
				        Ext.Ajax.request({
					        url: 'server/charts/maxirateStory.php',
					        method: 'GET',
					        params: {type: 'stack', id:this.id, anno: record.data.num, task: this.task, data: dataType},
					        success: function(obj) {
								strDataSet = new Array();
		                        var jsonData = Ext.util.JSON.decode(obj.responseText);
		                        //var arrLabel = jsonData.categorie; 
		                        var arrRes = jsonData.results;
		                        //var arrTarget = jsonData.target;
		                        var colori = ['#99bbe8','#aa88ff','#3588aa','#489999','#66aa88','#02b955','#55ca00','#a2ca00','#ff4400','#ffca00','#cc0088','#aa2266','#bbaa99'];
		                        var tension = [0.1,0.9,0.1,0.9,0.1,0.9,0.1,0.9,0.1,0.9,0.1,0.9,0.1];
		                        if(arrRes.length>0) {
		                        	for (i = 0; i < arrRes.length; i++) {
						        		categoriaMaxirata = arrRes[i].CategoriaMaxirata;
						        		var ind = strDataSet.indexOf(categoriaMaxirata);
										if (ind<0) {
											var newDataset = {
										        label: arrRes[i].CategoriaMaxirata,
										        fill: false,
										        backgroundColor: colori[this.gc2Story.data.datasets.length],
										        borderColor: colori[this.gc2Story.data.datasets.length],
										        borderWidth: 3,
										        lineTension: tension[this.gc2Story.data.datasets.length],
										        data: new Array(12),
										    };
										    this.gc2Story.data.datasets.push(newDataset);
											strDataSet.push(categoriaMaxirata);
											ind = strDataSet.indexOf(categoriaMaxirata);
										}
										//inserisco i valori dell'agenzia al mese corrispondente
									    data = arrRes[i].Mese;
									    anno = data.substr(0,4);
									    mese = data.substr(4,data.length);
									    this.gc2Story.data.datasets[ind].data[parseInt(mese)-1] = arrRes[i].TotaleImportoInsoluto;	
									}
						        }
					        	this.gc2Story.update();
					        },	scope: this});
				},
				scope: this
			}
		});

		Ext.apply(this,{
			header: true,
			border: false,
			items: [{
				xtype:'panel',
				layout: 'hbox',
				items:[{
					id: this.task+'_title',
					xtype: 'box',
					style: 'text-align:center;',
					flex: 1
				},this.comboAnni,this.comboMese,this.comboTipo,this.comboData]
			},{
				xtype:'panel',
				layout: 'column',
				id:this.task+'_pnl',
				margin: '20 0 0 0',
				items: [{
					xtype:'panel',
					columnWidth: .5,
					id:this.task+'_cc',
					html: '<canvas id="'+this.id+'_canvas" height="200px"></canvas>',
				},{
					xtype:'panel',
					columnWidth: .5,
					id:this.task+'_cc2',
					html: '<canvas id="'+this.id+'_canvas2" height="200px"></canvas>',
			}]},{
				xtype:'panel',
				layout: 'column',
				id:this.task+'_pnlStory',
				margin: '20 0 0 0',
				items: [{
					xtype:'panel',
					columnWidth: .5,
					id:this.task+'_story',
					html: '<canvas id="'+this.id+'_canvasStory" height="200px"></canvas>',
				},{
					xtype:'panel',
					columnWidth: .5,
					id:this.task+'_story2',
					html: '<canvas id="'+this.id+'_canvas2Story" height="200px"></canvas>',
			}]}],
			listeners: {
				activate: function(pnl) {
					if (this.comboTipo.getValue() == '') {
						this.comboTipo.setValue('Storico');
					}
					var idx = this.comboTipo.store.find('tipo',this.comboTipo.getValue());
					this.comboTipo.fireEvent('select',this.comboTipo,null,idx); 
				},
				scope: this
			}
	    });
		
		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
//	                '->', {type:'button', text:'Grafico', icon:'images/3dgraph.png', handler:this.changeType, scope:this},
					' '
				];

		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});

		DCS.ChartsMr.Maxirate.superclass.initComponent.call(this, arguments);

	},

	//--------------------------------------------------------
    // Visualizza dettaglio
    //--------------------------------------------------------
	changeType: function() {
		//var g = FusionCharts(this.task+"_chartId");
		this.itipo++; 
		if (this.itipo==DCS.ChartsMr.tipi.length) 
			this.itipo = 0;
		//g = g.clone( { swfUrl : 'FusionCharts/'+DCS.ChartsMr.tipi[this.itipo]+'.swf' } );
		//g.render(this.task+"_cc");
    },
	
	//--------------------------------------------------------
    // 
    //--------------------------------------------------------
	selectMonth: function(mese) {
		this.comboMese.setValue(mese);
		if (this.dsMese) // se inizializzato
		{
			var idx = this.dsMese.find('num',mese);
			if (idx>0) {
			  var rec = this.dsMese.getAt(idx);
			  this.comboMese.fireEvent('select',this.comboMese,rec,idx); 	
			}
		}
    },
	
	//--------------------------------------------------------
    // 
    //--------------------------------------------------------
	selectYear: function(anno) {
		this.comboAnni.setValue(anno);
		if (this.dsAnni) // se inizializzato
		{
			var idx = this.dsAnni.find('num',anno);
			var rec = this.dsAnni.getAt(idx);
			this.comboAnni.fireEvent('select',this.comboAnni,rec,idx); 
		}
    }

});

//--------------------------------------------------------------------
// Funzione richiamata da sintesiStory.php per il drill down
//--------------------------------------------------------------------
DCS.ChartsMr.presentaMese = function(idGrafico,numMese)
{
	//aaaa
	var pannello = Ext.getCmp(idGrafico);
	var idx = pannello.dsMese.find('num',numMese);
    if (idx>0) {
      pannello.comboTipo.setValue('Mensile');
	  pannello.comboMese.setValue(numMese);	
	  pannello.comboTipo.fireEvent('select',pannello.comboTipo,null,0); // Imposta la combo su mensile
	}  
	//pannello.selectMonth(numMese);
};

//-----------------------------------------------------------
// Griglia con i dati statistici per categoria maxirate
//-----------------------------------------------------------
DCS.ChartsMr.Statistiche = Ext.extend(Ext.grid.GridPanel, {
//	width:380,
	gstore: null,
	pagesize: 0,
	titlePanel: '',
		
	initComponent : function() {
		fields = [{name: 'IdCategoriaMaxirata'},
	              {name: 'TotaleImportoInsoluto',type:'float'},
	              {name: 'NumCategoriaMaxirata',type:'int'},
	              {name: 'CategoriaMaxirata'}
//		              {name: 'Sting'},{name: 'StingNum',type:'int'}
	              ];

	 	columns = [{dataIndex:'CategoriaMaxirata',width:100, header:'Categoria maxirata',sortable:false},
	   	           {dataIndex:'TotaleImportoInsoluto',width:100, header:'TOTALE Importo Insoluto',sortable:true,align:'right',css:'background-color:aquamarine;font-weight:bold;',xtype:'numbercolumn',format:'0.000,00/i'},
	   	           {dataIndex:'NumCategoriaMaxirata',width:50, header:'TOTALE N.',sortable:true,align:'right'},
	   	           ];
		
		this.gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/charts/maxirate.php',
				method: 'GET'
			}),   
			/* specificati al momento della reload: baseParams:{task:'table', anno:this.anno, mese:this.mese},*/
			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				fields: fields
			})
  		});

		Ext.apply(this,{
			store: this.gstore,
			autoHeight: false,
//			autoExpandColumn: true,
			view: new Ext.grid.GroupingView({
				autoFill: (Ext.state.Manager.get(this.stateId,'')==''), //false, //true,
				forceFit: false,
				groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "elementi" : "elemento"]})',
		        //enableNoGroups: false,
	            hideGroupedColumn: true
           }),
			border: true,
			loadMask: true,
			columns: columns,
			valign:'center'
	    });

		var tbarItems = [
							{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
							'->', {type: 'button', text: 'Stampa elenco',  icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}, scope:this},
			                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png',  handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
			                '-', helpButton("Grafici"),' '
						];
	
		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});
		DCS.ChartsMr.Statistiche.superclass.initComponent.call(this, arguments);
	}
});

//-----------------------------------------------------------
// Pagina con il grafico "cruscotto" e la tabella dei target
//-----------------------------------------------------------
DCS.ChartsMr.StatMaxirate = Ext.extend(Ext.Panel, {
	titlePanel: '',
	task: '',
	itipo: 4,
	dsMese: null,
	dsAnni: null,
	comboTipo: null,
	comboMese: null,
	comboAnni: null,
	grid: null,
		
	initComponent : function() {
	/*
		var myChart = new FusionCharts("FusionCharts/"+DCS.Charts.tipi[this.itipo]+".swf", this.task+"_chartId", "100%", "90%", "0", "1" );
		myChart.configure("XMLLoadingText","Caricamento in corso...");

		var myChart2 = new FusionCharts("FusionCharts/"+DCS.Charts.tipi[this.itipo]+".swf", this.task+"_chartId2", "100%", "90%", "0", "1" );
		myChart2.configure("XMLLoadingText","Caricamento in corso...");
	 */	
		
		this.dsMese = DCS.Store.ChartMesi.getInstance();

		this.dsAnni = DCS.Store.ChartAnni; //.getInstance();

		this.comboTipo = new Ext.form.ComboBox({
		    triggerAction: 'all',
		    mode: 'local',
			lazyInit: false, 
			forceSelection: true,
			autoSelect: true,
		    store: new Ext.data.ArrayStore({
	        	fields: ['tipo'],
	        	data: [['Mese'],['Anno fiscale']]
	    	}),
	    	valueField: 'tipo',
		    displayField: 'tipo',
			width: 100,
			listeners: {
				select: function(combo, record, index) {
					//this.comboMese.setVisible(index==0);
					//Ext.getCmp(this.task+'_pnl').setVisible(index==0);
					//if (!this.comboMese.hidden) {
						if (this.comboMese.getValue() == '') {
							var oggi = new Date();
							this.selectMonth(oggi.dateFormat('Ym'));
						} else {
							this.selectMonth(this.comboMese.getValue());
						}
					//}

					/*this.comboAnni.setVisible(index==1);
					Ext.getCmp(this.task+'_story').setVisible(index==1);
					if (!this.comboAnni.hidden) {
						if (this.comboAnni.getValue() == '') {
							var oggi = new Date();
							var fy = oggi.dateFormat('Y');
							if (oggi.dateFormat('m')>LAST_FY_MONTH) fy++;
							this.selectYear(fy);
						} else {
							this.selectYear(this.comboAnni.getValue());
						}
					}*/

					this.doLayout();
				},
				scope: this
			}
		});


		this.comboMese = new Ext.form.ComboBox({
		    triggerAction: 'all',
		    mode: 'local',
			lazyInit: false, 
			forceSelection: true,
			autoSelect: true,
		    store: this.dsMese,
		    valueField: 'num',
		    displayField: 'abbr',
			width: 80,
			listeners: {
				// alla selezione di anno o mese nella combobox, riempie grafico e tabella
				select: function(combo, record, index) {
					Ext.getCmp(this.task+'_title').update('<h1>' + record.data.FY + ' - ' + record.data.mese  + ' </h1>');
					// Aggiorna la griglia dei target
					this.grid.titlePanel = "Target "+record.data.mese;
					var gstore = this.grid.getStore();
					gstore.baseParams = {task:'store', mese:record.data.num};
					gstore.reload();
				},
				scope: this
			}
		});

		this.comboAnni = new Ext.form.ComboBox({
		    triggerAction: 'all',
		    mode: 'local',
			lazyInit: false, 
			forceSelection: true,
			autoSelect: true,
		    store: this.dsAnni,
		    valueField: 'num',
		    displayField: 'num',
		    width: 60,
			listeners: {
				select: function(combo, record, index) {
					// Aggiorna la griglia dei target
					this.grid.titlePanel = "Target Fiscal Year "+record.data.num;
					var gstore = this.grid.getStore();
					gstore.baseParams = {task:'store', anno:record.data.num, gruppo:this.gruppo};
					gstore.reload();
				},
				scope: this
			}
		});

		Ext.apply(this,{
			header: true,
			border: false,
			items: [{
				xtype:'panel',
				layout: 'hbox',
				items:[{
					id: this.task+'_title',
					xtype: 'box',
					style: 'text-align:center;',
					flex: 1
				},this.comboMese]   //,this.comboTipo,this.comboAnni
			},{
				xtype:'panel',
				layout: 'column',
				id:this.task+'_page',
				items: [{
					xtype:'panel',
					columnWidth: .6,
					layout: 'column',
					id:this.task+'_pnl'
					//html: '<canvas id="'+this.id+'_canvasTFSI"></canvas>'
				},{
					xtype:'panel',
					columnWidth: .4,
					id:this.task+'_table',
                    height:450,
					layout:'fit',
					items: [this.grid]
			}] // fine array dei tre items grafico+tabella
			}],// fine array dei due item che soctituiscono la pagina (titolo+combos , grafici+tabella)
			listeners: {
				activate: function(pnl) {
					if (this.comboTipo.getValue() == '') {
						this.comboTipo.setValue('Mese');
					}
					var idx = this.comboTipo.store.find('tipo',this.comboTipo.getValue());
					this.comboTipo.fireEvent('select',this.comboTipo,null,idx); 
				},
				scope: this
			}
	    });

		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
//	                '->', {type:'button', text:'Grafico', icon:'images/3dgraph.png', handler:this.changeType, scope:this},
					' '
				];
		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});

		DCS.ChartsMr.StatMaxirate.superclass.initComponent.call(this, arguments);

	},

	//--------------------------------------------------------
    // Visualizza dettaglio
    //--------------------------------------------------------
	changeType: function() {
		var g = FusionCharts(this.task+"_chartId");
		this.itipo++; 
		if (this.itipo==DCS.Charts.tipi.length) 
			this.itipo = 0;
		g = g.clone( { swfUrl : 'FusionCharts/'+DCS.Charts.tipi[this.itipo]+'.swf' } );
		g.render(this.task+"_pnl");
    },
	
	//--------------------------------------------------------
    // 
    //--------------------------------------------------------
	selectMonth: function(mese) {
		this.comboMese.setValue(mese);
		var idx = this.dsMese.find('num',mese);
		if (idx>0) {
		  var rec = this.dsMese.getAt(idx);
		  this.comboMese.fireEvent('select',this.comboMese,rec,idx);	
		}
	},
	
	//--------------------------------------------------------
    // 
    //--------------------------------------------------------
	selectYear: function(anno) {
		this.comboAnni.setValue(anno);
		var idx = this.dsAnni.find('num',anno);
		var rec = this.dsAnni.getAt(idx);
		this.comboAnni.fireEvent('select',this.comboAnni,rec,idx); 
    }

});

DCS.ChartsMr.TabsMRRL = function(){
	
	return {
		create_TFSI_MR: function(){
			items = Array();
			//if (CONTEXT.CAN_GRAPH_ALL)
				items.push(new DCS.ChartsMr.Maxirate({
					titlePanel: 'Maxirate',
					title: 'Maxirate',
					task: 'maxirate',id: 'graphMaxirate'
					}));
					
			var targetGrid = new DCS.ChartsMr.Statistiche({
				titlePanel: 'Sintesi del periodo',
				stateful: true, gruppo: 1
				});
			
			items.push(new DCS.ChartsMr.StatMaxirate({
				titlePanel: 'Sintesi mensile maxirate',
				title: 'Sintesi mensile maxirate',
				task: 'statistiche', id:'tabStat',
				grid: targetGrid, gruppo: 1
				}));		
			
			return new Ext.TabPanel({
    			activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				items: items
			});
		},
		create_TFSI_RL: function(){
			items = Array();
			//if (CONTEXT.CAN_GRAPH_ALL)
				items.push(new DCS.ChartsMr.RiscattiScaduti({
					titlePanel: 'Riscatti scaduti',
					title: 'Riscatti scaduti',
					task: 'riscattiscaduti',id: 'graphRiscattiScaduti'
					}));
			
			return new Ext.TabPanel({
    			activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				items: items
			});
		}		
	};
	
}();
