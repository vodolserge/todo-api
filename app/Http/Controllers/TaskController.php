<?php

namespace App\Http\Controllers;

use \Illuminate\Contracts\Foundation\Application as ApplicationContracts;
use \Illuminate\Contracts\View\Factory as Factory;
use \Illuminate\Contracts\View\View as View;
use \Illuminate\Foundation\Application as Application;

use App\Models\Task;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

/**
 * Implements CRUD logic for Task entity.
 */
class TaskController extends Controller
{
    /**
     * Gets all tasks with nested subtasks.
     *
     * @param Request $request Request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $customMessages = [
            'title.not_regex' => 'Field `title` have invalid characters.',
        ];

        $request->validate([
            'status'   => 'nullable|in:todo,done',
            'priority' => 'nullable|integer|min:1|max:5',
            'title'    => 'nullable|string|not_regex:/[№%@!%^&*,]/',
        ], $customMessages);

        $query = Task::query()->whereNull('parent_id')->with('childrenRecursive');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('title')) {
            $query->where('title', 'LIKE', '%' . strtolower($request->input('title')) . '%');
        }

        $tasks = $query->get();

        return response()->json($tasks);
    }

    /**
     * Shows `create` view.
     *
     * @return ApplicationContracts|Factory|View|Application
     */
    public function create()
    {
        $tasks = Task::all();

        if ($tasks->isEmpty()) {
            $tasks = [];
        }

        return view('tasks.create', compact('tasks'));
    }

    /**
     * Performs task creation.
     *
     * @param Request $request Request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'status'       => 'required|in:todo,done',
            'priority'     => 'required|integer|min:1|max:5',
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'parent_id'    => 'nullable|exists:tasks,id',
            'created_at'   => 'nullable|date',
            'completed_at' => 'nullable|date',
        ]);

        $data = $request->all();
        $data['user_id'] = auth()->id();

        $task = Task::create($data);

        return response()->json([
            'message' => 'Task created successfully.',
            'task'    => $task,
        ], 201);
    }

    /**
     * Shows `edit` view.
     *
     * @param Task $task
     *
     * @return ApplicationContracts|Factory|View|Application
     */
    public function edit(Task $task)
    {
        return view('tasks.edit', compact('task'));
    }

    /**
     * Performs update operation for task by ID.
     *
     * @param Request $request Request
     * @param string  $taskId Task ID
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $taskId)
    {
        try {
            $task = Task::findOrFail($taskId);
            $user = auth()->user();

            if (!$user || $user->id !== $task->user_id) {
                return response()->json(['error' => 'You do not have permission to edit this task.'], 403);
            }

            if ($request->status === 'done' && $task->children()->where('status', '!=', 'done')->exists()) {
                return response()->json(['error' => 'This task has unresolved subtasks.'], 400);
            }

            $request->validate([
                'status'       => 'required|in:todo,done',
                'priority'     => 'required|integer|min:1|max:5',
                'title'        => 'required|string|max:255',
                'description'  => 'nullable|string',
                'created_at'   => 'nullable|date',
                'completed_at' => 'nullable|date',
            ]);

            $data = $request->all();

            if ($request->input('status') === 'done') {
                $data['completed'] = true;
                $data['completed_at'] = now();
            } elseif ($request->input('status') === 'todo') {
                $data['completed'] = false;
                $data['completed_at'] = null;
            }

            $task->update($data);

            return response()->json(['success' => 'Task updated successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Task not found'], 404);
        }
    }

    /**
     * Performs delete operation for task by ID.
     *
     * @param string $taskId Task ID
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $taskId)
    {
        try {
            $task = Task::findOrFail($taskId);
            $user = auth()->user();

            if (!$user || $user->id !== $task->user_id) {
                return response()->json(['error' => 'You do not have permission to delete this task'], 403);
            }

            if ($task->completed) {
                return response()->json(['error' => 'Completed tasks cannot be deleted'], 422);
            }

            $hasIncompleteSubtasks = Task::where('parent_id', $task->id)->where('completed', false)->exists();
            if ($hasIncompleteSubtasks) {
                return response()->json(['error' => 'You cannot delete a task that has incomplete subtasks'], 422);
            }

            $task->delete();

            return response()->json(['success' => 'Task deleted successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Task not found'], 404);
        }
    }
}
