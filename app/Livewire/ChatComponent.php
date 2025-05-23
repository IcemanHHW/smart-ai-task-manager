<?php

namespace App\Livewire;

use Http;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\ConnectionException;
use Livewire\Attributes\On;
use Prism\Prism\Facades\Tool;
use Prism\Prism\Enums\Provider;
use Livewire\Component;
use Prism\Prism\Prism;

class ChatComponent extends Component
{
    public $todos = [];
    public $key = null;

    /**
     * @throws ConnectionException
     */
    public function mount(): void
    {
        $this->fetchTodos();
    }

    /**
     * @throws ConnectionException
     */
    public function fetchTodos(): void
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('sanctum.token'),
            'Accept' => 'application/json',
        ])->get('https://smart-ai-task-manager.test/api/todos');

        $this->todos = $response->json('todos') ?? [];
    }

    /**
     * @throws ConnectionException
     */
    public function toggleTodo($todoId): void
    {
       $todoId = (int) $todoId;

       foreach($this->todos as $key => $todo) {
           if ($todo['id'] === $todoId) {
                   $this->todos[$key]['completed'] = !$todo['completed'];
                   $this->key = $key;
                   break;
           }
       }

        Http::withHeaders([
            'Authorization' => 'Bearer ' . config('sanctum.token'),
            'Accept' => 'application/json',
        ])->patch('https://smart-ai-task-manager.test/api/todos/'.$todoId, [
            'completed' => !$this->todos[$this->key]['completed'],
        ]);
    }

    public function render(): View|Application|Factory
    {
        return view('livewire.chat-component');
    }
}
