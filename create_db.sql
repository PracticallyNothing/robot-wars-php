drop table if exists GameCommands;
drop table if exists Games;
drop table if exists GAMES;
drop table if exists UnitBlueprints;
drop table if exists Units;
drop table if exists Images;
drop table if exists Users;
drop table if exists USERS;

create table Users (
  Id integer AUTO_INCREMENT,
  Username Varchar(256) unique not null,
  Email Varchar(256) not null,
  PasswordHash Varchar(512) not null,
  Rank int default 1,

  primary key (Id)
);

insert into Users(Id, Username, Email, PasswordHash, Rank) values
(NULL, 'Mario Krastev', '119909@students.ue-varna.bg', '$2y$10$RgZLLf5L0gqmMxdXnJyzUeVzw6AxjvGyxvqxA9OKFFe88vtW4OyS6', 1);

create table Images (
  Id integer AUTO_INCREMENT,
  Name varchar(256) unique not null,
  ImageData blob,

  primary key (Id)
);

create table UnitBlueprints(
  Id integer AUTO_INCREMENT,
  Name varchar(32) not null,
  Caption varchar(255),
  Description text,
  IconId int,

  Cost integer,
  SecondsToBuild float,
  Speed float,

  primary key (Id),
  foreign key (IconId) references Images(Id)
);

insert into UnitBlueprints(Name, Caption, Cost, SecondsToBuild, Speed) values
('miner',        'Miner',          100,  5.0, 3.0),
('support',      'Support Truck',  150, 10.0, 5.0),
('flamethrower', 'Firethrower',    500, 10.0, 5.0),
('machineguns',  'Machinegunners', 300,  8.5, 7.0),
('artillery',    'Artillery',      800, 20.0, 1.0);

create table Games(
  Id integer AUTO_INCREMENT,
  UserId integer not null,
  DatetimeCreated timestamp not null default current_timestamp,
  DatetimeEnded timestamp,

  primary key (Id),
  constraint FK_Game_User
    foreign key (UserId)
    references Users(Id)
);

create table Units(
  Id integer AUTO_INCREMENT,
  GameId integer not null,
  BlueprintId integer not null,

  DatetimeDied timestamp,

  primary key (Id),
  constraint FK_Unit_Game
    foreign key (GameId)
    references Games(Id),
  constraint FK_Unit_UnitBlueprint
    foreign key (BlueprintId)
    references UnitBlueprints(Id)
);

create table GameCommands(
  Id integer AUTO_INCREMENT,
  GameId integer not null,

  CommandType enum('build_unit', 'move') not null,
  UnitBlueprintId integer,

  Sector Char(2),
  UnitId int,
  UnitStartXPos float,
  UnitStartYPos float,

  DatetimeIssued timestamp not null default current_timestamp,
  DatetimeEnd timestamp not null,

  primary key (Id),
  constraint FK_GameCommand_Game
    foreign key (GameId)
    references Games(Id),
  constraint FK_GameCommand_UnitBlueprint
    foreign key (UnitBlueprintId)
    references UnitBlueprints(Id),
  constraint FK_GameCommand_Unit
    foreign key (UnitId)
    references Units(Id)

);
