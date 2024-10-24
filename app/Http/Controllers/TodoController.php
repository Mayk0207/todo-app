<?php

namespace App\Http\Controllers;

use App\Models\Todo; 
use Illuminate\Http\Request;

class TodoController extends Controller
{
    public function index()
    {
        return Todo::all();
    }

    // Store 
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $todo = Todo::create([
            'title' => $request->title,
            'completed' => false, 
        ]);

        return response()->json($todo, 201);
    }

    // Update
    public function update(Request $request, Todo $todo)
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'completed' => 'sometimes|required|boolean',
        ]);

        $todo->update($request->only('title', 'completed'));

        return response()->json($todo);
    }

    // Delete
    public function destroy(Todo $todo)
    {
        $todo->delete();

        return response()->json(null, 204);
    }
}
