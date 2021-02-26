drop table if exists follower;
create table follower(
  id int(11) not null primary key auto_increment,
  inserted timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  following_root varchar(255),
  tweets bigint,
  name varchar(650),
  screen_name varchar(350),
  description varchar(3000),
  followers_count int(11),
  friends_count int(11),
  since datetime,
  unique key(screen_name(255))
);

