create table if not exists subcategories (
    id bigint unsigned primary key auto_increment,
    module_key varchar(40) not null default 'mart',
    category_id bigint unsigned not null,
    name varchar(190) not null,
    slug varchar(220) not null,
    image varchar(255) null,
    status tinyint(1) not null default 1,
    sort_order int not null default 0,
    created_at timestamp null,
    updated_at timestamp null,
    index subcategories_module_category_index (module_key, category_id),
    unique key subcategories_module_slug_unique (module_key, slug)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

set @subcategory_column_exists = (
    select count(*)
    from information_schema.columns
    where table_schema = database()
      and table_name = 'products'
      and column_name = 'subcategory_id'
);
set @subcategory_column_sql = if(
    @subcategory_column_exists = 0,
    'alter table products add column subcategory_id bigint unsigned null after category_id, add index products_subcategory_id_index (subcategory_id)',
    'select 1'
);
prepare subcategory_column_statement from @subcategory_column_sql;
execute subcategory_column_statement;
deallocate prepare subcategory_column_statement;
