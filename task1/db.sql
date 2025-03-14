create table t1_users (
    id int not null primary key auto_increment,
    username varchar(255) unique,
    email varchar(255) unique,
    validts int not null default 0,
    confirmed bool not null default 0,
    checked bool not null default 0,
    valid bool not null default 0
);

alter table t1_users ADD INDEX idx_validts (validts);

create table t1_last_sends_3d (user_id int primary key, ts int);
create table t1_last_sends_1d (user_id int primary key, ts int);

/* Bonus: Monitoring */

create table t1_monitor(ts int primary key, queue_1d int not null default 0, queue_3d int not null default 0, workers_1d int not null default 0, workers_3d int not null default 0);

