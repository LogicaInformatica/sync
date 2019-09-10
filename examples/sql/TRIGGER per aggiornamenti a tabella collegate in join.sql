/* Ulteriori trigger per provocare l'update di righe collegate a righe cancellate o inserite */
DROP TRIGGER IF EXISTS deleted_from_cms_gallerymedia;
delimiter $$ 
CREATE TRIGGER deleted_from_cms_gallerymedia 
	AFTER DELETE ON `cms_gallerymedia` 
	FOR EACH ROW
BEGIN
   UPDATE cms_gallery SET LastUpd=NOW() WHERE IdGallery=old.IdGallery;
END
$$
DELIMITER ;

DROP TRIGGER IF EXISTS insert_into_cms_gallerymedia;
delimiter $$ 
CREATE TRIGGER insert_into_cms_gallerymedia 
	AFTER INSERT ON `cms_gallerymedia` 
	FOR EACH ROW
BEGIN
   UPDATE cms_gallery SET LastUpd=NOW() WHERE IdGallery=new.IdGallery;
END
$$
DELIMITER ;
