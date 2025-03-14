create table db_users (id int primary key auto_increment, username varchar(255) unique);

create table db_orders (id int primary key auto_increment, user_id int not null, amount decimal(10,2) default 0.0 not null, payed bool default 0 not null);

create table db_payments (id int primary key auto_increment, order_id int not null, amount decimal(10,2) not null default 0.0, pay_system int, status enum ('success', 'fail', 'progress'));



