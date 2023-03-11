#create schema karma8_test collate utf8mb4_general_ci;

create table users (
    id int auto_increment
        primary key,
    username varchar(255) not null,
    email varchar(255) not null,
    validts timestamp null comment 'may be null if subscription indefinitely',
    confirmed boolean default FALSE null
);
alter table users
add constraint users_username_pk
    unique (username),
add constraint users_email_pk
    unique (email);


create table emails (
    email varchar(255) not null
        primary key,
    checked boolean default false not null,
    valid boolean default false not null
);
alter table emails
add constraint foreign_key_name
    foreign key (email) references karma8_test.users (email);
