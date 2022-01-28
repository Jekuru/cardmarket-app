<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Cardssale;
use App\Models\Card;
use App\Models\User;

class CardssalesController extends Controller
{
    /**
     * PONER A LA VENTA UN TIPO DE CARTA
     */ 
    public function sell(Request $req){

        $msg = ["status" => 0, "msg" => "Algo ha fallado durante el proceso de venta."];

        // CONOCER QUIÉN ES QUIEN PONE LA CARTA O CARTAS A LA VENTA
        if($req->has('token')){
            $token = $req->input('token');
        } else {
            $token = "";
        }
        $seller = User::where('api_token', '=', $token)->first(); // VENDEDOR
                
        // JSON
        $data = $req->getContent();
        $data = json_decode($data);
       
        // NUEVA ORDEN DE VENTA
        $newOrder = new Cardssale();

        $cardExists = Card::where('id', '=', $data->card_id)->first();
        

        try {            
            if($cardExists){
                // Asignar valores del JSON a la nueva orden de venta.
                $newOrder->card_name = $cardExists->name;
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

    /**
     * Busca una carta a la venta por nombre
     */
    public function search(Request $req){
        $response = ["status" => 0, "msg" => "Algo ha fallado en la busqueda de la carta."];

        if($req->has('filter')){
            $filter = $req->input('filter');
        } else {
            $filter = "";
        }

        // Se puede utilizar el parametro "filter" para buscar una carta concreta
        try {
            $cards = DB::table('cardssales')
                        ->select('cardssales.card_name AS Carta', 'cardssales.quantity AS Cantidad', 'cardssales.price AS Precio total', 'cardssales.user_users AS Vendedor')
                        ->where('cardssales.card_name', 'LIKE', '%' .$filter. '%')
                        ->orderBy('cardssales.price', 'asc')
                        ->get();
            if($cards){
                $response["status"] = 1;
                $response["msg"] = $cards;
            }
        } catch(\Exception $e){
            $response["status"] = 0;
            $response["msg"] = $e->getMessage();
        }

        return response()->json($response);
    }
}
