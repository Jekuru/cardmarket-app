<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Cardssale;
use App\Models\Card;
use App\Models\User;

class CardssalesController extends Controller
{
    // PONER A LA VENTA UN TIPO DE CARTA
    public function sell(Request $req){

        $msg = ["status" => 0, "msg" => "Algo ha fallado durante el proceso de venta."];

        // CONOCER QUIÉN ES QUIEN PONE LA CARTA O CARTAS A LA VENTA
        if($req->has('token')){
            $token = $req->input('token');
        } else {
            $token = "";
        }
        $seller = User::where('api_token', '=', $token)->first();
                
        // JSON
        $data = $req->getContent();
        $data = json_decode($data);
       
        // NUEVA ORDEN DE VENTA
        $newOrder = new Cardssale();

        $cardExists = Card::where('name', '=', $data->name)->first();
        

        try {            
            if($cardExists){
                // Asignar valores del JSON a la nueva orden de venta.
                $newOrder->card_name = $data->name;
                $newOrder->quantity = $data->quantity;
                $newOrder->price = $data->price;
                $newOrder->user_users = $seller->user;
                $newOrder->save();
                $msg['status'] = 1;
                $msg['msg'] = "Orden de venta registrada correctamente";
            } else {
                $msg['status'] = 0;
                $msg['msg'] = "No se pudo dar de alta la carta especificada, la carta '".$data->name."' no existe en nuestra base de datos.";
            }
        } catch(\Exception $e){
            $msg['status'] = 0;
            $msg['msg'] = $e->getMessage();
        }
        return response()->json($msg);
    }
}
