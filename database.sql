-- ==========================================================
-- Base de datos: blog_noticias  (MySQL / MariaDB de XAMPP)
-- Importar desde phpMyAdmin: pestaña "Importar" > elegir este archivo > Continuar
-- ==========================================================
DROP DATABASE IF EXISTS blog_noticias;
CREATE DATABASE blog_noticias CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE blog_noticias;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    usuario VARCHAR(40) NOT NULL UNIQUE,
    clave VARCHAR(255) NOT NULL,
    rol ENUM('admin','editor') NOT NULL DEFAULT 'editor',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    slug VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE noticias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    slug VARCHAR(170) NOT NULL,
    contenido TEXT NOT NULL,
    imagen VARCHAR(60) NULL,
    imagen_alt VARCHAR(200) NOT NULL DEFAULT '',
    categoria_id INT NOT NULL,
    autor_id INT NOT NULL,
    estado ENUM('publicada','borrador') NOT NULL DEFAULT 'publicada',
    destacada TINYINT(1) NOT NULL DEFAULT 0,
    vistas INT NOT NULL DEFAULT 0,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_estado_fecha (estado, creado_en),
    INDEX idx_categoria (categoria_id),
    FOREIGN KEY (categoria_id) REFERENCES categorias(id),
    FOREIGN KEY (autor_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE comentarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    noticia_id INT NOT NULL,
    nombre VARCHAR(60) NOT NULL,
    comentario TEXT NOT NULL,
    estado ENUM('pendiente','aprobado') NOT NULL DEFAULT 'pendiente',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_noticia (noticia_id, estado),
    FOREIGN KEY (noticia_id) REFERENCES noticias(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Usuario administrador -> usuario: admin  contraseña: admin123
INSERT INTO usuarios (nombre, usuario, clave, rol) VALUES
('Administrador', 'admin', '$2y$10$SCg2iWHem.gzLSUA2uIOn.pLOLq.kMDIYDa8Q.4Y1WhIlkTtMvUMS', 'admin');

INSERT INTO categorias (nombre, slug) VALUES
('Local', 'local'), ('Cultura', 'cultura'), ('Deportes', 'deportes'),
('Economía', 'economia'), ('Tecnología', 'tecnologia');

INSERT INTO noticias (titulo, slug, contenido, categoria_id, autor_id, estado, destacada, vistas, creado_en) VALUES
('Huancayo celebra su aniversario con un gran corso por la Calle Real',
 'huancayo-celebra-su-aniversario-con-un-gran-corso-por-la-calle-real',
 'Miles de vecinos salieron a la Calle Real para participar en el tradicional corso por el aniversario de la ciudad. Delegaciones de colegios, instituciones y agrupaciones de danza recorrieron el centro desde temprano.\n\nLa municipalidad informó que se reforzó la seguridad y la limpieza en toda la ruta. Además, se habilitaron puntos de atención médica y zonas para personas con discapacidad.\n\nLas actividades continuarán durante toda la semana con ferias gastronómicas, conciertos en la Plaza Constitución y concursos de danzas típicas.',
 1, 1, 'publicada', 1, 120, '2026-10-12 09:00:00'),
('La Huaconada atrae a turistas de todo el país',
 'la-huaconada-atrae-a-turistas-de-todo-el-pais',
 'La Huaconada de Mito, declarada Patrimonio Cultural Inmaterial de la Humanidad, volvió a reunir a cientos de visitantes nacionales y extranjeros.\n\nLos huacones, con sus máscaras de nariz prominente y sus látigos, representan a los antiguos sabios que imponían orden en la comunidad. Los organizadores destacaron la importancia de preservar esta tradición para las nuevas generaciones.',
 2, 1, 'publicada', 0, 85, '2026-10-11 10:30:00'),
('Club Sport Huancayo se prepara para la fecha decisiva',
 'club-sport-huancayo-se-prepara-para-la-fecha-decisiva',
 'El plantel del Rojo Matador entrenó a puertas cerradas en el estadio Huancayo de cara al partido del fin de semana. El comando técnico confirmó que contará con todos sus titulares.\n\nLa directiva anunció la venta de entradas por internet para evitar largas colas en las boleterías del estadio.',
 3, 1, 'publicada', 0, 64, '2026-10-10 16:00:00'),
('Productores de papa nativa exportarán a Europa',
 'productores-de-papa-nativa-exportaran-a-europa',
 'Una asociación de productores de papa nativa del valle del Mantaro firmó su primer contrato de exportación hacia mercados europeos. Se enviarán variedades de colores con alto valor nutricional.\n\nLos agricultores recibieron capacitación en buenas prácticas agrícolas y certificación orgánica gracias a un programa regional de apoyo a la agroexportación.',
 4, 1, 'publicada', 0, 47, '2026-10-09 08:15:00'),
('Universidades de Junín impulsan semilleros de innovación tecnológica',
 'universidades-de-junin-impulsan-semilleros-de-innovacion-tecnologica',
 'Estudiantes de ingeniería de sistemas presentaron aplicaciones web y móviles orientadas a resolver problemas de la ciudad, como el transporte público y la búsqueda de técnicos a domicilio.\n\nLos proyectos ganadores recibirán asesoría para convertirse en emprendimientos y podrán participar en ferias nacionales de innovación.',
 5, 1, 'publicada', 0, 39, '2026-10-08 11:45:00'),
('Inicia la campaña de mantenimiento de pistas en El Tambo',
 'inicia-la-campana-de-mantenimiento-de-pistas-en-el-tambo',
 'La municipalidad distrital de El Tambo inició trabajos de parchado y señalización en las principales avenidas. Se recomienda a los conductores tomar vías alternas durante las próximas dos semanas.\n\nLos trabajos se realizarán en horario nocturno para reducir el impacto en el tránsito.',
 1, 1, 'publicada', 0, 22, '2026-10-07 07:30:00'),
('Feria del libro de Huancayo reunirá a autores regionales',
 'feria-del-libro-de-huancayo-reunira-a-autores-regionales',
 'La feria del libro contará con presentaciones, talleres de lectura para niños y conversatorios con escritores de la región central del país.\n\nLa entrada será libre y habrá espacios con libros en braille y audiolibros para personas con discapacidad visual.',
 2, 1, 'publicada', 0, 18, '2026-10-06 15:00:00'),
('Borrador: nuevo horario del mercado modelo',
 'borrador-nuevo-horario-del-mercado-modelo',
 'Esta noticia está en borrador y no debe aparecer en la portada hasta que sea publicada por el administrador.',
 1, 1, 'borrador', 0, 0, '2026-10-05 12:00:00');

INSERT INTO comentarios (noticia_id, nombre, comentario, estado) VALUES
(1, 'Rosa Quispe', '¡Qué bonito estuvo el corso! Felicidades a todos los colegios.', 'aprobado'),
(1, 'Luis Huamán', 'Ojalá el próximo año pongan más baños públicos en la ruta.', 'aprobado'),
(2, 'Carlos Rojas', 'Excelente nota, hay que cuidar nuestras tradiciones.', 'pendiente');
