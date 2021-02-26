drop table if exists cronrun;
create table cronrun(
  id bigint not null primary key auto_increment,
  inserted timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tweets bigint
);

drop table if exists tweep;
create table tweep(
  id bigint not null primary key,
  is_blocked tinyint(4) not null default 0,
  handle varchar(512),
  screenname varchar(512)
);

drop table if exists thread;
create table thread(
  id bigint not null primary key auto_increment,
  example_tweet bigint not null,
  inserted timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);


drop table if exists tweet;
create table tweet(
  id bigint not null primary key,
  tweep bigint not null,
  cronrun bigint not null,
  inserted timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at datetime,
  complete text(61000),
  maintext varchar(512),
  hashtag bigint,
  url varchar(512),
  thread bigint ,
  parent bigint,
  son bigint,
  index(hashtag)
);

drop table if exists hashtag;
create table hashtag(
  id bigint not null primary key auto_increment,
  hashtag varchar(255),
  frequency bigint default 1,
  lastrequested datetime,
  lastid bigint
);
insert into hashtag set hashtag='NieWiederCDU';
insert into hashtag set hashtag='BTW21Fakt';
insert into hashtag set hashtag='BTW21Fakten';
insert into hashtag set hashtag='NieWiederCDUCSU';
insert into hashtag set hashtag='WhatTheFact';
