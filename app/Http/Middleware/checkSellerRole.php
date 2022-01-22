<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class checkSellerRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // COMPRUEBA EL ROL DEL TOKEN INTRODUCIDO
        $response = ["status" => 0, "msg" => "Error en middleware"];

        if($request->has('token')){
            $token = $request->input('token');
        } else {
            $token = "";
        }

        try {
            if($token != ""){
                $allow = DB::table('users')
                                ->where('api_token', '=', $token)
                                ->where('role', '=', 'Particular')
                                ->orWhere('role', '=', 'Profesional')
                                ->first();
                
                // SI ES ADMINISTRADOR SE CONCEDE ACCESO AL CONTROLADOR
                if($allow){
                    $response["status"] = 1;
                    $response["msg"] = "Usuario ". $allow->role. ", acceso concedido.";
                    return $next($request);
                } else {
                    $response["status"] = 0;
                    $response["msg"] = "Necesitas un usuario 'Particular' o 'Profesional' para realizar esta funcion.";
                }
            } else {
                $response["status"] = 0;
                $response["msg"] = "Inicie sesion para acceder a esta funcion.";
            }
        }catch(\Exception $e){
            $respuesta["status"] = 0;
            $respuesta["msg"] = "Se ha producido un error: ".$e->getMessage();
        }
            return response()->json($response);
    }
}