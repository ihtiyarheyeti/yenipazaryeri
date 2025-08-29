-- marketplace_connections tablosuna base_url kolonu ekle
USE yenipazaryeri;

ALTER TABLE marketplace_connections 
ADD COLUMN base_url VARCHAR(255) NULL AFTER supplier_id;
