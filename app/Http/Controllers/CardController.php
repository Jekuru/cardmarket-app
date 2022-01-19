<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CardController extends Controller
{
    //
    /**
     * Registrar nueva carta
     */
    public function register(Request $req){

        $msg = ["status" => 0, "msg" => "Algo ha fallado en el registro de la carta"];
                
        // JSON
        $data = $req->getContent();
        $data = json_decode($data);
       
        // NUEVA CARTA
        $card = new User();

        try {
            // COMPROBAR SI LA CARTA YA HA SIDO REGISTRADA
            $cardExists = Card::where('name', '=', $data->name)->first();
            if(!$cardExists){
                $card->name = $data->name;
                $card->description = $data->description;
                // COMPRUEBA QUE LA COLECCION EXISTE
                $collectionExists = Collection::where('id', '=', $data->collection_id);
                if($collectionExists)
                    $card->collection_id = $data->collection_id;
            } if($collectionExists){
                $msg['status'] = 0;
                $msg['msg'] = "No se pudo dar de alta la carta especificada, el ID de coleccion ".$data->collection_id." no existe.";
            } else {
                $msg['status'] = 0;
                $msg['msg'] = "No se pudo dar de alta la carta especificada, la ".$data->name." ya existe.";
            }
            if(!$cardExists && $collectionExists){
                $card->save();
                $msg['status'] = 1;
                $msg['msg'] = "Carta ".$card->name." registrada correctamente";
            }
        } catch(\Exception $e){
            $msg['status'] = 0;
            $msg['msg'] = $e->getMessage();
        }

        return response()->json($msg);
    }
}
