CREATE DATABASE cinemegapolis;
USE cinemegapolis;

CREATE TABLE clientes (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    edad INT NOT NULL CHECK (edad > 0),
    email VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE peliculas (
    id_pelicula INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    genero VARCHAR(50) NOT NULL,
    duracion INT NOT NULL,
    clasificacion ENUM('ATP', '+13', '+16', '+18') NOT NULL,
    formato VARCHAR(20),
    idioma VARCHAR(30),
    descripcion TEXT,
    fecha_estreno DATE,
    distribuidor VARCHAR(100),
    imagen VARCHAR(255),
    trailer VARCHAR(255),
    estado ENUM('Cartelera', 'Preventa') NOT NULL
);

CREATE TABLE salas (
    id_sala INT AUTO_INCREMENT PRIMARY KEY,
    numero_sala INT NOT NULL,
    capacidad INT NOT NULL CHECK (capacidad > 0)
);

CREATE TABLE funciones (
    id_funcion INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    id_sala INT NOT NULL,
    id_pelicula INT NOT NULL,

    UNIQUE(id_sala, fecha, hora),

    FOREIGN KEY (id_sala)
        REFERENCES salas(id_sala),

    FOREIGN KEY (id_pelicula)
        REFERENCES peliculas(id_pelicula)
);

CREATE TABLE entradas (
    id_entrada INT AUTO_INCREMENT PRIMARY KEY,
    precio INT NOT NULL CHECK (precio > 0),
    cantidad INT NOT NULL CHECK (cantidad BETWEEN 1 AND 10),
    asiento VARCHAR(10) NOT NULL,
    id_cliente INT NOT NULL,
    id_funcion INT NOT NULL,

    FOREIGN KEY (id_cliente)
        REFERENCES clientes(id_cliente),

    FOREIGN KEY (id_funcion)
        REFERENCES funciones(id_funcion)
);