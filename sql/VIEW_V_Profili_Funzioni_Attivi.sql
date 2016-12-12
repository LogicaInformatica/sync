create or replace view v_profili_funzioni_attivi
as
SELECT * FROM profilofunzione where now() between dataini and datafin;