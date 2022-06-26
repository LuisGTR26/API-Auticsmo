<?php

class rutinas {

    const NOMBRE_TABLA = "rutina";
    //-------------------------------------------//
    const ID_RUTINA = "idRutina";
    const NOMBRE_RUTINA = "titleRutina";
    const IMG_RUTINA = "imgRutina";
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
            return self::obtenerRutinas($idUsuario);
        else
            return self::obtenerRutinas($idUsuario, $peticion[0]);

    }

    private static function obtenerRutinas($idUsuario, $idRutina = NULL)
    {
        try {
            if (!$idRutina) {

                $comando = "SELECT * FROM " . self::NOMBRE_TABLA .
                    " WHERE " . self::ID_USUARIO . "=?";

                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
                // Ligar idUsuario
                $sentencia->bindParam(1, $idUsuario, PDO::PARAM_INT);

            } else {

                $comando = "SELECT * FROM " . self::NOMBRE_TABLA .
                    " WHERE " . self::ID_RUTINA . "=? AND " .
                    self::ID_USUARIO . "=?";

                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
                // Ligar idRutina e idUsuario
                $sentencia->bindParam(1, $idRutina, PDO::PARAM_INT);
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


    public static function post($peticion)
    {
        $idUsuario = usuarios::autorizar();

        $body = file_get_contents('php://input');
        $rutina = json_decode($body);

        $idRutina = rutinas::crear($idUsuario, $rutina);
      
        http_response_code(201);
        return [
            "estado" => self::CODIGO_EXITO,
            "mensaje" => "Rutina creada",
            "id" => $idRutina
        ];

    }

     /**
     * Añade una nueva rutina asociado a un usuario
     * @param int $idUsuario identificador del usuario
     * @param mixed $rutina datos del rutina
     * @return string identificador de la rutina
     * @throws ExcepcionApi
     */
    private static function crear($idUsuario, $rutina)
    {

        if ($rutina) {
            try {

                $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

                // Sentencia INSERT
                $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                    self::NOMBRE_RUTINA . "," .
                    self::IMG_RUTINA . "," .
                    self::ID_USUARIO . ")" .
                    " VALUES(?,?,?)";

                // Preparar la sentencia
                $sentencia = $pdo->prepare($comando);

                $sentencia->bindParam(1, $titleRutina);
                $sentencia->bindParam(2, $imgRutina);
                $sentencia->bindParam(3, $idUsuario);


                $titleRutina = $rutina->titleRutina;
                $imgRutina = $rutina->imgRutina;

                $sentencia->execute();

                // Retornar en el último id insertado
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


    public static function put($peticion)
    {
        $idUsuario = usuarios::autorizar();

        if (!empty($peticion[0])) {
            $body = file_get_contents('php://input');
            $rutina = json_decode($body);

            if (self::actualizar($idUsuario, $rutina, $peticion[0]) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::CODIGO_EXITO,
                    "mensaje" => "Registro actualizado correctamente"
                ];
            } else {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                    "La rutina al que intentas acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Falta id", 422);
        }
    }


    /**
     * Actualiza la rutina especificado por idUsuario
     * @param int $idUsuario
     * @param object $rutina objeto con los valores nuevos del rutina
     * @param int $idRutina
     * @return PDOStatement
     * @throws Exception
     */
    private static function actualizar($idUsuario, $rutina, $idRutina)
    {
        try {
            // Creando consulta UPDATE
            $consulta = "UPDATE " . self::NOMBRE_TABLA .
                " SET " . self::NOMBRE_RUTINA . "=?," .
                self::IMG_RUTINA . "=?" .
                " WHERE " . self::ID_RUTINA . "=? AND " . self::ID_USUARIO . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $sentencia->bindParam(1, $titleRutina);
            $sentencia->bindParam(2, $imgRutina);
            $sentencia->bindParam(3, $idRutina);
            $sentencia->bindParam(4, $idUsuario);

            $titleRutina = $rutina->titleRutina;
            $imgRutina = $rutina->imgRutina;

            // Ejecutar la sentencia
            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
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
                    "La rutina a la que intentas acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Falta id", 422);
        }

    }

    /**
     * Elimina una rutina asociado a un usuario
     * @param int $idUsuario identificador del usuario
     * @param int $idRutinas identificador de rutina
     * @return bool true si la eliminación se pudo realizar, en caso contrario false
     * @throws Exception excepcion por errores en la base de datos
     */
    private static function eliminar($idUsuario, $idRutina)
    {
        try {
            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::ID_RUTINA . "=? AND " .
                self::ID_USUARIO . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            $sentencia->bindParam(1, $idRutina);
            $sentencia->bindParam(2, $idUsuario);

            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

}




