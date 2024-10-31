<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TodoController extends Controller
{
    public function index(Request $request)
    {
        $query = Todo::query();

        if ($request->has('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'group_id' => 'required|exists:groups,id',
            'title' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    $existingTodo = Todo::where('title', $value)
                        ->where('group_id', $request->input('group_id'))
                        ->first();

                    if ($existingTodo) {
                        $fail('A todo with the same title and category already exists.');
                    }
                },
            ],
        ]);

        $todo = new Todo();
        $todo->title = $request->input('title');
        $todo->group_id = $request->input('group_id');
        $todo->save();

        return response()->json($todo, 201);
    }
    
    public function update(Request $request, Todo $todo)
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'completed' => 'sometimes|required|boolean',
            'group_id' => 'sometimes|required|exists:groups,id', 
        ]);
    
        if ($request->has('title') && $request->has('group_id')) {
            $existingTodo = Todo::where('title', $request->input('title'))
                                ->where('group_id', $request->input('group_id'))
                                ->where('id', '!=', $todo->id)
                                ->first();
    
            if ($existingTodo) {
                return response()->json([
                    'message' => 'A todo with the same title and group already exists.'
                ], 422);
            }
        }
    
        Log::info("Updating Todo", ['id' => $todo->id, 'new_group_id' => $request->input('group_id')]);
    
        $todo->update($request->only('title', 'completed', 'group_id'));
    
        return response()->json($todo);
    }
    

    public function destroy(Todo $todo)
    {
        $todo->delete();

        return response()->json(null, 204);
    }
}
