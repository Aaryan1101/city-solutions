set @schema_name = database();

set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'customers' and column_name = 'firebase_uid') = 0, 'alter table customers add column firebase_uid varchar(128) null', 'select 1'); prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'customers' and column_name = 'auth_provider') = 0, 'alter table customers add column auth_provider varchar(40) null', 'select 1'); prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'customers' and column_name = 'email_verified') = 0, 'alter table customers add column email_verified tinyint(1) not null default 0', 'select 1'); prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'customers' and column_name = 'phone_verified') = 0, 'alter table customers add column phone_verified tinyint(1) not null default 0', 'select 1'); prepare stmt from @sql; execute stmt; deallocate prepare stmt;
set @sql = if((select count(*) from information_schema.columns where table_schema = @schema_name and table_name = 'customers' and column_name = 'profile_photo') = 0, 'alter table customers add column profile_photo text null', 'select 1'); prepare stmt from @sql; execute stmt; deallocate prepare stmt;

set @sql = if((select count(*) from information_schema.statistics where table_schema = @schema_name and table_name = 'customers' and index_name = 'customers_firebase_uid_unique') = 0, 'create unique index customers_firebase_uid_unique on customers (firebase_uid)', 'select 1'); prepare stmt from @sql; execute stmt; deallocate prepare stmt;

insert into settings (key_name, value, updated_at) values
('firebase_auth_enabled', '0', current_timestamp),
('firebase_phone_auth_enabled', '0', current_timestamp),
('firebase_google_auth_enabled', '0', current_timestamp)
on duplicate key update updated_at = current_timestamp;
