<?php
namespace App;

//Inicializo sesión para poder traspasar variables entre páginas
session_start();

//Incluyo los controladores que voy a utilizar para que seran cargados por Autoload
use App\Controller\AppController;
use App\Controller\EquiposController;
use App\Controller\AdministradoresController;


echo password_hash("1234Abcd!", PASSWORD_BCRYPT, ['cost' => 12]);


/*
 * Asigno a sesión las rutas de las carpetas public y home, necesarias tanto para las rutas como para
 * poder enlazar imágenes y archivos css, js
 */
$_SESSION['public'] = '/CMS2/public/';
$_SESSION['home'] = $_SESSION['public'].'index.php/';

//Defino y llamo a la función que autocargará las clases cuando se instancien
spl_autoload_register('App\autoload');

function autoload($clase,$dir=null){

    //Directorio raíz de mi proyecto
    if (is_null($dir)){
        $dirname = str_replace('/public', '', dirname(__FILE__));
        $dir = realpath($dirname);
    }

    //Escaneo en busca de la clase de forma recursiva
    foreach (scandir($dir) as $file){
        //Si es un directorio (y no es de sistema) accedo y
        //busco la clase dentro de él
        if (is_dir($dir."/".$file) AND substr($file, 0, 1) !== '.'){
            autoload($clase, $dir."/".$file);
        }
        //Si es un fichero y el nombr conicide con el de la clase
        else if (is_file($dir."/".$file) AND $file == substr(strrchr($clase, "\\"), 1).".php"){
            require($dir."/".$file);
        }
    }

}

//Para invocar al controlador en cada ruta
function controlador($nombre=null){

    switch($nombre){
        default: return new AppController;  //front-end
        case "equipos": return new EquiposController; //back-end equipos
        case "administradores": return new AdministradoresController; //log in y back-end usuario
    }

}

//Quito la ruta de la home a la que me están pidiendo
$ruta = str_replace($_SESSION['home'], '', $_SERVER['REQUEST_URI']);

//Encamino cada ruta al controlador y acción correspondientes
switch ($ruta){

    //Front-end
    case "":
    case "/":
        controlador()->index();
        break;
    case "acerca-de":
        controlador()->acercade();
        break;
    case "equipos":
        controlador()->equipos();
        break;
    case (strpos($ruta,"equipo/") === 0): //si empieza por equipo/
        controlador()->equipo(str_replace("equipo/","",$ruta));
        break;

    //Back-end
    case "panel":
    case "panel/entrar":
        controlador("administradores")->entrar();
        break;
    case "panel/salir":
        controlador("administradores")->salir();
        break;
    case "panel/administradores":
        controlador("administradores")->index();
        break;
    case "panel/administradores/crear":
        controlador("administradores")->crear();
        break;
    case (strpos($ruta,"panel/administradores/editar/") === 0):
        controlador("administradores")->editar(str_replace("panel/administradores/editar/","",$ruta));
        break;
    case (strpos($ruta,"panel/administradores/activar/") === 0):
        controlador("administradores")->activar(str_replace("panel/administradores/activar/","",$ruta));
        break;
    case (strpos($ruta,"panel/administradores/borrar/") === 0):
        controlador("administradores")->borrar(str_replace("panel/administradores/borrar/","",$ruta));
        break;
    case "panel/equipos":
        controlador("equipos")->index();
        break;
    case "panel/equipos/crear":
        controlador("equipos")->crear();
        break;
    case (strpos($ruta,"panel/equipos/editar/") === 0):
        controlador("equipos")->editar(str_replace("panel/equipos/editar/","",$ruta));
        break;
    case (strpos($ruta,"panel/equipos/activar/") === 0):
        controlador("equipos")->activar(str_replace("panel/equipos/activar/","",$ruta));
        break;
    case (strpos($ruta,"panel/equipos/home/") === 0):
        controlador("equipos")->home(str_replace("panel/equipos/home/","",$ruta));
        break;
    case (strpos($ruta,"panel/equipos/borrar/") === 0):
        controlador("equipos")->borrar(str_replace("panel/equipos/borrar/","",$ruta));
        break;
    case (strpos($ruta,"panel/") === 0):
        controlador("administradores")->entrar();
        break;

    //Resto de rutas
    default:
        controlador()->index();

}