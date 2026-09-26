-- =============================================================
--  WebLab – Sistema de Gestión de Diagnósticos Clínicos
--  Esquema de base de datos generado desde el Diagrama de Clases
-- =============================================================

-- -------------------------------------------------------------
-- Tabla: Area
-- Áreas dentro del laboratorio donde se realizan análisis.
-- -------------------------------------------------------------
CREATE TABLE Area (
    id          INT           NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(150)  NOT NULL,
    descripcion TEXT,
    PRIMARY KEY (id)
);

-- -------------------------------------------------------------
-- Tabla: MVZAprobado
-- Médicos Veterinarios Zootecnistas que aprueban servicios.
-- -------------------------------------------------------------
CREATE TABLE MVZAprobado (
    id       INT          NOT NULL AUTO_INCREMENT,
    nombre   VARCHAR(255) NOT NULL,
    area_id  INT          NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_mvz_area FOREIGN KEY (area_id) REFERENCES Area(id)
);

-- -------------------------------------------------------------
-- Tabla: Norma
-- Normas oficiales relacionadas con los servicios.
-- -------------------------------------------------------------
CREATE TABLE Norma (
    id          INT          NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(255) NOT NULL,
    descripcion TEXT,
    PRIMARY KEY (id)
);

-- -------------------------------------------------------------
-- Tabla: TipoMuestra
-- Tipos de muestras biológicas que puede manejar el sistema.
-- -------------------------------------------------------------
CREATE TABLE TipoMuestra (
    id             INT          NOT NULL AUTO_INCREMENT,
    tipo_muestra   VARCHAR(150) NOT NULL,
    tipo_muestral  VARCHAR(150),
    PRIMARY KEY (id)
);

-- -------------------------------------------------------------
-- Tabla: Especie
-- Especies animales manejadas en el laboratorio.
-- -------------------------------------------------------------
CREATE TABLE Especie (
    id               INT          NOT NULL AUTO_INCREMENT,
    nombre           VARCHAR(150) NOT NULL,
    tipo_muestra_id  INT,
    PRIMARY KEY (id),
    CONSTRAINT fk_especie_tipo_muestra FOREIGN KEY (tipo_muestra_id) REFERENCES TipoMuestra(id)
);

-- -------------------------------------------------------------
-- Tabla: Raza
-- Razas por especie.
-- -------------------------------------------------------------
CREATE TABLE Raza (
    id         INT          NOT NULL AUTO_INCREMENT,
    nombre     VARCHAR(150) NOT NULL,
    especie_id INT          NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_raza_especie FOREIGN KEY (especie_id) REFERENCES Especie(id)
);

-- -------------------------------------------------------------
-- Tabla: Metodo
-- Métodos de análisis utilizados en los servicios.
-- -------------------------------------------------------------
CREATE TABLE Metodo (
    id          INT          NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(150) NOT NULL,
    descripcion TEXT,
    PRIMARY KEY (id)
);

-- -------------------------------------------------------------
-- Tabla: Categoria
-- Categorías que agrupan los servicios del laboratorio.
-- -------------------------------------------------------------
CREATE TABLE Categoria (
    id        INT          NOT NULL AUTO_INCREMENT,
    nombre    VARCHAR(150) NOT NULL,
    metodo_id INT,
    PRIMARY KEY (id),
    CONSTRAINT fk_categoria_metodo FOREIGN KEY (metodo_id) REFERENCES Metodo(id)
);

-- -------------------------------------------------------------
-- Tabla: Servicio
-- Servicios de diagnóstico ofrecidos por el laboratorio.
-- -------------------------------------------------------------
CREATE TABLE Servicio (
    id               INT           NOT NULL AUTO_INCREMENT,
    clave            VARCHAR(50)   NOT NULL UNIQUE,
    siglas           VARCHAR(50),
    descripcion      TEXT,
    independiente    TINYINT(1)    NOT NULL DEFAULT 0,
    compuesto        TINYINT(1)    NOT NULL DEFAULT 0,
    status           VARCHAR(50)   NOT NULL DEFAULT 'activo',
    referencias      TEXT,
    area_id          INT,
    metodo_id        INT,
    categoria_id     INT,
    mvz_aprobado_id  INT,
    PRIMARY KEY (id),
    CONSTRAINT fk_servicio_area        FOREIGN KEY (area_id)         REFERENCES Area(id),
    CONSTRAINT fk_servicio_metodo      FOREIGN KEY (metodo_id)       REFERENCES Metodo(id),
    CONSTRAINT fk_servicio_categoria   FOREIGN KEY (categoria_id)    REFERENCES Categoria(id),
    CONSTRAINT fk_servicio_mvz         FOREIGN KEY (mvz_aprobado_id) REFERENCES MVZAprobado(id)
);

-- -------------------------------------------------------------
-- Tabla pivote: ServicioNorma
-- Relación muchos-a-muchos entre Servicio y Norma.
-- -------------------------------------------------------------
CREATE TABLE ServicioNorma (
    servicio_id  INT NOT NULL,
    norma_id     INT NOT NULL,
    PRIMARY KEY (servicio_id, norma_id),
    CONSTRAINT fk_sn_servicio FOREIGN KEY (servicio_id) REFERENCES Servicio(id),
    CONSTRAINT fk_sn_norma    FOREIGN KEY (norma_id)    REFERENCES Norma(id)
);

-- -------------------------------------------------------------
-- Tabla pivote: ServicioTipoMuestra
-- Tipos de muestra relacionados a un servicio.
-- -------------------------------------------------------------
CREATE TABLE ServicioTipoMuestra (
    servicio_id     INT NOT NULL,
    tipo_muestra_id INT NOT NULL,
    PRIMARY KEY (servicio_id, tipo_muestra_id),
    CONSTRAINT fk_stm_servicio     FOREIGN KEY (servicio_id)     REFERENCES Servicio(id),
    CONSTRAINT fk_stm_tipo_muestra FOREIGN KEY (tipo_muestra_id) REFERENCES TipoMuestra(id)
);

-- -------------------------------------------------------------
-- Tabla: Propietario
-- Propietarios de las explotaciones / granjas.
-- -------------------------------------------------------------
CREATE TABLE Propietario (
    id         INT          NOT NULL AUTO_INCREMENT,
    nombre     VARCHAR(255) NOT NULL,
    direccion  VARCHAR(500),
    municipio  VARCHAR(100),
    estado     VARCHAR(100),
    telefono   VARCHAR(30),
    PRIMARY KEY (id)
);

-- -------------------------------------------------------------
-- Tabla: FuncionZootecnica
-- Funciones zootécnicas de una explotación.
-- -------------------------------------------------------------
CREATE TABLE FuncionZootecnica (
    id          INT          NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(150) NOT NULL,
    descripcion TEXT,
    PRIMARY KEY (id)
);

-- -------------------------------------------------------------
-- Tabla: Explotacion
-- Granjas o explotaciones pecuarias.
-- -------------------------------------------------------------
CREATE TABLE Explotacion (
    id                    INT          NOT NULL AUTO_INCREMENT,
    nombre                VARCHAR(255),
    direccion             VARCHAR(500),
    municipio             VARCHAR(100),
    estado                VARCHAR(100),
    lote                  VARCHAR(100),
    caseta                VARCHAR(100),
    parvada               VARCHAR(100),
    propietario_id        INT          NOT NULL,
    funcion_zootecnica_id INT,
    PRIMARY KEY (id),
    CONSTRAINT fk_explotacion_propietario        FOREIGN KEY (propietario_id)        REFERENCES Propietario(id),
    CONSTRAINT fk_explotacion_funcion_zootecnica FOREIGN KEY (funcion_zootecnica_id) REFERENCES FuncionZootecnica(id)
);

-- -------------------------------------------------------------
-- Tabla: HistoriaClinica
-- Historia clínica del paciente animal.
-- -------------------------------------------------------------
CREATE TABLE HistoriaClinica (
    id                          INT           NOT NULL AUTO_INCREMENT,
    fecha_anual                 DATE,
    fecha_muestra               DATE,
    notas_adicionales           TEXT,
    sexo                        VARCHAR(20),
    edad                        DECIMAL(5,2),
    tipo_edad                   VARCHAR(50),
    cant_animales_explotacion   INT,
    cant_animales_muertos        INT,
    cant_animales_zootecnica     INT,
    especie_id                  INT,
    raza_id                     INT,
    funcion_zootecnica_id       INT,
    propietario_id              INT,
    explotacion_id              INT,
    PRIMARY KEY (id),
    CONSTRAINT fk_hc_especie             FOREIGN KEY (especie_id)            REFERENCES Especie(id),
    CONSTRAINT fk_hc_raza                FOREIGN KEY (raza_id)               REFERENCES Raza(id),
    CONSTRAINT fk_hc_funcion_zootecnica  FOREIGN KEY (funcion_zootecnica_id) REFERENCES FuncionZootecnica(id),
    CONSTRAINT fk_hc_propietario         FOREIGN KEY (propietario_id)        REFERENCES Propietario(id),
    CONSTRAINT fk_hc_explotacion         FOREIGN KEY (explotacion_id)        REFERENCES Explotacion(id)
);

-- -------------------------------------------------------------
-- Tabla: Encuesta
-- Encuesta epidemiológica relacionada a la historia clínica.
-- -------------------------------------------------------------
CREATE TABLE Encuesta (
    id                                   INT        NOT NULL AUTO_INCREMENT,
    historia_clinica_id                  INT        NOT NULL,
    fecha_comenzo_brote                  DATE,
    hay_antecedentes                     TINYINT(1) DEFAULT 0,
    signos_manifestados                  TEXT,
    hay_brotes_alrededores               TINYINT(1) DEFAULT 0,
    se_presenta_en_grupos                TINYINT(1) DEFAULT 0,
    hallazgos_necropsias_anteriores      TEXT,
    si_animales_recientemente_adquisicion TINYINT(1) DEFAULT 0,
    animales_recientemente_adquisicion   TEXT,
    antibioticos_aplicados               TEXT,
    vacunas_aplicadas                    TEXT,
    vitaminas_minerales_aplicados        TEXT,
    temperatura                          DECIMAL(5,2),
    medicamento_desparasita              TEXT,
    apetito_animal_consumo_agua          TEXT,
    alimentos_consumidos                 TEXT,
    lugar_provienen_agua                 TEXT,
    lugar_alojamiento                    TEXT,
    especies_contacto                    TEXT,
    informacion                          TEXT,
    PRIMARY KEY (id),
    CONSTRAINT fk_encuesta_historia FOREIGN KEY (historia_clinica_id) REFERENCES HistoriaClinica(id)
);

-- -------------------------------------------------------------
-- Tabla: Analisis
-- Análisis solicitado dentro de una historia clínica.
-- -------------------------------------------------------------
CREATE TABLE Analisis (
    id                  INT        NOT NULL AUTO_INCREMENT,
    historia_clinica_id INT        NOT NULL,
    servicio_id         INT,
    interno             TINYINT(1) NOT NULL DEFAULT 0,
    pendiente           TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    CONSTRAINT fk_analisis_historia FOREIGN KEY (historia_clinica_id) REFERENCES HistoriaClinica(id),
    CONSTRAINT fk_analisis_servicio FOREIGN KEY (servicio_id)         REFERENCES Servicio(id)
);

-- -------------------------------------------------------------
-- Tabla: AnalisisMuestras
-- Muestras individuales asociadas a un análisis.
-- -------------------------------------------------------------
CREATE TABLE AnalisisMuestras (
    id           INT           NOT NULL AUTO_INCREMENT,
    analisis_id  INT           NOT NULL,
    notas        TEXT,
    cantidad     INT           NOT NULL DEFAULT 1,
    por_facturar TINYINT(1)    NOT NULL DEFAULT 0,
    tipo         VARCHAR(100),
    especie_id   INT,
    PRIMARY KEY (id),
    CONSTRAINT fk_am_analisis FOREIGN KEY (analisis_id) REFERENCES Analisis(id),
    CONSTRAINT fk_am_especie  FOREIGN KEY (especie_id)  REFERENCES Especie(id)
);

-- -------------------------------------------------------------
-- Tabla: Diagnostico
-- Diagnóstico emitido a partir de un análisis.
-- -------------------------------------------------------------
CREATE TABLE Diagnostico (
    id                           INT          NOT NULL AUTO_INCREMENT,
    analisis_id                  INT          NOT NULL,
    fecha_emision                DATE,
    fecha_realizacion            DATE,
    status                       VARCHAR(50)  NOT NULL DEFAULT 'pendiente',
    resultado_muestras_analizadas TEXT,
    resultado                    TEXT,
    observaciones                TEXT,
    PRIMARY KEY (id),
    CONSTRAINT fk_diagnostico_analisis FOREIGN KEY (analisis_id) REFERENCES Analisis(id)
);
