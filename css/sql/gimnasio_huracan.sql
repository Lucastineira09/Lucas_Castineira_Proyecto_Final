CREATE DATABASE gimnasio_huracan
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE gimnasio_huracan;

CREATE TABLE usuarios (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(80)  NOT NULL,
  apellido      VARCHAR(80)  NOT NULL,
  dni           VARCHAR(20)  NOT NULL UNIQUE,
  email         VARCHAR(254) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol           ENUM('usuario','admin') NOT NULL DEFAULT 'usuario',
  creado_en     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  archivado_en  TIMESTAMP    NULL DEFAULT NULL
)CREATE TABLE pagos (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id    INT UNSIGNED NOT NULL,
  plan          VARCHAR(20)  NOT NULL,
  meses         TINYINT UNSIGNED NOT NULL,
  cuota_social  TINYINT(1)   NOT NULL DEFAULT 0,
  monto         DECIMAL(10,2) NOT NULL,
  metodo        VARCHAR(20)  NOT NULL,
  fecha_pago    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  vence_el      DATE         NOT NULL,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);