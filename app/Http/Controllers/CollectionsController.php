<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Collection;
use App\Models\Card;

class CollectionsController extends Controller
{
    // Registrar nueva coleccion
    public function register(Request $req){

        $msg = ["status" => 0, "msg" => "Algo ha fallado en el registro de la coleccion"];
                
        // JSON
        $data = $req->getContent();
        $data = json_decode($data);

        try {
            // NUEVA COLECCION
            $collection = new Collection();

            // Comprobar si la coleccion ya ha sido registrada con ese nombre
            $collectionExists = Collection::where('name', '=', $data->name)->first();

            // Variable para conocer si se utilizara una carta existente o una nueva
            $existingCard = false;

            // !! NUEVA COLECCION CON CARTA EXISTENTE !!
            if (isset($data->card_id) && !$collectionExists){
                // Comprobar si la carta existe
                $cardExists = Card::where('id', '=', $data->card_id)->first();
                $existingCard = true;
            }
            // !! NUEVA COLECCIÓN CON NUEVA CARTA !!
            else if (!isset($data->card_id) && !$collectionExists){
                // NUEVA CARTA
                $card = new Card();
                // Comprobar si la nueva carta ya ha sido registrada
                $cardExists = Card::where('name', '=', $data->card_name)->first();
                if(!$cardExists){ // Asignar valores del JSON a la primera nueva carta de la colección
                    $card->name = $data->card_name;
                    $card->description = $data->card_description;
                    $card->save();
                }
            }

            // CREAR COLECCION
            if(!$collectionExists){ // Asignar valores del JSON a la nueva colección
                $collection->name = $data->name;
                $collection->symbol = $data->symbol;
                $collection->edition_date = $data->edition_date;
                $collection->save();
            }
            // Añadir nueva carta a la nueva coleccion
            if(!$existingCard){
                DB::table('cards_collections')->insert([
                    'card_id' => $card->id,
                    'collection_id' => $collection->id,
                    'created_at' => \Carbon\Carbon::now(),
                    "updated_at" => \Carbon\Carbon::now()
                ]);
                $msg['status'] = 1;
                $msg['msg'] = "Coleccion ".$collection->name." y carta " .$card->name. " registradas correctamente";
            } else if ($existingCard){
                DB::table('cards_collections')->insert([
                    'card_id' => $cardExists->id,
                    'collection_id' => $collection->id,
                    'created_at' => \Carbon\Carbon::now(),
                    "updated_at" => \Carbon\Carbon::now()
                ]);
                $msg['status'] = 1;
                $msg['msg'] = "Coleccion ".$collection->name." registrada correctamente";
            }

            if($collectionExists){
                $msg['status'] = 0;
                $msg['msg'] = "No se pudo dar de alta la coleccion especificada, la coleccion ".$data->name." ya existe.";
            } else if($cardExists && !$existingCard){
                $msg['status'] = 0;
                $msg['msg'] = "No se pudo dar de alta la carta especificada, la carta ".$data->card_name." ya existe.";
            }
        } catch(\Exception $e){
            $msg['status'] = 0;
            $msg['msg'] = $e->getMessage();
        }
        return response()->json($msg);
    }
}
