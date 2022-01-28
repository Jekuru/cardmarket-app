<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Collection;
use App\Models\Card;

class CollectionsController extends Controller
{
    //
    public function register(Request $req){

        $msg = ["status" => 0, "msg" => "Algo ha fallado en el registro de la coleccion"];
                
        // JSON
        $data = $req->getContent();
        $data = json_decode($data);
       
        // NUEVA COLECCION
        $collection = new Collection();
        // NUEVA CARTA
        $card = new Card();
        try {
            // COMPROBAR SI LA COLECCION Y CARTA YA HAN SIDO REGISTRADAS
            $collectionExists = Collection::where('name', '=', $data->name)->first();
            $cardExists = Card::where('name', '=', $data->card_name)->first();
            
            if(!$collectionExists && !$cardExists){
                // Asignar valores del JSON a la nueva colección
                $collection->name = $data->name;
                $collection->symbol = $data->symbol;
                $collection->edition_date = $data->edition_date;
                // Asignar valores del JSON a la primera nueva carta de la colección
                $card->name = $data->card_name;
                $card->description = $data->card_description;
                
            } if($collectionExists){
                $msg['status'] = 0;
                $msg['msg'] = "No se pudo dar de alta la coleccion especificada, la coleccion ".$data->name." ya existe.";
            } else if($cardExists){
                $msg['status'] = 0;
                $msg['msg'] = "No se pudo dar de alta la carta especificada, la carta ".$data->card_name." ya existe.";
            }
            if(!$collectionExists && !$cardExists){
                $collection->save();
                $card->save();
                $registeredCollection = Collection::where('name', '=', $collection->name)->first();
                $registeredCard = Card::where('name', '=', $card->name)->first();
                DB::table('cards_collections')->insert([
                    'card_id' => $card->id,
                    'collection_id' => $collection->id,
                    'created_at' => \Carbon\Carbon::now(),
                    "updated_at" => \Carbon\Carbon::now()
                ]);
                $msg['status'] = 1;
                $msg['msg'] = "Coleccion ".$collection->name." y carta " .$card->name. " registradas correctamente";
            }
        } catch(\Exception $e){
            $msg['status'] = 0;
            $msg['msg'] = $e->getMessage();
        }
        return response()->json($msg);
    }
}
