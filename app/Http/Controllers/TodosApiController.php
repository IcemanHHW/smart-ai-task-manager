<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TodosApiController extends Controller
{

    /**
     * Retrieve and return a list of todos associated with the authenticated user.
     * @return JsonResponse
     */
    public function index()
    {
        $todos = auth()->user()->todos()->get();

        return response()->json([
            'todos' => $todos,
        ]);
    }

    /**
     * Validate and store a new todo item
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $todo = auth()->user()->todos()->create([
            'title' => $request->title,
        ]);

        return response()->json([
            'todo' => $todo,
        ]);
    }

    /**
     * Update the completed status of a specified todo item.
     *
     * @param Request $request
     * @param Todo $todo
     * @return JsonResponse
     */
    public function update(Request $request, Todo $todo)
    {
        $request->validate([
            'completed' => 'required|boolean',
        ]);

        $todo->update([
            'completed' => $request->completed,
        ]);

        return response()->json([
            'todo' => $todo,
        ]);
    }
}
