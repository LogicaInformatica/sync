CREATE OR REPLACE VIEW v_experian_sintesi AS
    SELECT 
        f.IdExperian AS IdExperian,
        f.DataInvio AS DataInvio,
        f.DataRisposta AS DataRisposta,
        COUNT(e.IdExperian) AS NumClienti,
        COUNT(IF(D4CScoreIndex = 1, 1, NULL)) AS Score_1,
        COUNT(IF(D4CScoreIndex = 2, 1, NULL)) AS Score_2,
        COUNT(IF(D4CScoreIndex = 3, 1, NULL)) AS Score_3,
        COUNT(IF(D4CScoreIndex = 4, 1, NULL)) AS Score_4,
        COUNT(IF(D4CScoreIndex = 5, 1, NULL)) AS Score_5,
        COUNT(IF(D4CScoreIndex = 6, 1, NULL)) AS Score_6,
        COUNT(IF(D4CScoreIndex = 7, 1, NULL)) AS Score_7,
        COUNT(IF(D4CScoreIndex = 8, 1, NULL)) AS Score_8,
        COUNT(IF(D4CScoreIndex = 9, 1, NULL)) AS Score_9,
        COUNT(IF(D4CScoreIndex = 10, 1, NULL)) AS Score_10
	FROM experianfile f
    LEFT JOIN experian e  ON e.IdExperian = f.IdExperian
    GROUP BY f.IdExperian