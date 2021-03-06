<?php
namespace App\Controller;

use App\Helper\ViewHelper;
use App\Helper\DbHelper;
use App\Model\Administradores;


class AdministradoresController
{
    var $db;
    var $view;

    function __construct()
    {
        //Conexión a la BBDD
        $dbHelper = new DbHelper();
        $this->db = $dbHelper->db;

        //Instancio el ViewHelper
        $viewHelper = new ViewHelper();
        $this->view = $viewHelper;
    }

    public function admin(){

        //Compruebo permisos
        $this->view->permisos();

        //LLamo a la vista
        $this->view->vista("admin","index");

    }

    public function entrar(){

        //Si ya está autenticado, le llevo a la página de inicio del panel
        if (isset($_SESSION['persona'])){

            $this->admin();

        }
        //Si ha pulsado el botón de acceder, tramito el formulario
        else if (isset($_POST["acceder"])){
            //Recupero los datos del formulario
            $campo_usuario = filter_input(INPUT_POST, "usuario", FILTER_SANITIZE_STRING);
            $campo_clave = filter_input(INPUT_POST, "clave", FILTER_SANITIZE_STRING);


            //Busco al usuario en la base de datos
            $rowset = $this->db->query("SELECT * FROM administradores WHERE persona='$campo_usuario' AND activo=1 LIMIT 1");

            //Asigno resultado a una instancia del modelo
            $row = $rowset->fetch(\PDO::FETCH_OBJ);
            $persona = new Administradores($row);

            //Si existe el usuario
            if ($persona->persona){
                //Compruebo la clave
                if (password_verify($campo_clave,$persona->clave)) {

                    //Asigno el usuario y los permisos la sesión
                    $_SESSION["persona"] = $persona->persona;
                    $_SESSION["administradores"] = $persona->administradores;
                    $_SESSION["equipos"] = $persona->equipos;

                    //Guardo la fecha de último acceso
                    $ahora = new \DateTime("now", new \DateTimeZone("Europe/Madrid"));
                    $fecha = $ahora->format("Y-m-d H:i:s");
                    $this->db->exec("UPDATE administradores SET fecha_acceso='$fecha' WHERE persona='$campo_usuario'");

                    //Redirección con mensaje
                    $this->view->redireccionConMensaje("panel/index","green","Bienvenido al panel de administración.");
                }
                else{
                    //Redirección con mensaje
                    $this->view->redireccionConMensaje("panel","red","Contraseña incorrecta.");
                }
            }
            else{
                //Redirección con mensaje
                $this->view->redireccionConMensaje("panel","red","No existe ningún usuario con ese nombre.");
            }
        }
        //Le llevo a la página de acceso
        else{
            $this->view->vista("admin","administradores/entrar");
        }

    }

    public function salir(){

        //Borro al usuario de la sesión
        unset($_SESSION['persona']);

        //Redirección con mensaje
        $this->view->redireccionConMensaje("panel","green","Te has desconectado con éxito.");

    }

    //Listado de administradores
    public function index(){

        //Permisos
        $this->view->permisos("administradores");

        //Recojo los administradores de la base de datos
        $rowset = $this->db->query("SELECT * FROM administradores ORDER BY persona ASC");

        //Asigno resultados a un array de instancias del modelo
        $usuarios = array();
        while ($row = $rowset->fetch(\PDO::FETCH_OBJ)){
            array_push($usuarios,new Administradores($row));
        }

        $this->view->vista("admin","administradores/index", $usuarios);

    }

    //Para activar o desactivar
    public function activar($id){

        //Permisos
        $this->view->permisos("administradores");

        //Obtengo el usuario
        $rowset = $this->db->query("SELECT * FROM administradores WHERE id='$id' LIMIT 1");
        $row = $rowset->fetch(\PDO::FETCH_OBJ);
        $usuario = new Administradores($row);

        if ($usuario->activo == 1){

            //Desactivo el usuario
            $consulta = $this->db->exec("UPDATE administradores SET activo=0 WHERE id='$id'");

            //Mensaje y redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("panel/administradores","green","El usuario <strong>$usuario->persona</strong> se ha desactivado correctamente.") :
                $this->view->redireccionConMensaje("panel/administradores","red","Hubo un error al guardar en la base de datos.");
        }

        else{

            //Activo el usuario
            $consulta = $this->db->exec("UPDATE administradores SET activo=1 WHERE id='$id'");

            //Mensaje y redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("panel/administradores","green","El usuario <strong>$usuario->usuario</strong> se ha activado correctamente.") :
                $this->view->redireccionConMensaje("panel/administradores","red","Hubo un error al guardar en la base de datos.");
        }

    }

    public function borrar($id){

        //Permisos
        $this->view->permisos("administradores");

        //Borro el usuario
        $consulta = $this->db->exec("DELETE FROM administradores WHERE id='$id'");

        //Mensaje y redirección
        ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
            $this->view->redireccionConMensaje("panel/administradores","green","El usuario se ha borrado correctamente.") :
            $this->view->redireccionConMensaje("panel/administradores","red","Hubo un error al guardar en la base de datos.");

    }

    public function crear(){

        //Permisos
        $this->view->permisos("administradores");

        //Creo un nuevo usuario vacío
        $usuario = new Administradores();

        //Llamo a la ventana de edición
        $this->view->vista("admin","administradores/editar", $usuario);

    }

    public function editar($id){

        //Permisos
        $this->view->permisos("administradores");

        //Si ha pulsado el botón de guardar
        if (isset($_POST["guardar"])){

            //Recupero los datos del formulario
            $usuario = filter_input(INPUT_POST, "usuario", FILTER_SANITIZE_STRING);
            $clave = filter_input(INPUT_POST, "clave", FILTER_SANITIZE_STRING);
            $usuarios = (filter_input(INPUT_POST, 'administradores', FILTER_SANITIZE_STRING) == 'on') ? 1 : 0;
            $equipos = (filter_input(INPUT_POST, 'equipos', FILTER_SANITIZE_STRING) == 'on') ? 1 : 0;
            $cambiar_clave = (filter_input(INPUT_POST, 'cambiar_clave', FILTER_SANITIZE_STRING) == 'on') ? 1 : 0;

            //Encripto la clave
            $clave_encriptada = ($clave) ? password_hash($clave,  PASSWORD_BCRYPT, ['cost'=>12]) : "";

            if ($id == "nuevo"){

                //Creo un nuevo usuario
                $this->db->exec("INSERT INTO administradores (persona, clave, equipos, administradores) VALUES ('$usuario','$clave_encriptada',$equipos,$usuarios)");

                //Mensaje y redirección
                $this->view->redireccionConMensaje("panel/administradores","green","El usuario <strong>$usuario</strong> se creado correctamente.");
            }
            else{

                //Actualizo el usuario
                ($cambiar_clave) ?
                    $this->db->exec("UPDATE administradores SET persona='$usuario',clave='$clave_encriptada',equipos=$equipos,administradores=$usuarios WHERE id='$id'") :
                    $this->db->exec("UPDATE administradores SET persona='$usuario',equipos=$equipos,administradores=$usuarios WHERE id='$id'");

                //Mensaje y redirección
                $this->view->redireccionConMensaje("panel/administradores","green","El usuario <strong>$usuario</strong> se actualizado correctamente.");
            }
        }

        //Si no, obtengo usuario y muestro la ventana de edición
        else{

            //Obtengo el usuario
            $rowset = $this->db->query("SELECT * FROM administradores WHERE id='$id' LIMIT 1");
            $row = $rowset->fetch(\PDO::FETCH_OBJ);
            $usuario = new Administradores($row);

            //Llamo a la ventana de edición
            $this->view->vista("admin","administradores/editar", $usuario);
        }

    }


}