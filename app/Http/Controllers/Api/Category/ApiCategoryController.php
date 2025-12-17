<?php

namespace App\Http\Controllers\Api\Category;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\TicketCategory;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;

class ApiCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = TicketCategory::all();

        return response()->json([
            $categories
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryRequest $request)
    {

        try {
            $newCategory = TicketCategory::create([
                'name' => $request->name,
                'description' => $request->description ?? null,
            ]);

            return response()->json([
                'message' => 'Categoria creada con exito',
                'newCategory' => $newCategory
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ha habido un error al crear la categoria',
                $th
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = TicketCategory::find($id);

        if (! $category) {
            return response()->json([
                'message' => 'No se ha encontrado la categoría',
            ], 404);
        }

        return response()->json([
            'category' => $category,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */

    //pasandoele una cagtegoria en lugar de id ya no hace falta hacear un find ni comporbar si no esxtie la catagorya esto ya lo congtrla laravael. 
    //Pasandole el id es como se hace el metdoo changeCAtegroy. en laurl el paramaetro si el els id
    //{{base_url}}/api/category/update/8

    public function update(CategoryRequest $request, TicketCategory $category)
    {



        try {
            $category->name = $request->name;
            $category->description = $request->description ?? null;
            $category->save();

            return response()->json([
                'message'  => 'Se ha editado la categoría  correctamente',
                'category' => $category,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al editar la categoría',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    public function changeStatus(string $id)
    {
        $category = TicketCategory::find($id);

        if (! $category) {
            return response()->json([
                'message' => 'No se ha encontrado la categoría',
            ], 404);
        }

        try {
            $category->active = ! $category->active;
            $category->save();

            return response()->json([
                'message'  => 'Estado de la categoría cambiado correctamente',
                'category' => $category,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al cambiar el estado de la categoría',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
}
