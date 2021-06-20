drop table if exists tausende_user;
create table tausende_user(
  id int(11) not null primary key auto_increment,
  inserted timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  from_list bigint,
  tweets bigint,
  name varchar(650),
  screen_name varchar(350),
  description varchar(3000),
  followers_count int(11),
  friends_count int(11),
  since datetime,
  location_name varchar(255),
  latitude decimal(12,10),
  longitude decimal(12,10),
  twilink varchar(255),
  unique key(screen_name(255))
);

drop table if exists tausende_topics;
create table tausende_topics(
  id int(11) not null primary key auto_increment,
  inserted timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  name varchar(250)
);

drop table if exists tausende_user2topics;
create table tausende_user2topics(
  user int(11) not null,
  topic int(11) not null,
  primary key(user, topic)
);

