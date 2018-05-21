// Sintesi delle pratiche viste da operatore interno
Ext.namespace('DCS.Charts');

//var tipi = ['Column2D', 'Column3D', 'Bar2D', 'Line', 'Area2D', 'Pie2D', 'Pie3D', 'Doughnut2D', 'Doughnut3D', 'Pareto2D', 'Pareto3D', 'SSGrid'];
DCS.Charts.tipi = ['MSColumn2D', 'MSColumn3D', 'MSLine', 'MSBar2D', 'MSBar3D', 'MSArea', 'Marimekko', 'ZoomLine',
'StackedColumn3D', 'StackedColumn2D', 'StackedBar2D', 'StackedBar3D', 'StackedArea2D'];

DCS.Charts.Sintesi = Ext.extend(Ext.Panel, {
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
	/* da usare con FusionChart vers. 3, omesso per la vers. 2 
		var myChart = new FusionCharts("FusionCharts/"+DCS.Charts.tipi[this.itipo]+".swf", this.task+"_chartId", "100%", "90%", "0", "1" );
		myChart.configure("XMLLoadingText","Caricamento in corso...");
		myChart.configureLink({
			overlayButton: { message : 'Indietro' }
			},0
		);
		var myChart2 = new FusionCharts("FusionCharts/"+DCS.Charts.tipi[this.itipo]+".swf", this.task+"_chartId2", "100%", "90%", "0", "1" );
		myChart2.configure("XMLLoadingText","Caricamento in corso...");
		myChart2.configureLink({
			overlayButton: { message : 'Indietro' }
			},0
		);
		
		var myChart3 = new FusionCharts("FusionCharts/"+DCS.Charts.tipi[this.itipo]+".swf", this.task+"_chartId3", "100%", "90%", "0", "1" );
		myChart3.configure("XMLLoadingText","Caricamento in corso...");
		*/
		this.dsMese = DCS.Store.ChartMesi.getInstance();

		this.dsAnni = DCS.Store.ChartAnni;//.getInstance();

		this.comboTipo = new Ext.form.ComboBox({
		    triggerAction: 'all',
		    mode: 'local',
			lazyInit: false, 
			forceSelection: true,
			autoSelect: true,
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
					this.comboMese.setVisible(index==0);
					Ext.getCmp(this.task+'_pnl').setVisible(index==0);
					if (!this.comboMese.hidden) {
						if (this.comboMese.getValue() == '') {
							var oggi = new Date();
							this.selectMonth(oggi.dateFormat('Ym'));
						} else {
							this.selectMonth(this.comboMese.getValue());
						}
					}

					this.comboAnni.setVisible(index==1);
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
					}

					this.comboData.setVisible(index==1);
					if (!this.comboData.hidden) {
//						this.selectYear(this.comboData.getValue());
					}

					this.doLayout();
				},
				scope: this
			}
		});


		this.comboData = new Ext.form.ComboBox({
		    triggerAction: 'all',
		    mode: 'local',
			lazyInit: false, 
			forceSelection: true,
			autoSelect: true,
		    store: new Ext.data.ArrayStore({
	        	fields: ['dataType'],
	        	data: [['IPM'],['IPR']]
	    	}),
		    valueField: 'dataType',
		    displayField: 'dataType',
			value: 'IPM',
			width: 60,
			listeners: {
				select: function(combo, record, index) {
					this.selectYear(this.comboAnni.getValue());
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
				select: function(combo, record, index) {
					Ext.getCmp(this.task+'_title').update('<h1>' + record.data.FY + ' - ' + record.data.mese  + 
							'<br><font size=2>'+ this.titlePanel+'</font></h1>');
		
					// rilascia quelli gi� allocati
					var g = FusionCharts(this.task+"_chartId"); /* sufficiente per FusionChart vers.3, non per la 2 */
					if (g) g.dispose();
					var g2 = FusionCharts(this.task+"_chartId2");
					if (g2) g2.dispose();

					g = new FusionCharts("FusionCharts/"+DCS.Charts.tipi[this.itipo]+".swf", this.task+"_chartId", "100%", "90%", "0", "1" );
					// non va: � asincrono anche se la guida non lo dice
					//g.setXMLUrl("server/charts/sintesi.php?type=stack&mese="+record.data.num+"&task="+this.task);
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
						                return data.datasets[tooltipItem.datasetIndex].label+', '+tooltipItem.xLabel+', \u20ac '+Ext.util.Format.number(tooltipItem.yLabel, '0.0,00/i');
						            }
						        }
						    },
						    scales: {
					            yAxes: [{
					            	ticks: {
					                	padding: 5,
					                	maxTicksLimit: 5,
					                	beginAtZero: true,
						                callback: function (value) {
				                            return '\u20ac '+Ext.util.Format.number(value, '0.0/i');
				                        }
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
						                    data = '\u20ac '+Ext.util.Format.number(data, '0.0,00/i');                            
						                    ctx.fillStyle = configOptions.barValueDisplay.color;
						                    ctx.fillText(data, bar._model.x, bar._model.y);
						                });
						            });
						        }
						    }
			            }
			        });
			        /*Ext.Ajax.request({
				        url: 'server/charts/sintesi.php',
				        method: 'GET',
				        params: {type: 'stack', mese: record.data.num, task: this.task},
				        success: function(obj) {
							g.setXMLData(obj.responseText);
							g.render(this.task+"_cc");
				        },	scope: this});*/
				    Ext.Ajax.request({
				        url: 'server/charts/sintesi.php',
				        method: 'GET',
				        params: {type: 'stack', mese: record.data.num, task: this.task},
				        success: function(obj) {
				        	var jsonData = Ext.util.JSON.decode(obj.responseText);
				        	arrRes = jsonData.results;
				        	if (arrRes.length>0) {
				        	  var newDatasetCapRecupero = {
				                 label: 'Capitale recuperato',
				                 backgroundColor:'#99BBE8',
				                 borderColor: '#FF6384',
				                 borderWidth: 1,
				                 data: []
				              }; 
				              var newDatasetCapAffidato = {
				                 label: 'Capitale affidato',
				                 backgroundColor:'#88FF88',
				                 borderColor: '#FF6384',
				                 borderWidth: 1,
				                 data: []
				              };
				              this.gc.data.datasets.push(newDatasetCapRecupero);
							  this.gc.data.datasets.push(newDatasetCapAffidato);
							  if (arrRes.length>2) {
							  	this.gc.options.scales.xAxes[0].ticks.minor.fontSize = 9;
							  	this.gc.options.scales.xAxes[0].ticks.fontSize = 9;
							  }
							  for (i = 0; i < arrRes.length; i++) {
							     this.gc.data.labels[i]=arrRes[i].Agenzia +" ("+arrRes[i].NumIncassati+"/"+arrRes[i].NumAffidati+")";
							     this.gc.data.datasets[0].data.push(arrRes[i].ImpCapitaleIncassato);
							     this.gc.data.datasets[1].data.push(arrRes[i].ImpCapitaleAffidato);
							  }	
				        	} 
				        	this.gc.update();
						},	scope: this});  	

					g2 = new FusionCharts("FusionCharts/"+DCS.Charts.tipi[this.itipo]+".swf", this.task+"_chartId2", "100%", "90%", "0", "1" );
//                    g2.setXMLUrl("server/charts/sintesiPerc.php?type=stack&mese="+record.data.num+"&task="+this.task);
//                    g2.render(this.task+"_cc2");
                    //var ctx2 = document.getElementById(this.task+'_canvas2').getContext("2d");
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
				        plugins: [{
					        afterDatasetDraw: function(chartInstance, options) {
					            var yScale = chartInstance.scales["y-axis-0"];
					            var canvas = chartInstance.chart;
			                    var ctx = canvas.ctx;
			                    var index;
			                    var line;
			                    var style;
			
			                    if (chartInstance.options.horizontalLine) {
			                        for (index = 0; index < chartInstance.options.horizontalLine.length; index++) {
			                            line = chartInstance.options.horizontalLine[index];
							            style = (line.style) ? line.style : "rgba(169,169,169, .6)";
							            yValue = (line.y) ? yScale.getPixelForValue(line.y) : 0;
							            ctx.lineWidth = (line.width) ? line.width : 3;
							            if (yValue) {
							               ctx.beginPath();
							               ctx.moveTo(chartInstance.chartArea.left, yValue);
							               ctx.lineTo(chartInstance.chartArea.right, yValue);
							               ctx.strokeStyle = style;
							               ctx.stroke();
							            }
							            if (line.text) {
							               ctx.fillStyle = style;
							               ctx.fillText(line.text, 5, yValue + ctx.lineWidth);
							            }
			                        }
			                        return;
			                    };
					        }
					    }],
			            options: {
			                responsive: true,
			                hover: {
							    animationDuration : 0
							},
							layout: {
					            padding: {
					                left: 0,
					                right: 0,
					                top: 0,
					                bottom: 0
					            }
					        },
							barValueDisplay: {
						        color: 'rgba(0, 0, 0, 1)'
						    },
						    title: {
					            display: true,
					            text: '(Valori calcolati sulla media ponderata dei lotti scaduti nel mese di riferimento)'
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
						            	return data.datasets[tooltipItem.datasetIndex].label+', '+tooltipItem.xLabel+', '+tooltipItem.yLabel+'%';
						            }
						        }
						    },
						    scales: {
					            yAxes: [{
					                gridLines: {
		                                display: true
		                            },
		                            ticks: {
					                    padding: 30,
					                    min: 0,
					                    suggestedMax: 100,
					                    stepSize: 20,
						                beginAtZero: true,
						                callback: function(label, index, labels) {
					                        return label+'%';
					                    }
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
						                    data = data+"%";                            
						                    ctx.fillStyle = configOptions.barValueDisplay.color;
						                    ctx.fillText(data, bar._model.x, bar._model.y);
						                });
						            });
						        }
						    }
			            }
			        });
			        /*Ext.Ajax.request({
				        url: 'server/charts/sintesiPerc.php',
				        method: 'GET',
				        params: {type: 'stack', mese: record.data.num, task: this.task},
				        success: function(obj) {
							g2.setXMLData(obj.responseText);
							g2.render(this.task+"_cc2");
				        },	scope: this});*/	
                    Ext.Ajax.request({
				        url: 'server/charts/sintesiPerc.php',
				        method: 'GET',
				        params: {type: 'stack', mese: record.data.num, task: this.task},
				        success: function(obj) {
				        	var jsonData = Ext.util.JSON.decode(obj.responseText);
				        	var arrRes = jsonData.results;
				        	var arrTarget = jsonData.target;
				        	if (arrRes.length>0) {
				        	  for (i = 0; i < arrTarget.length; i++) {
				        	  	target = arrTarget[i];
				        	  	var newHorizontalLine = {
			                        style: 'rgba(255, 0, 0, .7)',
			                        font: '10px "Helvetica Nueue"',
							        fontColor: 'rgba(255, 0, 0, .7)',
							        y: target,
							        text: "Target "+target+"%"
			                    };
			                    this.gc2.options.horizontalLine.push(newHorizontalLine);
				        	  }
				        	  var newDatasetIPR = {
						         label: 'IPR (Recuperato/Affidato)',
						         backgroundColor:'#99BBE8',
				                 borderColor: '#FF6384',
				                 borderWidth: 1,
				                 data: []
							  };
							  var newDatasetIPM = {
						         label: 'IPM (Movimentate/Affidate)',
						         backgroundColor:'#88FF88',
				                 borderColor: '#FF6384',
				                 borderWidth: 1,
				                 data: []
							  };
							  this.gc2.data.datasets.push(newDatasetIPR);
							  this.gc2.data.datasets.push(newDatasetIPM);
							  if (arrRes.length>2) {
							  	this.gc2.options.scales.xAxes[0].ticks.minor.fontSize = 9;
							  	this.gc2.options.scales.xAxes[0].ticks.fontSize = 9;
							  }	
				        	  for (i = 0; i < arrRes.length; i++) {
							    this.gc2.data.labels[i]=arrRes[i].Agenzia;
							    this.gc2.data.datasets[0].data.push(arrRes[i].IPR);
							    this.gc2.data.datasets[1].data.push(arrRes[i].IPM);
							  }	
				        	} 
				        	this.gc2.update();
				        },	scope: this});	

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
					var dataType = this.comboData.getValue();
					if (record!=undefined) {
					  Ext.getCmp(this.task+'_title').update('<h1>'+dataType+' - Fiscal Year '+record.data.num +'</h1>');
					} else {
						 Ext.getCmp(this.task+'_title').update('<h1>'+dataType+' - Fiscal Year '+this.comboAnni.getValue() +'</h1>');
					}
					var g = FusionCharts(this.task+"_chartId3"); 
					if (g) g.dispose();
					g = new FusionCharts("FusionCharts/"+DCS.Charts.tipi[this.itipo]+".swf", this.task+"_chartId3", "100%", "90%", "0", "1" );
                    //g.setXMLUrl("server/charts/sintesiStory.php?type=stack&anno="+record.data.num+"&task="+this.task+"&data="+dataType); //pie");
                    //g.render(this.task+"_story");
                    var idPanel = this.id;
					var canvasStory = document.getElementById(this.id+'_canvasStory');
					var ctxStory = canvasStory.getContext("2d");
					if (this.gcStory!=null) {
                      this.gcStory.destroy();	
                    }
					this.gcStory = new Chart(ctxStory, {
			            type: 'bar',
			            data: {
				            labels: [],
				            datasets: []
				        },
				        plugins: [{
					        afterDatasetDraw: function(chartInstance, options) {
					            var yScale = chartInstance.scales["y-axis-0"];
					            var canvas = chartInstance.chart;
			                    var ctx = canvas.ctx;
			                    var index;
			                    var line;
			                    var style;
			
			                    if (chartInstance.options.horizontalLine) {
			                        for (index = 0; index < chartInstance.options.horizontalLine.length; index++) {
			                            line = chartInstance.options.horizontalLine[index];
							            style = (line.style) ? line.style : "rgba(169,169,169, .6)";
							            yValue = (line.y) ? yScale.getPixelForValue(line.y) : 0;
							            ctx.lineWidth = (line.width) ? line.width : 3;
							            if (yValue) {
							               ctx.beginPath();
							               ctx.moveTo(chartInstance.chartArea.left, yValue);
							               ctx.lineTo(chartInstance.chartArea.right, yValue);
							               ctx.strokeStyle = style;
							               ctx.stroke();
							            }
							            if (line.text) {
							               ctx.fillStyle = style;
							               ctx.fillText(line.text, 5, yValue + ctx.lineWidth);
							            }
			                        }
			                        return;
			                    };
					        },
					    }],
			            data: {
				            labels: [],
				            datasets: []
				        },
			            options: {
			                responsive: true,
			                hover: {
							    animationDuration : 0,
							    onHover: function(e, el) {
							      canvasStory.style.cursor = el[0] ? "pointer" : "";
							    }
							},
							layout: {
					            padding: {
					                left: 0,
					                right: 0,
					                top: 15,
					                bottom: 0
					            }
					        },
							barValueDisplay: {
						        color: 'rgba(0, 0, 0, 1)'
						    },
			                maintainAspectRatio: false,
			                legend: {
			                    position: 'bottom'
			                },
			                tooltips: {
			                	mode: 'single',
			                	position: 'cursor',
			                	backgroundColor: 'rgba(0, 0, 0, 1)',
			                	titleFontSize: 0,
			                	intersect: false,
							    callbacks: {
						            label: function(tooltipItem, data) {
						                return data.datasets[tooltipItem.datasetIndex].label+', '+tooltipItem.xLabel+', '+tooltipItem.yLabel+'%';
						            }
						        }
						    },
						    scales: {
					            yAxes: [{
					            	gridLines: {
		                                display: true
		                            },
		                            ticks: {
		                            	padding: 30,
					                    min: 0,
					                    suggestedMax: 100, 
						                stepSize: 20,
						                beginAtZero: true,
						                callback: function(label, index, labels) {
					                        return label+'%';
					                    }
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
						                    data = data+"%";
						                    ctx.fillStyle = configOptions.barValueDisplay.color;                            
						                    ctx.fillText(data, bar._model.x, bar._model.y);
						                });
						            });
						        }
						    },
                            onClick: handleClickStory
			            }
			        });
			        function handleClickStory(evt)
					{
					    var activePoints = this.getElementAtEvent(evt);
					    var firstPoint = activePoints[0];
						var label = this.data.labels[firstPoint._index];
						var res = label.split("/");
						DCS.Charts.presentaMese(idPanel,res[1]+res[0]);
					};    
			        /*Ext.Ajax.request({
				        url: 'server/charts/sintesiStory.php',
				        method: 'GET',
				        params: {type: 'stack', id:this.id, anno: record.data.num, task: this.task, data: dataType},
				        success: function(obj) {
							g.setXMLData(obj.responseText);
							g.render(this.task+"_story");
				        },	scope: this});*/
					if (record!=undefined) {
						var anno = record.data.num;
					} else {
					    var anno = this.comboAnni.getValue();
					}
                    Ext.Ajax.request({
				        url: 'server/charts/sintesiStory.php',
				        method: 'GET',
				        params: {type: 'stack', id:this.id, anno: anno, task: this.task, data: dataType},
				        success: function(obj) {
							strDataSet = new Array();
                            var jsonData = Ext.util.JSON.decode(obj.responseText);
                            var arrLabel = jsonData.categorie; 
                            var arrRes = jsonData.results;
                            var arrTarget = jsonData.target;
                            var colori = ['#99bbe8','#88ff88','#aa88ff','#3588aa','#489999','#66aa88','#02b955','#55ca00','#a2ca00','#ff4400','#ffca00','#cc0088','#aa2266','#bbaa99'];
                            if(arrRes.length>0) {
                            	for (i = 0; i < arrLabel.length; i++) {
                            	  this.gcStory.data.labels.push(arrLabel[i]);	
                            	}
                            	for (i = 0; i < arrTarget.length; i++) {
                            		target = arrTarget[i];
					        	  	var newHorizontalLine = {
				                        style: 'rgba(255, 0, 0, .7)',
				                        font: '10px "Helvetica Nueue"',
								        fontColor: 'rgba(255, 0, 0, .7)',
								        y: target,
								        text: "Target "+target+"%"
				                    };
				                    this.gcStory.options.horizontalLine.push(newHorizontalLine);
					        	}
                            	for (i = 0; i < arrRes.length; i++) {
					        		agenzia = arrRes[i].Agenzia;
					        		var ind = strDataSet.indexOf(agenzia);
									if (ind<0) {
										var newDataset = {
									        label: arrRes[i].Agenzia,
									        backgroundColor: colori[this.gcStory.data.datasets.length],
									        borderColor: '#FF6384',
									        borderWidth: 1,
									        data: new Array(12),
									    };
									    this.gcStory.data.datasets.push(newDataset);
										strDataSet.push(agenzia);
										ind = strDataSet.indexOf(agenzia);
									}
									//inserisco i valori dell'agenzia al mese corrispondente
								    data = arrRes[i].Mese;
								    anno = data.substr(0,4);
								    mese = data.substr(4,data.length);
								    indexLabel =this.gcStory.data.labels.indexOf(mese+"/"+anno); 
								    if (dataType=='IPR') {
								      this.gcStory.data.datasets[ind].data[indexLabel] = arrRes[i].IPR;	
								    } else {
								    	this.gcStory.data.datasets[ind].data[indexLabel] = arrRes[i].IPM;
								    }
								}
					        }
				        	this.gcStory.update();
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
				},this.comboTipo,this.comboData,this.comboAnni,this.comboMese]
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
			}]}, {
				xtype:'panel',
				hidden: true,
				id:this.task+'_story',
				html: '<canvas id="'+this.id+'_canvasStory" height="420px"></canvas>'
			}],
			listeners: {
				activate: function(pnl) {
					if (this.comboTipo.getValue() == '') {
						this.comboTipo.setValue('Mensile');
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

		DCS.Charts.Sintesi.superclass.initComponent.call(this, arguments);

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
		g.render(this.task+"_cc");
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
DCS.Charts.presentaMese = function(idGrafico,numMese)
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

//--------------------------------------------------------------------
// Tabella dei target, nella pagina del cruscotto=piramide
//--------------------------------------------------------------------
DCS.Charts.TargetTable = Ext.extend(Ext.grid.GridPanel, {
//	width:380,
	gstore: null,
	pagesize: 0,
	titlePanel: '',
		
	initComponent : function() {
		var fields = [{name: 'FasciaRecupero'},
		              {name: 'TitoloRegolaProvvigione'},
		              {name: 'Agenzia'},
		              {name: 'TargetIPR',type: 'int'},
		              {name: 'IPR',type: 'float'},
		              {name: 'IPF',type: 'float'},
		              {name: 'Mese',type: 'int'}];

		var	columns = [{dataIndex:'FasciaRecupero',width:70, header:'Fascia',sortable:false},
		   	           {dataIndex:'TitoloRegolaProvvigione',width:100, header:'Definizione',sortable:false,hidden:true},
		   	           {dataIndex:'Agenzia',width:120, header:'Agenzia',sortable:false},
		   	           {dataIndex:'TargetIPR',width:70, header:'Target IPR',sortable:false,align:'center'},
		   	           {dataIndex:'IPR',width:60, header:'IPR',sortable:false,align:'right',xtype:'numbercolumn',format:'000,00/i'},
		   	           {dataIndex:'IPF',width:60, header:'IPF',sortable:false,align:'right',xtype:'numbercolumn',format:'000,00/i'}];
		
		this.gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/charts/pyramid.php',
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

		DCS.Charts.TargetTable.superclass.initComponent.call(this, arguments);
	}
});
//-----------------------------------------------------------
// Griglia con i dati per area geografica
//-----------------------------------------------------------
DCS.Charts.GeoTable = Ext.extend(Ext.grid.GridPanel, {
	gstore: null,
	width: 2000,
	pagesize: 0,
	titlePanel: '',
	task:null,
		
	initComponent : function() {
		var fields,columns;
		if (this.task=="GEO")
		{
			fields = [{name: 'Area'},
		              {name: 'Totale',type:'float'},
		              {name: 'TotaleNum',type:'int'},
		              {name: 'Ats'},{name: 'AtsNum',type:'int'},
		              {name: 'City1'},{name: 'City1Num',type:'int'},
		              {name: 'City2'},{name: 'City2Num',type:'int'},
		              {name: 'Eurocollection'},{name: 'EurocollectionNum',type:'int'},
		              {name: 'Fides'},{name: 'FidesNum',type:'int'},
		              {name: 'GeaServices'},{name: 'GeaServicesNum',type:'int'},
		              {name: 'Kreos'},{name: 'KreosNum',type:'int'},
		              {name: 'Ncp31'},{name: 'Ncp31Num',type:'int'},
		              {name: 'Nicol35'},{name: 'Nicol35Num',type:'int'}, 
		              {name: 'Osirc'},{name: 'OsircNum',type:'int'},
		              {name: 'Sogec1'},{name: 'Sogec1Num',type:'int'},
		              {name: 'Sogec2'},{name: 'Sogec2Num',type:'int'},
		              {name: 'Starcredit'},{name: 'StarcreditNum',type:'int'}
//		              {name: 'Sting'},{name: 'StingNum',type:'int'}
		              ];

		 	columns = [{dataIndex:'Area',width:87, header:'Regione',sortable:false},
		   	           {dataIndex:'Totale',width:100, header:'TOTALE<br>IPR %',sortable:true,align:'right',css:'background-color:aquamarine;font-weight:bold;',xtype:'numbercolumn',format:'000,00 %/i'},
		   	           {dataIndex:'TotaleNum',width:50, header:'TOTALE<br>N.',sortable:true,align:'right'},
		   	           {dataIndex:'City1',width:100, header:'City (24)<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'City1Num',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'City2',width:100, header:'City (P4)<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'City2Num',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Eurocollection',width:100, header:'Eurocoll.<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'EurocollectionNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Fides',width:100, header:'Fides<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'FidesNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'GeaServices',width:100, header:'Gea Services<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'GeaServicesNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Kreos',width:100, header:'Kreos<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'KreosNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Ncp31',width:100, header:'NCP<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'Ncp31Num',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Nicol35',width:100, header:'Nicol (35)<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'Nicol35Num',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Osirc',width:100, header:'Osirc (2A)<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'OsircNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Sogec1',width:100, header:'Sogec (S2)<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'Sogec1Num',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Sogec2',width:100, header:'Sogec (P2)<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'Sogec2Num',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Starcredit',width:100, header:'Starcredit<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'StarcreditNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           //{dataIndex:'Sting',width:100, header:'Sting<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	        //{dataIndex:'StingNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'dummy',width:2}];
		}
		else if (this.task=="GEO2") // tabella per stragiudiziale
		{
			fields = [{name: 'Area'},
		              {name: 'Totale',type:'float'},
		              {name: 'TotaleNum',type:'int'},
		              {name: 'Css'},{name: 'CssNum',type:'int'},
		              {name: 'EY'},{name: 'EYNum',type:'int'},
		              {name: 'Fides'},{name: 'FidesNum',type:'int'},
		              {name: 'Fire'},{name: 'FireNum',type:'int'},
		              {name: 'Nicol'},{name: 'NicolNum',type:'int'},
		              {name: 'City'},{name: 'CityNum',type:'int'},
		              {name: 'Irc'},{name: 'IrcNum',type:'int'},
		              {name: 'Luzzi'},{name: 'LuzziNum',type:'int'}
		              ];

		 	columns = [{dataIndex:'Area',width:87, header:'Regione',sortable:false},
		   	           {dataIndex:'Totale',width:100, header:'TOTALE<br>IPR %',sortable:true,align:'right',css:'background-color:aquamarine;font-weight:bold;',xtype:'numbercolumn',format:'000,00 %/i'},
		   	           {dataIndex:'TotaleNum',width:50, header:'TOTALE<br>N.',sortable:true,align:'right'},
		   	           {dataIndex:'Css',width:100, header:'CSS<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'CssNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'EY',width:100, header:'ERNST & YOUNG<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'EYNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Fides',width:100, header:'FIDES<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'FidesNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Fire',width:100, header:'FIRE<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'FireNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Nicol',width:100, header:'NICOL<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'NicolNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'City',width:100, header:'CITY<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'CityNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Irc',width:100, header:'IRC<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'IrcNum',width:51, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},		   	         
		   	           {dataIndex:'Luzzi',width:100, header:'Studio Luzzi<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'LuzziNum',width:51, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},		   	         
					   {dataIndex:'dummy',width:2}];
		}
                else if (this.task=="GEO3") // tabella per legale
		{
			fields = [{name: 'Area'},
		              {name: 'Totale',type:'float'},
		              {name: 'TotaleNum',type:'int'},
		              {name: 'Luzzi'},{name: 'LuzziNum',type:'int'},
		              {name: 'LSCube'},{name: 'LSCube',type:'int'},
		              {name: 'Fides'},{name: 'FidesNum',type:'int'}
		              ];

		 	columns = [{dataIndex:'Area',width:87, header:'Regione',sortable:false},
		   	           {dataIndex:'Totale',width:100, header:'TOTALE<br>IPR %',sortable:true,align:'right',css:'background-color:aquamarine;font-weight:bold;',xtype:'numbercolumn',format:'000,00 %/i'},
		   	           {dataIndex:'TotaleNum',width:50, header:'TOTALE<br>N.',sortable:true,align:'right'},
		   	           {dataIndex:'Luzzi',width:100, header:'Luzzi<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'LuzziNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'LSCube',width:100, header:'LS Cube<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'LSCubeNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Fides',width:100, header:'FIDES<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'FidesNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
					   {dataIndex:'dummy',width:2}];
		}
		this.gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/charts/geography.php',
				method: 'GET'
			}),   
			/* specificati al momento della reload: baseParams: task:,anno:this.anno, mese:this.mese},*/
			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				fields: fields
			})
		});

		Ext.apply(this,{
			store: this.gstore,
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
			                '-', helpButton("TabellaGeo"),' '
						];
	
		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});

		DCS.Charts.GeoTable.superclass.initComponent.call(this, arguments);
	}
});
//-----------------------------------------------------------
// Pagina con il grafico "cruscotto" e la tabella dei target
//-----------------------------------------------------------
DCS.Charts.Pyramid = Ext.extend(Ext.Panel, {
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
					this.comboMese.setVisible(index==0);
					Ext.getCmp(this.task+'_pnl').setVisible(index==0);
					if (!this.comboMese.hidden) {
						if (this.comboMese.getValue() == '') {
							var oggi = new Date();
							this.selectMonth(oggi.dateFormat('Ym'));
						} else {
							this.selectMonth(this.comboMese.getValue());
						}
					}

					this.comboAnni.setVisible(index==1);
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
					}

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
		            // Costruisce il grafico
					var g = FusionCharts(this.task+"_chartId");
					if (g) g.dispose();
					g = new FusionCharts("FusionCharts/"+DCS.Charts.tipi[this.itipo]+".swf", this.task+"_chartId", "100%", "90%", "0", "1" );
                    //  g.setXMLUrl("server/charts/pyramid.php?type=stack&mese="+record.data.num);
                    //  g.render(this.task+"_pnl");
                    var canvasTFSI = document.getElementById(this.id+'_canvasTFSI');
                    var ctxTFSI = canvasTFSI.getContext("2d");
                    if (this.gcTFSI!=null) {
                      this.gcTFSI.destroy();	
                    }
                    this.gcTFSI = new Chart(ctxTFSI, {
			            type: 'horizontalBar',
						data: {
				            labels: [],
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
					                right: 30,
					                top: 0,
					                bottom: 0
					            }
					        },
							barValueDisplay: {
						        color: 'rgba(0, 0, 0, 1)'
						    },
			                legend: {
						    	display: false
						    },
			                tooltips: {
			                	mode: 'single',
			                	position: 'cursor',
			                	backgroundColor: 'rgba(0, 0, 0, 1)',
			                	titleFontSize: 0,
			                	intersect: false,
							    callbacks: {
						            label: function(tooltipItem, data) {
						                return tooltipItem.yLabel+', '+Ext.util.Format.number(tooltipItem.xLabel, '0.0/i');
						            }
						        }
						    },
						    scales: {
					            xAxes: [{
					            	ticks: {
					                	padding: 5,
					                	maxTicksLimit: 5,
					                	beginAtZero: true,
					                	callback: function (value) {
				                            return Ext.util.Format.number(value, '0.0/i');
				                        }
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
						                    data = Ext.util.Format.number(data, '0.0/i');
						                    ctx.fillStyle = configOptions.barValueDisplay.color;                            
						                    ctx.fillText(data, bar._model.x+15, bar._model.y+7);
						                });
						            });
						        }
						    }
			            }
			        });
			        /*Ext.Ajax.request({
				        url: 'server/charts/pyramid.php',
				        method: 'GET',
				        params: {type: 'stack', mese: record.data.num, gruppo: this.gruppo},
				        success: function(obj) {
				        	var result = obj.responseText;
				        	var parti  = result.split("\n");
							Ext.getCmp(this.task+'_title').update('<h1>' + record.data.FY + ' - ' + record.data.mese  + 
									'<br>('+ parti[0]+' pratiche affidate in totale)</h1>');
							
							g.setXMLData(parti[1]);
							g.render(this.task+"_pnl");
			        },	scope: this});*/	
			        Ext.Ajax.request({
				        url: 'server/charts/pyramid.php',
				        method: 'GET',
				        params: {type: 'stack', mese: record.data.num, gruppo: this.gruppo},
				        success: function(obj) {
				        	var jsonData = Ext.util.JSON.decode(obj.responseText);
				        	catRes = jsonData.categorie;
				        	arrRes = jsonData.results;
				        	affidati=0;
				        	if (arrRes.length>0) {
					        	var newDatasetTFSI = {
					                 //backgroundColor:'#99BBE8',
					                 backgroundColor: ['#99bbe8','#88ff88','#aa88ff','#3588aa','#489999','#66aa88','#02b955','#55ca00','#a2ca00','#ff4400','#ffca00','#cc0088','#aa2266','#bbaa99'],
					                 borderColor: '#FF6384',
					                 borderWidth: 1,
					                 data: []
					            };
					            this.gcTFSI.data.datasets.push(newDatasetTFSI); 
					        	for (i = 0; i < catRes.length; i++) {
					        		if (this.gcTFSI.data.labels.indexOf(catRes[i].FasciaRecupero)<0) {
					        		  this.gcTFSI.data.labels.push(catRes[i].FasciaRecupero);
					        		  this.gcTFSI.data.datasets[0].data.push(null);	
					        		}
					        	}
					        	for (i = 0; i < arrRes.length; i++) {
								    idx = this.gcTFSI.data.labels.indexOf(arrRes[i].chartFasciaRecupero);
								    //this.gcTFSI.data.labels[i]=arrRes[i].chartFasciaRecupero;
								    //this.gcTFSI.data.datasets[idx].data=[];
								    this.gcTFSI.data.datasets[0].data[idx]=arrRes[i].Affidati;
								    affidati += parseInt(arrRes[i].Affidati);
								}
							}	
				        	Ext.getCmp(this.task+'_title').update('<h1>' + record.data.FY + ' - ' + record.data.mese  + 
									'<br>('+ affidati +' pratiche affidate in totale)</h1>');
							this.gcTFSI.update();
			        },	scope: this});	
					
					// Aggiorna la griglia dei target
					this.grid.titlePanel = "Target "+record.data.mese;
					var gstore = this.grid.getStore();
					gstore.baseParams = {task:'table', mese:record.data.num, gruppo:this.gruppo};
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
					if (record!=undefined) {
					  Ext.getCmp(this.task+'_title').update('<h1>Fiscal Year '+record.data.num +'</h1>');
					} else {
						Ext.getCmp(this.task+'_title').update('<h1>Fiscal Year '+this.comboAnni.getValue() +'</h1>');
					}  
					var g = FusionCharts(this.task+"_chartId2");
					if (g) g.dispose();
					g = new FusionCharts("FusionCharts/"+DCS.Charts.tipi[this.itipo]+".swf", this.task+"_chartId2", "100%", "90%", "0", "1" );
					//g.setXMLUrl("server/charts/pyramid.php?type=stack&anno="+record.data.num);
                    //g.render(this.task+"_story");
					var canvasStoryTFSI = document.getElementById(this.id+'_canvasStoryTFSI');
					var ctxStoryTFSI = canvasStoryTFSI.getContext("2d");
                    if (this.gcStoryTFSI!=null) {
                      this.gcStoryTFSI.destroy();	
                    }
                    this.gcStoryTFSI = new Chart(ctxStoryTFSI, {
			            type: 'horizontalBar',
						data: {
				            labels: [],
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
					                right: 30,
					                top: 0,
					                bottom: 0
					            }
					        },
							barValueDisplay: {
						        color: 'rgba(0, 0, 0, 1)'
						    },
			                maintainAspectRatio: false,
			                legend: {
						    	display: false
						    },
			                tooltips: {
			                	mode: 'single',
			                	position: 'cursor',
			                	backgroundColor: 'rgba(0, 0, 0, 1)',
			                	titleFontSize: 0,
			                	intersect: false,
							    callbacks: {
						            label: function(tooltipItem, data) {
						                return tooltipItem.yLabel+', '+Ext.util.Format.number(tooltipItem.xLabel, '0.0/i');
						            }
						        }
						    },
						    scales: {
					            xAxes: [{
					            	ticks: {
					                	padding: 5,
					                	maxTicksLimit: 5,
					                	beginAtZero: true,
						                callback: function (value) {
				                            return Ext.util.Format.number(value, '0.0/i');
				                        }
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
						                    data = Ext.util.Format.number(data, '0.0/i');
						                    ctx.fillStyle = configOptions.barValueDisplay.color;                            
						                    ctx.fillText(data, bar._model.x+15, bar._model.y+7);
						                });
						            });
						        }
						    }
			            }
			        });
					/*Ext.Ajax.request({
				        url: 'server/charts/pyramid.php',
				        method: 'GET',
				        params: {type: 'stack', anno: record.data.num, gruppo: this.gruppo},
				        success: function(obj) {
				        	debugger;
				        	var result = obj.responseText;
				        	var parti  = result.split("\n");
							Ext.getCmp(this.task+'_title').update('<h1>Fiscal Year '+record.data.num  + 
									'<br>('+ parti[0]+' pratiche affidate in totale)</h1>');
							
							g.setXMLData(parti[1]);
							g.render(this.task+"_story");
				        },	scope: this});*/
				    if (record!=undefined) {
				      var anno = record.data.num;
				    } else {
				    	var anno = this.comboAnni.getValue(); 
				    }
				    Ext.Ajax.request({
				        url: 'server/charts/pyramid.php',
				        method: 'GET',
				        params: {type: 'stack', anno: anno, gruppo: this.gruppo},
				        success: function(obj) {
				        	var jsonData = Ext.util.JSON.decode(obj.responseText);
				        	catRes = jsonData.categorie;
				        	arrRes = jsonData.results;
				        	affidati=0;
				        	if (arrRes.length>0) {
				        	    var newDatasetStoryTFSI = {
					                 backgroundColor: ['#99bbe8','#88ff88','#aa88ff','#3588aa','#489999','#66aa88','#02b955','#55ca00','#a2ca00','#ff4400','#ffca00','#cc0088','#aa2266','#bbaa99'],
					                 borderColor: '#FF6384',
					                 borderWidth: 1,
					                 data: []
					            };
					            this.gcStoryTFSI.data.datasets.push(newDatasetStoryTFSI); 
					        	for (i = 0; i < catRes.length; i++) {
					        		if (this.gcStoryTFSI.data.labels.indexOf(catRes[i].FasciaRecupero)<0) {
					        		  this.gcStoryTFSI.data.labels.push(catRes[i].FasciaRecupero);
					        		  this.gcStoryTFSI.data.datasets[0].data.push(null);	
					        		}
					        	}
					        	for (i = 0; i < arrRes.length; i++) {
								    idx = this.gcStoryTFSI.data.labels.indexOf(arrRes[i].chartFasciaRecupero);
								    this.gcStoryTFSI.data.datasets[0].data[idx]=arrRes[i].Affidati;
								    affidati += parseInt(arrRes[i].Affidati);
								}	
				        	}
				        	if (record!=undefined) {    
							  Ext.getCmp(this.task+'_title').update('<h1>Fiscal Year '+record.data.num  + 
									'<br>('+affidati+' pratiche affidate in totale)</h1>');
						    } else {
						    	Ext.getCmp(this.task+'_title').update('<h1>Fiscal Year '+this.comboAnni.getValue()  + 
									'<br>('+affidati+' pratiche affidate in totale)</h1>');
						    }  
				        	
							this.gcStoryTFSI.update();
				        },	scope: this});    	
					// Aggiorna la griglia dei target
				    var gstore = this.grid.getStore();    
				    if (record!=undefined) {    
					  this.grid.titlePanel = "Target Fiscal Year "+record.data.num;
					  gstore.baseParams = {task:'table', anno:record.data.num, gruppo:this.gruppo};
				    } else {
				    	this.grid.titlePanel = "Target Fiscal Year "+this.comboAnni.getValue();
					    gstore.baseParams = {task:'table', anno:this.comboAnni.getValue(), gruppo:this.gruppo};
				    }  
					
					//gstore.baseParams = {task:'table', anno:record.data.num, gruppo:this.gruppo};
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
				},this.comboTipo,this.comboAnni,this.comboMese]
			},{
				xtype:'panel',
				layout: 'column',
				id:this.task+'_page',
				items: [{
					xtype:'panel',
					columnWidth: .6,
					layout: 'column',
					id:this.task+'_pnl',
					html: '<canvas id="'+this.id+'_canvasTFSI"></canvas>'
				},{
					xtype:'panel',
					hidden: true,
					columnWidth: .6,
					layout: 'column',
					id:this.task+'_story',
					html: '<canvas id="'+this.id+'_canvasStoryTFSI" height="420px"></canvas>'
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

		DCS.Charts.Pyramid.superclass.initComponent.call(this, arguments);

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
//------------------------------------------------------------
// Pagina con la tabella per area geografica
//------------------------------------------------------------
DCS.Charts.Geography = Ext.extend(Ext.Panel, {
	titlePanel: '',
	task: '',
	dsMese: null,
	dsAnni: null,
	comboTipo: null,
	comboMese: null,
	comboAnni: null,
	grid: null,
		
	initComponent : function() {
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
					this.comboMese.setVisible(index==0);
					if (!this.comboMese.hidden) {
						if (this.comboMese.getValue() == '') {
							var oggi = new Date();
							this.selectMonth(oggi.dateFormat('Ym'));
						} else {
							this.selectMonth(this.comboMese.getValue());
						}
					}

					this.comboAnni.setVisible(index==1);
					if (!this.comboAnni.hidden) {
						if (this.comboAnni.getValue() == '') {
							var oggi = new Date();
							var fy = oggi.dateFormat('Y');
							if (oggi.dateFormat('m')>LAST_FY_MONTH) fy++;
							this.selectYear(fy);
						} else {
							this.selectYear(this.comboAnni.getValue());
						}
					}

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
				// alla selezione di anno o mese nella combobox, riempie tabella
				select: function(combo, record, index) {
					Ext.getCmp(this.task+'_title').update('<h1>' + record.data.FY + ' - ' + record.data.mese  + ' </h1>'
							+'<br><font size=2>Recupero esattoriale per Regione</font></h1>');
					this.grid.titlePanel = "Risultati per regione - " + record.data.FY + ' - ' + record.data.mese;
					var gstore = this.grid.getStore();
					gstore.baseParams = {task:this.task,mese:record.data.num, gruppo:this.gruppo};
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
					if (record!=undefined) {
					  Ext.getCmp(this.task+'_title').update('<h1>Fiscal Year '+record.data.num +'</h1>');
					  this.grid.titlePanel = "Risultati per regione - Fiscal Year "+record.data.num;
					} else {
						Ext.getCmp(this.task+'_title').update('<h1>Fiscal Year '+this.comboAnni.getValue() +'</h1>');
						this.grid.titlePanel = "Risultati per regione - Fiscal Year "+this.comboAnni.getValue();
					}
		
					
					var gstore = this.grid.getStore();
					if (record!=undefined) { 
					  gstore.baseParams = {task:this.task,anno:record.data.num, gruppo:this.gruppo};
					} else {
					   gstore.baseParams = {task:this.task,anno:this.comboAnni.getValue(), gruppo:this.gruppo};
					}  
					gstore.reload();
				},
				scope: this
			}
		});

		Ext.apply(this,{
			header: true,
			border: false,
			layout: 'vbox',
			layoutConfig: {align: 'stretch'},
			items: [{
				xtype:'panel',
				layout: 'hbox',
				items:[{
					id: this.task+'_title',
					xtype: 'box',
					style: 'text-align:center;',
					flex: 1
				},this.comboTipo,this.comboAnni,this.comboMese]
			},Ext.apply(this.grid,{flex:1})],// fine array dei due item che costituiscono la pagina (titolo+combos , tabella)
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

		DCS.Charts.Geography.superclass.initComponent.call(this, arguments);

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

DCS.Charts.Tabs = function(){
	tabPanelAzienda = null;

	return {
		create: function(gruppo){		
			this.tabPanelAzienda = new Ext.TabPanel({
				enableTabScroll: true,
				flex: 1,
				items: []
			});
			Ext.Ajax.request({
				url: 'server/AjaxRequest.php', method:'POST',
				params: { 
					task: 'read',
					sql: "SELECT DISTINCT FasciaRecupero FROM v_graph_provvigione WHERE IdReparto="+CONTEXT.IdReparto+" and gruppo="+gruppo+" AND FasciaVecchia='N'"
				},
				scope: this,
	        	success: function(xhr) {
                	eval('var resp = '+xhr.responseText);
					var arr = resp.results;
					for (var i=0; i<resp.total; i++) {
						var fascia = arr[i].FasciaRecupero;
						this.tabPanelAzienda.add(new DCS.Charts.Sintesi({
							titlePanel: 'Sintesi',
							title: fascia,
							task: fascia
						}));
					}
					this.tabPanelAzienda.doLayout();
					this.tabPanelAzienda.setActiveTab(0);
				}
			});
			return tabPanelAzienda;
		},
		
		create_TFSI: function(){
			items = Array();
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_INS || CONTEXT.CAN_GRAPH_INS_TEK)
				items.push(new DCS.Charts.Sintesi({
					titlePanel: 'Prerecupero: < 30 gg',
					title: 'INS+TEK',
					task: 'INS,INS+TEK',id: 'graphINS'
					}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_INS_TEK_REC)
				items.push(new DCS.Charts.Sintesi({
					titlePanel: 'INS+TEK Recidivo 0-30 gg',
					title: 'INS+TEK Rec',
					task: 'INS+TEK REC',id: 'graphINSREC'
					}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_PHONE || CONTEXT.CAN_GRAPH_PHONE_COLL
					|| CONTEXT.CAN_GRAPH_I_ESA || CONTEXT.CAN_GRAPH_1o_HOME || CONTEXT.CAN_GRAPH_1o_HOME_COLL || CONTEXT.CAN_GRAPH_1o_HOME_LOAN)
				items.push(new DCS.Charts.Sintesi({
				titlePanel: 'Home collection 1 Livello: 31-60 gg',
				title: '1&deg; HOME COLL',
				task: '1&deg; HOME,1&deg; HOME COLL,1&deg; HOME LOAN,I ESA',	id: 'graph1ESA'
				}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_II_ESA_LOAN || CONTEXT.CAN_GRAPH_2o_HOME_LOAN)
				items.push(new DCS.Charts.Sintesi({
				titlePanel: 'Loan Home coll. 2&deg; livello: 61-90 gg',
				title: '2&deg; HOME LOAN',
				task: '2&deg; HOME LOAN,II ESA,II ESA LOAN',	id: 'graph2ESA'
				}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_ESA_LEASING || CONTEXT.CAN_GRAPH_2o_HOME_LEASING)
				items.push(new DCS.Charts.Sintesi({
				titlePanel: 'Leasing Home 2&deg; livello: >60 gg',
				title: '2&deg; HOME LEASING',
				task: '2&deg; HOME LEASING,ESA LEASING',	id: 'graphLEA'
				}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_III_ESA_LOAN || CONTEXT.CAN_GRAPH_3o_HOME_LOAN)
				items.push(new DCS.Charts.Sintesi({
				titlePanel: 'Loan Home coll. 3&deg; Livello: 91-150 gg',
				title: '3&deg; HOME LOAN',
				task: '3&deg; HOME LOAN,III ESA LOAN',	id: 'graph3ESA'
				}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_IV_ESA_LOAN || CONTEXT.CAN_GRAPH_4o_HOME_LOAN)
				items.push(new DCS.Charts.Sintesi({
				titlePanel: 'Loan Home coll. 4&deg; Livello: > 150gg',
				title: '4&deg; HOME LOAN',
				task: '4&deg; HOME LOAN,IV ESA LOAN',	id: 'graph4ESA'
				}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_1o_FLOTTE)
				items.push(new DCS.Charts.Sintesi({
				titlePanel: 'Flotte 1&deg; livello: 31-90 gg',
				title: '1&deg; FLOTTE',
				task: '1&deg; FLOTTE',	id: 'graphFLO1'
				}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_2o_FLOTTE || CONTEXT.CAN_GRAPH_FLOTTE)
				items.push(new DCS.Charts.Sintesi({
				titlePanel: 'Flotte 2&deg; livello: >90 gg',
				title: '2&deg; FLOTTE',
				task: '2&deg; FLOTTE,FLOTTE',	id: 'graphFLO2'
				}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_SALDI_POS)
				items.push(new DCS.Charts.Sintesi({
				titlePanel: 'Saldi positivi',
				title: 'SALDI POS.',
				task: 'SALDI POS',	id: 'graphSP'
				}));


			var targetGrid = new DCS.Charts.TargetTable({
				titlePanel: 'Target del periodo',
				stateId: 'TargetTable1',
				stateful: true, gruppo: 1
				});
			
			if (CONTEXT.CAN_GRAPH_ALL)
				items.push(new DCS.Charts.Pyramid({
				titlePanel: 'Cruscotto TFSI',
				title: 'Cruscotto TFSI',
				task: 'PYRAMID', id:'graphTFSI',
				grid: targetGrid, gruppo: 1
				}));

			var geoGrid = new DCS.Charts.GeoTable({
				stateId: 'GeoTable1',
				stateful: true,task: 'GEO',
				titlePanel: 'Risultati recupero esattoriale per regione'
				});
			
			if (CONTEXT.CAN_GRAPH_ALL)
				items.push(new DCS.Charts.Geography({
				titlePanel: 'Aree geografiche',
				title: 'Aree geografiche',
				task: 'GEO', grid:geoGrid
				}));

			return new Ext.TabPanel({
    			activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				items: items
			});
		},
		create_TFSI_STR: function(){
			items = Array();
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_REPO)
				items.push(new DCS.Charts.Sintesi({
					titlePanel: 'Stragiudiziale: REPO',
					title: 'REPO',
					task: 'REPO',id: 'graphREPO'
					}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_DBT_SOFT)
				items.push(new DCS.Charts.Sintesi({
					titlePanel: 'Stragiudiziale: DBT Soft',
					title: 'DBT Soft',
					task: 'DBT SOFT',id: 'graphDBTsoft'
					}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_DBT_HARD)
				items.push(new DCS.Charts.Sintesi({
					titlePanel: 'Stragiudiziale: DBT Hard',
					title: 'DBT Hard',
					task: 'DBT HARD',id: 'graphDBThard'
					}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_DBT_STRONG)
				items.push(new DCS.Charts.Sintesi({
				titlePanel: 'Stragiudiziale: DBT Strong',
				title: 'DBT Strong',
				task: 'DBT STRONG',	id: 'graphDBTstrong'
				}));

			var targetGrid = new DCS.Charts.TargetTable({
				titlePanel: 'Target del periodo',
				stateId: 'TargetTable2',
				stateful: true, gruppo: 2,
				hidden: false
				});
			
			if (CONTEXT.CAN_GRAPH_ALL)
			{
				items.push(new DCS.Charts.Pyramid({
				titlePanel: 'Cruscotto TFSI',
				title: 'Cruscotto TFSI',
				task: 'PYRAMIDSTR', id:'graphTFSISTR',
				grid: targetGrid, gruppo: 2
				}));
				
				var geoGrid = new DCS.Charts.GeoTable({
					stateId: 'GeoTable2',
					stateful: true,task: 'GEO2',
					titlePanel: 'Risultati recupero stragiudiziale per regione'
					});

				items.push(new DCS.Charts.Geography({
				titlePanel: 'Aree geografiche',
				title: 'Aree geografiche',
				task: 'GEO2', grid:geoGrid
				}));
			}
			return new Ext.TabPanel({
    			activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				items: items
			});
		},		
		create_TFSI_LEG: function(){
			items = Array();
			if (CONTEXT.CAN_GRAPH_ALL)
				items.push(new DCS.Charts.Sintesi({
					titlePanel: 'Legale',
					title: 'LEGALE',
					task: 'LEGALE',id: 'graphLEGALE'
					}));
			var targetGrid = new DCS.Charts.TargetTable({
				titlePanel: 'Target del periodo',
				stateId: 'TargetTable3',
				stateful: true, gruppo: 2,
				hidden: false
				});
			
			if (CONTEXT.CAN_GRAPH_ALL)
			{
				items.push(new DCS.Charts.Pyramid({
				titlePanel: 'Cruscotto TFSI',
				title: 'Cruscotto TFSI',
				task: 'PYRAMIDLEG', id:'graphTFSILEG',
				grid: targetGrid, gruppo: 2
				}));
				
				var geoGrid = new DCS.Charts.GeoTable({
					stateId: 'GeoTable3',
					stateful: true,task: 'GEO3',
					titlePanel: 'Risultati recupero legale per regione'
					});

				items.push(new DCS.Charts.Geography({
				titlePanel: 'Aree geografiche',
				title: 'Aree geografiche',
				task: 'GEO3', grid:geoGrid
				}));
			}
			return new Ext.TabPanel({
    			activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				items: items
			});
		}	
            };
	
}();
