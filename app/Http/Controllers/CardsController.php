<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Card;
use App\Models\Collection;

class CardsController extends Controller
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
        $card = new Card();

        try {
            // COMPROBAR SI LA CARTA YA HA SIDO REGISTRADA
            $cardExists = Card::where('name', '=', $data->name)->first();
            if(!$cardExists){
                $card->name = $data->name;
                $card->description = $data->description;
                // COMPRUEBA QUE LA COLECCION EXISTE
                $collectionExists = Collection::where('id', '=', $data->collection_id)->first();
            } if(!$collectionExists){
                $msg['status'] = 0;
                $msg['msg'] = "No se pudo dar de alta la carta especificada, el ID de coleccion ".$data->collection_id." no existe.";
            } else {
                $msg['status'] = 0;
                $msg['msg'] = "No se pudo dar de alta la carta especificada, la ".$data->name." ya existe.";
            }
            if(!$cardExists && $collectionExists){
                $card->save();
                DB::table('cards_collections')->insert([
                    'card_id' => $card->id,
                    'collection_id' => $collectionExists->id,
                    'created_at' => \Carbon\Carbon::now(),
                    "updated_at" => \Carbon\Carbon::now()
                ]);
                $msg['status'] = 1;
                $msg['msg'] = "Carta ".$card->name." registrada correctamente";
            }
        } catch(\Exception $e){
            $msg['status'] = 0;
            $msg['msg'] = $e->getMessage();
        }

        return response()->json($msg);
    }

    /**
     * Añade una carta a una colección existente
     */
    public function addCollection(Request $req){
        $response = ["status" => 0, "msg" => "Algo ha fallado en la modificación de la carta."];
        
        // JSON
        $data = $req->getContent();
        $data = json_decode($data);
        
        // Buscar la carta 
        $card = Card::where('id', $data->card_id)->first();
        // Buscar la coleccion a la que se quiere añadir la carta
        $collection = Collection::where('id', $data->collection_id)->first();

        if($card && $collection){
            // Comprobar si la carta ya tiene esa coleccion asignada
            $check = DB::table('cards_collections')
                ->where('cards_collections.card_id', '=', $data->card_id)
                ->where('cards_collections.collection_id', '=', $data->collection_id)
                ->first();
        }

        try {
            if($card && $collection && !$check){
                DB::table('cards_collections')->insert([
                    'card_id' => $card->id,
                    'collection_id' => $collection->id,
                    'created_at' => \Carbon\Carbon::now(),
                    "updated_at" => \Carbon\Carbon::now()
                ]);
                $response['status'] = 1;
                $response['msg'] = $card->name. " añadida a la coleccion " .$collection->name. " correctamente.";
            } else if (!$card && !$collection){
                $response['status'] = 0;
                $response['msg'] = "Los datos introducidos no son correctos.";
            } else if (!$card){
                $response['status'] = 0;
                $response['msg'] = "La carta introducida no existe.";
            } else if (!$collection){
                $response['status'] = 0;
                $response['msg'] = "La coleccion introducida no existe.";
            } else if ($check){
                $response['status'] = 0;
                $response['msg'] = "La carta " .$card->name. " ya forma parte de la coleccion " .$collection->name. ".";
            }
        }catch(\Exception $e){
            $response['msg'] = $e->getMessage();
            $response['status'] = 0;
            $response['msg'] = "Se ha producido un error inesperado: ".$e->getMessage();
        }

        return response()->json($response); 
    }

    /**
     * Busca una carta por nombre
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
            $query = DB::table('cards')
                        ->select('cards.name AS Nombre carta', 'cards.description AS Descripcion carta', 'cards.id AS ID carta', 'collections.name AS Nombre coleccion')
                        ->leftJoin('cards_collections', 'cards_collections.card_id', '=', 'cards.id')
                        ->leftJoin('collections', 'cards_collections.collection_id', '=', 'collections.id')
                        ->where('cards.name', 'LIKE', '%' .$filter. '%')
                        ->get();
                
                $response["status"] = 1;
                $response["msg"] = "Busqueda finalizada";
                $response["query"] = $query;
                //$response["collections"] = $collection;
        } catch(\Exception $e){
            $response["status"] = 0;
            $response["msg"] = $e->getMessage();
        }

        return response()->json($response);
    }
}

