drop database if exists coronelbox_db;
create database coronelbox_db;
use coronelbox_db;

create table miembros(
id_miembro int auto_increment primary key,
nombre varchar(100) not null,
apellido varchar(100) not null,
telefono varchar(20) not null,
fecha_nacimiento date not null,
fecha_registro datetime default current_timestamp,
puntos_box int not null,
estado boolean not null
);

create table membresia(
id_membresia int auto_increment primary key,
tipo varchar(255) not null,
precio	decimal(10,2) not null,
duracion_dias int not null
);

create table pagos(
id_pago int auto_increment primary key,
total decimal(4,2) not null,
descripcion varchar(255) null,
fecha_pago datetime default current_timestamp not null,
id_miembro int,
id_membresia int null,
foreign key (id_miembro) references miembros(id_miembro),
foreign key(id_membresia) references membresia(id_membresia)
);

create table entrenadores(
id_entrenador int auto_increment primary key,
nombre varchar(100) not null,
apellido varchar(100) not null,
telefono varchar(20) not null,
especialidad varchar(100) not null,
hora_inicio time not null,
hora_fin time not null,
estado boolean not null
);

create table users(
id_user int auto_increment primary key,
nombre varchar(100) not null,
contrasena varchar (255) not null,
rol enum('empleado','administrador') not null
);



create table inscripciones(
id_inscripcion int auto_increment primary key,
id_miembro	int,
id_membresia int,
fecha_inicio datetime default current_timestamp not null,
foreign key (id_miembro) references miembros(id_miembro),
foreign key (id_membresia) references membresia(id_membresia),
unique (id_miembro, id_membresia)
);


create table asistencia (
id_asistencia int auto_increment primary key,
id_miembro int,
id_entrenador int,
turno enum('Matutino','Vespertino','Nocturno') not null,
dia_semana	enum('Lunes','Martes','Miercoles','Jueves','Viernes','Sabado') not null,
fecha datetime default current_timestamp not null,
foreign key (id_miembro) references miembros(id_miembro),
foreign key (id_entrenador) references entrenadores(id_entrenador),
unique (id_miembro,id_entrenador,fecha)
);

insert into miembros(nombre, apellido, telefono, fecha_nacimiento, puntos_box, estado) values
('Diego','Palma','6875-3456','2008-01-07', 10, 1);

insert into entrenadores(nombre, apellido, telefono, especialidad, hora_inicio, hora_fin, estado) values
('wow','Ramirez','4567-1342','Counters','08:00','21:00','1');

insert into users(nombre,contrasena,rol) values
('Carolina','$2y$10$m.hBjSRpRQDUgW5osWpSNunafQC62HdsvxHyWxph3h0qx00yxgZqK','administrador');

insert into membresia(tipo,precio,duracion_dias) values
('Mensual',25.00,30);

insert into inscripciones(id_miembro,id_membresia) values
(1,1);

insert into asistencia(id_miembro,id_entrenador,turno,dia_semana) values
(1,1,'Matutino','Sabado');

INSERT INTO pagos (total, id_miembro, descripcion, id_membresia)
VALUES ('56', '1', 'vendas', NULL);


show full tables;
select * from miembros;
select * from entrenadores;
select * from users;
select * from membresia;
select * from inscripciones;
select * from asistencia;
select * from pagos;