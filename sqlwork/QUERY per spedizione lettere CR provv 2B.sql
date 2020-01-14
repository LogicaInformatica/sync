select * from automatismo  where idautomatismo in (113,115);

select * from messaggiodifferito where idmodello in (113,115)  and date(datacreazione)=CURDATE() and dataemissione IS NULL;

insert into messaggiodifferito (IdModello,IdContratto,Stato,Tipo,DataCreazione,TestoMessaggio)
select 113,IdContratto,'C','L',NOW(),'Lettera preavviso centrale rischi'
FROM contratto WHERE CodRegolaProvvigione='2B';
 
insert into messaggiodifferito (IdModello,IdContratto,Stato,Tipo,DataCreazione,TestoMessaggio)
select 115,IdContratto,'C','L',NOW(),'Lettera preavviso centrale rischi (garante)'
FROM contratto c WHERE CodRegolaProvvigione='2B' AND  EXISTS (SELECT 1 FROM v_recapiti_mandato v WHERE v.IdContratto=c.IdContratto and FlagGarante='Y');