<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\grupos;
use App\Models\alumnos_pertenecen_grupos;
use Carbon\Carbon;
use App\Http\Controllers\RegistrosController;

class gruposController extends Controller
{
    public function index()
    {

        return response()->json(grupos::all());
    }

    public function store(Request $request)
    {

        $request->validate([
            'nombreCompleto' => 'required|string',
            'idGrupo' => 'required|string',
            'anioElectivo' => 'required|max:4',
            'grado_id' => 'required|integer',
        ]);
        $grupo = grupos::where('idGrupo', $request->idGrupo)->first();
        if (empty($grupo)) {
            return $this->crearGrupo($request);
        }
        return response()->json(['error' => 'Grupo Existente'], 401);

    }


    public function show($id)
    {
        return response()->json(grupos::where('idGrupo', $id)->first());
    }

    public function destroy(Request $request, $id)
    {

        $grupo = grupos::where('idGrupo', $id)->first();
        if ($grupo) {
            self::eliminarProfesoresGrupo($request, $id);
            self::eliminarAlumnosGrupo($request, $id);
            RegistrosController::store("GRUPO", $request->header('token'), "DELETE", $request->idGrupo);
            $grupo->delete();
            return response()->json(['status' => 'Success'], 200);
        }
        return response()->json(['status' => 'Bad Request'], 400);
    }

    public function eliminarProfesoresGrupo($request, $id)
    {
        DB::table('grupos_tienen_profesor')
            ->where('idGrupo', $id)
            ->update(['deleted_at' => Carbon::now()]);
        RegistrosController::store("GRUPO PROFESOR", $request->header('token'), "DELETE", $request->idGrupo);
    }

    public function eliminarAlumnosGrupo($request, $id)
    {
        DB::table('alumnos_pertenecen_grupos')
            ->where('idGrupo', $id)
            ->update(['deleted_at' => Carbon::now()]);
        RegistrosController::store("GRUPO ALUMNOS", $request->header('token'), "DELETE", $request->idGrupo);
    }


    public function update(request $request, $id)
    {
        $grupo = grupos::where('idGrupo', $id)->first();
            if ($grupo) {
                $grupo->fill($request->all());
                $grupo->save();
                RegistrosController::store("GRUPO", $request->header('token'), "UPDATE", self::modifiedValue($grupo));
                return response()->json($grupo, 200);
            }
            return response()->json(['status' => 'Bad Request'], 400);
    }

    public function modifiedValue($grupo)
    {
        if ($grupo->isDirty('idGrupo') && !$grupo->isDirty('nombreCompleto')) {
            return $grupo->idGrupo . "-" . $grupo->getOriginal('idGrupo');
        }
        if ($grupo->isDirty('nombreCompleto') && !$grupo->isDirty('idGrupo')) {
            return $grupo->nombreCompleto . "-" . $grupo->getOriginal('nombreCompleto');
        }
        if ($grupo->isDirty('idGrupo') && $grupo->isDirty('nombreCompleto')) {
            return "Grupo Completo modificado";
        }
    }


    public function crearGrupo(Request $request)
    {
        $grupo = new grupos();
        $grupo->fill($request->all());
        $grupo->save();
        RegistrosController::store("GRUPO", $request->header('token'), "CREATE", $request->idGrupo);
        return response()->json($grupo);
    }


}
