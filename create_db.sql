create table Images (
  ID integer PRIMARY KEY AUTO_INCREMENT,
  NAME varchar(256) unique not null,
  IMAGE_DATA blob
);