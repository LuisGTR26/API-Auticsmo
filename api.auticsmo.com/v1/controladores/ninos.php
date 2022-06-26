<?php

class ninos
{

    const NOMBRE_TABLA = "nino";
    //---------------------------------------//
    const ID_NINO = "idNino";
    const PRIMER_NOMBRE = "primerNombre";
    const PRIMER_APELLIDO = "primerApellido";
    const EDAD = "edad";
    const COLOR_FAVORITO = "colorFavorito";
    const GENERO = "genero";
    const ID_USUARIO = "idUsuario";

    const CODIGO_EXITO = 1;
    const ESTADO_EXITO = 1;
    const ESTADO_ERROR = 2;
    const ESTADO_ERROR_BD = 3;
    const ESTADO_ERROR_PARAMETROS = 4;
    const ESTADO_NO_ENCONTRADO = 5;

    public static function get($peticion)
    {
        $idUsuario = usuarios::autorizar();

        if (empty($peticion[0]))
            return self::obtenerNinos($idUsuario);
        else
            return self::obtenerNinos($idUsuario, $peticion[0]);

    }

    public static function post($peticion)
    {
        $idUsuario = usuarios::autorizar();

        $body = file_get_contents('php://input');
        $nino = json_decode($body);

        $idNino = ninos::crear($idUsuario, $nino);

        http_response_code(201);
        return [
            "estado" => self::CODIGO_EXITO,
            "mensaje" => "NINO creado",
            "id" => $idNino
        ];

    }

    public static function put($peticion)
    {
        $idUsuario = usuarios::autorizar();

        if (!empty($peticion[0])) {
            $body = file_get_contents('php://input');
            $nino = json_decode($body);

            if (self::actualizar($idUsuario, $nino, $peticion[0]) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::CODIGO_EXITO,
                    "mensaje" => "Registro actualizado correctamente"
                ];
            } else {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                    "El ninio al que intentas acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Falta id", 422);
        }
    }

    public static function delete($peticion)
    {
        $idUsuario = usuarios::autorizar();

        if (!empty($peticion[0])) {
            if (self::eliminar($idUsuario, $peticion[0]) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::CODIGO_EXITO,
                    "mensaje" => "Registro eliminado correctamente"
                ];
            } else {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                    "El ninio al que intentas acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Falta id", 422);
        }

    }

    /**
     * Obtiene la colecci�n de ninios o un solo ninio indicado por el identificador
     * @param int $idUsuario identificador del usuario
     * @param null $idNino identificador del nino (Opcional)
     * @return array registros de la tabla nino
     * @throws Exception
     */
    private static function obtenerNinos($idUsuario, $idNino = NULL)
    {
        try {
            if (!$idNino) {
                $comando = "SELECT * FROM " . self::NOMBRE_TABLA .
                    " WHERE " . self::ID_USUARIO . "=?";

                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
                // Ligar idUsuario
                $sentencia->bindParam(1, $idUsuario, PDO::PARAM_INT);

            } else {
                $comando = "SELECT * FROM " . self::NOMBRE_TABLA .
                    " WHERE " . self::ID_NINO . "=? AND " .
                    self::ID_USUARIO . "=?";

                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
                // Ligar idNino e idUsuario
                $sentencia->bindParam(1, $idNino, PDO::PARAM_INT);
                $sentencia->bindParam(2, $idUsuario, PDO::PARAM_INT);
            }

            // Ejecutar sentencia preparada
            if ($sentencia->execute()) {
                http_response_code(200);
                return
                    [
                        "estado" => self::ESTADO_EXITO,
                        "datos" => $sentencia->fetchAll(PDO::FETCH_ASSOC)
                    ];
            } else
                throw new ExcepcionApi(self::ESTADO_ERROR, "Se ha producido un error");

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    /**
     * A�ade un nuevo ninio asociado a un usuario
     * @param int $idUsuario identificador del usuario
     * @param mixed $nino datos del nino
     * @return string identificador del nino
     * @throws ExcepcionApi
     */
    private static function crear($idUsuario, $nino)
    {
        if ($nino) {
            try {

                $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

                // Sentencia INSERT
                $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                    self::PRIMER_NOMBRE . "," .
                    self::PRIMER_APELLIDO . "," .
                    self::EDAD . "," .
                    self::COLOR_FAVORITO . "," .
                    self::GENERO . "," .
                    self::ID_USUARIO . ")" .
                    " VALUES(?,?,?,?,?,?)";

                // Preparar la sentencia
                $sentencia = $pdo->prepare($comando);

                $sentencia->bindParam(1, $primerNombre);
                $sentencia->bindParam(2, $primerApellido);
                $sentencia->bindParam(3, $edad);
                $sentencia->bindParam(4, $colorFavorito);
                $sentencia->bindParam(5, $genero);
                $sentencia->bindParam(6, $idUsuario);


                $primerNombre = $nino->primerNombre;
                $primerApellido = $nino->primerApellido;
                $edad = $nino->edad;
                $colorFavorito = $nino->colorFavorito;
                $genero = $nino->genero;

                $sentencia->execute();

                // Retornar en el �ltimo id insertado
                return $pdo->lastInsertId();

            } catch (PDOException $e) {
                throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
            }
        } else {
            throw new ExcepcionApi(
                self::ESTADO_ERROR_PARAMETROS,
                utf8_encode("Error en existencia o sintaxis de par�metros"));
        }

    }

    /**
     * Actualiza el ninio especificado por idUsuario
     * @param int $idUsuario
     * @param object $nino objeto con los valores nuevos del ninio
     * @param int $idNino
     * @return PDOStatement
     * @throws Exception
     */
    private static function actualizar($idUsuario, $nino, $idNino)
    {
        try {
            // Creando consulta UPDATE
            $consulta = "UPDATE " . self::NOMBRE_TABLA .
                " SET " . self::PRIMER_NOMBRE . "=?," .
                self::PRIMER_APELLIDO . "=?," .
                self::EDAD . "=?," .
                self::COLOR_FAVORITO . "=?, " .
                self::GENERO . "=? " .
                " WHERE " . self::ID_NINO . "=? AND " . self::ID_USUARIO . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $sentencia->bindParam(1, $primerNombre);
            $sentencia->bindParam(2, $primerApellido);
            $sentencia->bindParam(3, $edad);
            $sentencia->bindParam(4, $colorFavorito);
            $sentencia->bindParam(5, $genero);
            $sentencia->bindParam(6, $idNino);
            $sentencia->bindParam(7, $idUsuario);

            $primerNombre = $nino->primerNombre;
            $primerApellido = $nino->primerApellido;
            $edad = $nino->edad;
            $colorFavorito = $nino->colorFavorito;
            $genero = $nino->genero;

            // Ejecutar la sentencia
            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }


    /**
     * Elimina un ninio asociado a un usuario
     * @param int $idUsuario identificador del usuario
     * @param int $idNino identificador del ninio
     * @return bool true si la eliminaci�n se pudo realizar, en caso contrario false
     * @throws Exception excepcion por errores en la base de datos
     */
    private static function eliminar($idUsuario, $idNino)
    {
        try {
            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::ID_NINO . "=? AND " .
                self::ID_USUARIO . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            $sentencia->bindParam(1, $idNino);
            $sentencia->bindParam(2, $idUsuario);

            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }
}

