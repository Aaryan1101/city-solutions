create table if not exists admins (
    id integer primary key autoincrement,
    name text not null,
    email text not null unique,
    password text not null,
    role text not null default 'super_admin',
    created_at text,
    updated_at text
);

create table if not exists zones (
    id integer primary key autoincrement,
    name text not null,
    city text,
    state text,
    pincode text,
    pincodes text,
    latitude real,
    longitude real,
    radius_km real not null default 0,
    status integer not null default 1,
    sort_order integer not null default 0,
    created_at text,
    updated_at text
);

create table if not exists categories (
    id integer primary key autoincrement,
    module_key text not null default 'mart',
    name text not null,
    slug text not null,
    image text,
    shipping_cost real not null default 0,
    status integer not null default 1,
    sort_order integer not null default 0,
    created_at text,
    updated_at text
);

create table if not exists subcategories (
    id integer primary key autoincrement,
    module_key text not null default 'mart',
    category_id integer not null,
    name text not null,
    slug text not null,
    image text,
    status integer not null default 1,
    sort_order integer not null default 0,
    created_at text,
    updated_at text
);

create index if not exists subcategories_module_category_index
    on subcategories (module_key, category_id);

create table if not exists brands (
    id integer primary key autoincrement,
    module_key text not null default 'mart',
    name text not null,
    slug text not null,
    image text,
    status integer not null default 1,
    sort_order integer not null default 0,
    created_at text,
    updated_at text
);

create table if not exists products (
    id integer primary key autoincrement,
    module_key text not null default 'mart',
    zone_id integer,
    vendor_id integer,
    brand_id integer,
    category_id integer,
    subcategory_id integer,
    name text not null,
    slug text not null,
    description text,
    unit text not null default 'piece',
    price real not null default 0,
    discount_price real,
    stock integer not null default 0,
    sku text,
    thumbnail text,
    tax_percent real not null default 0,
    shipping_cost real not null default 0,
    barcode text,
    seo_title text,
    seo_description text,
    attributes_json text,
    colors_json text,
    is_digital integer not null default 0,
    digital_file_url text,
    is_flash_deal integer not null default 0,
    flash_deal_ends_at text,
    is_clearance integer not null default 0,
    medicine_type text,
    schedule_tag text,
    max_qty_per_order integer,
    max_qty_per_month integer,
    requires_pharmacist_review integer not null default 0,
    requires_age_confirmation integer not null default 0,
    status integer not null default 1,
    is_featured integer not null default 0,
    created_at text,
    updated_at text
);

create table if not exists product_images (
    id integer primary key autoincrement,
    product_id integer not null,
    image text not null,
    sort_order integer not null default 0,
    created_at text
);

create table if not exists product_variants (
    id integer primary key autoincrement,
    product_id integer not null,
    name text not null,
    unit text not null default 'piece',
    price real not null default 0,
    discount_price real,
    stock integer not null default 0,
    sku text,
    status integer not null default 1,
    created_at text,
    updated_at text
);

create table if not exists banners (
    id integer primary key autoincrement,
    module_key text not null default 'mart',
    title text,
    image text,
    link_type text not null default 'none',
    link_value text,
    status integer not null default 1,
    sort_order integer not null default 0,
    created_at text,
    updated_at text
);

create table if not exists carts (
    id integer primary key autoincrement,
    module_key text not null default 'mart',
    guest_id text not null,
    product_id integer not null,
    variant_id integer,
    quantity integer not null default 1,
    price real not null default 0,
    created_at text,
    updated_at text
);

create table if not exists coupons (
    id integer primary key autoincrement,
    module_key text not null default 'mart',
    code text not null unique,
    title text not null,
    discount_type text not null default 'flat',
    discount_value real not null default 0,
    minimum_order_amount real not null default 0,
    maximum_discount real,
    usage_limit integer,
    used_count integer not null default 0,
    starts_at text,
    expires_at text,
    status integer not null default 1,
    created_at text,
    updated_at text
);

create table if not exists cart_coupons (
    guest_id text primary key,
    module_key text not null default 'mart',
    coupon_id integer not null,
    code text not null,
    created_at text,
    updated_at text
);

create table if not exists customers (
    id integer primary key autoincrement,
    name text not null,
    phone text not null unique,
    email text,
    password text,
    auth_token text,
    firebase_uid text,
    auth_provider text,
    email_verified integer not null default 0,
    phone_verified integer not null default 0,
    profile_photo text,
    status integer not null default 1,
    created_at text,
    updated_at text
);

create unique index if not exists customers_firebase_uid_unique on customers(firebase_uid);

create table if not exists customer_addresses (
    id integer primary key autoincrement,
    customer_id integer not null,
    label text,
    contact_name text,
    contact_phone text,
    address text not null,
    city text,
    state text,
    pincode text,
    is_default integer not null default 0,
    created_at text,
    updated_at text
);

create table if not exists vendors (
    id integer primary key autoincrement,
    module_key text not null default 'mart',
    zone_id integer,
    shop_name text not null,
    owner_name text not null,
    phone text not null,
    email text,
    password text not null,
    auth_token text,
    address text,
    city text,
    status text not null default 'pending',
    admin_note text,
    created_at text,
    updated_at text
);

create unique index if not exists vendors_module_phone_unique on vendors(module_key, phone);

create table if not exists delivery_men (
    id integer primary key autoincrement,
    name text not null,
    phone text not null,
    email text,
    vehicle_type text,
    vehicle_number text,
    status integer not null default 1,
    created_at text,
    updated_at text
);

create table if not exists orders (
    id integer primary key autoincrement,
    module_key text not null default 'mart',
    zone_id integer,
    vendor_id integer,
    delivery_man_id integer,
    order_number text not null unique,
    guest_id text,
    customer_name text,
    customer_phone text,
    customer_email text,
    address text,
    order_amount real not null default 0,
    coupon_code text,
    coupon_discount real not null default 0,
    shipping_method_id integer,
    shipping_method_name text,
    shipping_cost real not null default 0,
    expected_delivery text,
    payment_method text not null default 'cash_on_delivery',
    payment_status text not null default 'unpaid',
    order_status text not null default 'pending',
    delivery_assigned_at text,
    order_note text,
    created_at text,
    updated_at text
);

create table if not exists order_items (
    id integer primary key autoincrement,
    order_id integer not null,
    vendor_id integer,
    product_id integer not null,
    variant_id integer,
    variant_name text,
    product_name text not null,
    quantity integer not null default 1,
    price real not null default 0,
    total real not null default 0,
    status text not null default 'pending',
    created_at text,
    updated_at text
);

create table if not exists order_status_history (
    id integer primary key autoincrement,
    order_id integer not null,
    order_item_id integer,
    status text not null,
    actor_type text not null,
    actor_name text,
    note text,
    created_at text
);

create table if not exists notifications (
    id integer primary key autoincrement,
    recipient_type text not null,
    recipient_id integer,
    guest_id text,
    title text not null,
    message text,
    order_id integer,
    read_at text,
    created_at text
);

create table if not exists wishlists (
    id integer primary key autoincrement,
    guest_id text not null,
    product_id integer not null,
    created_at text
);

create table if not exists product_reviews (
    id integer primary key autoincrement,
    product_id integer not null,
    vendor_id integer,
    guest_id text not null,
    customer_name text,
    rating integer not null default 5,
    comment text,
    reply text,
    status integer not null default 1,
    created_at text,
    updated_at text
);

create table if not exists refund_requests (
    id integer primary key autoincrement,
    order_id integer not null,
    order_item_id integer,
    vendor_id integer,
    guest_id text,
    customer_name text,
    customer_phone text,
    amount real not null default 0,
    reason text not null,
    note text,
    admin_note text,
    status text not null default 'pending',
    created_at text,
    updated_at text
);

create table if not exists payment_transactions (
    id integer primary key autoincrement,
    module_key text not null default 'mart',
    order_id integer not null,
    guest_id text,
    customer_name text,
    customer_phone text,
    payment_method text not null,
    amount real not null default 0,
    reference text,
    note text,
    status text not null default 'pending',
    admin_note text,
    gateway_response text,
    reconciled_at text,
    reconciled_by text,
    created_at text,
    updated_at text
);

create table if not exists payment_webhook_events (
    id integer primary key autoincrement,
    module_key text not null,
    event_key text not null,
    payload_hash text not null,
    status text not null default 'processed',
    received_at text,
    processed_at text
);

create unique index if not exists payment_webhook_events_key_unique on payment_webhook_events (module_key, event_key);
create index if not exists payment_webhook_events_hash_index on payment_webhook_events (payload_hash);

create table if not exists wallet_accounts (
    id integer primary key autoincrement,
    owner_type text not null,
    owner_key text not null,
    balance real not null default 0,
    created_at text,
    updated_at text
);

create unique index if not exists wallet_accounts_owner_unique on wallet_accounts (owner_type, owner_key);

create table if not exists wallet_ledgers (
    id integer primary key autoincrement,
    wallet_account_id integer not null,
    owner_type text not null,
    owner_key text not null,
    direction text not null,
    amount real not null default 0,
    entry_type text not null,
    reference_key text,
    description text,
    created_at text,
    updated_at text
);

create unique index if not exists wallet_ledgers_reference_unique on wallet_ledgers (owner_type, owner_key, reference_key);

create table if not exists withdrawal_requests (
    id integer primary key autoincrement,
    vendor_id integer not null default 0,
    owner_type text not null default 'vendor',
    owner_key text,
    amount real not null default 0,
    bank_details text,
    note text,
    status text not null default 'pending',
    admin_note text,
    created_at text,
    updated_at text
);

create table if not exists shipping_methods (
    id integer primary key autoincrement,
    name text not null,
    description text,
    cost real not null default 0,
    expected_days text,
    sort_order integer not null default 0,
    status integer not null default 1,
    created_at text,
    updated_at text
);

create table if not exists notifications (
    id integer primary key autoincrement,
    recipient_type text not null,
    recipient_id integer,
    guest_id text,
    title text not null,
    message text,
    order_id integer,
    read_at text,
    created_at text
);

create table if not exists device_tokens (
    id integer primary key autoincrement,
    module_key text not null default 'mart',
    owner_type text not null default 'customer',
    owner_id integer,
    guest_id text,
    token text not null,
    platform text,
    last_seen_at text,
    created_at text,
    updated_at text
);

create table if not exists push_outbox (
    id integer primary key autoincrement,
    module_key text not null,
    recipient_type text not null,
    recipient_id integer,
    guest_id text,
    title text not null,
    message text,
    data_json text,
    attempts integer not null default 0,
    last_error text,
    sent_at text,
    created_at text,
    updated_at text
);

create table if not exists settings (
    key_name text primary key,
    value text,
    updated_at text
);
