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

        $user->user = $data->user;
        $user->email = $data->email;
        $user->password = $data->password;    
        $user->role = $data->role;

        // COMPROBAR ENUM
        if($user->role == 'Particular' || $user->role == 'Profesional' || $user->role == 'Administrador'){
            $checkRole = true;
        } else {
            $checkRole = false;
        }

        // COMPROBAR SI SE HA INTRODUCIDO UN EMAIL VALIDO
        $checkEmail = $this->CheckEmail($user->email);

        // COMPROBAR SI LA CONTRASEÑA ES SEGURA Y CIFRARLA
        $checkPassword = $this->CheckPassword($user->password);
        if($checkPassword){
            $originalPassword = $user->password;
            $user->password = password_hash($user->password, PASSWORD_DEFAULT);            
        }

        // COMPROBAR SI EL USUARIO Y EL CORREO YA ESTAN REGISTRADOS
        $userExists = User::where('user', '=', $data->user)->first();
        $emailExists = User::where('email', '=', $data->email)->first();
        
        try {
            if($checkRole){
                if($checkEmail){
                    if($checkPassword){
                        if($userExists){
                            $msg['status'] = 0;
                            $msg['msg'] = "No se pudo crear el usuario especificado, el usuario ".$user->user." ya existe.";
                        } else if($emailExists){
                            $msg['status'] = 0;
                            $msg['msg'] = "No se pudo crear el usuario especificado, el correo ".$user->email." ya existe.";
                        } else {
                            $user->save();
                            $msg['status'] = 1;
                            $msg['msg'] = "Usuario ".$user->user." registrado correctamente";
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
        $response = ["status" => 0, "msg" => "Algo ha fallado en el inicio de sesión"];

        if($req->has('user')){
            $user = $req->input('user');
        } else {
            $user = "";
        }
        if($req->has('pswd')){
            $pswd = $req->input('pswd');
        } else {
            $pswd = "";
        }

        try {
            // Comprobar si existe el usuario (buscarlo en la BBDD)
            $checkEmail = DB::table('users')
                            ->where('user', '=', $user)
                            ->first();
            if($checkEmail){ // Si existe, comprobar contraseña
                $checkPswd = password_verify($pswd, $checkEmail->password);
            }
            if(!$checkEmail){ // Si no existe el usuario, imprimir mensaje...
                $response['status'] = 0;
                $response['msg'] = "Usuario no encontrado";
            } else if(!$checkPswd){ // Si la contraseña es incorrecta, imprimir mensaje...
                $response['status'] = 0;
                $response['msg'] = "Contraseña incorrecta";  
            } else if ($checkPswd){ // Si el usuario y contraseña son correctos, generar TOKEN de usuario
                $user = User::find($checkEmail->id);
                $user->api_token = Str::random(60);
                $user->save();
                // Mensaje de login con exito
                $response['status'] = 1;
                $response['msg'] = "Usuario logeado";
                $response['query'] = DB::table('users')
                            ->where('id', '=', $checkEmail->id)
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
        $response = ["status" => 0, "msg" => "Se ha producido un error al restablecer la contraseña."];

        if($req->has('email')){
            $email = $req->input('email');
        } else {
            $email = "";
        }

        $user = User::where('email', $req->email)->first();

        try{      
            if($user){
                $newPassword = $this->Random_str();
                $user->password = password_hash($newPassword, PASSWORD_DEFAULT);
                
                /* Enviar por email la nueva contraseña, al no disponer de servicio de correo, imprimir por consola...
                Mail::to($user->email)->send(new ResetPassword("Restablecer contraseña CardMarket-app",
                        "Nueva contraseña temporal", [
                            "Hola, " .$user->user,
                            "Tu nueva contraseña es: " .$newPassword,
                            "Al iniciar sesión nuevamente con esta contraseña se solicitará su cambio por una nueva.",
                            "Un saludo."
                        ]));
                */
                
                print("Nueva contraseña generada :" .$newPassword. "\n");
                $user->api_token = ""; // Eliminar token para cerrar sesion.
                $user -> save();
                $response["status"] = 1;
                $response["msg"] = "Se ha enviado una nueva contraseña temporal por email."; 
            } else {
                $response["status"] = 0;
                $response["msg"] = "No se ha encontrado el correo electronico introducido."; 
            }

            return response()->json($response);
        }catch(\Exception $e){
            $response['msg'] = $e->getMessage();
            $response['status'] = 0;
            $response['msg'] = "Se ha producido un error: ".$e->getMessage();
        }
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

    /**
     * Genera un string aleatorio, utilizando un generador de numeros
     * pseudoaleatorios criptograficamente seguro (random_int)
     * 
     * @param int $length      Longitud de caracteres
     * @param string $keyspace Un string con todos los posibles caracteres
     *                         para seleccionar
     * @return string
     */
    function Random_str(
        int $length = 32,
        string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ@!?.-_'
        ): string {
        if ($length < 1) {
            throw new \RangeException("La longitud debe ser un número entero positivo");
        }
        $pieces = [];
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $pieces []= $keyspace[random_int(0, $max)];
        }
        return implode('', $pieces);
    }
}