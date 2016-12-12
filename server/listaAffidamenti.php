<?
// temporaneo: lista insoluti correnti
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Lista Affidamenti Correnti</title>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<? include_once "stylesheets.inc"?>
<? include_once "scripts.inc"?>
<script type="text/javascript" src="grigliaComunicazioni.js"></script>
<script type="text/javascript" src="grigliaAffidamenti.js"></script>
<script type="text/javascript" src="dettaglioAffidamento.js"></script>
<!-- scripts di avvio -->
<script lang="javascript">
Ext.onReady(function(){
    // NOTE: This is an example showing simple state management. During development,
    // it is generally best to disable state management as dynamically-generated ids
    // can change across page loads, leading to unpredictable results.  The developer
    // should ensure that stable state ids are set for stateful components in real apps.
    Ext.state.Manager.setProvider(new Ext.state.CookieProvider());

    Ext.QuickTips.init();
    var cmp1 = new tabPanelAffidamenti({
        renderTo: Ext.getBody()
    });
    cmp1.show();
});

</script>

</head>

<body>

</body>

</html>
