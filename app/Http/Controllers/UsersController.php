<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

use App\Models\User;
//use App\Mail\ResetPassword;

class UsersController extends Controller
{
    /**
     * Registro nuevo usuario
     */
    public function register(Request $req){

        $msg = ["status" => 0, "msg" => "Algo ha fallado en el registro del usuario"];
                
        // JSON
        $data = $req->getContent();
        $data = json_decode($data);
       
        // NUEVO USUARIO
        $user = new User();

        $user->name = $data->name;
        $user->email = $data->email;
        $user->password = $data->password;    
        $user->role = $data->role;

        // COMPROBAR ENUM
        if($user->role == 'Particular' || $user->role == 'Professional' || $user->role == 'Administrator'){
            $checkRole = true;
        } else {
            $checkRole = false;
        }

        // COMPROBAR SI SE HA INTRODUCIDO UN EMAIL VALIDO
        $checkEmail = $this->CheckEmail($user->email);

        // COMPROBAR SI LA CONTRASEÑA ES SEGURA
        $checkPassword = $this->CheckPassword($user->password);

        // COMPROBAR SI EL USUARIO YA ESTÁ REGISTRADO
        $exists = User::where('name', '=', $data->name)->first();
        
        try {
            if($checkRole){
                if($checkEmail){
                    if($checkPassword){
                        if($exists){
                            $msg['status'] = 0;
                            $msg['msg'] = "No se pudo crear el usuario especificado, el usuario ".$user->name." ya existe.";
                        } else {
                            $user->save();
                            $msg['status'] = 1;
                            $msg['msg'] = "Usuario ".$user->name." registrado correctamente";
                        }
                    } else {
                        $msg['status'] = 0;
                        $msg['msg'] = "La contraseña no es segura";
                    }
                } else {
                    $msg['status'] = 0;
                    $msg['msg'] = "Por favor, introduce una dirección de correo valida";
                }
            } else {
                $msg['status'] = 0;
                $msg['msg'] = "No se ha especificado un rol valido";
            }
        } catch(\Exception $e){
            $msg['status'] = 0;
            $msg['msg'] = $e->getMessage();
        }

        return response()->json($msg);
    }

    /**
     * Iniciar sesión con un usuario
     * Genera un TOKEN
     */
    public function login(Request $req){
        $response = ["status" => 1, "msg" => ""];

        if($req->has('email')){
            $email = $req->input('email');
        } else {
            $email = "";
        }
        if($req->has('pswd')){
            $pswd = $req->input('pswd');
        } else {
            $pswd = "";
        }

        try {
            $checkEmail = DB::table('users')
                            ->where('email', '=', $email)
                            ->first();
            $checkPswd = DB::table('users')
                            ->where('password', '=', $pswd)
                            ->first();
            $checkUser = DB::table('users')
                            ->where('email', '=', $email)
                            ->where('password', '=', $pswd)
                            ->first();
            if(!$checkEmail){
                $response['status'] = 2;
                $response['msg'] = "Introduce un email valido";
            } else if(!$checkPswd || !$checkUser){
                $response['status'] = 3;
                $response['msg'] = "Contraseña incorrecta";  
            } else if ($checkUser){
                $user = User::find($checkUser->id);
                $user->api_token = Str::random(60);
                $user->save();
                
                $response['msg'] = "Usuario logeado";
                $response['query'] = DB::table('users')
                            ->where('id', '=', $checkUser->id)
                            ->select('email', 'api_token')
                            ->get();
            }

        }catch(\Exception $e){
            $response['msg'] = $e->getMessage();
            $response['status'] = 0;
            $response['msg'] = "Se ha producido un error: ".$e->getMessage();
        }

        return response()->json($response);
    }

    /**
     * Restablecer contraseña del usuario
     * Requiere introducir un email, genera una contraseña aleatoria y la envía por correo electrónico
     */
    public function resetPassword(Request $req){
        $response = ["status" => 1, "msg" => ""];

        if($req->has('email')){
            $email = $req->input('email');
        } else {
            $email = "";
        }

        $user = User::where('email', $req->email)->first();
        
        try{      
            if($user){
                $user->password = Str::random(12);
                
                // Enviar por email la nueva contraseña "temporal"
                Mail::to($user->email)->send(new ResetPassword("Restablecer contraseña empleados-app",
                        "Nueva contraseña temporal", [
                            "Hola, " .$user->name,
                            "Tu nueva contraseña es: " .$user->password,
                            "Al iniciar sesión nuevamente con esta contraseña se solicitará su cambio por una nueva.",
                            "Un saludo."
                        ]));
                $user -> save();
                $response["msg"] = "Se ha enviado una nueva contraseña temporal por email."; 
                return response()->json($response);
            } else {
                $response["status"] = 2;
                $response["msg"] = "No se ha encontrado el correo electronico introducido."; 
                return response()->json($response);
            }
        }catch(\Exception $e){
            $response['msg'] = $e->getMessage();
            $response['status'] = 0;
            $response['msg'] = "Se ha producido un error: ".$e->getMessage();
        }
    }

    /**
     * Listar todos los usuarios de la empresa
     * Únicamente puden listar usuarios los directivos y RRHH, gestionado por Middlware "checkUserRole"
     * Directivos ven a todos los usuarios excepto a otros directivos
     * RRHH ven a todos los usuarios excepto los de RRHH y directivos
     */
    public function list(Request $req){
        $response = ["status" => 1, "msg" => ""];

        if($req->has('token')){
            $token = $req->input('token');
        } else {
            $token = "";
        }
        
        try {
            $user = User::where('api_token', $req->token)->first();

            if ($user->role == "directive"){
                $query = DB::table('users')
                            ->select('name', 'role', 'salary')
                            ->whereIn('role', array('users', 'hr'))
                            ->get();
                $response["status"] = 2;
            } else {
                $query = DB::table('users')
                            ->select('name', 'role', 'salary')
                            ->where('role', '=', 'users')
                            ->get();
                $response["status"] = 3;                            
            }
            $response['msg'] = $query;
        } catch(\Exception $e){
            $response['msg'] = $e->getMessage();
            $response['status'] = 0;
            $response['msg'] = "Se ha producido un error: ".$e->getMessage();
        }
        return response()->json($response); 
    }

    /**
     * Ver un perfil de un usuario de la empresa
     * Lista nombre, email, puesto, biografía y salario.
     * Únicamente pueden ver un perfil ajeno los usuarios directivos y de RRHH
     * Los directivos ven todos los usuarios excepto a otros usuarios directivos.
     * RRHH ve a todos los usuarios excepto a otros usuarios de RRHH y directivos.
     * El usuario directivo o de RRHH puede ver su propio perfil
     */
    public function view(Request $req){
        $response = ["status" => 1, "msg" => ""];

        if($req->has('token')){
            $token = $req->input('token');
        } else {
            $token = "";
        }
        if($req->has('profileId')){
            $profileId = $req->input('profileId');
        } else {
            $profileId = "";
        }
        
        try {
            $user = User::where('api_token', $req->token)->first();
            $profile = User::where('id', $profileId)->first();

            if ($user->role == "directive"){
                if($profile->role != "directive" || $profile->id == $user->id){
                    $query = User::where('id', $profileId)
                                    ->select('name', 'email', 'role', 'biography', 'salary')
                                    ->first();
                    $response['msg'] = $query;
                } else {
                    $response['status'] = 3;
                    $response['msg'] = "Acceso denegado.";
                }
            } else {
                if($profile->role != "hr" && $profile->role != "directive" || $profile->id == $user->id){
                    $query = User::where('id', $profileId)
                                    ->select('name', 'email', 'role', 'biography', 'salary')
                                    ->first();
                    $response['msg'] = $query;
                } else {
                    $response['status'] = 3;
                    $response['msg'] = "Acceso denegado.";
                }                        
            }
            
        } catch(\Exception $e){
            $response['msg'] = $e->getMessage();
            $response['status'] = 0;
            $response['msg'] = "Se ha producido un error: ".$e->getMessage();
        }
        return response()->json($response); 
    }

    /**
     * Ver perfil propio
     * Lista nombre, email, rol, salario y biografía.
     * Es necesario haber iniciado sesión (tener un api_token activo).
     */
    public function profile(Request $req){
        $response = ["status" => 1, "msg" => ""];

        if($req->has('token')){
            $token = $req->input('token');
        } else {
            $token = "";
        }
        
        try {
            $user = User::where('api_token', $req->token)->first();

            if ($user->api_token){
                $response['msg'] = DB::table('users')
                            ->select('name', 'email', 'role', 'salary', 'biography')
                            ->where('api_token', '=', $token)
                            ->get();
            } else {
                $response['status'] = 3;
                $response['msg'] = "Inicie sesion antes de utilizar esta funcion.";
                echo "Hola";
            }                        
        } catch(\Exception $e){
            $response['msg'] = $e->getMessage();
            $response['status'] = 0;
            $response['msg'] = "Se ha producido un error: ".$e->getMessage();
        }

        return response()->json($response); 
    }

    /**
     * Modificar peril de usuario
     * Se pueden modificar los campos: name, email, salary y biography
     * Únicamente pueden modificar los usuarios directivos y de RRHH
     * Estos usuarios pueden modificar su propio perfil
     * Los usuarios directivos no pueden modificar otros usuarios directivos, pero sí pueden modificar a los usuarios de RRHH
     * Los usuarios de RRHH no pueden modificar otros usuarios de RRHH ni usuarios directivos
     * Requiere introducir el api_token del usuario que va a realizar la modificación y el ID de usuario que se va a modificar
     */
    public function modify(Request $req){
        $response = ["status" => 1, "msg" => ""];

        if($req->has('token')){
            $token = $req->input('token');
        } else {
            $token = "";
        }
        if($req->has('user')){
            $user = $req->input('user');
        } else {
            $user = "";
        }
        
        // Buscar el usuario que va a realizar la modificación
        $modifier = User::where('api_token', $token)->first();

        // Buscar el usuario a modificar
        $user = User::where('id', $req->user)->first();

        $data = $req->getContent();
        $data = json_decode($data);

        try {
            if(isset($user) && isset($modifier)){
                
                if (($modifier->role == "directive" && $user->role == "directive")
                || ($modifier->role == "hr" && $user->role == "hr")
                || ($modifier->role == "hr" && $user->role == "directive")){
                    $permission = false;
                    $response['status'] = 3;
                    $response['msg'] = "Acceso denegado, no se pueden modificar usuarios con un rol superior o igual al tuyo.";
                } else {
                    $permission = true;
                }
                if($modifier->id == $user->id){
                    $permission = true;
                }
                if($permission){
                    if(isset($data->name)){
                        $user->name = $data->name;
                        $dataChanged = true;
                     }

                    if(isset($data->email)){
                        $user->email = $data->email;
                        $dataChanged = true;
                    }

                    if(isset($data->salary)){
                        $user->salary = $data->salary;
                        $dataChanged = true;
                    }

                    if(isset($data->biography)){
                        $user->biography = $data->biography;
                        $dataChanged = true;
                    }

                    if ($dataChanged){
                        $user->save();
                        $response['msg'] = "Usuario modificado correctamente";
                    } else {
                        $response["msg"] = "No se modificaron datos";
                    }
                }
            } else if (!isset($modifier)){
                $response['status'] = 2;
                $response['msg'] = "Debes iniciar sesión para utilizar esta función.";
            } else if (!isset($user)){
                $response['status'] = 2;
                $response['msg'] = "Introduce un usuario a modificar correcto.";
            }
        }catch(\Exception $e){
            $response['msg'] = $e->getMessage();
            $response['status'] = 0;
            $response['msg'] = "Se ha producido un error: ".$e->getMessage();
        }

        return response()->json($response); 
    }

    /**
     * Comprobar si la contraseña es segura
     * Param: $password -> string
     */
    function CheckPassword($password){
        $pattern = '/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{6,}/';

        if(preg_match($pattern, $password)){
            return true;
        } else {
            return false;
        }
    }

    /**
     * Comprobar si se ha introducido un email válido
     * Param: $email -> string
     */
    function CheckEmail($email){
        if(filter_var($email, FILTER_VALIDATE_EMAIL)){
            return true;
        } else {
            return false;
        }
    }
}
