<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 *
 * Archivo: gre_mpcl/librerias/Database.class.php
 * Descripción: Clase centralizada para manejo de conexión a SQL Server
 *              IMPLEMENTACIÓN 100% PDO_SQLSRV
 * Autor: Sistema GRE
 * Fecha: 2025-10-26
 *
 * Dependencias:
 * - ../conexion.php  (debe entregar $base_de_datos = new PDO(...))
 * - PDO_SQLSRV Extension
 *
 * =======================================================
 */

class Database
{
    private static $instance = null;
    /** @var PDO */
    private $conexion;

    /* ---------- CONSTRUCTOR (SINGLETON) ---------- */
    private function __construct()
    {
        require_once "conexion.php";   // $base_de_datos debe ser un PDO
        if (!($base_de_datos instanceof PDO)) {
            throw new Exception("conexion.php debe devolver una instancia PDO.");
        }
        $this->conexion = $base_de_datos;
        // Aseguramos que lance excepciones
        $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /* ---------- SINGLETON ---------- */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /* ---------- ACCESO A LA CONEXIÓN (SI LO NECESITAS EXTERNAMENTE) ---------- */
    public function getConnection()
    {
        return $this->conexion;
    }

    /* ---------- SELECT MÚLTIPLE ---------- */
    public function select($sql, $params = [])
    {
        try {
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute($params);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Convertimos objetos DateTime a string
            foreach ($rows as &$row) {
                foreach ($row as &$value) {
                    if ($value instanceof DateTime) {
                        $value = $value->format('Y-m-d H:i:s');
                    }
                }
            }
            return $rows ?: [];
        } catch (PDOException $e) {
            $this->logError('PDO SELECT', $sql, $e->getMessage());
            return false;
        }
    }

    /* ---------- SELECT ÚNICO ---------- */
    public function selectOne($sql, $params = [])
    {
        $rows = $this->select($sql, $params);
        return $rows ? $rows[0] : false;
    }

    /* ---------- INSERT / UPDATE / DELETE ---------- */
    public function execute($sql, $params = [])
    {
        try {
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute($params);

            // Si es INSERT devolvemos el ID generado
            if (stripos(trim($sql), 'INSERT') === 0) {
                $id = $this->conexion->lastInsertId();
                // lastInsertId() puede devolver "0" en tablas sin IDENTITY
                return ($id && $id !== '0') ? (int)$id : true;
            }
            return true;
        } catch (PDOException $e) {
            $this->logError('PDO EXECUTE', $sql, $e->getMessage());
            return false;
        }
    }

    /* ---------- TRANSACCIONES ---------- */
    public function beginTransaction()
    {
        return $this->conexion->beginTransaction();
    }

    public function commit()
    {
        return $this->conexion->commit();
    }

    public function rollback()
    {
        return $this->conexion->rollback();
    }

    /* ---------- ESCAPE SIMPLE (opcional) ---------- */
    public function escape($value)
    {
        return str_replace("'", "''", $value);
    }

    /* ---------- MÉTODOS DE CONFIGURACIÓN ---------- */
    public function getConfig($parametro = null)
    {
        if ($parametro) {
            $sql = "SELECT VALOR FROM MPCL.dbo.TBL_CONFIG_GRE WHERE PARAMETRO = ?";
            $row = $this->selectOne($sql, [$parametro]);
            return $row ? $row['VALOR'] : false;
        }

        $sql = "SELECT PARAMETRO, VALOR FROM MPCL.dbo.TBL_CONFIG_GRE";
        $rows = $this->select($sql);
        $config = [];
        foreach ($rows as $r) {
            $config[$r['PARAMETRO']] = $r['VALOR'];
        }
        return $config;
    }

    public function setConfig($parametro, $valor)
    {
        $sql = "UPDATE MPCL.dbo.TBL_CONFIG_GRE SET VALOR = ? WHERE PARAMETRO = ?";
        return $this->execute($sql, [$valor, $parametro]);
    }

    /* ---------- LOG DE GUIAS ---------- */
    public function registrarLog($idGuia, $accion, $estadoAnterior = null,
                                  $estadoNuevo = null, $descripcion = null,
                                  $request = null, $response = null)
    {
        $sql = "INSERT INTO MPCL.dbo.TBL_GUIAS_LOG
                (ID_GUIA_CAB, ACCION, ESTADO_ANTERIOR, ESTADO_NUEVO,
                 DESCRIPCION, REQUEST, RESPONSE, USUARIO)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $usuario = $_SESSION['usuario'] ?? 'SYSTEM';
        return $this->execute($sql, [
            $idGuia, $accion, $estadoAnterior, $estadoNuevo,
            $descripcion, $request, $response, $usuario
        ]);
    }

    /* ---------- LOG DE ERRORES ---------- */
    private function logError($tipo, $sql, $error)
    {
        $logFile = __DIR__ . '/../logs/database_errors.log';
        $logDir  = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $errorMsg  = date('Y-m-d H:i:s') . " | $tipo" . PHP_EOL;
        $errorMsg .= "SQL: $sql" . PHP_EOL;
        $errorMsg .= "Error: " . print_r($error, true) . PHP_EOL;
        $errorMsg .= str_repeat('-', 80) . PHP_EOL;

        file_put_contents($logFile, $errorMsg, FILE_APPEND);
    }

    /* ---------- BLOQUEAR CLONACIÓN / DESERIALIZACIÓN ---------- */
    private function __clone() {}
    public function __wakeup()
    {
        throw new Exception('No se puede deserializar singleton');
    }
}