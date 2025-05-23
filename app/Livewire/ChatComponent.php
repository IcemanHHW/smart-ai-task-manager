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
    public $messages = [];
    public $isTyping = false;
    public $input = '';
    public $streamedResponse = '';
    public $streamResponse = '';

    /**
     * Initialize the component and fetch todos.
     *
     * @return void
     * @throws ConnectionException
     */
    public function mount(): void
    {
        $this->fetchTodos();
    }

    /**
     * Fetches a list of todos from the remote API and updates the local todos property.
     *
     * @throws ConnectionException If there is an issue connecting to the remote API.
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
     * Toggle the completion status of a specific todo item.
     *
     * Updates the local storage of todos and sends a patch request to
     * an external API with the updated completion status of the todo item.
     *
     * @param int $todoId
     * @return void
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

    /**
     * Sends a user message and triggers an AI response.
     *
     * This method updates the internal messages array with the user's input,
     * resets the input field, marks the typing state as active, and clears
     * any previously streamed responses. It also dispatches events to update
     * the UI and invoke an AI response.
     *
     * @return void
     */
    public function send(): void
    {
        // Add the user message to the messages array immediately
        $prompt = $this->input;
        $this->messages[] = [
            'user' => true,
            'text' => $prompt,
        ];
        $this->input = '';

        // Reset streamed response and set typing state
        $this->streamedResponse = '';
        $this->isTyping = true;

        // Dispatch UI updates first
        $this->dispatch('scroll-down');

        //Dispatch the event to trigger the AI response
        $this->dispatch('getAiResponse', $prompt);
    }

    /**
     * Handle the "getAiResponse" event by streaming the AI response.
     *
     * Processes the given prompt and streams the generated AI response
     * using the appropriate method.
     *
     * @param string $prompt The input text for generating an AI response.
     * @return void
     */
    #[On('getAiResponse')]
    public function getAiResponse(string $prompt): void
    {
        $this->streamResponse($prompt);
    }


    /**
     * Process and stream a response based on the provided prompt.
     *
     * This method utilizes a custom tool for creating todo items and integrates with
     * an AI provider to generate a streamed response. The response is processed in
     * chunks, streamed to the client, and accumulated for further storage. The completion
     * of the stream updates the application's internal message state and resets stream-related
     * properties.
     *
     * @param string $prompt The prompt to guide the AI's response generation.
     * @return void
     */
    public function streamResponse(string $prompt): void
    {
        $todoTool = Tool::as('todos')
            ->for('Create new todos')
            ->withStringParameter('todo', 'The title for the todo')
            ->using(function (string $todo): string {
                Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('sanctum.token'),
                    'Accept' => 'application/json'
                ])->post('http://laravel-prismphp-tools-example.test/api/todos', [
                    'title' => $todo,
                ]);

                // Refresh todos after creating a new one
                $this->fetchTodos();

                return "The new todo '{$todo}' was created!";
            });

        $stream = Prism::text()
            ->using(Provider::OpenAI, 'gpt-4')
            ->withMaxSteps(2)
            ->withPrompt($prompt)
            ->withTools([$todoTool])
            ->asStream();

        foreach ($stream as $chunk) {
            // Stream only new chunk text
            $this->stream('response', $chunk->text);

            // Accumulate full response for storage
            $this->streamedResponse .= $chunk->text;
        }

        // Add the completed response to the messages array
        $this->messages[] = ['user' => 'AI', 'text' => $this->streamedResponse];

        // Reset streaming state
        $this->streamedResponse = '';
        $this->isTyping = false;

        $this->dispatch('scroll-down');
    }

    /**
     * Render the Livewire chat component view.
     *
     * This method is responsible for returning the view associated
     * with the Livewire chat component.
     *
     * @return View|Application|Factory
     */
    public function render(): View|Application|Factory
    {
        return view('livewire.chat-component');
    }
}
